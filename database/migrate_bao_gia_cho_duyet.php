<?php
/**
 * migrate_bao_gia_cho_duyet.php
 *
 * Chuẩn hóa dữ liệu cũ cho quy trình duyệt mới (§10.2):
 *
 *   - Nhà thầu bấm "Hoàn thành" -> da_hoan_thanh = 1, trang_thai = 0 (Chờ duyệt)
 *   - Bên mời duyệt              -> trang_thai = 1 (Đã xác nhận)
 *   - Chưa hoàn thành            -> ẩn khỏi phần quản trị, tra cứu không thấy,
 *                                   cron dọn sau 24h
 *
 * Trước đây báo giá được xác nhận NGAY khi nhà thầu upload bản ký, nên trong DB
 * còn những bản `trang_thai = 1` mà `da_hoan_thanh = 0`. Với luật lọc mới, các
 * bản này sẽ biến mất khỏi cả danh sách quản trị lẫn bảng tổng hợp — coi như
 * mất báo giá đã duyệt.
 *
 * Script này đánh dấu chúng `da_hoan_thanh = 1` để giữ nguyên hiện trạng: đã
 * duyệt thì vẫn duyệt, vẫn vào tổng hợp, vẫn xem được ở phần quản trị.
 *
 * KHÔNG đụng tới báo giá `trang_thai = 0` chưa hoàn thành — đó đúng là bản nháp,
 * để nguyên cho cron dọn.
 *
 * Chạy:  php database/migrate_bao_gia_cho_duyet.php
 *        php database/migrate_bao_gia_cho_duyet.php --thu   (chỉ xem, không sửa)
 *
 * Chạy lại nhiều lần vẫn an toàn (idempotent).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Script nay chi chay bang dong lenh (CLI).\n");
}

require_once __DIR__ . '/../bootstrap.php';

$thu = in_array('--thu', $argv, true);

echo "=== CHUAN HOA BAO GIA CHO QUY TRINH DUYET ===\n";
echo "DB: " . AppConfig::DB_NAME . "\n";
if ($thu) echo "*** CHE DO THU: chi liet ke, KHONG sua gi ***\n";
echo "\n";

$db = Database::getConnection();

// ---------------------------------------------------------------
// 1. Bao gia DA DUYET (tt=1) hoac DA TU CHOI (tt=2) nhung chua danh dau
//    hoan thanh -> danh dau lai, neu khong se bi an mat.
// ---------------------------------------------------------------
$rows = $db->query(
    "SELECT id, ten_cong_ty, ma_so_thue, goi_thau_id, trang_thai
     FROM bg_bao_gia
     WHERE da_xoa = 0 AND da_hoan_thanh = 0 AND trang_thai IN (1, 2)
     ORDER BY id"
)->fetchAll();

echo "[1] Bao gia da duyet/tu choi nhung chua danh dau hoan thanh\n";
if (empty($rows)) {
    echo "    = khong co ban ghi nao, bo qua\n";
} else {
    foreach ($rows as $r) {
        printf("    - #%-4d goi %-3d tt=%d  %s (MST %s)\n",
            $r['id'], $r['goi_thau_id'], $r['trang_thai'],
            mb_substr((string)$r['ten_cong_ty'], 0, 40), (string)$r['ma_so_thue']);
    }

    if ($thu) {
        echo "    => SE danh dau " . count($rows) . " ban ghi (chay lai bo --thu de sua that)\n";
    } else {
        // ngay_hoan_thanh lay theo moc co that da co san, khong bia ra NOW()
        $n = $db->exec(
            "UPDATE bg_bao_gia
                SET da_hoan_thanh = 1,
                    ngay_hoan_thanh = COALESCE(ngay_hoan_thanh, ngay_xac_nhan, ngay_nop, ngay_tao),
                    ngay_cap_nhat = ngay_cap_nhat
              WHERE da_xoa = 0 AND da_hoan_thanh = 0 AND trang_thai IN (1, 2)"
        );
        echo "    + da danh dau {$n} ban ghi la da hoan thanh\n";
    }
}
echo "\n";

// ---------------------------------------------------------------
// 2. Thong ke lai cho de doi chieu
// ---------------------------------------------------------------
echo "[2] Hien trang sau khi chay\n";
$tk = $db->query(
    "SELECT trang_thai, da_hoan_thanh, COUNT(*) n
     FROM bg_bao_gia WHERE da_xoa = 0
     GROUP BY trang_thai, da_hoan_thanh ORDER BY trang_thai, da_hoan_thanh"
)->fetchAll();

$ten = [0 => 'Cho duyet', 1 => 'Da duyet', 2 => 'Tu choi'];
foreach ($tk as $r) {
    printf("    tt=%d (%-9s) hoan_thanh=%d  -> %d ban ghi%s\n",
        $r['trang_thai'], $ten[(int)$r['trang_thai']] ?? '?', $r['da_hoan_thanh'], $r['n'],
        (int)$r['da_hoan_thanh'] === 0 ? '   [ban nhap - an khoi quan tri, cron don sau 24h]' : ''
    );
}

echo "\n=== XONG ===\n";
