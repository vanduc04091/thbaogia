<?php
/**
 * migrate_go_cot_file_cu.php — Gỡ 4 cột file cũ khỏi bg_bao_gia.
 *
 * CHẠY THỦ CÔNG, VÀ CHỈ SAU KHI đã chạy migrate_bang_file.php + chạy thử hệ thống ổn:
 *     php database/migrate_go_cot_file_cu.php            (kiểm tra, chưa xóa)
 *     php database/migrate_go_cot_file_cu.php --that     (thực sự DROP)
 *
 * ⚠️ DROP COLUMN là thao tác KHÔNG HOÀN TÁC ĐƯỢC. Hãy sao lưu DB trước:
 *     mysqldump -u root thbaogia bg_bao_gia > backup_bg_bao_gia.sql
 *
 * Mặc định chỉ KIỂM TRA và báo cáo, không đụng vào cấu trúc.
 * Script tự từ chối DROP nếu phát hiện dữ liệu chưa chuyển hết sang bg_file.
 */
require_once __DIR__ . '/../bootstrap.php';

$isCli  = PHP_SAPI === 'cli';
$laThat = $isCli && in_array('--that', $argv ?? [], true);
if (!$isCli) header('Content-Type: text/plain; charset=utf-8');
function say(string $m): void { echo $m . "\n"; }

$pdo = Database::getConnection();

function coCot(PDO $pdo, string $bang, string $cot): bool
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :b AND COLUMN_NAME = :c"
    );
    $stmt->execute([':b' => $bang, ':c' => $cot]);
    return (int)$stmt->fetchColumn() > 0;
}

$cotCu = ['file_ban_ky', 'ten_file_goc', 'kich_thuoc_file', 'ngay_upload_ban_ky'];

try {
    // Bảng mới phải tồn tại đã
    $coBang = (int)$pdo->query(
        "SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bg_file'"
    )->fetchColumn();
    if ($coBang === 0) {
        say('LỖI: chưa có bảng bg_file. Chạy migrate_bang_file.php trước.');
        exit(1);
    }
    if (!coCot($pdo, 'bg_bao_gia', 'file_ban_ky_id')) {
        say('LỖI: chưa có cột file_ban_ky_id. Chạy migrate_bang_file.php trước.');
        exit(1);
    }

    // ---- Kiểm tra dữ liệu đã chuyển hết chưa ----
    say('→ Kiểm tra dữ liệu trước khi gỡ cột...');

    $conSot = 0;
    if (coCot($pdo, 'bg_bao_gia', 'file_ban_ky')) {
        $conSot = (int)$pdo->query(
            "SELECT COUNT(*) FROM bg_bao_gia
             WHERE file_ban_ky IS NOT NULL AND file_ban_ky <> ''
               AND (file_ban_ky_id IS NULL OR file_ban_ky_id = 0)"
        )->fetchColumn();
    }

    $soFile = (int)$pdo->query("SELECT COUNT(*) FROM bg_file WHERE da_xoa = 0")->fetchColumn();
    $soLienKet = (int)$pdo->query(
        "SELECT COUNT(*) FROM bg_bao_gia WHERE file_ban_ky_id IS NOT NULL AND file_ban_ky_id > 0"
    )->fetchColumn();

    say("  bg_file: {$soFile} bản ghi");
    say("  bg_bao_gia có file_ban_ky_id: {$soLienKet} dòng");
    say("  Dòng còn dữ liệu cũ CHƯA chuyển: {$conSot}");

    if ($conSot > 0) {
        say('');
        say('DỪNG LẠI: còn dữ liệu chưa chuyển sang bg_file.');
        say('Chạy lại  php database/migrate_bang_file.php  rồi mới gỡ cột.');
        exit(1);
    }

    // ---- Kiểm tra liên kết mồ côi (trỏ tới bg_file không tồn tại) ----
    $moCoi = (int)$pdo->query(
        "SELECT COUNT(*) FROM bg_bao_gia bg
         LEFT JOIN bg_file f ON f.id = bg.file_ban_ky_id
         WHERE bg.file_ban_ky_id IS NOT NULL AND bg.file_ban_ky_id > 0 AND f.id IS NULL"
    )->fetchColumn();
    if ($moCoi > 0) {
        say('');
        say("CẢNH BÁO: {$moCoi} báo giá trỏ tới bg_file không tồn tại. Kiểm tra lại trước khi gỡ.");
        exit(1);
    }

    $conLai = array_values(array_filter($cotCu, static fn($c) => coCot($pdo, 'bg_bao_gia', $c)));
    if (empty($conLai)) {
        say('');
        say('Các cột cũ đã được gỡ từ trước. Không còn gì để làm.');
        exit(0);
    }

    say('');
    say('Cột sẽ gỡ: ' . implode(', ', $conLai));

    if (!$laThat) {
        say('');
        say('Đây mới là bước KIỂM TRA. Dữ liệu đã sẵn sàng để gỡ cột.');
        say('Nhớ sao lưu rồi chạy lại với cờ --that:');
        say('    php database/migrate_go_cot_file_cu.php --that');
        exit(0);
    }

    say('');
    say('→ Đang gỡ cột...');
    foreach ($conLai as $c) {
        $pdo->exec("ALTER TABLE bg_bao_gia DROP COLUMN `{$c}`");
        say("  - đã gỡ {$c}");
    }

    say('');
    say('HOÀN TẤT. bg_bao_gia giờ chỉ còn file_ban_ky_id trỏ sang bg_file.');
} catch (Throwable $ex) {
    say('LỖI: ' . $ex->getMessage());
    exit(1);
}
