<?php
/**
 * download.php — Nhà thầu tải file mẫu báo giá của gói thầu (theo token QR).
 *
 * Quyền: đăng nhập + token hợp lệ (không dùng ma trận dm_phan_quyen vì
 * nhà thầu dùng tài khoản chung — xem chú thích ở portal/ajax_handler.php).
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../BUS/BG_HangHoa_BUS.php';
require_once __DIR__ . '/../../BUS/BG_GoiThau_BUS.php';
require_once __DIR__ . '/../../BUS/BG_BaoGia_BUS.php';
require_once __DIR__ . '/../../BUS/BG_TongHop_BUS.php';

function loiTaiFile(string $tieuDe, string $noiDung, string $token = ''): void
{
    http_response_code(400);
    header('Content-Type: text/html; charset=utf-8');
    $back = AppConfig::baseUrl('GUI/portal/index.php') . ($token !== '' ? '?t=' . urlencode($token) : '');
    echo '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
       . '<title>' . Helper::h($tieuDe) . '</title>'
       . '<link rel="stylesheet" href="' . Helper::h(AppConfig::baseUrl('assets/css/style.css')) . '">'
       . '</head><body class="portal-body"><div class="state-card is-danger">'
       . '<span class="state-icon">' . IconHelper::svg('alert-triangle', 40) . '</span>'
       . '<h2>' . Helper::h($tieuDe) . '</h2>'
       . '<p>' . Helper::h($noiDung) . '</p>'
       . ($token !== '' ? '<a class="btn btn-primary" href="' . Helper::h($back) . '">Quay lại trang chào giá</a>' : '')
       . '</div></body></html>';
    exit;
}

Helper::requireLogin();

// Token lấy từ URL, nhưng phải khớp token đang giữ trong phiên
$token = trim((string)Helper::get('t', ''));
$tokenPhien = (string)SessionHelper::get('portal_token', '');

if ($token === '' || $tokenPhien === '' || !hash_equals($tokenPhien, $token)) {
    loiTaiFile(
        'Phiên chào giá không hợp lệ',
        'Vui lòng mở lại trang chào giá bằng mã QR rồi tải file mẫu.',
        $token
    );
}

$goiThau = BG_GoiThau_DAL::getByToken($token);
if (!$goiThau) {
    loiTaiFile('Link không còn hiệu lực', 'Mã QR này đã bị thay thế. Vui lòng nhận link mới.', '');
}

// loai=mau (mặc định): file mẫu để điền giá
// loai=bao_gia&id=..: xuất lại báo giá đã nộp (từ cổng tra cứu)
$loai = (string)Helper::get('loai', 'mau');

try {
    if ($loai === 'bao_gia') {
        $baoGiaId = (int)Helper::get('id', 0);
        // Chỉ cho tải báo giá của MST vừa tra cứu thành công trong phiên này
        // → nhà thầu không dò được id báo giá của đối thủ.
        $mst = (string)SessionHelper::get('portal_mst_tra_cuu', '');
        if ($mst === '' || !BG_BaoGia_BUS::baoGiaThuocMst($baoGiaId, $mst, (int)$goiThau->id)) {
            loiTaiFile(
                'Không có quyền tải báo giá này',
                'Vui lòng tra cứu lại bằng mã số thuế của công ty rồi tải file.',
                $token
            );
        }
        $path = BG_TongHop_BUS::xuatChiTietBaoGia($baoGiaId, SessionHelper::userId());
        ExcelHelper::download($path, basename($path));
    }

    $path = BG_HangHoa_BUS::xuatFileMau((int)$goiThau->id);
    ExcelHelper::download($path, basename($path));
} catch (Throwable $ex) {
    loiTaiFile(
        'Không tải được file',
        AppConfig::APP_DEBUG ? $ex->getMessage() : 'Gói thầu chưa có danh mục hàng hóa.',
        $token
    );
}
