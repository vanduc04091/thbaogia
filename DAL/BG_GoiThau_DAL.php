<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../PUBLIC/Entities/BG_GoiThau_PUBLIC.php';

class BG_GoiThau_DAL
{
    const TABLE = 'bg_goi_thau';

    private static function selectSql(): string
    {
        return "SELECT gt.*,
                       (SELECT COUNT(*) FROM bg_hang_hoa hh
                         WHERE hh.goi_thau_id = gt.id AND hh.da_xoa = 0) AS so_hang_hoa,
                       (SELECT COUNT(*) FROM bg_bao_gia bg
                         WHERE bg.goi_thau_id = gt.id AND bg.da_xoa = 0) AS so_bao_gia,
                       (SELECT COUNT(*) FROM bg_bao_gia bg2
                         WHERE bg2.goi_thau_id = gt.id AND bg2.da_xoa = 0
                           AND bg2.trang_thai = 1) AS so_bao_gia_xac_nhan
                FROM bg_goi_thau gt";
    }

    public static function insert(BG_GoiThau_PUBLIC $e): int
    {
        $sql = "INSERT INTO bg_goi_thau
                    (so_thong_bao, ten_goi_thau, noi_dung, ngay_phat_hanh,
                     thoi_gian_mo_bao_gia, thoi_gian_dong_bao_gia, han_cuoi,
                     thoi_gian_hop_dong, hieu_luc_bao_gia, token, trang_thai,
                     ngay_tao, ngay_cap_nhat, nguoi_tao, nguoi_cap_nhat, da_xoa)
                VALUES (:stb, :ten, :nd, :npg, :mo, :dong, :hc, :tghd, :hlbg, :token, :tt,
                        NOW(), NOW(), :nt1, :nt2, 0)";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([
            ':stb'   => $e->so_thong_bao,
            ':ten'   => $e->ten_goi_thau,
            ':nd'    => $e->noi_dung,
            ':npg'   => $e->ngay_phat_hanh ?: null,
            ':mo'    => $e->thoi_gian_mo_bao_gia ?: null,
            ':dong'  => $e->thoi_gian_dong_bao_gia ?: null,
            ':hc'    => $e->han_cuoi ?: null,
            ':tghd'  => $e->thoi_gian_hop_dong,
            ':hlbg'  => $e->hieu_luc_bao_gia,
            ':token' => $e->token,
            ':tt'    => $e->trang_thai,
            ':nt1'   => $e->nguoi_tao,
            ':nt2'   => $e->nguoi_tao,
        ]);
        return (int)Database::getConnection()->lastInsertId();
    }

    public static function update(BG_GoiThau_PUBLIC $e): int
    {
        $sql = "UPDATE bg_goi_thau SET
                    so_thong_bao = :stb,
                    ten_goi_thau = :ten,
                    noi_dung = :nd,
                    ngay_phat_hanh = :npg,
                    thoi_gian_mo_bao_gia = :mo,
                    thoi_gian_dong_bao_gia = :dong,
                    han_cuoi = :hc,
                    thoi_gian_hop_dong = :tghd,
                    hieu_luc_bao_gia = :hlbg,
                    trang_thai = :tt,
                    ngay_cap_nhat = NOW(),
                    nguoi_cap_nhat = :ncn
                WHERE id = :id AND da_xoa = 0";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([
            ':stb'  => $e->so_thong_bao,
            ':ten'  => $e->ten_goi_thau,
            ':nd'   => $e->noi_dung,
            ':npg'  => $e->ngay_phat_hanh ?: null,
            ':mo'   => $e->thoi_gian_mo_bao_gia ?: null,
            ':dong' => $e->thoi_gian_dong_bao_gia ?: null,
            ':hc'   => $e->han_cuoi ?: null,
            ':tghd' => $e->thoi_gian_hop_dong,
            ':hlbg' => $e->hieu_luc_bao_gia,
            ':tt'   => $e->trang_thai,
            ':ncn'  => $e->nguoi_cap_nhat,
            ':id'   => $e->id,
        ]);
        return $stmt->rowCount();
    }

    public static function updateTrangThai(int $id, int $trangThai, int $u): int
    {
        $stmt = Database::getConnection()->prepare(
            "UPDATE bg_goi_thau SET trang_thai = :tt, ngay_cap_nhat = NOW(), nguoi_cap_nhat = :u
             WHERE id = :id AND da_xoa = 0"
        );
        $stmt->execute([':tt' => $trangThai, ':u' => $u, ':id' => $id]);
        return $stmt->rowCount();
    }

    /** Sinh lại token QR (khi cần vô hiệu link cũ) */
    public static function updateToken(int $id, string $token, int $u): int
    {
        $stmt = Database::getConnection()->prepare(
            "UPDATE bg_goi_thau SET token = :tk, ngay_cap_nhat = NOW(), nguoi_cap_nhat = :u
             WHERE id = :id AND da_xoa = 0"
        );
        $stmt->execute([':tk' => $token, ':u' => $u, ':id' => $id]);
        return $stmt->rowCount();
    }

    public static function softDelete(int $id, int $u): int
    {
        $stmt = Database::getConnection()->prepare(
            "UPDATE bg_goi_thau SET da_xoa = 1, ngay_cap_nhat = NOW(), nguoi_cap_nhat = :u WHERE id = :id"
        );
        $stmt->execute([':u' => $u, ':id' => $id]);
        return $stmt->rowCount();
    }

    public static function restore(int $id, int $u): int
    {
        $stmt = Database::getConnection()->prepare(
            "UPDATE bg_goi_thau SET da_xoa = 0, ngay_cap_nhat = NOW(), nguoi_cap_nhat = :u WHERE id = :id"
        );
        $stmt->execute([':u' => $u, ':id' => $id]);
        return $stmt->rowCount();
    }

    public static function delete(int $id): int
    {
        $stmt = Database::getConnection()->prepare("DELETE FROM bg_goi_thau WHERE id = :id AND da_xoa = 1");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount();
    }

    public static function getById(int $id): ?BG_GoiThau_PUBLIC
    {
        $stmt = Database::getConnection()->prepare(self::selectSql() . " WHERE gt.id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? Database::hydrate($row, 'BG_GoiThau_PUBLIC') : null;
    }

    /** Tra gói thầu theo token QR — dùng cho cổng nhà thầu */
    public static function getByToken(string $token): ?BG_GoiThau_PUBLIC
    {
        $stmt = Database::getConnection()->prepare(
            self::selectSql() . " WHERE gt.token = :tk AND gt.da_xoa = 0"
        );
        $stmt->execute([':tk' => $token]);
        $row = $stmt->fetch();
        return $row ? Database::hydrate($row, 'BG_GoiThau_PUBLIC') : null;
    }

    public static function checkSoThongBaoExists(string $soThongBao, int $excludeId = 0): bool
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT COUNT(*) FROM bg_goi_thau WHERE so_thong_bao = :stb AND da_xoa = 0 AND id <> :id"
        );
        $stmt->execute([':stb' => $soThongBao, ':id' => $excludeId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function tokenExists(string $token): bool
    {
        $stmt = Database::getConnection()->prepare("SELECT COUNT(*) FROM bg_goi_thau WHERE token = :tk");
        $stmt->execute([':tk' => $token]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * @param string $trangThaiBaoGia Lọc theo trạng thái báo giá suy từ thời gian:
     *                                '' | chua_mo | dang_mo | het_han | khong_nhan
     *                                (hằng BG_* trong BG_GoiThau_PUBLIC)
     */
    public static function getPaged(
        int $page,
        int $pageSize,
        string $search = '',
        int $daXoa = 0,
        int $trangThai = -1,
        string $trangThaiBaoGia = ''
    ): array {
        [$page, $pageSize, $offset] = PaginationHelper::normalize($page, $pageSize);

        $where = ' WHERE gt.da_xoa = :dx ';
        $params = [':dx' => $daXoa];

        if ($search !== '') {
            // KHÔNG reuse named placeholder (EMULATE_PREPARES = false) → :s1, :s2, :s3
            $where .= ' AND (gt.so_thong_bao LIKE :s1 OR gt.ten_goi_thau LIKE :s2 OR gt.noi_dung LIKE :s3) ';
            $like = "%{$search}%";
            $params[':s1'] = $like;
            $params[':s2'] = $like;
            $params[':s3'] = $like;
        }
        if ($trangThai >= 0) {
            $where .= ' AND gt.trang_thai = :tt ';
            $params[':tt'] = $trangThai;
        }

        // Lọc theo trạng thái báo giá — điều kiện phải KHỚP LOGIC
        // BG_GoiThau_PUBLIC::tinhTrangThaiBaoGia(), nếu sửa 1 bên phải sửa cả 2.
        // Mỗi mốc thời gian so sánh 2 lần → dùng tên placeholder riêng (:now1, :now2).
        $ttMo = BG_GoiThau_PUBLIC::TT_DANG_MO;
        switch ($trangThaiBaoGia) {
            case BG_GoiThau_PUBLIC::BG_CHUA_MO:
                $where .= " AND gt.trang_thai = {$ttMo}
                            AND gt.thoi_gian_mo_bao_gia IS NOT NULL
                            AND :now1 < gt.thoi_gian_mo_bao_gia ";
                $params[':now1'] = date('Y-m-d H:i:s');
                break;

            case BG_GoiThau_PUBLIC::BG_DANG_MO:
                $where .= " AND gt.trang_thai = {$ttMo}
                            AND (gt.thoi_gian_mo_bao_gia IS NULL OR :now1 >= gt.thoi_gian_mo_bao_gia)
                            AND (gt.thoi_gian_dong_bao_gia IS NULL OR :now2 <= gt.thoi_gian_dong_bao_gia) ";
                $params[':now1'] = date('Y-m-d H:i:s');
                $params[':now2'] = date('Y-m-d H:i:s');
                break;

            case BG_GoiThau_PUBLIC::BG_HET_HAN:
                $where .= " AND gt.trang_thai = {$ttMo}
                            AND gt.thoi_gian_dong_bao_gia IS NOT NULL
                            AND :now1 > gt.thoi_gian_dong_bao_gia ";
                $params[':now1'] = date('Y-m-d H:i:s');
                break;

            case BG_GoiThau_PUBLIC::BG_KHONG_NHAN:
                $where .= " AND gt.trang_thai <> {$ttMo} ";
                break;
        }

        $stmt = Database::getConnection()->prepare("SELECT COUNT(*) FROM bg_goi_thau gt" . $where);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        // LIMIT/OFFSET đã qua PaginationHelper::normalize() → an toàn để nội suy
        $sql = self::selectSql() . $where . " ORDER BY gt.id DESC LIMIT {$pageSize} OFFSET {$offset}";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute($params);

        return [
            'data' => $stmt->fetchAll(),
            'totalRecords' => $total,
            'totalPages' => PaginationHelper::totalPages($total, $pageSize),
        ];
    }

    /** Combo gói thầu đang mở / đã đóng — dùng cho bộ lọc và trang tổng hợp */
    public static function getCombo(): array
    {
        $stmt = Database::getConnection()->query(
            "SELECT id, so_thong_bao, ten_goi_thau, trang_thai
             FROM bg_goi_thau WHERE da_xoa = 0 ORDER BY id DESC"
        );
        return $stmt->fetchAll();
    }

    /** Thống kê cho dashboard */
    public static function thongKe(): array
    {
        $sql = "SELECT
                    COUNT(*) AS tong,
                    SUM(CASE WHEN trang_thai = 1 THEN 1 ELSE 0 END) AS dang_mo,
                    SUM(CASE WHEN trang_thai = 2 THEN 1 ELSE 0 END) AS da_dong,
                    SUM(CASE WHEN han_cuoi IS NOT NULL AND han_cuoi < CURDATE()
                                  AND trang_thai = 1 THEN 1 ELSE 0 END) AS qua_han
                FROM bg_goi_thau WHERE da_xoa = 0";
        $row = Database::getConnection()->query($sql)->fetch();
        return [
            'tong'    => (int)($row['tong'] ?? 0),
            'dang_mo' => (int)($row['dang_mo'] ?? 0),
            'da_dong' => (int)($row['da_dong'] ?? 0),
            'qua_han' => (int)($row['qua_han'] ?? 0),
        ];
    }
}
