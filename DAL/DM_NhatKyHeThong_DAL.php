<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../PUBLIC/Entities/DM_NhatKyHeThong_PUBLIC.php';

class DM_NhatKyHeThong_DAL
{
    /**
     * Ghi nhật ký. $bang/$idLienQuan được gộp vào cột noi_dung để khớp schema thật.
     * Không bao giờ ném exception ra ngoài — log hỏng không được làm gãy nghiệp vụ.
     */
    public static function log(int $nguoiDungId, string $module, string $hanhDong, ?string $bang = null, ?int $idLienQuan = null, ?string $noiDung = null): void
    {
        try {
            // Gộp thông tin bảng/bản ghi liên quan vào noi_dung (schema không có cột riêng)
            $parts = [];
            if ($bang !== null && $bang !== '') $parts[] = "bang={$bang}";
            if ($idLienQuan !== null && $idLienQuan > 0) $parts[] = "id={$idLienQuan}";
            if ($noiDung !== null && $noiDung !== '') $parts[] = $noiDung;
            $noiDungFull = $parts ? implode('; ', $parts) : null;

            $sql = "INSERT INTO dm_nhat_ky_he_thong (nguoi_dung_id, tai_khoan, module, hanh_dong, noi_dung, ip_address, user_agent, thoi_gian, ngay_tao)
                    VALUES (:u, :tk, :m, :hd, :nd, :ip, :ua, NOW(), NOW())";
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([
                ':u'  => $nguoiDungId ?: null,
                ':tk' => SessionHelper::taiKhoan() ?: null,
                ':m'  => $module,
                ':hd' => mb_substr($hanhDong, 0, 200),
                ':nd' => $noiDungFull,
                ':ip' => Helper::getClientIp(),
                ':ua' => mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500) ?: null,
            ]);
        } catch (Throwable $e) {
            // Không cho log làm gãy luồng chính
        }
    }

    private static function selectSql(): string
    {
        // nk.tai_khoan là snapshot lúc ghi log; u.tai_khoan là tài khoản hiện tại (có thể đã đổi tên)
        return "SELECT nk.*, IFNULL(nk.tai_khoan, u.tai_khoan) AS tai_khoan_hien_thi
                FROM dm_nhat_ky_he_thong nk
                LEFT JOIN dm_nguoi_dung u ON u.id = nk.nguoi_dung_id";
    }

    public static function getById(int $id): ?DM_NhatKyHeThong_PUBLIC
    {
        $stmt = Database::getConnection()->prepare(self::selectSql() . " WHERE nk.id=:id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? Database::hydrate($row, 'DM_NhatKyHeThong_PUBLIC') : null;
    }

    public static function getPaged(int $page, int $pageSize, string $search = '', string $module = '', int $nguoiDungId = 0, string $fromDate = '', string $toDate = ''): array
    {
        [$page, $pageSize, $offset] = PaginationHelper::normalize($page, $pageSize);
        $where = " WHERE 1=1 ";
        $params = [];
        if ($search !== '') {
            // PDO không emulate prepares → KHÔNG reuse named placeholder
            $where .= " AND (nk.hanh_dong LIKE :s1 OR nk.noi_dung LIKE :s2 OR nk.ip_address LIKE :s3 OR nk.tai_khoan LIKE :s4 OR u.tai_khoan LIKE :s5) ";
            $like = "%{$search}%";
            $params[':s1'] = $like;
            $params[':s2'] = $like;
            $params[':s3'] = $like;
            $params[':s4'] = $like;
            $params[':s5'] = $like;
        }
        if ($module !== '') {
            $where .= " AND nk.module=:m ";
            $params[':m'] = $module;
        }
        if ($nguoiDungId > 0) {
            $where .= " AND nk.nguoi_dung_id=:nd ";
            $params[':nd'] = $nguoiDungId;
        }
        if ($fromDate !== '') {
            $where .= " AND nk.thoi_gian >= :fd ";
            $params[':fd'] = $fromDate . ' 00:00:00';
        }
        if ($toDate !== '') {
            $where .= " AND nk.thoi_gian <= :td ";
            $params[':td'] = $toDate . ' 23:59:59';
        }

        $stmt = Database::getConnection()->prepare("SELECT COUNT(*) FROM dm_nhat_ky_he_thong nk LEFT JOIN dm_nguoi_dung u ON u.id=nk.nguoi_dung_id" . $where);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $sql = self::selectSql() . $where . " ORDER BY nk.thoi_gian DESC, nk.id DESC LIMIT {$pageSize} OFFSET {$offset}";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute($params);
        return [
            'data' => $stmt->fetchAll(),
            'totalRecords' => $total,
            'totalPages' => PaginationHelper::totalPages($total, $pageSize),
        ];
    }

    public static function getModuleList(): array
    {
        $sql = "SELECT DISTINCT module FROM dm_nhat_ky_he_thong WHERE module IS NOT NULL AND module <> '' ORDER BY module";
        return Database::getConnection()->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    }

    /** Xóa các log cũ hơn N ngày. Trả về số dòng bị xóa. */
    public static function purgeOlderThan(int $days): int
    {
        if ($days <= 0) return 0;
        $stmt = Database::getConnection()->prepare("DELETE FROM dm_nhat_ky_he_thong WHERE thoi_gian < DATE_SUB(NOW(), INTERVAL :d DAY)");
        $stmt->bindValue(':d', $days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }
}