<?php
/**
 * Helper - Hàm tiện ích dùng chung
 */
class Helper
{
    /**
     * Sanitize input string
     */
    public static function sanitize(?string $value): string
    {
        if ($value === null) return '';
        return trim(strip_tags($value));
    }

    /**
     * Escape output HTML
     */
    public static function h($value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Format datetime dd/mm/Y H:i
     */
    public static function formatDateTime(?string $datetime, string $format = 'd/m/Y H:i'): string
    {
        if (empty($datetime) || $datetime === '0000-00-00 00:00:00') return '';
        $ts = strtotime($datetime);
        return $ts ? date($format, $ts) : '';
    }

    /**
     * Format date dd/mm/Y
     */
    public static function formatDate(?string $date, string $format = 'd/m/Y'): string
    {
        return self::formatDateTime($date, $format);
    }

    /**
     * Chuẩn hóa giá trị từ <input type="datetime-local"> về DATETIME của MySQL.
     *
     * Browser gửi "2026-08-20T09:30" (có chữ T, thiếu giây) → MySQL cần
     * "2026-08-20 09:30:00". Trả '' nếu rỗng/không đúng dạng để DAL lưu NULL.
     */
    public static function chuanHoaDateTime(?string $value): string
    {
        $v = trim((string)($value ?? ''));
        if ($v === '') return '';

        $v = str_replace('T', ' ', $v);
        // "Y-m-d H:i" → thêm giây
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $v)) {
            $v .= ':00';
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $v)) {
            return '';
        }
        // Chặn ngày/giờ không tồn tại (VD 2026-02-31 hoặc 25:00)
        $ts = strtotime($v);
        return ($ts && date('Y-m-d H:i:s', $ts) === $v) ? $v : '';
    }

    /** DATETIME của MySQL → giá trị cho <input type="datetime-local"> */
    public static function dateTimeLocal(?string $value): string
    {
        $v = trim((string)($value ?? ''));
        if ($v === '' || strpos($v, '0000-00-00') === 0) return '';
        $ts = strtotime($v);
        return $ts ? date('Y-m-d\TH:i', $ts) : '';
    }

    /**
     * Validate email
     */
    public static function isEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate phone (VN)
     */
    public static function isPhone(string $phone): bool
    {
        return preg_match('/^[0-9+\-\s()]{8,20}$/', $phone) === 1;
    }

    /**
     * Bỏ dấu tiếng Việt → chữ Latin không dấu.
     *
     * Tự viết bảng thay thế thay vì dùng iconv('ASCII//TRANSLIT') vì hàm đó
     * cho kết quả khác nhau tùy hệ điều hành (Windows trả '?' cho chữ có dấu).
     */
    public static function boDau(string $str): string
    {
        $map = [
            'a' => 'áàảãạăắằẳẵặâấầẩẫậ',
            'e' => 'éèẻẽẹêếềểễệ',
            'i' => 'íìỉĩị',
            'o' => 'óòỏõọôốồổỗộơớờởỡợ',
            'u' => 'úùủũụưứừửữự',
            'y' => 'ýỳỷỹỵ',
            'd' => 'đ',
            'A' => 'ÁÀẢÃẠĂẮẰẲẴẶÂẤẦẨẪẬ',
            'E' => 'ÉÈẺẼẸÊẾỀỂỄỆ',
            'I' => 'ÍÌỈĨỊ',
            'O' => 'ÓÒỎÕỌÔỐỒỔỖỘƠỚỜỞỠỢ',
            'U' => 'ÚÙỦŨỤƯỨỪỬỮỰ',
            'Y' => 'ÝỲỶỸỴ',
            'D' => 'Đ',
        ];
        foreach ($map as $khongDau => $coDau) {
            // preg_split với //u để tách đúng ký tự UTF-8 nhiều byte
            $chars = preg_split('//u', $coDau, -1, PREG_SPLIT_NO_EMPTY);
            $str = str_replace($chars, $khongDau, $str);
        }
        return $str;
    }

    /**
     * Chuyển chuỗi thành slug an toàn cho TÊN FILE.
     *
     * "Mua vật tư tiêu hao PT cột sống 2026" → "mua-vat-tu-tieu-hao-pt-cot-song-2026"
     *
     * Chỉ giữ [a-z0-9-] nên an toàn tuyệt đối với đường dẫn: không có dấu chấm,
     * dấu gạch chéo, khoảng trắng hay '..' để lợi dụng path traversal.
     *
     * @param int $maxLen Cắt bớt cho tên file không quá dài (0 = không giới hạn)
     */
    public static function slug(string $str, int $maxLen = 60): string
    {
        $str = self::boDau(trim($str));
        $str = mb_strtolower($str, 'UTF-8');
        // Mọi thứ không phải chữ/số → gạch ngang
        $str = preg_replace('/[^a-z0-9]+/', '-', $str);
        $str = trim((string)$str, '-');

        if ($maxLen > 0 && strlen($str) > $maxLen) {
            $str = substr($str, 0, $maxLen);
            // Không cắt giữa chừng một từ
            $viTri = strrpos($str, '-');
            if ($viTri !== false && $viTri > $maxLen * 0.6) {
                $str = substr($str, 0, $viTri);
            }
            $str = trim($str, '-');
        }
        return $str;
    }

    /**
     * Sinh chuỗi ngẫu nhiên
     */
    public static function randomString(int $length = 16): string
    {
        return bin2hex(random_bytes((int)($length / 2)));
    }

    /**
     * Lấy IP client
     */
    public static function getClientIp(): string
    {
        foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = explode(',', $_SERVER[$key])[0];
                return trim($ip);
            }
        }
        return '0.0.0.0';
    }

    /**
     * Get POST / GET an toàn
     */
    public static function post(string $key, $default = '')
    {
        return $_POST[$key] ?? $default;
    }

    public static function get(string $key, $default = '')
    {
        return $_GET[$key] ?? $default;
    }

    public static function postInt(string $key, int $default = 0): int
    {
        return isset($_POST[$key]) ? (int)$_POST[$key] : $default;
    }

    public static function postStr(string $key, string $default = ''): string
    {
        return isset($_POST[$key]) ? self::sanitize((string)$_POST[$key]) : $default;
    }

    /**
     * Trả về trạng thái badge HTML
     */
    public static function badgeTrangThai(int $trangThai): string
    {
        return $trangThai == 1
            ? '<span class="badge badge-success">Hoạt động</span>'
            : '<span class="badge badge-danger">Ngừng</span>';
    }

    /**
     * Require login (redirect nếu chưa đăng nhập)
     */
    public static function requireLogin(): void
    {
        if (!SessionHelper::isLoggedIn()) {
            header('Location: ' . AppConfig::baseUrl('GUI/auth/login.php'));
            exit;
        }
    }

    /**
     * Require AJAX login
     */
    public static function requireAjaxLogin(): void
    {
        if (!SessionHelper::isLoggedIn()) {
            ResponseHelper::error('Phiên đăng nhập đã hết hạn', 401);
        }
    }

    /**
     * Require AJAX login + verify CSRF token.
     * Token được đọc từ header X-CSRF-Token (mặc định trong APP.ajax) hoặc POST _csrf_token.
     */
    /**
     * Dùng 403 (chuẩn HTTP) thay vì 419 — 419 là mã phi chuẩn, Apache/proxy
     * biến nó thành 500 và làm mất body JSON nên client không đọc được message.
     * Kèm cờ `csrf_expired` để JS phân biệt hết hạn phiên với lỗi thiếu quyền.
     */
    public static function requireAjaxCsrf(): void
    {
        self::requireAjaxLogin();
        $token = $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? ($_POST[AppConfig::CSRF_TOKEN_NAME] ?? ($_POST['_csrf'] ?? ''));
        if (!SessionHelper::verifyCsrf((string)$token)) {
            ResponseHelper::error(
                'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.',
                403,
                ['csrf_expired' => true]
            );
        }
    }
}