<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../PUBLIC/Entities/BG_BaoGia_PUBLIC.php';
require_once __DIR__ . '/../PUBLIC/Entities/BG_BaoGiaChiTiet_PUBLIC.php';

class BG_BaoGia_DAL
{
    const TABLE = 'bg_bao_gia';

    private static function selectSql(): string
    {
        // JOIN bg_file để lấy kèm thông tin bản ký — GUI dùng thẳng
        // ten_file / ten_file_goc / kich_thuoc_file / ngay_upload_ban_ky
        // như trước, không phải truy vấn thêm.
        return "SELECT bg.*, gt.so_thong_bao, gt.ten_goi_thau,
                       nd.tai_khoan AS tai_khoan_xac_nhan,
                       f.ten_file AS file_ban_ky,
                       f.ten_file_goc,
                       f.kich_thuoc AS kich_thuoc_file,
                       f.loai_file,
                       f.ngay_tao AS ngay_upload_ban_ky,
                       fc.ten_file_goc AS ten_file_catalog,
                       fc.ngay_tao AS ngay_upload_catalog,
                       fx.ten_file_goc AS ten_file_catalog_excel,
                       fx.ngay_tao AS ngay_upload_catalog_excel,
                       (SELECT COUNT(*) FROM bg_bao_gia_chi_tiet ct
                         WHERE ct.bao_gia_id = bg.id AND ct.da_xoa = 0
                           AND ct.don_gia > 0) AS so_dong_chao
                FROM bg_bao_gia bg
                LEFT JOIN bg_goi_thau gt ON gt.id = bg.goi_thau_id
                LEFT JOIN dm_nguoi_dung nd ON nd.id = bg.nguoi_xac_nhan
                LEFT JOIN bg_file f  ON f.id  = bg.file_ban_ky_id AND f.da_xoa = 0
                LEFT JOIN bg_file fc ON fc.id = bg.file_catalog_id AND fc.da_xoa = 0
                LEFT JOIN bg_file fx ON fx.id = bg.file_catalog_excel_id AND fx.da_xoa = 0";
    }

    public static function insert(BG_BaoGia_PUBLIC $e): int
    {
        $sql = "INSERT INTO bg_bao_gia
                    (goi_thau_id, ten_cong_ty, ma_so_thue, email, dien_thoai, dia_chi,
                     hieu_luc_bao_gia, ghi_chu, trang_thai, ngay_nop, tong_tien, ip_nop,
                     ngay_tao, ngay_cap_nhat, nguoi_tao, nguoi_cap_nhat, da_xoa)
                VALUES (:gt, :tcty, :mst, :em, :dt, :dc, :hl, :gc, :tt, :nn, :tong, :ip,
                        NOW(), NOW(), :nt1, :nt2, 0)";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([
            ':gt'   => $e->goi_thau_id,
            ':tcty' => $e->ten_cong_ty,
            ':mst'  => $e->ma_so_thue,
            ':em'   => $e->email,
            ':dt'   => $e->dien_thoai,
            ':dc'   => $e->dia_chi,
            ':hl'   => $e->hieu_luc_bao_gia,
            ':gc'   => $e->ghi_chu,
            ':tt'   => $e->trang_thai,
            ':nn'   => $e->ngay_nop,
            ':tong' => $e->tong_tien,
            ':ip'   => $e->ip_nop,
            ':nt1'  => $e->nguoi_tao,
            ':nt2'  => $e->nguoi_tao,
        ]);
        return (int)Database::getConnection()->lastInsertId();
    }

    public static function update(BG_BaoGia_PUBLIC $e): int
    {
        $sql = "UPDATE bg_bao_gia SET
                    ten_cong_ty = :tcty,
                    ma_so_thue = :mst,
                    email = :em,
                    dien_thoai = :dt,
                    dia_chi = :dc,
                    hieu_luc_bao_gia = :hl,
                    ghi_chu = :gc,
                    ngay_cap_nhat = NOW(),
                    nguoi_cap_nhat = :ncn
                WHERE id = :id AND da_xoa = 0";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([
            ':tcty' => $e->ten_cong_ty,
            ':mst'  => $e->ma_so_thue,
            ':em'   => $e->email,
            ':dt'   => $e->dien_thoai,
            ':dc'   => $e->dia_chi,
            ':hl'   => $e->hieu_luc_bao_gia,
            ':gc'   => $e->ghi_chu,
            ':ncn'  => $e->nguoi_cap_nhat,
            ':id'   => $e->id,
        ]);
        return $stmt->rowCount();
    }

    /** Xác nhận đã nhận bản giấy / từ chối */
    public static function updateXacNhan(int $id, int $trangThai, ?string $lyDo, int $u): int
    {
        $sql = "UPDATE bg_bao_gia SET
                    trang_thai = :tt,
                    ly_do_tu_choi = :ld,
                    ngay_xac_nhan = CASE WHEN :tt2 = 0 THEN NULL ELSE NOW() END,
                    nguoi_xac_nhan = CASE WHEN :tt3 = 0 THEN NULL ELSE :u1 END,
                    ngay_cap_nhat = NOW(),
                    nguoi_cap_nhat = :u2
                WHERE id = :id AND da_xoa = 0";
        $stmt = Database::getConnection()->prepare($sql);
        // Không reuse placeholder: :tt/:tt2/:tt3 và :u1/:u2 là các tên riêng
        $stmt->execute([
            ':tt'  => $trangThai,
            ':tt2' => $trangThai,
            ':tt3' => $trangThai,
            ':ld'  => $trangThai === BG_BaoGia_PUBLIC::TT_TU_CHOI ? $lyDo : null,
            ':u1'  => $u,
            ':u2'  => $u,
            ':id'  => $id,
        ]);
        return $stmt->rowCount();
    }

    /**
     * Lưu file bản ký + tự chuyển sang ĐÃ XÁC NHẬN trong CÙNG một câu lệnh.
     *
     * Gộp 2 việc vào 1 UPDATE để không có trạng thái nửa vời (file đã lưu mà
     * trạng thái chưa đổi, hoặc ngược lại).
     * `nguoi_xac_nhan` = NULL vì đây là nhà thầu tự xác nhận bằng bản ký,
     * không phải nhân viên bên mời tích tay.
     */
    /**
     * Gán file catalog (Bước 5). KHÔNG đụng tới trạng thái báo giá — catalog
     * chỉ là tài liệu chứng minh, việc xác nhận vẫn do bản ký quyết định.
     */
    public static function updateCatalog(int $id, ?int $fileId): int
    {
        $sql = "UPDATE bg_bao_gia SET
                    file_catalog_id = :fid,
                    ngay_cap_nhat = NOW()
                WHERE id = :id AND da_xoa = 0";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([':fid' => $fileId, ':id' => $id]);
        return $stmt->rowCount();
    }

    /** Gán file Excel chỉ dẫn vị trí tài liệu (Bước 5) */
    public static function updateCatalogExcel(int $id, ?int $fileId): int
    {
        $sql = "UPDATE bg_bao_gia SET
                    file_catalog_excel_id = :fid,
                    ngay_cap_nhat = NOW()
                WHERE id = :id AND da_xoa = 0";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([':fid' => $fileId, ':id' => $id]);
        return $stmt->rowCount();
    }

    /** Đánh dấu nhà thầu đã hoàn thành toàn bộ 5 bước → khóa sửa */
    public static function updateHoanThanh(int $id): int
    {
        // Chot xong 5 buoc MOI chuyen sang "Da xac nhan".
        // nguoi_xac_nhan = NULL de phan biet nha thau tu ky (10.2).
        $sql = "UPDATE bg_bao_gia SET
                    da_hoan_thanh = 1,
                    ngay_hoan_thanh = NOW(),
                    trang_thai = :tt,
                    ngay_xac_nhan = NOW(),
                    nguoi_xac_nhan = NULL,
                    ly_do_tu_choi = NULL,
                    ngay_cap_nhat = NOW()
                WHERE id = :id AND da_xoa = 0";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([':tt' => BG_BaoGia_PUBLIC::TT_DA_XAC_NHAN, ':id' => $id]);
        return $stmt->rowCount();
    }

    /**
     * Gan file ban ky (Buoc 4).
     *
     * KHONG dat trang thai "Da xac nhan" o day nua — nha thau con Buoc 5
     * (chi dan vi tri tai lieu). Chi khi bam HOAN THANH o cuoi Buoc 5 thi
     * bao gia moi chuyen sang "Da xac nhan" (xem updateHoanThanh).
     */
    public static function updateBanKy(int $id, int $fileId): int
    {
        $sql = "UPDATE bg_bao_gia SET
                    file_ban_ky_id = :fid,
                    ly_do_tu_choi = NULL,
                    ngay_cap_nhat = NOW()
                WHERE id = :id AND da_xoa = 0";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([':fid' => $fileId, ':id' => $id]);
        return $stmt->rowCount();
    }

    /** Gỡ file bản ký (khi upload đè thì xóa file cũ ở BUS) */
    public static function xoaBanKy(int $id, int $u): int
    {
        // Chỉ gỡ liên kết; bản ghi trong bg_file do BUS xử lý riêng
        $sql = "UPDATE bg_bao_gia SET
                    file_ban_ky_id = NULL,
                    ngay_cap_nhat = NOW(),
                    nguoi_cap_nhat = :u
                WHERE id = :id AND da_xoa = 0";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([':u' => $u, ':id' => $id]);
        return $stmt->rowCount();
    }

    public static function updateTongTien(int $id): void
    {
        $stmt = Database::getConnection()->prepare(
            "UPDATE bg_bao_gia bg
             SET bg.tong_tien = COALESCE((
                    SELECT SUM(ct.thanh_tien) FROM bg_bao_gia_chi_tiet ct
                     WHERE ct.bao_gia_id = bg.id AND ct.da_xoa = 0), 0)
             WHERE bg.id = :id"
        );
        $stmt->execute([':id' => $id]);
    }

    public static function markNop(int $id): int
    {
        $stmt = Database::getConnection()->prepare(
            "UPDATE bg_bao_gia SET ngay_nop = NOW(), ngay_cap_nhat = NOW() WHERE id = :id AND da_xoa = 0"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount();
    }

    public static function softDelete(int $id, int $u): int
    {
        $stmt = Database::getConnection()->prepare(
            "UPDATE bg_bao_gia SET da_xoa = 1, ngay_cap_nhat = NOW(), nguoi_cap_nhat = :u WHERE id = :id"
        );
        $stmt->execute([':u' => $u, ':id' => $id]);
        return $stmt->rowCount();
    }

    public static function restore(int $id, int $u): int
    {
        $stmt = Database::getConnection()->prepare(
            "UPDATE bg_bao_gia SET da_xoa = 0, ngay_cap_nhat = NOW(), nguoi_cap_nhat = :u WHERE id = :id"
        );
        $stmt->execute([':u' => $u, ':id' => $id]);
        return $stmt->rowCount();
    }

    public static function delete(int $id): int
    {
        $stmt = Database::getConnection()->prepare("DELETE FROM bg_bao_gia WHERE id = :id AND da_xoa = 1");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount();
    }

    public static function getById(int $id): ?BG_BaoGia_PUBLIC
    {
        $stmt = Database::getConnection()->prepare(self::selectSql() . " WHERE bg.id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? Database::hydrate($row, 'BG_BaoGia_PUBLIC') : null;
    }

    /**
     * @param int $coBanKy Lọc theo bản ký: -1 = tất cả, 1 = đã có, 0 = chưa có
     */
    public static function getPaged(
        int $page,
        int $pageSize,
        int $goiThauId = 0,
        string $search = '',
        int $trangThai = -1,
        int $daXoa = 0,
        int $coBanKy = -1
    ): array {
        [$page, $pageSize, $offset] = PaginationHelper::normalize($page, $pageSize);

        $where = ' WHERE bg.da_xoa = :dx ';
        $params = [':dx' => $daXoa];

        if ($goiThauId > 0) {
            $where .= ' AND bg.goi_thau_id = :gt ';
            $params[':gt'] = $goiThauId;
        }
        if ($search !== '') {
            // Tìm cả theo tên file bản ký để tra nhanh khi cầm tờ giấy trên tay
            $where .= ' AND (bg.ten_cong_ty LIKE :s1 OR bg.ma_so_thue LIKE :s2
                             OR bg.email LIKE :s3 OR f.ten_file_goc LIKE :s4) ';
            $like = "%{$search}%";
            $params[':s1'] = $like;
            $params[':s2'] = $like;
            $params[':s3'] = $like;
            $params[':s4'] = $like;
        }
        if ($trangThai >= 0) {
            $where .= ' AND bg.trang_thai = :tt ';
            $params[':tt'] = $trangThai;
        }
        if ($coBanKy === 1) {
            $where .= ' AND bg.file_ban_ky_id IS NOT NULL ';
        } elseif ($coBanKy === 0) {
            $where .= ' AND bg.file_ban_ky_id IS NULL ';
        }

        $countSql = "SELECT COUNT(*) FROM bg_bao_gia bg" . $where;
        $stmt = Database::getConnection()->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $sql = self::selectSql() . $where . " ORDER BY bg.id DESC LIMIT {$pageSize} OFFSET {$offset}";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute($params);

        return [
            'data' => $stmt->fetchAll(),
            'totalRecords' => $total,
            'totalPages' => PaginationHelper::totalPages($total, $pageSize),
        ];
    }

    /** Các báo giá ĐÃ XÁC NHẬN của 1 gói thầu — nguồn dữ liệu tổng hợp */
    public static function getDaXacNhanByGoiThau(int $goiThauId): array
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT bg.* FROM bg_bao_gia bg
             WHERE bg.goi_thau_id = :gt AND bg.da_xoa = 0 AND bg.trang_thai = 1
             ORDER BY bg.ten_cong_ty, bg.id"
        );
        $stmt->execute([':gt' => $goiThauId]);
        return $stmt->fetchAll();
    }

    /**
     * Tra cứu báo giá theo mã số thuế trong 1 gói thầu.
     *
     * Dùng cho cổng tra cứu của nhà thầu: MST là "khóa" nhà thầu tự biết,
     * chỉ trả về báo giá của chính MST đó (không lộ nhà thầu khác).
     * So sánh chính xác (=), không LIKE, để không dò được MST khác.
     */
    public static function getByMstTrongGoiThau(string $mst, int $goiThauId): array
    {
        $mst = trim($mst);
        if ($mst === '' || $goiThauId <= 0) return [];

        $stmt = Database::getConnection()->prepare(
            "SELECT bg.*, gt.so_thong_bao, gt.ten_goi_thau,
                    f.ten_file AS file_ban_ky,
                    f.ten_file_goc,
                    f.kich_thuoc AS kich_thuoc_file,
                    f.ngay_tao AS ngay_upload_ban_ky,
                    (SELECT COUNT(*) FROM bg_bao_gia_chi_tiet ct
                      WHERE ct.bao_gia_id = bg.id AND ct.da_xoa = 0 AND ct.don_gia > 0) AS so_dong_chao
             FROM bg_bao_gia bg
             LEFT JOIN bg_goi_thau gt ON gt.id = bg.goi_thau_id
             LEFT JOIN bg_file f ON f.id = bg.file_ban_ky_id AND f.da_xoa = 0
             WHERE bg.ma_so_thue = :mst AND bg.goi_thau_id = :gt AND bg.da_xoa = 0
             ORDER BY bg.id DESC"
        );
        $stmt->execute([':mst' => $mst, ':gt' => $goiThauId]);
        return $stmt->fetchAll();
    }

    /**
     * Tra cứu TẤT CẢ báo giá của 1 mã số thuế — không giới hạn gói thầu.
     *
     * Nhà thầu thường chào giá nhiều gói cùng lúc, nên tra theo MST phải ra hết
     * để họ theo dõi ở một chỗ. MST là "khóa" nhà thầu tự biết, và so sánh CHÍNH
     * XÁC (=, không LIKE) nên không dò được của công ty khác.
     *
     * Kèm thông tin gói thầu để nhóm kết quả theo từng gói ở giao diện.
     */
    public static function getAllByMst(string $mst): array
    {
        $mst = trim($mst);
        if ($mst === '') return [];

        $stmt = Database::getConnection()->prepare(
            "SELECT bg.*, gt.so_thong_bao, gt.ten_goi_thau,
                    gt.thoi_gian_mo_bao_gia, gt.thoi_gian_dong_bao_gia,
                    gt.trang_thai AS gt_trang_thai, gt.token AS gt_token,
                    f.ten_file AS file_ban_ky,
                    f.ten_file_goc,
                    f.kich_thuoc AS kich_thuoc_file,
                    f.ngay_tao AS ngay_upload_ban_ky,
                    (SELECT COUNT(*) FROM bg_bao_gia_chi_tiet ct
                      WHERE ct.bao_gia_id = bg.id AND ct.da_xoa = 0 AND ct.don_gia > 0) AS so_dong_chao
             FROM bg_bao_gia bg
             INNER JOIN bg_goi_thau gt ON gt.id = bg.goi_thau_id
             LEFT JOIN bg_file f ON f.id = bg.file_ban_ky_id AND f.da_xoa = 0
             WHERE bg.ma_so_thue = :mst AND bg.da_xoa = 0 AND gt.da_xoa = 0
             ORDER BY bg.ngay_nop DESC, bg.id DESC"
        );
        $stmt->execute([':mst' => $mst]);
        return $stmt->fetchAll();
    }

    /**
     * 1 báo giá có đúng của MST này không (bất kể gói thầu nào).
     * Dùng để kiểm tra quyền tải file ở cổng tra cứu liên gói.
     */
    public static function baoGiaCuaMst(int $baoGiaId, string $mst): bool
    {
        $mst = trim($mst);
        if ($baoGiaId <= 0 || $mst === '') return false;

        $stmt = Database::getConnection()->prepare(
            "SELECT COUNT(*) FROM bg_bao_gia
             WHERE id = :id AND ma_so_thue = :mst AND da_xoa = 0"
        );
        $stmt->execute([':id' => $baoGiaId, ':mst' => $mst]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /** Chặn 1 MST nộp trùng 2 lần cho cùng gói thầu khi chưa bị từ chối */
    public static function existsMstTrongGoiThau(string $mst, int $goiThauId, int $excludeId = 0): bool
    {
        if (trim($mst) === '') return false;
        $stmt = Database::getConnection()->prepare(
            "SELECT COUNT(*) FROM bg_bao_gia
             WHERE ma_so_thue = :mst AND goi_thau_id = :gt AND da_xoa = 0
               AND trang_thai <> 2 AND id <> :id"
        );
        $stmt->execute([':mst' => trim($mst), ':gt' => $goiThauId, ':id' => $excludeId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function thongKe(): array
    {
        $sql = "SELECT
                    COUNT(*) AS tong,
                    SUM(CASE WHEN trang_thai = 0 THEN 1 ELSE 0 END) AS cho_xac_nhan,
                    SUM(CASE WHEN trang_thai = 1 THEN 1 ELSE 0 END) AS da_xac_nhan,
                    SUM(CASE WHEN trang_thai = 2 THEN 1 ELSE 0 END) AS tu_choi
                FROM bg_bao_gia WHERE da_xoa = 0";
        $row = Database::getConnection()->query($sql)->fetch();
        return [
            'tong'         => (int)($row['tong'] ?? 0),
            'cho_xac_nhan' => (int)($row['cho_xac_nhan'] ?? 0),
            'da_xac_nhan'  => (int)($row['da_xac_nhan'] ?? 0),
            'tu_choi'      => (int)($row['tu_choi'] ?? 0),
        ];
    }

    // =====================================================================
    // CHI TIẾT BÁO GIÁ
    // =====================================================================

    /** Chi tiết kèm thông tin hàng hóa — dùng cho form chào giá và export */
    public static function getChiTiet(int $baoGiaId): array
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT ct.*, hh.ma_hh, hh.ten_hang_hoa, hh.dvt,
                    hh.so_luong, hh.thong_so_ky_thuat
             FROM bg_bao_gia_chi_tiet ct
             INNER JOIN bg_hang_hoa hh ON hh.id = ct.hang_hoa_id
             WHERE ct.bao_gia_id = :bg AND ct.da_xoa = 0 AND hh.da_xoa = 0
             ORDER BY hh.thu_tu, hh.id"
        );
        $stmt->execute([':bg' => $baoGiaId]);
        return $stmt->fetchAll();
    }

    /** Map hang_hoa_id => dòng chi tiết, cho tra cứu nhanh khi tổng hợp */
    public static function getChiTietMap(int $baoGiaId): array
    {
        $rows = self::getChiTiet($baoGiaId);
        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['hang_hoa_id']] = $r;
        }
        return $map;
    }

    /**
     * Ghi 1 dòng chi tiết (upsert theo UNIQUE(bao_gia_id, hang_hoa_id)).
     * thanh_tien tính ở BUS rồi truyền vào.
     */
    /**
     * Lưu 1 dòng chi tiết báo giá (gộp Mẫu 1 + Mẫu 2 của Phụ lục II).
     *
     * UNIQUE (bao_gia_id, hang_hoa_id) nên dùng ON DUPLICATE KEY UPDATE:
     * nhà thầu sửa đi sửa lại vẫn chỉ 1 dòng cho mỗi hàng hóa.
     */
    public static function upsertChiTiet(BG_BaoGiaChiTiet_PUBLIC $e): void
    {
        $sql = "INSERT INTO bg_bao_gia_chi_tiet
                    (bao_gia_id, hang_hoa_id,
                     thong_so_chao_gia, diem_khong_dat,
                     ten_thuong_mai, model, hang_san_xuat, xuat_xu, quy_cach,
                     don_gia, thanh_tien,
                     don_gia_trung_thau, tai_lieu_tham_chieu,
                     ngay_tao, ngay_cap_nhat, da_xoa)
                VALUES (:bg, :hh, :tsc, :dkd, :ttm, :md, :hsx, :xx, :qc,
                        :dg, :tt, :dgtt, :tltc, NOW(), NOW(), 0)
                ON DUPLICATE KEY UPDATE
                    thong_so_chao_gia = VALUES(thong_so_chao_gia),
                    diem_khong_dat = VALUES(diem_khong_dat),
                    ten_thuong_mai = VALUES(ten_thuong_mai),
                    model = VALUES(model),
                    hang_san_xuat = VALUES(hang_san_xuat),
                    xuat_xu = VALUES(xuat_xu),
                    quy_cach = VALUES(quy_cach),
                    don_gia = VALUES(don_gia),
                    thanh_tien = VALUES(thanh_tien),
                    don_gia_trung_thau = VALUES(don_gia_trung_thau),
                    tai_lieu_tham_chieu = VALUES(tai_lieu_tham_chieu),
                    ngay_cap_nhat = NOW(),
                    da_xoa = 0";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([
            ':bg'    => $e->bao_gia_id,
            ':hh'    => $e->hang_hoa_id,
            ':tsc'   => $e->thong_so_chao_gia,
            ':dkd'   => $e->diem_khong_dat,
            ':ttm'   => $e->ten_thuong_mai,
            ':md'    => $e->model,
            ':hsx'   => $e->hang_san_xuat,
            ':xx'    => $e->xuat_xu,
            ':qc'    => $e->quy_cach,
            ':dg'    => $e->don_gia,
            ':tt'    => $e->thanh_tien,
            ':dgtt'  => $e->don_gia_trung_thau,
            ':tltc'  => $e->tai_lieu_tham_chieu,
        ]);
    }

    /** Đơn giá thấp nhất theo từng hàng hóa trong các báo giá ĐÃ XÁC NHẬN */
    public static function giaThapNhatTheoHangHoa(int $goiThauId): array
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT ct.hang_hoa_id, MIN(ct.don_gia) AS gia_min
             FROM bg_bao_gia_chi_tiet ct
             INNER JOIN bg_bao_gia bg ON bg.id = ct.bao_gia_id
             WHERE bg.goi_thau_id = :gt AND bg.da_xoa = 0 AND bg.trang_thai = 1
               AND ct.da_xoa = 0 AND ct.don_gia > 0
             GROUP BY ct.hang_hoa_id"
        );
        $stmt->execute([':gt' => $goiThauId]);
        $out = [];
        foreach ($stmt->fetchAll() as $r) {
            $out[(int)$r['hang_hoa_id']] = (float)$r['gia_min'];
        }
        return $out;
    }
}
