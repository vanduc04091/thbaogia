<?php
require_once __DIR__ . '/../DAL/BG_Bo_DAL.php';
require_once __DIR__ . '/../DAL/BG_HangHoa_DAL.php';
require_once __DIR__ . '/../DAL/BG_GoiThau_DAL.php';
require_once __DIR__ . '/../DAL/DM_NhatKyHeThong_DAL.php';
require_once __DIR__ . '/../PUBLIC/Entities/BG_Nhom_PUBLIC.php';
require_once __DIR__ . '/BG_GoiThau_BUS.php';   // kiemTraChuaChotSo()

/**
 * BG_Bo_BUS — Bộ / phần / hệ thống trong 1 gói thầu (Phụ lục III Thư mời).
 *
 * Yêu cầu chung / khác / cấu hình gắn với BỘ, còn yêu cầu kỹ thuật gắn với
 * từng hàng hóa chi tiết. Nhóm nào dùng cột nào — hỏi BG_Nhom_PUBLIC, KHÔNG
 * tự viết lại điều kiện ở đây.
 */
class BG_Bo_BUS
{
    const MODULE_KEY = 'BG_HangHoa';   // dùng chung quyền với màn hình hàng hóa
    const MODULE_LOG = 'BaoGia';

    /**
     * Sinh mã bộ tiếp theo cho gói thầu: BO01, BO02, ...
     *
     * Dùng khuôn BO + số cho MỌI nhóm, không theo tiền tố nhóm (BDC/HT/VT):
     * gói thầu có thể đổi nhóm, lúc đó mã theo nhóm cũ sẽ sai ngữ nghĩa mà
     * không sửa được (mã đã in ra giấy, nhà thầu đã điền theo).
     *
     * Vòng lặp phòng trường hợp mã BOxx đã bị người dùng đặt tay cho bộ khác.
     */
    public static function sinhMaBo(int $goiThauId): string
    {
        $n = BG_Bo_DAL::soThuTuMaLonNhat($goiThauId);
        do {
            $n++;
            $ma = 'BO' . str_pad((string)$n, 2, '0', STR_PAD_LEFT);
        } while (BG_Bo_DAL::maTonTai($goiThauId, $ma));
        return $ma;
    }

    private static function validate(BG_Bo_PUBLIC $e): string
    {
        $e->ten_bo = trim((string)$e->ten_bo);
        $e->ma_bo  = trim((string)$e->ma_bo);

        if ($e->goi_thau_id <= 0)      return 'Chưa chọn gói thầu';
        if ($e->ten_bo === '')         return 'Tên bộ không được để trống';
        if (mb_strlen($e->ten_bo) > 1000) return 'Tên bộ tối đa 1000 ký tự';
        if (mb_strlen($e->ma_bo) > 50)    return 'Mã bộ tối đa 50 ký tự';
        if ($e->so_luong < 0)          return 'Số lượng không được âm';
        if ($e->so_luong > 99999999)   return 'Số lượng quá lớn';

        $gt = BG_GoiThau_DAL::getById($e->goi_thau_id);
        if (!$gt || (int)$gt->da_xoa === 1) return 'Gói thầu không tồn tại';

        // Nhóm mua theo BỘ: số lượng bộ phải > 0, nếu không thì không dựng
        // được giá cả bộ (cùng luật với hàng hóa chi tiết).
        $nhom = BG_Nhom_PUBLIC::chuanHoa($gt->nhom ?? null);
        if ($nhom !== BG_Nhom_PUBLIC::VAT_TU_DUOC && $e->so_luong <= 0) {
            return 'Nhóm ' . BG_Nhom_PUBLIC::tenNhom($nhom)
                 . ' mua theo bộ — Số lượng phải lớn hơn 0';
        }

        // Cột không thuộc nhóm này thì XÓA hẳn, không lưu rác: người dùng đổi
        // nhóm gói thầu sau đó sẽ thấy dữ liệu thừa xuất hiện lại ở file mẫu.
        if (!BG_Nhom_PUBLIC::coYeuCauChung($nhom))   $e->yeu_cau_chung = null;
        if (!BG_Nhom_PUBLIC::coYeuCauKhac($nhom))    $e->yeu_cau_khac = null;
        if (!BG_Nhom_PUBLIC::coYeuCauCauHinh($nhom)) $e->yeu_cau_cau_hinh = null;

        return '';
    }

    public static function insert(BG_Bo_PUBLIC $e): array
    {
        $err = self::validate($e);
        if ($err !== '') return ['success' => false, 'message' => $err];

        $chot = BG_GoiThau_BUS::kiemTraChuaChotSo($e->goi_thau_id);
        if (!$chot['ok']) return ['success' => false, 'message' => $chot['message']];

        try {
            $ds = BG_Bo_DAL::getByGoiThau($e->goi_thau_id);
            if ($e->thu_tu <= 0) $e->thu_tu = count($ds) + 1;
            if ($e->stt_bo === null || $e->stt_bo <= 0) {
                $max = 0;
                foreach ($ds as $b) $max = max($max, (int)($b['stt_bo'] ?? 0));
                $e->stt_bo = $max + 1;
            }

            // Bỏ trống Mã bộ -> TỰ SINH. Mã bộ là thứ duy nhất để đối chiếu
            // dòng BỘ khi nhà thầu import file; bộ không có mã thì dữ liệu
            // nhà thầu điền ở dòng đó từng bị bỏ qua im lặng (§ sự cố đã gặp).
            if (trim((string)$e->ma_bo) === '') {
                $e->ma_bo = self::sinhMaBo($e->goi_thau_id);
            }

            $id = BG_Bo_DAL::insert($e);
            DM_NhatKyHeThong_DAL::log(
                $e->nguoi_tao ?? 0, self::MODULE_LOG,
                "Thêm bộ: {$e->ten_bo}", 'bg_bo', $id
            );
            return ['success' => true, 'message' => 'Đã thêm bộ', 'data' => ['id' => $id]];
        } catch (Throwable $ex) {
            return ['success' => false, 'message' => 'Lỗi: ' . $ex->getMessage()];
        }
    }

    public static function update(BG_Bo_PUBLIC $e): array
    {
        if (!$e->id) return ['success' => false, 'message' => 'Thiếu ID'];

        $cu = BG_Bo_DAL::getById((int)$e->id);
        if (!$cu || (int)$cu->da_xoa === 1) return ['success' => false, 'message' => 'Không tìm thấy bộ'];

        // Không cho chuyển bộ sang gói thầu khác — hàng hóa con vẫn ở gói cũ
        $e->goi_thau_id = (int)$cu->goi_thau_id;

        $err = self::validate($e);
        if ($err !== '') return ['success' => false, 'message' => $err];

        $chot = BG_GoiThau_BUS::kiemTraChuaChotSo($e->goi_thau_id);
        if (!$chot['ok']) return ['success' => false, 'message' => $chot['message']];

        try {
            if ($e->thu_tu <= 0) $e->thu_tu = (int)$cu->thu_tu;
            BG_Bo_DAL::update($e);
            DM_NhatKyHeThong_DAL::log(
                $e->nguoi_cap_nhat ?? 0, self::MODULE_LOG,
                "Sửa bộ: {$e->ten_bo}", 'bg_bo', (int)$e->id
            );
            return ['success' => true, 'message' => 'Đã cập nhật bộ'];
        } catch (Throwable $ex) {
            return ['success' => false, 'message' => 'Lỗi: ' . $ex->getMessage()];
        }
    }

    /**
     * Xóa bộ — CHỈ khi bộ đã rỗng.
     *
     * Xóa bộ còn hàng hóa sẽ để lại hàng trỏ tới bo_id không còn tồn tại:
     * chúng biến mất khỏi cây bộ nhưng vẫn nằm trong bảng tổng hợp → lệch số.
     * Bắt người dùng chuyển/xóa hàng trước cho rõ ý định.
     */
    public static function trash(int $id, int $u): array
    {
        if ($id <= 0) return ['success' => false, 'message' => 'Thiếu ID'];

        $bo = BG_Bo_DAL::getById($id);
        if (!$bo || (int)$bo->da_xoa === 1) return ['success' => false, 'message' => 'Không tìm thấy bộ'];

        $chot = BG_GoiThau_BUS::kiemTraChuaChotSo((int)$bo->goi_thau_id);
        if (!$chot['ok']) return ['success' => false, 'message' => $chot['message']];

        $soCt = (int)($bo->so_chi_tiet ?? 0);
        if ($soCt > 0) {
            return [
                'success' => false,
                'message' => "Bộ này còn {$soCt} hàng hóa chi tiết — hãy chuyển chúng sang bộ khác "
                           . '(hoặc đổi thành hàng lẻ) rồi mới xóa bộ.',
            ];
        }

        BG_Bo_DAL::softDelete($id, $u);
        DM_NhatKyHeThong_DAL::log(
            $u, self::MODULE_LOG, "Xóa bộ: {$bo->ten_bo}", 'bg_bo', $id
        );
        return ['success' => true, 'message' => 'Đã xóa bộ'];
    }

    public static function getById(int $id): ?BG_Bo_PUBLIC
    {
        return BG_Bo_DAL::getById($id);
    }

    public static function getByGoiThau(int $goiThauId): array
    {
        return BG_Bo_DAL::getByGoiThau($goiThauId);
    }
}
