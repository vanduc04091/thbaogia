<?php
require_once __DIR__ . '/database.php';

/**
 * DM_DangNhapThatBai_DAL — Đếm số lần đăng nhập sai, lưu ở DB.
 *
 * VÌ SAO KHÔNG DÙNG SESSION: bộ đếm trong session bị reset khi xóa cookie,
 * mà bot dò mật khẩu luôn gửi request không kèm cookie → chặn được số 0.
 * Lưu theo IP + tài khoản ở DB thì xóa cookie cũng không thoát.
 */
class DM_DangNhapThatBai_DAL
{
    const TABLE = 'dm_dang_nhap_that_bai';

    /** Khóa đếm: gộp IP + tài khoản, băm để không vượt độ dài index */
    public static function taoKhoa(string $taiKhoan, string $ip): string
    {
        return sha1(mb_strtolower(trim($taiKhoan)) . '|' . $ip);
    }

    /**
     * Trả ['so_lan' => int, 'lan_cuoi_ts' => int].
     * Bản ghi quá cũ (ngoài cửa sổ khóa) coi như chưa từng sai.
     */
    public static function layTrangThai(string $khoa, int $cuaSoGiay): array
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT so_lan, UNIX_TIMESTAMP(lan_cuoi) AS lan_cuoi_ts
             FROM " . self::TABLE . " WHERE khoa = :k"
        );
        $stmt->execute([':k' => $khoa]);
        $r = $stmt->fetch();

        if (!$r) return ['so_lan' => 0, 'lan_cuoi_ts' => 0];

        // Hết cửa sổ khóa → bộ đếm không còn giá trị
        if ((time() - (int)$r['lan_cuoi_ts']) >= $cuaSoGiay) {
            return ['so_lan' => 0, 'lan_cuoi_ts' => 0];
        }
        return ['so_lan' => (int)$r['so_lan'], 'lan_cuoi_ts' => (int)$r['lan_cuoi_ts']];
    }

    /**
     * Ghi nhận 1 lần sai. Dùng ON DUPLICATE KEY để không cần SELECT trước
     * (tránh race condition khi bot bắn nhiều request song song).
     *
     * @param int $cuaSoGiay Quá cửa sổ này thì đếm lại từ 1
     */
    public static function ghiNhanSai(string $khoa, string $ip, string $taiKhoan, int $cuaSoGiay): void
    {
        $stmt = Database::getConnection()->prepare(
            "INSERT INTO " . self::TABLE . " (khoa, ip_address, tai_khoan, so_lan, lan_dau, lan_cuoi)
             VALUES (:k, :ip, :tk, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                 so_lan = IF(TIMESTAMPDIFF(SECOND, lan_cuoi, NOW()) >= :cs, 1, so_lan + 1),
                 lan_dau = IF(TIMESTAMPDIFF(SECOND, lan_cuoi, NOW()) >= :cs2, NOW(), lan_dau),
                 lan_cuoi = NOW()"
        );
        // KHÔNG reuse named placeholder (EMULATE_PREPARES = false) → :cs và :cs2
        $stmt->execute([
            ':k'   => $khoa,
            ':ip'  => $ip,
            ':tk'  => mb_substr($taiKhoan, 0, 100),
            ':cs'  => $cuaSoGiay,
            ':cs2' => $cuaSoGiay,
        ]);
    }

    /** Đăng nhập đúng → xóa bộ đếm */
    public static function xoa(string $khoa): void
    {
        $stmt = Database::getConnection()->prepare("DELETE FROM " . self::TABLE . " WHERE khoa = :k");
        $stmt->execute([':k' => $khoa]);
    }

    /** Dọn bản ghi cũ (gọi từ cron_cleanup.php) */
    public static function donCu(int $giuLaiGiay): int
    {
        $stmt = Database::getConnection()->prepare(
            "DELETE FROM " . self::TABLE . " WHERE lan_cuoi < DATE_SUB(NOW(), INTERVAL :s SECOND)"
        );
        $stmt->execute([':s' => $giuLaiGiay]);
        return $stmt->rowCount();
    }
}
