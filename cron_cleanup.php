<?php
// Cron dọn dẹp - chạy hàng tuần
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/BUS/DM_NhatKyHeThong_BUS.php';
require_once __DIR__ . '/BUS/BG_HangHoa_BUS.php';

try {
    // Xóa nhật ký cũ hơn 90 ngày
    $days = 90;
    $result = DM_NhatKyHeThong_BUS::purgeOlderThan($days, 1); // user id 1 = system
    echo "Đã xóa " . ($result['data'] ?? 0) . " bản ghi nhật ký cũ hơn {$days} ngày.\n";
} catch (Throwable $ex) {
    echo "Lỗi dọn nhật ký: " . $ex->getMessage() . "\n";
}

try {
    // Dọn bộ đếm đăng nhập sai đã hết hạn khóa
    require_once __DIR__ . '/BUS/DM_NguoiDung_BUS.php';
    require_once __DIR__ . '/DAL/DM_DangNhapThatBai_DAL.php';
    $soXoa = DM_DangNhapThatBai_DAL::donCu(DM_NguoiDung_BUS::LOGIN_LOCKOUT_SECONDS);
    echo "Đã xóa {$soXoa} bản ghi đếm đăng nhập sai đã hết hạn.
";
} catch (Throwable $ex) {
    echo "Lỗi dọn bộ đếm đăng nhập: " . $ex->getMessage() . "
";
}

try {
    // Dọn file Excel sinh tạm (file mẫu, bản tổng hợp, file nhà thầu upload).
    // download.php đã tự xóa file sau khi gửi, nhưng request bị hủy giữa đường
    // hoặc lỗi khi xuất sẽ để lại file mồ côi.
    $dir = BG_HangHoa_BUS::tempDir();
    $gioHetHan = 24;
    $moc = time() - $gioHetHan * 3600;
    $daXoa = 0;

    foreach ((array)glob($dir . '/*.xlsx') as $f) {
        if (is_file($f) && filemtime($f) < $moc && @unlink($f)) {
            $daXoa++;
        }
    }
    echo "Đã xóa {$daXoa} file Excel tạm cũ hơn {$gioHetHan} giờ.\n";
} catch (Throwable $ex) {
    echo "Lỗi dọn file tạm: " . $ex->getMessage() . "\n";
}