<?php
/**
 * BG_Bo_PUBLIC — DTO cho bảng `bg_bo`.
 *
 * 1 bộ = 1 dòng "bộ/phần/hệ thống" ở Phụ lục III Thư mời, chứa nhiều
 * hàng hóa chi tiết (bg_hang_hoa.bo_id trỏ về đây).
 *
 * Hàng lẻ (vd vật tư "Bơm tiêm 10ml") vẫn là 1 bộ có đúng 1 chi tiết —
 * không phân nhánh dữ liệu, mọi truy vấn đi chung một đường.
 *
 * yeu_cau_chung / khac / cau_hinh chỉ dùng ở một số nhóm — xem
 * BG_Nhom_PUBLIC::cotCuaBo().
 */
class BG_Bo_PUBLIC
{
    public ?int $id = null;
    public int $goi_thau_id = 0;

    public ?string $ma_bo = null;        // cột (1) Phụ lục III
    public ?int $stt_bo = null;          // cột (2)
    public ?string $ten_bo = null;       // cột (3)

    public ?string $yeu_cau_chung = null;     // cột (6) — bộ dụng cụ, hệ thống TBYT
    public ?string $yeu_cau_khac = null;      // cột (7) — bộ dụng cụ, hệ thống TBYT
    public ?string $yeu_cau_cau_hinh = null;  // cột (8) — CHỈ hệ thống TBYT
    public ?string $nhom_nuoc = null;         // cột (10)

    public ?string $dvt = null;
    public float $so_luong = 0;
    public int $thu_tu = 0;

    public ?string $ngay_tao = null;
    public ?string $ngay_cap_nhat = null;
    public ?int $nguoi_tao = null;
    public ?int $nguoi_cap_nhat = null;
    public int $da_xoa = 0;

    /** Cột tính từ JOIN — số hàng hóa chi tiết trong bộ */
    public ?int $so_chi_tiet = null;
}
