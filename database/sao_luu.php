<?php
/**
 * sao_luu.php — Sao lưu toàn bộ database + file bản ký.
 *
 * CHẠY TAY:   php database/sao_luu.php
 * CHẠY CRON:  0 2 * * *  php /duong/dan/database/sao_luu.php
 *
 * Dữ liệu báo giá là HỒ SƠ THẦU — mất là không dựng lại được. Script này
 * xuất toàn bộ bảng ra file .sql (tự viết, KHÔNG cần mysqldump nên chạy được
 * cả khi host không cho gọi lệnh hệ thống).
 *
 * File lưu ở assets/backup/ (đã chặn truy cập web bằng .htaccess).
 * Bản cũ hơn GIU_LAI_NGAY sẽ tự xóa để không đầy ổ.
 */
require_once __DIR__ . '/../bootstrap.php';

/** Số ngày giữ lại bản sao lưu */
const GIU_LAI_NGAY = 30;

$isCli = PHP_SAPI === 'cli';
if (!$isCli) {
    // Chạy qua web thì bắt buộc đăng nhập bằng tài khoản admin
    Helper::requireLogin();
    if (!PhanQuyenHelper::isAdminNhom(SessionHelper::nhomTaiKhoanId())) {
        http_response_code(403);
        exit('Chỉ quản trị viên mới được chạy sao lưu.');
    }
    header('Content-Type: text/plain; charset=utf-8');
}
function say(string $m): void { echo $m . "\n"; }

$pdo = Database::getConnection();

try {
    // ============ Thư mục sao lưu ============
    $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'backup';
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Không tạo được thư mục sao lưu: ' . $dir);
    }

    // Chặn tải file .sql qua web — file này chứa TOÀN BỘ dữ liệu
    $ht = $dir . DIRECTORY_SEPARATOR . '.htaccess';
    if (!is_file($ht)) {
        file_put_contents($ht,
            "# File sao luu chua TOAN BO du lieu - tuyet doi khong cho tai qua web.\n"
            . "Require all denied\n");
    }

    $ten = 'backup_' . AppConfig::DB_NAME . '_' . date('Ymd_His') . '.sql';
    $path = $dir . DIRECTORY_SEPARATOR . $ten;

    $fh = fopen($path, 'w');
    if (!$fh) throw new RuntimeException('Không mở được file để ghi');

    fwrite($fh, "-- Sao luu " . AppConfig::DB_NAME . " luc " . date('Y-m-d H:i:s') . "\n");
    fwrite($fh, "-- Sinh boi database/sao_luu.php\n");
    fwrite($fh, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n");

    $bangs = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $tongDong = 0;

    foreach ($bangs as $bang) {
        // --- Cấu trúc ---
        $create = $pdo->query("SHOW CREATE TABLE `{$bang}`")->fetch();
        fwrite($fh, "\n-- ---------- {$bang} ----------\n");
        fwrite($fh, "DROP TABLE IF EXISTS `{$bang}`;\n");
        fwrite($fh, ($create['Create Table'] ?? '') . ";\n\n");

        // --- Dữ liệu: đọc theo lô để không nổ RAM với bảng lớn ---
        $soDong = (int)$pdo->query("SELECT COUNT(*) FROM `{$bang}`")->fetchColumn();
        if ($soDong === 0) {
            say("  {$bang}: 0 dòng");
            continue;
        }

        $loSize = 500;
        for ($offset = 0; $offset < $soDong; $offset += $loSize) {
            // LIMIT/OFFSET là số nguyên do code sinh, không phải input người dùng
            $rows = $pdo->query("SELECT * FROM `{$bang}` LIMIT {$loSize} OFFSET {$offset}")->fetchAll();
            if (!$rows) break;

            $cols = '`' . implode('`,`', array_keys($rows[0])) . '`';
            $vals = [];
            foreach ($rows as $r) {
                $v = [];
                foreach ($r as $x) {
                    $v[] = $x === null ? 'NULL' : $pdo->quote((string)$x);
                }
                $vals[] = '(' . implode(',', $v) . ')';
            }
            fwrite($fh, "INSERT INTO `{$bang}` ({$cols}) VALUES\n" . implode(",\n", $vals) . ";\n");
        }
        $tongDong += $soDong;
        say("  {$bang}: {$soDong} dòng");
    }

    fwrite($fh, "\nSET FOREIGN_KEY_CHECKS = 1;\n");
    fclose($fh);

    $kb = round(filesize($path) / 1024);
    say('');
    say("✓ Đã sao lưu " . count($bangs) . " bảng / {$tongDong} dòng → {$ten} ({$kb} KB)");

    // ============ Nhắc sao lưu file bản ký ============
    $dirKy = rtrim(AppConfig::UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . 'ban_ky';
    $soFile = 0; $dungLuong = 0;
    foreach ((array)glob($dirKy . DIRECTORY_SEPARATOR . '*') as $f) {
        if (is_file($f) && basename($f) !== '.htaccess') { $soFile++; $dungLuong += filesize($f); }
    }
    say("  Lưu ý: {$soFile} file bản ký (" . round($dungLuong / 1048576, 1) . " MB) nằm ở assets/uploads/ban_ky/");
    say("  — file .sql KHÔNG chứa các file này, cần sao lưu thư mục đó riêng.");

    // ============ Dọn bản cũ ============
    $moc = time() - GIU_LAI_NGAY * 86400;
    $daXoa = 0;
    foreach ((array)glob($dir . DIRECTORY_SEPARATOR . 'backup_*.sql') as $f) {
        if (is_file($f) && filemtime($f) < $moc && @unlink($f)) $daXoa++;
    }
    if ($daXoa > 0) say("  Đã xóa {$daXoa} bản sao lưu cũ hơn " . GIU_LAI_NGAY . " ngày.");

    say('');
    say('HOÀN TẤT.');
} catch (Throwable $ex) {
    say('LỖI: ' . $ex->getMessage());
    exit(1);
}
