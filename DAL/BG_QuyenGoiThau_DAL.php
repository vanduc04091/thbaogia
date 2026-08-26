<?php
/**
 * BG_QuyenGoiThau_DAL — Ai được xem gói thầu nào.
 *
 * Gói CHƯA gán ai: chỉ nhóm admin (`la_admin = 1`) và nhóm MANAGER thấy.
 * Gói ĐÃ gán: thêm những người dùng được gán đích danh.
 */
require_once __DIR__ . '/database.php';

class BG_QuyenGoiThau_DAL
{
    /** Mã nhóm được xem mọi gói thầu (ngoài nhóm la_admin) */
    const NHOM_XEM_TAT_CA = 'MANAGER';

    /** Danh sách id người dùng được gán cho 1 gói thầu */
    public static function nguoiDungCuaGoi(int $goiThauId): array
    {
        $sql = "SELECT nguoi_dung_id FROM bg_quyen_goi_thau
                WHERE goi_thau_id = :gt AND da_xoa = 0";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([':gt' => $goiThauId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /** Danh sách id gói thầu 1 người dùng được gán */
    public static function goiThauCuaNguoiDung(int $nguoiDungId): array
    {
        $sql = "SELECT goi_thau_id FROM bg_quyen_goi_thau
                WHERE nguoi_dung_id = :nd AND da_xoa = 0";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([':nd' => $nguoiDungId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * Thay TOÀN BỘ danh sách người được xem 1 gói thầu.
     * Xóa hẳn dòng cũ rồi thêm mới — bảng này chỉ là ánh xạ, không cần lưu vết.
     */
    public static function thayDanhSach(int $goiThauId, array $nguoiDungIds, int $u): void
    {
        $pdo = Database::getConnection();

        $pdo->prepare("DELETE FROM bg_quyen_goi_thau WHERE goi_thau_id = :gt")
            ->execute([':gt' => $goiThauId]);

        $ids = array_values(array_unique(array_filter(
            array_map('intval', $nguoiDungIds),
            static fn($v) => $v > 0
        )));
        if (empty($ids)) return;

        // Chèn theo lô — placeholder đánh số PHẢI có dấu ngăn '_' (§3.3)
        $cho  = [];
        $bind = [];
        foreach ($ids as $i => $id) {
            $cho[] = "(:gt_{$i}, :nd_{$i}, NOW(), :ntao_{$i}, 0)";
            $bind[":gt_{$i}"]   = $goiThauId;
            $bind[":nd_{$i}"]   = $id;
            $bind[":ntao_{$i}"] = $u;
        }

        $sql = "INSERT INTO bg_quyen_goi_thau
                    (goi_thau_id, nguoi_dung_id, ngay_tao, nguoi_tao, da_xoa)
                VALUES " . implode(', ', $cho);
        $pdo->prepare($sql)->execute($bind);
    }

    /**
     * Người dùng này có được xem gói thầu đó không.
     * Dùng cho trang chi tiết — danh sách thì lọc bằng SQL cho nhanh.
     */
    public static function duocXem(int $goiThauId, int $nguoiDungId): bool
    {
        if (self::xemTatCa($nguoiDungId)) return true;

        $sql = "SELECT COUNT(*) FROM bg_quyen_goi_thau
                WHERE goi_thau_id = :gt AND nguoi_dung_id = :nd AND da_xoa = 0";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([':gt' => $goiThauId, ':nd' => $nguoiDungId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Người dùng thuộc nhóm được xem MỌI gói thầu?
     * (nhóm la_admin, hoặc nhóm Quản lý)
     */
    public static function xemTatCa(int $nguoiDungId): bool
    {
        $sql = "SELECT COUNT(*)
                FROM dm_nguoi_dung nd
                INNER JOIN dm_nhom_tai_khoan nt ON nt.id = nd.nhom_tai_khoan_id
                WHERE nd.id = :nd AND nd.da_xoa = 0 AND nt.da_xoa = 0
                  AND (nt.la_admin = 1 OR nt.ma_nhom = :ma)";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([':nd' => $nguoiDungId, ':ma' => self::NHOM_XEM_TAT_CA]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Mệnh đề WHERE lọc gói thầu theo quyền, dùng chung cho mọi truy vấn danh sách.
     *
     * @param string $alias Bí danh bảng bg_goi_thau trong câu SQL (vd 'gt')
     * @return array [chuỗi SQL thêm vào WHERE, mảng tham số bind]
     */
    public static function dieuKienLoc(int $nguoiDungId, string $alias = 'gt'): array
    {
        if (self::xemTatCa($nguoiDungId)) {
            return ['', []];   // xem tất cả -> không cần lọc
        }

        // Chỉ gói được gán đích danh
        $sql = " AND EXISTS (
                    SELECT 1 FROM bg_quyen_goi_thau q
                    WHERE q.goi_thau_id = {$alias}.id
                      AND q.nguoi_dung_id = :q_nd
                      AND q.da_xoa = 0
                 ) ";
        return [$sql, [':q_nd' => $nguoiDungId]];
    }

    /**
     * Giống dieuKienLoc() nhưng lọc theo MỘT CỘT goi_thau_id bất kỳ —
     * dùng cho bảng hàng hóa / báo giá / file (không phải bảng gói thầu).
     *
     * @param string $cot Biểu thức cột, vd 'hh.goi_thau_id'
     */
    public static function dieuKienLocTheoCot(int $nguoiDungId, string $cot): array
    {
        if (self::xemTatCa($nguoiDungId)) {
            return ['', []];
        }

        $sql = " AND EXISTS (
                    SELECT 1 FROM bg_quyen_goi_thau q
                    WHERE q.goi_thau_id = {$cot}
                      AND q.nguoi_dung_id = :qc_nd
                      AND q.da_xoa = 0
                 ) ";
        return [$sql, [':qc_nd' => $nguoiDungId]];
    }

    /** Xóa mọi phân quyền của 1 gói thầu (khi xóa vĩnh viễn gói) */
    public static function xoaTheoGoiThau(int $goiThauId): int
    {
        $stmt = Database::getConnection()
            ->prepare("DELETE FROM bg_quyen_goi_thau WHERE goi_thau_id = :gt");
        $stmt->execute([':gt' => $goiThauId]);
        return $stmt->rowCount();
    }
}
