<?php
/**
 * migrate_chong_brute_force.php — Bảng đếm số lần đăng nhập sai theo IP.
 *
 * CHẠY THỦ CÔNG:  php database/migrate_chong_brute_force.php
 * (Theo §8B trong CLAUDE.md: không tự động đổi cấu trúc DB.)
 *
 * LÝ DO: bộ đếm cũ lưu trong SESSION nên xóa cookie là reset — bot dò mật khẩu
 * luôn làm vậy, tức là hoàn toàn không có tác dụng. Đã kiểm chứng: đăng nhập
 * sai 7 lần liên tiếp với cookie mới mỗi lần, không hề bị khóa.
 *
 * Bảng này lưu theo IP + tài khoản nên xóa cookie không thoát được.
 *
 * Idempotent: chạy nhiều lần vẫn an toàn.
 */
require_once __DIR__ . '/../bootstrap.php';

$isCli = PHP_SAPI === 'cli';
if (!$isCli) header('Content-Type: text/plain; charset=utf-8');
function say(string $m): void { echo $m . "\n"; }

$pdo = Database::getConnection();

try {
    say('→ Tạo bảng dm_dang_nhap_that_bai...');

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS dm_dang_nhap_that_bai (
            id INT AUTO_INCREMENT PRIMARY KEY,
            khoa VARCHAR(190) NOT NULL COMMENT 'Khóa đếm: <ip>|<tai_khoan>',
            ip_address VARCHAR(45) NOT NULL,
            tai_khoan VARCHAR(100) NULL COMMENT 'Tài khoản bị dò (chỉ để tra cứu)',
            so_lan INT NOT NULL DEFAULT 1,
            lan_dau DATETIME NOT NULL COMMENT 'Lần sai đầu tiên trong chuỗi',
            lan_cuoi DATETIME NOT NULL COMMENT 'Lần sai gần nhất — mốc tính khóa',
            UNIQUE KEY uk_khoa (khoa),
            KEY idx_lan_cuoi (lan_cuoi),
            KEY idx_ip (ip_address)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    say('  ✓ dm_dang_nhap_that_bai');

    say('');
    say('HOÀN TẤT.');
    say('');
    say('GHI CHÚ: bản ghi cũ được dọn tự động trong cron_cleanup.php.');
    say('Muốn mở khóa tay cho 1 IP thì xóa dòng tương ứng trong bảng này.');
} catch (Throwable $ex) {
    say('LỖI: ' . $ex->getMessage());
    exit(1);
}
