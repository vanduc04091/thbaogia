<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../PUBLIC/Entities/DM_DanhSachForm_PUBLIC.php';

class DM_DanhSachForm_DAL
{
    private static function selectSql(): string
    {
        return "SELECT f.*
                FROM dm_danh_sach_form f";
    }

    public static function insert(DM_DanhSachForm_PUBLIC $e): int
    {
        $sql = "INSERT INTO dm_danh_sach_form (modules_tuong_ung, ten_form, form_cha_id, ngay_tao, ngay_cap_nhat, nguoi_tao, nguoi_cap_nhat, da_xoa)
                VALUES (:mod, :ten, :cha, NOW(), NOW(), :u1, :u2, 0)";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([':mod' => $e->modules_tuong_ung, ':ten' => $e->ten_form, ':cha' => $e->form_cha_id, ':u1' => $e->nguoi_tao ?? 0, ':u2' => $e->nguoi_tao ?? 0]);
        return (int)Database::getConnection()->lastInsertId();
    }

    public static function update(DM_DanhSachForm_PUBLIC $e): int
    {
        $sql = "UPDATE dm_danh_sach_form SET modules_tuong_ung=:mod, ten_form=:ten, form_cha_id=:cha, ngay_cap_nhat=NOW(), nguoi_cap_nhat=:u WHERE id=:id AND da_xoa=0";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([':mod' => $e->modules_tuong_ung, ':ten' => $e->ten_form, ':cha' => $e->form_cha_id, ':u' => $e->nguoi_cap_nhat ?? 0, ':id' => $e->id]);
        return $stmt->rowCount();
    }

    public static function softDelete(int $id, int $u): int
    {
        $stmt = Database::getConnection()->prepare("UPDATE dm_danh_sach_form SET da_xoa=1, ngay_cap_nhat=NOW(), nguoi_cap_nhat=:u WHERE id=:id");
        $stmt->execute([':u' => $u, ':id' => $id]);
        return $stmt->rowCount();
    }

    /** Khôi phục từ thùng rác */
    public static function restore(int $id, int $u): int
    {
        $stmt = Database::getConnection()->prepare("UPDATE dm_danh_sach_form SET da_xoa=0, ngay_cap_nhat=NOW(), nguoi_cap_nhat=:u WHERE id=:id");
        $stmt->execute([':u' => $u, ':id' => $id]);
        return $stmt->rowCount();
    }

    /** Xóa vĩnh viễn + dọn phân quyền liên quan (gọi trong transaction ở BUS) */
    public static function delete(int $id): int
    {
        $stmt = Database::getConnection()->prepare("DELETE FROM dm_danh_sach_form WHERE id=:id AND da_xoa=1");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount();
    }

    public static function getById(int $id): ?DM_DanhSachForm_PUBLIC
    {
        $stmt = Database::getConnection()->prepare(self::selectSql() . " WHERE f.id=:id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? Database::hydrate($row, 'DM_DanhSachForm_PUBLIC') : null;
    }

    public static function getAll(int $daXoa = 0): array
    {
        $stmt = Database::getConnection()->prepare(self::selectSql() . " WHERE f.da_xoa=:dx ORDER BY f.form_cha_id ASC, f.id ASC");
        $stmt->execute([':dx' => $daXoa]);
        return $stmt->fetchAll();
    }

    public static function getPaged(int $page, int $pageSize, string $search = '', int $daXoa = 0): array
    {
        [$page, $pageSize, $offset] = PaginationHelper::normalize($page, $pageSize);
        $where = " WHERE f.da_xoa=:dx ";
        $params = [':dx' => $daXoa];
        if ($search !== '') {
            // KHÔNG reuse named placeholder (EMULATE_PREPARES = false)
            $where .= " AND (f.ten_form LIKE :s1 OR f.modules_tuong_ung LIKE :s2) ";
            $like = "%{$search}%";
            $params[':s1'] = $like;
            $params[':s2'] = $like;
        }

        $countSql = "SELECT COUNT(*) FROM dm_danh_sach_form f" . $where;
        $stmt = Database::getConnection()->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $sql = self::selectSql() . $where . " ORDER BY f.form_cha_id ASC, f.id ASC LIMIT {$pageSize} OFFSET {$offset}";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute($params);
        return [
            'data' => $stmt->fetchAll(),
            'totalRecords' => $total,
            'totalPages' => PaginationHelper::totalPages($total, $pageSize),
        ];
    }

    public static function checkModuleExists(string $modules, int $excludeId = 0): bool
    {
        $stmt = Database::getConnection()->prepare("SELECT COUNT(*) FROM dm_danh_sach_form WHERE modules_tuong_ung=:m AND da_xoa=0 AND id<>:id");
        $stmt->execute([':m' => $modules, ':id' => $excludeId]);
        return (int)$stmt->fetchColumn() > 0;
    }
}