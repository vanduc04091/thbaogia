<?php
/**
 * BG_GoiThau_PUBLIC — DTO gói thầu / thông báo mời chào giá
 */
class BG_GoiThau_PUBLIC
{
    public ?int $id = null;
    public string $so_thong_bao = '';
    public string $ten_goi_thau = '';
    public ?string $noi_dung = null;
    public ?string $ngay_phat_hanh = null;
    public ?string $thoi_gian_mo_bao_gia = null;    // DATETIME — bắt đầu nhận báo giá
    public ?string $thoi_gian_dong_bao_gia = null;  // DATETIME — kết thúc nhận báo giá
    public ?string $han_cuoi = null;
    public int $thoi_gian_hop_dong = 0;
    public int $hieu_luc_bao_gia = 180;
    public string $token = '';
    public int $trang_thai = 1;
    public ?string $ngay_tao = null;
    public ?string $ngay_cap_nhat = null;
    public ?int $nguoi_tao = null;
    public ?int $nguoi_cap_nhat = null;
    public int $da_xoa = 0;

    // === Cột tính toán từ JOIN (không có trong bảng) ===
    public ?int $so_hang_hoa = null;
    public ?int $so_bao_gia = null;
    public ?int $so_bao_gia_xac_nhan = null;

    // === Trạng thái ===
    const TT_NHAP        = 0;
    const TT_DANG_MO     = 1;
    const TT_DA_DONG     = 2;
    const TT_DA_TONG_HOP = 3;

    public static function tenTrangThai(int $tt): string
    {
        switch ($tt) {
            case self::TT_NHAP:        return 'Nháp';
            case self::TT_DANG_MO:     return 'Đang mở';
            case self::TT_DA_DONG:     return 'Đã đóng';
            case self::TT_DA_TONG_HOP: return 'Đã tổng hợp';
            default:                   return 'Không rõ';
        }
    }

    public static function danhSachTrangThai(): array
    {
        return [
            self::TT_NHAP        => 'Nháp',
            self::TT_DANG_MO     => 'Đang mở',
            self::TT_DA_DONG     => 'Đã đóng',
            self::TT_DA_TONG_HOP => 'Đã tổng hợp',
        ];
    }

    // =====================================================================
    // TRẠNG THÁI BÁO GIÁ THEO THỜI GIAN
    // =====================================================================
    // Khác `trang_thai` (do người dùng đặt tay): đây là trạng thái SUY RA từ
    // mốc thời gian mở/đóng, quyết định nhà thầu quét QR có điền được hay không.

    /** Chưa tới thời gian mở báo giá */
    const BG_CHUA_MO = 'chua_mo';
    /** Đang trong khoảng thời gian nhận báo giá */
    const BG_DANG_MO = 'dang_mo';
    /** Đã qua thời gian đóng báo giá */
    const BG_HET_HAN = 'het_han';
    /** Gói thầu chưa được mở (trạng thái Nháp) hoặc đã đóng/tổng hợp thủ công */
    const BG_KHONG_NHAN = 'khong_nhan';

    public static function danhSachTrangThaiBaoGia(): array
    {
        return [
            self::BG_CHUA_MO    => 'Chưa mở báo giá',
            self::BG_DANG_MO    => 'Đang mở báo giá',
            self::BG_HET_HAN    => 'Hết thời gian báo giá',
            self::BG_KHONG_NHAN => 'Không nhận báo giá',
        ];
    }

    public static function tenTrangThaiBaoGia(string $ma): string
    {
        return self::danhSachTrangThaiBaoGia()[$ma] ?? 'Không rõ';
    }

    /**
     * Tính trạng thái báo giá từ trạng thái gói thầu + mốc thời gian.
     *
     * NGUỒN DUY NHẤT của logic này — dùng ở danh sách gói thầu, cổng nhà thầu
     * và mọi chỗ cần biết "có nhận báo giá lúc này không".
     *
     * @param string|null $mocHienTai 'Y-m-d H:i:s' (mặc định: bây giờ) — tham số hóa để test được
     */
    public static function tinhTrangThaiBaoGia(
        int $trangThai,
        ?string $moBaoGia,
        ?string $dongBaoGia,
        ?string $mocHienTai = null
    ): string {
        // Nháp / đã đóng / đã tổng hợp → không nhận, bất kể mốc thời gian
        if ($trangThai !== self::TT_DANG_MO) {
            return self::BG_KHONG_NHAN;
        }

        $now = $mocHienTai ?? date('Y-m-d H:i:s');

        if (!empty($moBaoGia) && $now < $moBaoGia) {
            return self::BG_CHUA_MO;
        }
        if (!empty($dongBaoGia) && $now > $dongBaoGia) {
            return self::BG_HET_HAN;
        }
        return self::BG_DANG_MO;
    }

    /** Trạng thái báo giá của chính bản ghi này */
    public function trangThaiBaoGia(?string $mocHienTai = null): string
    {
        return self::tinhTrangThaiBaoGia(
            (int)$this->trang_thai,
            $this->thoi_gian_mo_bao_gia,
            $this->thoi_gian_dong_bao_gia,
            $mocHienTai
        );
    }

    /** Nhà thầu có được điền báo giá lúc này không */
    public function choPhepChaoGia(?string $mocHienTai = null): bool
    {
        return $this->trangThaiBaoGia($mocHienTai) === self::BG_DANG_MO;
    }
}
