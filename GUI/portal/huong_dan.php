<?php
/**
 * huong_dan.php — Trang hướng dẫn thực hiện chào giá (xem lại bất cứ lúc nào).
 *
 * Nhà thầu vào từ nút "Hướng dẫn" trên đầu trang chào giá, hoặc mở trực tiếp
 * bằng link kèm token. Nội dung lấy từ huong_dan_noi_dung.php — dùng chung
 * với popup tự hiện lần đầu.
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../BUS/BG_GoiThau_BUS.php';

$token = trim((string)Helper::get('t', ''));

// Token không bắt buộc: nhà thầu vẫn xem được hướng dẫn dù chưa quét QR.
// Có token thì hiện thêm nút quay lại đúng gói thầu.
$goiThau = $token !== '' ? BG_GoiThau_DAL::getByToken($token) : null;
$urlQuayLai = $goiThau
    ? AppConfig::baseUrl('GUI/portal/index.php') . '?t=' . urlencode($token)
    : '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hướng dẫn chào giá · <?= Helper::h(AppConfig::APP_NAME) ?></title>
<link rel="stylesheet" href="<?= AppConfig::baseUrl('assets/css/style.css') ?>?v=<?= Helper::h(AppConfig::APP_VERSION) ?>">
</head>
<body class="portal-body">

<header class="portal-header">
    <div class="portal-inner">
        <span class="portal-logo"><?= IconHelper::svg('info', 24) ?></span>
        <div>
            <h1>Hướng dẫn thực hiện chào giá qua hệ thống</h1>
            <?php if ($goiThau): ?>
                <div class="portal-sub">
                    Thông báo số <?= Helper::h($goiThau->so_thong_bao) ?> —
                    <?= Helper::h($goiThau->ten_goi_thau) ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="portal-right">
            <?php if ($urlQuayLai !== ''): ?>
                <a class="btn btn-sm btn-primary" href="<?= Helper::h($urlQuayLai) ?>">
                    <?= IconHelper::svg('arrow-left', 15) ?><span class="btn-label">Quay lại chào giá</span>
                </a>
            <?php endif; ?>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                <?= IconHelper::svg('printer', 15) ?><span class="btn-label">In</span>
            </button>
        </div>
    </div>
</header>

<main class="portal-main">
    <div class="card hd-trang">
        <div style="padding:26px 30px">
            <?php require __DIR__ . '/huong_dan_noi_dung.php'; ?>
        </div>
    </div>

    <?php if ($urlQuayLai !== ''): ?>
        <div style="text-align:center;margin-top:18px">
            <a class="btn btn-primary" href="<?= Helper::h($urlQuayLai) ?>">
                <?= IconHelper::svg('arrow-left', 16) ?>Quay lại trang chào giá
            </a>
        </div>
    <?php endif; ?>
</main>

<footer class="portal-footer">
    <?= Helper::h(AppConfig::APP_NAME) ?> · Mọi thắc mắc về gói thầu xin liên hệ bên mời chào giá.
</footer>

</body>
</html>
