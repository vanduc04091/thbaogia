<?php
/**
 * migrate_bang_file.php — Tách file bản ký ra BẢNG RIÊNG `bg_file`.
 *
 * CHẠY THỦ CÔNG:  php database/migrate_bang_file.php
 * (Theo §8B trong CLAUDE.md: không tự động đổi cấu trúc DB.)
 *
 * TRƯỚC:  bg_bao_gia giữ luôn 4 cột file_ban_ky, ten_file_goc,
 *         kich_thuoc_file, ngay_upload_ban_ky
 * SAU:    bảng `bg_file` giữ toàn bộ thông tin file;
 *         bg_bao_gia chỉ còn 1 cột `file_ban_ky_id` trỏ sang.
 *
 * Việc file này làm:
 *   1. Tạo bảng bg_file
 *   2. Thêm cột file_ban_ky_id vào bg_bao_gia
 *   3. CHUYỂN dữ liệu 4 cột cũ sang bg_file, gán lại id
 *   4. GIỮ NGUYÊN 4 cột cũ (không DROP) — xem ghi chú cuối file
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
    // ============ 1. BẢNG bg_file ============
    say('→ 1. Tạo bảng bg_file...');

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS bg_file (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ten_file VARCHAR(255) NOT NULL COMMENT 'Tên file trên đĩa: <mst>_<slug-goi-thau>.<ext>',
            ten_file_goc VARCHAR(255) NULL COMMENT 'Tên gốc nhà thầu đặt, chỉ để hiển thị',
            duong_dan VARCHAR(100) NOT NULL DEFAULT 'ban_ky' COMMENT 'Thư mục con trong assets/uploads',
            loai_file VARCHAR(20) NULL COMMENT 'Đuôi file: pdf, jpg, png',
            mime_type VARCHAR(100) NULL COMMENT 'MIME thật đọc bằng finfo',
            kich_thuoc INT DEFAULT 0 COMMENT 'Dung lượng (byte)',
            nhom_file VARCHAR(50) NOT NULL DEFAULT 'ban_ky' COMMENT 'Phân loại nghiệp vụ: ban_ky, ...',
            ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
            ngay_cap_nhat DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            nguoi_tao INT NULL,
            nguoi_cap_nhat INT NULL,
            da_xoa INT DEFAULT 0,
            KEY idx_nhom (nhom_file, da_xoa),
            KEY idx_ten_file (ten_file)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    say('  ✓ bg_file');

    // ============ 2. CỘT file_ban_ky_id ============
    say('');
    say('→ 2. Thêm cột file_ban_ky_id vào bg_bao_gia...');

    if (coCot($pdo, 'bg_bao_gia', 'file_ban_ky_id')) {
        say('  = cột file_ban_ky_id đã có');
    } else {
        $pdo->exec("ALTER TABLE bg_bao_gia
                    ADD COLUMN file_ban_ky_id INT NULL
                    COMMENT 'Trỏ tới bg_file.id — bản báo giá có dấu & chữ ký'
                    AFTER ly_do_tu_choi");
        say('  + cột file_ban_ky_id');
    }

    // Index để join nhanh
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bg_bao_gia'
           AND INDEX_NAME = 'idx_file_ban_ky'"
    );
    $stmt->execute();
    if ((int)$stmt->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE bg_bao_gia ADD INDEX idx_file_ban_ky (file_ban_ky_id)");
        say('  + index idx_file_ban_ky');
    } else {
        say('  = index idx_file_ban_ky đã có');
    }

    // ============ 3. CHUYỂN DỮ LIỆU ============
    say('');
    say('→ 3. Chuyển dữ liệu file sang bg_file...');

    // Chỉ chuyển khi cột cũ còn tồn tại (lần chạy đầu)
    if (!coCot($pdo, 'bg_bao_gia', 'file_ban_ky')) {
        say('  = cột cũ không còn — dữ liệu đã chuyển ở lần chạy trước');
    } else {
        $rows = $pdo->query(
            "SELECT id, file_ban_ky, ten_file_goc, kich_thuoc_file, ngay_upload_ban_ky, nguoi_tao
             FROM bg_bao_gia
             WHERE file_ban_ky IS NOT NULL AND file_ban_ky <> ''
               AND (file_ban_ky_id IS NULL OR file_ban_ky_id = 0)"
        )->fetchAll();

        if (empty($rows)) {
            say('  = không có dòng nào cần chuyển');
        } else {
            $dir = rtrim(AppConfig::UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . 'ban_ky';

            $insFile = $pdo->prepare(
                "INSERT INTO bg_file
                    (ten_file, ten_file_goc, duong_dan, loai_file, mime_type, kich_thuoc,
                     nhom_file, ngay_tao, ngay_cap_nhat, nguoi_tao, nguoi_cap_nhat, da_xoa)
                 VALUES (:tf, :tfg, 'ban_ky', :loai, :mime, :kt, 'ban_ky', :nt, NOW(), :u1, :u2, 0)"
            );
            $updBg = $pdo->prepare("UPDATE bg_bao_gia SET file_ban_ky_id = :fid WHERE id = :id");

            $mimeMap = [
                'pdf'  => 'application/pdf',
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png'  => 'image/png',
            ];

            Database::beginTransaction();
            $so = 0;
            foreach ($rows as $r) {
                $ten  = basename((string)$r['file_ban_ky']);
                $ext  = strtolower(pathinfo($ten, PATHINFO_EXTENSION));
                $path = $dir . DIRECTORY_SEPARATOR . $ten;

                // Dung lượng: ưu tiên cột cũ, thiếu thì đọc lại từ đĩa
                $kt = (int)($r['kich_thuoc_file'] ?? 0);
                if ($kt <= 0 && is_file($path)) $kt = (int)filesize($path);

                $insFile->execute([
                    ':tf'   => $ten,
                    ':tfg'  => $r['ten_file_goc'],
                    ':loai' => $ext,
                    ':mime' => $mimeMap[$ext] ?? null,
                    ':kt'   => $kt,
                    // Giữ đúng thời điểm nhà thầu upload, không lấy NOW()
                    ':nt'   => $r['ngay_upload_ban_ky'] ?: date('Y-m-d H:i:s'),
                    ':u1'   => $r['nguoi_tao'],
                    ':u2'   => $r['nguoi_tao'],
                ]);
                $fid = (int)$pdo->lastInsertId();
                $updBg->execute([':fid' => $fid, ':id' => (int)$r['id']]);
                $so++;
                say("  + báo giá #{$r['id']} → bg_file #{$fid} ({$ten})");
            }
            Database::commit();
            say("  ✓ Đã chuyển {$so} file.");
        }
    }

    say('');
    say('HOÀN TẤT.');
    say('');
    say('GHI CHÚ: 4 cột cũ (file_ban_ky, ten_file_goc, kich_thuoc_file,');
    say('ngay_upload_ban_ky) vẫn được GIỮ NGUYÊN để có thể đối chiếu/hoàn tác.');
    say('Code mới không còn đọc chúng nữa. Khi đã chạy ổn định và muốn dọn,');
    say('chạy tiếp:  php database/migrate_go_cot_file_cu.php');
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    say('LỖI: ' . $ex->getMessage());
    exit(1);
}
