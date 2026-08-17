<?php
/**
 * migrate_bao_gia.php — Tạo các bảng nghiệp vụ tổng hợp báo giá.
 *
 * Chạy:  php database/migrate_bao_gia.php
 *
 * Idempotent: dùng CREATE TABLE IF NOT EXISTS nên chạy nhiều lần vô hại.
 * ALTER/CREATE có tiếng Việt trong DEFAULT → chạy qua PHP, không qua CLI mysql.
 */
require_once __DIR__ . '/../bootstrap.php';

$isCli = PHP_SAPI === 'cli';
if (!$isCli) header('Content-Type: text/plain; charset=utf-8');

function say(string $m): void { echo $m . "\n"; }

$pdo = Database::getConnection();

$tables = [];

// ============ 1. GÓI THẦU (thông báo mời chào giá) ============
$tables['bg_goi_thau'] = "
CREATE TABLE IF NOT EXISTS bg_goi_thau (
    id INT AUTO_INCREMENT PRIMARY KEY,
    so_thong_bao VARCHAR(100) NOT NULL COMMENT 'Số thông báo mời chào giá, VD: 5742/2026',
    ten_goi_thau VARCHAR(500) NOT NULL,
    noi_dung TEXT NULL COMMENT 'Mô tả/danh mục hàng hóa tóm tắt',
    ngay_phat_hanh DATE NULL,
    han_cuoi DATE NULL COMMENT 'Hạn cuối tiếp nhận báo giá',
    thoi_gian_hop_dong INT DEFAULT 0 COMMENT 'Thời gian thực hiện hợp đồng (tháng)',
    hieu_luc_bao_gia INT DEFAULT 180 COMMENT 'Hiệu lực báo giá tối thiểu (ngày)',
    token VARCHAR(64) NOT NULL COMMENT 'Token public dùng cho link QR',
    trang_thai INT DEFAULT 1 COMMENT '0=Nhap, 1=Dang mo, 2=Da dong, 3=Da tong hop',
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    ngay_cap_nhat DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    nguoi_tao INT NULL,
    nguoi_cap_nhat INT NULL,
    da_xoa INT DEFAULT 0,
    UNIQUE KEY uk_so_thong_bao (so_thong_bao, da_xoa),
    UNIQUE KEY uk_token (token),
    KEY idx_trang_thai (trang_thai, da_xoa),
    KEY idx_han_cuoi (han_cuoi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// ============ 2. HÀNG HÓA YÊU CẦU (cột A-K của file mẫu) ============
$tables['bg_hang_hoa'] = "
CREATE TABLE IF NOT EXISTS bg_hang_hoa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    goi_thau_id INT NOT NULL,
    ten_phan VARCHAR(200) NULL COMMENT 'A: Tên phần',
    stt_theo_phan VARCHAR(50) NULL COMMENT 'B: STT theo phần, VD P1.1',
    stt_thong_bao VARCHAR(50) NULL COMMENT 'C: STT TB mời chào giá',
    ten_hang_hoa VARCHAR(1000) NOT NULL COMMENT 'D: Tên hàng hoá',
    thong_so_ky_thuat TEXT NULL COMMENT 'E: Tính năng, thông số kỹ thuật',
    chung_nhan TEXT NULL COMMENT 'F: Chứng nhận yêu cầu',
    yeu_cau_xuat_xu TEXT NULL COMMENT 'G: Yêu cầu xuất xứ',
    dvt VARCHAR(50) NULL COMMENT 'H: Đơn vị tính',
    so_luong DECIMAL(18,3) DEFAULT 0 COMMENT 'I: Số lượng/khối lượng',
    yeu_cau_tro_cu TEXT NULL COMMENT 'J: Yêu cầu về trợ cụ/máy phụ trợ',
    thu_tu INT DEFAULT 0 COMMENT 'Thứ tự hiển thị',
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    ngay_cap_nhat DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    nguoi_tao INT NULL,
    nguoi_cap_nhat INT NULL,
    da_xoa INT DEFAULT 0,
    KEY idx_goi_thau (goi_thau_id, da_xoa, thu_tu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// ============ 3. BÁO GIÁ (1 lần nộp của 1 nhà thầu) ============
$tables['bg_bao_gia'] = "
CREATE TABLE IF NOT EXISTS bg_bao_gia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    goi_thau_id INT NOT NULL,
    ten_cong_ty VARCHAR(500) NOT NULL,
    ma_so_thue VARCHAR(50) NULL,
    email VARCHAR(200) NULL,
    dien_thoai VARCHAR(50) NULL,
    dia_chi VARCHAR(1000) NULL,
    hieu_luc_bao_gia INT DEFAULT 0 COMMENT 'Số ngày hiệu lực nhà thầu cam kết',
    ghi_chu TEXT NULL,
    trang_thai INT DEFAULT 0 COMMENT '0=Cho xac nhan, 1=Da xac nhan ban giay, 2=Tu choi',
    ngay_nop DATETIME NULL COMMENT 'Thời điểm nhà thầu nộp online',
    ngay_xac_nhan DATETIME NULL COMMENT 'Thời điểm tích xác nhận bản giấy',
    nguoi_xac_nhan INT NULL,
    ly_do_tu_choi VARCHAR(1000) NULL,
    tong_tien DECIMAL(20,2) DEFAULT 0 COMMENT 'Cache tổng thành tiền',
    ip_nop VARCHAR(45) NULL,
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    ngay_cap_nhat DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    nguoi_tao INT NULL,
    nguoi_cap_nhat INT NULL,
    da_xoa INT DEFAULT 0,
    KEY idx_goi_thau (goi_thau_id, da_xoa),
    KEY idx_trang_thai (trang_thai, da_xoa),
    KEY idx_mst (ma_so_thue)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// ============ 4. CHI TIẾT BÁO GIÁ (cột L-AD của file mẫu) ============
$tables['bg_bao_gia_chi_tiet'] = "
CREATE TABLE IF NOT EXISTS bg_bao_gia_chi_tiet (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bao_gia_id INT NOT NULL,
    hang_hoa_id INT NOT NULL,
    ten_thuong_mai VARCHAR(1000) NULL COMMENT 'L',
    model VARCHAR(500) NULL COMMENT 'M: Ký, mã, nhãn hiệu, model',
    ma_hs VARCHAR(200) NULL COMMENT 'N',
    hang_san_xuat VARCHAR(500) NULL COMMENT 'O',
    xuat_xu VARCHAR(500) NULL COMMENT 'P',
    quy_cach VARCHAR(500) NULL COMMENT 'R',
    chi_phi_dich_vu DECIMAL(20,2) DEFAULT 0 COMMENT 'T',
    thue_vat DECIMAL(8,4) DEFAULT 0 COMMENT 'U: tỷ lệ %, lưu dạng 10 = 10%',
    don_gia DECIMAL(20,2) DEFAULT 0 COMMENT 'V: đã gồm thuế phí',
    thanh_tien DECIMAL(20,2) DEFAULT 0 COMMENT 'W: tính = don_gia * so_luong',
    chung_nhan_chao TEXT NULL COMMENT 'X',
    don_gia_trung_thau DECIMAL(20,2) DEFAULT 0 COMMENT 'Y',
    tai_lieu_tham_chieu TEXT NULL COMMENT 'Z',
    ma_qr_hang_hoa VARCHAR(500) NULL COMMENT 'AA',
    thong_so_chao_gia TEXT NULL COMMENT 'AC: Thông số kỹ thuật chào giá',
    diem_khong_dat TEXT NULL COMMENT 'AD: Các điểm không đạt kèm thuyết minh',
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    ngay_cap_nhat DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    da_xoa INT DEFAULT 0,
    UNIQUE KEY uk_bao_gia_hang_hoa (bao_gia_id, hang_hoa_id),
    KEY idx_hang_hoa (hang_hoa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    foreach ($tables as $name => $sql) {
        $pdo->exec($sql);
        say("  ✓ {$name}");
    }
    say('');
    say('→ Khai báo form + phân quyền cho module mới...');

    // ============ 5. FORM + PHÂN QUYỀN ============
    // [modules_tuong_ung, ten_form]
    $forms = [
        ['BG_GoiThau',    'Gói thầu / Mời chào giá'],
        ['BG_HangHoa',    'Hàng hóa gói thầu'],
        ['BG_BaoGia',     'Báo giá nhà thầu'],
        ['BG_TongHop',    'Tổng hợp báo giá'],
    ];

    $formIds = [];
    foreach ($forms as [$mod, $ten]) {
        $stmt = $pdo->prepare("SELECT id FROM dm_danh_sach_form WHERE modules_tuong_ung = :m AND da_xoa = 0");
        $stmt->execute([':m' => $mod]);
        $id = (int)$stmt->fetchColumn();
        if ($id === 0) {
            $ins = $pdo->prepare(
                "INSERT INTO dm_danh_sach_form (modules_tuong_ung, ten_form, form_cha_id, ngay_tao, ngay_cap_nhat, nguoi_tao, nguoi_cap_nhat, da_xoa)
                 VALUES (:m, :t, 0, NOW(), NOW(), 1, 1, 0)"
            );
            $ins->execute([':m' => $mod, ':t' => $ten]);
            $id = (int)$pdo->lastInsertId();
            say("  + form {$mod}");
        }
        $formIds[$mod] = $id;
    }

    // Cấp full quyền cho mọi nhóm la_admin = 1
    $adminNhom = $pdo->query("SELECT id FROM dm_nhom_tai_khoan WHERE la_admin = 1 AND da_xoa = 0")->fetchAll();
    $upsert = $pdo->prepare(
        "INSERT INTO dm_phan_quyen (nhom_tai_khoan_id, form_id, quyen_xem, quyen_them, quyen_sua, quyen_xoa,
                                    ngay_tao, ngay_cap_nhat, nguoi_tao, nguoi_cap_nhat)
         VALUES (:nhom, :form, 1, 1, 1, 1, NOW(), NOW(), 1, 1)
         ON DUPLICATE KEY UPDATE quyen_xem=1, quyen_them=1, quyen_sua=1, quyen_xoa=1, ngay_cap_nhat=NOW()"
    );
    foreach ($adminNhom as $n) {
        foreach ($formIds as $fid) {
            $upsert->execute([':nhom' => (int)$n['id'], ':form' => $fid]);
        }
    }
    say('  ✓ Cấp full quyền cho ' . count($adminNhom) . ' nhóm admin.');

    // Quyền mặc định cho các nhóm nghiệp vụ sẵn có — chỉ THÊM nếu chưa có bản ghi,
    // không ghi đè thiết lập admin đã chỉnh tay ở GUI/DM_PhanQuyen.
    // [ma_nhom => [module => [xem, them, sua, xoa]]]
    $quyenMacDinh = [
        // Quản lý: làm được toàn bộ nghiệp vụ trừ xóa vĩnh viễn
        'MANAGER' => [
            'BG_GoiThau' => [1, 1, 1, 0],
            'BG_HangHoa' => [1, 1, 1, 0],
            'BG_BaoGia'  => [1, 0, 1, 0],   // sửa = xác nhận bản giấy
            'BG_TongHop' => [1, 0, 0, 0],
        ],
        // Nhân viên: nhập danh mục hàng hóa, xem báo giá, KHÔNG được xác nhận bản giấy
        'STAFF' => [
            'BG_GoiThau' => [1, 0, 0, 0],
            'BG_HangHoa' => [1, 1, 1, 0],
            'BG_BaoGia'  => [1, 0, 0, 0],
            'BG_TongHop' => [1, 0, 0, 0],
        ],
        // Chỉ xem
        'VIEWER' => [
            'BG_GoiThau' => [1, 0, 0, 0],
            'BG_HangHoa' => [1, 0, 0, 0],
            'BG_BaoGia'  => [1, 0, 0, 0],
            'BG_TongHop' => [1, 0, 0, 0],
        ],
    ];

    $insQuyen = $pdo->prepare(
        "INSERT INTO dm_phan_quyen (nhom_tai_khoan_id, form_id, quyen_xem, quyen_them, quyen_sua, quyen_xoa,
                                    ngay_tao, ngay_cap_nhat, nguoi_tao, nguoi_cap_nhat)
         VALUES (:nhom, :form, :xem, :them, :sua, :xoa, NOW(), NOW(), 1, 1)"
    );
    $checkQuyen = $pdo->prepare(
        "SELECT COUNT(*) FROM dm_phan_quyen WHERE nhom_tai_khoan_id = :nhom AND form_id = :form"
    );

    $soCap = 0;
    foreach ($quyenMacDinh as $maNhom => $dsModule) {
        $stmt = $pdo->prepare("SELECT id FROM dm_nhom_tai_khoan WHERE ma_nhom = :ma AND da_xoa = 0");
        $stmt->execute([':ma' => $maNhom]);
        $nhomId = (int)$stmt->fetchColumn();
        if ($nhomId === 0) continue;

        foreach ($dsModule as $mod => [$xem, $them, $sua, $xoa]) {
            if (!isset($formIds[$mod])) continue;
            $checkQuyen->execute([':nhom' => $nhomId, ':form' => $formIds[$mod]]);
            if ((int)$checkQuyen->fetchColumn() > 0) continue;   // đã có → giữ nguyên

            $insQuyen->execute([
                ':nhom' => $nhomId,
                ':form' => $formIds[$mod],
                ':xem'  => $xem,
                ':them' => $them,
                ':sua'  => $sua,
                ':xoa'  => $xoa,
            ]);
            $soCap++;
        }
    }
    if ($soCap > 0) say("  ✓ Cấp quyền mặc định cho MANAGER/STAFF/VIEWER ({$soCap} dòng).");

    // ============ 6. NHÓM + TÀI KHOẢN NHÀ THẦU (guest) ============
    $stmt = $pdo->prepare("SELECT id FROM dm_nhom_tai_khoan WHERE ma_nhom = 'NHATHAU' AND da_xoa = 0");
    $stmt->execute();
    $nhomNhaThau = (int)$stmt->fetchColumn();
    if ($nhomNhaThau === 0) {
        $ins = $pdo->prepare(
            "INSERT INTO dm_nhom_tai_khoan (ma_nhom, ten_nhom, mo_ta, trang_thai, la_admin, ngay_tao, ngay_cap_nhat, nguoi_tao, nguoi_cap_nhat, da_xoa)
             VALUES ('NHATHAU', :ten, :mt, 1, 0, NOW(), NOW(), 1, 1, 0)"
        );
        $ins->execute([
            ':ten' => 'Nhà thầu',
            ':mt'  => 'Tài khoản dùng chung cho nhà thầu quét QR vào chào giá',
        ]);
        $nhomNhaThau = (int)$pdo->lastInsertId();
        say('  + nhóm NHATHAU');
    }

    // Nhà thầu chỉ cần quyền xem BG_GoiThau (để mở form chào giá) — mọi thao tác
    // chào giá đi qua GUI/portal có kiểm tra riêng theo token, không qua ma trận này.
    $upsertNT = $pdo->prepare(
        "INSERT INTO dm_phan_quyen (nhom_tai_khoan_id, form_id, quyen_xem, quyen_them, quyen_sua, quyen_xoa,
                                    ngay_tao, ngay_cap_nhat, nguoi_tao, nguoi_cap_nhat)
         VALUES (:nhom, :form, 0, 0, 0, 0, NOW(), NOW(), 1, 1)
         ON DUPLICATE KEY UPDATE ngay_cap_nhat = NOW()"
    );
    foreach ($formIds as $fid) {
        $upsertNT->execute([':nhom' => $nhomNhaThau, ':form' => $fid]);
    }

    $stmt = $pdo->prepare("SELECT id FROM dm_nguoi_dung WHERE tai_khoan = 'guest' AND da_xoa = 0");
    $stmt->execute();
    if ((int)$stmt->fetchColumn() === 0) {
        $ins = $pdo->prepare(
            "INSERT INTO dm_nguoi_dung (tai_khoan, mat_khau, nhom_tai_khoan_id, trang_thai, ngay_tao, ngay_cap_nhat, nguoi_tao, nguoi_cap_nhat, da_xoa)
             VALUES ('guest', :mk, :nhom, 1, NOW(), NOW(), 1, 1, 0)"
        );
        $ins->execute([
            ':mk'   => password_hash('123456', AppConfig::PASSWORD_ALGO, ['cost' => AppConfig::PASSWORD_COST]),
            ':nhom' => $nhomNhaThau,
        ]);
        say('  + tài khoản guest / 123456 (nhà thầu)');
    }

    MemcachedHelper::deleteByPrefix('phan_quyen:');
    say('');
    say('HOÀN TẤT. Đã tạo 4 bảng nghiệp vụ + 4 form + nhóm/tài khoản nhà thầu.');
} catch (Throwable $ex) {
    say('LỖI: ' . $ex->getMessage());
    exit(1);
}
