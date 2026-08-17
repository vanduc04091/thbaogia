<?php
/**
 * SessionHelper - Quản lý session & đăng nhập
 */
class SessionHelper
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Không cho phép truyền session id qua URL (chống session fixation)
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');

            $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                  || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

            session_name(AppConfig::SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => AppConfig::SESSION_LIFETIME,
                'path' => '/',
                'secure' => $https,   // chỉ gửi cookie qua HTTPS khi có HTTPS
                'httponly' => true,   // JS không đọc được cookie phiên
                'samesite' => 'Lax',  // chống CSRF cross-site cơ bản
            ]);
            session_start();

            // Hết hạn phiên theo thời gian không hoạt động
            $now = time();
            if (!empty($_SESSION['last_activity'])
                && ($now - (int)$_SESSION['last_activity']) > AppConfig::SESSION_LIFETIME) {
                $_SESSION = [];
                session_regenerate_id(true);
            }
            $_SESSION['last_activity'] = $now;
        }
    }

    // === Chống brute force đăng nhập ===

    /** Trả ['count' => int, 'time' => int] của 1 khóa throttle */
    public static function getLoginFail(string $key): array
    {
        self::start();
        $d = $_SESSION['_login_fail'][$key] ?? null;
        return is_array($d) ? $d : ['count' => 0, 'time' => 0];
    }

    public static function addLoginFail(string $key): void
    {
        self::start();
        $d = self::getLoginFail($key);
        $_SESSION['_login_fail'][$key] = ['count' => $d['count'] + 1, 'time' => time()];
    }

    public static function clearLoginFail(string $key): void
    {
        self::start();
        unset($_SESSION['_login_fail'][$key]);
    }

    public static function set(string $key, $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, $default = null)
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    // === Auth ===
    public static function login(array $userInfo): void
    {
        self::start();
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$userInfo['id'];
        $_SESSION['tai_khoan'] = $userInfo['tai_khoan'] ?? '';
        $_SESSION['ho_ten'] = $userInfo['ho_ten'] ?? ($userInfo['tai_khoan'] ?? '');
        $_SESSION['nhom_tai_khoan_id'] = (int)($userInfo['nhom_tai_khoan_id'] ?? 0);
        $_SESSION['ten_nhom'] = $userInfo['ten_nhom'] ?? '';
        $_SESSION['login_time'] = time();
    }

    public static function isLoggedIn(): bool
    {
        self::start();
        return !empty($_SESSION['user_id']);
    }

    public static function userId(): int
    {
        return (int)self::get('user_id', 0);
    }

    public static function nhomTaiKhoanId(): int
    {
        return (int)self::get('nhom_tai_khoan_id', 0);
    }

    public static function taiKhoan(): string
    {
        return (string)self::get('tai_khoan', '');
    }

    public static function hoTen(): string
    {
        return (string)self::get('ho_ten', '');
    }

    // === CSRF ===
    public static function csrfToken(): string
    {
        self::start();
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function verifyCsrf(?string $token): bool
    {
        self::start();
        return !empty($token) && !empty($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $token);
    }

    // === Flash ===
    public static function flash(string $key, $value = null)
    {
        self::start();
        if ($value === null) {
            $v = $_SESSION['_flash'][$key] ?? null;
            unset($_SESSION['_flash'][$key]);
            return $v;
        }
        $_SESSION['_flash'][$key] = $value;
        return null;
    }
}