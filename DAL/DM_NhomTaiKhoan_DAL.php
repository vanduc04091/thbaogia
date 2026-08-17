<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../PUBLIC/Entities/DM_NhomTaiKhoan_PUBLIC.php';

class DM_NhomTaiKhoan_DAL
{
    private static function selectSql(): string
    {
        return "SELECT nt.*
                FROM dm_nhom_tai_khoan nt";
    }

    public static function insert(DM_NhomTaiKhoan_PUBLIC $e): int
    {
        $sql = "INSERT INTO dm_nhom_tai_khoan (ma_nhom, ten_nhom, mo_ta, trang_thai, la_admin, ngay_tao, ngay_cap_nhat, nguoi_tao, nguoi_cap_nhat, da_xoa)
                VALUES (:ma, :ten, :mt, :tt, :la, NOW(), NOW(), :u1, :u2, 0)";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([':ma' => $e->ma_nhom, ':ten' => $e->ten_nhom, ':mt' => $e->mo_ta, ':tt' => $e->trang_thai, ':la' => $e->la_admin, ':u1' => $e->nguoi_tao ?? 0, ':u2' => $e->nguoi_tao ?? 0]);
        return (int)Database::getConnection()->lastInsertId();
    }

    public static function update(DM_NhomTaiKhoan_PUBLIC $e): int
    {
        $sql = "UPDATE dm_nhom_tai_khoan SET ma_nhom=:ma, ten_nhom=:ten, mo_ta=:mt, trang_thai=:tt, la_admin=:la, ngay_cap_nhat=NOW(), nguoi_cap_nhat=:u WHERE id=:id AND da_xoa=0";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([':ma' => $e->ma_nhom, ':ten' => $e->ten_nhom, ':mt' => $e->mo_ta, ':tt' => $e->trang_thai, ':la' => $e->la_admin, ':u' => $e->nguoi_cap_nhat ?? 0, ':id' => $e->id]);
        return $stmt->rowCount();
    }

    public static function softDelete(int $id, int $u): int
    {
        $stmt = Database::getConnection()->prepare("UPDATE dm_nhom_tai_khoan SET da_xoa=1, ngay_cap_nhat=NOW(), nguoi_cap_nhat=:u WHERE id=:id");
        $stmt->execute([':u' => $u, ':id' => $id]);
        return $stmt->rowCount();
    }

    /** Khôi phục từ thùng rác */
    public static function restore(int $id, int $u): int
    {
        $stmt = Database::getConnection()->prepare("UPDATE dm_nhom_tai_khoan SET da_xoa=0, ngay_cap_nhat=NOW(), nguoi_cap_nhat=:u WHERE id=:id");
        $stmt->execute([':u' => $u, ':id' => $id]);
        return $stmt->rowCount();
    }

    /** Xóa vĩnh viễn — chỉ bản ghi đã ở thùng rác */
    public static function delete(int $id): int
    {
        $stmt = Database::getConnection()->prepare("DELETE FROM dm_nhom_tai_khoan WHERE id=:id AND da_xoa=1");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount();
    }

    /** Đếm số người dùng đang thuộc nhóm (chặn xóa nhóm còn user) */
    public static function countNguoiDung(int $nhomId): int
    {
        $stmt = Database::getConnection()->prepare("SELECT COUNT(*) FROM dm_nguoi_dung WHERE nhom_tai_khoan_id=:id AND da_xoa=0");
        $stmt->execute([':id' => $nhomId]);
        return (int)$stmt->fetchColumn();
    }

    public static function getById(int $id): ?DM_NhomTaiKhoan_PUBLIC
    {
        $stmt = Database::getConnection()->prepare(self::selectSql() . " WHERE nt.id=:id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? Database::hydrate($row, 'DM_NhomTaiKhoan_PUBLIC') : null;
    }

    public static function getAll(int $daXoa = 0): array
    {
        $stmt = Database::getConnection()->prepare(self::selectSql() . " WHERE nt.da_xoa=:dx ORDER BY nt.id ASC");
        $stmt->execute([':dx' => $daXoa]);
        return $stmt->fetchAll();
    }

    public static function getCombo(): array
    {
        $key = 'dm_nhom_tai_khoan:combo';
        $cached = MemcachedHelper::get($key);
        if ($cached !== null) return $cached;
        $stmt = Database::getConnection()->query("SELECT id, ma_nhom, ten_nhom FROM dm_nhom_tai_khoan WHERE da_xoa=0 ORDER BY ten_nhom");
        $data = $stmt->fetchAll();
        MemcachedHelper::set($key, $data, Constants::CACHE_TTL_COMBO);
        return $data;
    }

    public static function getPaged(int $page, int $pageSize, string $search = '', int $daXoa = 0): array
    {
        [$page, $pageSize, $offset] = PaginationHelper::normalize($page, $pageSize);
        $where = " WHERE nt.da_xoa=:dx ";
        $params = [':dx' => $daXoa];
        if ($search !== '') {
            // KHÔNG reuse named placeholder (EMULATE_PREPARES = false)
            $where .= " AND (nt.ma_nhom LIKE :s1 OR nt.ten_nhom LIKE :s2 OR nt.mo_ta LIKE :s3) ";
            $like = "%{$search}%";
            $params[':s1'] = $like;
            $params[':s2'] = $like;
            $params[':s3'] = $like;
        }
        $countSql = "SELECT COUNT(*) FROM dm_nhom_tai_khoan nt" . $where;
        $stmt = Database::getConnection()->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $sql = self::selectSql() . $where . " ORDER BY nt.id ASC LIMIT {$pageSize} OFFSET {$offset}";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute($params);
        return [
            'data' => $stmt->fetchAll(),
            'totalRecords' => $total,
            'totalPages' => PaginationHelper::totalPages($total, $pageSize),
        ];
    }

    public static function checkMaExists(string $ma, int $excludeId = 0): bool
    {
        $stmt = Database::getConnection()->prepare("SELECT COUNT(*) FROM dm_nhom_tai_khoan WHERE ma_nhom=:m AND da_xoa=0 AND id<>:id");
        $stmt->execute([':m' => $ma, ':id' => $excludeId]);
        return (int)$stmt->fetchColumn() > 0;
    }
}