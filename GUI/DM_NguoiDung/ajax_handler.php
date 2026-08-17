<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../BUS/DM_NguoiDung_BUS.php';
require_once __DIR__ . '/../../BUS/DM_NhomTaiKhoan_BUS.php';

Helper::requireAjaxCsrf();

$action = Helper::post('action', '');
$u = SessionHelper::userId();
$MODULE = DM_NguoiDung_BUS::MODULE_KEY;

try {
    switch ($action) {
        case 'getPaged':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XEM);
            $page = Helper::postInt('page', 1);
            $size = Helper::postInt('pageSize', AppConfig::DEFAULT_PAGE_SIZE);
            $search = Helper::postStr('search');
            $daXoa = Helper::postInt('da_xoa', 0);
            $nhomId = Helper::postInt('nhom_tai_khoan_id', 0);
            $res = DM_NguoiDung_BUS::getPaged($page, $size, $search, $daXoa, $nhomId);
            ResponseHelper::paged($res['data'], $page, $size, $res['totalRecords']);
            break;

        case 'getById':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XEM);
            $id = Helper::postInt('id');
            $user = DM_NguoiDung_BUS::getById($id);
            if (!$user) ResponseHelper::error('Không tìm thấy');
            unset($user->mat_khau);
            ResponseHelper::success('OK', $user);
            break;

        case 'insert':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_THEM);
            $e = new DM_NguoiDung_PUBLIC();
            $e->tai_khoan = Helper::postStr('tai_khoan');
            $e->nhom_tai_khoan_id = Helper::postInt('nhom_tai_khoan_id');
            $e->trang_thai = Helper::postInt('trang_thai', 1);
            $e->nguoi_tao = $u;
            $matKhau = (string)Helper::post('mat_khau', '');
            $res = DM_NguoiDung_BUS::insert($e, $matKhau);
            $res['success'] ? ResponseHelper::success($res['message'], $res['data'] ?? null) : ResponseHelper::error($res['message']);
            break;

        case 'update':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_SUA);
            $e = new DM_NguoiDung_PUBLIC();
            $e->id = Helper::postInt('id');
            $e->tai_khoan = Helper::postStr('tai_khoan');
            $e->nhom_tai_khoan_id = Helper::postInt('nhom_tai_khoan_id');
            $e->trang_thai = Helper::postInt('trang_thai', 1);
            $e->nguoi_cap_nhat = $u;
            $res = DM_NguoiDung_BUS::update($e);
            $res['success'] ? ResponseHelper::success($res['message']) : ResponseHelper::error($res['message']);
            break;

        case 'resetPassword':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_SUA);
            $id = Helper::postInt('id');
            $newPass = (string)Helper::post('mat_khau_moi', '');
            $res = DM_NguoiDung_BUS::resetPassword($id, $newPass, $u);
            $res['success'] ? ResponseHelper::success($res['message']) : ResponseHelper::error($res['message']);
            break;

        case 'trash':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XOA);
            $res = DM_NguoiDung_BUS::trash(Helper::postInt('id'), $u);
            $res['success'] ? ResponseHelper::success($res['message']) : ResponseHelper::error($res['message']);
            break;

        case 'restore':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_SUA);
            $res = DM_NguoiDung_BUS::restore(Helper::postInt('id'), $u);
            $res['success'] ? ResponseHelper::success($res['message']) : ResponseHelper::error($res['message']);
            break;

        case 'delete':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XOA);
            $res = DM_NguoiDung_BUS::delete(Helper::postInt('id'), $u);
            $res['success'] ? ResponseHelper::success($res['message']) : ResponseHelper::error($res['message']);
            break;

        case 'getComboNhom':
            ResponseHelper::success('OK', DM_NhomTaiKhoan_BUS::getCombo());
            break;

        default:
            ResponseHelper::error('Action không hợp lệ');
    }
} catch (Throwable $ex) {
    ResponseHelper::error(AppConfig::APP_DEBUG ? $ex->getMessage() : 'Lỗi hệ thống', 500);
}