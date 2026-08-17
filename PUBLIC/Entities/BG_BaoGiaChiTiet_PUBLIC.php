<?php
/**
 * BG_BaoGiaChiTiet_PUBLIC — DTO dòng chào giá cho 1 hàng hóa (cột L-AD file mẫu)
 */
class BG_BaoGiaChiTiet_PUBLIC
{
    public ?int $id = null;
    public int $bao_gia_id = 0;
    public int $hang_hoa_id = 0;
    public ?string $ten_thuong_mai = null;      // L
    public ?string $model = null;               // M
    public ?string $ma_hs = null;               // N
    public ?string $hang_san_xuat = null;       // O
    public ?string $xuat_xu = null;             // P
    public ?string $quy_cach = null;            // R
    public float $chi_phi_dich_vu = 0;          // T
    public float $thue_vat = 0;                 // U (%)
    public float $don_gia = 0;                  // V
    public float $thanh_tien = 0;               // W
    public ?string $chung_nhan_chao = null;     // X
    public float $don_gia_trung_thau = 0;       // Y
    public ?string $tai_lieu_tham_chieu = null; // Z
    public ?string $ma_qr_hang_hoa = null;      // AA
    public ?string $thong_so_chao_gia = null;   // AC
    public ?string $diem_khong_dat = null;      // AD
    public ?string $ngay_tao = null;
    public ?string $ngay_cap_nhat = null;
    public int $da_xoa = 0;

    // === Cột từ JOIN với bg_hang_hoa ===
    public ?string $ten_hang_hoa = null;
    public ?string $stt_theo_phan = null;
    public ?string $ten_phan = null;
    public ?string $dvt = null;
    public ?float $so_luong = null;
    public ?string $thong_so_ky_thuat = null;
}
