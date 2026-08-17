<?php
if (!isset($pageTitle)) $pageTitle = AppConfig::APP_NAME;
if (!isset($activeMenu)) $activeMenu = '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= Helper::h($pageTitle) ?> · <?= Helper::h(AppConfig::APP_NAME) ?></title>
<link rel="stylesheet" href="<?= AppConfig::baseUrl('assets/css/style.css') ?>?v=<?= Helper::h(AppConfig::APP_VERSION) ?>">
<script src="<?= AppConfig::baseUrl('assets/js/jquery-3.7.1.min.js') ?>"></script>
<script>
var APP_BASE = "<?= AppConfig::baseUrl('') ?>";
var CSRF_TOKEN = "<?= Helper::h(SessionHelper::csrfToken()) ?>";
</script>
<script src="<?= AppConfig::baseUrl('assets/js/app.js') ?>?v=<?= Helper::h(AppConfig::APP_VERSION) ?>"></script>
</head>
<body>
<a href="#main-content" class="skip-link">Bỏ qua tới nội dung chính</a>

<div class="app-wrapper">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <div class="sidebar-scrim" id="sidebarScrim" hidden></div>

    <div class="main">
        <header class="topbar">
            <div class="topbar-left">
                <button type="button" class="icon-btn sidebar-toggle" id="sidebarToggle" aria-label="Ẩn/hiện menu">
                    <?= IconHelper::svg('menu', 20) ?>
                </button>
                <h1 class="page-title"><?= Helper::h($pageTitle) ?></h1>
            </div>
            <div class="topbar-right">
                <div class="user-chip" title="<?= Helper::h(SessionHelper::taiKhoan()) ?>">
                    <span class="user-avatar" aria-hidden="true"><?= Helper::h(mb_strtoupper(mb_substr(SessionHelper::hoTen() ?: 'U', 0, 1))) ?></span>
                    <span class="user-meta">
                        <span class="user-name"><?= Helper::h(SessionHelper::hoTen()) ?></span>
                        <span class="user-role"><?= Helper::h(SessionHelper::get('ten_nhom', '')) ?></span>
                    </span>
                </div>
                <a href="<?= AppConfig::baseUrl('GUI/auth/logout.php') ?>" class="btn btn-sm btn-outline-secondary">
                    <?= IconHelper::svg('log-out', 16) ?><span class="btn-label">Đăng xuất</span>
                </a>
            </div>
        </header>

        <main class="content" id="main-content">
