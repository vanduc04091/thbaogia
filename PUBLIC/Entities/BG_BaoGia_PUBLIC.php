<?php
/**
 * BG_BaoGia_PUBLIC — DTO 1 lần nộp báo giá của 1 nhà thầu
 */
class BG_BaoGia_PUBLIC
{
    public ?int $id = null;
    public int $goi_thau_id = 0;
    public string $ten_cong_ty = '';
    public ?string $ma_so_thue = null;
    public ?string $email = null;
    public ?string $dien_thoai = null;
    public ?string $dia_chi = null;
    public int $hieu_luc_bao_gia = 0;
    public ?string $ghi_chu = null;
    public int $trang_thai = 0;
    /** Nhà thầu đã chốt xong 5 bước → khóa mọi chỉnh sửa, chỉ còn xem */
    public int $da_hoan_thanh = 0;
    public ?string $ngay_hoan_thanh = null;
    public ?string $ngay_nop = null;
    public ?string $ngay_xac_nhan = null;
    public ?int $nguoi_xac_nhan = null;
    public ?string $ly_do_tu_choi = null;
    /**
     * Trỏ tới bg_file.id — bản báo giá có dấu & chữ ký.
     * Thông tin file (tên, dung lượng, ngày tải) nằm ở bảng bg_file,
     * bảng này chỉ giữ khóa để không phình cột khi thêm loại file mới.
     */
    public ?int $file_ban_ky_id = null;
    public ?int $file_catalog_id = null;   // Bước 5 — file catalog đã ký
    public ?int $file_catalog_excel_id = null;   // Bước 5 — file Excel chỉ dẫn
    public float $tong_tien = 0;
    public ?string $ip_nop = null;
    public ?string $ngay_tao = null;
    public ?string $ngay_cap_nhat = null;
    public ?int $nguoi_tao = null;
    public ?int $nguoi_cap_nhat = null;
    public int $da_xoa = 0;

    // === Cột từ JOIN ===
    public ?string $so_thong_bao = null;
    public ?string $ten_goi_thau = null;
    public ?int $so_dong_chao = null;
    public ?string $tai_khoan_xac_nhan = null;

    // === Trạng thái ===
    const TT_CHO_XAC_NHAN = 0;
    const TT_DA_XAC_NHAN  = 1;
    const TT_TU_CHOI      = 2;

    public static function tenTrangThai(int $tt): string
    {
        switch ($tt) {
            case self::TT_CHO_XAC_NHAN: return 'Chờ xác nhận';
            case self::TT_DA_XAC_NHAN:  return 'Đã xác nhận';
            case self::TT_TU_CHOI:      return 'Từ chối';
            default:                    return 'Không rõ';
        }
    }

    public static function danhSachTrangThai(): array
    {
        return [
            self::TT_CHO_XAC_NHAN => 'Chờ xác nhận',
            self::TT_DA_XAC_NHAN  => 'Đã xác nhận',
            self::TT_TU_CHOI      => 'Từ chối',
        ];
    }
}
