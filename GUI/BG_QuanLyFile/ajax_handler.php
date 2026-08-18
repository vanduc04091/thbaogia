<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../BUS/BG_QuanLyFile_BUS.php';
require_once __DIR__ . '/../../BUS/BG_GoiThau_BUS.php';

Helper::requireAjaxCsrf();

$action = Helper::post('action', '');
$u = SessionHelper::userId();
$MODULE = BG_QuanLyFile_BUS::MODULE_KEY;

try {
    switch ($action) {
        case 'getPaged':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XEM);
            $page = Helper::postInt('page', 1);
            $size = Helper::postInt('pageSize', AppConfig::DEFAULT_PAGE_SIZE);
            $res = BG_QuanLyFile_BUS::getPaged(
                $page,
                $size,
                Helper::postInt('goi_thau_id', 0),
                Helper::postStr('search'),
                Helper::postStr('loai_file'),
                Helper::postStr('sap_xep', 'moi_nhat')
            );
            ResponseHelper::paged($res['data'], $page, $size, $res['totalRecords']);
            break;

        case 'thongKe':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XEM);
            ResponseHelper::success('OK', BG_QuanLyFile_BUS::thongKe(Helper::postInt('goi_thau_id', 0)));
            break;

        case 'getById':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XEM);
            $r = BG_QuanLyFile_BUS::getById(Helper::postInt('id'));
            if (!$r) ResponseHelper::error('Không tìm thấy file');
            ResponseHelper::success('OK', $r);
            break;

        /** Xóa file bản ký của 1 báo giá */
        case 'xoaFile':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XOA);
            $res = BG_QuanLyFile_BUS::xoaFile(Helper::postInt('id'), $u);
            $res['success'] ? ResponseHelper::success($res['message']) : ResponseHelper::error($res['message']);
            break;

        /** Danh sách file có trên đĩa nhưng không bản ghi nào dùng */
        case 'fileMoCoi':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XEM);
            ResponseHelper::success('OK', BG_QuanLyFile_BUS::timFileMoCoi());
            break;

        case 'xoaMoCoi':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XOA);
            $res = BG_QuanLyFile_BUS::xoaFileMoCoi(Helper::postStr('ten_file'), $u);
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
