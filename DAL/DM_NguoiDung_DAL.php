<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../PUBLIC/Entities/DM_NguoiDung_PUBLIC.php';

class DM_NguoiDung_DAL
{
    const TABLE = 'dm_nguoi_dung';

    /** SELECT đầy đủ (gồm mat_khau) — chỉ dùng nội bộ khi cần verify mật khẩu */
    private static function selectSql(): string
    {
        return "SELECT nd.*, nt.ten_nhom, nt.ma_nhom AS ma_nhom
                FROM dm_nguoi_dung nd
                LEFT JOIN dm_nhom_tai_khoan nt ON nt.id = nd.nhom_tai_khoan_id";
    }

    /**
     * SELECT an toàn cho danh sách: KHÔNG bao giờ trả cột mat_khau ra ngoài.
     * Dùng cho mọi truy vấn mà kết quả được gửi xuống client.
     */
    private static function selectSafeSql(): string
    {
        return "SELECT nd.id, nd.tai_khoan, nd.nhom_tai_khoan_id, nd.trang_thai, nd.lan_dang_nhap_cuoi,
                       nd.ngay_tao, nd.ngay_cap_nhat, nd.nguoi_tao, nd.nguoi_cap_nhat, nd.da_xoa,
                       nt.ten_nhom, nt.ma_nhom AS ma_nhom
                FROM dm_nguoi_dung nd
                LEFT JOIN dm_nhom_tai_khoan nt ON nt.id = nd.nhom_tai_khoan_id";
    }

    public static function insert(DM_NguoiDung_PUBLIC $e): int
    {
        $sql = "INSERT INTO dm_nguoi_dung (tai_khoan, mat_khau, nhom_tai_khoan_id, trang_thai,
                                          ngay_tao, ngay_cap_nhat, nguoi_tao, nguoi_cap_nhat, da_xoa)
                VALUES (:tai_khoan, :mat_khau, :nhom, :tt, NOW(), NOW(), :nt1, :nt2, 0)";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([
            ':tai_khoan' => $e->tai_khoan,
            ':mat_khau' => $e->mat_khau,
            ':nhom' => $e->nhom_tai_khoan_id,
            ':tt' => $e->trang_thai,
            ':nt1' => $e->nguoi_tao,
            ':nt2' => $e->nguoi_tao,
        ]);
        return (int)Database::getConnection()->lastInsertId();
    }

    public static function update(DM_NguoiDung_PUBLIC $e): int
    {
        $sql = "UPDATE dm_nguoi_dung
                SET tai_khoan = :tai_khoan,
                    nhom_tai_khoan_id = :nhom,
                    trang_thai = :tt,
                    ngay_cap_nhat = NOW(),
                    nguoi_cap_nhat = :ncn
                WHERE id = :id AND da_xoa = 0";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([
            ':tai_khoan' => $e->tai_khoan,
            ':nhom' => $e->nhom_tai_khoan_id,
            ':tt' => $e->trang_thai,
            ':ncn' => $e->nguoi_cap_nhat,
            ':id' => $e->id,
        ]);
        return $stmt->rowCount();
    }

    public static function updatePassword(int $id, string $hashedPass, int $nguoiCapNhat): int
    {
        $sql = "UPDATE dm_nguoi_dung SET mat_khau=:p, ngay_cap_nhat=NOW(), nguoi_cap_nhat=:u WHERE id=:id";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([':p' => $hashedPass, ':u' => $nguoiCapNhat, ':id' => $id]);
        return $stmt->rowCount();
    }

    public static function softDelete(int $id, int $nguoiCapNhat): int
    {
        $sql = "UPDATE dm_nguoi_dung SET da_xoa=1, ngay_cap_nhat=NOW(), nguoi_cap_nhat=:u WHERE id=:id";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([':u' => $nguoiCapNhat, ':id' => $id]);
        return $stmt->rowCount();
    }

    /** Khôi phục từ thùng rác */
    public static function restore(int $id, int $nguoiCapNhat): int
    {
        $sql = "UPDATE dm_nguoi_dung SET da_xoa=0, ngay_cap_nhat=NOW(), nguoi_cap_nhat=:u WHERE id=:id";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([':u' => $nguoiCapNhat, ':id' => $id]);
        return $stmt->rowCount();
    }

    /** Xóa vĩnh viễn — chỉ dùng cho bản ghi đã ở thùng rác */
    public static function delete(int $id): int
    {
        $stmt = Database::getConnection()->prepare("DELETE FROM dm_nguoi_dung WHERE id=:id AND da_xoa=1");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount();
    }

    /** Lấy 1 người dùng KHÔNG kèm mat_khau — dùng cho hiển thị */
    public static function getById(int $id): ?DM_NguoiDung_PUBLIC
    {
        $sql = self::selectSafeSql() . " WHERE nd.id = :id";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? Database::hydrate($row, 'DM_NguoiDung_PUBLIC') : null;
    }

    /**
     * Lấy người dùng KÈM hash mật khẩu — CHỈ dùng cho xác thực (login, đổi mật khẩu).
     * Không bao giờ trả kết quả của hàm này xuống client.
     */
    public static function getByIdWithPassword(int $id): ?DM_NguoiDung_PUBLIC
    {
        $sql = self::selectSql() . " WHERE nd.id = :id";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? Database::hydrate($row, 'DM_NguoiDung_PUBLIC') : null;
    }

    public static function getByTaiKhoan(string $taiKhoan): ?DM_NguoiDung_PUBLIC
    {
        $sql = self::selectSql() . " WHERE nd.tai_khoan = :tk AND nd.da_xoa = 0";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([':tk' => $taiKhoan]);
        $row = $stmt->fetch();
        return $row ? Database::hydrate($row, 'DM_NguoiDung_PUBLIC') : null;
    }

    public static function getPaged(int $page, int $pageSize, string $search = '', int $daXoa = 0, int $nhomId = 0): array
    {
        [$page, $pageSize, $offset] = PaginationHelper::normalize($page, $pageSize);
        $where = ' WHERE nd.da_xoa = :dx ';
        $params = [':dx' => $daXoa];
        if ($search !== '') {
            $where .= ' AND (nd.tai_khoan LIKE :s) ';
            $params[':s'] = "%{$search}%";
        }
        if ($nhomId > 0) {
            $where .= ' AND nd.nhom_tai_khoan_id = :nhom ';
            $params[':nhom'] = $nhomId;
        }

        $countSql = "SELECT COUNT(*) FROM dm_nguoi_dung nd" . $where;
        $stmt = Database::getConnection()->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        // selectSafeSql: không lộ hash mật khẩu ra response
        $sql = self::selectSafeSql() . $where . " ORDER BY nd.id DESC LIMIT {$pageSize} OFFSET {$offset}";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();

        return [
            'data' => $data,
            'totalRecords' => $total,
            'totalPages' => PaginationHelper::totalPages($total, $pageSize),
        ];
    }

    /** Combo tài khoản đang hoạt động (id + tai_khoan) */
    public static function getCombo(): array
    {
        $key = 'dm_nguoi_dung:combo';
        $cached = MemcachedHelper::get($key);
        if ($cached !== null) return $cached;
        $stmt = Database::getConnection()->query("SELECT id, tai_khoan FROM dm_nguoi_dung WHERE da_xoa=0 ORDER BY tai_khoan");
        $data = $stmt->fetchAll();
        MemcachedHelper::set($key, $data, Constants::CACHE_TTL_COMBO);
        return $data;
    }

    public static function checkTaiKhoanExists(string $taiKhoan, int $excludeId = 0): bool
    {
        $sql = "SELECT COUNT(*) FROM dm_nguoi_dung WHERE tai_khoan = :tk AND da_xoa = 0 AND id <> :id";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([':tk' => $taiKhoan, ':id' => $excludeId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function updateLastLogin(int $id): void
    {
        $stmt = Database::getConnection()->prepare("UPDATE dm_nguoi_dung SET lan_dang_nhap_cuoi = NOW() WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }
}