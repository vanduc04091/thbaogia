<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../BUS/BG_TongHop_BUS.php';
require_once __DIR__ . '/../../BUS/BG_GoiThau_BUS.php';

Helper::requireAjaxCsrf();

$action = Helper::post('action', '');
$MODULE = BG_TongHop_BUS::MODULE_KEY;

try {
    switch ($action) {
        case 'getTongHop':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XEM);
            $goiThauId = Helper::postInt('goi_thau_id');
            if ($goiThauId <= 0) ResponseHelper::error('Chưa chọn gói thầu');

            $d = BG_TongHop_BUS::duLieuTongHop($goiThauId);
            // Chuyển entity gói thầu thành mảng cho JSON gọn
            $gt = $d['goi_thau'];
            ResponseHelper::success('OK', [
                'goi_thau' => [
                    'id'           => (int)$gt->id,
                    'so_thong_bao' => $gt->so_thong_bao,
                    'ten_goi_thau' => $gt->ten_goi_thau,
                    'han_cuoi'     => $gt->han_cuoi,
                    'trang_thai'   => (int)$gt->trang_thai,
                    'so_bao_gia'   => (int)$gt->so_bao_gia,
                ],
                'nha_thau' => $d['nha_thau'],
                'hang_hoa' => $d['hang_hoa'],
                'tong_ket' => $d['tong_ket'],
            ]);
            break;

        case 'getComboGoiThau':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XEM);
            ResponseHelper::success('OK', BG_GoiThau_BUS::getCombo());
            break;

        default:
            ResponseHelper::error('Action không hợp lệ');
    }
} catch (Throwable $ex) {
    ResponseHelper::error(AppConfig::APP_DEBUG ? $ex->getMessage() : 'Lỗi hệ thống', 500);
}
