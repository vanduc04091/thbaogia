<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../PUBLIC/Entities/BG_BaoGiaBo_PUBLIC.php';

/**
 * BG_BaoGiaBo_DAL — giá nhà thầu chào cho cả BỘ (bảng bg_bao_gia_bo).
 *
 * Chỉ dùng cho 2 nhóm mua theo bộ. Xem BG_BaoGiaBo_PUBLIC để biết vì sao
 * tách bảng riêng thay vì nhét vào bg_bao_gia_chi_tiet.
 */
class BG_BaoGiaBo_DAL
{
    /**
     * Các dòng giá bộ của 1 báo giá, kèm thông tin bộ của bên mời.
     * Sắp xếp cùng thứ tự với cây bộ ở getChiTiet() để 2 bảng khớp nhau.
     */
    public static function getByBaoGia(int $baoGiaId): array
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT gb.*, b.ma_bo, b.stt_bo, b.ten_bo, b.dvt AS dvt_bo,
                    b.so_luong AS so_luong_bo, b.thu_tu
             FROM bg_bao_gia_bo gb
             INNER JOIN bg_bo b ON b.id = gb.bo_id AND b.da_xoa = 0
             WHERE gb.bao_gia_id = :bg AND gb.da_xoa = 0
             ORDER BY b.thu_tu, b.stt_bo, b.id"
        );
        $stmt->execute([':bg' => $baoGiaId]);
        return $stmt->fetchAll();
    }

    /** Map bo_id => dòng giá bộ, cho tra cứu nhanh khi dựng cây / tổng hợp */
    public static function getMap(int $baoGiaId): array
    {
        $map = [];
        foreach (self::getByBaoGia($baoGiaId) as $r) {
            $map[(int)$r['bo_id']] = $r;
        }
        return $map;
    }

    /**
     * Map cho NHIỀU báo giá cùng lúc: [bao_gia_id][bo_id] => dòng.
     * Bảng tổng hợp cần giá bộ của mọi nhà thầu — gọi getMap() trong vòng
     * lặp sẽ thành N+1 truy vấn.
     *
     * @param int[] $baoGiaIds
     */
    public static function getMapNhieuBaoGia(array $baoGiaIds): array
    {
        $ids = array_values(array_filter(array_map('intval', $baoGiaIds)));
        if (!$ids) return [];

        // Placeholder đánh số CÓ DẤU NGĂN — :bg_0, :bg_1... (§3.3)
        $ph = [];
        $bind = [];
        foreach ($ids as $i => $id) {
            $ph[] = ':bg_' . $i;
            $bind[':bg_' . $i] = $id;
        }

        $stmt = Database::getConnection()->prepare(
            "SELECT gb.*, b.ma_bo, b.stt_bo, b.ten_bo, b.dvt AS dvt_bo,
                    b.so_luong AS so_luong_bo
             FROM bg_bao_gia_bo gb
             INNER JOIN bg_bo b ON b.id = gb.bo_id AND b.da_xoa = 0
             WHERE gb.bao_gia_id IN (" . implode(',', $ph) . ") AND gb.da_xoa = 0"
        );
        $stmt->execute($bind);

        $out = [];
        foreach ($stmt->fetchAll() as $r) {
            $out[(int)$r['bao_gia_id']][(int)$r['bo_id']] = $r;
        }
        return $out;
    }

    /**
     * Ghi 1 dòng giá bộ (upsert theo UNIQUE(bao_gia_id, bo_id)).
     *
     * thanh_tien lấy NGUYÊN giá trị nhà thầu nhập — không tự tính lại.
     */
    public static function upsert(BG_BaoGiaBo_PUBLIC $e): void
    {
        $sql = "INSERT INTO bg_bao_gia_bo
                    (bao_gia_id, bo_id,
                     ten_thuong_mai, model, hang_san_xuat, nam_san_xuat, xuat_xu,
                     don_gia, thanh_tien,
                     dap_ung_chung, khong_dat_chung, dap_ung_khac, khong_dat_khac,
                     dap_ung_cau_hinh, khong_dat_cau_hinh,
                     thong_so_chao_gia, diem_khong_dat,
                     dap_ung_nhom_nuoc, khong_dat_nhom_nuoc, tai_lieu_chung_minh,
                     ngay_tao, ngay_cap_nhat, da_xoa)
                VALUES (:bg, :bo, :ttm, :md, :hsx, :nsx, :xx, :dg, :tt,
                        :duc, :kdc, :duk, :kdk, :duch, :kdch, :tsc, :dkd,
                        :dunn, :kdnn, :tlcm, NOW(), NOW(), 0)
                ON DUPLICATE KEY UPDATE
                    ten_thuong_mai = VALUES(ten_thuong_mai),
                    model          = VALUES(model),
                    hang_san_xuat  = VALUES(hang_san_xuat),
                    nam_san_xuat   = VALUES(nam_san_xuat),
                    xuat_xu        = VALUES(xuat_xu),
                    don_gia        = VALUES(don_gia),
                    thanh_tien     = VALUES(thanh_tien),
                    dap_ung_chung       = VALUES(dap_ung_chung),
                    khong_dat_chung     = VALUES(khong_dat_chung),
                    dap_ung_khac        = VALUES(dap_ung_khac),
                    khong_dat_khac      = VALUES(khong_dat_khac),
                    dap_ung_cau_hinh    = VALUES(dap_ung_cau_hinh),
                    khong_dat_cau_hinh  = VALUES(khong_dat_cau_hinh),
                    thong_so_chao_gia   = VALUES(thong_so_chao_gia),
                    diem_khong_dat      = VALUES(diem_khong_dat),
                    dap_ung_nhom_nuoc   = VALUES(dap_ung_nhom_nuoc),
                    khong_dat_nhom_nuoc = VALUES(khong_dat_nhom_nuoc),
                    tai_lieu_chung_minh = VALUES(tai_lieu_chung_minh),
                    ngay_cap_nhat  = NOW(),
                    da_xoa         = 0";

        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([
            ':bg'   => $e->bao_gia_id,
            ':bo'   => $e->bo_id,
            ':ttm'  => $e->ten_thuong_mai,
            ':md'   => $e->model,
            ':hsx'  => $e->hang_san_xuat,
            ':nsx'  => $e->nam_san_xuat,
            ':xx'   => $e->xuat_xu,
            ':dg'   => $e->don_gia,
            ':tt'   => $e->thanh_tien,
            ':duc'  => $e->dap_ung_chung,
            ':kdc'  => $e->khong_dat_chung,
            ':duk'  => $e->dap_ung_khac,
            ':kdk'  => $e->khong_dat_khac,
            ':duch' => $e->dap_ung_cau_hinh,
            ':kdch' => $e->khong_dat_cau_hinh,
            ':tsc'  => $e->thong_so_chao_gia,
            ':dkd'  => $e->diem_khong_dat,
            ':dunn' => $e->dap_ung_nhom_nuoc,
            ':kdnn' => $e->khong_dat_nhom_nuoc,
            ':tlcm' => $e->tai_lieu_chung_minh,
        ]);
    }

    /** Xóa hẳn giá bộ của 1 báo giá — dùng khi xóa vĩnh viễn báo giá */
    public static function deleteByBaoGia(int $baoGiaId): int
    {
        $stmt = Database::getConnection()->prepare(
            "DELETE FROM bg_bao_gia_bo WHERE bao_gia_id = :bg"
        );
        $stmt->execute([':bg' => $baoGiaId]);
        return $stmt->rowCount();
    }

    /** Số bộ đã được chào giá (đơn giá > 0) của 1 báo giá */
    public static function demDaChao(int $baoGiaId): int
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT COUNT(*) FROM bg_bao_gia_bo
              WHERE bao_gia_id = :bg AND da_xoa = 0 AND don_gia > 0"
        );
        $stmt->execute([':bg' => $baoGiaId]);
        return (int)$stmt->fetchColumn();
    }
}
