<?php
/**
 * BG_BaoGiaChiTiet_PUBLIC — DTO cho bảng `bg_bao_gia_chi_tiet`.
 *
 * Mỗi bản ghi = 1 hàng hóa trong báo giá của 1 nhà thầu, gom dữ liệu của
 * CẢ HAI mẫu trong Phụ lục II Thư mời:
 *
 * **Mẫu 1 — Bảng đáp ứng kỹ thuật** (Bước 2 ở cổng nhà thầu)
 *   thong_so_chao_gia  : Yêu cầu kỹ thuật chào giá
 *   diem_khong_dat     : Các điểm không đạt kèm thuyết minh
 *
 * **Mẫu 2 — Bảng chào giá** (Bước 3 ở cổng nhà thầu)
 *   ten_thuong_mai (2) | model (3) | hang_san_xuat (4) | xuat_xu (5)
 *   quy_cach (7) | don_gia (9) | thanh_tien (10)
 *   don_gia_trung_thau (11) | tai_lieu_tham_chieu (12)
 *
 * LƯU Ý: Mẫu 2 KHÔNG có cột thuế VAT / chi phí dịch vụ — đơn giá đã bao gồm
 * thuế, phí, lệ phí và các dịch vụ liên quan. Số lượng và ĐVT lấy từ
 * bg_hang_hoa (nhà thầu không được đổi).
 */
class BG_BaoGiaChiTiet_PUBLIC
{
    public ?int $id = null;
    public int $bao_gia_id = 0;
    public int $hang_hoa_id = 0;

    // ===== Mẫu 1: Bảng đáp ứng kỹ thuật =====
    /** Yêu cầu kỹ thuật chào giá — thông số của hàng hóa nhà thầu chào */
    public ?string $thong_so_chao_gia = null;
    /** Các điểm không đạt kèm thuyết minh */
    public ?string $diem_khong_dat = null;

    // --- Các cặp đáp ứng khác — CHỈ dùng ở một số nhóm gói thầu,
    //     xem BG_Nhom_PUBLIC::capDapUng() để biết nhóm nào có cột nào ---
    public ?string $dap_ung_chung = null;        // (11) Mẫu 1
    public ?string $khong_dat_chung = null;      // (12)
    public ?string $dap_ung_khac = null;         // (13)
    public ?string $khong_dat_khac = null;       // (14)
    public ?string $dap_ung_cau_hinh = null;     // (15) chỉ hệ thống TBYT
    public ?string $khong_dat_cau_hinh = null;   // (16) chỉ hệ thống TBYT
    public ?string $dap_ung_nhom_nuoc = null;    // (19)
    public ?string $khong_dat_nhom_nuoc = null;  // (20)
    /** (21) Tài liệu chứng minh: cam kết, catalog, HDSD... */
    public ?string $tai_lieu_chung_minh = null;

    // ===== Mẫu 2: Bảng chào giá =====
    public ?string $ten_thuong_mai = null;      // (2) Tên thương mại
    public ?string $model = null;               // (3) Ký, mã, nhãn hiệu, model
    public ?string $hang_san_xuat = null;       // (4) Hãng sản xuất
    public ?string $nam_san_xuat = null;        // Năm sản xuất (Mẫu 2 mới)
    public ?string $xuat_xu = null;             // (5) Xuất xứ
    public ?string $quy_cach = null;            // (7) Quy cách

    /** (9) Đơn giá — ĐÃ bao gồm thuế, phí, lệ phí và dịch vụ liên quan */
    public float $don_gia = 0;
    /** (10) Thành tiền = don_gia × bg_hang_hoa.so_luong (luôn tính ở server) */
    public float $thanh_tien = 0;

    /** (11) Đơn giá trúng thầu gần nhất trong vòng 360 ngày */
    public float $don_gia_trung_thau = 0;
    /** (12) Tài liệu tham chiếu đơn giá trúng thầu gần nhất */
    public ?string $tai_lieu_tham_chieu = null;
    /** Số thông báo mời thầu (VD: IB2500...) */

    public ?string $ngay_tao = null;
    public ?string $ngay_cap_nhat = null;
    public int $da_xoa = 0;

    /** Đã điền phần đáp ứng kỹ thuật (Mẫu 1) chưa */
    public function daDapUngKyThuat(): bool
    {
        return trim((string)$this->thong_so_chao_gia) !== '';
    }

    /** Đã chào giá (Mẫu 2) chưa */
    public function daChaoGia(): bool
    {
        return $this->don_gia > 0;
    }
}
