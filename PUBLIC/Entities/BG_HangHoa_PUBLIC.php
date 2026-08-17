<?php
/**
 * BG_HangHoa_PUBLIC — DTO hàng hóa yêu cầu của gói thầu (cột A-K file mẫu)
 */
class BG_HangHoa_PUBLIC
{
    public ?int $id = null;
    public int $goi_thau_id = 0;
    public ?string $ten_phan = null;          // A
    public ?string $stt_theo_phan = null;     // B
    public ?string $stt_thong_bao = null;     // C
    public string $ten_hang_hoa = '';         // D
    public ?string $thong_so_ky_thuat = null; // E
    public ?string $chung_nhan = null;        // F
    public ?string $yeu_cau_xuat_xu = null;   // G
    public ?string $dvt = null;               // H
    public float $so_luong = 0;               // I
    public ?string $yeu_cau_tro_cu = null;    // J
    public int $thu_tu = 0;
    public ?string $ngay_tao = null;
    public ?string $ngay_cap_nhat = null;
    public ?int $nguoi_tao = null;
    public ?int $nguoi_cap_nhat = null;
    public int $da_xoa = 0;
}
