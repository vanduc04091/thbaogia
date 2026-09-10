<?php
/**
 * migrate_bo_gia.php — Gỡ các "BỘ GIẢ" trong dữ liệu cũ.
 *
 * Bản import đầu tiên ép MỌI hàng hóa phải thuộc một bộ, nên hàng lẻ (vật tư,
 * dược...) bị bọc trong một bộ mang ĐÚNG TÊN hàng hóa đó. Kết quả: bảng hiện
 * 2 dòng cho 1 món, dữ liệu lặp vô nghĩa.
 *
 * Giờ `bg_hang_hoa.bo_id = NULL` nghĩa là hàng lẻ hợp lệ, nên script này:
 *   1. Tìm bộ chỉ có ĐÚNG 1 hàng hóa VÀ tên bộ trùng tên hàng hóa đó
 *   2. Gỡ hàng ra khỏi bộ (bo_id = NULL, stt_chi_tiet = NULL)
 *   3. Xóa hẳn bộ giả
 *
 * KHÔNG đụng bộ thật (nhiều chi tiết, hoặc tên khác tên hàng), và không đụng
 * bộ có mang yêu cầu chung/khác/cấu hình — đó là dữ liệu bên mời nhập.
 *
 * Chạy:  php database/migrate_bo_gia.php
 *        php database/migrate_bo_gia.php --thu   (chỉ xem, không sửa)
 *
 * Chạy lại nhiều lần vẫn an toàn.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Script nay chi chay bang dong lenh (CLI).\n");
}

require_once __DIR__ . '/../bootstrap.php';

$thu = in_array('--thu', $argv ?? [], true);

function say(string $m = ''): void { echo $m . "\n"; }

say('===========================================================');
say('  GỠ BỘ GIẢ (bộ chỉ bọc 1 hàng hóa cùng tên)');
say('  DB: ' . AppConfig::DB_NAME);
say('===========================================================');
if ($thu) say('*** CHẾ ĐỘ THỬ: chỉ liệt kê, KHÔNG sửa gì ***');
say('');

try {
    $pdo = Database::getConnection();

    // Bộ có ĐÚNG 1 hàng hóa, tên trùng, và KHÔNG mang yêu cầu cấp bộ nào
    $sql = "SELECT b.id, b.ten_bo, b.goi_thau_id, gt.so_thong_bao, gt.nhom,
                   hh.id AS hh_id, hh.ten_hang_hoa
            FROM bg_bo b
            INNER JOIN bg_goi_thau gt ON gt.id = b.goi_thau_id
            INNER JOIN bg_hang_hoa hh ON hh.bo_id = b.id AND hh.da_xoa = 0
            WHERE b.da_xoa = 0
              AND b.yeu_cau_chung IS NULL
              AND b.yeu_cau_khac IS NULL
              AND b.yeu_cau_cau_hinh IS NULL
              AND TRIM(b.ten_bo) = TRIM(hh.ten_hang_hoa)
              AND (SELECT COUNT(*) FROM bg_hang_hoa h2
                    WHERE h2.bo_id = b.id AND h2.da_xoa = 0) = 1
            ORDER BY gt.so_thong_bao, b.thu_tu";

    $rows = $pdo->query($sql)->fetchAll();

    // ---- BỘ RỖNG: không còn hàng hóa nào, cũng không mang yêu cầu gì ----
    // Sinh ra khi hàng hóa bị gỡ khỏi bộ. Để lại thì bảng hiện dòng tiêu đề
    // trống trơn, nhà thầu tưởng thiếu dữ liệu.
    $rong = $pdo->query(
        "SELECT b.id, b.ten_bo, gt.so_thong_bao
         FROM bg_bo b
         INNER JOIN bg_goi_thau gt ON gt.id = b.goi_thau_id
         WHERE b.da_xoa = 0
           AND b.yeu_cau_chung IS NULL
           AND b.yeu_cau_khac IS NULL
           AND b.yeu_cau_cau_hinh IS NULL
           AND NOT EXISTS (SELECT 1 FROM bg_hang_hoa h
                            WHERE h.bo_id = b.id AND h.da_xoa = 0)"
    )->fetchAll();

    foreach ($rong as $r) {
        printf("  [%s] bộ RỖNG #%d \"%s\"
", $r['so_thong_bao'], $r['id'],
            mb_substr((string)$r['ten_bo'], 0, 48));
    }

    if (empty($rows) && empty($rong)) {
        say('  = Không có bộ giả nào — dữ liệu đã sạch.');
        say('');
        say('===========================================================');
        exit(0);
    }

    foreach ($rows as $r) {
        printf("  [%s / %s] bộ #%d \"%s\"\n",
            $r['so_thong_bao'], $r['nhom'], $r['id'], mb_substr($r['ten_bo'], 0, 48));
    }
    say('');

    if ($thu) {
        say('  => SẼ gỡ ' . count($rows) . ' bộ giả + ' . count($rong)
           . ' bộ rỗng (chạy lại bỏ --thu để sửa thật)');
        say('');
        say('===========================================================');
        exit(0);
    }

    Database::beginTransaction();

    $stHh = $pdo->prepare(
        "UPDATE bg_hang_hoa SET bo_id = NULL, stt_chi_tiet = NULL, ngay_cap_nhat = NOW()
         WHERE id = :id"
    );
    $stBo = $pdo->prepare("DELETE FROM bg_bo WHERE id = :id");

    foreach ($rows as $r) {
        $stHh->execute([':id' => (int)$r['hh_id']]);
        $stBo->execute([':id' => (int)$r['id']]);
    }
    foreach ($rong as $r) {
        $stBo->execute([':id' => (int)$r['id']]);
    }

    Database::commit();
    say('  + đã gỡ ' . count($rows) . ' bộ giả (' . count($rows) . ' hàng thành HÀNG LẺ)'
       . ($rong ? ', xóa ' . count($rong) . ' bộ rỗng' : ''));

    // --- Hiện trạng ---
    say('');
    say('→ Hiện trạng');
    say('    bộ còn lại : ' . (int)$pdo->query("SELECT COUNT(*) FROM bg_bo WHERE da_xoa = 0")->fetchColumn());
    say('    hàng hóa   : ' . (int)$pdo->query("SELECT COUNT(*) FROM bg_hang_hoa WHERE da_xoa = 0")->fetchColumn());
    say('    hàng lẻ    : ' . (int)$pdo->query("SELECT COUNT(*) FROM bg_hang_hoa WHERE da_xoa = 0 AND bo_id IS NULL")->fetchColumn());

    say('');
    say('===========================================================');
    say('  HOÀN TẤT');
    say('===========================================================');

} catch (Throwable $ex) {
    if (Database::getConnection()->inTransaction()) Database::rollBack();
    say('');
    say('!!! LỖI: ' . $ex->getMessage());
    exit(1);
}
