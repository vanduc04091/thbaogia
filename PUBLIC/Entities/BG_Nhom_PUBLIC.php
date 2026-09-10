<?php
/**
 * BG_Nhom_PUBLIC — 3 nhóm gói thầu theo Thư mời báo giá chung.
 *
 * NGUỒN DUY NHẤT quyết định nhóm nào hiện cột nào. Mọi nơi cần biết
 * "nhóm này có yêu cầu cấu hình không?" đều hỏi class này, KHÔNG tự
 * viết lại điều kiện — sửa 1 chỗ là cả hệ thống theo.
 *
 * Khác biệt giữa 3 nhóm (Phụ lục III Thư mời):
 *
 *   | Nhóm            | YC chung | YC khác | YC cấu hình | YC kỹ thuật |
 *   |-----------------|----------|---------|-------------|-------------|
 *   | bo_dung_cu      |    có    |   có    |     —       |  có (c.tiết)|
 *   | he_thong_tbyt   |    có    |   có    |    có       |  có (c.tiết)|
 *   | vat_tu_duoc     |    —     |   —     |     —       |     có      |
 *
 * Yêu cầu chung/khác/cấu hình gắn với BỘ; yêu cầu kỹ thuật gắn với
 * HÀNG HÓA CHI TIẾT trong bộ.
 */
class BG_Nhom_PUBLIC
{
    const BO_DUNG_CU    = 'bo_dung_cu';
    const HE_THONG_TBYT = 'he_thong_tbyt';
    const VAT_TU_DUOC   = 'vat_tu_duoc';

    /** Nhóm mặc định khi tạo gói thầu mới + cho dữ liệu cũ */
    const MAC_DINH = self::VAT_TU_DUOC;

    public static function tenNhom(string $nhom): string
    {
        switch ($nhom) {
            case self::BO_DUNG_CU:    return 'Bộ y dụng cụ';
            case self::HE_THONG_TBYT: return 'Hệ thống máy, thiết bị y tế';
            case self::VAT_TU_DUOC:   return 'Vật tư, dược';
            default:                  return 'Không rõ';
        }
    }

    /** Mô tả ngắn để hiện dưới ô chọn nhóm */
    public static function moTa(string $nhom): string
    {
        switch ($nhom) {
            case self::BO_DUNG_CU:
                return 'Có yêu cầu chung + yêu cầu khác cho cả bộ; '
                     . 'yêu cầu kỹ thuật cho từng dụng cụ chi tiết.';
            case self::HE_THONG_TBYT:
                return 'Có yêu cầu chung + khác + cấu hình cho cả hệ thống; '
                     . 'yêu cầu kỹ thuật cho từng thành phần chi tiết.';
            case self::VAT_TU_DUOC:
                return 'Chỉ có yêu cầu kỹ thuật. Nếu là bộ thì yêu cầu kỹ thuật '
                     . 'nằm ở hàng hóa chi tiết trong bộ.';
            default:
                return '';
        }
    }

    /** Danh sách cho combo chọn nhóm */
    public static function danhSach(): array
    {
        return [
            self::BO_DUNG_CU    => self::tenNhom(self::BO_DUNG_CU),
            self::HE_THONG_TBYT => self::tenNhom(self::HE_THONG_TBYT),
            self::VAT_TU_DUOC   => self::tenNhom(self::VAT_TU_DUOC),
        ];
    }

    /** Giá trị gửi lên có hợp lệ không — dùng để validate ở BUS */
    public static function hopLe(string $nhom): bool
    {
        return isset(self::danhSach()[$nhom]);
    }

    /** Ép về nhóm hợp lệ, sai thì trả mặc định (không ném lỗi) */
    public static function chuanHoa(?string $nhom): string
    {
        $nhom = trim((string)$nhom);
        return self::hopLe($nhom) ? $nhom : self::MAC_DINH;
    }

    // =====================================================================
    // QUY TẮC ẨN/HIỆN CỘT — dùng chung cho GUI, file mẫu Excel, Word
    // =====================================================================

    /** Nhóm này có phần "yêu cầu chung" (gắn với bộ) không? */
    public static function coYeuCauChung(string $nhom): bool
    {
        return $nhom === self::BO_DUNG_CU || $nhom === self::HE_THONG_TBYT;
    }

    /** Nhóm này có phần "yêu cầu khác" (gắn với bộ) không? */
    public static function coYeuCauKhac(string $nhom): bool
    {
        return self::coYeuCauChung($nhom);   // luôn đi kèm yêu cầu chung
    }

    /** Yêu cầu cấu hình CHỈ có ở hệ thống thiết bị y tế */
    public static function coYeuCauCauHinh(string $nhom): bool
    {
        return $nhom === self::HE_THONG_TBYT;
    }

    /**
     * Các cột của BỘ mà nhóm này dùng.
     * @return string[] tên cột trong bg_bo
     */
    public static function cotCuaBo(string $nhom): array
    {
        $cot = [];
        if (self::coYeuCauChung($nhom))   $cot[] = 'yeu_cau_chung';
        if (self::coYeuCauKhac($nhom))    $cot[] = 'yeu_cau_khac';
        if (self::coYeuCauCauHinh($nhom)) $cot[] = 'yeu_cau_cau_hinh';
        return $cot;
    }

    /**
     * Các cặp (đáp ứng, không đạt) nhà thầu phải điền ở Mẫu 1, theo nhóm.
     * Trả mảng: [khóa => [nhãn, cột đáp ứng, cột không đạt]]
     *
     * Cặp "kỹ thuật" và "nhóm nước" LUÔN có ở cả 3 nhóm.
     */
    public static function capDapUng(string $nhom): array
    {
        $ds = [];
        if (self::coYeuCauChung($nhom)) {
            $ds['chung'] = ['Yêu cầu chung', 'dap_ung_chung', 'khong_dat_chung'];
        }
        if (self::coYeuCauKhac($nhom)) {
            $ds['khac'] = ['Yêu cầu khác', 'dap_ung_khac', 'khong_dat_khac'];
        }
        if (self::coYeuCauCauHinh($nhom)) {
            $ds['cau_hinh'] = ['Yêu cầu cấu hình', 'dap_ung_cau_hinh', 'khong_dat_cau_hinh'];
        }
        $ds['ky_thuat'] = ['Yêu cầu kỹ thuật', 'thong_so_chao_gia', 'diem_khong_dat'];
        $ds['nhom_nuoc'] = ['Nhóm nước, vùng lãnh thổ', 'dap_ung_nhom_nuoc', 'khong_dat_nhom_nuoc'];
        return $ds;
    }
}
