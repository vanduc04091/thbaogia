<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../BUS/DM_NguoiDung_BUS.php';
require_once __DIR__ . '/../../DAL/BG_GoiThau_DAL.php';

/**
 * Đích chuyển sau khi đăng nhập.
 *
 * Nhà thầu quét QR sẽ tới login kèm ?portal=<token>. KHÔNG nhận URL đích
 * trực tiếp từ tham số (tránh open redirect) — chỉ nhận token rồi tự dựng
 * URL nội bộ sau khi xác minh token có thật trong DB.
 */
function dichSauDangNhap(string $token): string
{
    $token = trim($token);
    // Token là hex 32 ký tự do BG_GoiThau_BUS::sinhToken() sinh
    if ($token !== '' && preg_match('/^[a-f0-9]{16,64}$/', $token)) {
        $gt = BG_GoiThau_DAL::getByToken($token);
        if ($gt) {
            return AppConfig::baseUrl('GUI/portal/index.php') . '?t=' . urlencode($token);
        }
    }
    return AppConfig::baseUrl('GUI/dashboard/index.php');
}

// Token portal: ưu tiên tham số URL, sau đó tới cái đã ghi nhớ trong phiên
$portalToken = (string)Helper::get('portal', '');
if ($portalToken === '') {
    $portalToken = (string)SessionHelper::get('portal_redirect_token', '');
}

// Nếu đã đăng nhập → chuyển tới đích phù hợp
if (SessionHelper::isLoggedIn()) {
    header('Location: ' . dichSauDangNhap($portalToken));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = Helper::post('_csrf', '');
    if (!SessionHelper::verifyCsrf($csrf)) {
        $error = 'Phiên làm việc đã hết hạn. Vui lòng thử lại.';
    } else {
        $taiKhoan = Helper::postStr('tai_khoan');
        $matKhau = (string)Helper::post('mat_khau', '');
        $result = DM_NguoiDung_BUS::login($taiKhoan, $matKhau);
        if ($result['success']) {
            // Lấy token từ form (giữ qua bước POST) hoặc từ phiên
            $tokenSauLogin = (string)Helper::post('portal', '');
            if ($tokenSauLogin === '') $tokenSauLogin = $portalToken;

            SessionHelper::login($result['data']);
            SessionHelper::remove('portal_redirect_token');
            header('Location: ' . dichSauDangNhap($tokenSauLogin));
            exit;
        }
        $error = $result['message'];
    }
}

$csrfToken = SessionHelper::csrfToken();
$laPortal = $portalToken !== '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Đăng nhập · <?= Helper::h(AppConfig::APP_NAME) ?></title>
<link rel="stylesheet" href="<?= AppConfig::baseUrl('assets/css/login.css') ?>">
</head>
<body>
<div class="login-wrap">
    <div class="login-header">
        <img class="login-logo"
             src="<?= AppConfig::baseUrl('assets/images/logo_bv.png') ?>?v=<?= AppConfig::APP_VERSION ?>"
             alt="Logo Bệnh viện Hữu nghị Đa khoa Nghệ An">
        <h1><?= $laPortal ? 'Đăng nhập chào giá' : 'Đăng nhập hệ thống' ?></h1>
        <p><?= $laPortal ? 'Dành cho nhà thầu tham gia chào giá' : Helper::h(AppConfig::APP_NAME) ?></p>
    </div>
    <div class="login-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= Helper::h($error) ?></div>
        <?php endif; ?>

        <?php if ($laPortal): ?>
            <div class="alert alert-info">
                Nhà thầu đăng nhập bằng tài khoản chung do bên mời chào giá cung cấp,
                sau đó tự khai thông tin công ty của mình.
            </div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <input type="hidden" name="_csrf" value="<?= Helper::h($csrfToken) ?>">
            <?php if ($laPortal): ?>
                <input type="hidden" name="portal" value="<?= Helper::h($portalToken) ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="tai_khoan">Tài khoản</label>
                <input type="text" id="tai_khoan" name="tai_khoan" class="form-control"
                       required autofocus placeholder="Nhập tài khoản"
                       value="<?= Helper::h(Helper::post('tai_khoan', '')) ?>">
            </div>

            <div class="form-group">
                <label for="mat_khau">Mật khẩu</label>
                <div class="input-group">
                    <input type="password" id="mat_khau" name="mat_khau" class="form-control"
                           required placeholder="Nhập mật khẩu">
                    <button type="button" class="toggle-pass" onclick="togglePass()">Hiện</button>
                </div>
            </div>

            <button type="submit" class="btn-login">Đăng nhập</button>
        </form>
    </div>
    <div class="login-footer">
        © <?= date('Y') ?> · <?= Helper::h(AppConfig::APP_NAME) ?> · v<?= AppConfig::APP_VERSION ?>
    </div>
</div>

<script>
function togglePass() {
    var el = document.getElementById('mat_khau');
    var btn = document.querySelector('.toggle-pass');
    if (el.type === 'password') { el.type = 'text'; btn.textContent = 'Ẩn'; }
    else { el.type = 'password'; btn.textContent = 'Hiện'; }
}
</script>
</body>
</html>