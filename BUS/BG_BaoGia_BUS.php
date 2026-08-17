<?php
require_once __DIR__ . '/../DAL/BG_BaoGia_DAL.php';
require_once __DIR__ . '/../DAL/BG_HangHoa_DAL.php';
require_once __DIR__ . '/../DAL/BG_GoiThau_DAL.php';
require_once __DIR__ . '/../DAL/DM_NhatKyHeThong_DAL.php';
require_once __DIR__ . '/../PUBLIC/Common/ExcelHelper.php';
require_once __DIR__ . '/BG_GoiThau_BUS.php';
require_once __DIR__ . '/BG_HangHoa_BUS.php';

class BG_BaoGia_BUS
{
    const MODULE_KEY = 'BG_BaoGia';
    const MODULE_LOG = 'BaoGia';

    /** Chỉ số cột phần nhà thầu điền (0-based) trong file mẫu 30 cột */
    const COL_TEN_THUONG_MAI = 11; // L
    const COL_MODEL          = 12; // M
    const COL_MA_HS          = 13; // N
    const COL_HANG_SX        = 14; // O
    const COL_XUAT_XU        = 15; // P
    const COL_QUY_CACH       = 17; // R
    const COL_CHI_PHI_DV     = 19; // T
    const COL_VAT            = 20; // U
    const COL_DON_GIA        = 21; // V
    const COL_CHUNG_NHAN     = 23; // X
    const COL_GIA_TRUNG_THAU = 24; // Y
    const COL_TAI_LIEU       = 25; // Z
    const COL_MA_QR          = 26; // AA
    const COL_THONG_SO_CHAO  = 28; // AC
    const COL_DIEM_KHONG_DAT = 29; // AD

    /** Dòng bắt đầu dữ liệu trong file mẫu do hệ thống sinh ra (1=header, 2=HD) */
    const EXCEL_DATA_ROW = 3;

    // =====================================================================
    // VALIDATE THÔNG TIN CÔNG TY
    // =====================================================================

    private static function validateThongTin(BG_BaoGia_PUBLIC $e): string
    {
        $e->ten_cong_ty = trim($e->ten_cong_ty);
        $e->ma_so_thue  = trim((string)$e->ma_so_thue);
        $e->email       = trim((string)$e->email);
        $e->dien_thoai  = trim((string)$e->dien_thoai);

        if ($e->ten_cong_ty === '') return 'Tên công ty không được để trống';
        if (mb_strlen($e->ten_cong_ty) > 500) return 'Tên công ty tối đa 500 ký tự';

        if ($e->ma_so_thue === '') return 'Mã số thuế không được để trống';
        // MST Việt Nam: 10 số, hoặc 10 số - 3 số (đơn vị trực thuộc)
        if (!preg_match('/^\d{10}(-\d{3})?$/', $e->ma_so_thue)) {
            return 'Mã số thuế không hợp lệ (10 số, hoặc dạng 0101234567-001)';
        }

        if ($e->email !== '' && !Helper::isEmail($e->email)) {
            return 'Email không hợp lệ';
        }
        if ($e->dien_thoai !== '' && !Helper::isPhone($e->dien_thoai)) {
            return 'Số điện thoại không hợp lệ';
        }
        if ($e->hieu_luc_bao_gia < 0 || $e->hieu_luc_bao_gia > 3650) {
            return 'Hiệu lực báo giá không hợp lệ';
        }
        return '';
    }

    // =====================================================================
    // NHÀ THẦU: TẠO / CẬP NHẬT BÁO GIÁ QUA CỔNG QR
    // =====================================================================

    /**
     * Nhà thầu khai thông tin công ty → tạo phiếu báo giá nháp.
     * Trả về id báo giá để tiếp tục điền giá.
     */
    public static function taoBaoGia(BG_BaoGia_PUBLIC $e, int $u): array
    {
        $gt = BG_GoiThau_DAL::getById($e->goi_thau_id);
        if (!$gt || $gt->da_xoa === 1) return ['success' => false, 'message' => 'Gói thầu không tồn tại'];

        $conNhan = BG_GoiThau_BUS::kiemTraConNhan($gt);
        if (!$conNhan['ok']) return ['success' => false, 'message' => $conNhan['message']];

        $err = self::validateThongTin($e);
        if ($err !== '') return ['success' => false, 'message' => $err];

        if (BG_BaoGia_DAL::existsMstTrongGoiThau((string)$e->ma_so_thue, $e->goi_thau_id)) {
            return [
                'success' => false,
                'message' => 'Mã số thuế này đã nộp báo giá cho gói thầu. Liên hệ bên mời chào giá nếu cần nộp lại.',
            ];
        }

        // Hiệu lực nhà thầu cam kết không được thấp hơn yêu cầu của gói thầu
        if ((int)$gt->hieu_luc_bao_gia > 0 && $e->hieu_luc_bao_gia < (int)$gt->hieu_luc_bao_gia) {
            return [
                'success' => false,
                'message' => 'Hiệu lực báo giá tối thiểu ' . (int)$gt->hieu_luc_bao_gia . ' ngày',
            ];
        }

        try {
            $e->trang_thai = BG_BaoGia_PUBLIC::TT_CHO_XAC_NHAN;
            $e->ip_nop = Helper::getClientIp();
            $e->nguoi_tao = $u;
            $id = BG_BaoGia_DAL::insert($e);

            DM_NhatKyHeThong_DAL::log(
                $u, self::MODULE_LOG,
                "Nhà thầu tạo báo giá gói {$gt->so_thong_bao}: {$e->ten_cong_ty} (MST {$e->ma_so_thue})",
                'bg_bao_gia', $id
            );
            return ['success' => true, 'message' => 'Đã lưu thông tin công ty', 'data' => ['id' => $id]];
        } catch (Throwable $ex) {
            return ['success' => false, 'message' => 'Lỗi: ' . $ex->getMessage()];
        }
    }

    /** Cập nhật thông tin công ty (khi chưa được xác nhận bản giấy) */
    public static function capNhatThongTin(BG_BaoGia_PUBLIC $e, int $u): array
    {
        if (!$e->id) return ['success' => false, 'message' => 'Thiếu ID'];
        $cu = BG_BaoGia_DAL::getById((int)$e->id);
        if (!$cu || $cu->da_xoa === 1) return ['success' => false, 'message' => 'Không tìm thấy báo giá'];
        if ((int)$cu->trang_thai === BG_BaoGia_PUBLIC::TT_DA_XAC_NHAN) {
            return ['success' => false, 'message' => 'Báo giá đã được xác nhận bản giấy — không thể sửa'];
        }

        $err = self::validateThongTin($e);
        if ($err !== '') return ['success' => false, 'message' => $err];

        if (BG_BaoGia_DAL::existsMstTrongGoiThau((string)$e->ma_so_thue, (int)$cu->goi_thau_id, (int)$e->id)) {
            return ['success' => false, 'message' => 'Mã số thuế này đã có báo giá khác trong gói thầu'];
        }

        try {
            $e->nguoi_cap_nhat = $u;
            BG_BaoGia_DAL::update($e);
            DM_NhatKyHeThong_DAL::log(
                $u, self::MODULE_LOG, "Cập nhật thông tin báo giá: {$e->ten_cong_ty}", 'bg_bao_gia', $e->id
            );
            return ['success' => true, 'message' => 'Cập nhật thành công'];
        } catch (Throwable $ex) {
            return ['success' => false, 'message' => 'Lỗi: ' . $ex->getMessage()];
        }
    }

    /**
     * Lưu giá cho 1 hàng hóa (nhập tay trên form web).
     *
     * @param array $input dữ liệu 1 dòng chào giá
     */
    public static function luuDongChaoGia(int $baoGiaId, int $hangHoaId, array $input, int $u): array
    {
        $bg = BG_BaoGia_DAL::getById($baoGiaId);
        if (!$bg || $bg->da_xoa === 1) return ['success' => false, 'message' => 'Không tìm thấy báo giá'];
        if ((int)$bg->trang_thai === BG_BaoGia_PUBLIC::TT_DA_XAC_NHAN) {
            return ['success' => false, 'message' => 'Báo giá đã xác nhận bản giấy — không thể sửa giá'];
        }

        $hh = BG_HangHoa_DAL::getById($hangHoaId);
        if (!$hh || $hh->da_xoa === 1) return ['success' => false, 'message' => 'Không tìm thấy hàng hóa'];
        if ((int)$hh->goi_thau_id !== (int)$bg->goi_thau_id) {
            return ['success' => false, 'message' => 'Hàng hóa không thuộc gói thầu của báo giá này'];
        }

        $donGia = ExcelHelper::toNumber($input['don_gia'] ?? 0);
        if ($donGia < 0) return ['success' => false, 'message' => 'Đơn giá không được âm'];
        $vat = self::chuanHoaVat($input['thue_vat'] ?? 0);

        $ct = new BG_BaoGiaChiTiet_PUBLIC();
        $ct->bao_gia_id          = $baoGiaId;
        $ct->hang_hoa_id         = $hangHoaId;
        $ct->ten_thuong_mai      = self::nullIfEmpty($input['ten_thuong_mai'] ?? '', 1000);
        $ct->model               = self::nullIfEmpty($input['model'] ?? '', 500);
        $ct->ma_hs               = self::nullIfEmpty($input['ma_hs'] ?? '', 200);
        $ct->hang_san_xuat       = self::nullIfEmpty($input['hang_san_xuat'] ?? '', 500);
        $ct->xuat_xu             = self::nullIfEmpty($input['xuat_xu'] ?? '', 500);
        $ct->quy_cach            = self::nullIfEmpty($input['quy_cach'] ?? '', 500);
        $ct->chi_phi_dich_vu     = ExcelHelper::toNumber($input['chi_phi_dich_vu'] ?? 0);
        $ct->thue_vat            = $vat;
        $ct->don_gia             = $donGia;
        $ct->thanh_tien          = round($donGia * (float)$hh->so_luong, 2);
        $ct->chung_nhan_chao     = self::nullIfEmpty($input['chung_nhan_chao'] ?? '');
        $ct->don_gia_trung_thau  = ExcelHelper::toNumber($input['don_gia_trung_thau'] ?? 0);
        $ct->tai_lieu_tham_chieu = self::nullIfEmpty($input['tai_lieu_tham_chieu'] ?? '');
        $ct->ma_qr_hang_hoa      = self::nullIfEmpty($input['ma_qr_hang_hoa'] ?? '', 500);
        $ct->thong_so_chao_gia   = self::nullIfEmpty($input['thong_so_chao_gia'] ?? '');
        $ct->diem_khong_dat      = self::nullIfEmpty($input['diem_khong_dat'] ?? '');

        // Ghi chi tiết + cập nhật tổng tiền ở bảng cha → 2 bảng, bọc transaction
        try {
            Database::beginTransaction();
            BG_BaoGia_DAL::upsertChiTiet($ct);
            BG_BaoGia_DAL::updateTongTien($baoGiaId);
            Database::commit();
            return ['success' => true, 'message' => 'Đã lưu', 'data' => [
                'thanh_tien' => $ct->thanh_tien,
            ]];
        } catch (Throwable $ex) {
            Database::rollBack();
            return ['success' => false, 'message' => 'Lỗi: ' . $ex->getMessage()];
        }
    }

    /** VAT: nhận 10 / "10%" / 0.1 / "0,1" → trả về 10 (đơn vị %) */
    private static function chuanHoaVat($raw): float
    {
        $n = ExcelHelper::toNumber($raw);
        if ($n < 0) return 0;
        // 0 < n <= 1 và có dấu thập phân → hiểu là tỷ lệ (0.1 = 10%)
        if ($n > 0 && $n <= 1 && strpos((string)$raw, '%') === false) {
            $s = trim((string)$raw);
            if (strpos($s, '.') !== false || strpos($s, ',') !== false) {
                return round($n * 100, 4);
            }
        }
        return min($n, 100);
    }

    private static function nullIfEmpty($v, int $maxLen = 0): ?string
    {
        $s = ExcelHelper::toText($v, $maxLen);
        return $s === '' ? null : $s;
    }

    /**
     * Nhà thầu nộp báo giá: chốt lại, đánh dấu ngày nộp.
     * Yêu cầu đã chào giá ít nhất 1 dòng.
     */
    public static function nopBaoGia(int $baoGiaId, int $u): array
    {
        $bg = BG_BaoGia_DAL::getById($baoGiaId);
        if (!$bg || $bg->da_xoa === 1) return ['success' => false, 'message' => 'Không tìm thấy báo giá'];
        if ((int)$bg->trang_thai === BG_BaoGia_PUBLIC::TT_DA_XAC_NHAN) {
            return ['success' => false, 'message' => 'Báo giá đã được xác nhận bản giấy'];
        }
        if ((int)$bg->so_dong_chao === 0) {
            return ['success' => false, 'message' => 'Chưa chào giá dòng nào — hãy điền đơn giá hoặc import file trước'];
        }

        $gt = BG_GoiThau_DAL::getById((int)$bg->goi_thau_id);
        if ($gt) {
            $conNhan = BG_GoiThau_BUS::kiemTraConNhan($gt);
            if (!$conNhan['ok']) return ['success' => false, 'message' => $conNhan['message']];
        }

        BG_BaoGia_DAL::markNop($baoGiaId);
        BG_BaoGia_DAL::updateTongTien($baoGiaId);
        DM_NhatKyHeThong_DAL::log(
            $u, self::MODULE_LOG,
            "Nhà thầu nộp báo giá: {$bg->ten_cong_ty} ({$bg->so_dong_chao} dòng)", 'bg_bao_gia', $baoGiaId
        );
        return [
            'success' => true,
            'message' => 'Đã nộp báo giá. Vui lòng gửi bản giấy tới bên mời chào giá để được xác nhận.',
        ];
    }

    // =====================================================================
    // IMPORT FILE BÁO GIÁ CỦA NHÀ THẦU
    // =====================================================================

    /**
     * Import file Excel báo giá do nhà thầu điền.
     * Khớp dòng theo thứ tự hàng hóa của gói thầu (file mẫu sinh ra theo đúng thứ tự đó),
     * đồng thời đối chiếu tên hàng hóa để phát hiện lệch.
     */
    public static function importFileBaoGia(int $baoGiaId, string $filePath, int $u): array
    {
        $bg = BG_BaoGia_DAL::getById($baoGiaId);
        if (!$bg || $bg->da_xoa === 1) return ['success' => false, 'message' => 'Không tìm thấy báo giá'];
        if ((int)$bg->trang_thai === BG_BaoGia_PUBLIC::TT_DA_XAC_NHAN) {
            return ['success' => false, 'message' => 'Báo giá đã xác nhận bản giấy — không thể import lại'];
        }

        $gt = BG_GoiThau_DAL::getById((int)$bg->goi_thau_id);
        if ($gt) {
            $conNhan = BG_GoiThau_BUS::kiemTraConNhan($gt);
            if (!$conNhan['ok']) return ['success' => false, 'message' => $conNhan['message']];
        }

        try {
            $rows = ExcelHelper::readSheet($filePath);
        } catch (Throwable $ex) {
            return ['success' => false, 'message' => 'Không đọc được file: ' . $ex->getMessage()];
        }
        if (empty($rows)) return ['success' => false, 'message' => 'File không có dữ liệu'];

        $hangHoa = BG_HangHoa_DAL::getByGoiThau((int)$bg->goi_thau_id);
        if (empty($hangHoa)) return ['success' => false, 'message' => 'Gói thầu chưa có hàng hóa'];

        // Lập chỉ mục tra cứu theo tên hàng hóa (chuẩn hóa) để khớp linh hoạt
        $theoTen = [];
        foreach ($hangHoa as $i => $hh) {
            $key = self::chuanHoaKhop($hh['ten_hang_hoa']);
            // Trùng tên → giữ danh sách để khớp theo thứ tự xuất hiện
            $theoTen[$key][] = $i;
        }
        $daDungTen = [];

        $canhBao = [];
        $items = [];
        $stt = 0;

        foreach ($rows as $rowNo => $cells) {
            if ($rowNo < self::EXCEL_DATA_ROW) continue;

            $tenFile = ExcelHelper::toText($cells[BG_HangHoa_BUS::COL_TEN_HANG_HOA] ?? '');
            $donGiaRaw = $cells[self::COL_DON_GIA] ?? '';

            // Dòng trắng hoàn toàn → bỏ qua
            if ($tenFile === '' && ExcelHelper::toText($donGiaRaw) === '') continue;
            if (mb_stripos($tenFile, 'HDSD') !== false) continue;
            if (mb_stripos($tenFile, 'KHÔNG SỬA') !== false) continue;

            // === Khớp hàng hóa ===
            $idx = null;
            $key = self::chuanHoaKhop($tenFile);
            if ($key !== '' && !empty($theoTen[$key])) {
                foreach ($theoTen[$key] as $cand) {
                    if (!isset($daDungTen[$cand])) { $idx = $cand; break; }
                }
            }
            // Không khớp được theo tên → dùng thứ tự dòng
            if ($idx === null) {
                if (isset($hangHoa[$stt])) {
                    $idx = $stt;
                    if ($tenFile !== '' && self::chuanHoaKhop($hangHoa[$stt]['ten_hang_hoa']) !== $key) {
                        $canhBao[] = "Dòng {$rowNo}: tên hàng hoá trong file không khớp danh mục "
                                   . "(\"" . mb_substr($tenFile, 0, 40) . "...\") → đã khớp theo thứ tự dòng";
                    }
                } else {
                    $canhBao[] = "Dòng {$rowNo}: vượt quá số hàng hóa của gói thầu → bỏ qua";
                    continue;
                }
            }
            $daDungTen[$idx] = true;
            $stt = $idx + 1;

            $hh = $hangHoa[$idx];
            $donGia = ExcelHelper::toNumber($donGiaRaw);
            if ($donGia < 0) {
                $canhBao[] = "Dòng {$rowNo}: đơn giá âm → đặt về 0";
                $donGia = 0;
            }

            $ct = new BG_BaoGiaChiTiet_PUBLIC();
            $ct->bao_gia_id          = $baoGiaId;
            $ct->hang_hoa_id         = (int)$hh['id'];
            $ct->ten_thuong_mai      = self::nullIfEmpty($cells[self::COL_TEN_THUONG_MAI] ?? '', 1000);
            $ct->model               = self::nullIfEmpty($cells[self::COL_MODEL] ?? '', 500);
            $ct->ma_hs               = self::nullIfEmpty($cells[self::COL_MA_HS] ?? '', 200);
            $ct->hang_san_xuat       = self::nullIfEmpty($cells[self::COL_HANG_SX] ?? '', 500);
            $ct->xuat_xu             = self::nullIfEmpty($cells[self::COL_XUAT_XU] ?? '', 500);
            $ct->quy_cach            = self::nullIfEmpty($cells[self::COL_QUY_CACH] ?? '', 500);
            $ct->chi_phi_dich_vu     = ExcelHelper::toNumber($cells[self::COL_CHI_PHI_DV] ?? 0);
            $ct->thue_vat            = self::chuanHoaVat($cells[self::COL_VAT] ?? 0);
            $ct->don_gia             = $donGia;
            $ct->thanh_tien          = round($donGia * (float)$hh['so_luong'], 2);
            $ct->chung_nhan_chao     = self::nullIfEmpty($cells[self::COL_CHUNG_NHAN] ?? '');
            $ct->don_gia_trung_thau  = ExcelHelper::toNumber($cells[self::COL_GIA_TRUNG_THAU] ?? 0);
            $ct->tai_lieu_tham_chieu = self::nullIfEmpty($cells[self::COL_TAI_LIEU] ?? '');
            $ct->ma_qr_hang_hoa      = self::nullIfEmpty($cells[self::COL_MA_QR] ?? '', 500);
            $ct->thong_so_chao_gia   = self::nullIfEmpty($cells[self::COL_THONG_SO_CHAO] ?? '');
            $ct->diem_khong_dat      = self::nullIfEmpty($cells[self::COL_DIEM_KHONG_DAT] ?? '');

            $items[] = $ct;
        }

        if (empty($items)) {
            return [
                'success' => false,
                'message' => 'Không đọc được dòng chào giá nào. Hãy dùng đúng file mẫu tải từ hệ thống '
                           . '(dữ liệu bắt đầu từ dòng ' . self::EXCEL_DATA_ROW . ').',
                'data' => ['canh_bao' => $canhBao],
            ];
        }

        try {
            Database::beginTransaction();
            $soCoGia = 0;
            foreach ($items as $ct) {
                BG_BaoGia_DAL::upsertChiTiet($ct);
                if ($ct->don_gia > 0) $soCoGia++;
            }
            BG_BaoGia_DAL::updateTongTien($baoGiaId);
            Database::commit();

            DM_NhatKyHeThong_DAL::log(
                $u, self::MODULE_LOG,
                "Import file báo giá {$bg->ten_cong_ty}: " . count($items) . " dòng, {$soCoGia} dòng có giá",
                'bg_bao_gia', $baoGiaId
            );

            return [
                'success' => true,
                'message' => 'Đã import ' . count($items) . " dòng ({$soCoGia} dòng có đơn giá)",
                'data' => ['so_dong' => count($items), 'so_co_gia' => $soCoGia, 'canh_bao' => $canhBao],
            ];
        } catch (Throwable $ex) {
            Database::rollBack();
            return ['success' => false, 'message' => 'Lỗi khi import: ' . $ex->getMessage()];
        }
    }

    /** Chuẩn hóa tên để so khớp: bỏ dấu cách thừa, lowercase */
    private static function chuanHoaKhop(?string $s): string
    {
        $s = mb_strtolower(trim((string)$s));
        $s = preg_replace('/\s+/u', ' ', $s);
        return $s;
    }

    // =====================================================================
    // BÊN MỜI: XÁC NHẬN BẢN GIẤY
    // =====================================================================

    /** Tích xác nhận đã nhận bản giấy → báo giá được đưa vào tổng hợp */
    public static function xacNhan(int $id, int $u): array
    {
        $bg = BG_BaoGia_DAL::getById($id);
        if (!$bg || $bg->da_xoa === 1) return ['success' => false, 'message' => 'Không tìm thấy báo giá'];
        if ((int)$bg->trang_thai === BG_BaoGia_PUBLIC::TT_DA_XAC_NHAN) {
            return ['success' => false, 'message' => 'Báo giá này đã được xác nhận'];
        }
        if ((int)$bg->so_dong_chao === 0) {
            return ['success' => false, 'message' => 'Báo giá chưa có dòng nào có đơn giá — không thể xác nhận'];
        }

        BG_BaoGia_DAL::updateXacNhan($id, BG_BaoGia_PUBLIC::TT_DA_XAC_NHAN, null, $u);
        DM_NhatKyHeThong_DAL::log(
            $u, self::MODULE_LOG,
            "Xác nhận bản giấy báo giá: {$bg->ten_cong_ty} (MST {$bg->ma_so_thue})", 'bg_bao_gia', $id
        );
        return ['success' => true, 'message' => 'Đã xác nhận nhận bản giấy'];
    }

    /** Từ chối báo giá (không đưa vào tổng hợp) */
    public static function tuChoi(int $id, string $lyDo, int $u): array
    {
        $lyDo = trim($lyDo);
        if ($lyDo === '') return ['success' => false, 'message' => 'Vui lòng nhập lý do từ chối'];
        if (mb_strlen($lyDo) > 1000) return ['success' => false, 'message' => 'Lý do tối đa 1000 ký tự'];

        $bg = BG_BaoGia_DAL::getById($id);
        if (!$bg || $bg->da_xoa === 1) return ['success' => false, 'message' => 'Không tìm thấy báo giá'];

        BG_BaoGia_DAL::updateXacNhan($id, BG_BaoGia_PUBLIC::TT_TU_CHOI, $lyDo, $u);
        DM_NhatKyHeThong_DAL::log(
            $u, self::MODULE_LOG,
            "Từ chối báo giá: {$bg->ten_cong_ty} — {$lyDo}", 'bg_bao_gia', $id
        );
        return ['success' => true, 'message' => 'Đã từ chối báo giá'];
    }

    /** Bỏ xác nhận → về trạng thái chờ */
    public static function boXacNhan(int $id, int $u): array
    {
        $bg = BG_BaoGia_DAL::getById($id);
        if (!$bg || $bg->da_xoa === 1) return ['success' => false, 'message' => 'Không tìm thấy báo giá'];

        BG_BaoGia_DAL::updateXacNhan($id, BG_BaoGia_PUBLIC::TT_CHO_XAC_NHAN, null, $u);
        DM_NhatKyHeThong_DAL::log(
            $u, self::MODULE_LOG, "Bỏ xác nhận báo giá: {$bg->ten_cong_ty}", 'bg_bao_gia', $id
        );
        return ['success' => true, 'message' => 'Đã chuyển về trạng thái chờ xác nhận'];
    }

    public static function trash(int $id, int $u): array
    {
        if ($id <= 0) return ['success' => false, 'message' => 'Thiếu ID'];
        $bg = BG_BaoGia_DAL::getById($id);
        if (!$bg) return ['success' => false, 'message' => 'Không tìm thấy báo giá'];

        BG_BaoGia_DAL::softDelete($id, $u);
        DM_NhatKyHeThong_DAL::log(
            $u, self::MODULE_LOG, "Xóa tạm báo giá: {$bg->ten_cong_ty}", 'bg_bao_gia', $id
        );
        return ['success' => true, 'message' => 'Đã chuyển vào thùng rác'];
    }

    public static function restore(int $id, int $u): array
    {
        if ($id <= 0) return ['success' => false, 'message' => 'Thiếu ID'];
        $bg = BG_BaoGia_DAL::getById($id);
        if (!$bg) return ['success' => false, 'message' => 'Không tìm thấy báo giá'];

        BG_BaoGia_DAL::restore($id, $u);
        DM_NhatKyHeThong_DAL::log(
            $u, self::MODULE_LOG, "Khôi phục báo giá: {$bg->ten_cong_ty}", 'bg_bao_gia', $id
        );
        return ['success' => true, 'message' => 'Đã khôi phục báo giá'];
    }

    public static function delete(int $id, int $u): array
    {
        if ($id <= 0) return ['success' => false, 'message' => 'Thiếu ID'];
        $bg = BG_BaoGia_DAL::getById($id);
        if (!$bg) return ['success' => false, 'message' => 'Không tìm thấy báo giá'];

        // Chi tiết xóa theo (ON DELETE không khai báo) → xóa tay, 2 bảng, bọc transaction
        try {
            Database::beginTransaction();
            $stmt = Database::getConnection()->prepare("DELETE FROM bg_bao_gia_chi_tiet WHERE bao_gia_id = :id");
            $stmt->execute([':id' => $id]);
            $n = BG_BaoGia_DAL::delete($id);
            Database::commit();

            if ($n === 0) {
                return ['success' => false, 'message' => 'Chỉ xóa vĩnh viễn được bản ghi trong thùng rác'];
            }
            DM_NhatKyHeThong_DAL::log(
                $u, self::MODULE_LOG, "Xóa vĩnh viễn báo giá: {$bg->ten_cong_ty}", 'bg_bao_gia', $id
            );
            return ['success' => true, 'message' => 'Đã xóa vĩnh viễn'];
        } catch (Throwable $ex) {
            Database::rollBack();
            return ['success' => false, 'message' => 'Lỗi: ' . $ex->getMessage()];
        }
    }

    public static function getById(int $id): ?BG_BaoGia_PUBLIC
    {
        return BG_BaoGia_DAL::getById($id);
    }

    public static function getPaged(int $page, int $pageSize, int $goiThauId = 0, string $search = '', int $trangThai = -1, int $daXoa = 0): array
    {
        return BG_BaoGia_DAL::getPaged($page, $pageSize, $goiThauId, $search, $trangThai, $daXoa);
    }

    public static function getChiTiet(int $baoGiaId): array
    {
        return BG_BaoGia_DAL::getChiTiet($baoGiaId);
    }

    /**
     * Danh mục hàng hóa của gói thầu ghép với dòng chào giá đã có của báo giá.
     * Dùng để render bảng điền giá ở cổng nhà thầu.
     */
    public static function getBangChaoGia(int $baoGiaId): array
    {
        $bg = BG_BaoGia_DAL::getById($baoGiaId);
        if (!$bg) return [];

        $hangHoa = BG_HangHoa_DAL::getByGoiThau((int)$bg->goi_thau_id);
        $daChao = BG_BaoGia_DAL::getChiTietMap($baoGiaId);

        $out = [];
        foreach ($hangHoa as $hh) {
            $id = (int)$hh['id'];
            $ct = $daChao[$id] ?? null;
            $out[] = [
                'hang_hoa_id'         => $id,
                'ten_phan'            => $hh['ten_phan'],
                'stt_theo_phan'       => $hh['stt_theo_phan'],
                'ten_hang_hoa'        => $hh['ten_hang_hoa'],
                'thong_so_ky_thuat'   => $hh['thong_so_ky_thuat'],
                'chung_nhan'          => $hh['chung_nhan'],
                'yeu_cau_xuat_xu'     => $hh['yeu_cau_xuat_xu'],
                'dvt'                 => $hh['dvt'],
                'so_luong'            => (float)$hh['so_luong'],
                // Phần nhà thầu đã điền (nếu có)
                'ten_thuong_mai'      => $ct['ten_thuong_mai'] ?? '',
                'model'               => $ct['model'] ?? '',
                'ma_hs'               => $ct['ma_hs'] ?? '',
                'hang_san_xuat'       => $ct['hang_san_xuat'] ?? '',
                'xuat_xu'             => $ct['xuat_xu'] ?? '',
                'quy_cach'            => $ct['quy_cach'] ?? '',
                'chi_phi_dich_vu'     => (float)($ct['chi_phi_dich_vu'] ?? 0),
                'thue_vat'            => (float)($ct['thue_vat'] ?? 0),
                'don_gia'             => (float)($ct['don_gia'] ?? 0),
                'thanh_tien'          => (float)($ct['thanh_tien'] ?? 0),
                'chung_nhan_chao'     => $ct['chung_nhan_chao'] ?? '',
                'don_gia_trung_thau'  => (float)($ct['don_gia_trung_thau'] ?? 0),
                'tai_lieu_tham_chieu' => $ct['tai_lieu_tham_chieu'] ?? '',
                'ma_qr_hang_hoa'      => $ct['ma_qr_hang_hoa'] ?? '',
                'thong_so_chao_gia'   => $ct['thong_so_chao_gia'] ?? '',
                'diem_khong_dat'      => $ct['diem_khong_dat'] ?? '',
            ];
        }
        return $out;
    }

    public static function thongKe(): array
    {
        return BG_BaoGia_DAL::thongKe();
    }

    // =====================================================================
    // TRA CỨU BÁO GIÁ THEO MÃ SỐ THUẾ
    // =====================================================================

    /**
     * Nhà thầu tra cứu báo giá của chính mình bằng MST.
     *
     * Chỉ trả báo giá khớp CHÍNH XÁC mã số thuế trong đúng gói thầu → nhà thầu
     * không xem được của nhau. Không trả `ip_nop`, `nguoi_tao` (thông tin nội bộ).
     *
     * @return array ['success'=>bool, 'message'=>string, 'data'=>[...]]
     */
    public static function traCuuTheoMst(string $mst, int $goiThauId): array
    {
        $mst = trim($mst);
        if ($mst === '') {
            return ['success' => false, 'message' => 'Vui lòng nhập mã số thuế'];
        }
        if (!preg_match('/^\d{10}(-\d{3})?$/', $mst)) {
            return ['success' => false, 'message' => 'Mã số thuế không hợp lệ (10 số, hoặc dạng 0101234567-001)'];
        }
        if ($goiThauId <= 0) {
            return ['success' => false, 'message' => 'Thiếu mã gói thầu'];
        }

        $rows = BG_BaoGia_DAL::getByMstTrongGoiThau($mst, $goiThauId);
        if (empty($rows)) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy báo giá nào của mã số thuế này trong gói thầu.',
            ];
        }

        $out = [];
        foreach ($rows as $r) {
            $id = (int)$r['id'];
            $out[] = [
                'id'              => $id,
                'ten_cong_ty'     => $r['ten_cong_ty'],
                'ma_so_thue'      => $r['ma_so_thue'],
                'email'           => $r['email'],
                'dien_thoai'      => $r['dien_thoai'],
                'dia_chi'         => $r['dia_chi'],
                'hieu_luc_bao_gia'=> (int)$r['hieu_luc_bao_gia'],
                'ghi_chu'         => $r['ghi_chu'],
                'trang_thai'      => (int)$r['trang_thai'],
                'ten_trang_thai'  => BG_BaoGia_PUBLIC::tenTrangThai((int)$r['trang_thai']),
                'ngay_nop'        => $r['ngay_nop'],
                'ngay_xac_nhan'   => $r['ngay_xac_nhan'],
                'ly_do_tu_choi'   => $r['ly_do_tu_choi'],
                'tong_tien'       => (float)$r['tong_tien'],
                'so_dong_chao'    => (int)$r['so_dong_chao'],
                'chi_tiet'        => BG_BaoGia_DAL::getChiTiet($id),
            ];
        }

        return [
            'success' => true,
            'message' => 'Tìm thấy ' . count($out) . ' báo giá',
            'data'    => $out,
        ];
    }

    /**
     * Kiểm tra 1 báo giá có thuộc MST + gói thầu đang tra cứu không.
     * Dùng trước khi cho tải Excel ở cổng tra cứu (chặn dò id báo giá của người khác).
     */
    public static function baoGiaThuocMst(int $baoGiaId, string $mst, int $goiThauId): bool
    {
        if ($baoGiaId <= 0) return false;
        foreach (BG_BaoGia_DAL::getByMstTrongGoiThau($mst, $goiThauId) as $r) {
            if ((int)$r['id'] === $baoGiaId) return true;
        }
        return false;
    }
}
