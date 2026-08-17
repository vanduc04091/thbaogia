<?php
/**
 * Bootstrap - Nạp sẵn các class PUBLIC/Common cần thiết
 * Mọi file GUI/ajax_handler nên require_once file này ở đầu.
 */

error_reporting(E_ALL);

require_once __DIR__ . '/PUBLIC/Common/AppConfig.php';

date_default_timezone_set(AppConfig::APP_TIMEZONE);
ini_set('display_errors', AppConfig::APP_DEBUG ? '1' : '0');

require_once __DIR__ . '/PUBLIC/Common/Constants.php';
require_once __DIR__ . '/PUBLIC/Common/MemcachedHelper.php';
require_once __DIR__ . '/PUBLIC/Common/SessionHelper.php';
require_once __DIR__ . '/PUBLIC/Common/ResponseHelper.php';
require_once __DIR__ . '/PUBLIC/Common/PaginationHelper.php';
require_once __DIR__ . '/PUBLIC/Common/Helper.php';
require_once __DIR__ . '/PUBLIC/Common/IconHelper.php';
require_once __DIR__ . '/PUBLIC/Common/PhanQuyenHelper.php';
require_once __DIR__ . '/DAL/database.php';

// Security headers — gửi trước mọi output (bỏ qua khi chạy CLI)
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Content-Type-Options: nosniff');          // chặn MIME sniffing
    header('X-Frame-Options: SAMEORIGIN');              // chống clickjacking
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-XSS-Protection: 0');                      // tắt bộ lọc XSS legacy (đã lỗi thời, gây lỗ hổng)
    header_remove('X-Powered-By');                      // không lộ phiên bản PHP
}

SessionHelper::start();