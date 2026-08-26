<?php
/**
 * download.php — Tải file Excel mẫu chào giá của 1 gói thầu.
 *
 * Trả về file nhị phân nên KHÔNG dùng ResponseHelper (JSON).
 * Vẫn kiểm tra đăng nhập + quyền xem như mọi trang khác.
 * Dùng GET vì đây là thao tác chỉ đọc, không thay đổi dữ liệu.
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../BUS/BG_HangHoa_BUS.php';

Helper::requireLogin();
PhanQuyenHelper::requireQuyenView(BG_HangHoa_BUS::MODULE_KEY);

$goiThauId = (int)Helper::get('goi_thau_id', 0);

// Chan tai file cua goi thau khong duoc phan quyen (3B.1)
if ($goiThauId > 0) {
    require_once __DIR__ . '/../../BUS/BG_QuyenGoiThau_BUS.php';
    BG_QuyenGoiThau_BUS::requireXem($goiThauId);
}

try {
    if ($goiThauId <= 0) {
        throw new RuntimeException('Thiếu mã gói thầu');
    }
    // Dùng mẫu DANH MỤC (không đòi hỏi đã có hàng hóa) — gói thầu mới tạo
    // chưa có dòng nào vẫn phải tải được file mẫu để import.
    $path = BG_HangHoa_BUS::xuatFileMauDanhMuc($goiThauId);
    ExcelHelper::download($path, basename($path));
} catch (Throwable $ex) {
    http_response_code(400);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
       . '<title>Không tải được file</title>'
       . '<link rel="stylesheet" href="' . Helper::h(AppConfig::baseUrl('assets/css/style.css')) . '">'
       . '</head><body><div class="state-card is-danger">'
       . '<span class="state-icon">' . IconHelper::svg('alert-triangle', 40) . '</span>'
       . '<h2>Không tải được file mẫu</h2>'
       . '<p>' . Helper::h(AppConfig::APP_DEBUG ? $ex->getMessage() : 'Có lỗi xảy ra khi tạo file.') . '</p>'
       . '<a class="btn btn-primary" href="' . Helper::h(AppConfig::baseUrl('GUI/BG_HangHoa/index.php')) . '">Quay lại</a>'
       . '</div></body></html>';
}
