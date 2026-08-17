<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../DAL/DM_NhatKyHeThong_DAL.php';

if (SessionHelper::isLoggedIn()) {
    DM_NhatKyHeThong_DAL::log(SessionHelper::userId(), Constants::MODULE_HE_THONG, 'Đăng xuất', 'dm_nguoi_dung', SessionHelper::userId());
}
SessionHelper::destroy();
header('Location: ' . AppConfig::baseUrl('GUI/auth/login.php'));
exit;