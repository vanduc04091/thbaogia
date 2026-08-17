<?php
/**
 * index.php (root) - Vào thẳng hệ thống quản lý.
 * - Đã đăng nhập: chuyển sang dashboard (dashboard/index.php sẽ tự redirect tiếp).
 * - Chưa đăng nhập: chuyển sang trang login.
 */
require_once __DIR__ . '/bootstrap.php';

if (SessionHelper::isLoggedIn()) {
    header('Location: ' . AppConfig::baseUrl('GUI/dashboard/index.php'));
} else {
    header('Location: ' . AppConfig::baseUrl('GUI/auth/login.php'));
}
exit;