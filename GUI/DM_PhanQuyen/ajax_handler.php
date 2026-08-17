<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../BUS/DM_PhanQuyen_BUS.php';

Helper::requireAjaxCsrf();

$action = Helper::post('action', '');
$u = SessionHelper::userId();
$MODULE = DM_PhanQuyen_BUS::MODULE_KEY;

try {
    switch ($action) {
        case 'getMatrix':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XEM);
            $nhomId = Helper::postInt('nhom_tai_khoan_id');
            // Frontend dùng bitmask theo form_id
            $matrix = DM_PhanQuyen_BUS::getBitmaskByNhom($nhomId);
            ResponseHelper::success('OK', $matrix);
            break;

        case 'saveMatrix':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_SUA);
            $nhomId = Helper::postInt('nhom_tai_khoan_id');
            $permissions = Helper::post('permissions', []);
            $res = DM_PhanQuyen_BUS::saveMatrix($nhomId, $permissions, $u);
            $res['success'] ? ResponseHelper::success($res['message']) : ResponseHelper::error($res['message']);
            break;

        case 'grantAll':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_SUA);
            $nhomId = Helper::postInt('nhom_tai_khoan_id');
            $res = DM_PhanQuyen_BUS::grantAllToNhom($nhomId, $u);
            $res['success'] ? ResponseHelper::success($res['message']) : ResponseHelper::error($res['message']);
            break;

        default:
            ResponseHelper::error('Action không hợp lệ');
    }
} catch (Throwable $ex) {
    ResponseHelper::error(AppConfig::APP_DEBUG ? $ex->getMessage() : 'Lỗi hệ thống', 500);
}