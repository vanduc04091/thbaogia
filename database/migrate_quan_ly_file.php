<?php
/**
 * migrate_quan_ly_file.php — Khai báo module "Quản lý file bản ký".
 *
 * CHẠY THỦ CÔNG:  php database/migrate_quan_ly_file.php
 * (Theo quy tắc §8B trong CLAUDE.md: không tự động đổi cấu trúc DB.)
 *
 * Việc file này làm:
 *   1. (Cũ) Thêm cột `kich_thuoc_file` vào bg_bao_gia — CHỈ chạy nếu DB còn
 *      cột file_ban_ky cũ. Từ bản tách bảng, dung lượng nằm ở bg_file.kich_thuoc
 *      nên DB mới KHÔNG cần cột này. Xem migrate_bang_file.php.
 *   2. Khai báo form BG_QuanLyFile + cấp quyền cho các nhóm sẵn có
 *
 * Idempotent: chạy nhiều lần vẫn an toàn, phần nào đã có thì bỏ qua.
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
    say('→ 1. Cột lưu dung lượng file bản ký (chỉ với DB đời cũ)...');

    // DB đã tách bảng bg_file thì bỏ qua hẳn bước này
    if (!coCot($pdo, 'bg_bao_gia', 'file_ban_ky')) {
        say('  = DB đã dùng bảng bg_file — bỏ qua (dung lượng nằm ở bg_file.kich_thuoc)');
    } elseif (coCot($pdo, 'bg_bao_gia', 'kich_thuoc_file')) {
        say('  = cột kich_thuoc_file đã có');
    } else {
        $pdo->exec("ALTER TABLE bg_bao_gia
                    ADD COLUMN kich_thuoc_file INT NULL
                    COMMENT 'Dung lượng file bản ký (byte)'
                    AFTER ten_file_goc");
        say('  + cột kich_thuoc_file');
    }

    // Điền dung lượng cho file cũ — CHỈ khi DB còn cột đời cũ.
    // DB đã tách bg_file thì các cột này không còn, truy vấn sẽ lỗi.
    if (coCot($pdo, 'bg_bao_gia', 'file_ban_ky') && coCot($pdo, 'bg_bao_gia', 'kich_thuoc_file')) {
        $dir = rtrim(AppConfig::UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . 'ban_ky';
        $rows = $pdo->query(
            "SELECT id, file_ban_ky FROM bg_bao_gia
             WHERE file_ban_ky IS NOT NULL AND file_ban_ky <> '' AND kich_thuoc_file IS NULL"
        )->fetchAll();

        $soCapNhat = 0;
        $upd = $pdo->prepare("UPDATE bg_bao_gia SET kich_thuoc_file = :kt WHERE id = :id");
        foreach ($rows as $r) {
            $p = $dir . DIRECTORY_SEPARATOR . basename((string)$r['file_ban_ky']);
            if (is_file($p)) {
                $upd->execute([':kt' => filesize($p), ':id' => (int)$r['id']]);
                $soCapNhat++;
            }
        }
        if ($soCapNhat > 0) say("  ✓ Điền dung lượng cho {$soCapNhat} file đã có.");
    }

    say('');
    say('→ 2. Khai báo form + phân quyền...');

    $moduleKey = 'BG_QuanLyFile';
    $stmt = $pdo->prepare("SELECT id FROM dm_danh_sach_form WHERE modules_tuong_ung = :m AND da_xoa = 0");
    $stmt->execute([':m' => $moduleKey]);
    $formId = (int)$stmt->fetchColumn();

    if ($formId === 0) {
        $ins = $pdo->prepare(
            "INSERT INTO dm_danh_sach_form (modules_tuong_ung, ten_form, form_cha_id, ngay_tao, ngay_cap_nhat, nguoi_tao, nguoi_cap_nhat, da_xoa)
             VALUES (:m, :t, 0, NOW(), NOW(), 1, 1, 0)"
        );
        $ins->execute([':m' => $moduleKey, ':t' => 'Quản lý file bản ký']);
        $formId = (int)$pdo->lastInsertId();
        say("  + form {$moduleKey}");
    } else {
        say("  = form {$moduleKey} đã có");
    }

    // Nhóm admin: full quyền
    $adminNhom = $pdo->query("SELECT id FROM dm_nhom_tai_khoan WHERE la_admin = 1 AND da_xoa = 0")->fetchAll();
    $upsert = $pdo->prepare(
        "INSERT INTO dm_phan_quyen (nhom_tai_khoan_id, form_id, quyen_xem, quyen_them, quyen_sua, quyen_xoa,
                                    ngay_tao, ngay_cap_nhat, nguoi_tao, nguoi_cap_nhat)
         VALUES (:nhom, :form, 1, 1, 1, 1, NOW(), NOW(), 1, 1)
         ON DUPLICATE KEY UPDATE quyen_xem=1, quyen_them=1, quyen_sua=1, quyen_xoa=1, ngay_cap_nhat=NOW()"
    );
    foreach ($adminNhom as $n) {
        $upsert->execute([':nhom' => (int)$n['id'], ':form' => $formId]);
    }
    say('  ✓ Cấp full quyền cho ' . count($adminNhom) . ' nhóm admin.');

    // Quyền mặc định cho nhóm nghiệp vụ — chỉ thêm nếu CHƯA có bản ghi,
    // không ghi đè thiết lập admin đã chỉnh tay ở GUI/DM_PhanQuyen.
    // [ma_nhom => [xem, them, sua, xoa]]
    // Lưu ý: quyen_xoa ở module này = xóa FILE bản ký, cần thận trọng.
    $quyenMacDinh = [
        'MANAGER' => [1, 0, 1, 0],
        'STAFF'   => [1, 0, 0, 0],
        'VIEWER'  => [1, 0, 0, 0],
    ];

    $insQuyen = $pdo->prepare(
        "INSERT INTO dm_phan_quyen (nhom_tai_khoan_id, form_id, quyen_xem, quyen_them, quyen_sua, quyen_xoa,
                                    ngay_tao, ngay_cap_nhat, nguoi_tao, nguoi_cap_nhat)
         VALUES (:nhom, :form, :xem, :them, :sua, :xoa, NOW(), NOW(), 1, 1)"
    );
    $check = $pdo->prepare("SELECT COUNT(*) FROM dm_phan_quyen WHERE nhom_tai_khoan_id = :nhom AND form_id = :form");

    $soCap = 0;
    foreach ($quyenMacDinh as $maNhom => [$xem, $them, $sua, $xoa]) {
        $st = $pdo->prepare("SELECT id FROM dm_nhom_tai_khoan WHERE ma_nhom = :ma AND da_xoa = 0");
        $st->execute([':ma' => $maNhom]);
        $nhomId = (int)$st->fetchColumn();
        if ($nhomId === 0) continue;

        $check->execute([':nhom' => $nhomId, ':form' => $formId]);
        if ((int)$check->fetchColumn() > 0) continue;

        $insQuyen->execute([
            ':nhom' => $nhomId, ':form' => $formId,
            ':xem' => $xem, ':them' => $them, ':sua' => $sua, ':xoa' => $xoa,
        ]);
        $soCap++;
    }
    if ($soCap > 0) say("  ✓ Cấp quyền mặc định cho {$soCap} nhóm nghiệp vụ.");

    // Nhóm nhà thầu: KHÔNG có quyền gì ở module quản trị này
    $st = $pdo->prepare("SELECT id FROM dm_nhom_tai_khoan WHERE ma_nhom = 'NHATHAU' AND da_xoa = 0");
    $st->execute();
    $nhomNT = (int)$st->fetchColumn();
    if ($nhomNT > 0) {
        $check->execute([':nhom' => $nhomNT, ':form' => $formId]);
        if ((int)$check->fetchColumn() === 0) {
            $insQuyen->execute([
                ':nhom' => $nhomNT, ':form' => $formId,
                ':xem' => 0, ':them' => 0, ':sua' => 0, ':xoa' => 0,
            ]);
            say('  ✓ Nhóm NHATHAU: không cấp quyền (đúng thiết kế).');
        }
    }

    MemcachedHelper::deleteByPrefix('phan_quyen:');

    say('');
    say('HOÀN TẤT. Vào GUI/DM_PhanQuyen để chỉnh quyền chi tiết nếu cần.');
} catch (Throwable $ex) {
    say('LỖI: ' . $ex->getMessage());
    exit(1);
}
