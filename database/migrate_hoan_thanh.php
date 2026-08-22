<?php
/**
 * migrate_hoan_thanh.php — Cột đánh dấu nhà thầu ĐÃ HOÀN THÀNH toàn bộ 5 bước.
 *
 * VÌ SAO CẦN CỘT RIÊNG, KHÔNG DÙNG trang_thai:
 *   Upload bản ký ở Bước 4 đã tự chuyển `trang_thai = 1 (Đã xác nhận)`.
 *   Nếu lấy luôn cờ đó làm "khóa sửa" thì nhà thầu bị khóa NGAY khi xong Bước 4,
 *   chưa kịp làm Bước 5 (chỉ dẫn vị trí tài liệu).
 *   Vì vậy tách riêng `da_hoan_thanh`: chỉ bật khi nhà thầu bấm xác nhận ở
 *   cuối Bước 5. Bật rồi thì mọi thao tác sửa đều bị chặn, chỉ còn xem.
 *
 * Idempotent: chạy nhiều lần vẫn an toàn.
 *
 * Cách chạy:  php database/migrate_hoan_thanh.php
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
say('  CỜ HOÀN THÀNH BÁO GIÁ (khóa sửa sau khi nhà thầu chốt)');
say('  DB: ' . AppConfig::DB_NAME);
say('===========================================================');
say('');

try {
    $pdo = Database::getConnection();

    say('→ Cột bg_bao_gia.da_hoan_thanh...');
    if (coCot($pdo, 'bg_bao_gia', 'da_hoan_thanh')) {
        say('  = cột da_hoan_thanh đã có');
    } else {
        $pdo->exec("ALTER TABLE bg_bao_gia
                    ADD COLUMN da_hoan_thanh TINYINT(1) NOT NULL DEFAULT 0
                    COMMENT 'Nhà thầu đã chốt xong 5 bước — khóa mọi chỉnh sửa'
                    AFTER trang_thai");
        say('  + cột da_hoan_thanh');
    }

    say('');
    say('→ Cột bg_bao_gia.ngay_hoan_thanh...');
    if (coCot($pdo, 'bg_bao_gia', 'ngay_hoan_thanh')) {
        say('  = cột ngay_hoan_thanh đã có');
    } else {
        $pdo->exec("ALTER TABLE bg_bao_gia
                    ADD COLUMN ngay_hoan_thanh DATETIME NULL
                    COMMENT 'Thời điểm nhà thầu bấm hoàn thành'
                    AFTER da_hoan_thanh");
        say('  + cột ngay_hoan_thanh');
    }

    say('');
    say('===========================================================');
    say('  XONG.');
    say('===========================================================');
} catch (Throwable $ex) {
    say('');
    say('LỖI: ' . $ex->getMessage());
    exit(1);
}
