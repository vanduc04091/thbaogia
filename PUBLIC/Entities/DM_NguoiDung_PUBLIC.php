<?php
class DM_NguoiDung_PUBLIC
{
    public ?int $id = null;
    public string $tai_khoan = '';
    public string $mat_khau = '';
    public ?int $nhom_tai_khoan_id = 0;
    public ?int $trang_thai = 1;
    public ?string $lan_dang_nhap_cuoi = null;
    public ?string $ngay_tao = null;
    public ?string $ngay_cap_nhat = null;
    public ?int $nguoi_tao = null;
    public ?int $nguoi_cap_nhat = null;
    public int $da_xoa = 0;
}