<?php
/**
 * BG_Catalog_DAL — Bước 5: chỉ dẫn vị trí tài liệu (trang catalog chứng minh).
 *
 * Mỗi dòng = 1 hàng hóa trong 1 báo giá + số trang catalog chứng minh.
 * "Tên hàng thương mại" hiển thị ở bảng lấy từ bg_bao_gia_chi_tiet.ten_thuong_mai
 * (Mẫu 2) — không lưu trùng ở đây.
 */
require_once __DIR__ . '/database.php';

class BG_Catalog_DAL
{
    /** Lấy map [hang_hoa_id => trang_catalog] của 1 báo giá */
    public static function getMap(int $baoGiaId): array
    {
        $sql = "SELECT hang_hoa_id, trang_catalog
                FROM bg_catalog
                WHERE bao_gia_id = :bg AND da_xoa = 0";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([':bg' => $baoGiaId]);

        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $out[(int)$r['hang_hoa_id']] = (string)($r['trang_catalog'] ?? '');
        }
        return $out;
    }

    /** Thêm mới hoặc cập nhật 1 dòng */
    public static function upsert(int $baoGiaId, int $hangHoaId, ?string $trang, int $u): void
    {
        $sql = "INSERT INTO bg_catalog
                    (bao_gia_id, hang_hoa_id, trang_catalog,
                     ngay_tao, ngay_cap_nhat, nguoi_tao, nguoi_cap_nhat, da_xoa)
                VALUES (:bg, :hh, :tr, NOW(), NOW(), :nt, :ncn, 0)
                ON DUPLICATE KEY UPDATE
                    trang_catalog = VALUES(trang_catalog),
                    ngay_cap_nhat = NOW(),
                    nguoi_cap_nhat = VALUES(nguoi_cap_nhat),
                    da_xoa = 0";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([
            ':bg'  => $baoGiaId,
            ':hh'  => $hangHoaId,
            ':tr'  => $trang,
            ':nt'  => $u,
            ':ncn' => $u,
        ]);
    }

    /** Đếm số dòng đã điền trang catalog */
    public static function demDaDien(int $baoGiaId): int
    {
        $sql = "SELECT COUNT(*) FROM bg_catalog
                WHERE bao_gia_id = :bg AND da_xoa = 0
                  AND trang_catalog IS NOT NULL AND trang_catalog <> ''";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([':bg' => $baoGiaId]);
        return (int)$stmt->fetchColumn();
    }
}
