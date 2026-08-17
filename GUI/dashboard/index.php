<?php
/**
 * dashboard/index.php — Trang tổng quan sau đăng nhập.
 *
 * Hiện số liệu nghiệp vụ báo giá + việc cần làm. Nếu user không có quyền
 * xem gói thầu (ví dụ tài khoản nhà thầu dùng chung) thì chuyển sang trang
 * phù hợp với quyền của họ thay vì hiện bảng trống.
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../BUS/BG_GoiThau_BUS.php';
require_once __DIR__ . '/../../BUS/BG_BaoGia_BUS.php';

Helper::requireLogin();

$xemGoiThau = PhanQuyenHelper::hasQuyen('BG_GoiThau', PhanQuyenHelper::QUYEN_XEM);
$xemBaoGia  = PhanQuyenHelper::hasQuyen('BG_BaoGia', PhanQuyenHelper::QUYEN_XEM);

// Không có quyền nghiệp vụ nào → chuyển tới trang đầu tiên mà user xem được
if (!$xemGoiThau && !$xemBaoGia) {
    $fallback = [
        ['DM_NguoiDung',     'GUI/DM_NguoiDung/index.php'],
        ['DM_NhomTaiKhoan',  'GUI/DM_NhomTaiKhoan/index.php'],
        ['DM_DanhSachForm',  'GUI/DM_DanhSachForm/index.php'],
        ['DM_PhanQuyen',     'GUI/DM_PhanQuyen/index.php'],
        ['DM_NhatKyHeThong', 'GUI/DM_NhatKyHeThong/index.php'],
    ];
    foreach ($fallback as [$mod, $url]) {
        if (PhanQuyenHelper::hasQuyen($mod, PhanQuyenHelper::QUYEN_XEM)) {
            header('Location: ' . AppConfig::baseUrl($url));
            exit;
        }
    }
    // Tài khoản nhà thầu: không có quyền quản trị nào — hiện hướng dẫn quét QR
    $pageTitle = 'Trang chủ';
    $activeMenu = '';
    require __DIR__ . '/../layouts/header.php';
    ?>
    <div class="card">
        <div class="empty-state">
            <?= IconHelper::svg('qr-code', 42) ?>
            <h3>Vui lòng quét mã QR của gói thầu</h3>
            <p>
                Tài khoản này dùng để chào giá. Hãy quét mã QR hoặc mở link chào giá
                do bên mời chào giá cung cấp để vào đúng gói thầu cần báo giá.
            </p>
            <a class="btn btn-secondary" href="<?= AppConfig::baseUrl('GUI/auth/logout.php') ?>">
                <?= IconHelper::svg('log-out', 16) ?>Đăng xuất
            </a>
        </div>
    </div>
    <?php
    require __DIR__ . '/../layouts/footer.php';
    exit;
}

$tkGoiThau = $xemGoiThau ? BG_GoiThau_BUS::thongKe() : null;
$tkBaoGia  = $xemBaoGia ? BG_BaoGia_BUS::thongKe() : null;

// Gói thầu sắp đến hạn / đã có báo giá chờ xác nhận — việc cần làm
$dsGoiThau = $xemGoiThau ? BG_GoiThau_BUS::getPaged(1, 8, '', 0, -1)['data'] : [];

$pageTitle  = 'Trang chủ';
$activeMenu = '';
require __DIR__ . '/../layouts/header.php';
?>

<div class="stat-grid" style="margin-bottom:18px">
    <?php if ($tkGoiThau): ?>
        <div class="stat-card">
            <span class="stat-icon"><?= IconHelper::svg('clipboard-list', 20) ?></span>
            <span class="stat-value"><?= (int)$tkGoiThau['dang_mo'] ?></span>
            <span class="stat-label">Gói thầu đang mở nhận báo giá</span>
        </div>
        <div class="stat-card">
            <span class="stat-icon amber"><?= IconHelper::svg('clock', 20) ?></span>
            <span class="stat-value"><?= (int)$tkGoiThau['qua_han'] ?></span>
            <span class="stat-label">Đang mở nhưng đã quá hạn cuối</span>
        </div>
    <?php endif; ?>

    <?php if ($tkBaoGia): ?>
        <div class="stat-card">
            <span class="stat-icon pink"><?= IconHelper::svg('file-spreadsheet', 20) ?></span>
            <span class="stat-value"><?= (int)$tkBaoGia['cho_xac_nhan'] ?></span>
            <span class="stat-label">Báo giá chờ xác nhận bản giấy</span>
        </div>
        <div class="stat-card">
            <span class="stat-icon blue"><?= IconHelper::svg('check-circle', 20) ?></span>
            <span class="stat-value"><?= (int)$tkBaoGia['da_xac_nhan'] ?></span>
            <span class="stat-label">Báo giá đã xác nhận, sẵn sàng tổng hợp</span>
        </div>
    <?php endif; ?>
</div>

<?php if ($xemGoiThau): ?>
    <div class="card">
        <div class="toolbar">
            <div class="left">
                <h2 style="font-size:15px;margin:0">Gói thầu gần đây</h2>
            </div>
            <div class="right">
                <a class="btn btn-outline-primary" href="<?= AppConfig::baseUrl('GUI/BG_GoiThau/index.php') ?>">
                    <?= IconHelper::svg('external-link', 16) ?><span class="btn-label">Xem tất cả</span>
                </a>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Số thông báo</th>
                        <th>Tên gói thầu</th>
                        <th>Hạn cuối</th>
                        <th>Hàng hóa</th>
                        <th>Báo giá</th>
                        <th>Trạng thái</th>
                        <th class="col-actions">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($dsGoiThau)): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <?= IconHelper::svg('clipboard-list', 40) ?>
                                <h3>Chưa có gói thầu nào</h3>
                                <p>Tạo thông báo mời chào giá, nhập danh mục hàng hóa rồi phát mã QR cho nhà thầu.</p>
                                <a class="btn btn-primary" href="<?= AppConfig::baseUrl('GUI/BG_GoiThau/index.php') ?>">
                                    <?= IconHelper::svg('plus', 16) ?>Tạo gói thầu đầu tiên
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($dsGoiThau as $g):
                        $tt = (int)$g['trang_thai'];
                        $quaHan = !empty($g['han_cuoi']) && $g['han_cuoi'] < date('Y-m-d');
                        $badge = $tt === BG_GoiThau_PUBLIC::TT_DANG_MO ? 'badge-success'
                               : ($tt === BG_GoiThau_PUBLIC::TT_DA_DONG ? 'badge-warning'
                               : ($tt === BG_GoiThau_PUBLIC::TT_DA_TONG_HOP ? 'badge-info' : 'badge-neutral'));
                        $soXN = (int)$g['so_bao_gia_xac_nhan'];
                    ?>
                        <tr>
                            <td><span class="text-mono cell-main"><?= Helper::h($g['so_thong_bao']) ?></span></td>
                            <td><span class="cell-main"><?= Helper::h(mb_substr($g['ten_goi_thau'], 0, 70)) ?></span></td>
                            <td>
                                <?php if (empty($g['han_cuoi'])): ?>
                                    <span class="text-muted">—</span>
                                <?php elseif ($quaHan && $tt === BG_GoiThau_PUBLIC::TT_DANG_MO): ?>
                                    <span class="badge badge-danger"><?= Helper::h(Helper::formatDate($g['han_cuoi'])) ?></span>
                                <?php else: ?>
                                    <?= Helper::h(Helper::formatDate($g['han_cuoi'])) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ((int)$g['so_hang_hoa'] > 0): ?>
                                    <?= (int)$g['so_hang_hoa'] ?> mục
                                <?php else: ?>
                                    <span class="badge badge-warning">Chưa có</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ((int)$g['so_bao_gia'] > 0): ?>
                                    <?= (int)$g['so_bao_gia'] ?> bản<?= $soXN > 0 ? ' · ' . $soXN . ' đã XN' : '' ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?= $badge ?>"><?= Helper::h(BG_GoiThau_PUBLIC::tenTrangThai($tt)) ?></span></td>
                            <td class="col-actions">
                                <span class="row-actions">
                                    <a class="btn btn-sm btn-outline-secondary" title="Hàng hóa"
                                       href="<?= AppConfig::baseUrl('GUI/BG_HangHoa/index.php') ?>?goi_thau_id=<?= (int)$g['id'] ?>">
                                        <?= IconHelper::svg('package', 15) ?>
                                    </a>
                                    <a class="btn btn-sm btn-outline-secondary" title="Báo giá"
                                       href="<?= AppConfig::baseUrl('GUI/BG_BaoGia/index.php') ?>?goi_thau_id=<?= (int)$g['id'] ?>">
                                        <?= IconHelper::svg('file-spreadsheet', 15) ?>
                                    </a>
                                    <?php if ($soXN > 0): ?>
                                        <a class="btn btn-sm btn-outline-primary" title="Tổng hợp"
                                           href="<?= AppConfig::baseUrl('GUI/BG_TongHop/index.php') ?>?goi_thau_id=<?= (int)$g['id'] ?>">
                                            <?= IconHelper::svg('bar-chart', 15) ?>
                                        </a>
                                    <?php endif; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
