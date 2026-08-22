<?php
/**
 * BG_HangHoa_PUBLIC — DTO cho bảng `bg_hang_hoa`.
 *
 * Cột bám đúng **Phụ lục III — Bảng mô tả yêu cầu kỹ thuật cơ bản của hàng hóa**
 * trong Thư mời chào giá:
 *
 *   STT | Mã HH | Tên hàng hóa chào giá | Yêu cầu kỹ thuật mời chào giá | ĐVT | Số lượng
 *
 * Các cột kiểu cũ (ten_phan, stt_theo_phan, chung_nhan, yeu_cau_xuat_xu,
 * yeu_cau_tro_cu...) đã được gỡ — xem database/migrate_don_cot_thua.php.
 */
class BG_HangHoa_PUBLIC
{
    public ?int $id = null;
    public int $goi_thau_id = 0;

    /** Mã HH — mã hàng hóa do bên mời đặt (VD: VT001). Duy nhất trong 1 gói thầu. */
    public ?string $ma_hh = null;

    /** Tên hàng hóa chào giá */
    public string $ten_hang_hoa = '';

    /** Yêu cầu kỹ thuật mời chào giá */
    public ?string $thong_so_ky_thuat = null;

    /** Đơn vị tính */
    public ?string $dvt = null;

    /** Số lượng / khối lượng */
    public float $so_luong = 0;

    /** Thứ tự hiển thị (STT trong bảng) */
    public int $thu_tu = 0;

    public ?string $ngay_tao = null;
    public ?string $ngay_cap_nhat = null;
    public ?int $nguoi_tao = null;
    public ?int $nguoi_cap_nhat = null;
    public int $da_xoa = 0;
}
