<?php
/**
 * PhanQuyenHelper - Kiểm tra quyền truy cập theo nhóm tài khoản & form
 */
class PhanQuyenHelper
{
    const QUYEN_XEM = 'quyen_xem';
    const QUYEN_THEM = 'quyen_them';
    const QUYEN_SUA = 'quyen_sua';
    const QUYEN_XOA = 'quyen_xoa';

    /**
     * Lấy ma trận quyền của 1 nhóm tài khoản.
     * Return: [ modules_tuong_ung => ['quyen_xem'=>1,...] ]
     */
    public static function getMatrixByNhom(int $nhomTaiKhoanId): array
    {
        if ($nhomTaiKhoanId <= 0) return [];
        $key = "phan_quyen:nhom:{$nhomTaiKhoanId}";
        $cached = MemcachedHelper::get($key);
        if ($cached !== null) return $cached;

        $sql = "SELECT f.modules_tuong_ung, pq.quyen_xem, pq.quyen_them, pq.quyen_sua, pq.quyen_xoa
                FROM dm_phan_quyen pq
                INNER JOIN dm_danh_sach_form f ON f.id = pq.form_id
                WHERE pq.nhom_tai_khoan_id = :id AND f.da_xoa = 0";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([':id' => $nhomTaiKhoanId]);
        $rows = $stmt->fetchAll();

        $matrix = [];
        foreach ($rows as $r) {
            $matrix[$r['modules_tuong_ung']] = [
                self::QUYEN_XEM => (int)$r['quyen_xem'],
                self::QUYEN_THEM => (int)$r['quyen_them'],
                self::QUYEN_SUA => (int)$r['quyen_sua'],
                self::QUYEN_XOA => (int)$r['quyen_xoa'],
            ];
        }
        MemcachedHelper::set($key, $matrix, Constants::CACHE_TTL_PHAN_QUYEN);
        return $matrix;
    }

    /**
     * Kiểm tra nhóm có cờ la_admin (full quyền) không. Có cache.
     */
    public static function isAdminNhom(int $nhomTaiKhoanId): bool
    {
        if ($nhomTaiKhoanId <= 0) return false;
        $key = "phan_quyen:admin:{$nhomTaiKhoanId}";
        $cached = MemcachedHelper::get($key);
        if ($cached !== null) return (bool)$cached;

        $sql = "SELECT la_admin FROM dm_nhom_tai_khoan WHERE id = :id AND da_xoa = 0";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([':id' => $nhomTaiKhoanId]);
        $isAdmin = (int)($stmt->fetchColumn() ?: 0) === 1;
        MemcachedHelper::set($key, $isAdmin ? 1 : 0, Constants::CACHE_TTL_PHAN_QUYEN);
        return $isAdmin;
    }

    public static function hasQuyen(string $moduleKey, string $quyen): bool
    {
        $nhomId = SessionHelper::nhomTaiKhoanId();
        if (self::isAdminNhom($nhomId)) return true;
        $matrix = self::getMatrixByNhom($nhomId);
        return !empty($matrix[$moduleKey][$quyen]);
    }

    /** Dùng cho ajax_handler — trả JSON 403 */
    public static function requireQuyen(string $moduleKey, string $quyen): void
    {
        if (!self::hasQuyen($moduleKey, $quyen)) {
            ResponseHelper::error('Bạn không có quyền thực hiện thao tác này', 403);
        }
    }

    /**
     * Dùng cho trang GUI — hiện trang 403 rồi dừng.
     * Gọi TRƯỚC khi render header để không vỡ layout.
     */
    public static function requireQuyenView(string $moduleKey): void
    {
        if (self::hasQuyen($moduleKey, self::QUYEN_XEM)) return;

        http_response_code(403);
        $home = AppConfig::baseUrl('GUI/dashboard/index.php');
        $css  = AppConfig::baseUrl('assets/css/style.css');
        echo '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8">'
           . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
           . '<title>Không có quyền truy cập</title>'
           . '<link rel="stylesheet" href="' . Helper::h($css) . '"></head><body>'
           . '<div style="max-width:460px;margin:12vh auto;padding:32px;text-align:center;'
           . 'background:#fff;border:1px solid #e2e8f0;border-radius:14px">'
           . '<div style="color:#dc2626;margin-bottom:12px">' . IconHelper::svg('lock', 40) . '</div>'
           . '<h1 style="font-size:18px;margin-bottom:8px">Không có quyền truy cập</h1>'
           . '<p style="color:#64748b;font-size:14px;margin:0 0 20px">'
           . 'Bạn không được cấp quyền xem chức năng này. Liên hệ quản trị viên nếu cần.</p>'
           . '<a class="btn btn-primary" href="' . Helper::h($home) . '">Về trang chủ</a>'
           . '</div></body></html>';
        exit;
    }

    public static function clearCache(int $nhomTaiKhoanId = 0): void
    {
        if ($nhomTaiKhoanId > 0) {
            MemcachedHelper::delete("phan_quyen:nhom:{$nhomTaiKhoanId}");
        } else {
            MemcachedHelper::deleteByPrefix('phan_quyen:');
        }
    }
}