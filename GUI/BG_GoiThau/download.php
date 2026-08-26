<?php
/**
 * download.php — Xuất THƯ MỜI CHÀO GIÁ (Word) của 1 gói thầu.
 *
 * Nội dung lấy từ mẫu MPS/thu_moi.docx (dựng từ chính docs/1.THU MOI CHAO GIA.docx),
 * tự điền số thư mời, thời hạn, đường dẫn cổng, tài khoản/mật khẩu và chèn mã QR thật.
 *
 * Trả file nhị phân → không dùng ResponseHelper (§3.5).
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../BUS/BG_GoiThau_BUS.php';
require_once __DIR__ . '/../../BUS/BG_HangHoa_BUS.php';

Helper::requireLogin();
PhanQuyenHelper::requireQuyenView(BG_GoiThau_BUS::MODULE_KEY);

$goiThauId = (int)Helper::get('goi_thau_id', 0);

// Chan tai file cua goi thau khong duoc phan quyen (3B.1)
if ($goiThauId > 0) {
    require_once __DIR__ . '/../../BUS/BG_QuyenGoiThau_BUS.php';
    BG_QuyenGoiThau_BUS::requireXem($goiThauId);
}

try {
    if ($goiThauId <= 0) throw new RuntimeException('Thiếu mã gói thầu');

    $path = BG_GoiThau_BUS::xuatThuMoi($goiThauId);
    WordHelper::download($path, basename($path));
} catch (Throwable $ex) {
    http_response_code(400);
    header('Content-Type: text/html; charset=utf-8');
    $back = AppConfig::baseUrl('GUI/BG_GoiThau/index.php');
    echo '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
       . '<title>Không xuất được thư mời</title>'
       . '<link rel="stylesheet" href="' . Helper::h(AppConfig::baseUrl('assets/css/style.css')) . '">'
       . '</head><body><div class="state-card is-warning">'
       . '<span class="state-icon">' . IconHelper::svg('alert-triangle', 40) . '</span>'
       . '<h2>Không xuất được thư mời</h2>'
       . '<p>' . Helper::h(AppConfig::APP_DEBUG
            ? $ex->getMessage()
            : 'Gói thầu chưa có mã QR hoặc thiếu file mẫu MPS/thu_moi.docx.') . '</p>'
       . '<a class="btn btn-primary" href="' . Helper::h($back) . '">Quay lại danh sách gói thầu</a>'
       . '</div></body></html>';
}
