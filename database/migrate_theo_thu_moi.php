<?php
/**
 * migrate_theo_thu_moi.php — Chuẩn hóa cột theo Phụ lục II Thư mời chào giá.
 *
 * CHẠY THỦ CÔNG:  php database/migrate_theo_thu_moi.php
 * (Theo §8B trong CLAUDE.md: không tự động đổi cấu trúc DB.)
 *
 * ⚠️ CÓ BƯỚC XÓA CỘT — SAO LƯU TRƯỚC KHI CHẠY:
 *     php database/sao_luu.php
 *
 * Việc file này làm:
 *   1. bg_hang_hoa: thêm `ma_hh` (Mã HH — Phụ lục III), tự sinh cho dòng cũ
 *   2. (đã bỏ — xem migrate_bo_cot_so_thong_bao.php)
 *   3. bg_bao_gia_chi_tiet: XÓA `thue_vat`, `chi_phi_dich_vu`
 *      (Mẫu 2 không có 2 cột này — đơn giá đã bao gồm thuế, phí, lệ phí)
 *
 * Idempotent: chạy nhiều lần vẫn an toàn.
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
    // ============ 1. Mã HH cho bg_hang_hoa ============
    say('→ 1. Cột ma_hh (Mã HH) trong bg_hang_hoa...');

    if (coCot($pdo, 'bg_hang_hoa', 'ma_hh')) {
        say('  = cột ma_hh đã có');
    } else {
        $pdo->exec("ALTER TABLE bg_hang_hoa
                    ADD COLUMN ma_hh VARCHAR(50) NULL
                    COMMENT 'Mã hàng hóa (VD: VT001) — Phụ lục III & Mẫu 1, Mẫu 2'
                    AFTER goi_thau_id");
        say('  + cột ma_hh');
    }

    // Index để tra nhanh + kiểm trùng trong 1 gói thầu
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bg_hang_hoa'
           AND INDEX_NAME = 'idx_ma_hh'"
    );
    $stmt->execute();
    if ((int)$stmt->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE bg_hang_hoa ADD INDEX idx_ma_hh (goi_thau_id, ma_hh)");
        say('  + index idx_ma_hh');
    } else {
        say('  = index idx_ma_hh đã có');
    }

    // Sinh mã cho hàng hóa cũ chưa có: HH001, HH002... theo từng gói thầu
    $rows = $pdo->query(
        "SELECT id, goi_thau_id FROM bg_hang_hoa
         WHERE (ma_hh IS NULL OR ma_hh = '') AND da_xoa = 0
         ORDER BY goi_thau_id, thu_tu, id"
    )->fetchAll();

    if (empty($rows)) {
        say('  = mọi hàng hóa đều đã có mã');
    } else {
        $upd = $pdo->prepare("UPDATE bg_hang_hoa SET ma_hh = :m WHERE id = :id");
        $dem = [];
        foreach ($rows as $r) {
            $gt = (int)$r['goi_thau_id'];
            $dem[$gt] = ($dem[$gt] ?? 0) + 1;
            $upd->execute([':m' => 'HH' . str_pad((string)$dem[$gt], 3, '0', STR_PAD_LEFT), ':id' => (int)$r['id']]);
        }
        say('  ✓ Sinh mã cho ' . count($rows) . ' hàng hóa cũ.');
    }

    // ============ 2. (ĐÃ BỮA BỌ) Số thông báo mời thầu ============
    // Mẫu 2 chỉ có 14 cột: ghi chú (12) "Điền số thông báo mời thầu" chính là nói về
    // cột `tai_lieu_tham_chieu`, KHÔNG phải một cột thứ 13 riêng.
    // Bước này trước đây tạo thừa cột so_thong_bao_moi_thau — nay bỏ.
    // Nếu DB đã lỡ tạo cột đó, chạy: php database/migrate_bo_cot_so_thong_bao.php --that
    say('');
    say('→ 2. Cột so_thong_bao_moi_thau — ĐÃ BỪA BỎI BƯỚC NÀY (xem migrate_bo_cot_so_thong_bao.php)');

    // ============ 3. XÓA cột không có trong Mẫu 2 ============
    say('');
    say('→ 3. Xóa cột không có trong Mẫu 2 (thue_vat, chi_phi_dich_vu)...');
    say('    Mẫu 2: "Đơn giá (đã bao gồm thuế phí, lệ phí và các dịch vụ liên quan)"');

    foreach (['thue_vat', 'chi_phi_dich_vu'] as $cot) {
        if (!coCot($pdo, 'bg_bao_gia_chi_tiet', $cot)) {
            say("  = cột {$cot} đã được gỡ trước đó");
            continue;
        }
        // Báo số liệu sắp mất để người chạy biết
        $coDuLieu = (int)$pdo->query("SELECT COUNT(*) FROM bg_bao_gia_chi_tiet
                                      WHERE `{$cot}` IS NOT NULL AND `{$cot}` <> 0")->fetchColumn();
        $pdo->exec("ALTER TABLE bg_bao_gia_chi_tiet DROP COLUMN `{$cot}`");
        say("  - đã xóa {$cot}" . ($coDuLieu > 0 ? " ({$coDuLieu} dòng có dữ liệu — đã mất)" : ''));
    }

    say('');
    say('HOÀN TẤT.');
    say('');
    say('LƯU Ý: đơn giá nhà thầu nhập từ giờ là GIÁ ĐÃ GỒM thuế, phí, lệ phí');
    say('và các dịch vụ liên quan — đúng như Mẫu 2 quy định.');
} catch (Throwable $ex) {
    say('LỖI: ' . $ex->getMessage());
    exit(1);
}
