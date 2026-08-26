<?php
/**
 * migrate_quyen_goi_thau.php — Phân quyền XEM theo từng gói thầu.
 *
 * Tạo bảng `bg_quyen_goi_thau`: mỗi dòng = 1 người dùng được xem 1 gói thầu.
 *
 * QUY TẮC XEM (xem PhanQuyenHelper / BG_GoiThau_BUS::locTheoQuyen):
 *   1. Nhóm có `la_admin = 1`      -> xem TẤT CẢ gói thầu.
 *   2. Nhóm MANAGER (Quản lý)      -> xem TẤT CẢ gói thầu.
 *   3. Người dùng khác             -> chỉ xem gói được gán trong bảng này.
 *      Gói CHƯA gán ai thì nhóm 1 + 2 vẫn thấy, người khác KHÔNG thấy.
 *
 * Quy tắc này do người dùng chọn: "Chỉ quản trị viên và quản lý thấy"
 * đối với gói chưa phân quyền riêng.
 *
 * Idempotent: chạy nhiều lần vẫn an toàn.
 *
 * Cách chạy:  php database/migrate_quyen_goi_thau.php
 */

require_once __DIR__ . '/../bootstrap.php';

function say(string $s = ''): void { echo $s . PHP_EOL; }

say('===========================================================');
say('  PHÂN QUYỀN XEM THEO GÓI THẦU');
say('  DB: ' . AppConfig::DB_NAME);
say('===========================================================');
say('');

try {
    $pdo = Database::getConnection();

    // ============ 1. Bảng bg_quyen_goi_thau ============
    say('→ 1. Bảng bg_quyen_goi_thau...');
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS bg_quyen_goi_thau (
            id INT AUTO_INCREMENT PRIMARY KEY,
            goi_thau_id INT NOT NULL,
            nguoi_dung_id INT NOT NULL,
            ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
            ngay_cap_nhat DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            nguoi_tao INT NULL,
            nguoi_cap_nhat INT NULL,
            da_xoa INT DEFAULT 0,
            UNIQUE KEY uk_goi_nguoi (goi_thau_id, nguoi_dung_id, da_xoa),
            KEY idx_nguoi_dung (nguoi_dung_id),
            KEY idx_goi_thau (goi_thau_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
          COMMENT='Ai được xem gói thầu nào — gói chưa gán thì chỉ admin + quản lý thấy'
    ");
    say('  + bảng bg_quyen_goi_thau (hoặc đã có)');

    // ============ 2. Khai báo form để cấp quyền thao tác ============
    say('');
    say('→ 2. Khai báo module trong dm_danh_sach_form...');

    $st = $pdo->prepare("SELECT id FROM dm_danh_sach_form
                         WHERE modules_tuong_ung = :m AND da_xoa = 0");
    $st->execute([':m' => 'BG_QuyenGoiThau']);
    $formId = (int)$st->fetchColumn();

    if ($formId > 0) {
        say('  = module BG_QuyenGoiThau đã khai báo (id=' . $formId . ')');
    } else {
        $ins = $pdo->prepare(
            "INSERT INTO dm_danh_sach_form (ten_form, modules_tuong_ung, ngay_tao, da_xoa)
             VALUES (:ten, :m, NOW(), 0)"
        );
        $ins->execute([
            ':ten' => 'Phân quyền gói thầu',
            ':m'   => 'BG_QuyenGoiThau',
        ]);
        $formId = (int)$pdo->lastInsertId();
        say('  + khai báo module BG_QuyenGoiThau (id=' . $formId . ')');
    }

    // ============ 3. Cấp full quyền cho nhóm admin ============
    say('');
    say('→ 3. Cấp quyền cho nhóm quản trị...');
    $nhomAdmin = $pdo->query("SELECT id, ten_nhom FROM dm_nhom_tai_khoan
                              WHERE la_admin = 1 AND da_xoa = 0")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($nhomAdmin as $n) {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM dm_phan_quyen
                              WHERE nhom_tai_khoan_id = :nh AND form_id = :f");
        $chk->execute([':nh' => $n['id'], ':f' => $formId]);

        if ((int)$chk->fetchColumn() > 0) {
            say('  = ' . $n['ten_nhom'] . ' đã có quyền');
        } else {
            $pdo->prepare(
                "INSERT INTO dm_phan_quyen
                    (nhom_tai_khoan_id, form_id, quyen_xem, quyen_them, quyen_sua, quyen_xoa, ngay_tao)
                 VALUES (:nh, :f, 1, 1, 1, 1, NOW())"
            )->execute([':nh' => $n['id'], ':f' => $formId]);
            say('  + cấp quyền đầy đủ cho ' . $n['ten_nhom']);
        }
    }

    // ============ 4. Báo cáo hiện trạng ============
    say('');
    say('→ 4. Hiện trạng:');
    $soGoi = (int)$pdo->query("SELECT COUNT(*) FROM bg_goi_thau WHERE da_xoa = 0")->fetchColumn();
    $soGan = (int)$pdo->query("SELECT COUNT(DISTINCT goi_thau_id) FROM bg_quyen_goi_thau
                               WHERE da_xoa = 0")->fetchColumn();
    say('  Tổng gói thầu           : ' . $soGoi);
    say('  Gói đã phân quyền riêng : ' . $soGan);
    say('  Gói chưa phân quyền     : ' . ($soGoi - $soGan) . ' (chỉ admin + quản lý thấy)');

    say('');
    say('===========================================================');
    say('  XONG.');
    say('  Vào "Phân quyền gói thầu" để chọn ai được xem gói nào.');
    say('===========================================================');
} catch (Throwable $ex) {
    say('');
    say('LỖI: ' . $ex->getMessage());
    exit(1);
}
