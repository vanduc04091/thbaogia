<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../BUS/DM_NhomTaiKhoan_BUS.php';

Helper::requireAjaxCsrf();

$action = Helper::post('action', '');
$u = SessionHelper::userId();
$MODULE = DM_NhomTaiKhoan_BUS::MODULE_KEY;

try {
    switch ($action) {
        case 'getPaged':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XEM);
            $page = Helper::postInt('page', 1);
            $size = Helper::postInt('pageSize', AppConfig::DEFAULT_PAGE_SIZE);
            $search = Helper::postStr('search');
            $daXoa = Helper::postInt('da_xoa', 0);
            $res = DM_NhomTaiKhoan_BUS::getPaged($page, $size, $search, $daXoa);
            ResponseHelper::paged($res['data'], $page, $size, $res['totalRecords']);
            break;

        case 'getById':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XEM);
            $id = Helper::postInt('id');
            $item = DM_NhomTaiKhoan_BUS::getById($id);
            if (!$item) ResponseHelper::error('Không tìm thấy');
            ResponseHelper::success('OK', $item);
            break;

        case 'insert':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_THEM);
            $e = new DM_NhomTaiKhoan_PUBLIC();
            $e->ma_nhom = Helper::postStr('ma_nhom');
            $e->ten_nhom = Helper::postStr('ten_nhom');
            $e->mo_ta = Helper::postStr('mo_ta');
            $e->trang_thai = Helper::postInt('trang_thai', 1);
            $e->la_admin = Helper::postInt('la_admin', 0);
            $e->nguoi_tao = $u;
            $res = DM_NhomTaiKhoan_BUS::insert($e);
            $res['success'] ? ResponseHelper::success($res['message'], $res['data'] ?? null) : ResponseHelper::error($res['message']);
            break;

        case 'update':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_SUA);
            $e = new DM_NhomTaiKhoan_PUBLIC();
            $e->id = Helper::postInt('id');
            $e->ma_nhom = Helper::postStr('ma_nhom');
            $e->ten_nhom = Helper::postStr('ten_nhom');
            $e->mo_ta = Helper::postStr('mo_ta');
            $e->trang_thai = Helper::postInt('trang_thai', 1);
            $e->la_admin = Helper::postInt('la_admin', 0);
            $e->nguoi_cap_nhat = $u;
            $res = DM_NhomTaiKhoan_BUS::update($e);
            $res['success'] ? ResponseHelper::success($res['message']) : ResponseHelper::error($res['message']);
            break;

        case 'trash':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XOA);
            $res = DM_NhomTaiKhoan_BUS::trash(Helper::postInt('id'), $u);
            $res['success'] ? ResponseHelper::success($res['message']) : ResponseHelper::error($res['message']);
            break;

        case 'restore':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_SUA);
            $res = DM_NhomTaiKhoan_BUS::restore(Helper::postInt('id'), $u);
            $res['success'] ? ResponseHelper::success($res['message']) : ResponseHelper::error($res['message']);
            break;

        case 'delete':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XOA);
            $res = DM_NhomTaiKhoan_BUS::delete(Helper::postInt('id'), $u);
            $res['success'] ? ResponseHelper::success($res['message']) : ResponseHelper::error($res['message']);
            break;

        default:
            ResponseHelper::error('Action không hợp lệ');
    }
} catch (Throwable $ex) {
    ResponseHelper::error(AppConfig::APP_DEBUG ? $ex->getMessage() : 'Lỗi hệ thống', 500);
}