<?php
/**
 * migrate_gia_bo.php — Nhà thầu chào giá theo BỘ (không phải theo hàng hóa chi tiết).
 *
 * VÌ SAO CẦN:
 * Với nhóm "Bộ y dụng cụ" và "Hệ thống máy, thiết bị y tế", bên mời mua TRỌN
 * BỘ chứ không mua lẻ từng chi tiết. Nhà thầu phải chào giá cho cả bộ; các
 * hàng hóa chi tiết bên dưới KHÔNG cần điền đơn giá / thành tiền.
 *
 * Trước đây giá của bộ là TỔNG CỘNG DỒN từ chi tiết (tính ở PHP lúc xuất file).
 * Giờ đảo lại: giá bộ do NHÀ THẦU NHẬP, chi tiết để trống.
 *
 * Bảng bg_bao_gia_chi_tiet bắt buộc gắn với 1 hàng hóa (hang_hoa_id NOT NULL
 * + UNIQUE(bao_gia_id, hang_hoa_id)) nên KHÔNG có chỗ chứa giá của bộ →
 * tạo bảng riêng bg_bao_gia_bo thay vì nới lỏng ràng buộc đang bảo vệ dữ liệu.
 *
 * LƯU Ý VỀ thanh_tien:
 * Khác với bg_bao_gia_chi_tiet (thành tiền luôn tính ở server = đơn giá × SL),
 * ở bảng này nhà thầu NHẬP CẢ HAI. Giá trọn gói của một bộ không phải lúc nào
 * cũng chia đều theo số lượng bộ, nên bên mời cần cho nhà thầu tự ghi. Đây là
 * ngoại lệ CÓ CHỦ Ý với §10.2 — đừng "sửa lại cho đúng chuẩn".
 *
 * Idempotent: chạy lại nhiều lần vẫn an toàn.
 *
 * Cách chạy:  php database/migrate_gia_bo.php
 */

require_once __DIR__ . '/../bootstrap.php';

function say(string $s = ''): void { echo $s . PHP_EOL; }

function coBang(PDO $pdo, string $bang): bool
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.TABLES
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :b"
    );
    $stmt->execute([':b' => $bang]);
    return (int)$stmt->fetchColumn() > 0;
}

$pdo = Database::getConnection();

say('');
say('===========================================================');
say('  CHÀO GIÁ THEO BỘ — tạo bảng bg_bao_gia_bo');
say('===========================================================');
say('');

/** Cột này đã có trong bảng chưa? */
function coCot(PDO $pdo, string $bang, string $cot): bool
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :b AND COLUMN_NAME = :c"
    );
    $stmt->execute([':b' => $bang, ':c' => $cot]);
    return (int)$stmt->fetchColumn() > 0;
}

if (coBang($pdo, 'bg_bao_gia_bo')) {
    say('  = bg_bao_gia_bo đã có, bỏ qua phần tạo bảng.');
} else {
    $pdo->exec("
        CREATE TABLE bg_bao_gia_bo (
            id            INT(11) NOT NULL AUTO_INCREMENT,
            bao_gia_id    INT(11) NOT NULL,
            bo_id         INT(11) NOT NULL,

            -- Nhà thầu điền (Mẫu 2, dòng BỘ)
            ten_thuong_mai VARCHAR(1000) DEFAULT NULL,
            model          VARCHAR(500)  DEFAULT NULL COMMENT 'Ký mã, nhãn hiệu, model',
            hang_san_xuat  VARCHAR(500)  DEFAULT NULL,
            nam_san_xuat   VARCHAR(20)   DEFAULT NULL,
            xuat_xu        VARCHAR(500)  DEFAULT NULL,

            don_gia        DECIMAL(20,2) DEFAULT 0.00 COMMENT 'Đơn giá TRỌN BỘ, nhà thầu nhập',
            thanh_tien     DECIMAL(20,2) DEFAULT 0.00 COMMENT 'Nhà thầu NHẬP TAY (ngoại lệ §10.2)',

            -- Đáp ứng cấp BỘ (Mẫu 1, dòng BỘ). Yêu cầu chung/khác/cấu hình gắn
            -- với BỘ nên phần ĐÁP ỨNG cho chúng cũng phải nằm ở dòng bộ.
            dap_ung_chung        TEXT DEFAULT NULL,
            khong_dat_chung      TEXT DEFAULT NULL,
            dap_ung_khac         TEXT DEFAULT NULL,
            khong_dat_khac       TEXT DEFAULT NULL,
            dap_ung_cau_hinh     TEXT DEFAULT NULL,
            khong_dat_cau_hinh   TEXT DEFAULT NULL,
            thong_so_chao_gia    TEXT DEFAULT NULL COMMENT 'Đáp ứng về yêu cầu kỹ thuật',
            diem_khong_dat       TEXT DEFAULT NULL COMMENT 'Các điểm không đáp ứng KT',
            dap_ung_nhom_nuoc    TEXT DEFAULT NULL,
            khong_dat_nhom_nuoc  TEXT DEFAULT NULL,
            tai_lieu_chung_minh  TEXT DEFAULT NULL,

            ngay_tao       DATETIME DEFAULT current_timestamp(),
            ngay_cap_nhat  DATETIME DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            da_xoa         INT(11)  DEFAULT 0,

            PRIMARY KEY (id),
            UNIQUE KEY uk_bao_gia_bo (bao_gia_id, bo_id),
            KEY idx_bo (bo_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
          COMMENT='Giá nhà thầu chào cho cả BỘ (nhóm mua theo bộ)'
    ");
    say('  + đã tạo bảng bg_bao_gia_bo');
}

// Bảng đã tạo từ lần chạy trước (chỉ có cột GIÁ) → bổ sung cột ĐÁP ỨNG.
// Tách riêng khỏi CREATE TABLE để chạy lại lần nào cũng an toàn.
$cotDapUng = [
    'dap_ung_chung'       => "TEXT DEFAULT NULL",
    'khong_dat_chung'     => "TEXT DEFAULT NULL",
    'dap_ung_khac'        => "TEXT DEFAULT NULL",
    'khong_dat_khac'      => "TEXT DEFAULT NULL",
    'dap_ung_cau_hinh'    => "TEXT DEFAULT NULL",
    'khong_dat_cau_hinh'  => "TEXT DEFAULT NULL",
    'thong_so_chao_gia'   => "TEXT DEFAULT NULL COMMENT 'Đáp ứng về yêu cầu kỹ thuật'",
    'diem_khong_dat'      => "TEXT DEFAULT NULL COMMENT 'Các điểm không đáp ứng KT'",
    'dap_ung_nhom_nuoc'   => "TEXT DEFAULT NULL",
    'khong_dat_nhom_nuoc' => "TEXT DEFAULT NULL",
    'tai_lieu_chung_minh' => "TEXT DEFAULT NULL",
];

$soThem = 0;
foreach ($cotDapUng as $cot => $kieu) {
    if (coCot($pdo, 'bg_bao_gia_bo', $cot)) {
        say("  = cột {$cot} đã có, bỏ qua.");
        continue;
    }
    $pdo->exec("ALTER TABLE bg_bao_gia_bo ADD COLUMN {$cot} {$kieu}");
    say("  + đã thêm cột {$cot}");
    $soThem++;
}
if ($soThem > 0) {
    say("  → thêm {$soThem} cột đáp ứng cấp BỘ.");
}

say('');
say('  Cấu trúc bg_bao_gia_bo:');
foreach ($pdo->query('SHOW COLUMNS FROM bg_bao_gia_bo')->fetchAll(PDO::FETCH_ASSOC) as $r) {
    say(sprintf('    %-16s %-16s %s', $r['Field'], $r['Type'], $r['Null'] === 'NO' ? 'NOT NULL' : ''));
}

// Thống kê để người chạy biết ảnh hưởng tới dữ liệu đang có
say('');
say('  Dữ liệu hiện tại sẽ chịu ảnh hưởng:');
$sql = "SELECT gt.nhom, COUNT(DISTINCT bg.id) AS so_bao_gia, COUNT(DISTINCT b.id) AS so_bo
          FROM bg_goi_thau gt
          LEFT JOIN bg_bao_gia bg ON bg.goi_thau_id = gt.id AND bg.da_xoa = 0
          LEFT JOIN bg_bo b       ON b.goi_thau_id  = gt.id AND b.da_xoa  = 0
         WHERE gt.da_xoa = 0 AND gt.nhom <> 'vat_tu_duoc'
         GROUP BY gt.nhom";
foreach ($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $r) {
    say(sprintf('    nhóm %-14s %d báo giá, %d bộ', $r['nhom'], $r['so_bao_gia'], $r['so_bo']));
}

say('');
say('  Báo giá CŨ của 2 nhóm này có giá nằm ở hàng hóa chi tiết. Sau khi đổi,');
say('  tổng tiền chỉ tính từ giá BỘ nên các báo giá đó sẽ hiện tổng = 0 cho');
say('  phần thuộc bộ, tới khi nhà thầu nhập lại giá bộ (hoặc bên mời nhập hộ).');
say('  Dữ liệu chi tiết KHÔNG bị xóa — vẫn xem lại được.');
say('');
say('  XONG.');
say('');
