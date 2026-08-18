<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../PUBLIC/Entities/BG_BaoGia_PUBLIC.php';

/**
 * BG_QuanLyFile_DAL — Truy vấn danh sách file bản ký đã upload.
 *
 * Không có bảng riêng: file bản ký lưu ngay trên bg_bao_gia
 * (file_ban_ky, ten_file_goc, kich_thuoc_file, ngay_upload_ban_ky).
 * Lớp này chỉ gom các truy vấn phục vụ màn hình quản lý file.
 */
class BG_QuanLyFile_DAL
{
    /** Chỉ lấy báo giá THỰC SỰ có file bản ký */
    private static function selectSql(): string
    {
        return "SELECT bg.id, bg.goi_thau_id, bg.ten_cong_ty, bg.ma_so_thue,
                       bg.email, bg.dien_thoai, bg.trang_thai, bg.tong_tien,
                       bg.file_ban_ky, bg.ten_file_goc, bg.kich_thuoc_file,
                       bg.ngay_upload_ban_ky, bg.ngay_nop, bg.ngay_xac_nhan,
                       bg.nguoi_xac_nhan,
                       gt.so_thong_bao, gt.ten_goi_thau,
                       nd.tai_khoan AS tai_khoan_xac_nhan
                FROM bg_bao_gia bg
                INNER JOIN bg_goi_thau gt ON gt.id = bg.goi_thau_id
                LEFT JOIN dm_nguoi_dung nd ON nd.id = bg.nguoi_xac_nhan";
    }

    /**
     * Danh sách file có phân trang.
     *
     * @param string $sapXep Cột sắp xếp — WHITELIST, không nhận trực tiếp từ input
     */
    public static function getPaged(
        int $page,
        int $pageSize,
        int $goiThauId = 0,
        string $search = '',
        string $loaiFile = '',
        string $sapXep = 'moi_nhat'
    ): array {
        [$page, $pageSize, $offset] = PaginationHelper::normalize($page, $pageSize);

        $where = " WHERE bg.da_xoa = 0 AND gt.da_xoa = 0
                   AND bg.file_ban_ky IS NOT NULL AND bg.file_ban_ky <> '' ";
        $params = [];

        if ($goiThauId > 0) {
            $where .= ' AND bg.goi_thau_id = :gt ';
            $params[':gt'] = $goiThauId;
        }
        if ($search !== '') {
            // KHÔNG reuse placeholder (EMULATE_PREPARES = false)
            $where .= ' AND (bg.ten_cong_ty LIKE :s1 OR bg.ma_so_thue LIKE :s2
                             OR bg.ten_file_goc LIKE :s3 OR bg.file_ban_ky LIKE :s4
                             OR gt.so_thong_bao LIKE :s5) ';
            $like = "%{$search}%";
            $params[':s1'] = $like;
            $params[':s2'] = $like;
            $params[':s3'] = $like;
            $params[':s4'] = $like;
            $params[':s5'] = $like;
        }
        if ($loaiFile === 'pdf') {
            $where .= " AND bg.file_ban_ky LIKE '%.pdf' ";
        } elseif ($loaiFile === 'anh') {
            $where .= " AND (bg.file_ban_ky LIKE '%.jpg' OR bg.file_ban_ky LIKE '%.jpeg'
                             OR bg.file_ban_ky LIKE '%.png') ";
        }

        // Whitelist cột sắp xếp — tên cột KHÔNG BAO GIỜ lấy thẳng từ input (§3B.5)
        $mapSapXep = [
            'moi_nhat'  => 'bg.ngay_upload_ban_ky DESC, bg.id DESC',
            'cu_nhat'   => 'bg.ngay_upload_ban_ky ASC, bg.id ASC',
            'lon_nhat'  => 'bg.kich_thuoc_file DESC, bg.id DESC',
            'ten_cty'   => 'bg.ten_cong_ty ASC, bg.id DESC',
            'goi_thau'  => 'gt.so_thong_bao ASC, bg.ten_cong_ty ASC',
        ];
        $orderBy = $mapSapXep[$sapXep] ?? $mapSapXep['moi_nhat'];

        $pdo = Database::getConnection();

        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM bg_bao_gia bg
                                    INNER JOIN bg_goi_thau gt ON gt.id = bg.goi_thau_id" . $where);
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        // LIMIT/OFFSET đã qua PaginationHelper::normalize() nên ép int an toàn (§3.4)
        $sql = self::selectSql() . $where . " ORDER BY {$orderBy} LIMIT {$pageSize} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return ['data' => $stmt->fetchAll(), 'totalRecords' => $total];
    }

    /** Thống kê tổng quan cho thanh số liệu đầu trang */
    public static function thongKe(int $goiThauId = 0): array
    {
        $where = " WHERE bg.da_xoa = 0 AND gt.da_xoa = 0
                   AND bg.file_ban_ky IS NOT NULL AND bg.file_ban_ky <> '' ";
        $params = [];
        if ($goiThauId > 0) {
            $where .= ' AND bg.goi_thau_id = :gt ';
            $params[':gt'] = $goiThauId;
        }

        $sql = "SELECT
                    COUNT(*) AS tong_file,
                    COALESCE(SUM(bg.kich_thuoc_file), 0) AS tong_dung_luong,
                    SUM(CASE WHEN bg.file_ban_ky LIKE '%.pdf' THEN 1 ELSE 0 END) AS so_pdf,
                    SUM(CASE WHEN bg.file_ban_ky LIKE '%.pdf' THEN 0 ELSE 1 END) AS so_anh,
                    COUNT(DISTINCT bg.goi_thau_id) AS so_goi_thau,
                    COUNT(DISTINCT bg.ma_so_thue) AS so_nha_thau
                FROM bg_bao_gia bg
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

    /** Lấy 1 bản ghi file (kèm thông tin gói thầu) */
    public static function getById(int $baoGiaId): ?array
    {
        $stmt = Database::getConnection()->prepare(
            self::selectSql() . " WHERE bg.id = :id AND bg.da_xoa = 0"
        );
        $stmt->execute([':id' => $baoGiaId]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    /** Toàn bộ tên file đang được DB tham chiếu — để dò file mồ côi trên đĩa */
    public static function tatCaTenFile(): array
    {
        $rows = Database::getConnection()->query(
            "SELECT file_ban_ky FROM bg_bao_gia
             WHERE file_ban_ky IS NOT NULL AND file_ban_ky <> ''"
        )->fetchAll();

        $out = [];
        foreach ($rows as $r) {
            $out[basename((string)$r['file_ban_ky'])] = true;
        }
        return $out;
    }
}
