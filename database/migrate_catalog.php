<?php
/**
 * migrate_catalog.php — Bước 5: Chỉ dẫn vị trí tài liệu (catalog).
 *
 * Tạo:
 *   1. Bảng `bg_catalog` — mỗi dòng = 1 hàng hóa + trang catalog chứng minh.
 *      Bảng theo Thư mời: STT | Mã HH | Tên hàng thương mại | Trang catalog chứng minh
 *      (Tên hàng thương mại lấy từ bg_bao_gia_chi_tiet.ten_thuong_mai, không lưu lại)
 *   2. Cột `bg_bao_gia.file_catalog_id` — trỏ sang bg_file (nhóm 'catalog')
 *   3. Thư mục assets/uploads/catalog/ + .htaccess chặn truy cập thẳng
 *
 * Idempotent: chạy nhiều lần vẫn an toàn.
 *
 * Cách chạy:  php database/migrate_catalog.php
 */

require_once __DIR__ . '/../bootstrap.php';

function say(string $s = ''): void { echo $s . PHP_EOL; }

function coCot(PDO $pdo, string $bang, string $cot): bool
{
    $st = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :bang AND COLUMN_NAME = :cot"
    );
    $st->execute([':db' => AppConfig::DB_NAME, ':bang' => $bang, ':cot' => $cot]);
    return (int)$st->fetchColumn() > 0;
}

say('===========================================================');
say('  BƯỚC 5: CHỈ DẪN VỊ TRÍ TÀI LIỆU (CATALOG)');
say('  DB: ' . AppConfig::DB_NAME);
say('===========================================================');
say('');

try {
    $pdo = Database::getConnection();

    // ============ 1. Bảng bg_catalog ============
    say('→ 1. Bảng bg_catalog...');
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS bg_catalog (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bao_gia_id INT NOT NULL,
            hang_hoa_id INT NOT NULL,
            trang_catalog VARCHAR(255) NULL COMMENT 'VD: Trang 1-15',
            ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
            ngay_cap_nhat DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            nguoi_tao INT NULL,
            nguoi_cap_nhat INT NULL,
            da_xoa INT DEFAULT 0,
            UNIQUE KEY uk_catalog (bao_gia_id, hang_hoa_id),
            KEY idx_hang_hoa (hang_hoa_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
          COMMENT='Bước 5 — chỉ dẫn trang catalog chứng minh cho từng hàng hóa'
    ");
    say('  + bảng bg_catalog (hoặc đã có)');

    // ============ 2. Cột file_catalog_id ============
    say('');
    say('→ 2. Cột bg_bao_gia.file_catalog_id...');
    if (coCot($pdo, 'bg_bao_gia', 'file_catalog_id')) {
        say('  = cột file_catalog_id đã có');
    } else {
        $pdo->exec("ALTER TABLE bg_bao_gia
                    ADD COLUMN file_catalog_id INT NULL
                    COMMENT 'File catalog đã ký, trỏ sang bg_file (nhom_file=catalog)'
                    AFTER file_ban_ky_id");
        say('  + cột file_catalog_id');
    }

    // ============ 2b. Cột file_catalog_excel_id ============
    say('');
    say('→ 2b. Cột bg_bao_gia.file_catalog_excel_id...');
    if (coCot($pdo, 'bg_bao_gia', 'file_catalog_excel_id')) {
        say('  = cột file_catalog_excel_id đã có');
    } else {
        $pdo->exec("ALTER TABLE bg_bao_gia
                    ADD COLUMN file_catalog_excel_id INT NULL
                    COMMENT 'File Excel chỉ dẫn vị trí tài liệu (bg_file, nhom_file=catalog_excel)'
                    AFTER file_catalog_id");
        say('  + cột file_catalog_excel_id');
    }

    // ============ 3. Thư mục lưu file ============
    say('');
    say('→ 3. Thư mục assets/uploads/catalog/...');
    $dir = rtrim(AppConfig::UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . 'catalog';
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Không tạo được thư mục ' . $dir);
    }
    say('  + thư mục catalog/');

    // Chặn truy cập thẳng — chỉ tải qua download.php có kiểm tra quyền
    $ht = $dir . DIRECTORY_SEPARATOR . '.htaccess';
    if (!is_file($ht)) {
        file_put_contents($ht, "Require all denied\n<IfModule !mod_authz_core.c>\n    Deny from all\n</IfModule>\n");
        say('  + .htaccess chặn truy cập thẳng');
    } else {
        say('  = .htaccess đã có');
    }

    say('');
    say('===========================================================');
    say('  XONG. Bước 5 đã sẵn sàng.');
    say('===========================================================');
} catch (Throwable $ex) {
    say('');
    say('LỖI: ' . $ex->getMessage());
    exit(1);
}
