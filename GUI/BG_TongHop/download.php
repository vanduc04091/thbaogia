<?php
/**
 * download.php — Xuất Excel tổng hợp báo giá của 1 gói thầu.
 *
 * Chỉ gộp các báo giá đã XÁC NHẬN bản giấy (BG_TongHop_BUS lo việc lọc).
 * Trả file nhị phân → không dùng ResponseHelper.
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../BUS/BG_TongHop_BUS.php';

Helper::requireLogin();
PhanQuyenHelper::requireQuyenView(BG_TongHop_BUS::MODULE_KEY);

$goiThauId = (int)Helper::get('goi_thau_id', 0);

// Chan tai file cua goi thau khong duoc phan quyen (3B.1)
if ($goiThauId > 0) {
    require_once __DIR__ . '/../../BUS/BG_QuyenGoiThau_BUS.php';
    BG_QuyenGoiThau_BUS::requireXem($goiThauId);
}

try {
    if ($goiThauId <= 0) throw new RuntimeException('Thiếu mã gói thầu');
    $path = BG_TongHop_BUS::xuatExcel($goiThauId, SessionHelper::userId());
    ExcelHelper::download($path, basename($path));
} catch (Throwable $ex) {
    http_response_code(400);
    header('Content-Type: text/html; charset=utf-8');
    $back = AppConfig::baseUrl('GUI/BG_TongHop/index.php')
          . ($goiThauId > 0 ? '?goi_thau_id=' . $goiThauId : '');
    echo '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
       . '<title>Không xuất được tổng hợp</title>'
       . '<link rel="stylesheet" href="' . Helper::h(AppConfig::baseUrl('assets/css/style.css')) . '">'
       . '</head><body><div class="state-card is-warning">'
       . '<span class="state-icon">' . IconHelper::svg('alert-triangle', 40) . '</span>'
       . '<h2>Chưa xuất được bảng tổng hợp</h2>'
       . '<p>' . Helper::h($ex->getMessage()) . '</p>'
       . '<a class="btn btn-primary" href="' . Helper::h($back) . '">Quay lại</a>'
       . '</div></body></html>';
}
