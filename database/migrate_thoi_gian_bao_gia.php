<?php
/**
 * migrate_thoi_gian_bao_gia.php — Thêm khoảng thời gian nhận báo giá cho gói thầu.
 *
 * Chạy:  php database/migrate_thoi_gian_bao_gia.php
 *
 * Trước đây chỉ có `han_cuoi` (ngày kết thúc). Nay tách rõ 2 mốc có GIỜ:
 *   - thoi_gian_mo_bao_gia   : trước mốc này, quét QR chỉ tra cứu được
 *   - thoi_gian_dong_bao_gia : sau mốc này, khóa điền báo giá
 *
 * `han_cuoi` được GIỮ LẠI để không phá dữ liệu/màn hình cũ, và dùng làm
 * nguồn suy ra `thoi_gian_dong_bao_gia` cho các gói thầu đã có.
 *
 * Idempotent: kiểm tra cột tồn tại trước khi ALTER.
 * ALTER có tiếng Việt trong COMMENT → chạy qua PHP, không qua CLI mysql.
 */
require_once __DIR__ . '/../bootstrap.php';

$isCli = PHP_SAPI === 'cli';
if (!$isCli) header('Content-Type: text/plain; charset=utf-8');

function say(string $m): void { echo $m . "\n"; }

$pdo = Database::getConnection();

/** Cột đã tồn tại chưa? */
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
    $them = [
        'thoi_gian_mo_bao_gia' => "ALTER TABLE bg_goi_thau
            ADD COLUMN thoi_gian_mo_bao_gia DATETIME NULL
            COMMENT 'Bắt đầu nhận báo giá. NULL = nhận ngay khi mở'
            AFTER ngay_phat_hanh",
        'thoi_gian_dong_bao_gia' => "ALTER TABLE bg_goi_thau
            ADD COLUMN thoi_gian_dong_bao_gia DATETIME NULL
            COMMENT 'Kết thúc nhận báo giá. NULL = không giới hạn giờ'
            AFTER thoi_gian_mo_bao_gia",
    ];

    foreach ($them as $cot => $sql) {
        if (coCot($pdo, 'bg_goi_thau', $cot)) {
            say("  = cột {$cot} đã có");
            continue;
        }
        $pdo->exec($sql);
        say("  + cột {$cot}");
    }

    // Suy dữ liệu cho gói thầu cũ: đóng vào cuối ngày han_cuoi,
    // mở từ ngày phát hành (hoặc ngày tạo nếu chưa có ngày phát hành).
    $n = $pdo->exec(
        "UPDATE bg_goi_thau
         SET thoi_gian_dong_bao_gia = CONCAT(han_cuoi, ' 23:59:59')
         WHERE thoi_gian_dong_bao_gia IS NULL AND han_cuoi IS NOT NULL"
    );
    say("  ✓ Suy thời gian đóng từ han_cuoi cho {$n} gói thầu.");

    $n2 = $pdo->exec(
        "UPDATE bg_goi_thau
         SET thoi_gian_mo_bao_gia = CONCAT(COALESCE(ngay_phat_hanh, DATE(ngay_tao)), ' 00:00:00')
         WHERE thoi_gian_mo_bao_gia IS NULL"
    );
    say("  ✓ Suy thời gian mở cho {$n2} gói thầu.");

    // Index phục vụ lọc/sắp xếp theo trạng thái thời gian
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bg_goi_thau'
           AND INDEX_NAME = 'idx_thoi_gian_bao_gia'"
    );
    $stmt->execute();
    if ((int)$stmt->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE bg_goi_thau
                    ADD INDEX idx_thoi_gian_bao_gia (thoi_gian_mo_bao_gia, thoi_gian_dong_bao_gia)");
        say('  + index idx_thoi_gian_bao_gia');
    } else {
        say('  = index idx_thoi_gian_bao_gia đã có');
    }

    say('');
    say('HOÀN TẤT.');
} catch (Throwable $ex) {
    say('LỖI: ' . $ex->getMessage());
    exit(1);
}
