<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../PUBLIC/Entities/BG_HangHoa_PUBLIC.php';

class BG_HangHoa_DAL
{
    const TABLE = 'bg_hang_hoa';

    private static function selectSql(): string
    {
        return "SELECT hh.* FROM bg_hang_hoa hh";
    }

    public static function insert(BG_HangHoa_PUBLIC $e): int
    {
        $sql = "INSERT INTO bg_hang_hoa
                    (goi_thau_id, ten_phan, stt_theo_phan, stt_thong_bao, ten_hang_hoa,
                     thong_so_ky_thuat, chung_nhan, yeu_cau_xuat_xu, dvt, so_luong,
                     yeu_cau_tro_cu, thu_tu, ngay_tao, ngay_cap_nhat, nguoi_tao, nguoi_cap_nhat, da_xoa)
                VALUES (:gt, :tp, :stp, :stb, :thh, :tskt, :cn, :ycxx, :dvt, :sl,
                        :yctc, :ttu, NOW(), NOW(), :nt1, :nt2, 0)";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute(self::bindParams($e) + [':nt1' => $e->nguoi_tao, ':nt2' => $e->nguoi_tao]);
        return (int)Database::getConnection()->lastInsertId();
    }

    public static function update(BG_HangHoa_PUBLIC $e): int
    {
        $sql = "UPDATE bg_hang_hoa SET
                    goi_thau_id = :gt,
                    ten_phan = :tp,
                    stt_theo_phan = :stp,
                    stt_thong_bao = :stb,
                    ten_hang_hoa = :thh,
                    thong_so_ky_thuat = :tskt,
                    chung_nhan = :cn,
                    yeu_cau_xuat_xu = :ycxx,
                    dvt = :dvt,
                    so_luong = :sl,
                    yeu_cau_tro_cu = :yctc,
                    thu_tu = :ttu,
                    ngay_cap_nhat = NOW(),
                    nguoi_cap_nhat = :ncn
                WHERE id = :id AND da_xoa = 0";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute(self::bindParams($e) + [':ncn' => $e->nguoi_cap_nhat, ':id' => $e->id]);
        return $stmt->rowCount();
    }

    /** Tham số dùng chung cho insert/update */
    private static function bindParams(BG_HangHoa_PUBLIC $e): array
    {
        return [
            ':gt'   => $e->goi_thau_id,
            ':tp'   => $e->ten_phan,
            ':stp'  => $e->stt_theo_phan,
            ':stb'  => $e->stt_thong_bao,
            ':thh'  => $e->ten_hang_hoa,
            ':tskt' => $e->thong_so_ky_thuat,
            ':cn'   => $e->chung_nhan,
            ':ycxx' => $e->yeu_cau_xuat_xu,
            ':dvt'  => $e->dvt,
            ':sl'   => $e->so_luong,
            ':yctc' => $e->yeu_cau_tro_cu,
            ':ttu'  => $e->thu_tu,
        ];
    }

    /**
     * Đếm số nhà thầu ĐÃ CHÀO GIÁ (đơn giá > 0) cho 1 hàng hóa.
     *
     * Dùng để chặn xóa hàng hóa đã có giá: xóa đi thì dòng biến mất khỏi bảng
     * tổng hợp nhưng tiền vẫn nằm trong tổng của nhà thầu → cộng tay không khớp
     * dòng TỔNG CỘNG.
     */
    public static function demBaoGiaDaChao(int $hangHoaId): int
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT COUNT(*) FROM bg_bao_gia_chi_tiet ct
             INNER JOIN bg_bao_gia bg ON bg.id = ct.bao_gia_id
             WHERE ct.hang_hoa_id = :id AND ct.da_xoa = 0 AND ct.don_gia > 0
               AND bg.da_xoa = 0"
        );
        $stmt->execute([':id' => $hangHoaId]);
        return (int)$stmt->fetchColumn();
    }

    public static function softDelete(int $id, int $u): int
    {
        $stmt = Database::getConnection()->prepare(
            "UPDATE bg_hang_hoa SET da_xoa = 1, ngay_cap_nhat = NOW(), nguoi_cap_nhat = :u WHERE id = :id"
        );
        $stmt->execute([':u' => $u, ':id' => $id]);
        return $stmt->rowCount();
    }

    public static function restore(int $id, int $u): int
    {
        $stmt = Database::getConnection()->prepare(
            "UPDATE bg_hang_hoa SET da_xoa = 0, ngay_cap_nhat = NOW(), nguoi_cap_nhat = :u WHERE id = :id"
        );
        $stmt->execute([':u' => $u, ':id' => $id]);
        return $stmt->rowCount();
    }

    /** Xóa mềm toàn bộ hàng hóa của 1 gói thầu — dùng khi import ghi đè */
    public static function softDeleteByGoiThau(int $goiThauId, int $u): int
    {
        $stmt = Database::getConnection()->prepare(
            "UPDATE bg_hang_hoa SET da_xoa = 1, ngay_cap_nhat = NOW(), nguoi_cap_nhat = :u
             WHERE goi_thau_id = :gt AND da_xoa = 0"
        );
        $stmt->execute([':u' => $u, ':gt' => $goiThauId]);
        return $stmt->rowCount();
    }

    public static function getById(int $id): ?BG_HangHoa_PUBLIC
    {
        $stmt = Database::getConnection()->prepare(self::selectSql() . " WHERE hh.id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? Database::hydrate($row, 'BG_HangHoa_PUBLIC') : null;
    }

    /** Toàn bộ hàng hóa của gói thầu — dùng cho form chào giá & export */
    public static function getByGoiThau(int $goiThauId): array
    {
        $stmt = Database::getConnection()->prepare(
            self::selectSql() . " WHERE hh.goi_thau_id = :gt AND hh.da_xoa = 0
                                  ORDER BY hh.thu_tu, hh.id"
        );
        $stmt->execute([':gt' => $goiThauId]);
        return $stmt->fetchAll();
    }

    public static function countByGoiThau(int $goiThauId): int
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT COUNT(*) FROM bg_hang_hoa WHERE goi_thau_id = :gt AND da_xoa = 0"
        );
        $stmt->execute([':gt' => $goiThauId]);
        return (int)$stmt->fetchColumn();
    }

    public static function getPaged(
        int $page,
        int $pageSize,
        int $goiThauId,
        string $search = '',
        int $daXoa = 0
    ): array {
        [$page, $pageSize, $offset] = PaginationHelper::normalize($page, $pageSize);

        $where = ' WHERE hh.da_xoa = :dx ';
        $params = [':dx' => $daXoa];

        if ($goiThauId > 0) {
            $where .= ' AND hh.goi_thau_id = :gt ';
            $params[':gt'] = $goiThauId;
        }
        if ($search !== '') {
            // :s1..:s4 — không reuse placeholder
            $where .= ' AND (hh.ten_hang_hoa LIKE :s1 OR hh.thong_so_ky_thuat LIKE :s2
                             OR hh.ten_phan LIKE :s3 OR hh.stt_theo_phan LIKE :s4) ';
            $like = "%{$search}%";
            $params[':s1'] = $like;
            $params[':s2'] = $like;
            $params[':s3'] = $like;
            $params[':s4'] = $like;
        }

        $stmt = Database::getConnection()->prepare("SELECT COUNT(*) FROM bg_hang_hoa hh" . $where);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $sql = self::selectSql() . $where
             . " ORDER BY hh.thu_tu, hh.id LIMIT {$pageSize} OFFSET {$offset}";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute($params);

        return [
            'data' => $stmt->fetchAll(),
            'totalRecords' => $total,
            'totalPages' => PaginationHelper::totalPages($total, $pageSize),
        ];
    }

    /** Thứ tự lớn nhất hiện có — để thêm dòng mới vào cuối */
    public static function maxThuTu(int $goiThauId): int
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT COALESCE(MAX(thu_tu), 0) FROM bg_hang_hoa WHERE goi_thau_id = :gt AND da_xoa = 0"
        );
        $stmt->execute([':gt' => $goiThauId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Insert nhiều dòng trong 1 câu lệnh — dùng cho import Excel.
     *
     * Placeholder đánh số theo dòng, LUÔN có dấu `_` ngăn giữa tiền tố và số
     * (:thh_0, :thh_1...). Không có dấu ngăn thì `:nt2` + i=0 sẽ tạo ra `:nt20`
     * trùng với `:nt` + i=20 → PDO báo HY093 Invalid parameter number khi lô
     * có từ 21 dòng trở lên. Đã gặp lỗi này khi import file mẫu 27 dòng.
     *
     * @param BG_HangHoa_PUBLIC[] $items
     */
    public static function insertBatch(array $items): int
    {
        if (empty($items)) return 0;

        $cols = '(goi_thau_id, ten_phan, stt_theo_phan, stt_thong_bao, ten_hang_hoa,
                  thong_so_ky_thuat, chung_nhan, yeu_cau_xuat_xu, dvt, so_luong,
                  yeu_cau_tro_cu, thu_tu, ngay_tao, ngay_cap_nhat, nguoi_tao, nguoi_cap_nhat, da_xoa)';

        $rows = [];
        $params = [];
        foreach ($items as $i => $e) {
            $rows[] = "(:gt_{$i}, :tp_{$i}, :stp_{$i}, :stb_{$i}, :thh_{$i}, :tskt_{$i}, :cn_{$i},
                        :ycxx_{$i}, :dvt_{$i}, :sl_{$i}, :yctc_{$i}, :ttu_{$i},
                        NOW(), NOW(), :ntao_{$i}, :ncn_{$i}, 0)";
            $params[":gt_{$i}"]   = $e->goi_thau_id;
            $params[":tp_{$i}"]   = $e->ten_phan;
            $params[":stp_{$i}"]  = $e->stt_theo_phan;
            $params[":stb_{$i}"]  = $e->stt_thong_bao;
            $params[":thh_{$i}"]  = $e->ten_hang_hoa;
            $params[":tskt_{$i}"] = $e->thong_so_ky_thuat;
            $params[":cn_{$i}"]   = $e->chung_nhan;
            $params[":ycxx_{$i}"] = $e->yeu_cau_xuat_xu;
            $params[":dvt_{$i}"]  = $e->dvt;
            $params[":sl_{$i}"]   = $e->so_luong;
            $params[":yctc_{$i}"] = $e->yeu_cau_tro_cu;
            $params[":ttu_{$i}"]  = $e->thu_tu;
            $params[":ntao_{$i}"] = $e->nguoi_tao;
            $params[":ncn_{$i}"]  = $e->nguoi_tao;
        }

        $sql = "INSERT INTO bg_hang_hoa {$cols} VALUES " . implode(',', $rows);
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
}
