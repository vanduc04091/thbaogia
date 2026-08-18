<?php
/**
 * xem_file.php — Xem / tải file bản ký từ module Quản lý file.
 *
 * File nằm trong assets/uploads/ban_ky đã bị .htaccess chặn truy cập thẳng,
 * nên phải qua đây để kiểm tra đăng nhập + quyền của module này.
 * Trả file nhị phân → KHÔNG dùng ResponseHelper.
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../BUS/BG_QuanLyFile_BUS.php';

Helper::requireLogin();
PhanQuyenHelper::requireQuyenView(BG_QuanLyFile_BUS::MODULE_KEY);

$id    = (int)Helper::get('id', 0);
$taiVe = (int)Helper::get('tai_ve', 0) === 1;

function loiXemFile(string $msg): void
{
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
       . '<title>Không xem được file</title>'
       . '<link rel="stylesheet" href="' . Helper::h(AppConfig::baseUrl('assets/css/style.css')) . '">'
       . '</head><body><div class="state-card is-danger">'
       . '<span class="state-icon">' . IconHelper::svg('alert-triangle', 40) . '</span>'
       . '<h2>Không xem được file</h2><p>' . Helper::h($msg) . '</p>'
       . '<a class="btn btn-primary" href="' . Helper::h(AppConfig::baseUrl('GUI/BG_QuanLyFile/index.php')) . '">Quay lại</a>'
       . '</div></body></html>';
    exit;
}

if ($id <= 0) loiXemFile('Thiếu mã báo giá');

$r = BG_QuanLyFile_BUS::getById($id);
if (!$r) loiXemFile('Không tìm thấy bản ghi');
if (empty($r['file_ban_ky'])) loiXemFile('Báo giá này chưa có file bản ký');

// basename() chặn path traversal nếu DB bị chèn giá trị lạ
$path = BG_BaoGia_BUS::thuMucBanKy() . DIRECTORY_SEPARATOR . basename((string)$r['file_ban_ky']);
if (!is_file($path)) loiXemFile('File không còn trên hệ thống');

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mimeMap = [
    'pdf'  => 'application/pdf',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
];

// Tên gửi ra: dùng chính tên đã chuẩn hóa (mst_slug.ext) cho dễ lưu trữ
$tenGui = basename((string)$r['file_ban_ky']);
$ascii  = preg_replace('/[^A-Za-z0-9._-]/', '_', $tenGui);

if (ob_get_level()) ob_end_clean();

header('Content-Type: ' . ($mimeMap[$ext] ?? 'application/octet-stream'));
header('Content-Disposition: ' . ($taiVe ? 'attachment' : 'inline')
     . '; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($tenGui));
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($path);
exit;
