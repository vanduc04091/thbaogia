<?php
require_once __DIR__ . '/../DAL/BG_HangHoa_DAL.php';
require_once __DIR__ . '/../DAL/BG_Bo_DAL.php';
require_once __DIR__ . '/../PUBLIC/Entities/BG_Nhom_PUBLIC.php';
require_once __DIR__ . '/../DAL/BG_GoiThau_DAL.php';
require_once __DIR__ . '/../DAL/DM_NhatKyHeThong_DAL.php';
require_once __DIR__ . '/../PUBLIC/Common/ExcelHelper.php';
require_once __DIR__ . '/BG_GoiThau_BUS.php';   // kiemTraChuaChotSo()

class BG_HangHoa_BUS
{
    const MODULE_KEY = 'BG_HangHoa';
    const MODULE_LOG = 'BaoGia';

    /** Dòng đầu tiên chứa dữ liệu trong file mẫu (1=header, 2-4=hướng dẫn) */
    const EXCEL_DATA_ROW = 5;

    /** Số dòng insert mỗi lô — tránh vượt giới hạn placeholder của MySQL */
    const BATCH_SIZE = 100;

    /**
     * Chỉ số cột (0-based) file mẫu danh mục — theo **Phụ lục III Thư mời
     * báo giá chung 3 nhóm**, đúng 12 cột:
     *
     *   A: Mã bộ/phần/hệ thống/hàng hóa/dụng cụ chi tiết
     *   B: STT bộ/phần/hệ thống      C: Tên bộ/phần/hệ thống
     *   D: STT chi tiết              E: Tên danh mục hàng hóa/dụng cụ chi tiết
     *   F: Yêu cầu chung             G: Yêu cầu khác
     *   H: Yêu cầu cấu hình          I: Yêu cầu kỹ thuật
     *   J: Yêu cầu nhóm nước, vùng lãnh thổ
     *   K: ĐVT                       L: Số lượng
     *
     * Phân biệt 2 loại dòng:
     *   - Dòng BỘ     : có STT bộ (cột B) → mở một bộ mới
     *   - Dòng CHI TIẾT: có STT chi tiết (cột D) → hàng hóa thuộc bộ đang mở
     * Dòng vừa có B vừa có D = hàng lẻ (vd vật tư "Bơm tiêm"): tự thành
     * 1 bộ chứa đúng 1 chi tiết.
     */
    const COL_MA            = 0;   // A
    const COL_STT_BO        = 1;   // B
    const COL_TEN_BO        = 2;   // C
    const COL_STT_CT        = 3;   // D
    const COL_TEN_HANG_HOA  = 4;   // E
    const COL_YC_CHUNG      = 5;   // F
    const COL_YC_KHAC       = 6;   // G
    const COL_YC_CAU_HINH   = 7;   // H
    const COL_THONG_SO      = 8;   // I  (yêu cầu kỹ thuật)
    const COL_NHOM_NUOC     = 9;   // J
    const COL_DVT           = 10;  // K
    const COL_SO_LUONG      = 11;  // L

    private static function validate(BG_HangHoa_PUBLIC $e, bool $isUpdate = false): string
    {
        $e->ten_hang_hoa = trim($e->ten_hang_hoa);
        $e->ma_hh        = trim((string)$e->ma_hh);

        if ($e->goi_thau_id <= 0) return 'Chưa chọn gói thầu';
        if ($e->ten_hang_hoa === '') return 'Tên hàng hóa không được để trống';
        if (mb_strlen($e->ten_hang_hoa) > 1000) return 'Tên hàng hóa tối đa 1000 ký tự';
        if ($e->so_luong < 0) return 'Số lượng không được âm';
        if ($e->so_luong > 99999999) return 'Số lượng quá lớn';

        // Nhóm mua theo BỘ: thiếu SL của 1 chi tiết là không dựng được giá cả
        // bộ. Chặn ở đây để nhập tay và import cùng một luật (§3B.1).
        $gtNhom = BG_GoiThau_DAL::getById($e->goi_thau_id);
        $nhom = BG_Nhom_PUBLIC::chuanHoa($gtNhom->nhom ?? null);
        if ($nhom !== BG_Nhom_PUBLIC::VAT_TU_DUOC && $e->so_luong <= 0) {
            return 'Nhóm ' . BG_Nhom_PUBLIC::tenNhom($nhom)
                 . ' mua theo bộ — Số lượng phải lớn hơn 0';
        }

        // bo_id phải là bộ CÓ THẬT trong ĐÚNG gói thầu này — không tin input
        if ($e->bo_id !== null && (int)$e->bo_id > 0) {
            $bo = BG_Bo_DAL::getById((int)$e->bo_id);
            if (!$bo || (int)$bo->da_xoa === 1 || (int)$bo->goi_thau_id !== $e->goi_thau_id) {
                return 'Bộ không hợp lệ hoặc không thuộc gói thầu này';
            }
        }

        // Mã HH bỏ trống → tự sinh HH001, HH002... theo gói thầu
        if ($e->ma_hh === '') {
            $e->ma_hh = 'HH' . str_pad(
                (string)(BG_HangHoa_DAL::soThuTuMaLonNhat($e->goi_thau_id) + 1), 3, '0', STR_PAD_LEFT
            );
        }
        if (mb_strlen($e->ma_hh) > 50) return 'Mã HH tối đa 50 ký tự';

        // Trùng mã trong cùng gói thầu → nhà thầu không biết chào cho hàng nào
        $excludeId = $isUpdate ? (int)$e->id : 0;
        if (BG_HangHoa_DAL::maHhExists($e->ma_hh, $e->goi_thau_id, $excludeId)) {
            return 'Mã HH "' . $e->ma_hh . '" đã tồn tại trong gói thầu này';
        }
        return '';
    }

    public static function insert(BG_HangHoa_PUBLIC $e): array
    {
        $err = self::validate($e);
        if ($err !== '') return ['success' => false, 'message' => $err];

        $gt = BG_GoiThau_DAL::getById($e->goi_thau_id);
        if (!$gt || $gt->da_xoa === 1) return ['success' => false, 'message' => 'Gói thầu không tồn tại'];

        try {
            if ($e->thu_tu <= 0) {
                $e->thu_tu = BG_HangHoa_DAL::maxThuTu($e->goi_thau_id) + 1;
            }
            $id = BG_HangHoa_DAL::insert($e);
            DM_NhatKyHeThong_DAL::log(
                $e->nguoi_tao ?? 0, self::MODULE_LOG,
                "Thêm hàng hóa vào gói {$gt->so_thong_bao}: {$e->ten_hang_hoa}", 'bg_hang_hoa', $id
            );
            return ['success' => true, 'message' => 'Thêm hàng hóa thành công', 'data' => ['id' => $id]];
        } catch (Throwable $ex) {
            return ['success' => false, 'message' => 'Lỗi: ' . $ex->getMessage()];
        }
    }

    public static function update(BG_HangHoa_PUBLIC $e): array
    {
        if (!$e->id) return ['success' => false, 'message' => 'Thiếu ID'];
        $cu = BG_HangHoa_DAL::getById((int)$e->id);
        if (!$cu || $cu->da_xoa === 1) return ['success' => false, 'message' => 'Không tìm thấy hàng hóa'];

        // Không cho chuyển hàng hóa sang gói thầu khác (giữ toàn vẹn chi tiết báo giá đã có)
        $e->goi_thau_id = (int)$cu->goi_thau_id;

        $err = self::validate($e, true);
        if ($err !== '') return ['success' => false, 'message' => $err];

        try {
            if ($e->thu_tu <= 0) $e->thu_tu = (int)$cu->thu_tu;
            BG_HangHoa_DAL::update($e);
            DM_NhatKyHeThong_DAL::log(
                $e->nguoi_cap_nhat ?? 0, self::MODULE_LOG,
                "Sửa hàng hóa: {$e->ten_hang_hoa}", 'bg_hang_hoa', $e->id
            );
            return ['success' => true, 'message' => 'Cập nhật hàng hóa thành công'];
        } catch (Throwable $ex) {
            return ['success' => false, 'message' => 'Lỗi: ' . $ex->getMessage()];
        }
    }

    public static function trash(int $id, int $u): array
    {
        if ($id <= 0) return ['success' => false, 'message' => 'Thiếu ID'];
        $hh = BG_HangHoa_DAL::getById($id);
        if (!$hh) return ['success' => false, 'message' => 'Không tìm thấy hàng hóa'];

        $chot = BG_GoiThau_BUS::kiemTraChuaChotSo((int)$hh->goi_thau_id);
        if (!$chot['ok']) return ['success' => false, 'message' => $chot['message']];

        // Đã có nhà thầu chào giá thì KHÔNG cho xóa: dòng sẽ biến mất khỏi bảng
        // tổng hợp nhưng tiền vẫn nằm trong tổng của nhà thầu → sai số.
        $soChao = BG_HangHoa_DAL::demBaoGiaDaChao($id);
        if ($soChao > 0) {
            return [
                'success' => false,
                'message' => "Đã có {$soChao} nhà thầu chào giá cho hàng hóa này — không thể xóa. "
                           . 'Xóa sẽ làm lệch tổng tiền trong bảng tổng hợp.',
            ];
        }

        BG_HangHoa_DAL::softDelete($id, $u);
        DM_NhatKyHeThong_DAL::log(
            $u, self::MODULE_LOG, "Xóa tạm hàng hóa: {$hh->ten_hang_hoa}", 'bg_hang_hoa', $id
        );
        return ['success' => true, 'message' => 'Đã chuyển vào thùng rác'];
    }

    public static function restore(int $id, int $u): array
    {
        if ($id <= 0) return ['success' => false, 'message' => 'Thiếu ID'];
        $hh = BG_HangHoa_DAL::getById($id);
        if (!$hh) return ['success' => false, 'message' => 'Không tìm thấy hàng hóa'];

        BG_HangHoa_DAL::restore($id, $u);
        DM_NhatKyHeThong_DAL::log(
            $u, self::MODULE_LOG, "Khôi phục hàng hóa: {$hh->ten_hang_hoa}", 'bg_hang_hoa', $id
        );
        return ['success' => true, 'message' => 'Đã khôi phục hàng hóa'];
    }

    public static function getById(int $id): ?BG_HangHoa_PUBLIC
    {
        return BG_HangHoa_DAL::getById($id);
    }

    public static function getPaged(int $page, int $pageSize, int $goiThauId, string $search = '', int $daXoa = 0): array
    {
        return BG_HangHoa_DAL::getPaged($page, $pageSize, $goiThauId, $search, $daXoa);
    }

    public static function getByGoiThau(int $goiThauId): array
    {
        return BG_HangHoa_DAL::getByGoiThau($goiThauId);
    }

    // =====================================================================
    // IMPORT EXCEL
    // =====================================================================

    /**
     * Đọc file Excel Phụ lục III → cây BỘ + hàng hóa chi tiết (chỉ parse,
     * chưa ghi DB) để xem trước rồi mới import thật.
     *
     * Quy tắc nhận dạng dòng (xem hằng COL_* ở đầu class):
     *   - Có STT bộ (cột B) hoặc Tên bộ (cột C) → MỞ MỘT BỘ MỚI
     *   - Có STT chi tiết (cột D) hoặc Tên hàng hóa (cột E) → CHI TIẾT của bộ đang mở
     *   - Có cả hai → hàng lẻ: bộ chỉ chứa đúng 1 chi tiết
     *
     * Chi tiết xuất hiện trước khi có bộ nào (file thiếu dòng bộ đầu) sẽ được
     * gom vào một bộ ngầm, KHÔNG vứt bỏ dữ liệu của người dùng.
     *
     * @return array ['success'=>bool, 'message'=>string, 'data'=>[bộ...], 'loi'=>[...]]
     */
    public static function docFileExcel(string $filePath, string $nhom = ''): array
    {
        try {
            $rows = ExcelHelper::readSheet($filePath);
        } catch (Throwable $ex) {
            return ['success' => false, 'message' => 'Không đọc được file: ' . $ex->getMessage()];
        }

        if (empty($rows)) {
            return ['success' => false, 'message' => 'File không có dữ liệu'];
        }

        // Dò dòng header trong 8 dòng đầu (file thật có tiêu đề + dòng đánh số cột).
        // So khớp sau khi BỎ DẤU để không phụ thuộc dấu tiếng Việt.
        $dongHeader = 0;
        for ($d = 1; $d <= 8; $d++) {
            $tenCt = self::boDau(ExcelHelper::toText($rows[$d][self::COL_TEN_HANG_HOA] ?? ''));
            $tenBo = self::boDau(ExcelHelper::toText($rows[$d][self::COL_TEN_BO] ?? ''));
            if (mb_stripos($tenCt, 'ten danh muc') !== false
                || mb_stripos($tenCt, 'hang hoa') !== false
                || mb_stripos($tenBo, 'ten bo') !== false) {
                $dongHeader = $d;
                break;
            }
        }
        if ($dongHeader === 0) {
            return [
                'success' => false,
                'message' => 'File không đúng định dạng Phụ lục III (12 cột). Cần: '
                           . 'Mã | STT bộ | Tên bộ | STT chi tiết | Tên hàng hóa chi tiết | '
                           . 'Yêu cầu chung | Yêu cầu khác | Yêu cầu cấu hình | Yêu cầu kỹ thuật | '
                           . 'Nhóm nước | ĐVT | Số lượng. Hãy tải file mẫu và điền theo đúng cấu trúc.',
            ];
        }

        $bo     = [];   // danh sách bộ đã dựng
        $hangLe = [];   // hàng KHÔNG thuộc bộ nào (bo_id = NULL)
        $loi    = [];
        $iBo    = -1;   // chỉ số bộ đang mở

        foreach ($rows as $rowNo => $cells) {
            if ($rowNo <= $dongHeader) continue;

            $lay = function (int $c, int $max = 0) use ($cells): string {
                return ExcelHelper::toText($cells[$c] ?? '', $max ?: 0);
            };

            $ma      = $lay(self::COL_MA, 50);
            $sttBo   = $lay(self::COL_STT_BO);
            $tenBo   = $lay(self::COL_TEN_BO, 1000);
            $sttCt   = $lay(self::COL_STT_CT);
            $tenCt   = $lay(self::COL_TEN_HANG_HOA, 1000);

            // Dòng trắng hoàn toàn → bỏ qua im lặng
            if ($ma === '' && $sttBo === '' && $tenBo === '' && $sttCt === '' && $tenCt === '') {
                continue;
            }
            // Dòng chú thích còn sót trong file mẫu ("( lấy ví dụ ...")
            if ($tenBo === '' && $tenCt === '' && mb_strpos($ma, '(') !== false) {
                continue;
            }

            $soLuong = ExcelHelper::toNumber($cells[self::COL_SO_LUONG] ?? 0);
            if ($soLuong < 0) {
                $loi[] = "Dòng {$rowNo}: số lượng âm → đặt về 0";
                $soLuong = 0;
            }
            $dvt = $lay(self::COL_DVT, 50);

            // CHỈ là dòng BỘ khi có TÊN BỘ (cột C). Chỉ điền STT bộ mà không
            // có tên thì không đủ để dựng một bộ — thường là hàng lẻ đánh số.
            $laDongBo = ($tenBo !== '');
            $laDongCt = ($sttCt !== '' || $tenCt !== '');

            // ---- Mở bộ mới ----
            if ($laDongBo) {
                $bo[] = [
                    'row'              => $rowNo,
                    'ma_bo'            => $ma,
                    'stt_bo'           => $sttBo !== '' ? (int)ExcelHelper::toNumber($sttBo) : null,
                    'ten_bo'           => $tenBo,
                    'yeu_cau_chung'    => $lay(self::COL_YC_CHUNG),
                    'yeu_cau_khac'     => $lay(self::COL_YC_KHAC),
                    'yeu_cau_cau_hinh' => $lay(self::COL_YC_CAU_HINH),
                    'nhom_nuoc'        => $lay(self::COL_NHOM_NUOC, 500),
                    'dvt'              => $dvt,
                    'so_luong'         => $soLuong,
                    'chi_tiet'         => [],
                ];
                $iBo = count($bo) - 1;

                // Dòng bộ KHÔNG kèm chi tiết → xong, chờ các dòng chi tiết bên dưới
                if (!$laDongCt) continue;
            }

            // ---- Chi tiết ----
            if ($laDongCt) {
                if ($tenCt === '') {
                    $loi[] = "Dòng {$rowNo}: có STT chi tiết nhưng thiếu Tên hàng hóa (cột E) → đã bỏ qua";
                    continue;
                }
                // KHÔNG tự tạo bộ ngầm nữa: hàng không có bộ là HÀNG LẺ hợp lệ
                // (vật tư, dược phần lớn như vậy) — gom vào rổ riêng, bo_id = NULL.
                $dich = &$hangLe;
                if ($iBo >= 0) $dich = &$bo[$iBo]['chi_tiet'];

                $dich[] = [
                    'row'               => $rowNo,
                    // Dòng bộ-kiêm-chi-tiết: mã nằm ở cột A của chính dòng đó
                    'ma_hh'             => $ma,
                    'stt_chi_tiet'      => $sttCt !== '' ? (int)ExcelHelper::toNumber($sttCt) : null,
                    'ten_hang_hoa'      => $tenCt,
                    'thong_so_ky_thuat' => $lay(self::COL_THONG_SO),
                    'nhom_nuoc'         => $lay(self::COL_NHOM_NUOC, 500),
                    'dvt'               => $dvt,
                    'so_luong'          => $soLuong,
                ];
            }
        }

        // Bộ không có chi tiết nào = dữ liệu thiếu → báo rõ, không nạp âm thầm
        $tongCt = count($hangLe);
        foreach ($bo as $b) $tongCt += count($b['chi_tiet']);

        if ($tongCt === 0) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy hàng hóa chi tiết nào. Mỗi bộ phải có ít nhất 1 dòng '
                           . 'chi tiết (cột D "STT chi tiết" + cột E "Tên hàng hóa").',
                'loi' => $loi,
            ];
        }
        foreach ($bo as $b) {
            if (empty($b['chi_tiet'])) {
                $loi[] = "Dòng {$b['row']}: bộ \"{$b['ten_bo']}\" không có hàng hóa chi tiết nào";
            }
        }

        // ---- Số lượng bắt buộc khi đã khai BỘ ----
        // Bộ dụng cụ / hệ thống TBYT: mua theo bộ nên THIẾU SL của 1 chi tiết là
        // không dựng được giá cả bộ. Vật tư dược phần lớn là hàng lẻ, không ép.
        $epSoLuong = $nhom !== '' && $nhom !== BG_Nhom_PUBLIC::VAT_TU_DUOC;
        if ($epSoLuong) {
            $thieu = [];
            $moiDong = $hangLe;
            foreach ($bo as $b) {
                foreach ($b['chi_tiet'] as $c1) $moiDong[] = $c1;
            }
            foreach ([['', $moiDong]] as [$tenBo, $dsCt]) {
                foreach ($dsCt as $ct) {
                    if ((float)$ct['so_luong'] <= 0) {
                        $thieu[] = "dòng {$ct['row']} — " . mb_substr($ct['ten_hang_hoa'], 0, 40);
                    }
                }
            }
            if ($thieu) {
                return [
                    'success' => false,
                    'message' => 'Nhóm ' . BG_Nhom_PUBLIC::tenNhom($nhom) . ' mua theo BỘ nên '
                               . 'MỌI hàng hóa chi tiết đều phải có Số lượng (cột L) lớn hơn 0. '
                               . 'Còn ' . count($thieu) . ' dòng chưa nhập: '
                               . implode('; ', array_slice($thieu, 0, 5))
                               . (count($thieu) > 5 ? '; ...' : ''),
                    'loi' => $loi,
                ];
            }
        }

        // Hàng lẻ (vật tư dược) thiếu SL thì chỉ cảnh báo, vẫn cho nạp
        $moiDong2 = $hangLe;
        foreach ($bo as $b) foreach ($b['chi_tiet'] as $c2) $moiDong2[] = $c2;
        foreach ([$moiDong2] as $dsCt) {
            foreach ($dsCt as $ct) {
                if ((float)$ct['so_luong'] <= 0) {
                    $loi[] = "Dòng {$ct['row']}: \"" . mb_substr($ct['ten_hang_hoa'], 0, 40)
                           . "\" chưa có Số lượng — nhà thầu sẽ không tính được thành tiền";
                }
            }
        }

        $moTa = count($bo) . ' bộ';
        if ($hangLe) $moTa .= ', ' . count($hangLe) . ' hàng lẻ';

        return [
            'success'  => true,
            'message'  => 'Đọc được ' . $moTa . ', ' . $tongCt . ' hàng hóa',
            'data'     => $bo,
            'hang_le'  => $hangLe,
            'loi'      => $loi,
        ];
    }


    /**
     * Import danh mục (BỘ + hàng hóa chi tiết) từ file Excel Phụ lục III.
     *
     * Ghi 2 bảng (bg_bo + bg_hang_hoa) nên BẮT BUỘC bọc transaction: hỏng
     * giữa chừng mà không rollback sẽ để lại bộ rỗng không có hàng hóa.
     *
     * @param bool $ghiDe true = xóa danh mục cũ trước khi nạp mới
     */
    public static function importExcel(int $goiThauId, string $filePath, bool $ghiDe, int $u): array
    {
        if ($goiThauId <= 0) return ['success' => false, 'message' => 'Chưa chọn gói thầu'];

        $gt = BG_GoiThau_DAL::getById($goiThauId);
        if (!$gt || $gt->da_xoa === 1) return ['success' => false, 'message' => 'Gói thầu không tồn tại'];

        // Đã có báo giá → đổi danh mục sẽ làm lệch dữ liệu nhà thầu đã chào
        if ((int)$gt->so_bao_gia > 0 && $ghiDe) {
            return [
                'success' => false,
                'message' => 'Gói thầu đã có ' . (int)$gt->so_bao_gia . ' báo giá — không thể ghi đè danh mục hàng hóa. '
                           . 'Hãy tạo gói thầu mới nếu cần thay đổi danh mục.',
            ];
        }

        $nhomGt = BG_Nhom_PUBLIC::chuanHoa($gt->nhom ?? null);
        $doc = self::docFileExcel($filePath, $nhomGt);
        if (!$doc['success']) return $doc;

        $dsBo = $doc['data'];

        try {
            Database::beginTransaction();

            if ($ghiDe) {
                // Xóa hàng hóa TRƯỚC rồi mới xóa bộ — ngược lại sẽ còn hàng
                // trỏ tới bo_id không tồn tại.
                BG_HangHoa_DAL::softDeleteByGoiThau($goiThauId, $u);
                BG_Bo_DAL::deleteByGoiThau($goiThauId);
                $thuTuBo = 0;
                $thuTuHh = 0;
            } else {
                $thuTuBo = count(BG_Bo_DAL::getByGoiThau($goiThauId));
                $thuTuHh = BG_HangHoa_DAL::maxThuTu($goiThauId);
            }

            // Bộ đếm sinh Mã HH cho dòng bỏ trống mã. Tính cả mã HHxxx do
            // CHÍNH file này khai sẵn, nếu không dòng trống mã sẽ sinh trùng.
            $soTiepTheo = BG_HangHoa_DAL::soThuTuMaLonNhat($goiThauId);
            foreach ($dsBo as $b) {
                foreach ($b['chi_tiet'] as $ct) {
                    if (preg_match('/^HH(\d+)$/', (string)$ct['ma_hh'], $m)) {
                        $soTiepTheo = max($soTiepTheo, (int)$m[1]);
                    }
                }
            }

            $soBo = 0;
            $soHh = 0;
            $lo = [];

            // Dung 1 dong hang hoa — dung chung cho HANG LE (boId = null) va
            // hang trong bo, de 2 nhanh khong lech nhau.
            $dungHang = function (array $ct, ?int $boId, int $stt)
                use ($goiThauId, $u, &$soTiepTheo, &$thuTuHh): BG_HangHoa_PUBLIC {
                $maHh = $ct['ma_hh'];
                if ($maHh === '') {
                    $soTiepTheo++;
                    $maHh = 'HH' . str_pad((string)$soTiepTheo, 3, '0', STR_PAD_LEFT);
                }
                $e = new BG_HangHoa_PUBLIC();
                $e->goi_thau_id       = $goiThauId;
                $e->bo_id             = $boId;
                $e->stt_chi_tiet      = $boId === null ? null : ($ct['stt_chi_tiet'] ?? $stt);
                $e->ma_hh             = $maHh;
                $e->ten_hang_hoa      = $ct['ten_hang_hoa'];
                $e->thong_so_ky_thuat = $ct['thong_so_ky_thuat'] !== '' ? $ct['thong_so_ky_thuat'] : null;
                $e->nhom_nuoc         = $ct['nhom_nuoc'] !== '' ? $ct['nhom_nuoc'] : null;
                $e->dvt               = $ct['dvt'] !== '' ? $ct['dvt'] : null;
                $e->so_luong          = (float)$ct['so_luong'];
                $e->thu_tu            = ++$thuTuHh;
                $e->nguoi_tao         = $u;
                return $e;
            };

            // ---- HANG LE: khong thuoc bo nao, bo_id = NULL ----
            foreach ($doc['hang_le'] ?? [] as $j => $ct) {
                $lo[] = $dungHang($ct, null, $j + 1);
                if (count($lo) >= self::BATCH_SIZE) {
                    $soHh += BG_HangHoa_DAL::insertBatch($lo);
                    $lo = [];
                }
            }

            foreach ($dsBo as $b) {
                if (empty($b['chi_tiet'])) continue;   // bộ rỗng → đã cảnh báo ở parser

                $eb = new BG_Bo_PUBLIC();
                $eb->goi_thau_id      = $goiThauId;
                $eb->ma_bo            = $b['ma_bo'] !== '' ? $b['ma_bo'] : null;
                $eb->stt_bo           = $b['stt_bo'] ?? (++$soBo);
                $eb->ten_bo           = $b['ten_bo'] !== '' ? $b['ten_bo'] : null;
                $eb->yeu_cau_chung    = $b['yeu_cau_chung'] !== '' ? $b['yeu_cau_chung'] : null;
                $eb->yeu_cau_khac     = $b['yeu_cau_khac'] !== '' ? $b['yeu_cau_khac'] : null;
                $eb->yeu_cau_cau_hinh = $b['yeu_cau_cau_hinh'] !== '' ? $b['yeu_cau_cau_hinh'] : null;
                $eb->nhom_nuoc        = $b['nhom_nuoc'] !== '' ? $b['nhom_nuoc'] : null;
                $eb->dvt              = $b['dvt'] !== '' ? $b['dvt'] : null;
                $eb->so_luong         = (float)$b['so_luong'];
                $eb->thu_tu           = ++$thuTuBo;
                $eb->nguoi_tao        = $u;

                $boId = BG_Bo_DAL::insert($eb);
                if ($b['stt_bo'] === null) $soBo = max($soBo, $eb->stt_bo);

                foreach ($b['chi_tiet'] as $j => $ct) {
                    $lo[] = $dungHang($ct, $boId, $j + 1);
                    if (count($lo) >= self::BATCH_SIZE) {
                        $soHh += BG_HangHoa_DAL::insertBatch($lo);
                        $lo = [];
                    }
                }
            }
            if ($lo) $soHh += BG_HangHoa_DAL::insertBatch($lo);

            $soBoThat = 0;
            foreach ($dsBo as $b) if (!empty($b['chi_tiet'])) $soBoThat++;

            Database::commit();

            DM_NhatKyHeThong_DAL::log(
                $u, self::MODULE_LOG,
                "Import {$soBoThat} bộ / {$soHh} hàng hóa vào gói thầu {$gt->so_thong_bao}"
                . ($ghiDe ? ' (ghi đè)' : ' (thêm tiếp)'),
                'bg_hang_hoa', $goiThauId
            );

            return [
                'success' => true,
                'message' => 'Đã import ' . ($soBoThat > 0 ? "{$soBoThat} bộ, " : '') . "{$soHh} hàng hóa"
                           . ($ghiDe ? ' (đã thay danh mục cũ)' : ''),
                'data' => [
                    'so_bo'    => $soBoThat,
                    'so_dong'  => $soHh,
                    'canh_bao' => $doc['loi'] ?? [],
                ],
            ];
        } catch (Throwable $ex) {
            Database::rollBack();
            return ['success' => false, 'message' => 'Lỗi khi import: ' . $ex->getMessage()];
        }
    }

    /**
     * Sinh file Excel mẫu cho nhà thầu — MỖI MẪU MỘT FILE RIÊNG.
     *
     * Tách riêng (thay vì 2 sheet trong 1 file) để nhà thầu tải đúng phần
     * đang làm ở bước hiện tại, đỡ nhầm sang sheet kia.
     *
     * @param string $mau 'mau1' = Bảng đáp ứng kỹ thuật (Mẫu 1)
     *                    'mau2' = Bảng chào giá (Mẫu 2)
     */
    /**
     * File Excel mau DANH MUC HANG HOA cho BEN MOI import len.
     *
     * KHAC voi xuatFileMau(): ham kia sinh Mau 1 / Mau 2 cho NHA THAU chao gia
     * nen bat buoc phai co san hang hoa. Con o day la mau de NAP hang hoa vao,
     * goi thau moi tao chua co dong nao la chuyen binh thuong â van phai tai duoc.
     *
     * Cot phai khop hang so COL_* dung khi import (Â§4.2).
     */
    /** Bỏ dấu tiếng Việt để so khớp tiêu đề cột không phụ thuộc dấu */
    private static function boDau(string $s): string
    {
        $s = mb_strtolower(trim($s), 'UTF-8');
        $map = [
            'a' => 'áàảãạăắằẳẵặâấầẩẫậ',
            'e' => 'éèẻẽẹêếềểễệ',
            'i' => 'íìỉĩị',
            'o' => 'óòỏõọôốồổỗộơớờởỡợ',
            'u' => 'úùủũụưứừửữự',
            'y' => 'ýỳỷỹỵ',
            'd' => 'đ',
        ];
        foreach ($map as $khong => $co) {
            foreach (preg_split('//u', $co, -1, PREG_SPLIT_NO_EMPTY) as $ch) {
                $s = str_replace($ch, $khong, $s);
            }
        }
        return $s;
    }

    /**
     * File Excel mẫu DANH MỤC (Phụ lục III) cho BÊN MỜI import lên.
     *
     * Luôn xuất đủ 12 cột đúng thứ tự hằng COL_* — parser đọc theo VỊ TRÍ cột,
     * nên KHÔNG được bỏ bớt cột của nhóm không dùng, chỉ đánh dấu "không áp
     * dụng" ở tiêu đề để người điền biết mà bỏ qua.
     *
     * Dòng ví dụ sinh theo đúng nhóm của gói thầu (bộ dụng cụ / hệ thống TBYT /
     * vật tư dược), lấy nguyên mẫu từ Phụ lục III Thư mời.
     */
    public static function xuatFileMauDanhMuc(int $goiThauId): string
    {
        $gt = BG_GoiThau_DAL::getById($goiThauId);
        if (!$gt) throw new RuntimeException('Không tìm thấy gói thầu');

        $nhom = BG_Nhom_PUBLIC::chuanHoa($gt->nhom ?? null);

        $H = ExcelHelper::S_HEADER;
        $S = ExcelHelper::S_TEXT_WRAP;
        $C = ExcelHelper::S_CENTER;
        $N = ExcelHelper::S_NUMBER;

        // Cột không dùng ở nhóm này vẫn PHẢI giữ chỗ — ghi rõ để khỏi điền nhầm
        $naChung   = BG_Nhom_PUBLIC::coYeuCauChung($nhom)   ? '' : ' (không áp dụng)';
        $naKhac    = BG_Nhom_PUBLIC::coYeuCauKhac($nhom)    ? '' : ' (không áp dụng)';
        $naCauHinh = BG_Nhom_PUBLIC::coYeuCauCauHinh($nhom) ? '' : ' (không áp dụng)';

        $rows = [
            [['v' => 'PHỤ LỤC III — BẢNG MÔ TẢ YÊU CẦU', 's' => ExcelHelper::S_TITLE]],
            [['v' => 'Thư mời số ' . $gt->so_thong_bao . ' — ' . $gt->ten_goi_thau,
              's' => ExcelHelper::S_SUBTITLE]],
            [['v' => 'Nhóm: ' . BG_Nhom_PUBLIC::tenNhom($nhom) . '. '
                   . BG_Nhom_PUBLIC::moTa($nhom),
              's' => ExcelHelper::S_SUBTITLE]],
            [['v' => 'Cách điền: dòng BỘ điền cột B,C (STT bộ, Tên bộ) — dòng HÀNG HÓA CHI TIẾT '
                   . 'điền cột D,E. Hàng lẻ điền cả B,C,D,E trên cùng 1 dòng. '
                   . 'Bỏ trống cột A (Mã) thì hệ thống tự sinh. Xóa các dòng ví dụ trước khi import.',
              's' => ExcelHelper::S_SUBTITLE]],
            [
                ['v' => 'Mã bộ/hàng hóa chi tiết', 's' => $H],
                ['v' => 'STT bộ/phần/hệ thống', 's' => $H],
                ['v' => 'Tên bộ/phần/hệ thống', 's' => $H],
                ['v' => 'STT chi tiết', 's' => $H],
                ['v' => 'Tên danh mục hàng hóa/dụng cụ chi tiết', 's' => $H],
                ['v' => 'Yêu cầu chung' . $naChung, 's' => $H],
                ['v' => 'Yêu cầu khác' . $naKhac, 's' => $H],
                ['v' => 'Yêu cầu cấu hình' . $naCauHinh, 's' => $H],
                ['v' => 'Yêu cầu kỹ thuật', 's' => $H],
                ['v' => 'Yêu cầu nhóm nước, vùng lãnh thổ (nếu có)', 's' => $H],
                ['v' => 'ĐVT', 's' => $H],
                ['v' => 'Số lượng', 's' => $H],
            ],
        ];

        /** Dựng 1 dòng 12 cột — tham số rỗng thì để trống */
        $dong = function (array $c) use ($S, $C, $N): array {
            $sl = $c[11] ?? '';
            return [
                ['v' => (string)($c[0] ?? ''),  's' => $C],
                ['v' => $c[1] === '' || !isset($c[1]) ? '' : (float)$c[1], 's' => $C,
                 't' => isset($c[1]) && $c[1] !== '' ? 'n' : 's'],
                ['v' => (string)($c[2] ?? ''),  's' => $S],
                ['v' => $c[3] === '' || !isset($c[3]) ? '' : (float)$c[3], 's' => $C,
                 't' => isset($c[3]) && $c[3] !== '' ? 'n' : 's'],
                ['v' => (string)($c[4] ?? ''),  's' => $S],
                ['v' => (string)($c[5] ?? ''),  's' => $S],
                ['v' => (string)($c[6] ?? ''),  's' => $S],
                ['v' => (string)($c[7] ?? ''),  's' => $S],
                ['v' => (string)($c[8] ?? ''),  's' => $S],
                ['v' => (string)($c[9] ?? ''),  's' => $C],
                ['v' => (string)($c[10] ?? ''), 's' => $C],
                ['v' => $sl === '' ? '' : (float)$sl, 's' => $N, 't' => $sl === '' ? 's' : 'n'],
            ];
        };

        // Gói đã có danh mục -> đổ ra để sửa; chưa có -> ví dụ theo nhóm
        $dsBo    = BG_Bo_DAL::getByGoiThau($goiThauId);
        $hangHoa = BG_HangHoa_DAL::getByGoiThau($goiThauId);

        // Gom theo bộ — khóa 0 = HÀNG LẺ (bo_id NULL)
        $theoBo = [];
        foreach ($hangHoa as $hh) {
            $theoBo[(int)($hh['bo_id'] ?? 0)][] = $hh;
        }

        // Chỉ đổ VÍ DỤ khi gói chưa có hàng hóa nào. Trước đây xét theo $dsBo
        // nên gói toàn HÀNG LẺ (vật tư, dược) bị coi là rỗng và file mẫu tải về
        // toàn dòng ví dụ thay vì danh mục thật.
        if (empty($hangHoa)) {
            foreach (self::viDuTheoNhom($nhom) as $v) {
                $rows[] = $dong($v);
            }
        } else {
            // ---- HÀNG LẺ: không thuộc bộ nào, để trống cột B, C ----
            foreach ($theoBo[0] ?? [] as $h) {
                $rows[] = $dong([
                    (string)($h['ma_hh'] ?? ''), '', '',
                    '', (string)$h['ten_hang_hoa'],
                    '', '', '',
                    (string)($h['thong_so_ky_thuat'] ?? ''),
                    (string)($h['nhom_nuoc'] ?? ''),
                    (string)($h['dvt'] ?? ''), (float)$h['so_luong'],
                ]);
            }

            foreach ($dsBo as $b) {
                $ct = $theoBo[(int)$b['id']] ?? [];

                // Dòng BỘ — mang yêu cầu chung/khác/cấu hình
                $rows[] = $dong([
                    (string)($b['ma_bo'] ?? ''), $b['stt_bo'], (string)$b['ten_bo'],
                    '', '',
                    (string)($b['yeu_cau_chung'] ?? ''), (string)($b['yeu_cau_khac'] ?? ''),
                    (string)($b['yeu_cau_cau_hinh'] ?? ''), '',
                    (string)($b['nhom_nuoc'] ?? ''),
                    (string)($b['dvt'] ?? ''), (float)$b['so_luong'],
                ]);
                foreach ($ct as $k => $h) {
                    $rows[] = $dong([
                        (string)($h['ma_hh'] ?? ''), '', '',
                        $h['stt_chi_tiet'] ?? ($k + 1), (string)$h['ten_hang_hoa'],
                        '', '', '',
                        (string)($h['thong_so_ky_thuat'] ?? ''),
                        (string)($h['nhom_nuoc'] ?? ''),
                        (string)($h['dvt'] ?? ''), (float)$h['so_luong'],
                    ]);
                }
            }
        }

        $path = self::tempDir() . '/DanhMuc_'
              . preg_replace('/[^0-9A-Za-z]/', '_', (string)$gt->so_thong_bao)
              . '_' . date('Ymd_His') . '.xlsx';

        ExcelHelper::write($path, [
            'DanhMucHangHoa' => [
                'cols'    => [16, 8, 34, 8, 40, 30, 30, 30, 40, 20, 10, 10],
                'freeze'  => 'A6',
                'heights' => [5 => 46],
                'rows'    => $rows,
            ],
        ]);
        return $path;
    }

    /**
     * Dòng ví dụ theo nhóm — lấy nguyên mẫu từ Phụ lục III Thư mời để bên mời
     * nhìn là biết phải điền thế nào. Mảng 12 phần tử khớp thứ tự COL_*.
     *
     * SỐ LƯỢNG để 0, KHÔNG để 1: người dùng thường sửa tên hàng rồi quên sửa
     * SL, để 1 thì cả danh mục lặng lẽ thành "1 cái" mà không ai phát hiện;
     * để 0 thì import sẽ cảnh báo và bảng hiện rõ là chưa nhập.
     */
    private static function viDuTheoNhom(string $nhom): array
    {
        if ($nhom === BG_Nhom_PUBLIC::BO_DUNG_CU) {
            return [
                ['', 1, 'BỘ DỤNG CỤ PHẪU THUẬT SỌ NÃO', '', '',
                 'Yêu cầu chung của cả bộ', 'Yêu cầu khác của cả bộ', '', '',
                 'Châu Âu', 'Bộ', 0],
                ['', '', '', 1, 'Khớp nối cố định thanh đỡ hệ thống vén não',
                 '', '', '', 'Yêu cầu kỹ thuật của dụng cụ này', '', 'Cái', 0],
                ['', '', '', 2, 'Thanh đỡ hệ thống vén não',
                 '', '', '', 'Yêu cầu kỹ thuật của dụng cụ này', '', 'Cái', 0],
                ['', 2, 'BỘ DỤNG CỤ PHẪU THUẬT CHI DƯỚI', '', '',
                 'Yêu cầu chung của cả bộ', 'Yêu cầu khác của cả bộ', '', '', '', 'Bộ', 0],
                ['', '', '', 1, 'Cán dao số 3', '', '', '', 'Yêu cầu kỹ thuật', '', 'Cái', 0],
            ];
        }

        if ($nhom === BG_Nhom_PUBLIC::HE_THONG_TBYT) {
            return [
                ['', 1, 'Hệ thống máy siêu âm', '', '',
                 'Yêu cầu chung của cả hệ thống', 'Yêu cầu khác của cả hệ thống',
                 'Yêu cầu cấu hình của cả hệ thống', '', '', 'Hệ thống', 0],
                ['', '', '', 1, 'Máy chính', '', '', '', 'Yêu cầu kỹ thuật', '', 'Cái', 0],
                ['', '', '', 2, 'Màn hình',  '', '', '', 'Yêu cầu kỹ thuật', '', 'Cái', 0],
                ['', '', '', 3, 'Phần mềm',  '', '', '', 'Yêu cầu kỹ thuật', '', 'Cái', 0],
                ['', '', '', 4, 'Xe đẩy',    '', '', '', 'Yêu cầu kỹ thuật', '', 'Cái', 0],
            ];
        }

        // vat_tu_duoc — phần lớn là HÀNG LẺ: để trống cột B, C (STT bộ, Tên bộ).
        // Vẫn có thể khai bộ khi cần (VT002 dưới đây) — lúc đó yêu cầu kỹ thuật
        // nằm ở hàng hóa chi tiết, không nằm ở bộ.
        return [
            ['VT001', '', '', 1, 'Bơm tiêm 10ml',
             '', '', '', 'Yêu cầu kỹ thuật', 'Việt Nam', 'Cái', 0],
            ['VT002', 1, 'Bộ máy tạo nhịp', '', '', '', '', '', '', 'G7', 'Bộ', 0],
            ['VT002.1', '', '', 1, 'Dây dẫn',           '', '', '', 'Yêu cầu kỹ thuật', '', 'Cái', 0],
            ['VT002.2', '', '', 2, 'Máy tạo nhịp tim',  '', '', '', 'Yêu cầu kỹ thuật', '', 'Cái', 0],
            ['VT002.3', '', '', 3, 'Điện cực tạo nhịp', '', '', '', 'Yêu cầu kỹ thuật', '', 'Cái', 0],
        ];
    }

    /**
     * File mẫu cho NHÀ THẦU điền rồi import lên.
     *
     * @param string $mau 'mau1' = Bảng đáp ứng (Phụ lục II Mẫu 1)
     *                    'mau2' = Bảng chào giá (Phụ lục II Mẫu 2)
     */
    public static function xuatFileMau(int $goiThauId, string $mau = 'mau2'): string
    {
        $gt = BG_GoiThau_DAL::getById($goiThauId);
        if (!$gt) throw new RuntimeException('Không tìm thấy gói thầu');

        $dsBo    = BG_Bo_DAL::getByGoiThau($goiThauId);
        $hangHoa = BG_HangHoa_DAL::getByGoiThau($goiThauId);

        // Chan theo HANG HOA, khong theo BO: goi toan hang le (vat tu, duoc)
        // khong co bo nao van phai tai duoc file mau, neu khong nha thau
        // khong co gi de dien va upload.
        if (empty($hangHoa)) throw new RuntimeException('Gói thầu chưa có danh mục hàng hóa');

        // Gom hàng hóa theo bộ — khóa 0 = HÀNG LẺ (bo_id NULL)
        $theoBo = [];
        foreach ($hangHoa as $hh) $theoBo[(int)($hh['bo_id'] ?? 0)][] = $hh;

        return $mau === 'mau1'
            ? self::xuatMau1($gt, $dsBo, $theoBo)
            : self::xuatMau2($gt, $dsBo, $theoBo);
    }

    /**
     * MẪU 1 — Bảng đáp ứng (Phụ lục II).
     *
     * 5 cột đầu = danh mục bên mời (khóa, nhà thầu không sửa), phần còn lại là
     * các CẶP (đáp ứng / không đáp ứng) — số cặp thay đổi theo nhóm gói thầu,
     * xem BG_Nhom_PUBLIC::capDapUng(). Cuối cùng là cột tài liệu chứng minh.
     */
    private static function xuatMau1(BG_GoiThau_PUBLIC $gt, array $dsBo, array $theoBo): string
    {
        $nhom = BG_Nhom_PUBLIC::chuanHoa($gt->nhom ?? null);
        $cap  = BG_Nhom_PUBLIC::capDapUng($nhom);

        $H  = ExcelHelper::S_HEADER;
        $HA = ExcelHelper::S_HEADER_ALT;
        $S  = ExcelHelper::S_TEXT_WRAP;
        $C  = ExcelHelper::S_CENTER;

        // --- Header ---
        $hdr = [
            ['v' => 'Mã bộ/hàng hóa chi tiết', 's' => $H],
            ['v' => 'STT bộ', 's' => $H],
            ['v' => 'Tên bộ/phần/hệ thống', 's' => $H],
            ['v' => 'STT chi tiết', 's' => $H],
            ['v' => 'Tên hàng hóa/dụng cụ chi tiết', 's' => $H],
        ];
        $cols = [16, 7, 30, 8, 36];

        // Cột yêu cầu của bên mời (chỉ những phần nhóm này dùng)
        if (BG_Nhom_PUBLIC::coYeuCauChung($nhom))   { $hdr[] = ['v' => 'Yêu cầu chung', 's' => $H];      $cols[] = 30; }
        if (BG_Nhom_PUBLIC::coYeuCauKhac($nhom))    { $hdr[] = ['v' => 'Yêu cầu khác', 's' => $H];       $cols[] = 30; }
        if (BG_Nhom_PUBLIC::coYeuCauCauHinh($nhom)) { $hdr[] = ['v' => 'Yêu cầu cấu hình', 's' => $H];   $cols[] = 30; }
        $hdr[] = ['v' => 'Yêu cầu kỹ thuật', 's' => $H];                 $cols[] = 38;
        $hdr[] = ['v' => 'Yêu cầu nhóm nước, vùng lãnh thổ', 's' => $H]; $cols[] = 20;

        $soCotMoi = count($hdr);   // cột bên mời điền — nhà thầu KHÔNG sửa

        // Cột nhà thầu điền: mỗi cặp 2 cột
        foreach ($cap as $c) {
            $hdr[] = ['v' => 'Đáp ứng về ' . mb_strtolower($c[0]), 's' => $HA];       $cols[] = 32;
            $hdr[] = ['v' => 'Các điểm KHÔNG đáp ứng về ' . mb_strtolower($c[0]), 's' => $HA]; $cols[] = 32;
        }
        $hdr[] = ['v' => 'Tài liệu chứng minh (cam kết, catalog, HDSD...)', 's' => $HA];
        $cols[] = 34;

        $rows = [
            [['v' => 'MẪU 1 — BẢNG ĐÁP ỨNG CHÀO GIÁ', 's' => ExcelHelper::S_TITLE]],
            [['v' => 'Thư mời số ' . $gt->so_thong_bao . ' — ' . $gt->ten_goi_thau,
              's' => ExcelHelper::S_SUBTITLE]],
            [['v' => 'Nhóm: ' . BG_Nhom_PUBLIC::tenNhom($nhom)
                   . '. Chỉ điền các cột nền vàng (từ cột ' . self::tenCot($soCotMoi) . ' trở đi). '
                   . 'KHÔNG sửa, chèn hay xóa dòng — hệ thống đối chiếu theo Mã.',
              's' => ExcelHelper::S_SUBTITLE]],
            $hdr,
        ];

        // Dựng 1 dòng CHI TIẾT — dùng chung cho hàng lẻ và hàng trong bộ.
        // $b = null nghĩa là HÀNG LẺ (không thuộc bộ nào).
        $dongChiTiet = function (array $h, ?array $b, int $stt)
            use ($nhom, $cap, $S, $C): array {
            $d = [
                ['v' => (string)($h['ma_hh'] ?? ''), 's' => $C],
                ['v' => '', 's' => $C],
                ['v' => '', 's' => $S],
                ['v' => $b === null ? '' : (string)($h['stt_chi_tiet'] ?? $stt), 's' => $C],
                ['v' => (string)$h['ten_hang_hoa'], 's' => $S],
            ];
            // Yêu cầu chung/khác/cấu hình gắn với BỘ nên dòng chi tiết để trống
            if (BG_Nhom_PUBLIC::coYeuCauChung($nhom))   $d[] = ['v' => '', 's' => $S];
            if (BG_Nhom_PUBLIC::coYeuCauKhac($nhom))    $d[] = ['v' => '', 's' => $S];
            if (BG_Nhom_PUBLIC::coYeuCauCauHinh($nhom)) $d[] = ['v' => '', 's' => $S];
            $d[] = ['v' => (string)($h['thong_so_ky_thuat'] ?? ''), 's' => $S];
            $d[] = ['v' => (string)($h['nhom_nuoc'] ?? ''), 's' => $C];
            foreach ($cap as $_) { $d[] = ['v' => '', 's' => $S]; $d[] = ['v' => '', 's' => $S]; }
            $d[] = ['v' => '', 's' => $S];
            return $d;
        };

        // ---- HÀNG LẺ đứng TRƯỚC: không có dòng tiêu đề bộ ----
        foreach ($theoBo[0] ?? [] as $k => $h) {
            $rows[] = $dongChiTiet($h, null, $k + 1);
        }

        foreach ($dsBo as $b) {
            $ct = $theoBo[(int)$b['id']] ?? [];

            // --- Dòng BỘ (yêu cầu chung/khác/cấu hình nằm ở đây) ---
            $d = [
                ['v' => (string)($b['ma_bo'] ?? ''), 's' => $C],
                ['v' => (string)($b['stt_bo'] ?? ''), 's' => $C],
                ['v' => (string)($b['ten_bo'] ?? ''), 's' => $S],
                ['v' => '', 's' => $C],
                ['v' => '', 's' => $S],
            ];
            if (BG_Nhom_PUBLIC::coYeuCauChung($nhom))   $d[] = ['v' => (string)($b['yeu_cau_chung'] ?? ''), 's' => $S];
            if (BG_Nhom_PUBLIC::coYeuCauKhac($nhom))    $d[] = ['v' => (string)($b['yeu_cau_khac'] ?? ''), 's' => $S];
            if (BG_Nhom_PUBLIC::coYeuCauCauHinh($nhom)) $d[] = ['v' => (string)($b['yeu_cau_cau_hinh'] ?? ''), 's' => $S];
            $d[] = ['v' => '', 's' => $S];
            $d[] = ['v' => (string)($b['nhom_nuoc'] ?? ''), 's' => $C];
            foreach ($cap as $_) { $d[] = ['v' => '', 's' => $S]; $d[] = ['v' => '', 's' => $S]; }
            $d[] = ['v' => '', 's' => $S];
            $rows[] = $d;

            // --- Dòng CHI TIẾT (yêu cầu kỹ thuật nằm ở đây) ---
            foreach ($ct as $k => $h) {
                $rows[] = $dongChiTiet($h, $b, $k + 1);
            }
        }

        $path = self::tempDir() . '/Mau1_BangDapUng_'
              . preg_replace('/[^0-9A-Za-z]/', '_', (string)$gt->so_thong_bao)
              . '_' . date('Ymd_His') . '.xlsx';

        ExcelHelper::write($path, [
            'Mau1_BangDapUng' => [
                'cols'    => $cols,
                'freeze'  => 'F5',
                'heights' => [4 => 52],
                'rows'    => $rows,
            ],
        ]);
        return $path;
    }

    /**
     * MẪU 2 — Bảng chào giá (Phụ lục II), 14 cột đúng Thư mời.
     *
     * KHÔNG phụ thuộc nhóm: cả 3 nhóm đều chào giá theo cùng bộ cột. Chỉ có
     * dòng BỘ là để trống phần giá (giá nằm ở hàng hóa chi tiết).
     */
    private static function xuatMau2(BG_GoiThau_PUBLIC $gt, array $dsBo, array $theoBo): string
    {
        $H  = ExcelHelper::S_HEADER;
        $HA = ExcelHelper::S_HEADER_ALT;
        $S  = ExcelHelper::S_TEXT_WRAP;
        $C  = ExcelHelper::S_CENTER;
        $N  = ExcelHelper::S_NUMBER;

        $rows = [
            [['v' => 'MẪU 2 — BẢNG CHÀO GIÁ', 's' => ExcelHelper::S_TITLE]],
            [['v' => 'Thư mời số ' . $gt->so_thong_bao . ' — ' . $gt->ten_goi_thau,
              's' => ExcelHelper::S_SUBTITLE]],
            [['v' => 'Chỉ điền các cột nền vàng (từ cột F trở đi). Đơn giá đã gồm thuế, '
                   . 'vận chuyển và mọi chi phí phát sinh. KHÔNG sửa, chèn hay xóa dòng.',
              's' => ExcelHelper::S_SUBTITLE]],
            [
                ['v' => 'Mã bộ/hàng hóa chi tiết', 's' => $H],
                ['v' => 'STT bộ', 's' => $H],
                ['v' => 'Tên bộ/phần/hệ thống', 's' => $H],
                ['v' => 'STT chi tiết', 's' => $H],
                ['v' => 'Tên hàng hóa/dụng cụ chi tiết', 's' => $H],
                ['v' => 'Tên thương mại chào giá', 's' => $HA],
                ['v' => 'Ký mã, nhãn hiệu, model', 's' => $HA],
                ['v' => 'Hãng sản xuất', 's' => $HA],
                ['v' => 'Năm sản xuất', 's' => $HA],
                ['v' => 'Xuất xứ', 's' => $HA],
                ['v' => 'Đơn vị tính', 's' => $H],
                ['v' => 'Số lượng', 's' => $H],
                ['v' => 'Đơn giá', 's' => $HA],
                ['v' => 'Thành tiền (VND)', 's' => $H],
            ],
        ];

        // Dựng 1 dòng CHI TIẾT — dùng chung cho hàng lẻ và hàng trong bộ
        $dongChiTiet = function (array $h, bool $trongBo, int $stt)
            use ($S, $C, $N): array {
            return [
                ['v' => (string)($h['ma_hh'] ?? ''), 's' => $C],
                ['v' => '', 's' => $C],
                ['v' => '', 's' => $S],
                ['v' => $trongBo ? (string)($h['stt_chi_tiet'] ?? $stt) : '', 's' => $C],
                ['v' => (string)$h['ten_hang_hoa'], 's' => $S],
                ['v' => '', 's' => $S], ['v' => '', 's' => $S], ['v' => '', 's' => $S],
                ['v' => '', 's' => $C], ['v' => '', 's' => $S],
                ['v' => (string)($h['dvt'] ?? ''), 's' => $C],
                ['v' => (float)$h['so_luong'], 's' => $N, 't' => 'n'],
                ['v' => '', 's' => $N], ['v' => '', 's' => $N],
            ];
        };

        // ---- HÀNG LẺ đứng TRƯỚC: không có dòng tiêu đề bộ ----
        foreach ($theoBo[0] ?? [] as $k => $h) {
            $rows[] = $dongChiTiet($h, false, $k + 1);
        }

        foreach ($dsBo as $b) {
            $ct = $theoBo[(int)$b['id']] ?? [];

            // Dòng bộ: chỉ để nhận biết, không chào giá ở đây
            $rows[] = [
                ['v' => (string)($b['ma_bo'] ?? ''), 's' => $C],
                ['v' => (string)($b['stt_bo'] ?? ''), 's' => $C],
                ['v' => (string)($b['ten_bo'] ?? ''), 's' => $S],
                ['v' => '', 's' => $C], ['v' => '', 's' => $S],
                ['v' => '', 's' => $S], ['v' => '', 's' => $S], ['v' => '', 's' => $S],
                ['v' => '', 's' => $C], ['v' => '', 's' => $S],
                ['v' => (string)($b['dvt'] ?? ''), 's' => $C],
                ['v' => (float)$b['so_luong'], 's' => $N, 't' => 'n'],
                ['v' => '', 's' => $N], ['v' => '', 's' => $N],
            ];

            foreach ($ct as $k => $h) {
                $rows[] = $dongChiTiet($h, true, $k + 1);
            }
        }

        $path = self::tempDir() . '/Mau2_BangChaoGia_'
              . preg_replace('/[^0-9A-Za-z]/', '_', (string)$gt->so_thong_bao)
              . '_' . date('Ymd_His') . '.xlsx';

        ExcelHelper::write($path, [
            'Mau2_BangChaoGia' => [
                'cols'    => [16, 7, 30, 8, 36, 26, 20, 20, 12, 16, 10, 10, 16, 18],
                'freeze'  => 'F5',
                'heights' => [4 => 46],
                'rows'    => $rows,
            ],
        ]);
        return $path;
    }

    /** Số cột (1-based) → tên cột Excel: 1=A, 6=F, 27=AA */
    private static function tenCot(int $n): string
    {
        $s = '';
        while ($n > 0) {
            $n--;
            $s = chr(65 + ($n % 26)) . $s;
            $n = intdiv($n, 26);
        }
        return $s;
    }

    /** Thư mục tạm cho file xuất — tự tạo nếu chưa có */
    public static function tempDir(): string
    {
        $dir = rtrim(AppConfig::UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . 'temp';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return sys_get_temp_dir();
        }
        return $dir;
    }
}
