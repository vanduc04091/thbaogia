<?php
/**
 * index.php — Cổng chào giá cho nhà thầu (vào bằng link/QR có token).
 *
 * Luồng: quét QR → đăng nhập tài khoản chung → khai thông tin công ty
 *        → tải file mẫu / điền giá trực tiếp hoặc import → nộp.
 *
 * Layout độc lập (không sidebar) vì người dùng là nhà thầu bên ngoài,
 * không phải nhân viên back-office.
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../BUS/BG_GoiThau_BUS.php';
require_once __DIR__ . '/../../BUS/BG_BaoGia_BUS.php';
require_once __DIR__ . '/../../BUS/BG_HangHoa_BUS.php';

$token = trim((string)Helper::get('t', ''));

/** Trang thông báo trạng thái dùng chung — không cần đăng nhập vẫn xem được */
function trangTrangThai(string $loai, string $tieuDe, string $noiDung, string $icon, array $nut = []): void
{
    $css = AppConfig::baseUrl('assets/css/style.css');
    echo '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
       . '<title>' . Helper::h($tieuDe) . ' · ' . Helper::h(AppConfig::APP_NAME) . '</title>'
       . '<link rel="stylesheet" href="' . Helper::h($css) . '?v=' . Helper::h(AppConfig::APP_VERSION) . '">'
       . '</head><body class="portal-body">'
       . '<div class="state-card is-' . Helper::h($loai) . '">'
       . '<span class="state-icon">' . IconHelper::svg($icon, 42) . '</span>'
       . '<h2>' . Helper::h($tieuDe) . '</h2>'
       . '<p>' . $noiDung . '</p>';
    foreach ($nut as $n) {
        echo '<a class="btn ' . Helper::h($n['class']) . '" href="' . Helper::h($n['url']) . '">'
           . Helper::h($n['label']) . '</a> ';
    }
    echo '</div></body></html>';
    exit;
}

// ============ 1. Kiểm tra token ============
if ($token === '') {
    trangTrangThai(
        'danger',
        'Thiếu mã truy cập',
        'Đường dẫn không có mã gói thầu. Vui lòng quét lại mã QR do bên mời chào giá cung cấp.',
        'qr-code'
    );
}

$goiThau = BG_GoiThau_DAL::getByToken($token);
if (!$goiThau) {
    trangTrangThai(
        'danger',
        'Link không còn hiệu lực',
        'Mã QR này đã bị thay thế hoặc gói thầu không còn tồn tại.<br>'
        . 'Vui lòng liên hệ bên mời chào giá để nhận link mới.',
        'x-circle'
    );
}

// ============ 2. Yêu cầu đăng nhập (tài khoản dùng chung cho nhà thầu) ============
if (!SessionHelper::isLoggedIn()) {
    // Ghi nhớ token để sau khi đăng nhập quay lại đúng gói thầu
    SessionHelper::set('portal_redirect_token', $token);
    $loginUrl = AppConfig::baseUrl('GUI/auth/login.php') . '?portal=' . urlencode($token);
    header('Location: ' . $loginUrl);
    exit;
}

// Gắn token vào phiên — ajax_handler chỉ tin token từ session, không tin từ POST
SessionHelper::set('portal_token', $token);

// ============ 3. Gói thầu còn nhận báo giá không? ============
$conNhan = BG_GoiThau_BUS::kiemTraConNhan($goiThau);

// ============ 4. Báo giá đang làm trong phiên (nếu có) ============
$baoGiaId = (int)SessionHelper::get('portal_bao_gia_id', 0);
$baoGia = null;
if ($baoGiaId > 0) {
    $bg = BG_BaoGia_BUS::getById($baoGiaId);
    // Chỉ nhận nếu thuộc đúng gói thầu này
    if ($bg && (int)$bg->da_xoa === 0 && (int)$bg->goi_thau_id === (int)$goiThau->id) {
        $baoGia = $bg;
    } else {
        SessionHelper::remove('portal_bao_gia_id');
        $baoGiaId = 0;
    }
}

$AJAX = AppConfig::baseUrl('GUI/portal/ajax_handler.php');
$hanCuoiTxt = $goiThau->han_cuoi ? Helper::formatDate($goiThau->han_cuoi) : 'Không đặt hạn';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chào giá <?= Helper::h($goiThau->so_thong_bao) ?> · <?= Helper::h(AppConfig::APP_NAME) ?></title>
<link rel="stylesheet" href="<?= AppConfig::baseUrl('assets/css/style.css') ?>?v=<?= Helper::h(AppConfig::APP_VERSION) ?>">
<script src="<?= AppConfig::baseUrl('assets/js/jquery-3.7.1.min.js') ?>"></script>
<script>
var APP_BASE = "<?= AppConfig::baseUrl('') ?>";
var CSRF_TOKEN = "<?= Helper::h(SessionHelper::csrfToken()) ?>";
</script>
<script src="<?= AppConfig::baseUrl('assets/js/app.js') ?>?v=<?= Helper::h(AppConfig::APP_VERSION) ?>"></script>
</head>
<body class="portal-body">
<a href="#main" class="skip-link">Bỏ qua tới nội dung chính</a>

<header class="portal-header">
    <div class="portal-inner">
        <span class="portal-logo"><?= IconHelper::svg('file-spreadsheet', 24) ?></span>
        <div>
            <h1>Cổng chào giá — Thông báo số <?= Helper::h($goiThau->so_thong_bao) ?></h1>
            <div class="portal-sub"><?= Helper::h($goiThau->ten_goi_thau) ?></div>
        </div>
        <div class="portal-right">
            <span class="badge badge-neutral"><?= Helper::h(SessionHelper::taiKhoan()) ?></span>
            <a class="btn btn-sm btn-outline-secondary" href="<?= AppConfig::baseUrl('GUI/auth/logout.php') ?>">
                <?= IconHelper::svg('log-out', 15) ?><span class="btn-label">Thoát</span>
            </a>
        </div>
    </div>
</header>

<main class="portal-main" id="main">

<?php if (!$conNhan['ok']): ?>
    <!-- ============ NGOÀI THỜI GIAN CHÀO GIÁ → CHỈ TRA CỨU ============ -->
    <?php
        $ttBg = $conNhan['trang_thai_bao_gia'];
        $laChuaMo = $ttBg === BG_GoiThau_PUBLIC::BG_CHUA_MO;
        $iconTt = $laChuaMo ? 'clock' : ($ttBg === BG_GoiThau_PUBLIC::BG_HET_HAN ? 'x-circle' : 'lock');
    ?>
    <div class="context-bar <?= $laChuaMo ? '' : 'is-warning' ?>">
        <span class="ctx-item">
            <?= IconHelper::svg($iconTt, 16) ?>
            <span class="ctx-value"><?= Helper::h(BG_GoiThau_PUBLIC::tenTrangThaiBaoGia($ttBg)) ?></span>
        </span>
        <span class="ctx-item"><span class="ctx-label"><?= Helper::h($conNhan['message']) ?></span></span>
        <?php if (!empty($goiThau->thoi_gian_mo_bao_gia)): ?>
            <span class="ctx-item">
                <?= IconHelper::svg('calendar', 16) ?>
                <span class="ctx-label">Mở</span>
                <span class="ctx-value"><?= Helper::h(Helper::formatDateTime($goiThau->thoi_gian_mo_bao_gia)) ?></span>
            </span>
        <?php endif; ?>
        <?php if (!empty($goiThau->thoi_gian_dong_bao_gia)): ?>
            <span class="ctx-item">
                <?= IconHelper::svg('clock', 16) ?>
                <span class="ctx-label">Đóng</span>
                <span class="ctx-value"><?= Helper::h(Helper::formatDateTime($goiThau->thoi_gian_dong_bao_gia)) ?></span>
            </span>
        <?php endif; ?>
    </div>

    <div class="card lookup-box">
        <div style="padding:20px">
            <div style="text-align:center;margin-bottom:18px">
                <span style="display:inline-flex;color:var(--gray-400)"><?= IconHelper::svg('search', 34) ?></span>
                <h2 style="font-size:17px;margin:10px 0 6px">Tra cứu báo giá đã nộp</h2>
                <p style="font-size:13.5px;color:var(--gray-600);margin:0">
                    <?= $laChuaMo
                        ? 'Hiện chưa tới thời gian nhận báo giá. Quý công ty có thể tra cứu các báo giá đã nộp trước đó.'
                        : 'Đã hết thời gian nhận báo giá. Quý công ty vẫn có thể tra cứu và tải lại báo giá đã nộp.' ?>
                </p>
            </div>

            <form class="lookup-form" id="lookupForm" onsubmit="return traCuu()">
                <div class="form-group">
                    <label for="lk_mst">Mã số thuế công ty <span class="req">*</span></label>
                    <input type="text" id="lk_mst" class="form-control" required maxlength="14"
                           placeholder="VD: 0101234567" autocomplete="off">
                </div>
                <button type="submit" class="btn btn-primary">
                    <?= IconHelper::svg('search', 16) ?>Tra cứu
                </button>
            </form>
            <p class="form-hint" style="margin-top:10px">
                Chỉ hiển thị báo giá của đúng mã số thuế nhập vào.
            </p>
        </div>
    </div>

    <div id="lookupResult"></div>

<?php elseif ($baoGia && (int)$baoGia->trang_thai === BG_BaoGia_PUBLIC::TT_DA_XAC_NHAN): ?>
    <!-- Đã được xác nhận bản giấy → khóa, không cho sửa -->
    <div class="state-card is-success">
        <span class="state-icon"><?= IconHelper::svg('check-circle', 42) ?></span>
        <h2>Báo giá đã được xác nhận</h2>
        <p>
            Bên mời chào giá đã xác nhận nhận được bản giấy của
            <strong><?= Helper::h($baoGia->ten_cong_ty) ?></strong>
            lúc <?= Helper::h(Helper::formatDateTime($baoGia->ngay_xac_nhan)) ?>.<br>
            Báo giá đã được khóa và đưa vào bảng tổng hợp. Cảm ơn quý công ty.
        </p>
        <p style="font-size:13px;color:var(--gray-500)">
            Tổng giá trị: <strong><?= number_format((float)$baoGia->tong_tien, 0, ',', '.') ?> VND</strong>
        </p>
    </div>

<?php else: ?>

    <!-- Thanh thông tin gói thầu (như ảnh mẫu: hạn cuối + thời gian hợp đồng) -->
    <div class="context-bar">
        <span class="ctx-item">
            <?= IconHelper::svg('clock', 16) ?>
            <span class="ctx-label">Hạn cuối tiếp nhận báo giá</span>
            <span class="ctx-value"><?= Helper::h($hanCuoiTxt) ?></span>
        </span>
        <?php if ((int)$goiThau->thoi_gian_hop_dong > 0): ?>
            <span class="ctx-item">
                <?= IconHelper::svg('calendar', 16) ?>
                <span class="ctx-label">Thời gian thực hiện hợp đồng</span>
                <span class="ctx-value"><?= (int)$goiThau->thoi_gian_hop_dong ?> tháng</span>
            </span>
        <?php endif; ?>
        <span class="ctx-item">
            <?= IconHelper::svg('package', 16) ?>
            <span class="ctx-label">Danh mục</span>
            <span class="ctx-value"><?= (int)$goiThau->so_hang_hoa ?> hàng hóa</span>
        </span>
    </div>

    <!-- Tiến trình 3 bước -->
    <div class="steps" id="steps">
        <span class="step <?= $baoGia ? 'is-done' : 'is-active' ?>" id="step1">
            <span class="step-no">1</span> Khai thông tin công ty
        </span>
        <span class="step <?= $baoGia ? 'is-active' : '' ?>" id="step2">
            <span class="step-no">2</span> Điền giá hoặc import file
        </span>
        <span class="step" id="step3">
            <span class="step-no">3</span> Nộp báo giá
        </span>
    </div>

    <!-- ============ BƯỚC 1: THÔNG TIN CÔNG TY ============ -->
    <div class="card" style="margin-bottom:16px">
        <div class="card-header" style="display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--gray-200)">
            <?= IconHelper::svg('building', 19) ?>
            <h2 style="font-size:15px;margin:0">Thông tin công ty chào giá</h2>
            <?php if ($baoGia): ?>
                <span class="badge badge-success" style="margin-left:auto">Đã lưu</span>
            <?php endif; ?>
        </div>
        <form id="formCty" onsubmit="return luuThongTin()">
            <div class="modal-body" style="padding:18px">
                <input type="hidden" id="bao_gia_id" value="<?= (int)$baoGiaId ?>">

                <div class="form-group">
                    <label for="ten_cong_ty">Tên công ty <span class="req">*</span></label>
                    <input type="text" id="ten_cong_ty" name="ten_cong_ty" class="form-control" required
                           maxlength="500" placeholder="Tên công ty theo giấy đăng ký kinh doanh"
                           value="<?= Helper::h($baoGia->ten_cong_ty ?? '') ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="ma_so_thue">Mã số thuế <span class="req">*</span></label>
                        <input type="text" id="ma_so_thue" name="ma_so_thue" class="form-control" required
                               maxlength="14" placeholder="VD: 0101234567"
                               value="<?= Helper::h($baoGia->ma_so_thue ?? '') ?>">
                        <div class="form-hint">10 số, hoặc dạng 0101234567-001. Mỗi MST nộp 1 báo giá cho gói thầu.</div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email liên hệ</label>
                        <input type="email" id="email" name="email" class="form-control" maxlength="200"
                               placeholder="email@congty.vn" value="<?= Helper::h($baoGia->email ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="dien_thoai">Số điện thoại</label>
                        <input type="text" id="dien_thoai" name="dien_thoai" class="form-control" maxlength="50"
                               placeholder="Số điện thoại liên hệ" value="<?= Helper::h($baoGia->dien_thoai ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="dia_chi">Địa chỉ công ty</label>
                    <input type="text" id="dia_chi" name="dia_chi" class="form-control" maxlength="1000"
                           placeholder="Địa chỉ trên giấy đăng ký kinh doanh"
                           value="<?= Helper::h($baoGia->dia_chi ?? '') ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="hieu_luc_bao_gia">Hiệu lực báo giá (ngày) <span class="req">*</span></label>
                        <input type="number" id="hieu_luc_bao_gia" name="hieu_luc_bao_gia" class="form-control"
                               min="<?= (int)$goiThau->hieu_luc_bao_gia ?>" max="3650" required
                               value="<?= (int)($baoGia->hieu_luc_bao_gia ?? $goiThau->hieu_luc_bao_gia) ?>">
                        <div class="form-hint">
                            Tối thiểu <?= (int)$goiThau->hieu_luc_bao_gia ?> ngày
                            kể từ <?= Helper::h($hanCuoiTxt) ?>.
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="ghi_chu">Ghi chú</label>
                        <input type="text" id="ghi_chu" name="ghi_chu" class="form-control" maxlength="500"
                               placeholder="Thông tin bổ sung (nếu có)"
                               value="<?= Helper::h($baoGia->ghi_chu ?? '') ?>">
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="padding:14px 18px">
                <button type="submit" class="btn btn-primary">
                    <?= IconHelper::svg('save', 16) ?><?= $baoGia ? 'Cập nhật thông tin' : 'Lưu và tiếp tục' ?>
                </button>
            </div>
        </form>
    </div>

    <!-- ============ BƯỚC 2: FILE + BẢNG GIÁ ============ -->
    <div id="buocGia" <?= $baoGia ? '' : 'hidden' ?>>

        <div class="card" style="margin-bottom:16px">
            <div class="card-header" style="display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--gray-200)">
                <?= IconHelper::svg('file-spreadsheet', 19) ?>
                <h2 style="font-size:15px;margin:0">Thao tác và tải file</h2>
            </div>
            <div style="padding:18px">
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px">
                    <a class="btn btn-primary" id="btnTaiMau"
                       href="<?= AppConfig::baseUrl('GUI/portal/download.php') ?>?t=<?= urlencode($token) ?>">
                        <?= IconHelper::svg('download', 16) ?>Tải file mẫu báo giá
                    </a>
                    <button type="button" class="btn btn-success" onclick="openImport()">
                        <?= IconHelper::svg('upload', 16) ?>Upload file báo giá của công ty
                    </button>
                </div>
                <div class="alert alert-info">
                    <?= IconHelper::svg('info', 16) ?>
                    <span>
                        Tải file mẫu về, điền các cột từ <strong>Tên thương mại</strong> đến
                        <strong>Điểm không đạt</strong> rồi upload lên. Cột
                        <strong>Đơn giá</strong> ghi dạng <code>10000</code> — không dùng dấu phân cách
                        như <code>10.000,00</code>. Hoặc điền trực tiếp vào bảng bên dưới.
                    </span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header" style="display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--gray-200);flex-wrap:wrap">
                <?= IconHelper::svg('package', 19) ?>
                <h2 style="font-size:15px;margin:0">Danh mục hàng hóa</h2>
                <span class="badge badge-neutral" id="badgeTienDo">—</span>
                <span style="margin-left:auto">
                    <span class="search-box" style="max-width:260px">
                        <?= IconHelper::svg('search', 16) ?>
                        <input type="text" id="searchHang" class="form-control" placeholder="Tìm hàng hóa...">
                    </span>
                </span>
            </div>

            <div class="table-wrap has-sticky" id="bangWrap">
                <table class="table" id="bangGia">
                    <thead>
                        <tr>
                            <th class="col-id">STT</th>
                            <th class="sticky-col">Tên hàng hóa / Yêu cầu kỹ thuật</th>
                            <th>ĐVT</th>
                            <th class="col-qty">Số lượng</th>
                            <th>Tên thương mại</th>
                            <th>Model</th>
                            <th>Hãng SX</th>
                            <th>Xuất xứ</th>
                            <th>VAT (%)</th>
                            <th class="col-price">Đơn giá (VND)</th>
                            <th class="col-price">Thành tiền</th>
                            <th class="col-actions">Lưu</th>
                        </tr>
                    </thead>
                    <tbody id="bangBody"></tbody>
                </table>
            </div>

            <div class="total-bar">
                <span class="tb-label">Tổng giá trị báo giá</span>
                <span class="tb-value" id="tongTien">0</span>
                <span class="tb-label">VND</span>
                <span class="tb-spacer"></span>
                <span class="tb-note" id="tbNote"></span>
                <button type="button" class="btn btn-primary" id="btnNop" onclick="nopBaoGia()">
                    <?= IconHelper::svg('send', 16) ?>Nộp báo giá
                </button>
            </div>
        </div>
    </div>

    <!-- ============ Modal chi tiết 1 dòng ============ -->
    <div class="modal" id="dongModal">
        <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="dongTitle" style="max-width:820px">
            <div class="modal-header">
                <h3 id="dongTitle">Chi tiết chào giá</h3>
                <button type="button" class="close" onclick="closeDong()" aria-label="Đóng"><?= IconHelper::svg('x', 20) ?></button>
            </div>
            <form id="formDong" onsubmit="return luuDongChiTiet()">
                <div class="modal-body">
                    <input type="hidden" id="d_hang_hoa_id">
                    <div id="d_yeuCau"></div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="d_ten_thuong_mai">Tên thương mại</label>
                            <input type="text" id="d_ten_thuong_mai" class="form-control" maxlength="1000">
                        </div>
                        <div class="form-group">
                            <label for="d_model">Ký, mã, nhãn hiệu, model</label>
                            <input type="text" id="d_model" class="form-control" maxlength="500">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="d_ma_hs">Mã HS</label>
                            <input type="text" id="d_ma_hs" class="form-control" maxlength="200">
                        </div>
                        <div class="form-group">
                            <label for="d_hang_san_xuat">Hãng sản xuất</label>
                            <input type="text" id="d_hang_san_xuat" class="form-control" maxlength="500">
                        </div>
                        <div class="form-group">
                            <label for="d_xuat_xu">Xuất xứ</label>
                            <input type="text" id="d_xuat_xu" class="form-control" maxlength="500">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="d_quy_cach">Quy cách đóng gói</label>
                            <input type="text" id="d_quy_cach" class="form-control" maxlength="500" placeholder="VD: 1 bộ/hộp">
                        </div>
                        <div class="form-group">
                            <label for="d_chi_phi_dich_vu">Chi phí dịch vụ liên quan (VND)</label>
                            <input type="text" id="d_chi_phi_dich_vu" class="form-control" placeholder="VD: 10000">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="d_thue_vat">Thuế VAT (%)</label>
                            <input type="text" id="d_thue_vat" class="form-control" placeholder="VD: 5 hoặc 10">
                        </div>
                        <div class="form-group">
                            <label for="d_don_gia">Đơn giá đã gồm thuế, phí (VND) <span class="req">*</span></label>
                            <input type="text" id="d_don_gia" class="form-control" placeholder="VD: 10000">
                            <div class="form-hint">Ghi số thuần, không dùng dấu phân cách nghìn.</div>
                        </div>
                        <div class="form-group">
                            <label for="d_don_gia_trung_thau">Đơn giá trúng thầu gần nhất (VND)</label>
                            <input type="text" id="d_don_gia_trung_thau" class="form-control" placeholder="Nếu có">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="d_chung_nhan_chao">Chứng nhận hàng hóa chào</label>
                        <textarea id="d_chung_nhan_chao" class="form-control" rows="2"
                                  placeholder="VD: FDA (510(k)) / CE (MDR) / ISO13485"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="d_tai_lieu_tham_chieu">Tài liệu tham chiếu đơn giá trúng thầu</label>
                        <textarea id="d_tai_lieu_tham_chieu" class="form-control" rows="2"
                                  placeholder="Loại văn bản; số; ngày; cơ sở y tế ban hành"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="d_ma_qr_hang_hoa">Mã QR / Barcode định danh hàng hóa</label>
                        <select id="d_ma_qr_hang_hoa" class="form-select">
                            <option value="">-- Chọn --</option>
                            <option value="Có QR/Barcode trên từng sản phẩm">Có QR/Barcode trên từng sản phẩm</option>
                            <option value="Chỉ có QR/Barcode trên hộp chứa nhiều sản phẩm">Chỉ có QR/Barcode trên hộp chứa nhiều sản phẩm</option>
                            <option value="Không có QR/Barcode">Không có QR/Barcode</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="d_thong_so_chao_gia">Thông số kỹ thuật chào giá</label>
                        <textarea id="d_thong_so_chao_gia" class="form-control" rows="3"
                                  placeholder="Nêu thông số kỹ thuật của hàng hóa tương ứng yêu cầu"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="d_diem_khong_dat">Các điểm không đạt kèm thuyết minh</label>
                        <textarea id="d_diem_khong_dat" class="form-control" rows="2"
                                  placeholder="Nêu rõ thông số không đáp ứng (nếu có) kèm lý giải"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeDong()">Hủy</button>
                    <button type="submit" class="btn btn-primary"><?= IconHelper::svg('save', 16) ?>Lưu dòng này</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============ Modal import ============ -->
    <div class="modal" id="importModal">
        <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="impTitle" style="max-width:700px">
            <div class="modal-header">
                <h3 id="impTitle">Upload file báo giá</h3>
                <button type="button" class="close" onclick="closeImport()" aria-label="Đóng"><?= IconHelper::svg('x', 20) ?></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <?= IconHelper::svg('info', 16) ?>
                    <span>
                        Dùng đúng file đã tải từ nút <strong>"Tải file mẫu báo giá"</strong> — hệ thống khớp
                        từng dòng theo danh mục hàng hóa. Không xóa/chèn dòng, không sửa các cột A–K.
                    </span>
                </div>

                <label class="dropzone" id="dropzone" for="fileBg">
                    <span class="dz-icon"><?= IconHelper::svg('file-spreadsheet', 34) ?></span>
                    <span class="dz-main">Chọn file Excel đã điền giá</span>
                    <span class="dz-sub">Chỉ nhận .xlsx, tối đa <?= round(AppConfig::UPLOAD_MAX_SIZE / 1048576) ?>MB</span>
                    <input type="file" id="fileBg" accept=".xlsx" onchange="onFileChosen(this)">
                </label>

                <div id="fileInfo"></div>
                <div id="impResult"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeImport()">Hủy</button>
                <button type="button" class="btn btn-primary" id="btnDoImport" onclick="doImport()" disabled>
                    <?= IconHelper::svg('upload', 16) ?>Upload và nạp giá
                </button>
            </div>
        </div>
    </div>

<?php endif; ?>

</main>

<footer class="portal-footer">
    <?= Helper::h(AppConfig::APP_NAME) ?> · Mọi thắc mắc về gói thầu xin liên hệ bên mời chào giá.
</footer>

<div class="toast-container" id="toastContainer" aria-live="polite" aria-atomic="true"></div>

<script>
var AJAX_URL = <?= json_encode($AJAX) ?>;
var URL_DOWNLOAD = <?= json_encode(AppConfig::baseUrl('GUI/portal/download.php')) ?>;
var PORTAL_TOKEN = <?= json_encode($token) ?>;
var BAO_GIA_ID = <?= (int)$baoGiaId ?>;
var HIEU_LUC_MIN = <?= (int)$goiThau->hieu_luc_bao_gia ?>;
var TT_BG_XN = <?= (int)BG_BaoGia_PUBLIC::TT_DA_XAC_NHAN ?>;
var TT_BG_TC = <?= (int)BG_BaoGia_PUBLIC::TT_TU_CHOI ?>;
var DONG = [];      // dữ liệu bảng chào giá

function money(v) { return Number(v || 0).toLocaleString('vi-VN'); }

/* ============ TRA CỨU BÁO GIÁ (khi ngoài thời gian chào giá) ============ */
function traCuu() {
    var mst = ($('#lk_mst').val() || '').trim();
    if (!mst) { APP.toast('Nhập mã số thuế', 'warning'); return false; }

    APP.showLoading('#lookupResult');
    APP.ajax(AJAX_URL, { action: 'traCuuMst', ma_so_thue: mst }, {
        success: function (res) { renderTraCuu(res.data || []); },
        error: function (res) {
            $('#lookupResult').html(
                '<div class="card" style="padding:18px"><div class="alert alert-warning" style="margin:0">' +
                APP.icon('info', 16) + ' ' +
                APP.escape((res && res.message) || 'Không tìm thấy báo giá') + '</div></div>'
            );
        },
        complete: function () { APP.hideLoading('#lookupResult'); }
    });
    return false;
}

function renderTraCuu(list) {
    if (!list.length) {
        $('#lookupResult').html(
            '<div class="card" style="padding:18px"><div class="alert alert-warning" style="margin:0">' +
            APP.icon('info', 16) + ' Không tìm thấy báo giá nào của mã số thuế này.</div></div>'
        );
        return;
    }

    var html = '<div class="card" style="padding:18px">';
    html += '<h3 style="font-size:15px;margin:0 0 14px">Tìm thấy ' + list.length + ' báo giá</h3>';

    for (var i = 0; i < list.length; i++) {
        var b = list[i];
        var cls = b.trang_thai === TT_BG_XN ? 'badge-success'
                : (b.trang_thai === TT_BG_TC ? 'badge-danger' : 'badge-warning');

        html += '<div class="quote-card">' +
            '<div class="quote-card-head">' +
                '<span class="qc-title">' +
                    '<span class="qc-name">' + APP.escape(b.ten_cong_ty) + '</span>' +
                    '<span class="qc-mst">MST: ' + APP.escape(b.ma_so_thue || '—') +
                        ' · Mã báo giá #' + b.id + '</span>' +
                '</span>' +
                '<span class="qc-actions">' +
                    '<span class="badge ' + cls + '">' + APP.escape(b.ten_trang_thai) + '</span>' +
                    '<span class="quote-total">' + money(b.tong_tien) + ' đ</span>' +
                    '<a class="btn btn-sm btn-outline-primary" href="' + URL_DOWNLOAD +
                        '?t=' + encodeURIComponent(PORTAL_TOKEN) + '&loai=bao_gia&id=' + b.id + '">' +
                        APP.icon('download', 15) + '<span class="btn-label">Tải Excel</span></a>' +
                '</span>' +
            '</div>' +
            '<div class="detail-grid">' +
                dItemLk('Số dòng đã chào', b.so_dong_chao + ' dòng') +
                dItemLk('Ngày nộp', b.ngay_nop ? APP.formatDateTime(b.ngay_nop) : '') +
                dItemLk('Ngày xác nhận bản giấy', b.ngay_xac_nhan ? APP.formatDateTime(b.ngay_xac_nhan) : '') +
                dItemLk('Hiệu lực báo giá', b.hieu_luc_bao_gia ? b.hieu_luc_bao_gia + ' ngày' : '') +
                (b.ly_do_tu_choi ? dItemLk('Lý do từ chối', b.ly_do_tu_choi, 'span-2') : '') +
            '</div>' +
        '</div>';
    }
    html += '</div>';
    $('#lookupResult').html(html);
}

function dItemLk(label, value, cls) {
    var empty = (value === null || typeof value === 'undefined' || String(value).trim() === '');
    return '<div class="detail-item ' + (cls || '') + '">' +
        '<span class="detail-label">' + APP.escape(label) + '</span>' +
        '<span class="detail-value' + (empty ? ' is-empty' : '') + '">' +
        APP.escape(empty ? 'Chưa có' : value) + '</span></div>';
}

/** Đọc số người dùng nhập: chấp nhận 10.000 / 10,000 / 10000 */
function parseSo(s) {
    if (s === null || typeof s === 'undefined') return 0;
    s = String(s).trim();
    if (s === '') return 0;
    // Bỏ mọi ký tự không phải số / dấu phân cách
    s = s.replace(/[^\d,.\-]/g, '');
    var lastDot = s.lastIndexOf('.'), lastComma = s.lastIndexOf(',');
    if (lastDot >= 0 && lastComma >= 0) {
        if (lastComma > lastDot) { s = s.replace(/\./g, '').replace(',', '.'); }
        else { s = s.replace(/,/g, ''); }
    } else if (lastComma >= 0) {
        var after = s.length - lastComma - 1;
        s = (after === 3 && s.length > 4) ? s.replace(/,/g, '') : s.replace(',', '.');
    } else if (lastDot >= 0) {
        var a2 = s.length - lastDot - 1;
        if (a2 === 3 && s.length > 4) s = s.replace(/\./g, '');
    }
    var n = parseFloat(s);
    return isNaN(n) ? 0 : n;
}

/* ============ BƯỚC 1 ============ */
function luuThongTin() {
    var data = {
        action: BAO_GIA_ID ? 'capNhatThongTin' : 'khaiThongTin',
        bao_gia_id: BAO_GIA_ID,
        ten_cong_ty: $('#ten_cong_ty').val(),
        ma_so_thue: $('#ma_so_thue').val(),
        email: $('#email').val(),
        dien_thoai: $('#dien_thoai').val(),
        dia_chi: $('#dia_chi').val(),
        hieu_luc_bao_gia: $('#hieu_luc_bao_gia').val(),
        ghi_chu: $('#ghi_chu').val()
    };
    APP.ajax(AJAX_URL, data, {
        success: function (res) {
            APP.toast(res.message, 'success');
            if (res.data && res.data.id) {
                BAO_GIA_ID = parseInt(res.data.id, 10);
                $('#bao_gia_id').val(BAO_GIA_ID);
                $('#buocGia').prop('hidden', false);
                $('#step1').removeClass('is-active').addClass('is-done');
                $('#step2').addClass('is-active');
                loadBang();
                // Cuộn tới bảng để người dùng thấy việc cần làm tiếp
                $('html, body').animate({ scrollTop: $('#buocGia').offset().top - 20 }, 300);
            }
        }
    });
    return false;
}

/* ============ BƯỚC 2: BẢNG GIÁ ============ */
function loadBang() {
    if (!BAO_GIA_ID) return;
    APP.showLoading('#bangWrap');
    $('#bangBody').html(APP.skeletonRows(6, 12));

    APP.ajax(AJAX_URL, { action: 'getBangChaoGia', bao_gia_id: BAO_GIA_ID }, {
        success: function (res) {
            DONG = res.data.dong || [];
            renderBang();
            capNhatTong();
        },
        complete: function () { APP.hideLoading('#bangWrap'); }
    });
}

function renderBang() {
    var kw = ($('#searchHang').val() || '').toLowerCase();
    var html = '', hien = 0;

    for (var i = 0; i < DONG.length; i++) {
        var r = DONG[i];
        if (kw) {
            var hay = (r.ten_hang_hoa + ' ' + (r.thong_so_ky_thuat || '') + ' ' + (r.stt_theo_phan || '')).toLowerCase();
            if (hay.indexOf(kw) < 0) continue;
        }
        hien++;

        var coGia = Number(r.don_gia) > 0;
        html += '<tr data-hh="' + r.hang_hoa_id + '" data-i="' + i + '">' +
            '<td class="col-id">' + (i + 1) + '</td>' +
            '<td class="sticky-col">' +
                '<span class="cell-main">' + APP.escape(r.ten_hang_hoa) + '</span>' +
                (r.stt_theo_phan ? '<span class="cell-sub">' + APP.escape(r.stt_theo_phan) + '</span>' : '') +
                (r.thong_so_ky_thuat ? '<div class="spec-box">' + APP.escape(r.thong_so_ky_thuat) + '</div>' : '') +
                (r.chung_nhan ? '<span class="cell-sub">Chứng nhận YC: ' + APP.escape(r.chung_nhan) + '</span>' : '') +
            '</td>' +
            '<td>' + APP.escape(r.dvt || '—') + '</td>' +
            '<td class="col-qty">' + Number(r.so_luong || 0).toLocaleString('vi-VN') + '</td>' +
            '<td><input type="text" class="form-control input-inline f-ttm" value="' + APP.escape(r.ten_thuong_mai || '') + '" aria-label="Tên thương mại"></td>' +
            '<td><input type="text" class="form-control input-inline f-model" value="' + APP.escape(r.model || '') + '" aria-label="Model"></td>' +
            '<td><input type="text" class="form-control input-inline f-hsx" value="' + APP.escape(r.hang_san_xuat || '') + '" aria-label="Hãng sản xuất"></td>' +
            '<td><input type="text" class="form-control input-inline f-xx" value="' + APP.escape(r.xuat_xu || '') + '" aria-label="Xuất xứ"></td>' +
            '<td><input type="text" class="form-control input-inline text-right f-vat" style="min-width:64px" value="' + (Number(r.thue_vat) || '') + '" aria-label="VAT"></td>' +
            '<td class="col-price"><input type="text" class="form-control input-inline text-right f-gia" value="' + (coGia ? Number(r.don_gia) : '') + '" aria-label="Đơn giá"></td>' +
            '<td class="cell-total f-tt">' + (coGia ? money(r.thanh_tien) : '—') + '</td>' +
            '<td class="col-actions"><span class="row-actions">' +
                '<button type="button" class="btn btn-sm btn-outline-primary" onclick="luuDongNhanh(this)" title="Lưu dòng">' + APP.icon('save', 15) + '</button>' +
                '<button type="button" class="btn btn-sm btn-outline-secondary" onclick="openDong(' + i + ')" title="Nhập đầy đủ các cột">' + APP.icon('pencil', 15) + '</button>' +
            '</span></td>' +
            '</tr>';
    }

    if (!hien) {
        html = APP.emptyRow(12, kw ? 'Không tìm thấy hàng hóa khớp từ khóa' : 'Gói thầu chưa có hàng hóa');
    }
    $('#bangBody').html(html);
}

/** Tính lại tổng + tiến độ từ dữ liệu đang giữ */
function capNhatTong() {
    var tong = 0, soChao = 0;
    for (var i = 0; i < DONG.length; i++) {
        if (Number(DONG[i].don_gia) > 0) { soChao++; tong += Number(DONG[i].thanh_tien) || 0; }
    }
    $('#tongTien').text(money(tong));
    $('#badgeTienDo').text('Đã chào ' + soChao + '/' + DONG.length + ' hàng hóa');
    $('#badgeTienDo').attr('class', 'badge ' + (soChao === 0 ? 'badge-warning'
        : (soChao === DONG.length ? 'badge-success' : 'badge-info')));

    var note = '';
    if (soChao === 0) note = 'Cần chào giá ít nhất 1 hàng hóa trước khi nộp.';
    else if (soChao < DONG.length) note = 'Còn ' + (DONG.length - soChao) + ' hàng hóa chưa chào giá.';
    $('#tbNote').text(note);
    $('#btnNop').prop('disabled', soChao === 0);
    if (soChao > 0) $('#step3').addClass('is-active');
    else $('#step3').removeClass('is-active');
}

/** Lưu nhanh 1 dòng từ các input trên bảng */
function luuDongNhanh(btn) {
    var $tr = $(btn).closest('tr');
    var i = parseInt($tr.data('i'), 10);
    var r = DONG[i];
    if (!r) return;

    var gia = parseSo($tr.find('.f-gia').val());
    if (gia <= 0) {
        APP.toast('Nhập đơn giá lớn hơn 0 cho hàng hóa này', 'warning');
        $tr.find('.f-gia').trigger('focus');
        return;
    }

    var payload = {
        action: 'luuDong',
        bao_gia_id: BAO_GIA_ID,
        hang_hoa_id: r.hang_hoa_id,
        // Giữ các trường đã nhập ở modal, chỉ ghi đè phần trên bảng
        ten_thuong_mai: $tr.find('.f-ttm').val(),
        model: $tr.find('.f-model').val(),
        hang_san_xuat: $tr.find('.f-hsx').val(),
        xuat_xu: $tr.find('.f-xx').val(),
        thue_vat: $tr.find('.f-vat').val(),
        don_gia: gia,
        ma_hs: r.ma_hs || '',
        quy_cach: r.quy_cach || '',
        chi_phi_dich_vu: r.chi_phi_dich_vu || 0,
        chung_nhan_chao: r.chung_nhan_chao || '',
        don_gia_trung_thau: r.don_gia_trung_thau || 0,
        tai_lieu_tham_chieu: r.tai_lieu_tham_chieu || '',
        ma_qr_hang_hoa: r.ma_qr_hang_hoa || '',
        thong_so_chao_gia: r.thong_so_chao_gia || '',
        diem_khong_dat: r.diem_khong_dat || ''
    };

    APP.ajax(AJAX_URL, payload, {
        success: function (res) {
            // Cập nhật bộ đệm cục bộ, không load lại cả bảng (đỡ mất chỗ đang nhập)
            r.ten_thuong_mai = payload.ten_thuong_mai;
            r.model = payload.model;
            r.hang_san_xuat = payload.hang_san_xuat;
            r.xuat_xu = payload.xuat_xu;
            r.thue_vat = parseSo(payload.thue_vat);
            r.don_gia = gia;
            r.thanh_tien = (res.data && res.data.thanh_tien) ? Number(res.data.thanh_tien) : gia * Number(r.so_luong);
            $tr.find('.f-tt').text(money(r.thanh_tien));
            capNhatTong();
            APP.toast('Đã lưu dòng ' + (i + 1), 'success');
        }
    });
}

/* ============ Modal nhập đầy đủ 1 dòng ============ */
function openDong(i) {
    var r = DONG[i];
    if (!r) return;
    $('#dongTitle').text('Chào giá: ' + r.ten_hang_hoa);
    $('#d_hang_hoa_id').val(r.hang_hoa_id);

    var yc = '<div class="alert alert-info" style="margin-bottom:16px"><div>' +
        '<strong>Yêu cầu của bên mời:</strong><br>' +
        'Số lượng: <strong>' + Number(r.so_luong).toLocaleString('vi-VN') + ' ' + APP.escape(r.dvt || '') + '</strong>';
    if (r.chung_nhan) yc += '<br>Chứng nhận: ' + APP.escape(r.chung_nhan);
    if (r.yeu_cau_xuat_xu) yc += '<br>Xuất xứ: ' + APP.escape(r.yeu_cau_xuat_xu);
    if (r.thong_so_ky_thuat) {
        yc += '<div class="spec-box" style="margin-top:8px">' + APP.escape(r.thong_so_ky_thuat) + '</div>';
    }
    yc += '</div></div>';
    $('#d_yeuCau').html(yc);

    $('#d_ten_thuong_mai').val(r.ten_thuong_mai || '');
    $('#d_model').val(r.model || '');
    $('#d_ma_hs').val(r.ma_hs || '');
    $('#d_hang_san_xuat').val(r.hang_san_xuat || '');
    $('#d_xuat_xu').val(r.xuat_xu || '');
    $('#d_quy_cach').val(r.quy_cach || '');
    $('#d_chi_phi_dich_vu').val(Number(r.chi_phi_dich_vu) || '');
    $('#d_thue_vat').val(Number(r.thue_vat) || '');
    $('#d_don_gia').val(Number(r.don_gia) || '');
    $('#d_don_gia_trung_thau').val(Number(r.don_gia_trung_thau) || '');
    $('#d_chung_nhan_chao').val(r.chung_nhan_chao || '');
    $('#d_tai_lieu_tham_chieu').val(r.tai_lieu_tham_chieu || '');
    $('#d_ma_qr_hang_hoa').val(r.ma_qr_hang_hoa || '');
    $('#d_thong_so_chao_gia').val(r.thong_so_chao_gia || '');
    $('#d_diem_khong_dat').val(r.diem_khong_dat || '');

    $('#dongModal').addClass('open');
}

function luuDongChiTiet() {
    var gia = parseSo($('#d_don_gia').val());
    if (gia <= 0) {
        APP.toast('Nhập đơn giá lớn hơn 0', 'warning');
        $('#d_don_gia').trigger('focus');
        return false;
    }
    APP.ajax(AJAX_URL, {
        action: 'luuDong',
        bao_gia_id: BAO_GIA_ID,
        hang_hoa_id: $('#d_hang_hoa_id').val(),
        ten_thuong_mai: $('#d_ten_thuong_mai').val(),
        model: $('#d_model').val(),
        ma_hs: $('#d_ma_hs').val(),
        hang_san_xuat: $('#d_hang_san_xuat').val(),
        xuat_xu: $('#d_xuat_xu').val(),
        quy_cach: $('#d_quy_cach').val(),
        chi_phi_dich_vu: $('#d_chi_phi_dich_vu').val(),
        thue_vat: $('#d_thue_vat').val(),
        don_gia: gia,
        chung_nhan_chao: $('#d_chung_nhan_chao').val(),
        don_gia_trung_thau: $('#d_don_gia_trung_thau').val(),
        tai_lieu_tham_chieu: $('#d_tai_lieu_tham_chieu').val(),
        ma_qr_hang_hoa: $('#d_ma_qr_hang_hoa').val(),
        thong_so_chao_gia: $('#d_thong_so_chao_gia').val(),
        diem_khong_dat: $('#d_diem_khong_dat').val()
    }, {
        success: function (res) {
            APP.toast(res.message, 'success');
            closeDong();
            loadBang();
        }
    });
    return false;
}

/* ============ IMPORT ============ */
function openImport() {
    $('#fileBg').val('');
    $('#fileInfo').empty();
    $('#impResult').empty();
    $('#btnDoImport').prop('disabled', true);
    $('#importModal').addClass('open');
}

function onFileChosen(input) {
    var f = input.files && input.files[0];
    $('#impResult').empty();
    if (!f) { $('#fileInfo').empty(); $('#btnDoImport').prop('disabled', true); return; }
    var sz = f.size < 1048576 ? (f.size / 1024).toFixed(0) + ' KB' : (f.size / 1048576).toFixed(1) + ' MB';
    $('#fileInfo').html('<div class="file-chosen">' + APP.icon('file-spreadsheet', 17) +
        '<span class="fc-name">' + APP.escape(f.name) + '</span>' +
        '<span class="fc-size">' + sz + '</span></div>');
    $('#btnDoImport').prop('disabled', false);
}

function doImport() {
    var f = document.getElementById('fileBg').files[0];
    if (!f) { APP.toast('Chưa chọn file', 'warning'); return; }

    var fd = new FormData();
    fd.append('action', 'importFile');
    fd.append('bao_gia_id', BAO_GIA_ID);
    fd.append('file', f);

    APP.showLoading('#importModal .modal-body');
    $.ajax({
        url: AJAX_URL,
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        dataType: 'json',
        headers: { 'X-CSRF-Token': CSRF_TOKEN },
        success: function (res) {
            if (res && res.success) {
                APP.toast(res.message, 'success');
                var d = res.data || {};
                if (d.canh_bao && d.canh_bao.length) {
                    $('#impResult').html(renderCanhBao(d.canh_bao));
                    APP.toast('Có ' + d.canh_bao.length + ' cảnh báo — xem chi tiết trong hộp thoại', 'warning');
                } else {
                    closeImport();
                }
                loadBang();
            } else {
                APP.toast((res && res.message) || 'Có lỗi xảy ra', 'error');
                if (res && res.data && res.data.canh_bao && res.data.canh_bao.length) {
                    $('#impResult').html(renderCanhBao(res.data.canh_bao));
                }
            }
        },
        error: function (xhr) {
            var msg = 'Lỗi tải file';
            try {
                var r = JSON.parse(xhr.responseText);
                msg = r.message || msg;
                if (r.data && r.data.canh_bao && r.data.canh_bao.length) {
                    $('#impResult').html(renderCanhBao(r.data.canh_bao));
                }
            } catch (e) {}
            APP.toast(msg, 'error');
        },
        complete: function () { APP.hideLoading('#importModal .modal-body'); }
    });
}

function renderCanhBao(list) {
    var h = '<div class="import-warnings"><strong style="font-size:12.5px;color:#78350f">Cảnh báo (' +
            list.length + '):</strong><ul>';
    for (var i = 0; i < list.length; i++) h += '<li>' + APP.escape(list[i]) + '</li>';
    return h + '</ul></div>';
}

/* ============ NỘP ============ */
function nopBaoGia() {
    var soChao = 0;
    for (var i = 0; i < DONG.length; i++) if (Number(DONG[i].don_gia) > 0) soChao++;

    var msg = 'Nộp báo giá với ' + soChao + '/' + DONG.length + ' hàng hóa đã chào giá?';
    if (soChao < DONG.length) {
        msg += '\n\nCác hàng hóa chưa điền giá sẽ được ghi nhận là KHÔNG CHÀO.';
    }
    msg += '\n\nSau khi nộp, hãy gửi bản giấy tới bên mời chào giá để được xác nhận.';

    APP.confirm(msg, function () {
        APP.ajax(AJAX_URL, { action: 'nopBaoGia', bao_gia_id: BAO_GIA_ID }, {
            success: function (res) {
                APP.toast(res.message, 'success');
                $('#step3').removeClass('is-active').addClass('is-done');
                // Tải lại để hiện trạng thái đã nộp
                setTimeout(function () { window.location.reload(); }, 1200);
            }
        });
    }, { yesClass: 'btn-primary', yesText: 'Nộp báo giá' });
}

function closeDong() { $('#dongModal').removeClass('open'); }
function closeImport() { $('#importModal').removeClass('open'); }

/* Kéo thả file */
var $dz = $('#dropzone');
if ($dz.length) {
    $dz.on('dragover dragenter', function (e) { e.preventDefault(); e.stopPropagation(); $dz.addClass('is-dragover'); });
    $dz.on('dragleave dragend drop', function (e) { e.preventDefault(); e.stopPropagation(); $dz.removeClass('is-dragover'); });
    $dz.on('drop', function (e) {
        var files = e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files;
        if (files && files.length) {
            document.getElementById('fileBg').files = files;
            onFileChosen(document.getElementById('fileBg'));
        }
    });
}

$('#searchHang').on('keyup', APP.debounce(renderBang, 300));
$('#dongModal, #importModal').on('click', function (e) { if (e.target === this) $(this).removeClass('open'); });
$(document).on('keydown', function (e) { if (e.key === 'Escape') { closeDong(); closeImport(); } });

/* Enter trong ô đơn giá → lưu luôn dòng đó */
$(document).on('keydown', '.f-gia', function (e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        luuDongNhanh($(this).closest('tr').find('.btn-outline-primary')[0]);
    }
});

$(document).ready(function () { if (BAO_GIA_ID) loadBang(); });
</script>
</body>
</html>
