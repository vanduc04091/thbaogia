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
// loai=ban_ky (mặc định) | catalog | catalog_excel | tat_ca (gói .zip)
$loai  = (string)Helper::get('loai', 'ban_ky');
if (!in_array($loai, ['ban_ky', 'catalog', 'catalog_excel', 'tat_ca'], true)) $loai = 'ban_ky';

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
// Tải TẤT CẢ tài liệu trong 1 file .zip — bên mời khỏi bấm từng file
if ($loai === 'tat_ca') {
    try {
        $zipPath = BG_BaoGia_BUS::xuatZipTaiLieu($id);
    } catch (Throwable $ex) {
        loiXem($ex->getMessage());
    }

    $tenZip = basename($zipPath);
    $ascii  = preg_replace('/[^A-Za-z0-9._-]/', '_', $tenZip);

    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $ascii . '"');
    header('Content-Length: ' . filesize($zipPath));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    readfile($zipPath);
    @unlink($zipPath);   // file tạm, xóa sau khi gửi
    exit;
}

// Thông tin file nằm ở bảng bg_file, bg_bao_gia chỉ giữ khóa
if ($loai === 'catalog') {
    if (empty($bg->file_catalog_id)) loiXem('Báo giá này chưa có catalog');
    $fileBk = BG_BaoGia_BUS::fileCatalog($id);
    $path   = BG_BaoGia_BUS::duongDanCatalog($id);
    $nhanFile = 'catalog';
} elseif ($loai === 'catalog_excel') {
    if (empty($bg->file_catalog_excel_id)) loiXem('Báo giá này chưa có file Excel chỉ dẫn');
    $fileBk = BG_BaoGia_BUS::fileCatalogExcel($id);
    $path   = BG_BaoGia_BUS::duongDanCatalogExcel($id);
    $nhanFile = 'Excel chỉ dẫn';
    $taiVe = true;   // Excel không xem được trên trình duyệt
} else {
    if (empty($bg->file_ban_ky_id)) loiXem('Báo giá này chưa có bản ký');
    $fileBk = BG_BaoGia_BUS::fileBanKy($id);
    $path   = BG_BaoGia_BUS::duongDanBanKy($id);
    $nhanFile = 'bản ký';
}

if (!$fileBk) loiXem('Không tìm thấy bản ghi file');
if ($path === '') loiXem('File ' . $nhanFile . ' không còn trên hệ thống');

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mimeMap = [
    'pdf'  => 'application/pdf',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'xls'  => 'application/vnd.ms-excel',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'doc'  => 'application/msword',
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
