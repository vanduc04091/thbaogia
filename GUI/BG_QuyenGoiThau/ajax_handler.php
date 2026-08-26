<?php
/**
 * ajax_handler.php — Phân quyền xem theo gói thầu.
 *
 * Mỗi action tự kiểm tra quyền riêng (§3B.1).
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../BUS/BG_QuyenGoiThau_BUS.php';
require_once __DIR__ . '/../../BUS/BG_GoiThau_BUS.php';

Helper::requireAjaxCsrf();

$MODULE = BG_QuyenGoiThau_BUS::MODULE_KEY;
$u      = (int)SessionHelper::userId();
$action = Helper::post('action', '');

try {
    switch ($action) {

        /** Danh sách người dùng + cờ được xem gói thầu này */
        case 'getDanhSach':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XEM);
            $id = Helper::postInt('goi_thau_id');
            if ($id <= 0) ResponseHelper::error('Thiếu mã gói thầu');

            ResponseHelper::success('OK', [
                'nguoi_dung' => BG_QuyenGoiThau_BUS::danhSachPhanQuyen($id),
            ]);
            break;

        /** Lưu danh sách người được xem */
        case 'luu':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_SUA);
            $id = Helper::postInt('goi_thau_id');
            if ($id <= 0) ResponseHelper::error('Thiếu mã gói thầu');

            $json = (string)Helper::post('nguoi_dung_ids', '');
            $ids  = json_decode($json, true);
            if (!is_array($ids)) ResponseHelper::error('Dữ liệu gửi lên không hợp lệ');

            $res = BG_QuyenGoiThau_BUS::luu($id, $ids, $u);
            $res['success']
                ? ResponseHelper::success($res['message'], $res['data'] ?? null)
                : ResponseHelper::error($res['message']);
            break;

        /** Combo gói thầu — đã tự lọc theo quyền của người đang đăng nhập */
        case 'getComboGoiThau':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XEM);
            ResponseHelper::success('OK', BG_GoiThau_BUS::getCombo());
            break;

        default:
            ResponseHelper::error('Action không hợp lệ');
    }
} catch (Throwable $ex) {
    ResponseHelper::error(
        AppConfig::APP_DEBUG ? $ex->getMessage() : 'Có lỗi xảy ra, vui lòng thử lại.',
        500
    );
}
