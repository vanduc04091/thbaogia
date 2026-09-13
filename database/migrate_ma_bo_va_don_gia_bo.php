<?php
/**
 * migrate_ma_bo_va_don_gia_bo.php
 *
 * Làm 2 việc, đều là dọn hậu quả của các lần đổi quy tắc trước đó:
 *
 * 1. XÓA DÒNG GIÁ BỘ CHẾT trong bg_bao_gia_bo.
 *    Từ khi chốt: chỉ nhóm he_thong_tbyt mới nhập giá bộ tay
 *    (BG_Nhom_PUBLIC::giaBoNhapTay()). Nên các dòng sau là dữ liệu chết,
 *    không được cộng vào tổng ở bất cứ đâu, chỉ gây nhầm khi đối chiếu:
 *      - Báo giá thuộc nhóm vat_tu_duoc  (nhóm này không mua theo bộ)
 *      - Báo giá thuộc nhóm bo_dung_cu   (tiền bộ = tổng chi tiết)
 *    KHÔNG đụng dòng của he_thong_tbyt — đó là dữ liệu thật đang dùng.
 *
 *    Phần ĐÁP ỨNG cấp bộ (dap_ung_chung, thong_so_chao_gia...) của nhóm
 *    bo_dung_cu VẪN CẦN, nên chỉ xóa dòng khi nó KHÔNG có đáp ứng nào;
 *    dòng còn đáp ứng thì chỉ đưa giá về 0.
 *
 * 2. VÁ MÃ BỘ còn trống (ma_bo IS NULL / rỗng) -> sinh BO01, BO02...
 *    Mã bộ là thứ duy nhất để đối chiếu dòng BỘ khi nhà thầu import file
 *    chào giá. Bộ không có mã thì dữ liệu nhà thầu điền ở dòng đó bị bỏ
 *    qua IM LẶNG — đã gặp thật, mất trắng cả bảng đáp ứng.
 *    Từ nay BG_Bo_BUS::insert() và importExcel() tự sinh mã nên lỗi này
 *    không tái diễn; file này chỉ vá dữ liệu cũ.
 *
 * Idempotent: chạy lại nhiều lần vẫn an toàn.
 *
 * Cách chạy:  php database/migrate_ma_bo_va_don_gia_bo.php
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../PUBLIC/Entities/BG_Nhom_PUBLIC.php';

function say(string $s = ''): void { echo $s . PHP_EOL; }

$pdo = Database::getConnection();

say('');
say('===========================================================');
say('  DỌN GIÁ BỘ CHẾT + VÁ MÃ BỘ');
say('===========================================================');

// =====================================================================
// 1. Dòng giá bộ chết
// =====================================================================
say('');
say('--- 1. Dòng giá bộ của nhóm KHÔNG nhập giá bộ tay ---');

$sql = "SELECT gb.id, gb.bao_gia_id, gb.bo_id, gb.don_gia, gb.thanh_tien,
               gt.nhom, gt.so_thong_bao, bg.ten_cong_ty,
               CONCAT_WS('', gb.dap_ung_chung, gb.khong_dat_chung,
                             gb.dap_ung_khac, gb.khong_dat_khac,
                             gb.dap_ung_cau_hinh, gb.khong_dat_cau_hinh,
                             gb.thong_so_chao_gia, gb.diem_khong_dat,
                             gb.dap_ung_nhom_nuoc, gb.khong_dat_nhom_nuoc,
                             gb.tai_lieu_chung_minh) AS co_dap_ung
          FROM bg_bao_gia_bo gb
          INNER JOIN bg_bao_gia bg ON bg.id = gb.bao_gia_id
          INNER JOIN bg_goi_thau gt ON gt.id = bg.goi_thau_id
         WHERE gb.da_xoa = 0";

$soXoa = 0;
$soVe0 = 0;
foreach ($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $nhom = BG_Nhom_PUBLIC::chuanHoa($r['nhom']);
    if (BG_Nhom_PUBLIC::giaBoNhapTay($nhom)) {
        continue;   // he_thong_tbyt — giữ nguyên
    }

    $coGia    = (float)$r['don_gia'] > 0 || (float)$r['thanh_tien'] > 0;
    $coDapUng = trim((string)$r['co_dap_ung']) !== '';

    if (!$coGia && !$coDapUng) {
        $pdo->prepare('DELETE FROM bg_bao_gia_bo WHERE id = :id')
            ->execute([':id' => $r['id']]);
        say(sprintf('  - XÓA  id=%-3s bg=%-3s bo=%-3s %-14s (rỗng hoàn toàn)',
            $r['id'], $r['bao_gia_id'], $r['bo_id'], $nhom));
        $soXoa++;
    } elseif ($coGia && !$coDapUng) {
        $pdo->prepare('DELETE FROM bg_bao_gia_bo WHERE id = :id')
            ->execute([':id' => $r['id']]);
        say(sprintf('  - XÓA  id=%-3s bg=%-3s bo=%-3s %-14s giá %s (không có đáp ứng)',
            $r['id'], $r['bao_gia_id'], $r['bo_id'], $nhom, $r['thanh_tien']));
        $soXoa++;
    } elseif ($coGia) {
        // Còn đáp ứng -> giữ dòng, chỉ bỏ giá
        $pdo->prepare('UPDATE bg_bao_gia_bo SET don_gia = 0, thanh_tien = 0,
                              ngay_cap_nhat = NOW() WHERE id = :id')
            ->execute([':id' => $r['id']]);
        say(sprintf('  ~ VỀ 0 id=%-3s bg=%-3s bo=%-3s %-14s (giữ phần đáp ứng)',
            $r['id'], $r['bao_gia_id'], $r['bo_id'], $nhom));
        $soVe0++;
    }
}
if ($soXoa === 0 && $soVe0 === 0) say('  = không có dòng nào cần dọn.');
else say("  → xóa {$soXoa} dòng, đưa giá về 0 cho {$soVe0} dòng.");

// =====================================================================
// 2. Vá mã bộ
// =====================================================================
say('');
say('--- 2. Bộ chưa có Mã bộ ---');

$dsBo = $pdo->query(
    "SELECT b.id, b.goi_thau_id, b.stt_bo, b.ten_bo, gt.so_thong_bao
       FROM bg_bo b INNER JOIN bg_goi_thau gt ON gt.id = b.goi_thau_id
      WHERE b.da_xoa = 0 AND (b.ma_bo IS NULL OR TRIM(b.ma_bo) = '')
      ORDER BY b.goi_thau_id, b.stt_bo, b.id"
)->fetchAll(PDO::FETCH_ASSOC);

if (!$dsBo) {
    say('  = mọi bộ đều đã có mã.');
} else {
    // Số lớn nhất đang dùng theo từng gói, để sinh tiếp không trùng
    $dem = [];
    foreach ($dsBo as $b) {
        $gid = (int)$b['goi_thau_id'];

        if (!isset($dem[$gid])) {
            $st = $pdo->prepare(
                "SELECT ma_bo FROM bg_bo
                  WHERE goi_thau_id = :gt AND da_xoa = 0 AND ma_bo REGEXP '^BO[0-9]+$'"
            );
            $st->execute([':gt' => $gid]);
            $max = 0;
            foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $ma) {
                $max = max($max, (int)substr((string)$ma, 2));
            }
            $dem[$gid] = $max;
        }

        // Tránh trùng mã người dùng đã đặt tay
        do {
            $dem[$gid]++;
            $ma = 'BO' . str_pad((string)$dem[$gid], 2, '0', STR_PAD_LEFT);
            $st = $pdo->prepare(
                "SELECT COUNT(*) FROM bg_bo
                  WHERE goi_thau_id = :gt AND da_xoa = 0 AND UPPER(ma_bo) = :ma"
            );
            $st->execute([':gt' => $gid, ':ma' => $ma]);
        } while ((int)$st->fetchColumn() > 0);

        $pdo->prepare('UPDATE bg_bo SET ma_bo = :ma, ngay_cap_nhat = NOW() WHERE id = :id')
            ->execute([':ma' => $ma, ':id' => $b['id']]);

        say(sprintf('  + bộ id=%-3s gói %-12s stt=%-3s -> %s  (%s)',
            $b['id'], $b['so_thong_bao'], $b['stt_bo'], $ma,
            mb_substr((string)$b['ten_bo'], 0, 32)));
    }
    say('  → đã vá ' . count($dsBo) . ' bộ.');
}

// =====================================================================
// Kết quả
// =====================================================================
say('');
say('--- Kiểm lại ---');
$conThieu = (int)$pdo->query(
    "SELECT COUNT(*) FROM bg_bo WHERE da_xoa = 0 AND (ma_bo IS NULL OR TRIM(ma_bo) = '')"
)->fetchColumn();
say('  Bộ còn thiếu mã: ' . $conThieu);

$conChet = (int)$pdo->query(
    "SELECT COUNT(*) FROM bg_bao_gia_bo gb
       INNER JOIN bg_bao_gia bg ON bg.id = gb.bao_gia_id
       INNER JOIN bg_goi_thau gt ON gt.id = bg.goi_thau_id
      WHERE gb.da_xoa = 0 AND gt.nhom <> 'he_thong_tbyt'
        AND (gb.don_gia > 0 OR gb.thanh_tien > 0)"
)->fetchColumn();
say('  Dòng giá bộ chết còn lại: ' . $conChet);

say('');
say('  XONG.');
say('');
