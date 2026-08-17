<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../PUBLIC/Entities/DM_PhanQuyen_PUBLIC.php';

class DM_PhanQuyen_DAL
{
    /**
     * Lấy ma trận phân quyền của 1 nhóm tài khoản.
     * Kết hợp với DS_FORM: form nào chưa có phân quyền → trả về quyền = 0.
     */
    public static function getMatrixByNhom(int $nhomId): array
    {
        $sql = "SELECT f.id AS form_id, f.ten_form, f.modules_tuong_ung, f.form_cha_id,
                       IFNULL(pq.quyen_xem, 0) AS quyen_xem,
                       IFNULL(pq.quyen_them, 0) AS quyen_them,
                       IFNULL(pq.quyen_sua, 0) AS quyen_sua,
                       IFNULL(pq.quyen_xoa, 0) AS quyen_xoa
                FROM dm_danh_sach_form f
                LEFT JOIN dm_phan_quyen pq ON pq.form_id = f.id AND pq.nhom_tai_khoan_id = :nhom
                WHERE f.da_xoa = 0
                ORDER BY f.form_cha_id ASC, f.id ASC";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([':nhom' => $nhomId]);
        return $stmt->fetchAll();
    }

    /**
     * Ghi (upsert) quyền: nếu có thì update, không có thì insert.
     */
    public static function upsert(int $nhomId, int $formId, int $xem, int $them, int $sua, int $xoa, int $u): int
    {
        $sql = "INSERT INTO dm_phan_quyen (nhom_tai_khoan_id, form_id, quyen_xem, quyen_them, quyen_sua, quyen_xoa,
                                           ngay_tao, ngay_cap_nhat, nguoi_tao, nguoi_cap_nhat)
                VALUES (:nhom, :form, :x1, :t1, :s1, :xo1, NOW(), NOW(), :u1, :u2)
                ON DUPLICATE KEY UPDATE quyen_xem=:x2, quyen_them=:t2, quyen_sua=:s2, quyen_xoa=:xo2,
                                        ngay_cap_nhat=NOW(), nguoi_cap_nhat=:u3";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([
            ':nhom' => $nhomId, ':form' => $formId,
            ':x1' => $xem, ':t1' => $them, ':s1' => $sua, ':xo1' => $xoa,
            ':x2' => $xem, ':t2' => $them, ':s2' => $sua, ':xo2' => $xoa,
            ':u1' => $u, ':u2' => $u, ':u3' => $u,
        ]);
        return $stmt->rowCount();
    }

    /**
     * Xóa toàn bộ phân quyền của 1 nhóm (dùng khi xóa nhóm).
     */
    public static function deleteByNhom(int $nhomId): int
    {
        $stmt = Database::getConnection()->prepare("DELETE FROM dm_phan_quyen WHERE nhom_tai_khoan_id=:n");
        $stmt->execute([':n' => $nhomId]);
        return $stmt->rowCount();
    }

    /**
     * Set full quyền cho Admin (nhom_tai_khoan_id = 1) - tiện dụng khi seed.
     */
    public static function grantAllToNhom(int $nhomId, int $u): int
    {
        $sql = "INSERT INTO dm_phan_quyen (nhom_tai_khoan_id, form_id, quyen_xem, quyen_them, quyen_sua, quyen_xoa,
                                           ngay_tao, ngay_cap_nhat, nguoi_tao, nguoi_cap_nhat)
                SELECT :nhom, f.id, 1, 1, 1, 1, NOW(), NOW(), :u1, :u2
                FROM dm_danh_sach_form f
                WHERE f.da_xoa = 0
                ON DUPLICATE KEY UPDATE quyen_xem=1, quyen_them=1, quyen_sua=1, quyen_xoa=1, ngay_cap_nhat=NOW(), nguoi_cap_nhat=:u3";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([':nhom' => $nhomId, ':u1' => $u, ':u2' => $u, ':u3' => $u]);
        return $stmt->rowCount();
    }

    /**
     * Xóa toàn bộ phân quyền của 1 form (dùng khi xóa vĩnh viễn form).
     */
    public static function deleteByForm(int $formId): int
    {
        $stmt = Database::getConnection()->prepare("DELETE FROM dm_phan_quyen WHERE form_id=:f");
        $stmt->execute([':f' => $formId]);
        return $stmt->rowCount();
    }

    public static function getByNhomVaForm(int $nhomId, int $formId): ?array
    {
        $stmt = Database::getConnection()->prepare("SELECT * FROM dm_phan_quyen WHERE nhom_tai_khoan_id=:n AND form_id=:f");
        $stmt->execute([':n' => $nhomId, ':f' => $formId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}