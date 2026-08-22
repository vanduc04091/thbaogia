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


        /** Chi tiết 1 file theo ID FILE (mọi nhóm: bản ký, catalog, Excel) */
        case 'getFile':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XEM);
            $r = BG_QuanLyFile_BUS::getFileById(Helper::postInt('id'));
            if (!$r) ResponseHelper::error('Không tìm thấy file');

            // Bổ sung thông tin nhà thầu + tên nhóm cho hộp thoại xem
            $bgF = BG_File_DAL::baoGiaDungFile((int)$r['id']);
            $tenNhom = [
                'ban_ky'        => 'Bản báo giá đã ký',
                'catalog'       => 'Catalog đã ký',
                'catalog_excel' => 'Excel chỉ dẫn vị trí tài liệu',
            ];
            $r['ten_cong_ty'] = $bgF['ten_cong_ty'] ?? '';
            $r['ma_so_thue']  = $bgF['ma_so_thue'] ?? '';
            $r['bao_gia_id']  = (int)($bgF['id'] ?? 0);

            // Số thông báo gói thầu — hộp thoại xem có hiển thị
            $gtF = !empty($bgF['goi_thau_id'])
                 ? BG_GoiThau_BUS::getById((int)$bgF['goi_thau_id']) : null;
            $r['so_thong_bao'] = $gtF->so_thong_bao ?? '';
            $r['ten_goi_thau'] = $gtF->ten_goi_thau ?? '';
            $r['ten_nhom']    = $tenNhom[$r['nhom_file']] ?? $r['nhom_file'];
            ResponseHelper::success('OK', $r);
            break;

        /** Xóa 1 file theo ID FILE */
        case 'xoaFile':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XOA);
            $res = BG_QuanLyFile_BUS::xoaFileTheoId(Helper::postInt('id'), $u);
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
