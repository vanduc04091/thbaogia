<?php
/**
 * migrate_bo_cot_so_thong_bao.php
 *
 * Bỏ cột `bg_bao_gia_chi_tiet.so_thong_bao_moi_thau`.
 *
 * LÝ DO: đọc lại Phụ lục II của Thư mời thì Mẫu 2 chỉ có 14 cột, kết thúc ở
 * "Tài liệu tham chiếu đơn giá trúng thầu gần nhất (12)". Ghi chú (12) chính là
 * "Công ty điền số thông báo mời thầu (Ví dụ: IB2500…)" — tức là số thông báo
 * mời thầu ghi LUÔN vào cột 12, không phải một cột thứ 13 riêng.
 *
 * Trước đây hiểu nhầm thành 2 cột nên sinh thừa `so_thong_bao_moi_thau`.
 *
 * AN TOÀN: script tự kiểm tra dữ liệu trước khi xóa.
 *   - Chạy không tham số  -> chỉ BÁO CÁO, không đụng vào DB.
 *   - Chạy với --that     -> mới thực sự DROP COLUMN.
 *   - Nếu cột còn dữ liệu, script sẽ GỘP vào `tai_lieu_tham_chieu` trước khi xóa
 *     (không làm mất thông tin nhà thầu đã nhập).
 *
 * Cách chạy:
 *   php database/migrate_bo_cot_so_thong_bao.php           # xem trước
 *   php database/migrate_bo_cot_so_thong_bao.php --that    # làm thật
 */

require_once __DIR__ . '/../bootstrap.php';

function say(string $s = ''): void
{
    echo $s . PHP_EOL;
}

function coCot(PDO $pdo, string $bang, string $cot): bool
{
    $sql = "SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :bang AND COLUMN_NAME = :cot";
    $st = $pdo->prepare($sql);
    $st->execute([':db' => AppConfig::DB_NAME, ':bang' => $bang, ':cot' => $cot]);
    return (int)$st->fetchColumn() > 0;
}

$lamThat = in_array('--that', $argv ?? [], true);

say('===========================================================');
say('  BỎ CỘT so_thong_bao_moi_thau (Mẫu 2 chỉ có 14 cột)');
say('  DB: ' . AppConfig::DB_NAME);
say('  Chế độ: ' . ($lamThat ? 'LÀM THẬT (--that)' : 'CHỈ BÁO CÁO'));
say('===========================================================');
say();

try {
    $pdo = Database::getConnection();

    if (!coCot($pdo, 'bg_bao_gia_chi_tiet', 'so_thong_bao_moi_thau')) {
        say('= Cột so_thong_bao_moi_thau không tồn tại — đã xử lý trước đó.');
        say();
        say('XONG. Không có gì để làm.');
        exit(0);
    }

    // --- Kiểm tra dữ liệu đang có ---
    $tong = (int)$pdo->query('SELECT COUNT(*) FROM bg_bao_gia_chi_tiet')->fetchColumn();
    $coDl = (int)$pdo->query(
        "SELECT COUNT(*) FROM bg_bao_gia_chi_tiet
         WHERE so_thong_bao_moi_thau IS NOT NULL AND so_thong_bao_moi_thau <> ''"
    )->fetchColumn();

    say("  Tổng số dòng chi tiết       : {$tong}");
    say("  Dòng có số thông báo mời thầu: {$coDl}");
    say();

    if ($coDl > 0) {
        // Gộp vào tai_lieu_tham_chieu để không mất dữ liệu nhà thầu đã nhập
        $ds = $pdo->query(
            "SELECT id, tai_lieu_tham_chieu, so_thong_bao_moi_thau
             FROM bg_bao_gia_chi_tiet
             WHERE so_thong_bao_moi_thau IS NOT NULL AND so_thong_bao_moi_thau <> ''
             LIMIT 10"
        )->fetchAll(PDO::FETCH_ASSOC);

        say('  Sẽ GỘP số thông báo vào cột tai_lieu_tham_chieu. Ví dụ:');
        foreach ($ds as $d) {
            $cu  = trim((string)$d['tai_lieu_tham_chieu']);
            $moi = $cu === '' ? $d['so_thong_bao_moi_thau'] : $cu . ' — ' . $d['so_thong_bao_moi_thau'];
            say("    ct{$d['id']}: [{$cu}] + [{$d['so_thong_bao_moi_thau']}]");
            say("            -> [{$moi}]");
        }
        say();
    } else {
        say('  Cột đang RỖNG hoàn toàn — xóa không mất dữ liệu gì.');
        say();
    }

    if (!$lamThat) {
        say('-----------------------------------------------------------');
        say('  ĐANG Ở CHẾ ĐỘ XEM TRƯỚC — chưa đụng gì vào database.');
        say('  Chạy lại kèm --that để thực hiện:');
        say('    php database/migrate_bo_cot_so_thong_bao.php --that');
        say('-----------------------------------------------------------');
        exit(0);
    }

    // ================= LÀM THẬT =================
    Database::beginTransaction();

    if ($coDl > 0) {
        $n = $pdo->exec(
            "UPDATE bg_bao_gia_chi_tiet
             SET tai_lieu_tham_chieu = TRIM(CONCAT(
                     COALESCE(NULLIF(tai_lieu_tham_chieu, ''), ''),
                     CASE WHEN NULLIF(tai_lieu_tham_chieu, '') IS NULL THEN '' ELSE ' — ' END,
                     so_thong_bao_moi_thau))
             WHERE so_thong_bao_moi_thau IS NOT NULL AND so_thong_bao_moi_thau <> ''"
        );
        say("  + đã gộp {$n} dòng vào tai_lieu_tham_chieu");
    }

    Database::commit();

    // DDL không nằm trong transaction được (MySQL tự commit ngầm)
    $pdo->exec('ALTER TABLE bg_bao_gia_chi_tiet DROP COLUMN so_thong_bao_moi_thau');
    say('  + đã DROP cột so_thong_bao_moi_thau');

    say();
    say('===========================================================');
    say('  XONG. Mẫu 2 giờ đúng 14 cột như Phụ lục II Thư mời.');
    say('===========================================================');
} catch (Throwable $ex) {
    if (Database::getConnection()->inTransaction()) {
        Database::rollBack();
    }
    say();
    say('LỖI: ' . $ex->getMessage());
    exit(1);
}
