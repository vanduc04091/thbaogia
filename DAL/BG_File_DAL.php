<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../PUBLIC/Entities/BG_File_PUBLIC.php';

/**
 * BG_File_DAL — Truy cập bảng `bg_file`.
 *
 * Bảng lưu MỌI file người dùng tải lên. Bảng nghiệp vụ (bg_bao_gia) chỉ giữ
 * id trỏ sang đây, nên thêm loại file mới không phải sửa bảng nghiệp vụ.
 */
class BG_File_DAL
{
    const TABLE = 'bg_file';

    public static function insert(BG_File_PUBLIC $e): int
    {
        $sql = "INSERT INTO bg_file
                    (ten_file, ten_file_goc, duong_dan, loai_file, mime_type, kich_thuoc,
                     nhom_file, ngay_tao, ngay_cap_nhat, nguoi_tao, nguoi_cap_nhat, da_xoa)
                VALUES (:tf, :tfg, :dd, :loai, :mime, :kt, :nhom, NOW(), NOW(), :nt1, :nt2, 0)";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([
            ':tf'   => $e->ten_file,
            ':tfg'  => $e->ten_file_goc,
            ':dd'   => $e->duong_dan,
            ':loai' => $e->loai_file,
            ':mime' => $e->mime_type,
            ':kt'   => $e->kich_thuoc,
            ':nhom' => $e->nhom_file,
            ':nt1'  => $e->nguoi_tao,
            ':nt2'  => $e->nguoi_tao,
        ]);
        return (int)Database::getConnection()->lastInsertId();
    }

    public static function getById(int $id): ?BG_File_PUBLIC
    {
        if ($id <= 0) return null;
        $stmt = Database::getConnection()->prepare(
            "SELECT * FROM bg_file WHERE id = :id AND da_xoa = 0"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? Database::hydrate($row, 'BG_File_PUBLIC') : null;
    }

    /** Soft delete — giữ bản ghi để còn tra được lịch sử */
    public static function softDelete(int $id, int $u): int
    {
        $stmt = Database::getConnection()->prepare(
            "UPDATE bg_file SET da_xoa = 1, ngay_cap_nhat = NOW(), nguoi_cap_nhat = :u WHERE id = :id"
        );
        $stmt->execute([':u' => $u, ':id' => $id]);
        return $stmt->rowCount();
    }

    /**
     * Danh sách file bản ký kèm thông tin báo giá / gói thầu, có phân trang.
     *
     * @param string $sapXep WHITELIST — không nhận tên cột trực tiếp từ input
     */
    public static function getPagedBanKy(
        int $page,
        int $pageSize,
        int $goiThauId = 0,
        string $search = '',
        string $loaiFile = '',
        string $sapXep = 'moi_nhat'
    ): array {
        [$page, $pageSize, $offset] = PaginationHelper::normalize($page, $pageSize);

        $where = " WHERE f.da_xoa = 0 AND bg.da_xoa = 0 AND gt.da_xoa = 0 ";
        $params = [];

        if ($goiThauId > 0) {
            $where .= ' AND bg.goi_thau_id = :gt ';
            $params[':gt'] = $goiThauId;
        }
        if ($search !== '') {
            // KHÔNG reuse named placeholder (EMULATE_PREPARES = false)
            $where .= ' AND (bg.ten_cong_ty LIKE :s1 OR bg.ma_so_thue LIKE :s2
                             OR f.ten_file LIKE :s3 OR f.ten_file_goc LIKE :s4
                             OR gt.so_thong_bao LIKE :s5) ';
            $like = "%{$search}%";
            $params[':s1'] = $like;
            $params[':s2'] = $like;
            $params[':s3'] = $like;
            $params[':s4'] = $like;
            $params[':s5'] = $like;
        }
        if ($loaiFile === 'pdf') {
            $where .= " AND f.loai_file = 'pdf' ";
        } elseif ($loaiFile === 'anh') {
            $where .= " AND f.loai_file IN ('jpg','jpeg','png') ";
        }

        $mapSapXep = [
            'moi_nhat' => 'f.ngay_tao DESC, f.id DESC',
            'cu_nhat'  => 'f.ngay_tao ASC, f.id ASC',
            'lon_nhat' => 'f.kich_thuoc DESC, f.id DESC',
            'ten_cty'  => 'bg.ten_cong_ty ASC, f.id DESC',
            'goi_thau' => 'gt.so_thong_bao ASC, bg.ten_cong_ty ASC',
        ];
        $orderBy = $mapSapXep[$sapXep] ?? $mapSapXep['moi_nhat'];

        $from = " FROM bg_file f
                  INNER JOIN bg_bao_gia bg ON bg.file_ban_ky_id = f.id
                  INNER JOIN bg_goi_thau gt ON gt.id = bg.goi_thau_id ";

        $pdo = Database::getConnection();

        $stmtCount = $pdo->prepare("SELECT COUNT(*) " . $from . $where);
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        // LIMIT/OFFSET đã qua PaginationHelper::normalize() nên an toàn (§3.4)
        $sql = "SELECT f.*,
                       bg.id AS bao_gia_id, bg.goi_thau_id, bg.ten_cong_ty, bg.ma_so_thue,
                       bg.trang_thai, bg.tong_tien, bg.nguoi_xac_nhan,
                       gt.so_thong_bao, gt.ten_goi_thau"
             . $from . $where . " ORDER BY {$orderBy} LIMIT {$pageSize} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return ['data' => $stmt->fetchAll(), 'totalRecords' => $total];
    }

    /** Thống kê file bản ký */
    public static function thongKeBanKy(int $goiThauId = 0): array
    {
        $where = " WHERE f.da_xoa = 0 AND bg.da_xoa = 0 AND gt.da_xoa = 0 ";
        $params = [];
        if ($goiThauId > 0) {
            $where .= ' AND bg.goi_thau_id = :gt ';
            $params[':gt'] = $goiThauId;
        }

        $sql = "SELECT
                    COUNT(*) AS tong_file,
                    COALESCE(SUM(f.kich_thuoc), 0) AS tong_dung_luong,
                    SUM(CASE WHEN f.loai_file = 'pdf' THEN 1 ELSE 0 END) AS so_pdf,
                    SUM(CASE WHEN f.loai_file = 'pdf' THEN 0 ELSE 1 END) AS so_anh,
                    COUNT(DISTINCT bg.goi_thau_id) AS so_goi_thau,
                    COUNT(DISTINCT bg.ma_so_thue) AS so_nha_thau
                FROM bg_file f
                INNER JOIN bg_bao_gia bg ON bg.file_ban_ky_id = f.id
                INNER JOIN bg_goi_thau gt ON gt.id = bg.goi_thau_id" . $where;

        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute($params);
        $r = $stmt->fetch() ?: [];

        return [
            'tong_file'       => (int)($r['tong_file'] ?? 0),
            'tong_dung_luong' => (int)($r['tong_dung_luong'] ?? 0),
            'so_pdf'          => (int)($r['so_pdf'] ?? 0),
            'so_anh'          => (int)($r['so_anh'] ?? 0),
            'so_goi_thau'     => (int)($r['so_goi_thau'] ?? 0),
            'so_nha_thau'     => (int)($r['so_nha_thau'] ?? 0),
        ];
    }

    /** 1 dòng file kèm thông tin báo giá — dùng cho màn hình chi tiết */
    public static function getBanKyByBaoGia(int $baoGiaId): ?array
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT f.*,
                    bg.id AS bao_gia_id, bg.goi_thau_id, bg.ten_cong_ty, bg.ma_so_thue,
                    bg.trang_thai, bg.nguoi_xac_nhan,
                    gt.so_thong_bao, gt.ten_goi_thau
             FROM bg_file f
             INNER JOIN bg_bao_gia bg ON bg.file_ban_ky_id = f.id
             INNER JOIN bg_goi_thau gt ON gt.id = bg.goi_thau_id
             WHERE bg.id = :id AND f.da_xoa = 0 AND bg.da_xoa = 0"
        );
        $stmt->execute([':id' => $baoGiaId]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    /**
     * Mọi tên file đang được tham chiếu (kể cả bản ghi đã soft delete),
     * để dò file mồ côi trên đĩa mà không xóa nhầm file còn tra cứu được.
     */
    public static function tatCaTenFile(): array
    {
        $rows = Database::getConnection()->query(
            "SELECT ten_file FROM bg_file"
        )->fetchAll();

        $out = [];
        foreach ($rows as $r) {
            $out[basename((string)$r['ten_file'])] = true;
        }
        return $out;
    }

    /** Đếm số báo giá đang trỏ tới 1 file (chặn xóa file còn dùng) */
    public static function demBaoGiaDungFile(int $fileId): int
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT COUNT(*) FROM bg_bao_gia WHERE file_ban_ky_id = :fid AND da_xoa = 0"
        );
        $stmt->execute([':fid' => $fileId]);
        return (int)$stmt->fetchColumn();
    }
}
