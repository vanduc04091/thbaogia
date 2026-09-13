<?php
/**
 * BG_BaoGiaBo_PUBLIC — DTO cho bảng `bg_bao_gia_bo`.
 *
 * Giá nhà thầu chào cho CẢ BỘ, dùng ở 2 nhóm mua theo bộ
 * (bo_dung_cu, he_thong_tbyt). Nhóm vat_tu_duoc không dùng bảng này —
 * hàng lẻ chào giá ở bg_bao_gia_chi_tiet như cũ.
 *
 * Vì sao tách bảng riêng: bg_bao_gia_chi_tiet bắt buộc gắn với 1 hàng hóa
 * (hang_hoa_id NOT NULL + UNIQUE), không có chỗ chứa giá cấp BỘ. Nới lỏng
 * ràng buộc đó sẽ mất lớp bảo vệ "1 báo giá chỉ chào 1 lần cho 1 hàng hóa".
 *
 * ⚠️ thanh_tien ở ĐÂY do NHÀ THẦU NHẬP, khác với bg_bao_gia_chi_tiet
 * (server luôn tự tính = đơn giá × số lượng). Giá trọn gói một bộ không
 * phải lúc nào cũng chia đều theo số lượng bộ nên bên mời cho nhập tay.
 * Ngoại lệ CÓ CHỦ Ý với §10.2 — đừng "sửa lại cho đúng chuẩn".
 */
class BG_BaoGiaBo_PUBLIC
{
    public ?int $id = null;
    public int $bao_gia_id = 0;
    public int $bo_id = 0;

    // Nhà thầu điền ở dòng BỘ của Mẫu 2
    public ?string $ten_thuong_mai = null;
    public ?string $model = null;
    public ?string $hang_san_xuat = null;
    public ?string $nam_san_xuat = null;
    public ?string $xuat_xu = null;

    public float $don_gia = 0;      // đơn giá TRỌN BỘ
    public float $thanh_tien = 0;   // nhà thầu nhập tay

    // ---- Đáp ứng cấp BỘ (Mẫu 1, dòng BỘ) ----
    // Yêu cầu chung / khác / cấu hình gắn với BỘ (bg_bo) nên phần ĐÁP ỨNG cho
    // chúng cũng phải nằm ở dòng bộ, không phải ở từng hàng hóa chi tiết.
    // Trước đây không có chỗ chứa → nhà thầu điền vào dòng bộ thì import bỏ
    // qua im lặng, Bước 4 in ra bảng đáp ứng trống trơn.
    public ?string $dap_ung_chung = null;
    public ?string $khong_dat_chung = null;
    public ?string $dap_ung_khac = null;
    public ?string $khong_dat_khac = null;
    public ?string $dap_ung_cau_hinh = null;
    public ?string $khong_dat_cau_hinh = null;
    public ?string $thong_so_chao_gia = null;   // đáp ứng yêu cầu kỹ thuật
    public ?string $diem_khong_dat = null;      // điểm không đáp ứng kỹ thuật
    public ?string $dap_ung_nhom_nuoc = null;
    public ?string $khong_dat_nhom_nuoc = null;
    public ?string $tai_lieu_chung_minh = null;

    public ?string $ngay_tao = null;
    public ?string $ngay_cap_nhat = null;
    public int $da_xoa = 0;
}
