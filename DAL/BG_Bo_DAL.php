<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../PUBLIC/Entities/BG_Bo_PUBLIC.php';

class BG_Bo_DAL
{
    const TABLE = 'bg_bo';

    private static function selectSql(): string
    {
        return "SELECT b.*,
                       (SELECT COUNT(*) FROM bg_hang_hoa hh
                         WHERE hh.bo_id = b.id AND hh.da_xoa = 0) AS so_chi_tiet
                FROM bg_bo b";
    }

    public static function getById(int $id): ?BG_Bo_PUBLIC
    {
        $stmt = Database::getConnection()->prepare(self::selectSql() . " WHERE b.id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? Database::hydrate($row, BG_Bo_PUBLIC::class) : null;
    }

    /** Tất cả bộ của 1 gói thầu, theo đúng thứ tự in ở Phụ lục III */
    public static function getByGoiThau(int $goiThauId): array
    {
        $stmt = Database::getConnection()->prepare(
            self::selectSql() . " WHERE b.goi_thau_id = :gt AND b.da_xoa = 0
                                  ORDER BY b.thu_tu, b.stt_bo, b.id"
        );
        $stmt->execute([':gt' => $goiThauId]);
        return $stmt->fetchAll();
    }

    /**
     * Số thứ tự lớn nhất trong các mã bộ dạng BOxxx của 1 gói thầu.
     *
     * Dùng để sinh mã tiếp theo khi bên mời không tự đặt. Chỉ xét mã đúng
     * khuôn BO + số; mã do người dùng tự đặt (BDC01, HT02...) không đụng tới,
     * nên sinh mã mới không bao giờ ghi đè lên mã có sẵn.
     */
    public static function soThuTuMaLonNhat(int $goiThauId): int
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT ma_bo FROM bg_bo
             WHERE goi_thau_id = :gt AND da_xoa = 0 AND ma_bo REGEXP '^BO[0-9]+$'"
        );
        $stmt->execute([':gt' => $goiThauId]);

        $max = 0;
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $ma) {
            $n = (int)substr((string)$ma, 2);
            if ($n > $max) $max = $n;
        }
        return $max;
    }

    /** Mã bộ này đã có trong gói thầu chưa? (bỏ qua chính bộ $trừId) */
    public static function maTonTai(int $goiThauId, string $maBo, int $truId = 0): bool
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT COUNT(*) FROM bg_bo
             WHERE goi_thau_id = :gt AND da_xoa = 0
               AND UPPER(ma_bo) = UPPER(:ma) AND id <> :tru"
        );
        $stmt->execute([':gt' => $goiThauId, ':ma' => $maBo, ':tru' => $truId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function insert(BG_Bo_PUBLIC $e): int
    {
        $sql = "INSERT INTO bg_bo
                    (goi_thau_id, ma_bo, stt_bo, ten_bo, yeu_cau_chung, yeu_cau_khac,
                     yeu_cau_cau_hinh, nhom_nuoc, dvt, so_luong, thu_tu,
                     ngay_tao, ngay_cap_nhat, nguoi_tao, nguoi_cap_nhat, da_xoa)
                VALUES (:gt, :ma, :stt, :ten, :ycc, :yck, :ycch, :nn, :dvt, :sl, :ttu,
                        NOW(), NOW(), :nt1, :nt2, 0)";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([
            ':gt'   => $e->goi_thau_id,
            ':ma'   => $e->ma_bo,
            ':stt'  => $e->stt_bo,
            ':ten'  => $e->ten_bo,
            ':ycc'  => $e->yeu_cau_chung,
            ':yck'  => $e->yeu_cau_khac,
            ':ycch' => $e->yeu_cau_cau_hinh,
            ':nn'   => $e->nhom_nuoc,
            ':dvt'  => $e->dvt,
            ':sl'   => $e->so_luong,
            ':ttu'  => $e->thu_tu,
            ':nt1'  => $e->nguoi_tao,
            ':nt2'  => $e->nguoi_tao,
        ]);
        return (int)Database::getConnection()->lastInsertId();
    }

    public static function update(BG_Bo_PUBLIC $e): int
    {
        $sql = "UPDATE bg_bo SET
                    ma_bo = :ma,
                    stt_bo = :stt,
                    ten_bo = :ten,
                    yeu_cau_chung = :ycc,
                    yeu_cau_khac = :yck,
                    yeu_cau_cau_hinh = :ycch,
                    nhom_nuoc = :nn,
                    dvt = :dvt,
                    so_luong = :sl,
                    thu_tu = :ttu,
                    ngay_cap_nhat = NOW(),
                    nguoi_cap_nhat = :ncn
                WHERE id = :id AND da_xoa = 0";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([
            ':ma'   => $e->ma_bo,
            ':stt'  => $e->stt_bo,
            ':ten'  => $e->ten_bo,
            ':ycc'  => $e->yeu_cau_chung,
            ':yck'  => $e->yeu_cau_khac,
            ':ycch' => $e->yeu_cau_cau_hinh,
            ':nn'   => $e->nhom_nuoc,
            ':dvt'  => $e->dvt,
            ':sl'   => $e->so_luong,
            ':ttu'  => $e->thu_tu,
            ':ncn'  => $e->nguoi_cap_nhat,
            ':id'   => $e->id,
        ]);
        return $stmt->rowCount();
    }

    public static function softDelete(int $id, int $u): int
    {
        $stmt = Database::getConnection()->prepare(
            "UPDATE bg_bo SET da_xoa = 1, ngay_cap_nhat = NOW(), nguoi_cap_nhat = :u
             WHERE id = :id"
        );
        $stmt->execute([':u' => $u, ':id' => $id]);
        return $stmt->rowCount();
    }

    /**
     * Xóa HẲN mọi bộ của 1 gói thầu — dùng khi import ghi đè danh mục.
     * Hàng hóa chi tiết do BG_HangHoa_DAL xóa riêng (gọi trước hàm này).
     */
    public static function deleteByGoiThau(int $goiThauId): int
    {
        $stmt = Database::getConnection()->prepare(
            "DELETE FROM bg_bo WHERE goi_thau_id = :gt"
        );
        $stmt->execute([':gt' => $goiThauId]);
        return $stmt->rowCount();
    }

    /**
     * Chèn nhiều bộ 1 lần, trả về mảng id theo ĐÚNG thứ tự truyền vào —
     * import cần id ngay để gán cho hàng hóa chi tiết bên dưới.
     *
     * MariaDB cấp id liên tiếp cho 1 câu INSERT nhiều VALUES, nhưng chỉ
     * đúng khi innodb_autoinc_lock_mode != 2 (interleaved). Không dựa vào
     * giả định đó: chèn từng dòng, lấy lastInsertId() chắc chắn đúng.
     * Số bộ trong 1 gói thường vài chục nên chi phí không đáng kể.
     *
     * @param BG_Bo_PUBLIC[] $items
     * @return int[] id theo thứ tự
     */
    public static function insertNhieu(array $items): array
    {
        $ids = [];
        foreach ($items as $e) {
            $ids[] = self::insert($e);
        }
        return $ids;
    }
}
