<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../BUS/BG_BaoGia_BUS.php';
require_once __DIR__ . '/../../BUS/BG_GoiThau_BUS.php';

Helper::requireAjaxCsrf();

$action = Helper::post('action', '');
$u = SessionHelper::userId();
$MODULE = BG_BaoGia_BUS::MODULE_KEY;

try {
    switch ($action) {
        case 'getPaged':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XEM);
            $page = Helper::postInt('page', 1);
            $size = Helper::postInt('pageSize', AppConfig::DEFAULT_PAGE_SIZE);
            $res = BG_BaoGia_BUS::getPaged(
                $page,
                $size,
                Helper::postInt('goi_thau_id', 0),
                Helper::postStr('search'),
                Helper::postInt('trang_thai', -1),
                Helper::postInt('da_xoa', 0),
                Helper::postInt('co_ban_ky', -1)
            );
            ResponseHelper::paged($res['data'], $page, $size, $res['totalRecords']);
            break;

        case 'getById':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XEM);
            $bg = BG_BaoGia_BUS::getById(Helper::postInt('id'));
            if (!$bg) ResponseHelper::error('Không tìm thấy báo giá');
            ResponseHelper::success('OK', $bg);
            break;

        /** Thông tin công ty + toàn bộ dòng chào giá — dùng cho modal chi tiết */
        case 'getChiTiet':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XEM);
            $id = Helper::postInt('id');
            $bg = BG_BaoGia_BUS::getById($id);
            if (!$bg) ResponseHelper::error('Không tìm thấy báo giá');
            ResponseHelper::success('OK', [
                'bao_gia'  => $bg,
                'chi_tiet' => BG_BaoGia_BUS::getChiTiet($id),
            ]);
            break;

        case 'update':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_SUA);
            $e = new BG_BaoGia_PUBLIC();
            $e->id               = Helper::postInt('id');
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

        /** Tích xác nhận đã nhận bản giấy */
        case 'xacNhan':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_SUA);
            $res = BG_BaoGia_BUS::xacNhan(Helper::postInt('id'), $u);
            $res['success'] ? ResponseHelper::success($res['message']) : ResponseHelper::error($res['message']);
            break;

        case 'boXacNhan':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_SUA);
            $res = BG_BaoGia_BUS::boXacNhan(Helper::postInt('id'), $u);
            $res['success'] ? ResponseHelper::success($res['message']) : ResponseHelper::error($res['message']);
            break;

        case 'tuChoi':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_SUA);
            $res = BG_BaoGia_BUS::tuChoi(Helper::postInt('id'), Helper::postStr('ly_do'), $u);
            $res['success'] ? ResponseHelper::success($res['message']) : ResponseHelper::error($res['message']);
            break;

        case 'trash':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XOA);
            $res = BG_BaoGia_BUS::trash(Helper::postInt('id'), $u);
            $res['success'] ? ResponseHelper::success($res['message']) : ResponseHelper::error($res['message']);
            break;

        case 'restore':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_SUA);
            $res = BG_BaoGia_BUS::restore(Helper::postInt('id'), $u);
            $res['success'] ? ResponseHelper::success($res['message']) : ResponseHelper::error($res['message']);
            break;

        case 'delete':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XOA);
            $res = BG_BaoGia_BUS::delete(Helper::postInt('id'), $u);
            $res['success'] ? ResponseHelper::success($res['message']) : ResponseHelper::error($res['message']);
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
