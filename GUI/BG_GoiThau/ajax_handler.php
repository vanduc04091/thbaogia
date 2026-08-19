<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../BUS/BG_GoiThau_BUS.php';
require_once __DIR__ . '/../../PUBLIC/Common/QrHelper.php';

Helper::requireAjaxCsrf();

$action = Helper::post('action', '');
$u = SessionHelper::userId();
$MODULE = BG_GoiThau_BUS::MODULE_KEY;

try {
    switch ($action) {
        case 'getPaged':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XEM);
            $page = Helper::postInt('page', 1);
            $size = Helper::postInt('pageSize', AppConfig::DEFAULT_PAGE_SIZE);
            $res = BG_GoiThau_BUS::getPaged(
                $page,
                $size,
                Helper::postStr('search'),
                Helper::postInt('da_xoa', 0),
                Helper::postInt('trang_thai', -1),
                Helper::postStr('trang_thai_bao_gia')
            );
            ResponseHelper::paged($res['data'], $page, $size, $res['totalRecords']);
            break;

        case 'getById':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XEM);
            $gt = BG_GoiThau_BUS::getById(Helper::postInt('id'));
            if (!$gt) ResponseHelper::error('Không tìm thấy gói thầu');
            // Kèm trạng thái báo giá đã tính để GUI khỏi lặp lại logic thời gian
            $data = get_object_vars($gt);
            $data['trang_thai_bao_gia'] = $gt->trangThaiBaoGia();
            $data['ten_trang_thai_bao_gia'] = BG_GoiThau_PUBLIC::tenTrangThaiBaoGia($data['trang_thai_bao_gia']);
            ResponseHelper::success('OK', $data);
            break;

        /** Thông tin link QR + ảnh SVG để hiển thị/in */
        case 'getQr':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XEM);
            $gt = BG_GoiThau_BUS::getById(Helper::postInt('id'));
            if (!$gt) ResponseHelper::error('Không tìm thấy gói thầu');
            $url = BG_GoiThau_BUS::urlPortal($gt->token);
            ResponseHelper::success('OK', [
                'url'                    => $url,
                'svg'                    => QrHelper::svg($url, 260),
                'so_thong_bao'           => $gt->so_thong_bao,
                'ten_goi_thau'           => $gt->ten_goi_thau,
                'han_cuoi'               => $gt->han_cuoi,
                'thoi_gian_mo_bao_gia'   => $gt->thoi_gian_mo_bao_gia,
                'thoi_gian_dong_bao_gia' => $gt->thoi_gian_dong_bao_gia,
                'trang_thai'             => (int)$gt->trang_thai,
                'trang_thai_bao_gia'     => $gt->trangThaiBaoGia(),
            ]);
            break;

        case 'insert':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_THEM);
            $e = new BG_GoiThau_PUBLIC();
            $e->so_thong_bao       = Helper::postStr('so_thong_bao');
            $e->ten_goi_thau       = Helper::postStr('ten_goi_thau');
            $e->noi_dung           = Helper::postStr('noi_dung');
            $e->ngay_phat_hanh     = Helper::postStr('ngay_phat_hanh');
            // input datetime-local gửi "Y-m-dTH:i" → chuẩn hóa về DATETIME của MySQL
            $e->thoi_gian_mo_bao_gia   = Helper::chuanHoaDateTime(Helper::postStr('thoi_gian_mo_bao_gia'));
            $e->thoi_gian_dong_bao_gia = Helper::chuanHoaDateTime(Helper::postStr('thoi_gian_dong_bao_gia'));
            $e->han_cuoi           = Helper::postStr('han_cuoi');
            $e->thoi_gian_hop_dong = Helper::postInt('thoi_gian_hop_dong', 0);
            $e->hieu_luc_bao_gia   = Helper::postInt('hieu_luc_bao_gia', 180);
            $e->trang_thai         = Helper::postInt('trang_thai', BG_GoiThau_PUBLIC::TT_NHAP);
            $e->nguoi_tao          = $u;
            $res = BG_GoiThau_BUS::insert($e);
            $res['success']
                ? ResponseHelper::success($res['message'], $res['data'] ?? null)
                : ResponseHelper::error($res['message']);
            break;

        case 'update':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_SUA);
            $e = new BG_GoiThau_PUBLIC();
            $e->id                 = Helper::postInt('id');
            $e->so_thong_bao       = Helper::postStr('so_thong_bao');
            $e->ten_goi_thau       = Helper::postStr('ten_goi_thau');
            $e->noi_dung           = Helper::postStr('noi_dung');
            $e->ngay_phat_hanh     = Helper::postStr('ngay_phat_hanh');
            // input datetime-local gửi "Y-m-dTH:i" → chuẩn hóa về DATETIME của MySQL
            $e->thoi_gian_mo_bao_gia   = Helper::chuanHoaDateTime(Helper::postStr('thoi_gian_mo_bao_gia'));
            $e->thoi_gian_dong_bao_gia = Helper::chuanHoaDateTime(Helper::postStr('thoi_gian_dong_bao_gia'));
            $e->han_cuoi           = Helper::postStr('han_cuoi');
            $e->thoi_gian_hop_dong = Helper::postInt('thoi_gian_hop_dong', 0);
            $e->hieu_luc_bao_gia   = Helper::postInt('hieu_luc_bao_gia', 180);
            $e->trang_thai         = Helper::postInt('trang_thai', BG_GoiThau_PUBLIC::TT_NHAP);
            $e->nguoi_cap_nhat     = $u;
            $res = BG_GoiThau_BUS::update($e);
            $res['success'] ? ResponseHelper::success($res['message']) : ResponseHelper::error($res['message']);
            break;

        case 'doiTrangThai':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_SUA);
            $res = BG_GoiThau_BUS::doiTrangThai(Helper::postInt('id'), Helper::postInt('trang_thai'), $u);
            $res['success'] ? ResponseHelper::success($res['message']) : ResponseHelper::error($res['message']);
            break;

        case 'trash':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XOA);
            $res = BG_GoiThau_BUS::trash(Helper::postInt('id'), $u);
            $res['success'] ? ResponseHelper::success($res['message']) : ResponseHelper::error($res['message']);
            break;

        case 'restore':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_SUA);
            $res = BG_GoiThau_BUS::restore(Helper::postInt('id'), $u);
            $res['success'] ? ResponseHelper::success($res['message']) : ResponseHelper::error($res['message']);
            break;

        case 'delete':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XOA);
            $res = BG_GoiThau_BUS::delete(Helper::postInt('id'), $u);
            $res['success'] ? ResponseHelper::success($res['message']) : ResponseHelper::error($res['message']);
            break;

        default:
            ResponseHelper::error('Action không hợp lệ');
    }
} catch (Throwable $ex) {
    ResponseHelper::error(AppConfig::APP_DEBUG ? $ex->getMessage() : 'Lỗi hệ thống', 500);
}
