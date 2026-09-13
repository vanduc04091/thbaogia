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
// ?sua=<id> — quay lại báo giá ĐANG LÀM DỞ TRONG CHÍNH PHIÊN NÀY.
//
// CHỈ nhận khi id nằm trong danh sách báo giá của phiên. KHÔNG còn cho quay
// lại bằng MST đã tra cứu: tra cứu chỉ trả báo giá đã hoàn thành, và bản nháp
// làm dở thì đóng trình duyệt là mất — nhà thầu phải làm lại từ đầu (§10.2).
$suaId = (int)Helper::get('sua', 0);
if ($suaId > 0) {
    $idsPhien = SessionHelper::get('portal_bao_gia_ids', []);
    $duocSua = is_array($idsPhien) && in_array($suaId, $idsPhien, true);

    if ($duocSua) {
        $bgSua = BG_BaoGia_BUS::getById($suaId);
        if ($bgSua && (int)$bgSua->da_xoa === 0
            && (int)$bgSua->goi_thau_id === (int)$goiThau->id
            && (int)($bgSua->da_hoan_thanh ?? 0) === 0) {
            SessionHelper::set('portal_bao_gia_id', $suaId);

            // PHẢI thêm vào danh sách báo giá của phiên, nếu không mọi lời gọi
            // AJAX sau đó đều bị kiemTraBaoGiaThuocPhien() chặn 403 và trang
            // hiện ra trống trơn kèm "Bạn không có quyền thao tác trên báo giá này".
            $ds = SessionHelper::get('portal_bao_gia_ids', []);
            if (!is_array($ds)) $ds = [];
            if (!in_array($suaId, $ds, true)) {
                $ds[] = $suaId;
                SessionHelper::set('portal_bao_gia_ids', $ds);
            }
        }
    }
}

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
        <span class="portal-logo">
            <img src="<?= AppConfig::baseUrl('assets/images/logo_bv.png') ?>?v=<?= AppConfig::APP_VERSION ?>"
                 alt="Logo Bệnh viện Hữu nghị Đa khoa Nghệ An">
        </span>
        <div>
            <h1>Cổng chào giá — Thông báo số <?= Helper::h($goiThau->so_thong_bao) ?></h1>
            <div class="portal-sub"><?= Helper::h($goiThau->ten_goi_thau) ?></div>
        </div>
        <nav class="portal-nav">
            <?php if (AppConfig::PORTAL_CHO_TRA_CUU): ?>
            <button type="button" class="pnav-item" onclick="moTraCuu()">
                <?= IconHelper::svg('search', 16) ?><span>Tra cứu báo giá của tôi</span>
            </button>
            <?php endif; ?>
            <button type="button" class="pnav-item" onclick="moHuongDan()">
                <?= IconHelper::svg('info', 16) ?><span>Hướng dẫn</span>
            </button>
            <span class="pnav-user">
                <?= IconHelper::svg('user', 15) ?><?= Helper::h(SessionHelper::taiKhoan()) ?>
            </span>
            <a class="pnav-item pnav-out" href="<?= AppConfig::baseUrl('GUI/auth/logout.php') ?>">
                <?= IconHelper::svg('log-out', 16) ?><span>Thoát</span>
            </a>
        </nav>
    </div>
</header>

<main class="portal-main" id="main">

<!-- Hiện khi đang làm dở: nhắc KHÔNG được tắt trình duyệt giữa chừng.
     Báo giá chưa hoàn thành không tra cứu lại được (§10.2). -->
<div class="banner-lam-do" id="bannerLamDo" hidden>
    <?= IconHelper::svg('alert-triangle', 20) ?>
    <span>
        <strong>Đang khai báo giá — chưa hoàn thành.</strong>
        Vui lòng làm <strong>liên tục hết các bước</strong> rồi bấm
        <strong>“Hoàn thành báo giá”</strong> ở Bước 4.
        Nếu <strong>tắt trình duyệt giữa chừng</strong>, toàn bộ nội dung đã nhập
        sẽ mất và lần sau quý công ty <strong>phải khai lại từ đầu</strong>.
    </span>
</div>

<!-- Hiện khi nhà thầu đã chốt xong 5 bước — toàn bộ chuyển sang chỉ xem -->
<div class="banner-khoa" id="bannerKhoa" hidden>
    <?= IconHelper::svg('lock', 20) ?>
    <span>
        <strong>Báo giá đã hoàn thành.</strong>
        Bạn chỉ còn xem lại, không chỉnh sửa được nữa.
        Cần sửa hãy liên hệ bên mời chào giá.
    </span>
</div>

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

    <div class="state-card is-warning" style="margin-top:0">
        <span class="state-icon"><?= IconHelper::svg($iconTt, 42) ?></span>
        <h2>Chưa thể điền báo giá</h2>
        <p>
            <?= Helper::h($conNhan['message']) ?><br>
            <?php if (AppConfig::PORTAL_CHO_TRA_CUU): ?>
                Quý công ty vẫn có thể <strong>tra cứu báo giá đã nộp</strong> và
                <strong>tải bản có dấu, chữ ký</strong> ở mục bên dưới.
            <?php else: ?>
                Cần xem lại báo giá đã nộp, xin <strong>liên hệ bên mời chào giá</strong>
                theo thông tin trong Thư mời.
            <?php endif; ?>
        </p>
        <?php if (AppConfig::PORTAL_CHO_TRA_CUU): ?>
        <button type="button" class="btn btn-primary" onclick="moTraCuu()">
            <?= IconHelper::svg('search', 16) ?>Tra cứu báo giá đã nộp
        </button>
        <?php endif; ?>
    </div>

<?php elseif ($baoGia && (int)($baoGia->da_hoan_thanh ?? 0) === 1): ?>
    <!-- ĐÃ CHỐT HOÀN THÀNH → khóa, không cho sửa.
         KHÔNG khóa theo trang_thai = "Đã xác nhận": nhà thầu tự ký (upload bản
         ký) là đã thành "Đã xác nhận", nhưng còn trong thời gian chào giá thì
         vẫn phải được sửa. Hết hạn thì kiemTraConNhan() ở nhánh trên lo. -->
    <div class="state-card is-success">
        <span class="state-icon"><?= IconHelper::svg('check-circle', 42) ?></span>
        <h2>Báo giá đã hoàn thành</h2>
        <p>
            <strong><?= Helper::h($baoGia->ten_cong_ty) ?></strong>
            đã chốt hoàn thành báo giá
            lúc <?= Helper::h(Helper::formatDateTime($baoGia->ngay_hoan_thanh ?? $baoGia->ngay_xac_nhan)) ?>.<br>
            Báo giá đã được khóa và <strong>chuyển bên mời duyệt</strong>. Cảm ơn quý công ty.
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

    <!-- Tiến trình 4 bước — BẤM ĐƯỢC để quay lại sửa bước trước -->
    <div class="steps" id="steps">
        <button type="button" class="step step-btn <?= $baoGia ? 'is-done' : 'is-active' ?>"
                id="step1" onclick="veBuoc(1)">
            <span class="step-no">1</span> Thông tin công ty
        </button>
        <button type="button" class="step step-btn <?= $baoGia ? 'is-active' : '' ?>"
                id="step2" onclick="veBuoc(2)">
            <span class="step-no">2</span> Bảng đáp ứng kỹ thuật
            <span class="mt-dem" id="demM1">0</span>
        </button>
        <button type="button" class="step step-btn" id="step3" onclick="veBuoc(3)">
            <span class="step-no">3</span> Bảng chào giá
            <span class="mt-dem" id="demM2">0</span>
        </button>
        <button type="button" class="step step-btn" id="step4" onclick="veBuoc(4)">
            <span class="step-no">4</span> Bản báo giá đã ký
        </button>
    </div>

    <!-- ============ BƯỚC 1: THÔNG TIN CÔNG TY ============ -->
    <!-- Thanh tóm tắt: hiện THAY CHO form khi đã sang bước 2, để màn hình
         chỉ còn bảng điền giá. Bấm "Sửa thông tin" mở lại form. -->
    <div class="context-bar" id="ttTomTat" <?= $baoGia ? '' : 'hidden' ?>>
        <span class="ctx-item">
            <?= IconHelper::svg('building', 16) ?>
            <span class="ctx-label">Công ty</span>
            <span class="ctx-value" id="tt_ten"><?= Helper::h($baoGia->ten_cong_ty ?? '') ?></span>
        </span>
        <span class="ctx-item">
            <span class="ctx-label">MST</span>
            <span class="ctx-value" id="tt_mst"><?= Helper::h($baoGia->ma_so_thue ?? '') ?></span>
        </span>
        <span class="ctx-item">
            <span class="ctx-label">Hiệu lực</span>
            <span class="ctx-value" id="tt_hl"><?= (int)($baoGia->hieu_luc_bao_gia ?? 0) ?> ngày</span>
        </span>
        <span class="ctx-spacer"></span>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="suaThongTin()">
            <?= IconHelper::svg('pencil', 15) ?><span class="btn-label">Sửa thông tin</span>
        </button>
    </div>

    <div class="card" id="cardThongTin" style="margin-bottom:16px" <?= $baoGia ? 'hidden' : '' ?>>
        <div class="card-header" style="display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--gray-200)">
            <?= IconHelper::svg('building', 19) ?>
            <h2 style="font-size:15px;margin:0">Thông tin công ty chào giá</h2>
            <!-- Luôn render, JS ẩn/hiện: khi CHƯA lưu lần nào thì không cho đóng
                 (đóng sẽ không còn gì để nhập). -->
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnDongTT"
                    style="margin-left:auto" onclick="dongSuaThongTin()"
                    <?= $baoGia ? '' : 'hidden' ?>>
                <?= IconHelper::svg('x', 15) ?><span class="btn-label">Đóng</span>
            </button>
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
                <h2 style="font-size:15px;margin:0" id="tieuDeMau">Tải file mẫu và nộp lên</h2>
            </div>
            <div style="padding:18px">
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px">
                    <a class="btn btn-primary" id="btnTaiMau1"
                       href="<?= AppConfig::baseUrl('GUI/portal/download.php') ?>?t=<?= urlencode($token) ?>&mau=mau1">
                        <?= IconHelper::svg('download', 16) ?>Tải Mẫu 1 — Bảng đáp ứng
                    </a>
                    <a class="btn btn-primary" id="btnTaiMau2" hidden
                       href="<?= AppConfig::baseUrl('GUI/portal/download.php') ?>?t=<?= urlencode($token) ?>&mau=mau2">
                        <?= IconHelper::svg('download', 16) ?>Tải Mẫu 2 — Bảng chào giá
                    </a>
                    <button type="button" class="btn btn-success" id="btnUpMau1" onclick="openImport()">
                        <?= IconHelper::svg('upload', 16) ?>Upload Mẫu 1 đã điền
                    </button>
                    <button type="button" class="btn btn-success" id="btnUpMau2" onclick="openImport()" hidden>
                        <?= IconHelper::svg('upload', 16) ?>Upload Mẫu 2 đã điền
                    </button>
                </div>

                <div class="callout-cach">
                    <?= IconHelper::svg('info', 22) ?>
                    <span id="ghiChuMau">
                        <strong class="chon-cach">Làm theo 3 bước:</strong>
                        <span class="cach"><span class="cach-no">1</span>
                            <strong>Tải file mẫu</strong> về máy.</span>
                        <span class="cach"><span class="cach-no">2</span>
                            <strong>Điền vào các cột nền vàng</strong> trong file — giữ nguyên
                            cột Mã và tên sheet.</span>
                        <span class="cach"><span class="cach-no">3</span>
                            <strong>Upload file đã điền</strong> lên hệ thống.</span>
                        <span class="cach-chi-tiet" id="ghiChuNhom"></span>
                    </span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header" style="display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--gray-200);flex-wrap:wrap">
                <?= IconHelper::svg('package', 19) ?>
                <h2 style="font-size:15px;margin:0">Nội dung đã nộp</h2>
                <span class="badge badge-neutral" id="badgeTienDo">—</span>
                <span style="margin-left:auto">
                    <span class="search-box" style="max-width:260px">
                        <?= IconHelper::svg('search', 16) ?>
                        <input type="text" id="searchHang" class="form-control" placeholder="Tìm hàng hóa...">
                    </span>
                </span>
            </div>

            <!-- Bảng TÓM TẮT — chỉ để xem lại, không điền tay.
                 Bảng Mẫu 1 tới 21 cột nên điền trực tiếp trên trình duyệt rất khó;
                 nhà thầu điền trong file Excel rồi import lên (§10.2). -->
            <div id="khungTomTat" style="padding:0 0 4px">
                <div class="table-wrap has-sticky">
                    <table class="table" id="bangTomTat">
                        <thead id="tomTatHead"></thead>
                        <tbody id="tomTatBody"></tbody>
                    </table>
                </div>
                <p class="form-hint" style="padding:10px 16px 0" id="tomTatGhiChu"></p>
            </div>

            <div class="total-bar">
                <span class="tb-label">Tổng giá trị báo giá</span>
                <span class="tb-value" id="tongTien">0</span>
                <span class="tb-label">VND</span>
                <span class="tb-spacer"></span>
                <span class="tb-note" id="tbNote"></span>
                <button type="button" class="btn btn-primary" id="btnTiepTuc" onclick="veBuoc(3)">
                    Tiếp tục: Bảng chào giá <?= IconHelper::svg('chevron-right', 16) ?>
                </button>
                <button type="button" class="btn btn-primary" id="btnNop" onclick="nopBaoGia()" hidden>
                    <?= IconHelper::svg('send', 16) ?>Nộp báo giá
                </button>
            </div>
        </div>
    </div>


    <!-- ============ BƯỚC 4: BẢN BÁO GIÁ ĐÃ KÝ ============ -->
    <div id="buocBanKy" hidden>
        <div class="card">
            <div class="card-header" style="display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--gray-200)">
                <?= IconHelper::svg('file-text', 19) ?>
                <h2 style="font-size:15px;margin:0">Bước 4 — Bản báo giá có dấu và chữ ký</h2>
                <span class="badge badge-neutral" id="bkTrangThai">Chưa có file</span>
            </div>
            <div style="padding:18px">
                <div class="callout-cach" style="margin-bottom:16px">
                    <?= IconHelper::svg('info', 22) ?>
                    <span id="ghiChuMau">
                        <strong class="chon-cach">Làm theo 2 bước:</strong>
                        <span class="cach"><span class="cach-no">1</span>
                            <strong>Tải file Word</strong> báo giá về, in ra ký + đóng dấu.</span>
                        <span class="cach-hoac">rồi</span>
                        <span class="cach"><span class="cach-no">2</span>
                            <strong>Upload file đã ký</strong> (bản scan PDF hoặc ảnh).</span>
                        <span class="cach-chi-tiet">Upload xong nhớ bấm
                            <strong>Hoàn thành báo giá</strong> mới là nộp xong.</span>
                    </span>
                </div>

                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px">
                    <a class="btn btn-primary" id="btnTaiWord" href="#">
                        <?= IconHelper::svg('download', 16) ?>Tải file Word để ký
                    </a>
                    <button type="button" class="btn btn-success" onclick="moUpBanKy()">
                        <?= IconHelper::svg('upload', 16) ?>Upload file đã ký
                    </button>
                    <a class="btn btn-outline-secondary" id="btnXemBanKy" href="#" target="_blank" rel="noopener" hidden>
                        <?= IconHelper::svg('eye', 16) ?>Xem file đã tải lên
                    </a>
                </div>

                <div class="total-bar">
                    <span class="tb-note" id="bkNote">Chưa tải bản ký lên.</span>
                    <span class="tb-spacer"></span>
                    <button type="button" class="btn btn-primary" onclick="hoanThanhBaoGia()" id="btnHoanThanh">
                        <?= IconHelper::svg('check-circle', 16) ?>Hoàn thành báo giá
                    </button>
                </div>
            </div>
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

<!-- ============================================================
     NÚT NỔI + TRANG TRA CỨU BÁO GIÁ THEO MÃ SỐ THUẾ
     Hiện ở MỌI trạng thái của cổng. Bấm nút -> mở lớp phủ toàn trang
     liệt kê TẤT CẢ báo giá của MST đó (mọi gói thầu), nhóm theo gói.
     ============================================================ -->

<?php if (AppConfig::PORTAL_CHO_TRA_CUU): ?>
<div class="tracuu-overlay" id="traCuuOverlay" role="dialog" aria-modal="true"
     aria-labelledby="tcTieuDe" hidden>
    <!-- Dùng đúng header như các trang portal khác cho đồng nhất -->
    <header class="portal-header">
        <div class="portal-inner">
            <span class="portal-logo">
                <img src="<?= AppConfig::baseUrl('assets/images/logo_bv.png') ?>?v=<?= AppConfig::APP_VERSION ?>"
                     alt="Logo Bệnh viện Hữu nghị Đa khoa Nghệ An">
            </span>
            <div>
                <h1 id="tcTieuDe">Báo giá đã nộp của công ty</h1>
                <div class="portal-sub">Tra theo mã số thuế — hiển thị tất cả gói thầu</div>
            </div>
            <nav class="portal-nav">
                <button type="button" class="pnav-item" onclick="dongTraCuu()">
                    <?= IconHelper::svg('arrow-left', 16) ?><span>Quay lại chào giá</span>
                </button>
                <button type="button" class="pnav-item" onclick="moHuongDan()">
                    <?= IconHelper::svg('info', 16) ?><span>Hướng dẫn</span>
                </button>
                <span class="pnav-user">
                    <?= IconHelper::svg('user', 15) ?><?= Helper::h(SessionHelper::taiKhoan()) ?>
                </span>
                <a class="pnav-item pnav-out" href="<?= AppConfig::baseUrl('GUI/auth/logout.php') ?>">
                    <?= IconHelper::svg('log-out', 16) ?><span>Thoát</span>
                </a>
            </nav>
        </div>
    </header>

    <div class="tracuu-body">
        <form class="tracuu-search" id="lookupForm" onsubmit="return traCuu()">
            <div class="form-group">
                <label for="lk_mst">Mã số thuế công ty <span class="req">*</span></label>
                <input type="text" id="lk_mst" class="form-control" required maxlength="14"
                       placeholder="VD: 0101234567" autocomplete="off" inputmode="numeric">
                <div class="form-hint">Chỉ hiển thị báo giá của đúng mã số thuế nhập vào.</div>
            </div>
            <button type="submit" class="btn btn-primary">
                <?= IconHelper::svg('search', 16) ?>Tra cứu
            </button>
        </form>

        <div id="lookupResult"></div>
    </div>
</div>
<?php endif; ?>

<div class="modal" id="banKyModal">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="bkTitle" style="max-width:640px">
        <div class="modal-header">
            <h3 id="bkTitle">Upload file đã ký (có dấu và chữ ký)</h3>
            <button type="button" class="close" onclick="closeBanKy()" aria-label="Đóng"><?= IconHelper::svg('x', 20) ?></button>
        </div>
        <div class="modal-body">
            <div class="alert alert-info">
                <?= IconHelper::svg('info', 16) ?>
                <span>
                    Tải lên bản báo giá đã <strong>ký tên và đóng dấu</strong> (bản scan hoặc ảnh chụp rõ nét).
                    Tải xong vẫn <strong>chưa nộp xong</strong> — phải bấm
                    <strong>Hoàn thành báo giá</strong> ở cuối Bước 4.
                </span>
            </div>

            <div id="bkCongTy" class="detail-grid" style="margin-bottom:14px"></div>

            <label class="dropzone" id="bkDropzone" for="bkFile">
                <span class="dz-icon"><?= IconHelper::svg('upload', 34) ?></span>
                <span class="dz-main">Chọn file PDF hoặc ảnh đã ký đóng dấu</span>
                <span class="dz-sub">Nhận PDF, JPG, PNG — tối đa 20MB</span>
                <input type="file" id="bkFile" accept=".pdf,.jpg,.jpeg,.png" onchange="onBanKyChosen(this)">
            </label>

            <div id="bkFileInfo"></div>
            <div id="bkPreview"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeBanKy()">Hủy</button>
            <button type="button" class="btn btn-primary" id="bkBtnUpload" onclick="uploadBanKy()" disabled>
                <?= IconHelper::svg('upload', 16) ?>Tải lên và xác nhận
            </button>
        </div>
    </div>
</div>

<!-- ============================================================
     POPUP HƯỚNG DẪN — tự hiện lần đầu khi nhà thầu quét QR vào.
     Nội dung dùng chung với trang huong_dan.php (huong_dan_noi_dung.php).
     ============================================================ -->
<div class="modal" id="hdModal">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="hdTitle">
        <div class="modal-header">
            <h3 id="hdTitle">Hướng dẫn thực hiện chào giá qua hệ thống</h3>
            <button type="button" class="close" onclick="dongHuongDan()" aria-label="Đóng">
                <?= IconHelper::svg('x', 20) ?>
            </button>
        </div>
        <div class="modal-body">
            <?php require __DIR__ . '/huong_dan_noi_dung.php'; ?>
        </div>
        <div class="modal-footer">
            <a class="btn btn-outline-secondary" target="_blank" rel="noopener"
               href="<?= AppConfig::baseUrl('GUI/portal/huong_dan.php') ?>?t=<?= urlencode($token) ?>">
                <?= IconHelper::svg('external-link', 16) ?>Mở trang hướng dẫn
            </a>
            <button type="button" class="btn btn-primary" onclick="dongHuongDan()">
                <?= IconHelper::svg('check', 16) ?>Tôi đã hiểu
            </button>
        </div>
    </div>
</div>

</main>

<footer class="portal-footer">
    <?= Helper::h(AppConfig::APP_NAME) ?> · Mọi thắc mắc về gói thầu xin liên hệ bên mời chào giá.
</footer>

<div class="toast-container" id="toastContainer" aria-live="polite" aria-atomic="true"></div>

<script>
var AJAX_URL = <?= json_encode($AJAX) ?>;
var URL_DOWNLOAD = <?= json_encode(AppConfig::baseUrl('GUI/portal/download.php')) ?>;
var PORTAL_TOKEN = <?= json_encode($token) ?>;
/* Có cho nhà thầu tra cứu báo giá cũ không — xem AppConfig::PORTAL_CHO_TRA_CUU */
var CHO_TRA_CUU = <?= AppConfig::PORTAL_CHO_TRA_CUU ? 'true' : 'false' ?>;
var BAO_GIA_ID = <?= (int)$baoGiaId ?>;
var HIEU_LUC_MIN = <?= (int)$goiThau->hieu_luc_bao_gia ?>;
var TT_BG_XN = <?= (int)BG_BaoGia_PUBLIC::TT_DA_XAC_NHAN ?>;
var TT_BG_TC = <?= (int)BG_BaoGia_PUBLIC::TT_TU_CHOI ?>;

function money(v) { return Number(v || 0).toLocaleString('vi-VN'); }

/* ============ HƯỚNG DẪN CHÀO GIÁ ============ */

/** Khóa ghi nhớ đã xem hướng dẫn — theo từng gói thầu */
var HD_KEY = 'thbg_hd_' + PORTAL_TOKEN;

function moHuongDan() {
    $('#hdModal').addClass('open');
}

function dongHuongDan() {
    $('#hdModal').removeClass('open');
    // Ghi nhớ đã xem để lần sau không tự bật lại nữa.
    // try/catch: chế độ ẩn danh của một số trình duyệt chặn localStorage.
    try { localStorage.setItem(HD_KEY, '1'); } catch (e) {}
}

/** Lần đầu vào gói thầu này thì tự bật popup hướng dẫn */
function tuHienHuongDan() {
    var daXem = false;
    try { daXem = localStorage.getItem(HD_KEY) === '1'; } catch (e) {}
    if (!daXem) setTimeout(moHuongDan, 400);
}

/* ============ TRA CỨU BÁO GIÁ ĐÃ NỘP (nút nổi + lớp phủ toàn trang) ============ */

/**
 * Mở trang tra cứu.
 * @param {string} mstGoiY  MST điền sẵn (dùng khi vừa nộp báo giá xong)
 * @param {boolean} tuTra   true = tra cứu luôn, không đợi bấm nút
 */
function moTraCuu(mstGoiY, tuTra) {
    // Tắt tra cứu (AppConfig::PORTAL_CHO_TRA_CUU = false) -> không mở gì cả.
    // Chặn ở ĐÂY thay vì xóa từng chỗ gọi: còn chỗ nào gọi sót (nộp xong tự
    // mở, phím tắt...) cũng vô hiệu theo, không hở.
    if (!CHO_TRA_CUU) return;

    var $ov = $('#traCuuOverlay');
    $ov.prop('hidden', false).addClass('open');
    // Khóa cuộn trang nền để không cuộn 2 lớp cùng lúc
    $('body').css('overflow', 'hidden');

    if (mstGoiY) $('#lk_mst').val(mstGoiY);
    if (tuTra && $('#lk_mst').val()) traCuu();
    else $('#lk_mst').trigger('focus');
}

function dongTraCuu() {
    $('#traCuuOverlay').removeClass('open').prop('hidden', true);
    $('body').css('overflow', '');
}

function traCuu() {
    if (!CHO_TRA_CUU) return false;   // tắt tra cứu -> không gọi server

    var mst = ($('#lk_mst').val() || '').trim();
    if (!mst) { APP.toast('Nhập mã số thuế', 'warning'); return false; }

    APP.showLoading('#lookupResult');
    APP.ajax(AJAX_URL, { action: 'traCuuMst', ma_so_thue: mst }, {
        success: function (res) { renderTraCuu(res.data || null); },
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

function renderTraCuu(d) {
    if (!d || !d.nhom || !d.nhom.length) {
        $('#lookupResult').html(
            '<div class="card" style="padding:18px"><div class="alert alert-warning" style="margin:0">' +
            APP.icon('info', 16) + ' Không tìm thấy báo giá nào của mã số thuế này.</div></div>'
        );
        return;
    }

    var tk = d.tong_ket || {};
    var html = '';

    // --- Thẻ tóm tắt công ty ---
    html += '<div class="company-card">' +
        '<span class="cc-name">' + APP.escape(tk.ten_cong_ty || '') +
            '<span>MST: ' + APP.escape(d.ma_so_thue || '') + '</span></span>' +
        '<span class="cc-stat"><b>' + (tk.so_bao_gia || 0) + '</b><span>Báo giá</span></span>' +
        '<span class="cc-stat"><b>' + (tk.so_goi_thau || 0) + '</b><span>Gói thầu</span></span>' +
        '<span class="cc-stat"><b>' + (tk.da_xac_nhan || 0) + '</b><span>Đã xác nhận</span></span>' +
        '<span class="cc-stat"><b>' + (tk.cho_xac_nhan || 0) + '</b><span>Chờ xác nhận</span></span>' +
        '</div>';

    // --- Từng gói thầu ---
    for (var i = 0; i < d.nhom.length; i++) {
        var g = d.nhom[i];
        var clsGoi = g.trang_thai_bao_gia === 'dang_mo' ? 'badge-success'
                   : (g.trang_thai_bao_gia === 'chua_mo' ? 'badge-info' : 'badge-neutral');

        html += '<div class="goi-group">' +
            '<div class="goi-group-head">' +
                APP.icon('clipboard-list', 17) +
                '<span class="gg-so">' + APP.escape(g.so_thong_bao) + '</span>' +
                '<span class="gg-ten">' + APP.escape(g.ten_goi_thau) + '</span>' +
                '<span class="badge ' + clsGoi + '">' + APP.escape(g.ten_trang_thai_bao_gia) + '</span>' +
            '</div>' +
            '<div class="goi-group-body">';

        for (var j = 0; j < g.bao_gia.length; j++) {
            html += theBaoGia(g.bao_gia[j], g);
        }

        html += '</div></div>';
    }

    $('#lookupResult').html(html);
}

/** 1 thẻ báo giá trong kết quả tra cứu */
function theBaoGia(b, g) {
    var cls = b.trang_thai === TT_BG_XN ? 'badge-success'
            : (b.trang_thai === TT_BG_TC ? 'badge-danger' : 'badge-warning');

    return '<div class="quote-card">' +
        '<div class="quote-card-head">' +
            '<span class="qc-title">' +
                '<span class="qc-name">Báo giá #' + b.id + '</span>' +
                '<span class="qc-mst">' +
                    (b.ngay_nop ? 'Nộp lúc ' + APP.escape(APP.formatDateTime(b.ngay_nop)) : 'Chưa nộp') +
                    ' · ' + b.so_dong_chao + ' dòng đã chào' +
                '</span>' +
            '</span>' +
            '<span class="qc-actions">' +
                '<span class="badge ' + cls + '">' + APP.escape(b.ten_trang_thai) + '</span>' +
                (Number(b.da_hoan_thanh) === 1
                    ? '<span class="badge badge-neutral">' + APP.icon('lock', 13) +
                      ' Đã hoàn thành</span>'
                    : '') +
                // Tra cuu CHI tra bao gia da hoan thanh -> khong con nut sua lai.
                // Bao gia da nop la chot, muon sua phai lien he ben moi (§10.2).
                '<span class="quote-total">' + money(b.tong_tien) + ' đ</span>' +
                // Bản Word BÁO GIÁ để in ra ký + đóng dấu — chỉ có nghĩa khi đã nộp
                (b.ngay_nop
                    ? '<a class="btn btn-sm btn-primary" href="' + URL_DOWNLOAD +
                      '?t=' + encodeURIComponent(PORTAL_TOKEN) + '&loai=word_ban_ky&id=' + b.id + '">' +
                      APP.icon('file-text', 15) +
                      '<span class="btn-label">Tải Word để ký</span></a>'
                    : '') +
                nutBanKy(b) +
            '</span>' +
        '</div>' +
        '<div class="detail-grid">' +
            dItemLk('Ngày xác nhận', b.ngay_xac_nhan ? APP.formatDateTime(b.ngay_xac_nhan) : '') +
            dItemLk('Hiệu lực báo giá', b.hieu_luc_bao_gia ? b.hieu_luc_bao_gia + ' ngày' : '') +
            dItemLk('Bản có dấu & chữ ký', b.ten_file_goc
                ? b.ten_file_goc + (b.ngay_upload_ban_ky ? ' (' + APP.formatDateTime(b.ngay_upload_ban_ky) + ')' : '')
                : '', 'span-2') +
            (b.ly_do_tu_choi ? dItemLk('Lý do từ chối', b.ly_do_tu_choi, 'span-2') : '') +
        '</div>' +
    '</div>';
}

/* ===================== BƯỚC 4: BẢN BÁO GIÁ ĐÃ KÝ ===================== */

/** Nạp trạng thái bước 4 (không cần sang trang tra cứu MST nữa) */
function napBuocBanKy() {
    if (!BAO_GIA_ID) return;

    // Link tải Word luôn theo báo giá đang làm
    $('#btnTaiWord').attr('href',
        URL_DOWNLOAD + '?t=' + encodeURIComponent(PORTAL_TOKEN)
        + '&loai=word_ban_ky&id=' + BAO_GIA_ID);

    // Lấy trạng thái file bản ký của CHÍNH báo giá này — không gọi traCuuMst
    // (traCuuMst còn ghi session MST đã tra cứu, không nên gây tác dụng phụ ở đây)
    APP.ajax(AJAX_URL, { action: 'trangThaiBanKy', bao_gia_id: BAO_GIA_ID }, {
        success: function (res) {
            if (!res || !res.success) return;
            if (res.data && Number(res.data.da_hoan_thanh) === 1) apDungKhoa(true);
            var f = res.data && res.data.file_ban_ky;
            var co = !!(f && f.ten_file_goc);
            $('#bkTrangThai')
                .text(co ? 'Đã có file' : 'Chưa có file')
                .attr('class', 'badge ' + (co ? 'badge-success' : 'badge-warning'));
            $('#bkNote').text(co
                ? 'Đã tải lên: ' + f.ten_file_goc
                : 'Chưa tải bản ký lên. Tải file Word về, in ra ký + đóng dấu rồi upload.');
            $('#btnXemBanKy').prop('hidden', !co).attr('href',
                URL_DOWNLOAD + '?t=' + encodeURIComponent(PORTAL_TOKEN)
                + '&loai=ban_ky&id=' + BAO_GIA_ID);
        }
    });
}

/** Mở hộp thoại upload bản ký cho chính báo giá đang làm */
function moUpBanKy() {
    openBanKy(BAO_GIA_ID, $('#tt_ten').text() || '', $('#tt_mst').text() || '', true);
}


/* ===================== KHÓA SAU KHI HOÀN THÀNH ===================== */
var DA_HOAN_THANH = false;

/**
 * Nhà thầu chốt xong toàn bộ 5 bước.
 * Hỏi kỹ trước vì sau khi chốt là KHÔNG sửa lại được.
 */
function hoanThanhBaoGia() {
    if (!BAO_GIA_ID) return;

    APP.confirm(
        'Bạn có chắc chắn đã hoàn thành toàn bộ các bước chào giá?\n\n'
        + 'Sau khi xác nhận, báo giá sẽ bị KHÓA: bạn chỉ còn xem lại, '
        + 'KHÔNG chỉnh sửa được nữa. Cần sửa phải liên hệ bên mời chào giá.',
        function () {
            APP.showLoading('#buocBanKy');
            APP.ajax(AJAX_URL, { action: 'hoanThanh', bao_gia_id: BAO_GIA_ID }, {
                success: function (res) {
                    APP.hideLoading('#buocBanKy');
                    if (res && res.success) {
                        APP.toast(res.message, 'success');
                        apDungKhoa(true);
                        $('html, body').animate({ scrollTop: 0 }, 250);
                    } else {
                        APP.toast((res && res.message) || 'Không hoàn thành được', 'error');
                    }
                },
                error: function () {
                    APP.hideLoading('#buocBanKy');
                    APP.toast('Không hoàn thành được, hãy thử lại', 'error');
                }
            });
        },
        { title: 'Xác nhận hoàn thành', yesText: 'Tôi đã hoàn thành', noText: 'Chưa, để tôi xem lại' }
    );
}

/**
 * Bật/tắt chế độ CHỈ XEM.
 * Khóa mọi ô nhập + nút ghi; giữ lại nút tải file để nhà thầu còn xem lại được.
 */
function apDungKhoa(khoa) {
    DA_HOAN_THANH = !!khoa;
    if (!DA_HOAN_THANH) return;


    $('#bannerKhoa').prop('hidden', false);
    $('#bannerLamDo').prop('hidden', true);   // hết "làm dở" -> bỏ cảnh báo

    // Khóa toàn bộ ô nhập trong các bảng + form thông tin
    $('#buocGia input, #buocGia textarea, #buocGia select').prop('disabled', true);

    // Ẩn các nút ghi dữ liệu
    $('#btnTiepTuc, #btnNop, #btnHoanThanh').prop('hidden', true);
    // Ẩn mọi nút upload (kể cả nút Excel chỉ dẫn — đều dùng .btn-success)
    $('#buocGia .btn-success').prop('hidden', true);
    $('#buocBanKy .btn-success').prop('hidden', true);
    $('[onclick="suaThongTin()"]').prop('hidden', true);

    // Bảng chuyển sang nền xám nhạt cho dễ nhận biết
    $('#bangTomTat').addClass('is-locked');
}

/* ============ TẢI BẢN KÝ (PDF/ảnh có dấu + chữ ký) ============ */
var bkBaoGiaId = 0;
var BK_TU_BUOC4 = false;   // upload bản ký mở từ Bước 4?

/**
 * Nút trong thẻ kết quả: đã có bản ký thì cho xem lại, chưa có thì cho tải lên.
 *
 * KHÔNG nhét dữ liệu vào onclick="..." — tên công ty có dấu nháy kép sẽ cắt đứt
 * thuộc tính HTML, làm nút bấm không chạy (đã gặp lỗi này). Dùng data-* + APP.escape
 * rồi bắt sự kiện bằng event delegation ở dưới.
 */
function nutBanKy(b) {
    var h = '';
    if (b.ten_file_goc) {
        h += '<a class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener" href="' +
             URL_DOWNLOAD + '?t=' + encodeURIComponent(PORTAL_TOKEN) + '&loai=ban_ky&id=' + b.id + '">' +
             APP.icon('eye', 15) + '<span class="btn-label">Xem bản ký</span></a>';
    }
    // Chưa nộp online thì chưa cho tải bản ký (server cũng chặn).
    // Đã chốt hoàn thành thì KHÔNG cho tải đè nữa — chỉ còn xem.
    if (b.ngay_nop && Number(b.da_hoan_thanh) !== 1) {
        h += '<button type="button" class="btn btn-sm js-ban-ky ' +
             (b.ten_file_goc ? 'btn-outline-secondary' : 'btn-primary') + '"' +
             ' data-id="' + b.id + '"' +
             ' data-cty="' + APP.escape(b.ten_cong_ty || '') + '"' +
             ' data-mst="' + APP.escape(b.ma_so_thue || '') + '">' +
             APP.icon('upload', 15) + '<span class="btn-label">' +
             (b.ten_file_goc ? 'Upload lại file đã ký' : 'Upload file đã ký') + '</span></button>';
    }
    return h;
}

// Bắt sự kiện cho nút tải bản ký (nội dung render động nên phải delegate)
$(document).on('click', '.js-ban-ky', function () {
    var $b = $(this);
    openBanKy(parseInt($b.data('id'), 10), String($b.data('cty') || ''), String($b.data('mst') || ''));
});

/**
 * @param {boolean} tuBuoc4 true = mở từ Bước 4 trong luồng chào giá,
 *                          false/undefined = mở từ trang tra cứu MST.
 *                          Quyết định sau khi upload xong sẽ đi đâu.
 */
function openBanKy(id, tenCty, mst, tuBuoc4) {
    bkBaoGiaId = id;
    BK_TU_BUOC4 = !!tuBuoc4;
    $('#bkFile').val('');
    $('#bkFileInfo').empty();
    $('#bkPreview').empty();
    $('#bkBtnUpload').prop('disabled', true);
    $('#bkCongTy').html(
        dItemLk('Công ty', tenCty) + dItemLk('Mã số thuế', mst) + dItemLk('Mã báo giá', '#' + id)
    );
    $('#banKyModal').addClass('open');
}

function closeBanKy() { $('#banKyModal').removeClass('open'); }

function onBanKyChosen(input) {
    var f = input.files && input.files[0];
    $('#bkPreview').empty();
    if (!f) { $('#bkFileInfo').empty(); $('#bkBtnUpload').prop('disabled', true); return; }

    var sz = f.size < 1048576 ? (f.size / 1024).toFixed(0) + ' KB' : (f.size / 1048576).toFixed(1) + ' MB';
    if (f.size > 20 * 1048576) {
        $('#bkFileInfo').html('<div class="alert alert-warning" style="margin-top:12px">' +
            APP.icon('alert-triangle', 16) + ' File ' + sz + ' vượt quá 20MB.</div>');
        $('#bkBtnUpload').prop('disabled', true);
        return;
    }

    $('#bkFileInfo').html('<div class="file-chosen">' + APP.icon('file-spreadsheet', 17) +
        '<span class="fc-name">' + APP.escape(f.name) + '</span>' +
        '<span class="fc-size">' + sz + '</span></div>');

    // Xem trước nếu là ảnh, giúp nhà thầu biết đã chọn đúng file chưa
    if (/^image\//.test(f.type)) {
        var url = URL.createObjectURL(f);
        $('#bkPreview').html('<div style="margin-top:12px;text-align:center">' +
            '<img src="' + url + '" alt="Xem trước bản ký" ' +
            'style="max-width:100%;max-height:260px;border:1px solid var(--gray-200);border-radius:var(--radius-sm)" ' +
            'onload="URL.revokeObjectURL(this.src)"></div>');
    }
    $('#bkBtnUpload').prop('disabled', false);
}

function uploadBanKy() {
    var f = document.getElementById('bkFile').files[0];
    if (!f || !bkBaoGiaId) { APP.toast('Chưa chọn file', 'warning'); return; }

    APP.confirm('Tải lên bản ký này?',
    function () {
        var fd = new FormData();
        fd.append('action', 'uploadBanKy');
        fd.append('bao_gia_id', bkBaoGiaId);
        fd.append('file', f);

        APP.showLoading('#banKyModal .modal-body');
        $.ajax({
            url: AJAX_URL, type: 'POST', data: fd,
            processData: false, contentType: false, dataType: 'json',
            headers: { 'X-CSRF-Token': CSRF_TOKEN },
            success: function (res) {
                if (res && res.success) {
                    APP.toast(res.message, 'success');
                    closeBanKy();
                    if (BK_TU_BUOC4) {
                        // Bước 4 giờ là bước CUỐI: tải bản ký xong thì nạp lại
                        // để hiện nút "Hoàn thành báo giá".
                        napBuocBanKy();
                    } else {
                        traCuu();   // mở từ trang tra cứu -> tải lại kết quả
                    }
                } else {
                    APP.toast((res && res.message) || 'Có lỗi xảy ra', 'error');
                }
            },
            error: function (xhr) {
                var msg = 'Lỗi tải file';
                try { msg = JSON.parse(xhr.responseText).message || msg; } catch (e) {}
                APP.toast(msg, 'error');
            },
            complete: function () { APP.hideLoading('#banKyModal .modal-body'); }
        });
    }, { yesClass: 'btn-primary', yesText: 'Tải lên và xác nhận' });
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
            capNhatTomTat();

            if (res.data && res.data.id) {
                // Lần đầu lưu -> chuyển hẳn sang bước 2
                BAO_GIA_ID = parseInt(res.data.id, 10);
                $('#bao_gia_id').val(BAO_GIA_ID);
                $('#buocGia').prop('hidden', false);
                // Từ giờ đã có bản nháp -> nhắc ngay: tắt trình duyệt là mất
                if (!DA_HOAN_THANH) $('#bannerLamDo').prop('hidden', false);
                $('#step1').removeClass('is-active').addClass('is-done');
                $('#step2').addClass('is-active');
                loadBang();
            }
            // Ẩn form, chỉ để lại thanh tóm tắt + bảng điền giá
            $('#btnDongTT').prop('hidden', false);
            dongSuaThongTin();
            $('html, body').animate({ scrollTop: $('#ttTomTat').offset().top - 16 }, 300);
        }
    });
    return false;
}

/** Mở lại form sửa thông tin công ty */
function suaThongTin() {
    $('#cardThongTin').prop('hidden', false);
    $('#btnDongTT').prop('hidden', !BAO_GIA_ID);
    $('#ttTomTat').prop('hidden', true);
    $('html, body').animate({ scrollTop: $('#cardThongTin').offset().top - 16 }, 250);
    $('#ten_cong_ty').trigger('focus');
}

/** Đóng form, quay lại thanh tóm tắt gọn */
function dongSuaThongTin() {
    if (!BAO_GIA_ID) return;   // chưa lưu lần nào thì phải giữ form
    $('#cardThongTin').prop('hidden', true);
    $('#ttTomTat').prop('hidden', false);
}

/** Đồng bộ thanh tóm tắt với dữ liệu đang nhập trong form */
function capNhatTomTat() {
    $('#tt_ten').text($('#ten_cong_ty').val() || '');
    $('#tt_mst').text($('#ma_so_thue').val() || '');
    $('#tt_hl').text(($('#hieu_luc_bao_gia').val() || '0') + ' ngày');
}

/* ============ BƯỚC 2 & 3: TÓM TẮT NỘI DUNG ĐÃ NỘP ============
   Nhà thầu KHÔNG điền tay trên web nữa: tải file mẫu về điền rồi import lên,
   màn hình chỉ hiện tóm tắt để đối chiếu (§10.2). */
var TAB_HIEN = 1;      // 1 = Mẫu 1 (đáp ứng), 2 = Mẫu 2 (chào giá)
var DU_LIEU = null;    // kết quả getBangChaoGia gần nhất
/* Nhóm mua theo BỘ (bộ dụng cụ / hệ thống TBYT): giá chào nằm ở DÒNG BỘ,
   hàng hóa chi tiết bên dưới không cần điền đơn giá / thành tiền.
   Gán lại mỗi lần nạp dữ liệu — xem loadBang(). */
var GIA_THEO_BO = false;

/** Chuyển giữa Mẫu 1 (bước 2) và Mẫu 2 (bước 3) */
function chuyenTab(n) {
    TAB_HIEN = n;

    // Đổi nút tải / upload theo mẫu đang xem
    $('#btnTaiMau1').prop('hidden', n !== 1);
    $('#btnUpMau1').prop('hidden', DA_HOAN_THANH || n !== 1);
    $('#btnTaiMau2').prop('hidden', n !== 2);
    $('#btnUpMau2').prop('hidden', DA_HOAN_THANH || n !== 2);
    $('#tieuDeMau').text(n === 1
        ? 'Mẫu 1 — Bảng đáp ứng: tải mẫu, điền rồi nộp lên'
        : 'Mẫu 2 — Bảng chào giá: tải mẫu, điền rồi nộp lên');

    // Thanh bước trên đầu
    $('#step1').removeClass('is-active').addClass('is-done');
    $('#step2').toggleClass('is-active', n === 1).toggleClass('is-done', n === 2);
    $('#step3').toggleClass('is-active', n === 2).removeClass('is-done');

    $('#btnTiepTuc').prop('hidden', DA_HOAN_THANH || n !== 1);
    $('#btnNop').prop('hidden', DA_HOAN_THANH || n !== 2);

    renderBang();
}

/** Nạp dữ liệu tóm tắt từ server */
function loadBang() {
    if (!BAO_GIA_ID) return;
    $('#tomTatBody').html(APP.skeletonRows(6, 6));

    APP.ajax(AJAX_URL, { action: 'getBangChaoGia', bao_gia_id: BAO_GIA_ID }, {
        success: function (res) {
            if (!res || !res.success) return;
            DU_LIEU = res.data || null;
            if (DU_LIEU && DU_LIEU.bao_gia && Number(DU_LIEU.bao_gia.da_hoan_thanh) === 1) {
                apDungKhoa(true);
            }
            // Nhóm mua theo BỘ: giá nằm ở dòng BỘ, chi tiết bên dưới bỏ trống.
            // Nhóm vật tư dược ngược lại — giá ở từng hàng hóa.
            GIA_THEO_BO = !!(DU_LIEU && DU_LIEU.nhom && DU_LIEU.nhom !== 'vat_tu_duoc');
            if (DU_LIEU && DU_LIEU.ten_nhom) {
                $('#ghiChuNhom').html('Gói thầu thuộc nhóm <strong>' +
                    APP.escape(DU_LIEU.ten_nhom) + '</strong> — file mẫu chỉ gồm ' +
                    'các cột áp dụng cho nhóm này.');
            }
            chuyenTab(TAB_HIEN);
        }
    });
}

/**
 * Vẽ các dòng hàng hóa chi tiết.
 *
 * Tách khỏi renderBang() để dùng chung cho 2 nhánh: hàng thuộc BỘ (có thụt vào)
 * và HÀNG LẺ (không thuộc bộ nào — không có dòng tiêu đề bộ phía trên).
 *
 * @param {boolean} thut Thụt lề tên hàng — chỉ khi hàng nằm trong một bộ
 */
function veChiTiet(ct, cap, m1, thut) {
    var html = '';
    for (var k = 0; k < ct.length; k++) {
        var d = ct[k];
        html += '<tr>' +
            '<td class="col-id">' + APP.escape(d.ma_hh || '') + '</td>' +
            '<td class="sticky-col"><span class="cell-main' + (thut ? ' cell-thut' : '') + '">' +
                (d.stt_chi_tiet && thut ? APP.escape(String(d.stt_chi_tiet)) + '. ' : '') +
                APP.escape(d.ten_hang_hoa) + '</span></td>';

        if (m1) {
            html += '<td class="cell-wrap">' + APP.escape(d.thong_so_ky_thuat || '') + '</td>';
            for (var c = 0; c < cap.length; c++) {
                var o = (d.dap_ung || {})[cap[c].khoa] || {};
                html += '<td class="cell-wrap">' + oDapUng(o) + '</td>';
            }
            var tl = (d.dap_ung || {}).tai_lieu;
            html += '<td class="cell-wrap">' +
                (tl && tl.dap_ung ? APP.escape(tl.dap_ung) : chuaCo()) + '</td>';
        } else {
            html += '<td>' + (d.ten_thuong_mai ? APP.escape(d.ten_thuong_mai) : chuaCo()) + '</td>' +
                '<td>' + APP.escape(d.model || '') + '</td>' +
                '<td>' + APP.escape(d.hang_san_xuat || '') + '</td>' +
                '<td>' + APP.escape(d.nam_san_xuat || '') + '</td>' +
                '<td>' + APP.escape(d.xuat_xu || '') + '</td>' +
                '<td class="col-qty">' + APP.escape(String(d.so_luong)) + '</td>' +
                '<td>' + APP.escape(d.dvt || '') + '</td>' +
                '<td class="col-price">' + (d.don_gia > 0 ? money(d.don_gia) : chuaCo()) + '</td>' +
                '<td class="col-price">' + (d.thanh_tien > 0 ? money(d.thanh_tien) : '—') + '</td>';
        }
        html += '</tr>';
    }
    return html;
}

/** Vẽ bảng tóm tắt theo BỘ, cột thay đổi theo mẫu đang xem */
function renderBang() {
    if (!DU_LIEU) return;

    var tim = ($('#searchHang').val() || '').trim().toLowerCase();
    var cap = DU_LIEU.cap || [];
    var m1  = TAB_HIEN === 1;

    // ---- Tiêu đề ----
    var th = '<tr>' +
        '<th class="col-id">Mã</th>' +
        '<th class="sticky-col">Tên hàng hóa / bộ</th>';
    if (m1) {
        th += '<th>Yêu cầu kỹ thuật mời chào giá</th>';
        for (var i = 0; i < cap.length; i++) {
            th += '<th>' + APP.escape(cap[i].nhan) + '</th>';
        }
        th += '<th>Tài liệu chứng minh</th>';
    } else {
        th += '<th>Tên thương mại</th><th>Model</th><th>Hãng SX</th>' +
              '<th>Năm SX</th><th>Xuất xứ</th>' +
              '<th class="col-qty">SL</th><th>ĐVT</th>' +
              '<th class="col-price">Đơn giá</th><th class="col-price">Thành tiền</th>';
    }
    th += '</tr>';
    $('#tomTatHead').html(th);

    var soCot = $('#tomTatHead tr th').length;

    // ---- Thân bảng ----
    var html = '';
    var hienDong = 0;

    for (var b = 0; b < (DU_LIEU.bo || []).length; b++) {
        var bo = DU_LIEU.bo[b];

        // Lọc theo ô tìm kiếm: giữ bộ nếu tên bộ HOẶC bất kỳ chi tiết nào khớp
        var ct = bo.chi_tiet || [];
        if (tim) {
            var khopBo = (bo.ten_bo || '').toLowerCase().indexOf(tim) > -1;
            if (!khopBo) {
                ct = ct.filter(function (d) {
                    return (d.ten_hang_hoa || '').toLowerCase().indexOf(tim) > -1
                        || (d.ma_hh || '').toLowerCase().indexOf(tim) > -1;
                });
                if (!ct.length) continue;
            }
        }

        // Dòng BỘ — nền đậm, gộp cả hàng.
        // Hàng LẺ (la_hang_le) không có bộ nên KHÔNG in dòng tiêu đề, hàng hóa
        // hiện thẳng như dòng bình thường.
        if (bo.la_hang_le) {
            html += veChiTiet(ct, cap, m1, false);
            hienDong += ct.length;
            continue;
        }

        // Ở tab MẪU 2 của nhóm mua theo bộ, chính DÒNG BỘ mang giá chào —
        // không gộp hết cột nữa mà in đủ tên thương mại / model / ... / thành
        // tiền, giống một dòng chào giá thật sự.
        if (!m1 && GIA_THEO_BO) {
            html += '<tr class="row-bo">' +
                '<td>' + APP.escape(bo.ma_bo || '') + '</td>' +
                '<td class="sticky-col"><strong>' +
                    (bo.stt_bo ? APP.escape(String(bo.stt_bo)) + '. ' : '') +
                    APP.escape(bo.ten_bo || '') + '</strong></td>' +
                '<td>' + (bo.ten_thuong_mai ? APP.escape(bo.ten_thuong_mai) : chuaCo()) + '</td>' +
                '<td>' + APP.escape(bo.model || '') + '</td>' +
                '<td>' + APP.escape(bo.hang_san_xuat || '') + '</td>' +
                '<td>' + APP.escape(bo.nam_san_xuat || '') + '</td>' +
                '<td>' + APP.escape(bo.xuat_xu || '') + '</td>' +
                '<td class="col-qty">' + APP.escape(String(bo.so_luong)) + '</td>' +
                '<td>' + APP.escape(bo.dvt || '') + '</td>' +
                '<td class="col-price">' + (bo.don_gia > 0 ? money(bo.don_gia) : chuaCo()) + '</td>' +
                '<td class="col-price">' + (bo.thanh_tien > 0 ? money(bo.thanh_tien) : '—') + '</td>' +
                '</tr>';
        } else if (m1) {
            // Tab MẪU 1: yêu cầu chung/khác/cấu hình gắn với BỘ nên phần ĐÁP
            // ỨNG cho chúng nhà thầu điền ở DÒNG BỘ. Vì vậy dòng bộ phải hiện
            // từng cột như dòng chi tiết, không gộp ô — nếu gộp thì nhà thầu
            // điền xong không thấy đâu, tưởng hệ thống không lưu.
            var duBo = bo.dap_ung || {};
            html += '<tr class="row-bo">' +
                '<td>' + APP.escape(bo.ma_bo || '') + '</td>' +
                '<td class="sticky-col"><strong>' +
                    (bo.stt_bo ? APP.escape(String(bo.stt_bo)) + '. ' : '') +
                    APP.escape(bo.ten_bo || '') + '</strong>' +
                    yeuCauBo(bo) +
                '</td>' +
                // Cột "Yêu cầu kỹ thuật mời chào giá" là của hàng hóa chi tiết,
                // dòng bộ không có → để trống cho khỏi hiểu nhầm.
                '<td></td>';
            for (var cb = 0; cb < cap.length; cb++) {
                html += '<td class="cell-wrap">' + oDapUng(duBo[cap[cb].khoa] || {}) + '</td>';
            }
            var tlBo = duBo.tai_lieu;
            html += '<td class="cell-wrap">' +
                (tlBo && tlBo.dap_ung ? APP.escape(tlBo.dap_ung) : chuaCo()) + '</td>' +
                '</tr>';
        } else {
            html += '<tr class="row-bo">' +
                '<td>' + APP.escape(bo.ma_bo || '') + '</td>' +
                '<td colspan="' + (soCot - 1) + '"><strong>' +
                    (bo.stt_bo ? APP.escape(String(bo.stt_bo)) + '. ' : '') +
                    APP.escape(bo.ten_bo || '') + '</strong>' +
                    (bo.dvt ? ' <span class="cell-sub">' + APP.escape(bo.dvt) +
                        ' × ' + APP.escape(String(bo.so_luong)) + '</span>' : '') +
                    yeuCauBo(bo) +
                '</td></tr>';
        }

        // Dòng chi tiết
        html += veChiTiet(ct, cap, m1, true);
        hienDong += ct.length;
    }

    if (!hienDong) {
        html = APP.emptyRow(soCot, tim
            ? 'Không tìm thấy hàng hóa khớp từ khóa'
            : 'Gói thầu chưa có danh mục hàng hóa');
    }
    $('#tomTatBody').html(html);

    capNhatTong();
}

/** Ô đáp ứng: hiện thông số đã khai + điểm không đạt (nếu có) */
function oDapUng(o) {
    var h = '';
    if (o.dap_ung) h += APP.escape(o.dap_ung);
    if (o.khong_dat) {
        h += (h ? '<br>' : '') +
             '<span class="badge badge-warning badge-quote">Không đạt</span> ' +
             APP.escape(o.khong_dat);
    }
    return h || chuaCo();
}

/** Yêu cầu chung / khác / cấu hình của BỘ — chỉ hiện phần nhóm này có */
function yeuCauBo(bo) {
    var h = '';
    if (bo.yeu_cau_chung)    h += '<div class="cell-sub">Yêu cầu chung: ' + APP.escape(bo.yeu_cau_chung) + '</div>';
    if (bo.yeu_cau_khac)     h += '<div class="cell-sub">Yêu cầu khác: ' + APP.escape(bo.yeu_cau_khac) + '</div>';
    if (bo.yeu_cau_cau_hinh) h += '<div class="cell-sub">Yêu cầu cấu hình: ' + APP.escape(bo.yeu_cau_cau_hinh) + '</div>';
    if (bo.nhom_nuoc)        h += '<div class="cell-sub">Nhóm nước: ' + APP.escape(bo.nhom_nuoc) + '</div>';
    return h;
}

function chuaCo() {
    return '<span class="text-muted">Chưa có</span>';
}

/** Cập nhật thanh tổng + badge tiến độ */
function capNhatTong() {
    if (!DU_LIEU) return;
    var t = DU_LIEU.tong || {};

    $('#tongTien').text(money(t.tong_tien || 0));
    $('#badgeTienDo').text(TAB_HIEN === 1
        ? 'Đã khai ' + (t.so_dap_ung || 0) + '/' + (t.so_hang_hoa || 0) + ' hàng hóa'
        : 'Đã chào giá ' + (t.so_chao || 0) + '/' + (t.so_hang_hoa || 0) + ' hàng hóa');

    var thieu = (t.so_hang_hoa || 0) - (TAB_HIEN === 1 ? (t.so_dap_ung || 0) : (t.so_chao || 0));
    $('#tbNote').text(thieu > 0
        ? 'Còn ' + thieu + ' hàng hóa chưa có dữ liệu — tải file mẫu về điền rồi upload lên.'
        : 'Đã đủ dữ liệu.');

    $('#tomTatGhiChu').html(TAB_HIEN === 1
        ? 'Bảng chỉ để <strong>xem lại</strong>. Muốn sửa: tải Mẫu 1 về, sửa trong file rồi upload lại — '
          + 'hệ thống ghi đè theo cột Mã.'
        : 'Đơn giá <strong>đã bao gồm</strong> thuế, phí, lệ phí và các dịch vụ liên quan. '
          + 'Muốn sửa: tải Mẫu 2 về, sửa rồi upload lại.');

    // Đếm trên thanh bước
    $('#demM1').text(t.so_dap_ung || 0);
    $('#demM2').text(t.so_chao || 0);
}

/* Không còn ô nhập tay trên web (nhà thầu điền trong file Excel rồi import)
   nên bỏ hẳn cơ chế "thay đổi chưa lưu" — chuyển bước là đi thẳng. */
function hoiTruocKhiRoi(tiep) { tiep(); }

/**
 * Bấm vào thanh bước để quay lại sửa.
 * B1 mở form thông tin; B2/B3 chuyển bảng; B4 bản ký; B5 chỉ dẫn tài liệu.
 */
function veBuoc(n) {
    if (n === 1) {
        hoiTruocKhiRoi(function () { suaThongTin(); });
        return;
    }

    if (!BAO_GIA_ID) {
        APP.toast('Hãy lưu thông tin công ty trước (bước 1)', 'warning');
        return;
    }

    hoiTruocKhiRoi(function () { diToiBuoc(n); });
}

/** Chuyển khối hiển thị sang bước n (đã qua bước hỏi lưu) */
function diToiBuoc(n) {
    // Ẩn hết rồi bật đúng khối cần
    $('#buocGia').prop('hidden', n !== 2 && n !== 3);
    $('#buocBanKy').prop('hidden', n !== 4);

    for (var i = 1; i <= 4; i++) {
        $('#step' + i).toggleClass('is-active', i === n);
    }
    $('#step1').addClass('is-done');

    if (n === 2 || n === 3) {
        chuyenTab(n === 2 ? 1 : 2);
    } else if (n === 4) {
        napBuocBanKy();
    }

    $('html, body').animate({ scrollTop: $('#steps').offset().top - 16 }, 250);
}

/**
 * Gom TẤT CẢ dữ liệu đang nhập trên bảng rồi gửi 1 lần.
 * Thay cho việc bấm Lưu từng dòng như trước.
 *
 * @param {function} xong callback(ok)
 */
/* ============ IMPORT ============ */
function openImport() {
    // Nêu rõ đang upload mẫu nào để nhà thầu khỏi chọn nhầm file
    $('#impTitle').text(TAB_HIEN === 1
        ? 'Upload Mẫu 1 — Bảng đáp ứng kỹ thuật'
        : 'Upload Mẫu 2 — Bảng chào giá');
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
    var t = (DU_LIEU && DU_LIEU.tong) || {};
    var soChao = t.so_chao || 0;
    var tong   = t.so_hang_hoa || 0;

    var msg = 'Nộp báo giá với ' + soChao + '/' + tong + ' hàng hóa đã chào giá?';
    if (soChao < tong) {
        msg += '\n\nCác hàng hóa chưa điền giá sẽ được ghi nhận là KHÔNG CHÀO.';
    }
    msg += '\n\nSau khi nộp, hãy gửi bản giấy tới bên mời chào giá để được xác nhận.';

    APP.confirm(msg, function () {
        APP.ajax(AJAX_URL, { action: 'nopBaoGia', bao_gia_id: BAO_GIA_ID }, {
            success: function (res) {
                APP.toast(res.message, 'success');
                $('#step3').removeClass('is-active').addClass('is-done');
                // Nộp xong -> sang Bước 4 để tải file Word về ký rồi upload.
                // (Trước đây mở thẳng trang tra cứu MST — không còn hợp với luồng 5 bước.)
                setTimeout(function () { diToiBuoc(4); }, 600);
            }
        });
    }, { yesClass: 'btn-primary', yesText: 'Nộp báo giá' });
}

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
$('#importModal, #banKyModal').on('click', function (e) { if (e.target === this) $(this).removeClass('open'); });
$('#hdModal').on('click', function (e) { if (e.target === this) dongHuongDan(); });
$(document).on('keydown', function (e) {
    if (e.key !== 'Escape') return;
    // Esc: đóng modal con trước, hết modal mới đóng trang tra cứu
    if ($('#hdModal').hasClass('open')) { dongHuongDan(); return; }
    if ($('#banKyModal').hasClass('open')) { closeBanKy(); return; }
    if ($('#importModal').hasClass('open')) { closeImport(); return; }
    if ($('#traCuuOverlay').hasClass('open')) dongTraCuu();
});

/* ============== CẢNH BÁO KHI ĐÓNG TRÌNH DUYỆT GIỮA CHỪNG ==============
   Báo giá làm dở KHÔNG tra cứu lại được (§10.2): đóng tab là mất hết, lần sau
   phải khai lại từ đầu. Cảnh báo ngay lúc người dùng định rời trang.

   Chỉ cảnh báo khi ĐANG làm dở: đã tạo báo giá nhưng chưa chốt Hoàn thành.
   Đã hoàn thành rồi thì dữ liệu đã lưu chắc chắn, không cần chặn.

   Lưu ý: trình duyệt hiện đại KHÔNG cho tự đặt nội dung thông báo, chỉ hiện
   câu mặc định của chính nó. Vì vậy phần dặn dò chi tiết đặt ở banner trên
   trang (#bannerLamDo), không trông chờ vào hộp thoại này. */
var BO_QUA_CANH_BAO = false;   // bật khi rời trang có chủ đích (đăng xuất...)

window.addEventListener('beforeunload', function (e) {
    if (BO_QUA_CANH_BAO) return;
    if (!BAO_GIA_ID || DA_HOAN_THANH) return;

    e.preventDefault();
    e.returnValue = '';       // bắt buộc cho Chrome/Edge mới hiện hộp thoại
    return '';
});

/* Nút TẢI FILE là thẻ <a href> -> trình duyệt coi như điều hướng và bật
   cảnh báo "Rời khỏi trang web", trong khi thực tế trang KHÔNG rời đi
   (server trả Content-Disposition: attachment). Tắt cảnh báo trong chốc lát
   quanh cú bấm, rồi bật lại — không dùng cờ vĩnh viễn để nếu người dùng bấm
   Thoát ngay sau đó vẫn được hỏi. */
$(document).on('click', 'a[href*="download.php"]', function () {
    BO_QUA_CANH_BAO = true;
    setTimeout(function () { BO_QUA_CANH_BAO = false; }, 3000);
});

/* Bấm Thoát / mở trang hướng dẫn là rời trang CÓ CHỦ ĐÍCH — vẫn mất dữ liệu
   nên vẫn phải hỏi, nhưng hỏi bằng câu tiếng Việt rõ nghĩa của mình thay vì
   câu mặc định cụt lủn của trình duyệt. */
$(document).on('click', '.pnav-out', function (e) {
    if (!BAO_GIA_ID || DA_HOAN_THANH) return;
    if (!window.confirm(
        'Báo giá của bạn CHƯA hoàn thành.\n\n'
        + 'Thoát bây giờ sẽ mất toàn bộ nội dung đã nhập, lần sau phải khai '
        + 'lại từ đầu.\n\nBạn vẫn muốn thoát?'
    )) {
        e.preventDefault();
        return;
    }
    BO_QUA_CANH_BAO = true;   // đã đồng ý -> không hỏi lại lần 2
});

$(document).ready(function () {
    if (BAO_GIA_ID) loadBang();
    tuHienHuongDan();

    // Đang làm dở -> hiện banner nhắc không được tắt trình duyệt
    if (BAO_GIA_ID && !DA_HOAN_THANH) $('#bannerLamDo').prop('hidden', false);
});
</script>
</body>
</html>
