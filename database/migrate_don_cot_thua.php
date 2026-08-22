<?php
/**
 * migrate_don_cot_thua.php — Gỡ các cột KHÔNG có trong Thư mời chào giá.
 *
 * CHẠY THỦ CÔNG:  php database/migrate_don_cot_thua.php            (kiểm tra)
 *                 php database/migrate_don_cot_thua.php --that     (thực sự xóa)
 *
 * ⚠️ DROP COLUMN KHÔNG HOÀN TÁC ĐƯỢC — SAO LƯU TRƯỚC:
 *     php database/sao_luu.php
 *
 * Các cột này còn sót từ file Excel mẫu 30 cột cũ, KHÔNG xuất hiện trong
 * Phụ lục II (Mẫu 1, Mẫu 2) hay Phụ lục III của Thư mời:
 *
 * bg_hang_hoa (Phụ lục III chỉ có: STT | Mã HH | Tên hàng hóa | YCKT | ĐVT | Số lượng)
 *   - ten_phan, stt_theo_phan, stt_thong_bao : đánh số kiểu cũ, nay dùng ma_hh
 *   - chung_nhan, yeu_cau_xuat_xu, yeu_cau_tro_cu : không có trong Phụ lục III
 *
 * bg_bao_gia_chi_tiet (Mẫu 1 + Mẫu 2)
 *   - ma_hs, chung_nhan_chao, ma_qr_hang_hoa : không có trong 2 mẫu
 *
 * Mặc định chỉ BÁO CÁO, không xóa. Phải thêm --that mới thực sự DROP.
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

/** Đếm số dòng còn dữ liệu ở cột sắp xóa — để người chạy biết mất gì */
function demCoDuLieu(PDO $pdo, string $bang, string $cot): int
{
    $sql = "SELECT COUNT(*) FROM `{$bang}` WHERE `{$cot}` IS NOT NULL AND `{$cot}` <> ''";
    return (int)$pdo->query($sql)->fetchColumn();
}

$canXoa = [
    'bg_hang_hoa' => [
        'ten_phan'        => 'Tên phần (Phụ lục III không có)',
        'stt_theo_phan'   => 'STT theo phần (thay bằng Mã HH)',
        'stt_thong_bao'   => 'STT TB mời chào giá (thay bằng Mã HH)',
        'chung_nhan'      => 'Chứng nhận yêu cầu (Phụ lục III không có)',
        'yeu_cau_xuat_xu' => 'Yêu cầu xuất xứ (Phụ lục III không có)',
        'yeu_cau_tro_cu'  => 'Yêu cầu trợ cụ (Phụ lục III không có)',
    ],
    'bg_bao_gia_chi_tiet' => [
        'ma_hs'           => 'Mã HS (Mẫu 2 không có)',
        'chung_nhan_chao' => 'Chứng nhận hàng hóa chào (Mẫu 2 không có)',
        'ma_qr_hang_hoa'  => 'Mã QR/Barcode hàng hóa (Mẫu 2 không có)',
    ],
];

try {
    // Điều kiện tiên quyết: phải có ma_hh rồi mới được bỏ cách đánh số cũ
    if (!coCot($pdo, 'bg_hang_hoa', 'ma_hh')) {
        say('LỖI: chưa có cột ma_hh. Chạy migrate_theo_thu_moi.php trước.');
        exit(1);
    }
    $thieuMa = (int)$pdo->query(
        "SELECT COUNT(*) FROM bg_hang_hoa WHERE (ma_hh IS NULL OR ma_hh = '') AND da_xoa = 0"
    )->fetchColumn();
    if ($thieuMa > 0) {
        say("LỖI: còn {$thieuMa} hàng hóa chưa có Mã HH. Chạy migrate_theo_thu_moi.php trước.");
        exit(1);
    }

    say('→ Các cột sẽ gỡ:');
    say('');

    $tong = 0;
    foreach ($canXoa as $bang => $cots) {
        say("  [{$bang}]");
        foreach ($cots as $cot => $lyDo) {
            if (!coCot($pdo, $bang, $cot)) {
                say("    = {$cot} — đã gỡ trước đó");
                continue;
            }
            $n = demCoDuLieu($pdo, $bang, $cot);
            say("    - {$cot}: {$lyDo}" . ($n > 0 ? "  [{$n} dòng có dữ liệu — SẼ MẤT]" : '  [trống]'));
            $tong++;
        }
        say('');
    }

    if ($tong === 0) {
        say('Không còn cột nào để gỡ.');
        exit(0);
    }

    if (!$laThat) {
        say('Đây mới là bước KIỂM TRA — chưa xóa gì.');
        say('Sao lưu rồi chạy lại với cờ --that:');
        say('    php database/sao_luu.php');
        say('    php database/migrate_don_cot_thua.php --that');
        exit(0);
    }

    say('→ Đang gỡ cột...');
    foreach ($canXoa as $bang => $cots) {
        foreach ($cots as $cot => $lyDo) {
            if (!coCot($pdo, $bang, $cot)) continue;
            $pdo->exec("ALTER TABLE `{$bang}` DROP COLUMN `{$cot}`");
            say("  - đã gỡ {$bang}.{$cot}");
        }
    }

    say('');
    say('HOÀN TẤT. Cấu trúc bảng giờ khớp đúng Phụ lục II & III của Thư mời.');
} catch (Throwable $ex) {
    say('LỖI: ' . $ex->getMessage());
    exit(1);
}
