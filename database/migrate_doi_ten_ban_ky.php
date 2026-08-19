<?php
/**
 * migrate_doi_ten_ban_ky.php — Đổi tên các file bản ký ĐÃ UPLOAD sang quy tắc mới.
 *
 * CHẠY THỦ CÔNG:  php database/migrate_doi_ten_ban_ky.php
 *                 php database/migrate_doi_ten_ban_ky.php --that     (thực thi)
 *
 * MẶC ĐỊNH LÀ CHẠY THỬ (dry-run): chỉ in ra dự định đổi tên, KHÔNG đụng vào file.
 * Thêm cờ --that mới thực sự đổi tên. Đây là thao tác trên file thật nên phải
 * xem trước rồi mới chạy.
 *
 * Quy tắc tên mới:  <mst>_<slug-goi-thau>.<ext>
 *   VD: 0101234567_mua-vat-tu-tieu-hao-phau-thuat-cot-song.pdf
 * Nếu trùng tên (1 MST nộp lại nhiều lần) thì thêm hậu tố -2, -3...
 *
 * Tên cũ dạng: bk_<id>_<ngay>_<random>.<ext>
 *
 * Đọc/ghi trên bảng `bg_file` — chạy SAU migrate_bang_file.php.
 */
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../BUS/BG_BaoGia_BUS.php';

$isCli   = PHP_SAPI === 'cli';
$laThat  = $isCli && in_array('--that', $argv ?? [], true);
if (!$isCli) header('Content-Type: text/plain; charset=utf-8');
function say(string $m): void { echo $m . "\n"; }

$pdo = Database::getConnection();

try {
    $dir = BG_BaoGia_BUS::thuMucBanKy();

    // Đọc từ bảng bg_file (bg_bao_gia chỉ còn khóa file_ban_ky_id)
    $rows = $pdo->query(
        "SELECT f.id AS file_id, f.ten_file, f.ten_file_goc,
                bg.id, bg.ma_so_thue,
                gt.so_thong_bao, gt.ten_goi_thau
         FROM bg_file f
         INNER JOIN bg_bao_gia bg ON bg.file_ban_ky_id = f.id
         INNER JOIN bg_goi_thau gt ON gt.id = bg.goi_thau_id
         WHERE f.da_xoa = 0 AND bg.da_xoa = 0
         ORDER BY f.id"
    )->fetchAll();

    if (empty($rows)) {
        say('Không có file bản ký nào cần đổi tên.');
        exit(0);
    }

    say($laThat ? '=== THỰC THI ĐỔI TÊN ===' : '=== CHẠY THỬ (dry-run) — chưa đụng vào file ===');
    say('Số file: ' . count($rows));
    say('');

    $upd = $pdo->prepare("UPDATE bg_file SET ten_file = :f, ngay_cap_nhat = NOW() WHERE id = :id");
    $daDung = [];      // tên đã dùng trong lượt chạy này
    $soDoi = 0;
    $soBoQua = 0;

    foreach ($rows as $r) {
        $id      = (int)$r['id'];          // id báo giá (để in ra cho dễ đối chiếu)
        $fileId  = (int)$r['file_id'];     // id bản ghi trong bg_file
        $tenCu   = basename((string)$r['ten_file']);
        $duongCu = $dir . DIRECTORY_SEPARATOR . $tenCu;

        if (!is_file($duongCu)) {
            say("  ! #{$id}: KHÔNG thấy file '{$tenCu}' — bỏ qua");
            $soBoQua++;
            continue;
        }

        $ext = strtolower(pathinfo($tenCu, PATHINFO_EXTENSION));
        $tenMoi = BG_BaoGia_BUS::tenFileBanKy(
            (string)$r['ma_so_thue'],
            (string)$r['ten_goi_thau'],
            (string)$r['so_thong_bao'],
            $ext,
            $dir,
            $daDung,
            $tenCu            // bỏ qua chính nó khi kiểm trùng
        );

        if ($tenMoi === $tenCu) {
            say("  = #{$id}: đã đúng quy tắc '{$tenCu}'");
            $daDung[$tenMoi] = true;
            continue;
        }

        say("  → #{$id}: {$tenCu}");
        say("        thành  {$tenMoi}");

        if ($laThat) {
            $duongMoi = $dir . DIRECTORY_SEPARATOR . $tenMoi;
            if (!@rename($duongCu, $duongMoi)) {
                say("     LỖI: không đổi được tên file — bỏ qua, DB giữ nguyên");
                $soBoQua++;
                continue;
            }
            $upd->execute([':f' => $tenMoi, ':id' => $fileId]);
        }
        $daDung[$tenMoi] = true;
        $soDoi++;
    }

    say('');
    if ($laThat) {
        say("HOÀN TẤT: đã đổi tên {$soDoi} file" . ($soBoQua ? ", bỏ qua {$soBoQua}" : '') . '.');
    } else {
        say("Sẽ đổi tên {$soDoi} file" . ($soBoQua ? ", bỏ qua {$soBoQua}" : '') . '.');
        say('Chạy lại với cờ --that để thực thi:');
        say('    php database/migrate_doi_ten_ban_ky.php --that');
    }
} catch (Throwable $ex) {
    say('LỖI: ' . $ex->getMessage());
    exit(1);
}
