<?php
/**
 * ajax_handler.php — Cổng chào giá cho nhà thầu.
 *
 * KHÁC với các handler quản trị: quyền ở đây KHÔNG theo ma trận dm_phan_quyen
 * (nhà thầu dùng tài khoản chung 'guest' không có quyền gì trong ma trận).
 * Thay vào đó mọi action đều kiểm tra:
 *   1. Đã đăng nhập + CSRF hợp lệ                      (Helper::requireAjaxCsrf)
 *   2. Token gói thầu trong session khớp bản ghi        (layGoiThauTheoSession)
 *   3. Báo giá đang thao tác thuộc đúng gói thầu đó     (kiemTraBaoGiaThuocPhien)
 *   4. Gói thầu còn nhận báo giá (trạng thái + hạn)     (BUS kiểm tra lại)
 *
 * Nhờ vậy nhà thầu A không đọc/ghi được báo giá của nhà thầu B dù cùng dùng
 * tài khoản 'guest' — vì id báo giá phải nằm trong danh sách của phiên.
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../BUS/BG_BaoGia_BUS.php';
require_once __DIR__ . '/../../BUS/BG_GoiThau_BUS.php';
require_once __DIR__ . '/../../BUS/BG_HangHoa_BUS.php';

Helper::requireAjaxCsrf();

$action = Helper::post('action', '');
$u = SessionHelper::userId();

/** Khóa session lưu danh sách id báo giá mà phiên này được phép sửa */
const SS_BAO_GIA_CUA_TOI = 'portal_bao_gia_ids';

/** Lấy gói thầu theo token đang giữ trong session (đặt bởi index.php) */
function layGoiThauTheoSession(): BG_GoiThau_PUBLIC
{
    $token = (string)SessionHelper::get('portal_token', '');
    if ($token === '') {
        ResponseHelper::error('Phiên chào giá không hợp lệ. Vui lòng quét lại mã QR.', 403);
    }
    $gt = BG_GoiThau_DAL::getByToken($token);
    if (!$gt) {
        ResponseHelper::error('Link chào giá không còn hiệu lực. Vui lòng nhận link mới từ bên mời chào giá.', 403);
    }
    return $gt;
}

/** Đánh dấu báo giá thuộc phiên hiện tại */
function ghiNhanBaoGiaCuaPhien(int $baoGiaId): void
{
    $ids = SessionHelper::get(SS_BAO_GIA_CUA_TOI, []);
    if (!is_array($ids)) $ids = [];
    if (!in_array($baoGiaId, $ids, true)) {
        $ids[] = $baoGiaId;
        SessionHelper::set(SS_BAO_GIA_CUA_TOI, $ids);
    }
}

/**
 * Chỉ cho thao tác trên báo giá do chính phiên này tạo VÀ thuộc gói thầu của token.
 * Chặn nhà thầu khác dò id (IDOR) dù dùng chung tài khoản guest.
 */
function kiemTraBaoGiaThuocPhien(int $baoGiaId, BG_GoiThau_PUBLIC $gt): BG_BaoGia_PUBLIC
{
    $ids = SessionHelper::get(SS_BAO_GIA_CUA_TOI, []);
    if (!is_array($ids) || !in_array($baoGiaId, $ids, true)) {
        ResponseHelper::error('Bạn không có quyền thao tác trên báo giá này.', 403);
    }
    $bg = BG_BaoGia_BUS::getById($baoGiaId);
    if (!$bg || (int)$bg->da_xoa === 1) {
        ResponseHelper::error('Không tìm thấy báo giá', 404);
    }
    if ((int)$bg->goi_thau_id !== (int)$gt->id) {
        ResponseHelper::error('Báo giá không thuộc gói thầu này.', 403);
    }
    return $bg;
}

/** Nhận file .xlsx an toàn — cùng quy tắc §3B.9 như phía quản trị */
function nhanFileExcelPortal(string $field): string
{
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
        throw new RuntimeException('Chưa chọn file');
    }
    $f = $_FILES[$field];
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $map = [
            UPLOAD_ERR_INI_SIZE  => 'File vượt quá giới hạn của server',
            UPLOAD_ERR_FORM_SIZE => 'File vượt quá giới hạn cho phép',
            UPLOAD_ERR_PARTIAL   => 'File tải lên chưa hoàn tất, hãy thử lại',
            UPLOAD_ERR_NO_FILE   => 'Chưa chọn file',
        ];
        throw new RuntimeException($map[$f['error']] ?? 'Lỗi tải file');
    }
    if (!is_uploaded_file($f['tmp_name']) || (int)$f['size'] <= 0) {
        throw new RuntimeException('File không hợp lệ hoặc rỗng');
    }
    if ((int)$f['size'] > AppConfig::UPLOAD_MAX_SIZE) {
        throw new RuntimeException('File tối đa ' . round(AppConfig::UPLOAD_MAX_SIZE / 1048576) . 'MB');
    }
    if (strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION)) !== 'xlsx') {
        throw new RuntimeException('Chỉ nhận file .xlsx. File .xls cũ hãy lưu lại thành .xlsx.');
    }
    if (function_exists('finfo_open')) {
        $fi = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($fi, $f['tmp_name']);
        finfo_close($fi);
        $ok = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'];
        if (!in_array($mime, $ok, true)) {
            throw new RuntimeException('Nội dung file không phải Excel .xlsx hợp lệ');
        }
    }
    $dest = BG_HangHoa_BUS::tempDir() . '/portal_' . Helper::randomString(20) . '.xlsx';
    if (!move_uploaded_file($f['tmp_name'], $dest)) {
        throw new RuntimeException('Không lưu được file tải lên');
    }
    return $dest;
}

try {
    $gt = layGoiThauTheoSession();

    switch ($action) {
        /** Bước 1: nhà thầu khai thông tin công ty → tạo phiếu báo giá */
        case 'khaiThongTin':

            $conNhan = BG_GoiThau_BUS::kiemTraConNhan($gt);
            if (!$conNhan['ok']) ResponseHelper::error($conNhan['message'], 403);
            $e = new BG_BaoGia_PUBLIC();
            $e->goi_thau_id      = (int)$gt->id;
            $e->ten_cong_ty      = Helper::postStr('ten_cong_ty');
            $e->ma_so_thue       = Helper::postStr('ma_so_thue');
            $e->email            = Helper::postStr('email');
            $e->dien_thoai       = Helper::postStr('dien_thoai');
            $e->dia_chi          = Helper::postStr('dia_chi');
            $e->hieu_luc_bao_gia = Helper::postInt('hieu_luc_bao_gia', (int)$gt->hieu_luc_bao_gia);
            $e->ghi_chu          = (string)Helper::post('ghi_chu', '');

            $res = BG_BaoGia_BUS::taoBaoGia($e, $u);
            if (!$res['success']) ResponseHelper::error($res['message']);

            $id = (int)$res['data']['id'];
            ghiNhanBaoGiaCuaPhien($id);
            SessionHelper::set('portal_bao_gia_id', $id);
            ResponseHelper::success($res['message'], ['id' => $id]);
            break;

        /** Cập nhật lại thông tin công ty */
        case 'capNhatThongTin':
            $id = Helper::postInt('bao_gia_id');

            $conNhan = BG_GoiThau_BUS::kiemTraConNhan($gt);
            if (!$conNhan['ok']) ResponseHelper::error($conNhan['message'], 403);
            kiemTraBaoGiaThuocPhien($id, $gt);

            $e = new BG_BaoGia_PUBLIC();
            $e->id               = $id;
            $e->ten_cong_ty      = Helper::postStr('ten_cong_ty');
            $e->ma_so_thue       = Helper::postStr('ma_so_thue');
            $e->email            = Helper::postStr('email');
            $e->dien_thoai       = Helper::postStr('dien_thoai');
            $e->dia_chi          = Helper::postStr('dia_chi');
            $e->hieu_luc_bao_gia = Helper::postInt('hieu_luc_bao_gia', 0);
            $e->ghi_chu          = (string)Helper::post('ghi_chu', '');

            $res = BG_BaoGia_BUS::capNhatThongTin($e, $u);
            $res['success'] ? ResponseHelper::success($res['message']) : ResponseHelper::error($res['message']);
            break;

        /** Bảng hàng hóa + giá đã điền */
        case 'getBangChaoGia':
            $id = Helper::postInt('bao_gia_id');
            $bg = kiemTraBaoGiaThuocPhien($id, $gt);
            // Tra THANG cau truc tom tat (nhom / cap / bo / tong) — GUI doc
            // res.data.bo, res.data.cap... khong long them 1 tang nua.
            $tt = BG_BaoGia_BUS::getBangChaoGia($id);
            $tt['bao_gia'] = [
                'id'           => (int)$bg->id,
                'ten_cong_ty'  => $bg->ten_cong_ty,
                'ma_so_thue'   => $bg->ma_so_thue,
                'trang_thai'   => (int)$bg->trang_thai,
                'tong_tien'    => (float)$bg->tong_tien,
                'ngay_nop'     => $bg->ngay_nop,
                'so_dong_chao' => (int)$bg->so_dong_chao,
                'da_hoan_thanh' => (int)($bg->da_hoan_thanh ?? 0),
            ];
            ResponseHelper::success('OK', $tt);
            break;

        /** Bước 4 — trạng thái bản ký của báo giá đang làm */
        case 'trangThaiBanKy':
            $id = Helper::postInt('bao_gia_id');
            kiemTraBaoGiaThuocPhien($id, $gt);
            ResponseHelper::success('OK', [
                'file_ban_ky'   => BG_BaoGia_BUS::fileBanKy($id),
                // UI dùng cờ này để chuyển sang chế độ CHỈ XEM
                'da_hoan_thanh' => (int)(BG_BaoGia_BUS::getById($id)->da_hoan_thanh ?? 0),
            ]);
            break;

        /** Bước 5 — nhà thầu chốt hoàn thành, khóa mọi chỉnh sửa */
        case 'hoanThanh':
            $id = Helper::postInt('bao_gia_id');

            $conNhan = BG_GoiThau_BUS::kiemTraConNhan($gt);
            if (!$conNhan['ok']) ResponseHelper::error($conNhan['message'], 403);
            kiemTraBaoGiaThuocPhien($id, $gt);

            $res = BG_BaoGia_BUS::hoanThanh($id, $u);
            $res['success']
                ? ResponseHelper::success($res['message'], $res['data'] ?? null)
                : ResponseHelper::error($res['message']);
            break;

        /** Import file báo giá đã điền */
        case 'importFile':
            $id = Helper::postInt('bao_gia_id');

            $conNhan = BG_GoiThau_BUS::kiemTraConNhan($gt);
            if (!$conNhan['ok']) ResponseHelper::error($conNhan['message'], 403);
            kiemTraBaoGiaThuocPhien($id, $gt);

            $path = nhanFileExcelPortal('file');
            try {
                $res = BG_BaoGia_BUS::importFileBaoGia($id, $path, $u);
                $res['success']
                    ? ResponseHelper::success($res['message'], $res['data'] ?? null)
                    : ResponseHelper::error($res['message'], 400, ['data' => $res['data'] ?? null]);
            } finally {
                @unlink($path);
            }
            break;

        /**
         * Tra cứu báo giá đã nộp theo MST — dùng khi NGOÀI thời gian chào giá.
         * Cho phép ở mọi trạng thái thời gian (kể cả đã hết hạn), vì đây là
         * quyền xem lại của chính nhà thầu. Chỉ trả báo giá khớp đúng MST.
         */
        case 'traCuuMst':
            // Chặn Ở SERVER khi tắt tra cứu — ẩn nút ngoài giao diện không phải
            // bảo mật (§3B.1), kẻ gọi thẳng ajax_handler vẫn tra được như thường.
            if (!AppConfig::PORTAL_CHO_TRA_CUU) {
                ResponseHelper::error('Chức năng tra cứu báo giá hiện không khả dụng.', 403);
            }
            $mst = Helper::postStr('ma_so_thue');
            // Tra TẤT CẢ gói thầu — nhà thầu thường chào nhiều gói, cần xem 1 chỗ
            $res = BG_BaoGia_BUS::traCuuTatCaTheoMst($mst);
            if (!$res['success']) ResponseHelper::error($res['message']);

            // Ghi nhớ MST đã tra cứu thành công → cho phép tải file của MST này
            SessionHelper::set('portal_mst_tra_cuu', trim($mst));
            ResponseHelper::success($res['message'], $res['data']);
            break;

        /**
         * Nhà thầu upload bản báo giá có dấu + chữ ký (PDF/ảnh).
         * Upload xong báo giá tự chuyển sang ĐÃ XÁC NHẬN.
         *
         * Cho phép cả khi đã hết thời gian chào giá — vì bản ký thường được gửi
         * sau khi nộp online. Nhưng báo giá phải thuộc MST đã tra cứu trong phiên
         * (hoặc do chính phiên này tạo) để nhà thầu không ký thay người khác.
         */
        case 'uploadBanKy':
            $id = Helper::postInt('bao_gia_id');

            $conNhan = BG_GoiThau_BUS::kiemTraConNhan($gt);
            if (!$conNhan['ok']) ResponseHelper::error($conNhan['message'], 403);

            $duocPhep = false;
            // 1) Báo giá do chính phiên này tạo
            $idsCuaToi = SessionHelper::get(SS_BAO_GIA_CUA_TOI, []);
            if (is_array($idsCuaToi) && in_array($id, $idsCuaToi, true)) {
                $duocPhep = true;
            }
            // 2) Hoặc thuộc MST vừa tra cứu thành công — KHÔNG giới hạn gói thầu,
            //    vì trang tra cứu hiện báo giá của mọi gói, nhà thầu tải bản ký
            //    cho gói nào cũng phải được.
            if (!$duocPhep) {
                $mst = (string)SessionHelper::get('portal_mst_tra_cuu', '');
                if ($mst !== '' && BG_BaoGia_BUS::baoGiaCuaMst($id, $mst)) {
                    $duocPhep = true;
                }
            }
            if (!$duocPhep) {
                ResponseHelper::error(
                    'Bạn không có quyền tải bản ký cho báo giá này. Hãy tra cứu bằng mã số thuế của công ty trước.',
                    403
                );
            }

            $bgKt = BG_BaoGia_BUS::getById($id);
            if (!$bgKt || (int)$bgKt->da_xoa === 1) {
                ResponseHelper::error('Không tìm thấy báo giá', 404);
            }

            if (!isset($_FILES['file'])) ResponseHelper::error('Chưa chọn file');

            $res = BG_BaoGia_BUS::uploadBanKy($id, $_FILES['file'], $u);
            $res['success']
                ? ResponseHelper::success($res['message'], $res['data'] ?? null)
                : ResponseHelper::error($res['message']);
            break;

        /** Nộp báo giá */
        case 'nopBaoGia':
            $id = Helper::postInt('bao_gia_id');

            $conNhan = BG_GoiThau_BUS::kiemTraConNhan($gt);
            if (!$conNhan['ok']) ResponseHelper::error($conNhan['message'], 403);
            kiemTraBaoGiaThuocPhien($id, $gt);
            $res = BG_BaoGia_BUS::nopBaoGia($id, $u);
            $res['success'] ? ResponseHelper::success($res['message']) : ResponseHelper::error($res['message']);
            break;

        default:
            ResponseHelper::error('Action không hợp lệ');
    }
} catch (Throwable $ex) {
    ResponseHelper::error(AppConfig::APP_DEBUG ? $ex->getMessage() : 'Lỗi hệ thống', 500);
}
