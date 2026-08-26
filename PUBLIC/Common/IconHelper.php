<?php
/**
 * IconHelper - Nguồn icon SVG duy nhất của hệ thống.
 *
 * QUY TẮC: mọi icon trong PHP/HTML phải gọi IconHelper::svg().
 * KHÔNG hardcode <svg> inline, KHÔNG dùng emoji, KHÔNG dùng icon font/CDN.
 * Phía JS dùng APP.icon() (assets/js/app.js) — giữ 2 bộ đồng bộ khi thêm icon mới.
 *
 * Bộ icon theo phong cách Lucide (outline, stroke 2, viewBox 24x24).
 */
class IconHelper
{
    /**
     * Thân path của từng icon. Thêm icon mới tại đây.
     * @var array<string,string>
     */
    private const PATHS = [
        // --- Navigation / layout ---
        'menu'          => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'chevron-left'  => '<path d="m15 18-6-6 6-6"/>',
        'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
        'chevron-down'  => '<path d="m6 9 6 6 6-6"/>',
        'home'          => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>',
        'log-out'       => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',

        // --- Module hệ thống ---
        'user'          => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'users'         => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'layout-list'   => '<rect x="3" y="4" width="7" height="7" rx="1"/><rect x="3" y="13" width="7" height="7" rx="1"/><path d="M14 6h7M14 11h7M14 16h7M14 21h7"/>',
        'shield-check'  => '<path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V6l8-3 8 3z"/><path d="m9 12 2 2 4-4"/>',
        'file-clock'    => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><circle cx="12" cy="15" r="3"/><path d="M12 14v1.5l1 .5"/>',

        // --- Hành động ---
        'plus'          => '<path d="M12 5v14M5 12h14"/>',
        'pencil'        => '<path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/>',
        'trash'         => '<path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/>',
        'rotate-ccw'    => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>',
        'search'        => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'save'          => '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/>',
        'key'           => '<circle cx="7.5" cy="15.5" r="4.5"/><path d="m21 2-9.6 9.6M15.5 7.5l3 3"/>',
        'filter'        => '<path d="M22 3H2l8 9.46V19l4 2v-8.54z"/>',
        'refresh'       => '<path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/>',

        // --- Trạng thái ---
        'check'         => '<path d="M20 6 9 17l-5-5"/>',
        'x'             => '<path d="M18 6 6 18M6 6l12 12"/>',
        'alert-triangle'=> '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4M12 17h.01"/>',
        'info'          => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
        'lock'          => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'inbox'         => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
        'eye'           => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
        'eye-off'       => '<path d="M9.9 4.24A9.1 9.1 0 0 1 12 4c6.4 0 10 7 10 7a17.6 17.6 0 0 1-2.2 3.2M6.6 6.6A17.8 17.8 0 0 0 2 11s3.6 7 10 7a9 9 0 0 0 5.4-1.6"/><path d="M2 2l20 20"/>',

        // --- Nghiệp vụ báo giá ---
        'clipboard-list' => '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M8 11h8M8 15h6"/>',
        'package'        => '<path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/>',
        'file-spreadsheet' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h8M12 13v4"/>',
        'file-text' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h8M8 9h2"/>',
        'qr-code'        => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM19 19h2v2h-2zM14 19h1M19 14h2"/>',
        'download'       => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5M12 15V3"/>',
        'upload'         => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5M12 3v12"/>',
        'check-circle'   => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
        'x-circle'       => '<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/>',
        'building'       => '<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 6h.01M15 6h.01M9 10h.01M15 10h.01M9 14h.01M15 14h.01"/><path d="M10 22v-4h4v4"/>',
        'calendar'       => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 11h18"/>',
        'clock'          => '<circle cx="12" cy="12" r="10"/><path d="M12 7v5l3 2"/>',
        'link'           => '<path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.8 1.8"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.8-1.8"/>',
        'copy'           => '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
        'printer'        => '<path d="M6 9V3h12v6"/><rect x="6" y="13" width="12" height="8"/><path d="M6 17H4a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2"/>',
        'bar-chart'      => '<path d="M3 3v18h18"/><rect x="7" y="12" width="3" height="6"/><rect x="12" y="8" width="3" height="10"/><rect x="17" y="4" width="3" height="14"/>',
        'send'           => '<path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/>',
        'arrow-left'  => '<path d="M19 12H5"/><path d="m12 19-7-7 7-7"/>',
        'external-link'  => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6M10 14 21 3"/>',
        'folder'         => '<path d="M4 20a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5l2 3h7a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2z"/>',
    ];

    /**
     * Icon đại diện cho từng module (key = modules_tuong_ung).
     *
     * NGUỒN DUY NHẤT: sidebar, ma trận phân quyền và mọi nơi cần icon theo module
     * đều đọc từ đây — thêm module mới chỉ khai báo 1 chỗ này.
     */
    private const MODULE_ICONS = [
        // Nghiệp vụ báo giá
        'BG_GoiThau'      => 'clipboard-list',
        'BG_HangHoa'      => 'package',
        'BG_BaoGia'       => 'file-spreadsheet',
        'BG_TongHop'      => 'bar-chart',
        'BG_QuanLyFile'   => 'folder',
        'BG_QuyenGoiThau' => 'shield-check',
        // Hệ thống
        'DM_NguoiDung'     => 'user',
        'DM_NhomTaiKhoan'  => 'users',
        'DM_DanhSachForm'  => 'layout-list',
        'DM_PhanQuyen'     => 'shield-check',
        'DM_NhatKyHeThong' => 'file-clock',
    ];

    /** Tên icon của 1 module; module lạ → icon mặc định 'layout-list' */
    public static function moduleIconName(string $moduleKey): string
    {
        return self::MODULE_ICONS[$moduleKey] ?? 'layout-list';
    }

    /** Render trực tiếp icon của 1 module */
    public static function moduleIcon(string $moduleKey, int $size = 18, string $class = ''): string
    {
        return self::svg(self::moduleIconName($moduleKey), $size, $class);
    }

    /** Map module => tên icon (dùng khi cần đẩy xuống JS) */
    public static function moduleIconMap(): array
    {
        return self::MODULE_ICONS;
    }

    /**
     * Render 1 icon SVG.
     *
     * @param string $name  Tên icon (key trong PATHS)
     * @param int    $size  Kích thước px (width = height)
     * @param string $class Class CSS thêm vào
     * @param string $color Màu stroke; mặc định currentColor (kế thừa màu chữ)
     */
    public static function svg(string $name, int $size = 18, string $class = '', string $color = 'currentColor'): string
    {
        $body = self::PATHS[$name] ?? null;
        if ($body === null) {
            // Icon không tồn tại → trả rỗng thay vì vỡ layout (và báo khi debug)
            if (AppConfig::APP_DEBUG) {
                return '<!-- IconHelper: không có icon "' . Helper::h($name) . '" -->';
            }
            return '';
        }

        $size  = max(8, min(96, $size));
        $class = trim('icon ' . $class);

        return sprintf(
            '<svg class="%s" width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="%s" '
            . 'stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%s</svg>',
            Helper::h($class),
            $size,
            $size,
            Helper::h($color),
            $body
        );
    }

    /** Icon kèm nhãn cho screen reader (dùng khi icon đứng một mình, không có text) */
    public static function svgLabeled(string $name, string $label, int $size = 18, string $class = ''): string
    {
        return self::svg($name, $size, $class) . '<span class="sr-only">' . Helper::h($label) . '</span>';
    }

    /** Danh sách tên icon hiện có — tiện khi dev cần tra cứu */
    public static function names(): array
    {
        return array_keys(self::PATHS);
    }
}
