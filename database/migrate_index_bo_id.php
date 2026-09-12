<?php
/**
 * migrate_index_bo_id.php — Thêm index cho bg_hang_hoa.bo_id.
 *
 * VÌ SAO CẦN:
 * Cột bo_id được JOIN/GROUP liên tục ở getBangChaoGia(), duLieuTongHop(),
 * xuatFileMauDanhMuc(), xuatMau1/xuatMau2 — nhưng chưa có index nào phủ.
 * Hiện danh mục còn nhỏ nên không ai thấy chậm; gói vài nghìn hàng hóa sẽ
 * thành full table scan nằm trong vòng lặp theo từng bộ.
 *
 * Kèm da_xoa vì MỌI truy vấn đều lọc da_xoa = 0 (§3.1), để index dùng được
 * cho cả điều kiện lọc chứ không chỉ điều kiện join.
 *
 * Idempotent: chạy lại nhiều lần vẫn an toàn.
 *
 * Cách chạy:  php database/migrate_index_bo_id.php
 */

require_once __DIR__ . '/../bootstrap.php';

function say(string $s = ''): void { echo $s . PHP_EOL; }

/** Index này đã tồn tại chưa? */
function coIndex(PDO $pdo, string $bang, string $ten): bool
{
    $sql = "SELECT COUNT(*) FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = :bang
              AND INDEX_NAME   = :ten";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':bang' => $bang, ':ten' => $ten]);
    return (int)$stmt->fetchColumn() > 0;
}

$pdo = Database::getConnection();

say('');
say('===========================================================');
say('  THÊM INDEX bg_hang_hoa.bo_id');
say('===========================================================');
say('');

if (coIndex($pdo, 'bg_hang_hoa', 'idx_bo')) {
    say('  = idx_bo đã có, bỏ qua.');
} else {
    $pdo->exec('ALTER TABLE bg_hang_hoa ADD INDEX idx_bo (bo_id, da_xoa)');
    say('  + đã thêm index idx_bo (bo_id, da_xoa) vào bg_hang_hoa');
}

say('');
say('  Index hiện có của bg_hang_hoa:');
foreach ($pdo->query('SHOW INDEX FROM bg_hang_hoa')->fetchAll(PDO::FETCH_ASSOC) as $r) {
    say(sprintf('    %-14s %s', $r['Key_name'], $r['Column_name']));
}

say('');
say('  XONG.');
say('');
