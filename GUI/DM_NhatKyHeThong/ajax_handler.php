<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../BUS/DM_NhatKyHeThong_BUS.php';

Helper::requireAjaxCsrf();

$action = Helper::post('action', '');
$u = SessionHelper::userId();
$MODULE = DM_NhatKyHeThong_BUS::MODULE_KEY;

try {
    switch ($action) {
        case 'getPaged':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XEM);
            $page = Helper::postInt('page', 1);
            $size = Helper::postInt('pageSize', AppConfig::DEFAULT_PAGE_SIZE);
            $search = Helper::postStr('search');
            $module = Helper::postStr('module');
            $nguoiDungId = Helper::postInt('nguoi_dung_id', 0);
            $fromDate = Helper::postStr('from_date');
            $toDate = Helper::postStr('to_date');
            $res = DM_NhatKyHeThong_BUS::getPaged($page, $size, $search, $module, $nguoiDungId, $fromDate, $toDate);
            ResponseHelper::paged($res['data'], $page, $size, $res['totalRecords']);
            break;

        case 'getById':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XEM);
            $id = Helper::postInt('id');
            $item = DM_NhatKyHeThong_BUS::getById($id);
            if (!$item) ResponseHelper::error('Không tìm thấy');
            ResponseHelper::success('OK', $item);
            break;

        case 'purge':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XOA);
            $days = Helper::postInt('days', 30);
            $res = DM_NhatKyHeThong_BUS::purgeOlderThan($days, $u);
            $res['success'] ? ResponseHelper::success($res['message']) : ResponseHelper::error($res['message']);
            break;

        case 'getModuleList':
            ResponseHelper::success('OK', DM_NhatKyHeThong_BUS::getModuleList());
            break;

        default:
            ResponseHelper::error('Action không hợp lệ');
    }
} catch (Throwable $ex) {
    ResponseHelper::error(AppConfig::APP_DEBUG ? $ex->getMessage() : 'Lỗi hệ thống', 500);
}