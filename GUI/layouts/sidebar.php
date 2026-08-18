<?php
if (!isset($activeMenu)) $activeMenu = '';
$base = AppConfig::baseUrl('');

// Icon lấy từ IconHelper::moduleIconName() — không khai báo lặp ở đây
// để sidebar và ma trận phân quyền luôn dùng cùng một bộ icon.

/** Menu nghiệp vụ báo giá: [module key, nhãn, đường dẫn] */
$menuBaoGia = [
    ['BG_GoiThau', 'Gói thầu / Mời chào giá', 'GUI/BG_GoiThau/index.php'],
    ['BG_HangHoa', 'Hàng hóa gói thầu',       'GUI/BG_HangHoa/index.php'],
    ['BG_BaoGia',  'Báo giá nhà thầu',        'GUI/BG_BaoGia/index.php'],
    ['BG_TongHop', 'Tổng hợp báo giá',        'GUI/BG_TongHop/index.php'],
    ['BG_QuanLyFile', 'Quản lý file bản ký',  'GUI/BG_QuanLyFile/index.php'],
];

/** Menu hệ thống: [module key, nhãn, đường dẫn] */
$menuHeThong = [
    ['DM_NguoiDung',     'Người dùng',      'GUI/DM_NguoiDung/index.php'],
    ['DM_NhomTaiKhoan',  'Nhóm tài khoản',  'GUI/DM_NhomTaiKhoan/index.php'],
    ['DM_DanhSachForm',  'Danh sách form',  'GUI/DM_DanhSachForm/index.php'],
    ['DM_PhanQuyen',     'Phân quyền',      'GUI/DM_PhanQuyen/index.php'],
    ['DM_NhatKyHeThong', 'Nhật ký hệ thống','GUI/DM_NhatKyHeThong/index.php'],
];

// Chỉ hiện menu user có quyền xem
$locQuyen = static function (array $menu): array {
    return array_values(array_filter($menu, static function (array $m): bool {
        return PhanQuyenHelper::hasQuyen($m[0], PhanQuyenHelper::QUYEN_XEM);
    }));
};
$menuBaoGia  = $locQuyen($menuBaoGia);
$menuHeThong = $locQuyen($menuHeThong);
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <span class="sidebar-logo"><?= IconHelper::svg('shield-check', 22) ?></span>
        <h2><?= Helper::h(AppConfig::APP_NAME) ?></h2>
    </div>

    <nav class="sidebar-nav" aria-label="Menu chính">
        <?php if ($menuBaoGia): ?>
            <div class="nav-group">
                <div class="nav-group-title">Nghiệp vụ báo giá</div>
                <ul>
                    <?php foreach ($menuBaoGia as [$key, $label, $url]): ?>
                        <li class="nav-item <?= $activeMenu === $key ? 'active' : '' ?>">
                            <a href="<?= $base . $url ?>" <?= $activeMenu === $key ? 'aria-current="page"' : '' ?>>
                                <span class="nav-icon"><?= IconHelper::moduleIcon($key, 18) ?></span>
                                <span class="nav-label"><?= Helper::h($label) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($menuHeThong): ?>
            <div class="nav-group">
                <div class="nav-group-title">Hệ thống</div>
                <ul>
                    <?php foreach ($menuHeThong as [$key, $label, $url]): ?>
                        <li class="nav-item <?= $activeMenu === $key ? 'active' : '' ?>">
                            <a href="<?= $base . $url ?>" <?= $activeMenu === $key ? 'aria-current="page"' : '' ?>>
                                <span class="nav-icon"><?= IconHelper::moduleIcon($key, 18) ?></span>
                                <span class="nav-label"><?= Helper::h($label) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!$menuBaoGia && !$menuHeThong): ?>
            <p class="nav-empty">Bạn chưa được cấp quyền truy cập chức năng nào.</p>
        <?php endif; ?>
    </nav>
</aside>
