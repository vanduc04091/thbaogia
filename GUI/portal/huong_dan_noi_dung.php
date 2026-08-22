<?php
/**
 * huong_dan_noi_dung.php — Nội dung "Hướng dẫn thực hiện chào giá qua hệ thống".
 *
 * Dùng CHUNG cho 2 nơi (viết 1 lần, sửa 1 chỗ):
 *   1. Popup tự hiện khi nhà thầu quét QR vào lần đầu   (portal/index.php)
 *   2. Trang hướng dẫn xem lại bất cứ lúc nào           (portal/huong_dan.php)
 *
 * Nội dung bám theo ảnh docs/anh thong bao.jpg do bên mời cung cấp.
 */
?>
<div class="hd-noi-dung">
    <p class="hd-mo-dau">Đề nghị Nhà cung cấp thực hiện đầy đủ các nội dung sau:</p>

    <ol class="hd-danh-sach">
        <li>
            <strong>Kê khai thông tin chào giá</strong> theo Thư mời và các biểu mẫu trên hệ thống.
            <span class="hd-phu">Gồm 3 bước: thông tin công ty → bảng đáp ứng kỹ thuật → bảng chào giá.</span>
        </li>
        <li>
            <strong>In, ký, đóng dấu và scan</strong> Báo giá đã kê khai, sau đó tải lên hệ thống.
        </li>
        <li>
            <strong>Scan toàn bộ tài liệu chứng minh</strong> yêu cầu kỹ thuật thành <strong>01 file PDF</strong>.
        </li>
        <li>
            Lập <strong>01 file Excel chỉ dẫn vị trí tài liệu</strong>, gồm:
            <strong>Mã HH chào giá – số trang tài liệu chứng minh</strong>.

            <table class="hd-bang">
                <thead>
                    <tr>
                        <th style="width:60px">STT</th>
                        <th style="width:90px">Mã HH</th>
                        <th>Tên hàng thương mại</th>
                        <th style="width:180px">Trang catalog chứng minh</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>1</td><td><em>VT001</em></td><td>…</td><td>Trang 1-15</td></tr>
                    <tr><td>2</td><td><em>VT002</em></td><td>….</td><td>Trang 16-20</td></tr>
                </tbody>
            </table>
        </li>
        <li>
            <strong>Tải đầy đủ các file</strong> lên hệ thống và <strong>hoàn tất nộp báo giá</strong>.
        </li>
    </ol>

    <div class="hd-luu-y">
        <?= IconHelper::svg('alert-triangle', 17) ?>
        <span>
            <strong>Lưu ý:</strong> Số trang trong file Excel phải chính xác theo file PDF
            tài liệu kỹ thuật được tải lên hệ thống.
        </span>
    </div>
</div>
