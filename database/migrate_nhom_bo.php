<?php
/**
 * migrate_nhom_bo.php — Cơ cấu lại theo Thư mời báo giá chung 3 nhóm.
 *
 * Thay đổi lớn, gồm 4 phần:
 *
 *   1. NHÓM GÓI THẦU — bg_goi_thau.nhom
 *      bo_dung_cu     : bộ y dụng cụ      → yêu cầu chung + khác
 *      he_thong_tbyt  : hệ thống TBYT     → yêu cầu chung + khác + cấu hình
 *      vat_tu_duoc    : vật tư, dược      → chỉ yêu cầu kỹ thuật
 *      Gói cũ mặc định `vat_tu_duoc` (ít cột nhất, khớp cấu trúc đang có).
 *
 *   2. BẢNG bg_bo — mỗi gói thầu gồm nhiều BỘ, mỗi bộ gồm nhiều hàng hóa
 *      chi tiết. Hàng lẻ (VD vật tư "Bơm tiêm") vẫn là 1 bộ có đúng 1 chi tiết
 *      → mọi truy vấn dùng chung một đường, không phải phân nhánh.
 *
 *   3. CỘT MỚI cho bg_hang_hoa (bo_id, stt_chi_tiet, nhom_nuoc) và
 *      bg_bao_gia_chi_tiet (các cặp đáp ứng / không đạt theo Mẫu 1 - 21 cột).
 *
 *   4. XÓA HẲN phần catalog (Bước 5 cũ) — user đã xác nhận xóa dữ liệu:
 *      bảng bg_catalog, cột file_catalog_id / file_catalog_excel_id,
 *      file nhóm 'catalog' trong bg_file + file trên đĩa.
 *
 * Idempotent: chạy nhiều lần vẫn an toàn.
 *
 * Cách chạy:  php database/migrate_nhom_bo.php
 *             php database/migrate_nhom_bo.php --thu    (chỉ xem, không sửa)
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Script nay chi chay bang dong lenh (CLI).\n");
}

require_once __DIR__ . '/../bootstrap.php';

$thu = in_array('--thu', $argv, true);

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

function coBang(PDO $pdo, string $bang): bool
{
    $st = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :bang"
    );
    $st->execute([':db' => AppConfig::DB_NAME, ':bang' => $bang]);
    return (int)$st->fetchColumn() > 0;
}

/** Thêm cột nếu chưa có — in rõ đã thêm hay bỏ qua */
function themCot(PDO $pdo, string $bang, string $cot, string $dinhNghia, bool $thu): void
{
    if (coCot($pdo, $bang, $cot)) {
        say("  = {$bang}.{$cot} đã có, bỏ qua");
        return;
    }
    if ($thu) { say("  → SẼ thêm {$bang}.{$cot}"); return; }
    $pdo->exec("ALTER TABLE {$bang} ADD COLUMN {$cot} {$dinhNghia}");
    say("  + {$bang}.{$cot}");
}

say('===========================================================');
say('  CƠ CẤU LẠI THEO THƯ MỜI CHUNG 3 NHÓM');
say('  DB: ' . AppConfig::DB_NAME);
say('===========================================================');
if ($thu) say('*** CHẾ ĐỘ THỬ: chỉ liệt kê, KHÔNG sửa gì ***');
say('');

try {
    $pdo = Database::getConnection();

    // =================================================================
    // 1. NHÓM GÓI THẦU
    // =================================================================
    say('→ 1. Nhóm gói thầu');
    themCot($pdo, 'bg_goi_thau', 'nhom',
        "VARCHAR(20) NOT NULL DEFAULT 'vat_tu_duoc' "
        . "COMMENT 'bo_dung_cu | he_thong_tbyt | vat_tu_duoc' AFTER ten_goi_thau", $thu);
    say('');

    // =================================================================
    // 2. BẢNG bg_bo
    // =================================================================
    say('→ 2. Bảng bg_bo (bộ / phần / hệ thống)');
    if (coBang($pdo, 'bg_bo')) {
        say('  = bảng bg_bo đã có, bỏ qua');
    } elseif ($thu) {
        say('  → SẼ tạo bảng bg_bo');
    } else {
        $pdo->exec("
            CREATE TABLE bg_bo (
                id INT AUTO_INCREMENT PRIMARY KEY,
                goi_thau_id INT NOT NULL,
                ma_bo VARCHAR(50) NULL COMMENT 'Mã bộ/phần/hệ thống — cột (1) Phụ lục III',
                stt_bo INT NULL COMMENT 'STT bộ — cột (2)',
                ten_bo VARCHAR(1000) NULL COMMENT 'Tên bộ/phần/hệ thống — cột (3)',
                yeu_cau_chung TEXT NULL COMMENT 'Chỉ bộ dụng cụ + hệ thống TBYT',
                yeu_cau_khac TEXT NULL COMMENT 'Chỉ bộ dụng cụ + hệ thống TBYT',
                yeu_cau_cau_hinh TEXT NULL COMMENT 'CHỈ hệ thống TBYT',
                nhom_nuoc VARCHAR(500) NULL COMMENT 'Yêu cầu nhóm nước, vùng lãnh thổ',
                dvt VARCHAR(50) NULL COMMENT 'ĐVT của bộ (Bộ, Hệ thống...)',
                so_luong DECIMAL(18,3) NOT NULL DEFAULT 0,
                thu_tu INT NOT NULL DEFAULT 0,
                ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
                ngay_cap_nhat DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                nguoi_tao INT NULL,
                nguoi_cap_nhat INT NULL,
                da_xoa INT NOT NULL DEFAULT 0,
                KEY idx_goi_thau (goi_thau_id, da_xoa),
                KEY idx_thu_tu (goi_thau_id, thu_tu)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
              COMMENT='Bộ/phần/hệ thống trong 1 gói thầu — Phụ lục III Thư mời'
        ");
        say('  + bảng bg_bo');
    }
    say('');

    // =================================================================
    // 3. CỘT MỚI CHO bg_hang_hoa
    // =================================================================
    say('→ 3. Cột mới cho bg_hang_hoa');
    themCot($pdo, 'bg_hang_hoa', 'bo_id',
        "INT NULL COMMENT 'Thuộc bộ nào (bg_bo.id)' AFTER goi_thau_id", $thu);
    themCot($pdo, 'bg_hang_hoa', 'stt_chi_tiet',
        "INT NULL COMMENT 'STT chi tiết trong bộ — cột (4)' AFTER bo_id", $thu);
    themCot($pdo, 'bg_hang_hoa', 'nhom_nuoc',
        "VARCHAR(500) NULL COMMENT 'Yêu cầu nhóm nước riêng cho hàng chi tiết' AFTER thong_so_ky_thuat", $thu);
    say('');

    // =================================================================
    // 4. CỘT MỚI CHO bg_bao_gia_chi_tiet (Mẫu 1 — 21 cột)
    // =================================================================
    say('→ 4. Cột đáp ứng cho bg_bao_gia_chi_tiet');
    // thong_so_chao_gia / diem_khong_dat đã có = cặp "yêu cầu kỹ thuật"
    themCot($pdo, 'bg_bao_gia_chi_tiet', 'dap_ung_chung',      "TEXT NULL COMMENT 'Cột (11) Mẫu 1'", $thu);
    themCot($pdo, 'bg_bao_gia_chi_tiet', 'khong_dat_chung',    "TEXT NULL COMMENT 'Cột (12)'", $thu);
    themCot($pdo, 'bg_bao_gia_chi_tiet', 'dap_ung_khac',       "TEXT NULL COMMENT 'Cột (13)'", $thu);
    themCot($pdo, 'bg_bao_gia_chi_tiet', 'khong_dat_khac',     "TEXT NULL COMMENT 'Cột (14)'", $thu);
    themCot($pdo, 'bg_bao_gia_chi_tiet', 'dap_ung_cau_hinh',   "TEXT NULL COMMENT 'Cột (15)'", $thu);
    themCot($pdo, 'bg_bao_gia_chi_tiet', 'khong_dat_cau_hinh', "TEXT NULL COMMENT 'Cột (16)'", $thu);
    themCot($pdo, 'bg_bao_gia_chi_tiet', 'dap_ung_nhom_nuoc',  "TEXT NULL COMMENT 'Cột (19)'", $thu);
    themCot($pdo, 'bg_bao_gia_chi_tiet', 'khong_dat_nhom_nuoc',"TEXT NULL COMMENT 'Cột (20)'", $thu);
    themCot($pdo, 'bg_bao_gia_chi_tiet', 'tai_lieu_chung_minh',"TEXT NULL COMMENT 'Cột (21) — cam kết, catalog, HDSD'", $thu);
    themCot($pdo, 'bg_bao_gia_chi_tiet', 'nam_san_xuat',       "VARCHAR(20) NULL COMMENT 'Mẫu 2 — năm sản xuất' AFTER hang_san_xuat", $thu);
    say('');

    // =================================================================
    // 5. CHUYỂN DỮ LIỆU CŨ: mỗi hàng hóa hiện có -> 1 bộ tương ứng
    // =================================================================
    say('→ 5. Chuyển hàng hóa cũ vào bộ');
    if ($thu) {
        $n = (int)$pdo->query(
            "SELECT COUNT(*) FROM bg_hang_hoa WHERE da_xoa = 0"
        )->fetchColumn();
        say("  → SẼ tạo bộ cho {$n} hàng hóa hiện có");
    } elseif (!coCot($pdo, 'bg_hang_hoa', 'bo_id')) {
        say('  ! chưa có cột bo_id — bỏ qua (chạy lại sau khi thêm cột)');
    } else {
        // Chỉ xử lý hàng hóa CHƯA gán bộ -> chạy lại không tạo trùng
        $rows = $pdo->query(
            "SELECT id, goi_thau_id, ma_hh, ten_hang_hoa, dvt, so_luong, thu_tu, nguoi_tao
             FROM bg_hang_hoa
             WHERE da_xoa = 0 AND (bo_id IS NULL OR bo_id = 0)
             ORDER BY goi_thau_id, thu_tu, id"
        )->fetchAll();

        if (empty($rows)) {
            say('  = mọi hàng hóa đã thuộc bộ, bỏ qua');
        } else {
            Database::beginTransaction();
            $stBo = $pdo->prepare(
                "INSERT INTO bg_bo
                    (goi_thau_id, ma_bo, stt_bo, ten_bo, dvt, so_luong, thu_tu, ngay_tao, nguoi_tao, da_xoa)
                 VALUES (:gt, :ma, :stt, :ten, :dvt, :sl, :tt, NOW(), :u, 0)"
            );
            $stHh = $pdo->prepare(
                "UPDATE bg_hang_hoa SET bo_id = :bo, stt_chi_tiet = 1 WHERE id = :id"
            );

            $sttTheoGoi = [];
            foreach ($rows as $r) {
                $gt = (int)$r['goi_thau_id'];
                $sttTheoGoi[$gt] = ($sttTheoGoi[$gt] ?? 0) + 1;

                // Hàng lẻ: bộ mang đúng tên/mã hàng, 1 chi tiết bên trong.
                // Giữ nguyên ĐVT + số lượng để tổng hợp cũ không lệch.
                $stBo->execute([
                    ':gt'  => $gt,
                    ':ma'  => $r['ma_hh'],
                    ':stt' => $sttTheoGoi[$gt],
                    ':ten' => $r['ten_hang_hoa'],
                    ':dvt' => $r['dvt'],
                    ':sl'  => $r['so_luong'],
                    ':tt'  => (int)$r['thu_tu'],
                    ':u'   => $r['nguoi_tao'],
                ]);
                $stHh->execute([':bo' => (int)$pdo->lastInsertId(), ':id' => (int)$r['id']]);
            }
            Database::commit();
            say('  + đã tạo ' . count($rows) . ' bộ cho hàng hóa cũ');
        }
    }
    say('');

    // =================================================================
    // 6. XÓA HẲN PHẦN CATALOG (Bước 5 cũ)
    // =================================================================
    say('→ 6. Xóa phần catalog (Bước 5 cũ)');

    // 6a. Xóa file catalog trên đĩa TRƯỚC khi mất dấu vết trong DB
    if (coBang($pdo, 'bg_file')) {
        $files = $pdo->query(
            "SELECT id, ten_file, duong_dan FROM bg_file
             WHERE nhom_file IN ('catalog', 'catalog_excel')"
        )->fetchAll();

        if (empty($files)) {
            say('  = không có file catalog nào');
        } elseif ($thu) {
            say('  → SẼ xóa ' . count($files) . ' file catalog (DB + đĩa)');
        } else {
            $xoaDia = 0;
            foreach ($files as $f) {
                $p = rtrim(AppConfig::UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR
                   . trim((string)$f['duong_dan'], '/\\') . DIRECTORY_SEPARATOR
                   . (string)$f['ten_file'];
                if (is_file($p) && @unlink($p)) $xoaDia++;
            }
            $n = $pdo->exec("DELETE FROM bg_file WHERE nhom_file IN ('catalog', 'catalog_excel')");
            say("  + xóa {$n} bản ghi bg_file, {$xoaDia} file trên đĩa");
        }
    }

    // 6b. Bỏ cột trỏ tới file catalog
    foreach (['file_catalog_id', 'file_catalog_excel_id'] as $cot) {
        if (!coCot($pdo, 'bg_bao_gia', $cot)) {
            say("  = bg_bao_gia.{$cot} không còn, bỏ qua");
        } elseif ($thu) {
            say("  → SẼ bỏ cột bg_bao_gia.{$cot}");
        } else {
            $pdo->exec("ALTER TABLE bg_bao_gia DROP COLUMN {$cot}");
            say("  - bỏ cột bg_bao_gia.{$cot}");
        }
    }

    // 6c. Bỏ bảng bg_catalog
    if (!coBang($pdo, 'bg_catalog')) {
        say('  = bảng bg_catalog không còn, bỏ qua');
    } elseif ($thu) {
        $n = (int)$pdo->query("SELECT COUNT(*) FROM bg_catalog")->fetchColumn();
        say("  → SẼ xóa bảng bg_catalog ({$n} dòng)");
    } else {
        $pdo->exec("DROP TABLE bg_catalog");
        say('  - xóa bảng bg_catalog');
    }

    // 6d. Xóa thư mục upload catalog nếu đã rỗng
    if (!$thu) {
        $dir = rtrim(AppConfig::UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . 'catalog';
        if (is_dir($dir)) {
            foreach ((array)glob($dir . '/*') as $f) {
                if (is_file($f) && basename($f) !== '.htaccess') @unlink($f);
            }
            say('  + dọn thư mục assets/uploads/catalog/');
        }
    }
    say('');

    // =================================================================
    // 7. Tổng kết
    // =================================================================
    say('→ 7. Hiện trạng');
    if (!$thu) {
        foreach ($pdo->query(
            "SELECT nhom, COUNT(*) n FROM bg_goi_thau WHERE da_xoa = 0 GROUP BY nhom"
        ) as $r) {
            say("    gói thầu nhóm {$r['nhom']}: {$r['n']}");
        }
        say('    bộ: '        . (int)$pdo->query("SELECT COUNT(*) FROM bg_bo WHERE da_xoa = 0")->fetchColumn());
        say('    hàng hóa: '  . (int)$pdo->query("SELECT COUNT(*) FROM bg_hang_hoa WHERE da_xoa = 0")->fetchColumn());
        say('    hàng chưa thuộc bộ: '
            . (int)$pdo->query("SELECT COUNT(*) FROM bg_hang_hoa WHERE da_xoa = 0 AND (bo_id IS NULL OR bo_id = 0)")->fetchColumn());
    }

    say('');
    say('===========================================================');
    say($thu ? '  THỬ XONG — chạy lại bỏ --thu để làm thật' : '  HOÀN TẤT');
    say('===========================================================');

} catch (Throwable $ex) {
    if (Database::getConnection()->inTransaction()) Database::rollBack();
    say('');
    say('!!! LỖI: ' . $ex->getMessage());
    say('    ' . $ex->getFile() . ':' . $ex->getLine());
    exit(1);
}
