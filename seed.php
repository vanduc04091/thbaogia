<?php
/**
 * seed.php — Nạp dữ liệu test cho môi trường dev.
 *
 * Chạy:  php seed.php            (giữ dữ liệu cũ, chỉ thêm cái còn thiếu)
 *        php seed.php --reset    (xóa sạch dữ liệu rồi seed lại từ đầu)
 *
 * An toàn để chạy nhiều lần (idempotent): bản ghi đã có sẽ được bỏ qua.
 * CHỈ dùng khi AppConfig::APP_DEBUG = true.
 */
require_once __DIR__ . '/bootstrap.php';

if (!AppConfig::APP_DEBUG) {
    fwrite(STDERR, "Từ chối: seed chỉ chạy khi APP_DEBUG = true.\n");
    exit(1);
}

$isCli   = PHP_SAPI === 'cli';
$doReset = $isCli && in_array('--reset', $argv ?? [], true);
if (!$isCli) header('Content-Type: text/plain; charset=utf-8');

function say(string $msg): void { echo $msg . "\n"; }

$pdo = Database::getConnection();

try {
    // ============ RESET (tùy chọn) ============
    if ($doReset) {
        say('→ Đang xóa dữ liệu cũ...');
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['dm_phan_quyen', 'dm_nhat_ky_he_thong', 'dm_nguoi_dung', 'dm_nhom_tai_khoan', 'dm_danh_sach_form'] as $t) {
            $pdo->exec("TRUNCATE TABLE `{$t}`");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        say('  Đã xóa sạch 5 bảng.');
    }

    Database::beginTransaction();

    // ============ 1. NHÓM TÀI KHOẢN ============
    // [ma_nhom, ten_nhom, mo_ta, la_admin, trang_thai]
    $nhomData = [
        ['ADMIN',   'Quản trị viên',   'Toàn quyền trên hệ thống',                        1, 1],
        ['MANAGER', 'Quản lý',         'Xem, thêm, sửa dữ liệu; không được xóa',          0, 1],
        ['STAFF',   'Nhân viên',       'Chủ yếu xem và nhập liệu cơ bản',                 0, 1],
        ['VIEWER',  'Chỉ xem',         'Chỉ được xem, không thay đổi dữ liệu',            0, 1],
        ['TEMP',    'Tài khoản tạm',   'Nhóm đã ngừng hoạt động - dùng để test trạng thái',0, 0],
    ];

    $nhomIds = [];
    foreach ($nhomData as [$ma, $ten, $moTa, $laAdmin, $tt]) {
        $stmt = $pdo->prepare("SELECT id FROM dm_nhom_tai_khoan WHERE ma_nhom = :ma AND da_xoa = 0");
        $stmt->execute([':ma' => $ma]);
        $id = (int)$stmt->fetchColumn();

        if ($id === 0) {
            $ins = $pdo->prepare(
                "INSERT INTO dm_nhom_tai_khoan (ma_nhom, ten_nhom, mo_ta, trang_thai, la_admin, ngay_tao, ngay_cap_nhat, nguoi_tao, nguoi_cap_nhat, da_xoa)
                 VALUES (:ma, :ten, :mt, :tt, :la, NOW(), NOW(), 1, 1, 0)"
            );
            $ins->execute([':ma' => $ma, ':ten' => $ten, ':mt' => $moTa, ':tt' => $tt, ':la' => $laAdmin]);
            $id = (int)$pdo->lastInsertId();
            say("  + Nhóm {$ma} (id={$id})");
        }
        $nhomIds[$ma] = $id;
    }
    say('✓ Nhóm tài khoản: ' . count($nhomIds));

    // ============ 2. DANH SÁCH FORM ============
    // [modules_tuong_ung, ten_form]
    $formData = [
        ['DM_NguoiDung',     'Quản lý người dùng'],
        ['DM_NhomTaiKhoan',  'Quản lý nhóm tài khoản'],
        ['DM_DanhSachForm',  'Danh sách form'],
        ['DM_PhanQuyen',     'Phân quyền'],
        ['DM_NhatKyHeThong', 'Nhật ký hệ thống'],
    ];

    $formIds = [];
    foreach ($formData as [$mod, $ten]) {
        $stmt = $pdo->prepare("SELECT id FROM dm_danh_sach_form WHERE modules_tuong_ung = :m AND da_xoa = 0");
        $stmt->execute([':m' => $mod]);
        $id = (int)$stmt->fetchColumn();

        if ($id === 0) {
            $ins = $pdo->prepare(
                "INSERT INTO dm_danh_sach_form (modules_tuong_ung, ten_form, form_cha_id, ngay_tao, ngay_cap_nhat, nguoi_tao, nguoi_cap_nhat, da_xoa)
                 VALUES (:m, :t, 0, NOW(), NOW(), 1, 1, 0)"
            );
            $ins->execute([':m' => $mod, ':t' => $ten]);
            $id = (int)$pdo->lastInsertId();
            say("  + Form {$mod} (id={$id})");
        }
        $formIds[$mod] = $id;
    }
    say('✓ Danh sách form: ' . count($formIds));

    // ============ 3. NGƯỜI DÙNG ============
    // [tai_khoan, mat_khau, ma_nhom, trang_thai]
    $userData = [
        ['admin',    'Admin@123',    'ADMIN',   1],
        ['manager',  'Manager@123',  'MANAGER', 1],
        ['staff01',  'Staff@123',    'STAFF',   1],
        ['staff02',  'Staff@123',    'STAFF',   1],
        ['viewer',   'Viewer@123',   'VIEWER',  1],
        ['locked',   'Locked@123',   'STAFF',   0], // tài khoản bị khóa - test đăng nhập thất bại
    ];

    $soUser = 0;
    foreach ($userData as [$tk, $mk, $maNhom, $tt]) {
        $stmt = $pdo->prepare("SELECT id FROM dm_nguoi_dung WHERE tai_khoan = :tk AND da_xoa = 0");
        $stmt->execute([':tk' => $tk]);
        if ((int)$stmt->fetchColumn() > 0) continue;

        $hash = password_hash($mk, AppConfig::PASSWORD_ALGO, ['cost' => AppConfig::PASSWORD_COST]);
        $ins = $pdo->prepare(
            "INSERT INTO dm_nguoi_dung (tai_khoan, mat_khau, nhom_tai_khoan_id, trang_thai, ngay_tao, ngay_cap_nhat, nguoi_tao, nguoi_cap_nhat, da_xoa)
             VALUES (:tk, :mk, :nh, :tt, NOW(), NOW(), 1, 1, 0)"
        );
        $ins->execute([':tk' => $tk, ':mk' => $hash, ':nh' => $nhomIds[$maNhom], ':tt' => $tt]);
        $soUser++;
        say("  + User {$tk} / {$mk}");
    }
    say('✓ Người dùng mới: ' . $soUser);

    // ============ 4. PHÂN QUYỀN ============
    // Ma trận quyền theo nhóm: [xem, them, sua, xoa]
    $quyenTheoNhom = [
        // MANAGER: xem/thêm/sửa mọi form, không được xóa
        'MANAGER' => [
            'DM_NguoiDung'     => [1, 1, 1, 0],
            'DM_NhomTaiKhoan'  => [1, 1, 1, 0],
            'DM_DanhSachForm'  => [1, 0, 0, 0],
            'DM_PhanQuyen'     => [1, 0, 1, 0],
            'DM_NhatKyHeThong' => [1, 0, 0, 0],
        ],
        // STAFF: chỉ làm việc với người dùng, xem nhật ký
        'STAFF' => [
            'DM_NguoiDung'     => [1, 1, 1, 0],
            'DM_NhomTaiKhoan'  => [1, 0, 0, 0],
            'DM_NhatKyHeThong' => [1, 0, 0, 0],
        ],
        // VIEWER: chỉ xem
        'VIEWER' => [
            'DM_NguoiDung'     => [1, 0, 0, 0],
            'DM_NhomTaiKhoan'  => [1, 0, 0, 0],
            'DM_DanhSachForm'  => [1, 0, 0, 0],
            'DM_NhatKyHeThong' => [1, 0, 0, 0],
        ],
    ];

    $soQuyen = 0;
    $upsert = $pdo->prepare(
        "INSERT INTO dm_phan_quyen (nhom_tai_khoan_id, form_id, quyen_xem, quyen_them, quyen_sua, quyen_xoa,
                                    ngay_tao, ngay_cap_nhat, nguoi_tao, nguoi_cap_nhat)
         VALUES (:nhom, :form, :x1, :t1, :s1, :xo1, NOW(), NOW(), 1, 1)
         ON DUPLICATE KEY UPDATE quyen_xem=:x2, quyen_them=:t2, quyen_sua=:s2, quyen_xoa=:xo2, ngay_cap_nhat=NOW()"
    );

    foreach ($quyenTheoNhom as $maNhom => $dsForm) {
        foreach ($dsForm as $modKey => [$x, $t, $s, $xo]) {
            if (!isset($formIds[$modKey])) continue;
            // KHÔNG reuse named placeholder (EMULATE_PREPARES = false)
            $upsert->execute([
                ':nhom' => $nhomIds[$maNhom], ':form' => $formIds[$modKey],
                ':x1' => $x, ':t1' => $t, ':s1' => $s, ':xo1' => $xo,
                ':x2' => $x, ':t2' => $t, ':s2' => $s, ':xo2' => $xo,
            ]);
            $soQuyen++;
        }
    }

    // Nhóm ADMIN có cờ la_admin = 1 nên bỏ qua ma trận, nhưng vẫn ghi đủ quyền cho nhất quán
    foreach ($formIds as $fid) {
        $upsert->execute([
            ':nhom' => $nhomIds['ADMIN'], ':form' => $fid,
            ':x1' => 1, ':t1' => 1, ':s1' => 1, ':xo1' => 1,
            ':x2' => 1, ':t2' => 1, ':s2' => 1, ':xo2' => 1,
        ]);
        $soQuyen++;
    }
    say('✓ Bản ghi phân quyền: ' . $soQuyen);

    // ============ 5. NHẬT KÝ MẪU ============
    $soLog = (int)$pdo->query("SELECT COUNT(*) FROM dm_nhat_ky_he_thong")->fetchColumn();
    if ($soLog === 0) {
        $logs = [
            ['admin',   'HeThong', 'Đăng nhập',            'Đăng nhập thành công',                 '127.0.0.1'],
            ['admin',   'HeThong', 'Thêm nhóm tài khoản',  'bang=dm_nhom_tai_khoan; Thêm nhóm STAFF','127.0.0.1'],
            ['manager', 'HeThong', 'Đăng nhập',            'Đăng nhập thành công',                 '192.168.1.20'],
            ['manager', 'HeThong', 'Sửa người dùng',       'bang=dm_nguoi_dung; id=3',             '192.168.1.20'],
            ['staff01', 'HeThong', 'Đăng nhập',            'Đăng nhập thành công',                 '192.168.1.35'],
            ['staff01', 'HeThong', 'Đăng nhập thất bại',   'Sai mật khẩu',                         '192.168.1.35'],
            ['viewer',  'HeThong', 'Đăng nhập',            'Đăng nhập thành công',                 '10.0.0.8'],
        ];

        $insLog = $pdo->prepare(
            "INSERT INTO dm_nhat_ky_he_thong (nguoi_dung_id, tai_khoan, module, hanh_dong, noi_dung, ip_address, user_agent, thoi_gian, ngay_tao)
             VALUES ((SELECT id FROM dm_nguoi_dung WHERE tai_khoan = :tk1 AND da_xoa = 0 LIMIT 1),
                     :tk2, :m, :hd, :nd, :ip, 'Mozilla/5.0 (Seed Data)',
                     DATE_SUB(NOW(), INTERVAL :phut MINUTE), NOW())"
        );

        $phut = 5;
        foreach ($logs as [$tk, $mod, $hd, $nd, $ip]) {
            $insLog->execute([
                ':tk1' => $tk, ':tk2' => $tk, ':m' => $mod,
                ':hd' => $hd, ':nd' => $nd, ':ip' => $ip, ':phut' => $phut,
            ]);
            $phut += 37;
        }
        say('✓ Nhật ký mẫu: ' . count($logs));
    } else {
        say("✓ Nhật ký: giữ nguyên {$soLog} bản ghi sẵn có");
    }

    Database::commit();

    // Cache phân quyền cũ có thể sai sau khi seed
    PhanQuyenHelper::clearCache();

    say('');
    say('=========================================');
    say(' SEED THÀNH CÔNG');
    say('=========================================');
    say(' Tài khoản đăng nhập (mật khẩu / vai trò):');
    say('   admin    / Admin@123    → toàn quyền');
    say('   manager  / Manager@123  → xem, thêm, sửa (không xóa)');
    say('   staff01  / Staff@123    → người dùng + nhật ký');
    say('   staff02  / Staff@123    → người dùng + nhật ký');
    say('   viewer   / Viewer@123   → chỉ xem');
    say('   locked   / Locked@123   → BỊ KHÓA (test đăng nhập lỗi)');
    say('');
    say(' ĐỔI MẬT KHẨU TRƯỚC KHI ĐƯA LÊN PRODUCTION.');
    say('=========================================');

} catch (Throwable $ex) {
    Database::rollBack();
    fwrite(STDERR, 'Lỗi seed: ' . $ex->getMessage() . "\n");
    fwrite(STDERR, $ex->getTraceAsString() . "\n");
    exit(1);
}
