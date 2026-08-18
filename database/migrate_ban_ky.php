<?php
/**
 * migrate_ban_ky.php — Thêm chỗ lưu bản báo giá có dấu & chữ ký của nhà thầu.
 *
 * Chạy:  php database/migrate_ban_ky.php
 *
 * Nghiệp vụ: nhà thầu vào phần tra cứu, upload file PDF/ảnh bản báo giá đã ký
 * đóng dấu. Upload xong hệ thống tự chuyển trạng thái sang ĐÃ XÁC NHẬN
 * (thay cho việc bên mời phải tích tay sau khi nhận bản giấy).
 *
 * Idempotent: kiểm tra cột tồn tại trước khi ALTER.
 */
require_once __DIR__ . '/../bootstrap.php';

$isCli = PHP_SAPI === 'cli';
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

try {
    $cols = [
        'file_ban_ky' => "ALTER TABLE bg_bao_gia
            ADD COLUMN file_ban_ky VARCHAR(255) NULL
            COMMENT 'Tên file bản báo giá có dấu+chữ ký (đã đổi tên khi lưu)'
            AFTER ly_do_tu_choi",
        'ten_file_goc' => "ALTER TABLE bg_bao_gia
            ADD COLUMN ten_file_goc VARCHAR(255) NULL
            COMMENT 'Tên file gốc nhà thầu tải lên, chỉ để hiển thị'
            AFTER file_ban_ky",
        'ngay_upload_ban_ky' => "ALTER TABLE bg_bao_gia
            ADD COLUMN ngay_upload_ban_ky DATETIME NULL
            COMMENT 'Thời điểm nhà thầu upload bản ký'
            AFTER ten_file_goc",
    ];

    foreach ($cols as $cot => $sql) {
        if (coCot($pdo, 'bg_bao_gia', $cot)) {
            say("  = cột {$cot} đã có");
            continue;
        }
        $pdo->exec($sql);
        say("  + cột {$cot}");
    }

    // Thư mục lưu file bản ký + chặn truy cập trực tiếp qua URL
    $dir = rtrim(AppConfig::UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . 'ban_ky';
    if (!is_dir($dir)) {
        if (@mkdir($dir, 0775, true) || is_dir($dir)) {
            say('  + thư mục assets/uploads/ban_ky');
        } else {
            say('  ! KHÔNG tạo được thư mục ' . $dir . ' — hãy tạo tay');
        }
    } else {
        say('  = thư mục ban_ky đã có');
    }

    $ht = $dir . DIRECTORY_SEPARATOR . '.htaccess';
    if (is_dir($dir) && !is_file($ht)) {
        $noiDung = "# File ban bao gia co dau + chu ky cua nha thau.\n"
                 . "# Day la tai lieu phap ly, KHONG cho tai truc tiep qua URL.\n"
                 . "# Chi duoc gui ra qua GUI/*/xem_ban_ky.php (co kiem tra quyen).\n"
                 . "Require all denied\n\n"
                 . "<FilesMatch \"\\.(php|php3|php4|php5|php7|phtml|phar)$\">\n"
                 . "    Require all denied\n"
                 . "</FilesMatch>\n";
        file_put_contents($ht, $noiDung);
        say('  + .htaccess chặn truy cập trực tiếp');
    }

    say('');
    say('HOÀN TẤT.');
} catch (Throwable $ex) {
    say('LỖI: ' . $ex->getMessage());
    exit(1);
}
