<?php
/**
 * huong_dan_noi_dung.php — Nội dung "Hướng dẫn thực hiện chào giá qua hệ thống".
 *
 * Dùng CHUNG cho 2 nơi (viết 1 lần, sửa 1 chỗ):
 *   1. Popup tự hiện khi nhà thầu quét QR vào lần đầu   (portal/index.php)
 *   2. Trang hướng dẫn xem lại bất cứ lúc nào           (portal/huong_dan.php)
 *
 * Nội dung bám theo docs/HƯỚNG DẪN THỰC HIỆN CHÀO GIÁ.docx do bên mời cung cấp.
 * Sửa nội dung thì sửa ở ĐÂY, không sửa rải rác 2 nơi.
 *
 * Bản mới đã BỎ mục "lập file Excel chỉ dẫn vị trí catalog" — Bước 5 cũ của
 * cổng chào giá cũng gỡ theo (xem database/migrate_nhom_bo.php).
 */
?>
<div class="hd-noi-dung">
    <p class="hd-mo-dau">Đề nghị các đơn vị chào giá thực hiện đầy đủ các nội dung sau:</p>

    <ol class="hd-danh-sach">
        <li>
            <strong>Kê khai thông tin chào giá</strong> theo Thư mời và các biểu mẫu trên hệ thống.
            <span class="hd-phu">Gồm: thông tin công ty → bảng đáp ứng (Mẫu 1) → bảng chào giá (Mẫu 2).</span>
        </li>
        <li>
            <strong>In, ký, đóng dấu và scan</strong> báo giá đã kê khai, sau đó tải lên hệ thống.
        </li>
        <li>
            <strong>Scan toàn bộ tài liệu chứng minh</strong>: Yêu cầu kỹ thuật của hàng hóa
            chào giá thành <strong>01 bản PDF</strong>.
        </li>
        <li>
            <strong>Tải các file đầy đủ lên hệ thống</strong> và <strong>hoàn tất nộp báo giá</strong>.
        </li>
        <li>
            <strong>Tài liệu cứng</strong> (báo giá hoàn chỉnh, tài liệu chứng minh: cam kết,
            catalog, hướng dẫn sử dụng…) gửi về theo địa chỉ thông tin người nhận trong
            <strong>Thư mời báo giá</strong>.
        </li>
        <li>
            Thông tin chào giá của các nhà thầu <strong>sẽ được bảo mật</strong>.
        </li>
    </ol>

    <div class="hd-luu-y hd-lien-he">
        <?= IconHelper::svg('info', 17) ?>
        <span>
            Mọi thắc mắc hoặc khó khăn trong quá trình thực hiện liên hệ
            <strong>Đ/C Lương: <a href="tel:0934420788">0934 420 788</a></strong>
            để được hướng dẫn.
        </span>
    </div>
</div>
