<?php
/**
 * xem_ban_ky.php — Xem/tải bản báo giá có dấu + chữ ký (phía quản trị).
 *
 * File nằm trong assets/uploads/ban_ky đã bị .htaccess chặn truy cập thẳng,
 * nên phải đi qua đây để được kiểm tra đăng nhập + quyền xem báo giá.
 * Trả file nhị phân → không dùng ResponseHelper.
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../BUS/BG_BaoGia_BUS.php';

Helper::requireLogin();
PhanQuyenHelper::requireQuyenView(BG_BaoGia_BUS::MODULE_KEY);

$id = (int)Helper::get('id', 0);
$taiVe = (int)Helper::get('tai_ve', 0) === 1;

function loiXem(string $msg): void
{
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
       . '<title>Không xem được file</title>'
       . '<link rel="stylesheet" href="' . Helper::h(AppConfig::baseUrl('assets/css/style.css')) . '">'
       . '</head><body><div class="state-card is-danger">'
       . '<span class="state-icon">' . IconHelper::svg('alert-triangle', 40) . '</span>'
       . '<h2>Không xem được bản ký</h2>'
       . '<p>' . Helper::h($msg) . '</p>'
       . '<a class="btn btn-primary" href="' . Helper::h(AppConfig::baseUrl('GUI/BG_BaoGia/index.php')) . '">Quay lại</a>'
       . '</div></body></html>';
    exit;
}

if ($id <= 0) loiXem('Thiếu mã báo giá');

$bg = BG_BaoGia_BUS::getById($id);
if (!$bg || (int)$bg->da_xoa === 1) loiXem('Không tìm thấy báo giá');
if (empty($bg->file_ban_ky_id)) loiXem('Báo giá này chưa có bản ký');

// Thông tin file nằm ở bảng bg_file, bg_bao_gia chỉ giữ khóa
$fileBk = BG_BaoGia_BUS::fileBanKy($id);
if (!$fileBk) loiXem('Không tìm thấy bản ghi file');

$path = BG_BaoGia_BUS::duongDanBanKy($id);
if ($path === '') loiXem('File bản ký không còn trên hệ thống');

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mimeMap = [
    'pdf'  => 'application/pdf',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
];
$mime = $mimeMap[$ext] ?? 'application/octet-stream';

// Tên file gửi ra: dùng tên gốc nhà thầu đặt cho dễ nhận biết
$tenGoc = (string)($fileBk->ten_file_goc ?: ('ban_ky_' . $id . '.' . $ext));
$ascii  = preg_replace('/[^A-Za-z0-9._-]/', '_', $tenGoc);

if (ob_get_level()) ob_end_clean();

// inline = xem ngay trên trình duyệt; attachment = tải về
$disposition = $taiVe ? 'attachment' : 'inline';
header('Content-Type: ' . $mime);
header('Content-Disposition: ' . $disposition . '; filename="' . $ascii . '"; '
     . "filename*=UTF-8''" . rawurlencode($tenGoc));
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($path);
exit;
