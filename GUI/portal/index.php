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
// ?sua=<id> — từ trang tra cứu bấm "Sửa lại báo giá" để làm tiếp báo giá cũ.
// Chỉ nhận khi báo giá thuộc đúng gói thầu này, CHƯA chốt hoàn thành, và
// nằm trong danh sách báo giá của phiên (chống sửa báo giá của công ty khác).
$suaId = (int)Helper::get('sua', 0);
if ($suaId > 0) {
    $idsPhien = SessionHelper::get('portal_bao_gia_ids', []);
    $mstTraCuu = (string)SessionHelper::get('portal_mst_tra_cuu', '');

    $duocSua = (is_array($idsPhien) && in_array($suaId, $idsPhien, true))
            || ($mstTraCuu !== '' && BG_BaoGia_BUS::baoGiaCuaMst($suaId, $mstTraCuu));

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
            <button type="button" class="pnav-item" onclick="moTraCuu()">
                <?= IconHelper::svg('search', 16) ?><span>Tra cứu báo giá của tôi</span>
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

<main class="portal-main" id="main">

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
            Quý công ty vẫn có thể <strong>tra cứu báo giá đã nộp</strong> và
            <strong>tải bản có dấu, chữ ký</strong> ở mục bên dưới.
        </p>
        <button type="button" class="btn btn-primary" onclick="moTraCuu()">
            <?= IconHelper::svg('search', 16) ?>Tra cứu báo giá đã nộp
        </button>
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
        <button type="button" class="step step-btn" id="step5" onclick="veBuoc(5)">
            <span class="step-no">5</span> Chỉ dẫn vị trí tài liệu
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
                <h2 style="font-size:15px;margin:0">Thao tác và tải file</h2>
            </div>
            <div style="padding:18px">
                <!-- Chỉ hiện file mẫu + upload của ĐÚNG bước đang xem (JS bật/tắt theo tab) -->
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px">
                    <a class="btn btn-primary" id="btnTaiMau1"
                       href="<?= AppConfig::baseUrl('GUI/portal/download.php') ?>?t=<?= urlencode($token) ?>&mau=mau1">
                        <?= IconHelper::svg('download', 16) ?>Tải Mẫu 1 — Đáp ứng kỹ thuật
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
                        <strong class="chon-cach">Chọn 1 trong 2 cách:</strong>
                        <span class="cach"><span class="cach-no">1</span>
                            Tải file mẫu về, điền rồi <strong>import Excel</strong> lên.</span>
                        <span class="cach-hoac">hoặc</span>
                        <span class="cach"><span class="cach-no">2</span>
                            <strong>Điền thủ công</strong> trực tiếp vào bảng bên dưới.</span>
                        <span class="cach-chi-tiet">Mẫu 1: điền 2 cột cuối
                            (<strong>Yêu cầu kỹ thuật chào giá</strong>,
                            <strong>Các điểm không đạt</strong>).</span>
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


            <!-- ===== MẪU 1: BẢNG ĐÁP ỨNG KỸ THUẬT ===== -->
            <div id="paneM1">
                <div class="table-wrap has-sticky" id="bangWrapM1">
                    <table class="table" id="bangM1">
                        <thead>
                            <tr>
                                <th class="col-id">Mã HH</th>
                                <th class="sticky-col">Tên hàng hóa mời chào giá</th>
                                <th>Yêu cầu kỹ thuật mời chào giá</th>
                                <th>Yêu cầu kỹ thuật chào giá <span class="req">*</span></th>
                                <th>Các điểm không đạt kèm thuyết minh</th>
                            </tr>
                        </thead>
                        <tbody id="bangBodyM1"></tbody>
                    </table>
                </div>
            </div>

            <!-- ===== MẪU 2: BẢNG CHÀO GIÁ ===== -->
            <div id="paneM2" hidden>
                <div class="table-wrap has-sticky" id="bangWrapM2">
                    <table class="table" id="bangM2">
                        <thead>
                            <tr>
                                <th class="col-id">Mã HH</th>
                                <th class="sticky-col">Tên hàng hóa mời chào giá</th>
                                <th>Tên thương mại</th>
                                <th>Ký, mã, nhãn hiệu, model</th>
                                <th>Hãng sản xuất</th>
                                <th>Xuất xứ</th>
                                <th class="col-qty">Số lượng</th>
                                <th>Quy cách</th>
                                <th>ĐVT</th>
                                <th class="col-price">Đơn giá (VND)</th>
                                <th class="col-price">Thành tiền</th>
                                <th class="col-actions">Chi tiết</th>
                            </tr>
                        </thead>
                        <tbody id="bangBodyM2"></tbody>
                    </table>
                </div>
                <p class="form-hint" style="padding:10px 16px 0">
                    Đơn giá <strong>đã bao gồm</strong> thuế, phí, lệ phí và các dịch vụ liên quan (nếu có).
                    Nhập dạng <code>10000</code>, không dùng dấu phân cách.
                    Bấm <?= IconHelper::svg('pencil', 13) ?> để nhập thêm giá trúng thầu gần nhất,
                    tài liệu tham chiếu, số thông báo mời thầu.
                </p>
            </div>

            <div class="total-bar">
                <span class="tb-label">Tổng giá trị báo giá</span>
                <span class="tb-value" id="tongTien">0</span>
                <span class="tb-label">VND</span>
                <span class="tb-spacer"></span>
                <span class="tb-note" id="tbNote"></span>
                <!-- Lưu TẤT CẢ các dòng 1 lần, không phải bấm lưu từng dòng -->
                <button type="button" class="btn btn-primary" id="btnTiepTuc" onclick="luuVaTiepTuc()">
                    <?= IconHelper::svg('save', 16) ?>Lưu và tiếp tục <?= IconHelper::svg('chevron-right', 16) ?>
                </button>
                <button type="button" class="btn btn-primary" id="btnNop" onclick="luuVaNop()" hidden>
                    <?= IconHelper::svg('send', 16) ?>Lưu và nộp báo giá
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
                        <span class="cach-chi-tiet">Upload xong báo giá tự chuyển sang trạng thái
                            <strong>Đã xác nhận</strong>.</span>
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
                    <button type="button" class="btn btn-primary" onclick="veBuoc(5)">
                        Tiếp tục: Chỉ dẫn vị trí tài liệu <?= IconHelper::svg('chevron-right', 16) ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ BƯỚC 5: CHỈ DẪN VỊ TRÍ TÀI LIỆU ============ -->
    <div id="buocCatalog" hidden>
        <div class="card">
            <div class="card-header" style="display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--gray-200);flex-wrap:wrap">
                <?= IconHelper::svg('file-spreadsheet', 19) ?>
                <h2 style="font-size:15px;margin:0">Bước 5 — Chỉ dẫn vị trí tài liệu</h2>
                <span class="badge badge-neutral" id="clTrangThai">Chưa có file</span>
            </div>
            <div style="padding:18px">
                <div class="callout-cach" style="margin-bottom:16px">
                    <?= IconHelper::svg('info', 22) ?>
                    <span>
                        <strong class="chon-cach">Làm theo 3 bước:</strong>
                        <span class="cach"><span class="cach-no">1</span>
                            <strong>Tải file mẫu</strong> bảng chỉ dẫn (Word) về máy.</span>
                        <span class="cach"><span class="cach-no">2</span>
                            Điền <strong>trang catalog chứng minh</strong> vào file, in ký + đóng dấu.</span>
                        <span class="cach"><span class="cach-no">3</span>
                            <strong>Upload file Word</strong> đã điền và <strong>catalog</strong> đã ký lên.</span>
                        <span class="cach-chi-tiet">Trong file ghi rõ số trang catalog chứng minh thông số kỹ thuật
                            đã chào. Ví dụ: <code>Trang 1-15</code>, <code>Trang 16-20</code>.</span>
                    </span>
                </div>

                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px">
                    <a class="btn btn-primary" id="btnTaiCatalog" href="#">
                        <?= IconHelper::svg('download', 16) ?>Tải bảng chỉ dẫn (Word)
                    </a>
                    <button type="button" class="btn btn-success" onclick="moUpCatalog()">
                        <?= IconHelper::svg('upload', 16) ?>Upload catalog
                    </button>
                    <button type="button" class="btn btn-success" onclick="moUpCatalogExcel()">
                        <?= IconHelper::svg('file-text', 16) ?>Upload bảng chỉ dẫn đã điền
                    </button>
                    <a class="btn btn-outline-secondary" id="btnXemCatalog" href="#" target="_blank" rel="noopener" hidden>
                        <?= IconHelper::svg('eye', 16) ?>Xem catalog đã tải
                    </a>
                    <a class="btn btn-outline-secondary" id="btnXemCatalogExcel" href="#" hidden>
                        <?= IconHelper::svg('download', 16) ?>Tải bảng chỉ dẫn đã nộp
                    </a>
                </div>

                <!-- Đã bỏ bảng điền tay: nhà thầu điền thẳng vào file Word mẫu -->
                <div class="total-bar" id="bangWrapCl">
                    <span class="tb-note" id="clNote"></span>
                    <span class="tb-spacer"></span>
                    <button type="button" class="btn btn-primary" onclick="hoanThanhBaoGia()" id="btnHoanThanh">
                        <?= IconHelper::svg('check-circle', 16) ?>Hoàn thành báo giá
                    </button>
                </div>
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

                    <!-- ===== MẪU 2: Bảng chào giá ===== -->
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
                            <label for="d_hang_san_xuat">Hãng sản xuất</label>
                            <input type="text" id="d_hang_san_xuat" class="form-control" maxlength="500">
                        </div>
                        <div class="form-group">
                            <label for="d_xuat_xu">Xuất xứ</label>
                            <input type="text" id="d_xuat_xu" class="form-control" maxlength="500">
                        </div>
                        <div class="form-group">
                            <label for="d_quy_cach">Quy cách</label>
                            <input type="text" id="d_quy_cach" class="form-control" maxlength="500">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="d_don_gia">Đơn giá (VND) <span class="req">*</span></label>
                            <input type="text" id="d_don_gia" class="form-control" placeholder="VD: 10000">
                            <div class="form-hint">
                                Đã bao gồm thuế, phí, lệ phí và dịch vụ liên quan.
                                Ghi số thuần, không dùng dấu phân cách nghìn.
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="d_don_gia_trung_thau">Đơn giá trúng thầu gần nhất (VNĐ)</label>
                            <input type="text" id="d_don_gia_trung_thau" class="form-control" placeholder="VD: 10000">
                            <div class="form-hint">Trong vòng 360 ngày, nếu có.</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="d_tai_lieu_tham_chieu">Tài liệu tham chiếu đơn giá trúng thầu gần nhất</label>
                        <textarea id="d_tai_lieu_tham_chieu" class="form-control" rows="2"
                                  placeholder="Điền số thông báo mời thầu (Ví dụ: IB2500…)"></textarea>
                        <div class="form-hint">Theo ghi chú (12) của Mẫu 2 — Thư mời chào giá.</div>
                    </div>

                    <!-- ===== MẪU 1: Bảng đáp ứng kỹ thuật ===== -->
                    <div class="form-group">
                        <label for="d_thong_so_chao_gia">Yêu cầu kỹ thuật chào giá</label>
                        <textarea id="d_thong_so_chao_gia" class="form-control" rows="3"
                                  placeholder="Nêu các thông số kỹ thuật của hàng hóa tương ứng với yêu cầu"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="d_diem_khong_dat">Các điểm không đạt kèm thuyết minh</label>
                        <textarea id="d_diem_khong_dat" class="form-control" rows="3"
                                  placeholder="Nêu rõ thông số không đáp ứng (nếu có) kèm thuyết minh/lý giải"></textarea>
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

<!-- ============================================================
     NÚT NỔI + TRANG TRA CỨU BÁO GIÁ THEO MÃ SỐ THUẾ
     Hiện ở MỌI trạng thái của cổng. Bấm nút -> mở lớp phủ toàn trang
     liệt kê TẤT CẢ báo giá của MST đó (mọi gói thầu), nhóm theo gói.
     ============================================================ -->

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

<div class="modal" id="clExcelModal">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="clXlTitle" style="max-width:640px">
        <div class="modal-header">
            <h3 id="clXlTitle">Upload bảng chỉ dẫn vị trí tài liệu đã điền</h3>
            <button type="button" class="close" onclick="closeCatalogExcel()" aria-label="Đóng"><?= IconHelper::svg('x', 20) ?></button>
        </div>
        <div class="modal-body">
            <div class="alert alert-info">
                <?= IconHelper::svg('info', 16) ?>
                <span>Tải <strong>file mẫu Word</strong> ở trên về, điền số trang catalog chứng minh
                cho từng hàng hóa, rồi tải file đã điền lên đây. Chỉ nhận
                <strong>.docx</strong>, <strong>.doc</strong> hoặc <strong>.pdf</strong> (bản scan đã ký), tối đa 10MB.</span>
            </div>
            <div class="form-group">
                <label for="clXlFile">Chọn file <span class="req">*</span></label>
                <input type="file" id="clXlFile" class="form-control" accept=".docx,.doc,.pdf">
                <div class="form-hint" id="clXlInfo"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeCatalogExcel()">Hủy</button>
            <button type="button" class="btn btn-primary" id="btnDoUpCatalogExcel" onclick="doUpCatalogExcel()" disabled>
                <?= IconHelper::svg('upload', 16) ?>Tải lên
            </button>
        </div>
    </div>
</div>

<div class="modal" id="catalogModal">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="clTitle" style="max-width:640px">
        <div class="modal-header">
            <h3 id="clTitle">Upload file chỉ dẫn vị trí tài liệu đã ký</h3>
            <button type="button" class="close" onclick="closeCatalogModal()" aria-label="Đóng"><?= IconHelper::svg('x', 20) ?></button>
        </div>
        <div class="modal-body">
            <div class="alert alert-info">
                <?= IconHelper::svg('info', 16) ?>
                <span>Tải bảng chỉ dẫn về, in ra ký + đóng dấu kèm catalog,
                rồi scan thành PDF hoặc ảnh để tải lên đây. Tối đa 10MB.</span>
            </div>
            <div class="form-group">
                <label for="clFile">Chọn file (PDF, JPG, PNG) <span class="req">*</span></label>
                <input type="file" id="clFile" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                <div class="form-hint" id="clFileInfo"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeCatalogModal()">Hủy</button>
            <button type="button" class="btn btn-primary" id="btnDoUpCatalog" onclick="doUpCatalog()" disabled>
                <?= IconHelper::svg('upload', 16) ?>Tải lên
            </button>
        </div>
    </div>
</div>

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
                    Sau khi tải lên, báo giá sẽ tự chuyển sang trạng thái
                    <strong>ĐÃ XÁC NHẬN</strong> và không sửa được nữa.
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
var BAO_GIA_ID = <?= (int)$baoGiaId ?>;
var HIEU_LUC_MIN = <?= (int)$goiThau->hieu_luc_bao_gia ?>;
var TT_BG_XN = <?= (int)BG_BaoGia_PUBLIC::TT_DA_XAC_NHAN ?>;
var TT_BG_TC = <?= (int)BG_BaoGia_PUBLIC::TT_TU_CHOI ?>;
var DONG = [];      // dữ liệu bảng chào giá

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
                // Chưa chốt hoàn thành + gói còn nhận -> cho quay lại sửa tiếp
                (Number(b.da_hoan_thanh) !== 1 && g && g.trang_thai_bao_gia === 'dang_mo'
                    ? '<a class="btn btn-sm btn-primary" href="' + APP.escape(g.url_portal) +
                      '&sua=' + b.id + '">' +
                      APP.icon('pencil', 15) + '<span class="btn-label">Sửa lại báo giá</span></a>'
                    : '') +
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
    APP.ajax(AJAX_URL, { action: 'getBangCatalog', bao_gia_id: BAO_GIA_ID }, {
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

/* ---------- Upload file Excel chỉ dẫn (Bước 5) ---------- */
function moUpCatalogExcel() {
    $('#clXlFile').val('');
    $('#clXlInfo').empty();
    $('#btnDoUpCatalogExcel').prop('disabled', true);
    $('#clExcelModal').addClass('open');
}
function closeCatalogExcel() { $('#clExcelModal').removeClass('open'); }

$(document).on('change', '#clXlFile', function () {
    var f = this.files[0];
    $('#btnDoUpCatalogExcel').prop('disabled', !f);
    $('#clXlInfo').text(f ? f.name + ' (' + Math.round(f.size / 1024) + ' KB)' : '');
});

function doUpCatalogExcel() {
    var f = document.getElementById('clXlFile').files[0];
    if (!f) { APP.toast('Chưa chọn file', 'warning'); return; }

    var fd = new FormData();
    fd.append('action', 'uploadCatalogExcel');
    fd.append('bao_gia_id', BAO_GIA_ID);
    fd.append('file', f);

    APP.showLoading('#clExcelModal .modal-body');
    $.ajax({
        url: AJAX_URL, type: 'POST', data: fd,
        processData: false, contentType: false, dataType: 'json',
        headers: { 'X-CSRF-Token': CSRF_TOKEN },
        success: function (res) {
            if (res && res.success) {
                APP.toast(res.message, 'success');
                closeCatalogExcel();
                napBuocCatalog();
            } else {
                APP.toast((res && res.message) || 'Tải lên thất bại', 'error');
            }
        },
        error: function (xhr) {
            var m = 'Tải lên thất bại';
            try { m = JSON.parse(xhr.responseText).message || m; } catch (e) {}
            APP.toast(m, 'error');
        },
        complete: function () { APP.hideLoading('#clExcelModal .modal-body'); }
    });
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
            APP.showLoading('#buocCatalog');
            APP.ajax(AJAX_URL, { action: 'hoanThanh', bao_gia_id: BAO_GIA_ID }, {
                success: function (res) {
                    APP.hideLoading('#buocCatalog');
                    if (res && res.success) {
                        APP.toast(res.message, 'success');
                        apDungKhoa(true);
                        $('html, body').animate({ scrollTop: 0 }, 250);
                    } else {
                        APP.toast((res && res.message) || 'Không hoàn thành được', 'error');
                    }
                },
                error: function () {
                    APP.hideLoading('#buocCatalog');
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

    CHUA_LUU = false;        // đã khóa thì không còn "thay đổi chưa lưu"
    CHUA_LUU_CL = false;

    $('#bannerKhoa').prop('hidden', false);

    // Khóa toàn bộ ô nhập trong các bảng + form thông tin
    $('#buocGia input, #buocGia textarea, #buocGia select').prop('disabled', true);

    // Ẩn các nút ghi dữ liệu
    $('#btnTiepTuc, #btnNop, #btnHoanThanh').prop('hidden', true);
    // Ẩn mọi nút upload (kể cả nút Excel chỉ dẫn — đều dùng .btn-success)
    $('#buocGia .btn-success, #buocCatalog .btn-success').prop('hidden', true);
    $('#buocBanKy .btn-success').prop('hidden', true);
    $('[onclick="suaThongTin()"]').prop('hidden', true);

    // Bảng chuyển sang nền xám nhạt cho dễ nhận biết
    $('#bangM1, #bangM2').addClass('is-locked');
}

/* ============== BƯỚC 5: CHỈ DẪN VỊ TRÍ TÀI LIỆU (CATALOG) ============== */
var CATALOG = [];

/** Nạp bảng chỉ dẫn vị trí tài liệu */
function napBuocCatalog() {
    if (!BAO_GIA_ID) return;

    $('#btnTaiCatalog').attr('href',
        URL_DOWNLOAD + '?t=' + encodeURIComponent(PORTAL_TOKEN)
        + '&loai=word_catalog&id=' + BAO_GIA_ID);

    APP.showLoading('#bangWrapCl');
    APP.ajax(AJAX_URL, { action: 'getBangCatalog', bao_gia_id: BAO_GIA_ID }, {
        success: function (res) {
            APP.hideLoading('#bangWrapCl');
            if (!res || !res.success) return;
            CATALOG = (res.data && res.data.dong) || [];

            if (res.data && Number(res.data.da_hoan_thanh) === 1) apDungKhoa(true);

            var f = res.data && res.data.file;
            var co = !!(f && f.ten_file_goc);
            $('#clTrangThai')
                .text(co ? 'Đã có file' : 'Chưa có file')
                .attr('class', 'badge ' + (co ? 'badge-success' : 'badge-warning'));
            $('#btnXemCatalog').prop('hidden', !co).attr('href',
                URL_DOWNLOAD + '?t=' + encodeURIComponent(PORTAL_TOKEN)
                + '&loai=catalog&id=' + BAO_GIA_ID);

            var fx = res.data && res.data.file_excel;
            var coXl = !!(fx && fx.ten_file_goc);
            $('#btnXemCatalogExcel').prop('hidden', !coXl).attr('href',
                URL_DOWNLOAD + '?t=' + encodeURIComponent(PORTAL_TOKEN)
                + '&loai=catalog_excel&id=' + BAO_GIA_ID);

            // Phải nộp ĐỦ 2 file mới cho chốt hoàn thành
            $('#btnHoanThanh').prop('disabled', (!co || !coXl) && !DA_HOAN_THANH);
            $('#clNote').text(
                co && coXl ? 'Đã nộp đủ catalog và bảng chỉ dẫn.'
                : (!co && !coXl ? 'Chưa nộp catalog và bảng chỉ dẫn.'
                : (!co ? 'Chưa nộp catalog đã ký.' : 'Chưa nộp bảng chỉ dẫn đã điền.')));
        },
        error: function () { APP.hideLoading('#bangWrapCl'); }
    });
}




// Gõ vào bất kỳ ô nào của bảng M1/M2 -> đánh dấu chưa lưu
$(document).on('input change',
    '#bangM1 .f-tsc, #bangM1 .f-dkd, #bangM2 .f-ttm, #bangM2 .f-model, '
    + '#bangM2 .f-hsx, #bangM2 .f-xx, #bangM2 .f-qc, #bangM2 .f-gia',
    function () {
        danhDauSua();
        // Đọc ngay vào DONG[] để bộ đếm + nút "Lưu và tiếp tục" cập nhật tức thì,
        // không phải chờ tới lúc lưu mới biết đã điền đủ chưa.
        docBangVaoDONG();
        capNhatTong();
    });

// Rời trang khi còn thay đổi chưa lưu -> trình duyệt tự hỏi
$(window).on('beforeunload', function () {
    if (CHUA_LUU || CHUA_LUU_CL) return 'Bạn có thay đổi chưa lưu.';
});

var CHUA_LUU_CL = false;   // giữ lại cho beforeunload, không còn ô nhập tay

/** Mở hộp thoại upload file catalog đã ký */
function moUpCatalog() {
    $('#clFile').val('');
    $('#clFileInfo').empty();
    $('#btnDoUpCatalog').prop('disabled', true);
    $('#catalogModal').addClass('open');
}
function closeCatalogModal() { $('#catalogModal').removeClass('open'); }

$(document).on('change', '#clFile', function () {
    var f = this.files[0];
    $('#btnDoUpCatalog').prop('disabled', !f);
    $('#clFileInfo').text(f ? f.name + ' (' + Math.round(f.size / 1024) + ' KB)' : '');
});

function doUpCatalog() {
    var f = document.getElementById('clFile').files[0];
    if (!f) { APP.toast('Chưa chọn file', 'warning'); return; }

    var fd = new FormData();
    fd.append('action', 'uploadCatalog');
    fd.append('bao_gia_id', BAO_GIA_ID);
    fd.append('file', f);

    APP.showLoading('#catalogModal .modal-body');
    $.ajax({
        url: AJAX_URL, type: 'POST', data: fd,
        processData: false, contentType: false, dataType: 'json',
        headers: { 'X-CSRF-Token': CSRF_TOKEN },
        success: function (res) {
            if (res && res.success) {
                APP.toast(res.message, 'success');
                closeCatalogModal();
                napBuocCatalog();
            } else {
                APP.toast((res && res.message) || 'Tải lên thất bại', 'error');
            }
        },
        error: function (xhr) {
            var m = 'Tải lên thất bại';
            try { m = JSON.parse(xhr.responseText).message || m; } catch (e) {}
            APP.toast(m, 'error');
        },
        complete: function () { APP.hideLoading('#catalogModal .modal-body'); }
    });
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

    APP.confirm('Tải lên bản ký này? Sau khi tải lên, báo giá chuyển sang ĐÃ XÁC NHẬN và KHÔNG sửa được nữa.',
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
                        // Đang ở Bước 4 trong luồng chào giá -> đi tiếp Bước 5,
                        // KHÔNG nhảy sang trang tra cứu MST.
                        diToiBuoc(5);
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

/* ============ BƯỚC 2: BẢNG GIÁ ============ */
var TAB_HIEN = 1;   // 1 = Mẫu 1 (đáp ứng KT), 2 = Mẫu 2 (chào giá)

/** Chuyển giữa Mẫu 1 (bước 2) và Mẫu 2 (bước 3) */
function chuyenTab(n) {
    TAB_HIEN = n;
    $('#paneM1').prop('hidden', n !== 1);
    $('#paneM2').prop('hidden', n !== 2);

    // Thanh bước trên đầu: bước đang xem = is-active, bước đã qua = is-done
    $('#step1').removeClass('is-active').addClass('is-done');
    $('#step2').toggleClass('is-active', n === 1).toggleClass('is-done', n === 2);
    $('#step3').toggleClass('is-active', n === 2).removeClass('is-done');

    // Nút dưới: bước 2 -> "Tiếp tục", bước 3 -> "Nộp báo giá".
    // Đã chốt hoàn thành thì ẩn hẳn cả 2 (chỉ còn xem).
    $('#btnTiepTuc').prop('hidden', DA_HOAN_THANH || n !== 1);
    $('#btnNop').prop('hidden', DA_HOAN_THANH || n !== 2);

    // Chỉ hiện file mẫu + upload của ĐÚNG bước đang xem
    $('#btnTaiMau1').prop('hidden', n !== 1);
    $('#btnUpMau1').prop('hidden', n !== 1);
    $('#btnTaiMau2').prop('hidden', n !== 2);
    $('#btnUpMau2').prop('hidden', n !== 2);

    $('#ghiChuMau').html(
        '<strong class="chon-cach">Chọn 1 trong 2 cách:</strong>'
        + '<span class="cach"><span class="cach-no">1</span> '
        + 'Tải file mẫu về, điền rồi <strong>import Excel</strong> lên.</span>'
        + '<span class="cach-hoac">hoặc</span>'
        + '<span class="cach"><span class="cach-no">2</span> '
        + '<strong>Điền thủ công</strong> trực tiếp vào bảng bên dưới.</span>'
        + '<span class="cach-chi-tiet">' + (n === 1
            ? 'Mẫu 1: điền 2 cột cuối (<strong>Yêu cầu kỹ thuật chào giá</strong>, '
              + '<strong>Các điểm không đạt</strong>).'
            : 'Mẫu 2: điền từ <strong>Tên thương mại</strong> đến '
              + '<strong>Số thông báo mời thầu</strong>. Cột <strong>Đơn giá</strong> '
              + 'ghi dạng <code>10000</code> — không dùng dấu phân cách như <code>10.000,00</code>.')
        + '</span>');

    capNhatTong();
}

/* ===================== THEO DÕI THAY ĐỔI CHƯA LƯU ===================== */
var CHUA_LUU = false;

/** Đánh dấu có sửa nhưng chưa lưu */
function danhDauSua() {
    CHUA_LUU = true;
    $('#tbNote').addClass('is-dirty');
}
/** Đã lưu xong -> xóa dấu */
function xoaDauSua() {
    CHUA_LUU = false;
    $('#tbNote').removeClass('is-dirty');
}

/**
 * Hỏi trước khi rời khỏi bước đang sửa dở.
 * @param {function} tiep Việc cần làm sau khi người dùng quyết định
 */
function hoiTruocKhiRoi(tiep) {
    if (!CHUA_LUU) { tiep(); return; }
    // APP.confirm chỉ có nhánh "Đồng ý" (không có callback cho nút Hủy),
    // nên đặt Đồng ý = "Lưu rồi chuyển". Bấm Hủy thì ở lại bảng, không mất dữ liệu.
    APP.confirm(
        'Bạn có thay đổi chưa lưu ở bảng này. Lưu lại rồi chuyển bước?',
        function () {
            luuTatCa(function (ok) { if (ok) tiep(); });
        },
        { title: 'Chưa lưu thay đổi', yesText: 'Lưu rồi chuyển', noText: 'Ở lại', yesClass: 'btn-primary' }
    );
}

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
    $('#buocCatalog').prop('hidden', n !== 5);

    for (var i = 1; i <= 5; i++) {
        $('#step' + i).toggleClass('is-active', i === n);
    }
    $('#step1').addClass('is-done');

    if (n === 2 || n === 3) {
        chuyenTab(n === 2 ? 1 : 2);
    } else if (n === 4) {
        napBuocBanKy();
    } else if (n === 5) {
        napBuocCatalog();
    }

    $('html, body').animate({ scrollTop: $('#steps').offset().top - 16 }, 250);
}

/**
 * Gom TẤT CẢ dữ liệu đang nhập trên bảng rồi gửi 1 lần.
 * Thay cho việc bấm Lưu từng dòng như trước.
 *
 * @param {function} xong callback(ok)
 */
function luuTatCa(xong) {
    if (!BAO_GIA_ID) { if (xong) xong(false); return; }

    docBangVaoDONG();       // đọc giá trị đang gõ trên input vào mảng DONG

    var payload = [];
    for (var i = 0; i < DONG.length; i++) {
        var r = DONG[i];
        payload.push({
            hang_hoa_id:         r.hang_hoa_id,
            thong_so_chao_gia:   r.thong_so_chao_gia || '',
            diem_khong_dat:      r.diem_khong_dat || '',
            ten_thuong_mai:      r.ten_thuong_mai || '',
            model:               r.model || '',
            hang_san_xuat:       r.hang_san_xuat || '',
            xuat_xu:             r.xuat_xu || '',
            quy_cach:            r.quy_cach || '',
            don_gia:             r.don_gia || 0,
            don_gia_trung_thau:  r.don_gia_trung_thau || 0,
            tai_lieu_tham_chieu: r.tai_lieu_tham_chieu || ''
        });
    }

    APP.showLoading('#buocGia');
    APP.ajax(AJAX_URL, {
        action: 'luuNhieuDong',
        bao_gia_id: BAO_GIA_ID,
        dong: JSON.stringify(payload)
    }, {
        success: function (res) {
            APP.hideLoading('#buocGia');
            if (res && res.success) {
                xoaDauSua();
                loadBang();
                if (xong) xong(true);
            } else {
                APP.toast((res && res.message) || 'Lưu thất bại', 'error');
                if (xong) xong(false);
            }
        },
        error: function () {
            APP.hideLoading('#buocGia');
            APP.toast('Không lưu được, hãy thử lại', 'error');
            if (xong) xong(false);
        }
    });
}

/** Đọc giá trị đang gõ trên bảng vào mảng DONG (chưa gửi server) */
function docBangVaoDONG() {
    $('#bangM1 tbody tr[data-i]').each(function () {
        var i = parseInt($(this).data('i'), 10);
        if (isNaN(i) || !DONG[i]) return;
        DONG[i].thong_so_chao_gia = $(this).find('.f-tsc').val() || '';
        DONG[i].diem_khong_dat    = $(this).find('.f-dkd').val() || '';
    });
    $('#bangM2 tbody tr[data-i]').each(function () {
        var i = parseInt($(this).data('i'), 10);
        if (isNaN(i) || !DONG[i]) return;
        DONG[i].ten_thuong_mai = $(this).find('.f-ttm').val() || '';
        DONG[i].model          = $(this).find('.f-model').val() || '';
        DONG[i].hang_san_xuat  = $(this).find('.f-hsx').val() || '';
        DONG[i].xuat_xu        = $(this).find('.f-xx').val() || '';
        DONG[i].quy_cach       = $(this).find('.f-qc').val() || '';
        DONG[i].don_gia        = parseSo($(this).find('.f-gia').val());
    });
}

/** Bước 2: lưu tất cả rồi sang bước 3 */
function luuVaTiepTuc() {
    docBangVaoDONG();
    var soDapUng = 0;
    for (var i = 0; i < DONG.length; i++) {
        if ((DONG[i].thong_so_chao_gia || '').trim() !== '') soDapUng++;
    }
    if (soDapUng === 0) {
        APP.toast('Cần điền ít nhất 1 dòng ở Bảng đáp ứng kỹ thuật', 'warning');
        return;
    }
    luuTatCa(function (ok) {
        if (!ok) return;
        APP.toast('Đã lưu ' + soDapUng + '/' + DONG.length + ' dòng đáp ứng kỹ thuật', 'success');
        diToiBuoc(3);
    });
}

/** Bước 3: lưu tất cả rồi nộp báo giá */
function luuVaNop() {
    docBangVaoDONG();
    var soChao = 0;
    for (var i = 0; i < DONG.length; i++) {
        if (Number(DONG[i].don_gia) > 0) soChao++;
    }
    if (soChao === 0) {
        APP.toast('Cần chào giá ít nhất 1 hàng hóa trước khi nộp', 'warning');
        return;
    }
    luuTatCa(function (ok) { if (ok) nopBaoGia(); });
}

function loadBang() {
    if (!BAO_GIA_ID) return;
    APP.showLoading('#bangWrapM1');
    $('#bangBodyM1').html(APP.skeletonRows(6, 6));
    $('#bangBodyM2').html(APP.skeletonRows(6, 12));

    APP.ajax(AJAX_URL, { action: 'getBangChaoGia', bao_gia_id: BAO_GIA_ID }, {
        success: function (res) {
            DONG = res.data.dong || [];
            renderBang();
            // Đồng bộ nút dưới + tab với TAB_HIEN ngay khi có dữ liệu
            chuyenTab(TAB_HIEN);
        },
        complete: function () { APP.hideLoading('#bangWrapM1'); }
    });
}

/** Lọc theo từ khóa tìm kiếm — dùng chung cho cả 2 bảng */
function locDong(r) {
    var kw = ($('#searchHang').val() || '').toLowerCase();
    if (!kw) return true;
    var hay = (r.ten_hang_hoa + ' ' + (r.thong_so_ky_thuat || '') + ' ' + (r.ma_hh || '')).toLowerCase();
    return hay.indexOf(kw) >= 0;
}

function renderBang() {
    renderM1();
    renderM2();
    // Vẽ lại bảng sinh ra ô nhập mới -> phải khóa lại nếu đã chốt hoàn thành
    if (DA_HOAN_THANH) {
        $('#buocGia input, #buocGia textarea, #buocGia select').prop('disabled', true);
        $('#bangM1, #bangM2').addClass('is-locked');
    }
}

/** ===== MẪU 1: Bảng đáp ứng kỹ thuật ===== */
function renderM1() {
    var html = '', hien = 0;

    for (var i = 0; i < DONG.length; i++) {
        var r = DONG[i];
        if (!locDong(r)) continue;
        hien++;

        var daDien = (r.thong_so_chao_gia || '').trim() !== '';
        html += '<tr data-hh="' + r.hang_hoa_id + '" data-i="' + i + '"' +
                (daDien ? ' class="row-done"' : '') + '>' +
            '<td class="col-id"><span class="text-mono">' + APP.escape(r.ma_hh || '—') + '</span></td>' +
            '<td class="sticky-col"><span class="cell-main">' + APP.escape(r.ten_hang_hoa) + '</span></td>' +
            '<td><div class="spec-box">' + APP.escape(r.thong_so_ky_thuat || '—') + '</div></td>' +
            '<td><textarea class="form-control input-inline f-tsc" rows="6" ' +
                'placeholder="Nêu thông số kỹ thuật của hàng hóa chào" ' +
                'aria-label="Yêu cầu kỹ thuật chào giá">' + APP.escape(r.thong_so_chao_gia || '') + '</textarea></td>' +
            '<td><textarea class="form-control input-inline f-dkd" rows="6" ' +
                'placeholder="Nêu rõ điểm không đạt (nếu có) kèm thuyết minh" ' +
                'aria-label="Các điểm không đạt">' + APP.escape(r.diem_khong_dat || '') + '</textarea></td>' +
            '</tr>';
    }

    if (!hien) {
        html = APP.emptyRow(6, $('#searchHang').val() ? 'Không tìm thấy hàng hóa khớp từ khóa' : 'Gói thầu chưa có hàng hóa');
    }
    $('#bangBodyM1').html(html);
}

/** ===== MẪU 2: Bảng chào giá ===== */
function renderM2() {
    var html = '', hien = 0;

    for (var i = 0; i < DONG.length; i++) {
        var r = DONG[i];
        if (!locDong(r)) continue;
        hien++;

        var coGia = Number(r.don_gia) > 0;
        html += '<tr data-hh="' + r.hang_hoa_id + '" data-i="' + i + '"' +
                (coGia ? ' class="row-done"' : '') + '>' +
            '<td class="col-id"><span class="text-mono">' + APP.escape(r.ma_hh || '—') + '</span></td>' +
            '<td class="sticky-col"><span class="cell-main">' + APP.escape(r.ten_hang_hoa) + '</span></td>' +
            '<td><input type="text" class="form-control input-inline f-ttm" value="' + APP.escape(r.ten_thuong_mai || '') + '" aria-label="Tên thương mại"></td>' +
            '<td><input type="text" class="form-control input-inline f-model" value="' + APP.escape(r.model || '') + '" aria-label="Model"></td>' +
            '<td><input type="text" class="form-control input-inline f-hsx" value="' + APP.escape(r.hang_san_xuat || '') + '" aria-label="Hãng sản xuất"></td>' +
            '<td><input type="text" class="form-control input-inline f-xx" value="' + APP.escape(r.xuat_xu || '') + '" aria-label="Xuất xứ"></td>' +
            '<td class="col-qty">' + Number(r.so_luong || 0).toLocaleString('vi-VN') + '</td>' +
            '<td><input type="text" class="form-control input-inline f-qc" value="' + APP.escape(r.quy_cach || '') + '" aria-label="Quy cách"></td>' +
            '<td>' + APP.escape(r.dvt || '—') + '</td>' +
            '<td class="col-price"><input type="text" class="form-control input-inline text-right f-gia" value="' + (coGia ? Number(r.don_gia) : '') + '" aria-label="Đơn giá"></td>' +
            '<td class="cell-total f-tt">' + (coGia ? money(r.thanh_tien) : '—') + '</td>' +
            '<td class="col-actions"><span class="row-actions">' +
                '<button type="button" class="btn btn-sm btn-outline-secondary" onclick="openDong(' + i + ')" title="Nhập đầy đủ các cột">' + APP.icon('pencil', 15) + '</button>' +
            '</span></td>' +
            '</tr>';
    }

    if (!hien) {
        html = APP.emptyRow(12, $('#searchHang').val() ? 'Không tìm thấy hàng hóa khớp từ khóa' : 'Gói thầu chưa có hàng hóa');
    }
    $('#bangBodyM2').html(html);
}

/** Tính lại tổng + tiến độ của CẢ 2 mẫu */
function capNhatTong() {
    var tong = 0, soChao = 0, soDapUng = 0;
    for (var i = 0; i < DONG.length; i++) {
        if (Number(DONG[i].don_gia) > 0) { soChao++; tong += Number(DONG[i].thanh_tien) || 0; }
        if ((DONG[i].thong_so_chao_gia || '').trim() !== '') soDapUng++;
    }

    $('#tongTien').text(money(tong));
    $('#demM1').text(soDapUng + '/' + DONG.length);
    $('#demM2').text(soChao + '/' + DONG.length);

    $('#badgeTienDo').text('Đã chào ' + soChao + '/' + DONG.length + ' hàng hóa');
    $('#badgeTienDo').attr('class', 'badge ' + (soChao === 0 ? 'badge-warning'
        : (soChao === DONG.length ? 'badge-success' : 'badge-info')));

    // Ghi chú + nút phụ thuộc bước đang xem
    var note = '';
    if (TAB_HIEN === 1) {
        // Bước 2: chỉ cần 1 dòng là qua được bước 3
        if (soDapUng === 0) note = 'Cần điền và lưu ít nhất 1 dòng để sang bước 3.';
        else if (soDapUng < DONG.length) note = 'Đã điền ' + soDapUng + '/' + DONG.length + ' dòng. Có thể sang bước 3.';
        else note = 'Đã điền đủ đáp ứng kỹ thuật.';
        $('#btnTiepTuc').prop('disabled', DA_HOAN_THANH || soDapUng === 0);
    } else {
        // Bước 3: phải chào giá ít nhất 1 mặt hàng mới nộp được
        if (soChao === 0) note = 'Cần chào giá ít nhất 1 hàng hóa trước khi nộp.';
        else if (soChao < DONG.length) note = 'Còn ' + (DONG.length - soChao) + ' hàng hóa chưa chào giá.';
        $('#btnNop').prop('disabled', DA_HOAN_THANH || soChao === 0);
    }
    $('#tbNote').text(note);

    $('#step4').toggleClass('is-active', soChao > 0);
}

/** Lưu nhanh 1 dòng từ các input trên bảng */
/**
 * Gói dữ liệu 1 dòng để gửi lên server.
 * Luôn gửi ĐỦ mọi trường (lấy từ bộ đệm DONG nếu không có trên bảng đang mở),
 * vì server ghi đè cả dòng — thiếu trường nào là mất dữ liệu trường đó.
 */
function goiDuLieuDong(r) {
    return {
        action: 'luuDong',
        bao_gia_id: BAO_GIA_ID,
        hang_hoa_id: r.hang_hoa_id,
        // Mẫu 1
        thong_so_chao_gia: r.thong_so_chao_gia || '',
        diem_khong_dat: r.diem_khong_dat || '',
        // Mẫu 2
        ten_thuong_mai: r.ten_thuong_mai || '',
        model: r.model || '',
        hang_san_xuat: r.hang_san_xuat || '',
        xuat_xu: r.xuat_xu || '',
        quy_cach: r.quy_cach || '',
        don_gia: Number(r.don_gia) || 0,
        don_gia_trung_thau: Number(r.don_gia_trung_thau) || 0,
        tai_lieu_tham_chieu: r.tai_lieu_tham_chieu || ''
    };
}



/* ============ Modal nhập đầy đủ 1 dòng ============ */
function openDong(i) {
    var r = DONG[i];
    if (!r) return;
    $('#dongTitle').text('Chào giá: ' + r.ten_hang_hoa);
    $('#d_hang_hoa_id').val(r.hang_hoa_id);

    var yc = '<div class="alert alert-info" style="margin-bottom:16px"><div>' +
        '<strong>Yêu cầu của bên mời:</strong><br>' +
        'Mã HH: <strong>' + APP.escape(r.ma_hh || '—') + '</strong> · ' +
        'Số lượng: <strong>' + Number(r.so_luong).toLocaleString('vi-VN') + ' ' + APP.escape(r.dvt || '') + '</strong>';
    if (r.thong_so_ky_thuat) {
        yc += '<div class="spec-box" style="margin-top:8px">' + APP.escape(r.thong_so_ky_thuat) + '</div>';
    }
    yc += '</div></div>';
    $('#d_yeuCau').html(yc);

    $('#d_ten_thuong_mai').val(r.ten_thuong_mai || '');
    $('#d_model').val(r.model || '');
    $('#d_hang_san_xuat').val(r.hang_san_xuat || '');
    $('#d_xuat_xu').val(r.xuat_xu || '');
    $('#d_quy_cach').val(r.quy_cach || '');
    $('#d_don_gia').val(Number(r.don_gia) || '');
    $('#d_don_gia_trung_thau').val(Number(r.don_gia_trung_thau) || '');
    $('#d_tai_lieu_tham_chieu').val(r.tai_lieu_tham_chieu || '');
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

    var payload = {
        action: 'luuDong',
        bao_gia_id: BAO_GIA_ID,
        hang_hoa_id: $('#d_hang_hoa_id').val(),
        // Mẫu 1
        thong_so_chao_gia: $('#d_thong_so_chao_gia').val(),
        diem_khong_dat: $('#d_diem_khong_dat').val(),
        // Mẫu 2
        ten_thuong_mai: $('#d_ten_thuong_mai').val(),
        model: $('#d_model').val(),
        hang_san_xuat: $('#d_hang_san_xuat').val(),
        xuat_xu: $('#d_xuat_xu').val(),
        quy_cach: $('#d_quy_cach').val(),
        don_gia: gia,
        don_gia_trung_thau: parseSo($('#d_don_gia_trung_thau').val()),
        tai_lieu_tham_chieu: $('#d_tai_lieu_tham_chieu').val()
    };

    APP.ajax(AJAX_URL, payload, {
        success: function () {
            APP.toast('Đã lưu', 'success');
            closeDong();
            loadBang();
        }
    });
    return false;
}

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
                // Nộp xong -> sang Bước 4 để tải file Word về ký rồi upload.
                // (Trước đây mở thẳng trang tra cứu MST — không còn hợp với luồng 5 bước.)
                setTimeout(function () { diToiBuoc(4); }, 600);
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
$('#dongModal, #importModal, #banKyModal').on('click', function (e) { if (e.target === this) $(this).removeClass('open'); });
$('#hdModal').on('click', function (e) { if (e.target === this) dongHuongDan(); });
$(document).on('keydown', function (e) {
    if (e.key !== 'Escape') return;
    // Esc: đóng modal con trước, hết modal mới đóng trang tra cứu
    if ($('#hdModal').hasClass('open')) { dongHuongDan(); return; }
    if ($('#banKyModal').hasClass('open')) { closeBanKy(); return; }
    if ($('#dongModal').hasClass('open') || $('#importModal').hasClass('open')) {
        closeDong(); closeImport(); return;
    }
    if ($('#traCuuOverlay').hasClass('open')) dongTraCuu();
});

/* Enter trong ô đơn giá → nhảy xuống ô đơn giá dòng dưới cho nhập nhanh.
   (Không lưu ngay nữa — giờ lưu 1 lần bằng nút dưới bảng) */
$(document).on('keydown', '.f-gia', function (e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        var o = $('#bangM2 .f-gia');
        var i = o.index(this);
        if (i > -1 && i + 1 < o.length) o.eq(i + 1).trigger('focus').trigger('select');
    }
});

$(document).ready(function () {
    if (BAO_GIA_ID) loadBang();
    tuHienHuongDan();
});
</script>
</body>
</html>
