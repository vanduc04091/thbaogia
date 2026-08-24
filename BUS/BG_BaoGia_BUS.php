<?php
require_once __DIR__ . '/../DAL/BG_BaoGia_DAL.php';
require_once __DIR__ . '/../DAL/BG_HangHoa_DAL.php';
require_once __DIR__ . '/../DAL/BG_GoiThau_DAL.php';
require_once __DIR__ . '/../DAL/DM_NhatKyHeThong_DAL.php';
require_once __DIR__ . '/../DAL/BG_File_DAL.php';
require_once __DIR__ . '/../DAL/BG_Catalog_DAL.php';
require_once __DIR__ . '/../PUBLIC/Common/ExcelHelper.php';
require_once __DIR__ . '/../PUBLIC/Common/WordHelper.php';
require_once __DIR__ . '/../PUBLIC/Common/WordTemplate.php';
require_once __DIR__ . '/BG_GoiThau_BUS.php';
require_once __DIR__ . '/BG_HangHoa_BUS.php';

class BG_BaoGia_BUS
{
    const MODULE_KEY = 'BG_BaoGia';
    const MODULE_LOG = 'BaoGia';

    // ===== Cột sheet "Mau1_DapUngKyThuat" (Phụ lục II — Mẫu 1) =====
    //   A: Mã HH | B: Tên HH mời | C: YCKT mời | D: YCKT chào giá | E: Điểm không đạt
    const M1_MA_HH           = 0;
    const M1_THONG_SO_CHAO   = 3;
    const M1_DIEM_KHONG_DAT  = 4;

    // ===== Cột sheet "Mau2_BangChaoGia" (Phụ lục II — Mẫu 2) =====
    //   A: TT | B: Mã HH | C: Tên HH mời | D: Tên TM | E: Model | F: Hãng SX
    //   G: Xuất xứ | H: SL | I: Quy cách | J: ĐVT | K: Đơn giá | L: Thành tiền
    //   M: Giá trúng thầu gần nhất | N: Tài liệu TC | O: Số TB mời thầu
    const M2_MA_HH           = 1;
    const M2_TEN_THUONG_MAI  = 3;
    const M2_MODEL           = 4;
    const M2_HANG_SX         = 5;
    const M2_XUAT_XU         = 6;
    const M2_QUY_CACH        = 8;
    const M2_DON_GIA         = 10;
    const M2_GIA_TRUNG_THAU  = 12;
    const M2_TAI_LIEU        = 13;

    /** Dòng bắt đầu dữ liệu trong file mẫu (1-3 tiêu đề, 4 header, 5 hướng dẫn) */
    const M1_DATA_ROW = 6;
    const M2_DATA_ROW = 5;

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

    /**
     * Liệt kê các trường THAY ĐỔI giữa bản cũ và bản mới, dạng
     * "nhãn: cũ → mới". Dùng ghi vào nhật ký để có tranh chấp còn truy được
     * ai sửa gì, chứ không chỉ biết "đã sửa".
     *
     * KHÔNG ghi mật khẩu / token / dữ liệu nhạy cảm (§3B.11).
     */
    private static function soSanhThayDoi(array $cu, array $moi, array $nhan): string
    {
        $doi = [];
        foreach ($nhan as $truong => $ten) {
            $a = trim((string)($cu[$truong] ?? ''));
            $b = trim((string)($moi[$truong] ?? ''));
            if ($a === $b) continue;
            $doi[] = $ten . ': ' . ($a === '' ? '(trống)' : $a) . ' → ' . ($b === '' ? '(trống)' : $b);
        }
        return implode('; ', $doi);
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
        // KHÔNG khóa theo "đã xác nhận" nữa: nhà thầu tự ký là đã thành
        // "Đã xác nhận", nhưng còn trong thời gian chào giá thì vẫn được sửa.
        // Chỉ chặn khi đã chốt hoàn thành (xem bên dưới).
        if ((int)($cu->da_hoan_thanh ?? 0) === 1) {
            return ['success' => false, 'message' => 'Báo giá đã hoàn thành — không chỉnh sửa được nữa'];
        }

        $err = self::validateThongTin($e);
        if ($err !== '') return ['success' => false, 'message' => $err];

        if (BG_BaoGia_DAL::existsMstTrongGoiThau((string)$e->ma_so_thue, (int)$cu->goi_thau_id, (int)$e->id)) {
            return ['success' => false, 'message' => 'Mã số thuế này đã có báo giá khác trong gói thầu'];
        }

        try {
            // Ghi lại GIÁ TRỊ CŨ → MỚI trước khi update, để nhật ký truy được
            $thayDoi = self::soSanhThayDoi(
                [
                    'ten_cong_ty' => $cu->ten_cong_ty, 'ma_so_thue' => $cu->ma_so_thue,
                    'email' => $cu->email, 'dien_thoai' => $cu->dien_thoai,
                    'dia_chi' => $cu->dia_chi, 'hieu_luc_bao_gia' => $cu->hieu_luc_bao_gia,
                ],
                [
                    'ten_cong_ty' => $e->ten_cong_ty, 'ma_so_thue' => $e->ma_so_thue,
                    'email' => $e->email, 'dien_thoai' => $e->dien_thoai,
                    'dia_chi' => $e->dia_chi, 'hieu_luc_bao_gia' => $e->hieu_luc_bao_gia,
                ],
                [
                    'ten_cong_ty' => 'Tên công ty', 'ma_so_thue' => 'MST',
                    'email' => 'Email', 'dien_thoai' => 'Điện thoại',
                    'dia_chi' => 'Địa chỉ', 'hieu_luc_bao_gia' => 'Hiệu lực',
                ]
            );

            $e->nguoi_cap_nhat = $u;
            BG_BaoGia_DAL::update($e);
            DM_NhatKyHeThong_DAL::log(
                $u, self::MODULE_LOG,
                "Cập nhật thông tin báo giá: {$e->ten_cong_ty}"
                . ($thayDoi !== '' ? ' | ' . $thayDoi : ' (không có gì đổi)'),
                'bg_bao_gia', $e->id
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
        if ((int)($bg->da_hoan_thanh ?? 0) === 1) {
            return ['success' => false, 'message' => 'Báo giá đã hoàn thành — không chỉnh sửa được nữa'];
        }

        $hh = BG_HangHoa_DAL::getById($hangHoaId);
        if (!$hh || $hh->da_xoa === 1) return ['success' => false, 'message' => 'Không tìm thấy hàng hóa'];
        if ((int)$hh->goi_thau_id !== (int)$bg->goi_thau_id) {
            return ['success' => false, 'message' => 'Hàng hóa không thuộc gói thầu của báo giá này'];
        }

        $donGia = ExcelHelper::toNumber($input['don_gia'] ?? 0);
        if ($donGia < 0) return ['success' => false, 'message' => 'Đơn giá không được âm'];

        $ct = new BG_BaoGiaChiTiet_PUBLIC();
        $ct->bao_gia_id          = $baoGiaId;
        $ct->hang_hoa_id         = $hangHoaId;
        // ===== Mẫu 2: Bảng chào giá =====
        $ct->ten_thuong_mai        = self::nullIfEmpty($input['ten_thuong_mai'] ?? '', 1000);
        $ct->model                 = self::nullIfEmpty($input['model'] ?? '', 500);
        $ct->hang_san_xuat         = self::nullIfEmpty($input['hang_san_xuat'] ?? '', 500);
        $ct->xuat_xu               = self::nullIfEmpty($input['xuat_xu'] ?? '', 500);
        $ct->quy_cach              = self::nullIfEmpty($input['quy_cach'] ?? '', 500);
        $ct->don_gia               = $donGia;
        // Thành tiền LUÔN tính ở server = đơn giá × số lượng của bên mời (§10.2),
        // không nhận giá trị client gửi lên.
        $ct->thanh_tien            = round($donGia * (float)$hh->so_luong, 2);
        $ct->don_gia_trung_thau    = ExcelHelper::toNumber($input['don_gia_trung_thau'] ?? 0);
        $ct->tai_lieu_tham_chieu   = self::nullIfEmpty($input['tai_lieu_tham_chieu'] ?? '');
        // ===== Mẫu 1: Bảng đáp ứng kỹ thuật =====
        $ct->thong_so_chao_gia     = self::nullIfEmpty($input['thong_so_chao_gia'] ?? '');
        $ct->diem_khong_dat        = self::nullIfEmpty($input['diem_khong_dat'] ?? '');

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
        if ((int)($bg->da_hoan_thanh ?? 0) === 1) {
            return ['success' => false, 'message' => 'Báo giá đã hoàn thành — không nộp lại được'];
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
    /**
     * Nhà thầu import file Excel đã điền (file mẫu 2 sheet do hệ thống sinh).
     *
     * Khớp dòng theo **Mã HH** — không khớp theo tên hàng hóa nữa vì tên dài,
     * dễ bị sửa/xuống dòng khi copy, còn Mã HH là duy nhất trong 1 gói thầu.
     *
     * Đọc CẢ HAI sheet:
     *   Mau1_DapUngKyThuat → thong_so_chao_gia, diem_khong_dat
     *   Mau2_BangChaoGia   → tên TM, model, hãng SX, xuất xứ, quy cách, đơn giá...
     *
     * Sheet nào thiếu thì bỏ qua sheet đó (nhà thầu có thể nộp dần).
     */
    public static function importFileBaoGia(int $baoGiaId, string $filePath, int $u): array
    {
        $bg = BG_BaoGia_DAL::getById($baoGiaId);
        if (!$bg || $bg->da_xoa === 1) return ['success' => false, 'message' => 'Không tìm thấy báo giá'];
        if ((int)($bg->da_hoan_thanh ?? 0) === 1) {
            return ['success' => false, 'message' => 'Báo giá đã hoàn thành — không import được nữa'];
        }

        $gt = BG_GoiThau_DAL::getById((int)$bg->goi_thau_id);
        if ($gt) {
            $conNhan = BG_GoiThau_BUS::kiemTraConNhan($gt);
            if (!$conNhan['ok']) return ['success' => false, 'message' => $conNhan['message']];
        }

        $hangHoa = BG_HangHoa_DAL::getByGoiThau((int)$bg->goi_thau_id);
        if (empty($hangHoa)) return ['success' => false, 'message' => 'Gói thầu chưa có danh mục hàng hóa'];

        // Map Mã HH (chuẩn hóa hoa/thường) → thông tin hàng hóa
        $theoMa = [];
        foreach ($hangHoa as $hh) {
            $ma = mb_strtoupper(trim((string)($hh['ma_hh'] ?? '')));
            if ($ma !== '') $theoMa[$ma] = $hh;
        }

        $tenSheet = ExcelHelper::sheetNames($filePath);
        $canhBao  = [];
        $duLieu   = [];   // ma_hh => mảng giá trị gom từ 2 sheet

        // ---------- Sheet 1: Bảng đáp ứng kỹ thuật ----------
        if (in_array('Mau1_DapUngKyThuat', $tenSheet, true)) {
            $rows = ExcelHelper::readSheet($filePath, 'Mau1_DapUngKyThuat');
            foreach ($rows as $rowNo => $cells) {
                if ($rowNo < self::M1_DATA_ROW) continue;
                $ma = mb_strtoupper(ExcelHelper::toText($cells[self::M1_MA_HH] ?? '', 50));
                if ($ma === '') continue;
                if (!isset($theoMa[$ma])) {
                    $canhBao[] = "Mẫu 1 dòng {$rowNo}: Mã HH \"{$ma}\" không có trong gói thầu — bỏ qua";
                    continue;
                }
                $duLieu[$ma]['thong_so_chao_gia'] = ExcelHelper::toText($cells[self::M1_THONG_SO_CHAO] ?? '');
                $duLieu[$ma]['diem_khong_dat']    = ExcelHelper::toText($cells[self::M1_DIEM_KHONG_DAT] ?? '');
            }
        }
        // File mẫu nay tách riêng từng mẫu nên thiếu sheet kia là BÌNH THƯỜNG,
        // không cảnh báo. Chỉ báo lỗi khi cả 2 sheet đều không có (kiểm ở dưới).

        // ---------- Sheet 2: Bảng chào giá ----------
        if (in_array('Mau2_BangChaoGia', $tenSheet, true)) {
            $rows = ExcelHelper::readSheet($filePath, 'Mau2_BangChaoGia');
            foreach ($rows as $rowNo => $cells) {
                if ($rowNo < self::M2_DATA_ROW) continue;
                $ma = mb_strtoupper(ExcelHelper::toText($cells[self::M2_MA_HH] ?? '', 50));
                if ($ma === '') continue;
                if (!isset($theoMa[$ma])) {
                    $canhBao[] = "Mẫu 2 dòng {$rowNo}: Mã HH \"{$ma}\" không có trong gói thầu — bỏ qua";
                    continue;
                }

                $donGia = ExcelHelper::toNumber($cells[self::M2_DON_GIA] ?? 0);
                if ($donGia < 0) {
                    $canhBao[] = "Mẫu 2 dòng {$rowNo}: đơn giá âm → đặt về 0";
                    $donGia = 0;
                }

                $duLieu[$ma]['ten_thuong_mai']        = ExcelHelper::toText($cells[self::M2_TEN_THUONG_MAI] ?? '', 1000);
                $duLieu[$ma]['model']                 = ExcelHelper::toText($cells[self::M2_MODEL] ?? '', 500);
                $duLieu[$ma]['hang_san_xuat']         = ExcelHelper::toText($cells[self::M2_HANG_SX] ?? '', 500);
                $duLieu[$ma]['xuat_xu']               = ExcelHelper::toText($cells[self::M2_XUAT_XU] ?? '', 500);
                $duLieu[$ma]['quy_cach']              = ExcelHelper::toText($cells[self::M2_QUY_CACH] ?? '', 500);
                $duLieu[$ma]['don_gia']               = $donGia;
                $duLieu[$ma]['don_gia_trung_thau']    = ExcelHelper::toNumber($cells[self::M2_GIA_TRUNG_THAU] ?? 0);
                $duLieu[$ma]['tai_lieu_tham_chieu']   = ExcelHelper::toText($cells[self::M2_TAI_LIEU] ?? '');
            }
        }

        if (empty($duLieu)) {
            return [
                'success' => false,
                'message' => 'Không đọc được dòng nào khớp Mã HH của gói thầu. '
                           . 'Hãy tải lại file mẫu và giữ nguyên cột Mã HH.',
            ];
        }

        // ---------- Ghi vào DB ----------
        try {
            Database::beginTransaction();

            $soDong = 0;
            foreach ($duLieu as $ma => $v) {
                $hh = $theoMa[$ma];
                $soLuong = (float)$hh['so_luong'];
                $donGia  = (float)($v['don_gia'] ?? 0);

                $ct = new BG_BaoGiaChiTiet_PUBLIC();
                $ct->bao_gia_id            = $baoGiaId;
                $ct->hang_hoa_id           = (int)$hh['id'];
                $ct->thong_so_chao_gia     = self::nullIfEmpty($v['thong_so_chao_gia'] ?? '');
                $ct->diem_khong_dat        = self::nullIfEmpty($v['diem_khong_dat'] ?? '');
                $ct->ten_thuong_mai        = self::nullIfEmpty($v['ten_thuong_mai'] ?? '', 1000);
                $ct->model                 = self::nullIfEmpty($v['model'] ?? '', 500);
                $ct->hang_san_xuat         = self::nullIfEmpty($v['hang_san_xuat'] ?? '', 500);
                $ct->xuat_xu               = self::nullIfEmpty($v['xuat_xu'] ?? '', 500);
                $ct->quy_cach              = self::nullIfEmpty($v['quy_cach'] ?? '', 500);
                $ct->don_gia               = $donGia;
                // Thành tiền LUÔN tính ở server, không tin cột Thành tiền trong file
                $ct->thanh_tien            = round($donGia * $soLuong, 2);
                $ct->don_gia_trung_thau    = (float)($v['don_gia_trung_thau'] ?? 0);
                $ct->tai_lieu_tham_chieu   = self::nullIfEmpty($v['tai_lieu_tham_chieu'] ?? '');

                BG_BaoGia_DAL::upsertChiTiet($ct);
                $soDong++;
            }

            BG_BaoGia_DAL::updateTongTien($baoGiaId);
            Database::commit();

            DM_NhatKyHeThong_DAL::log(
                $u, self::MODULE_LOG,
                "Nhà thầu import file báo giá: {$bg->ten_cong_ty} — {$soDong} dòng",
                'bg_bao_gia', $baoGiaId
            );

            return [
                'success' => true,
                'message' => "Đã import {$soDong} dòng",
                'data' => ['tong_dong' => $soDong, 'canh_bao' => $canhBao],
            ];
        } catch (Throwable $ex) {
            Database::rollBack();
            return ['success' => false, 'message' => 'Lỗi: ' . $ex->getMessage()];
        }
    }


    // =====================================================================
    // BÊN MỜI: XÁC NHẬN BẢN GIẤY
    // =====================================================================

    /** Tích xác nhận đã nhận bản giấy → báo giá được đưa vào tổng hợp */
    public static function xacNhan(int $id, int $u): array
    {
        $bg = BG_BaoGia_DAL::getById($id);
        if (!$bg || $bg->da_xoa === 1) return ['success' => false, 'message' => 'Không tìm thấy báo giá'];

        $chot = BG_GoiThau_BUS::kiemTraChuaChotSo((int)$bg->goi_thau_id);
        if (!$chot['ok']) return ['success' => false, 'message' => $chot['message']];
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

        $chot = BG_GoiThau_BUS::kiemTraChuaChotSo((int)$bg->goi_thau_id);
        if (!$chot['ok']) return ['success' => false, 'message' => $chot['message']];

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

    public static function getPaged(
        int $page,
        int $pageSize,
        int $goiThauId = 0,
        string $search = '',
        int $trangThai = -1,
        int $daXoa = 0,
        int $coBanKy = -1
    ): array {
        return BG_BaoGia_DAL::getPaged($page, $pageSize, $goiThauId, $search, $trangThai, $daXoa, $coBanKy);
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
                // --- Bên mời điền sẵn (Phụ lục III) ---
                'hang_hoa_id'           => $id,
                'ma_hh'                 => $hh['ma_hh'],
                'ten_hang_hoa'          => $hh['ten_hang_hoa'],
                'thong_so_ky_thuat'     => $hh['thong_so_ky_thuat'],
                'dvt'                   => $hh['dvt'],
                'so_luong'              => (float)$hh['so_luong'],
                // --- Mẫu 1: Bảng đáp ứng kỹ thuật ---
                'thong_so_chao_gia'     => $ct['thong_so_chao_gia'] ?? '',
                'diem_khong_dat'        => $ct['diem_khong_dat'] ?? '',
                // --- Mẫu 2: Bảng chào giá ---
                'ten_thuong_mai'        => $ct['ten_thuong_mai'] ?? '',
                'model'                 => $ct['model'] ?? '',
                'hang_san_xuat'         => $ct['hang_san_xuat'] ?? '',
                'xuat_xu'               => $ct['xuat_xu'] ?? '',
                'quy_cach'              => $ct['quy_cach'] ?? '',
                'don_gia'               => (float)($ct['don_gia'] ?? 0),
                'thanh_tien'            => (float)($ct['thanh_tien'] ?? 0),
                'don_gia_trung_thau'    => (float)($ct['don_gia_trung_thau'] ?? 0),
                'tai_lieu_tham_chieu'   => $ct['tai_lieu_tham_chieu'] ?? '',
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
                // Bản có dấu + chữ ký: chỉ trả tên gốc để hiển thị, KHÔNG trả
                // tên file thật trên đĩa (tránh lộ đường dẫn lưu trữ)
                // ?? null: phòng trường hợp truy vấn nguồn chưa JOIN bg_file —
                // thiếu key sẽ in Warning ra giữa JSON làm hỏng response.
                'ten_file_goc'       => $r['ten_file_goc'] ?? null,
                'ngay_upload_ban_ky' => $r['ngay_upload_ban_ky'] ?? null,
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

    // =====================================================================
    // BẢN BÁO GIÁ CÓ DẤU & CHỮ KÝ
    // =====================================================================

    /** Dung lượng tối đa cho bản ký (20MB — ảnh chụp/scan thường lớn) */
    const BAN_KY_MAX_SIZE = 20971520;

    // Đuôi/MIME cho phép khai báo ở BG_File_PUBLIC (dùng chung cho mọi loại file)

    /** Thư mục lưu bản ký */
    public static function thuMucBanKy(): string
    {
        $dir = rtrim(AppConfig::UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . 'ban_ky';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Không tạo được thư mục lưu file');
        }
        return $dir;
    }

    /**
     * Sinh tên file bản ký theo quy tắc: <mst>_<slug-goi-thau>.<ext>
     *
     * VD: 0101234567_mua-vat-tu-tieu-hao-phau-thuat-cot-song.pdf
     *
     * Nhìn tên là biết ngay của công ty nào, gói thầu nào — không phải tra DB.
     * Vẫn an toàn: MST chỉ chứa số và dấu '-', slug chỉ chứa [a-z0-9-], nên
     * không thể chèn '/' hay '..' để thoát khỏi thư mục.
     *
     * Trùng tên (1 MST nộp lại, hoặc 2 gói cùng slug) → thêm hậu tố -2, -3...
     *
     * @param string $mst        Mã số thuế nhà thầu
     * @param string $tenGoiThau Tên gói thầu (dùng làm slug)
     * @param string $soThongBao Số thông báo — dự phòng khi tên gói rỗng
     * @param string $ext        Đuôi file đã kiểm tra (pdf/jpg/jpeg/png)
     * @param string $dir        Thư mục lưu, để kiểm tra trùng
     * @param array  $daDung     Tên đã dùng trong cùng lượt xử lý (cho migration)
     * @param string $tenHienTai  Tên file bản ghi này ĐANG dùng — bỏ qua khi kiểm
     *                            trùng, nếu không migration chạy lần 2 sẽ thấy
     *                            chính nó trên đĩa rồi cứ thêm -2, -3... mãi.
     */
    public static function tenFileBanKy(
        string $mst,
        string $tenGoiThau,
        string $soThongBao,
        string $ext,
        string $dir,
        array $daDung = [],
        string $tenHienTai = ''
    ): string {
        // MST: chỉ giữ số và dấu '-' (dạng 0101234567-001)
        $mstSach = preg_replace('/[^0-9-]/', '', trim($mst));
        if ($mstSach === '') $mstSach = 'khong-mst';

        $slug = Helper::slug($tenGoiThau, 60);
        if ($slug === '') $slug = Helper::slug($soThongBao, 30);   // dự phòng
        if ($slug === '') $slug = 'goi-thau';

        $goc = $mstSach . '_' . $slug;
        $ten = $goc . '.' . $ext;

        // Tên đang dùng của chính bản ghi này thì coi như hợp lệ, không cần đổi
        if ($tenHienTai !== '' && $ten === $tenHienTai) {
            return $ten;
        }

        // Tránh ghi đè file của lần nộp trước
        $i = 1;
        while (isset($daDung[$ten]) || is_file($dir . DIRECTORY_SEPARATOR . $ten)) {
            $i++;
            $ten = $goc . '-' . $i . '.' . $ext;
            if ($i > 500) {   // chặn vòng lặp vô hạn
                $ten = $goc . '-' . Helper::randomString(8) . '.' . $ext;
                break;
            }
        }
        return $ten;
    }

    /**
     * Nhà thầu upload bản báo giá có dấu + chữ ký.
     *
     * Upload thành công thì báo giá TỰ CHUYỂN sang "Đã xác nhận" — bản ký chính
     * là bằng chứng thay cho việc bên mời tích tay khi nhận bản giấy.
     *
     * Chỉ cho upload khi báo giá ĐÃ NỘP (có ngay_nop) và có ít nhất 1 dòng giá,
     * tránh trường hợp lách bằng cách upload file rồi thành "đã xác nhận" mà
     * chưa hề chào giá.
     *
     * @param array $file phần tử của $_FILES
     */
    public static function uploadBanKy(int $baoGiaId, array $file, int $u): array
    {
        $bg = BG_BaoGia_DAL::getById($baoGiaId);
        if (!$bg || (int)$bg->da_xoa === 1) {
            return ['success' => false, 'message' => 'Không tìm thấy báo giá'];
        }
        if ((int)($bg->da_hoan_thanh ?? 0) === 1) {
            return ['success' => false, 'message' => 'Báo giá đã hoàn thành — không chỉnh sửa được nữa'];
        }
        if (empty($bg->ngay_nop)) {
            return ['success' => false, 'message' => 'Chưa nộp báo giá — hãy nộp báo giá trước khi tải bản ký lên'];
        }
        if ((int)$bg->so_dong_chao === 0) {
            return ['success' => false, 'message' => 'Báo giá chưa có dòng nào có đơn giá'];
        }

        // --- Kiểm tra file (§3B.9) ---
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $map = [
                UPLOAD_ERR_INI_SIZE  => 'File vượt quá giới hạn của server',
                UPLOAD_ERR_FORM_SIZE => 'File vượt quá giới hạn cho phép',
                UPLOAD_ERR_PARTIAL   => 'File tải lên chưa hoàn tất, hãy thử lại',
                UPLOAD_ERR_NO_FILE   => 'Chưa chọn file',
            ];
            return ['success' => false, 'message' => $map[$file['error']] ?? 'Lỗi tải file'];
        }
        if (!is_uploaded_file($file['tmp_name']) || (int)$file['size'] <= 0) {
            return ['success' => false, 'message' => 'File không hợp lệ hoặc rỗng'];
        }
        if ((int)$file['size'] > self::BAN_KY_MAX_SIZE) {
            return ['success' => false, 'message' => 'File tối đa ' . round(self::BAN_KY_MAX_SIZE / 1048576) . 'MB'];
        }

        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, BG_File_PUBLIC::EXT_CHO_PHEP, true)) {
            return ['success' => false, 'message' => 'Chỉ nhận file PDF hoặc ảnh (JPG, PNG)'];
        }

        // MIME thật, không tin phần mở rộng lẫn $_FILES['type']
        $mime = null;
        if (function_exists('finfo_open')) {
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($fi, $file['tmp_name']);
            finfo_close($fi);
            if (!in_array($mime, BG_File_PUBLIC::MIME_CHO_PHEP, true)) {
                return ['success' => false, 'message' => 'Nội dung file không phải PDF/ảnh hợp lệ'];
            }
            // Đuôi phải khớp nội dung thật (chặn đổi tên .php thành .pdf)
            if (!in_array($mime, BG_File_PUBLIC::EXT_MIME[$ext] ?? [], true)) {
                return ['success' => false, 'message' => 'Đuôi file không khớp nội dung thật của file'];
            }
        }

        try {
            $dir = self::thuMucBanKy();

            // Đổi tên khi lưu — KHÔNG giữ tên gốc từ user (§3B.9).
            // Quy tắc: <mst>_<slug-goi-thau>.<ext> để nhìn tên là biết của ai, gói nào.
            $gt = BG_GoiThau_DAL::getById((int)$bg->goi_thau_id);
            $tenLuu = self::tenFileBanKy(
                (string)$bg->ma_so_thue,
                (string)($gt->ten_goi_thau ?? ''),
                (string)($gt->so_thong_bao ?? ''),
                $ext,
                $dir
            );
            $dich = $dir . DIRECTORY_SEPARATOR . $tenLuu;

            if (!move_uploaded_file($file['tmp_name'], $dich)) {
                return ['success' => false, 'message' => 'Không lưu được file tải lên'];
            }

            // Ghi vào bảng file rồi gán id sang báo giá — 2 bảng nên bọc transaction (§3.7)
            $fileCuId = (int)($bg->file_ban_ky_id ?? 0);
            $fileCu   = $fileCuId > 0 ? BG_File_DAL::getById($fileCuId) : null;

            try {
                Database::beginTransaction();

                $ef = new BG_File_PUBLIC();
                $ef->ten_file     = $tenLuu;
                $ef->ten_file_goc = ExcelHelper::toText($file['name'], 255);
                $ef->duong_dan    = 'ban_ky';
                $ef->loai_file    = $ext;
                $ef->mime_type    = $mime;
                $ef->kich_thuoc   = (int)$file['size'];
                $ef->nhom_file    = BG_File_PUBLIC::NHOM_BAN_KY;
                $ef->nguoi_tao    = $u;
                $fileId = BG_File_DAL::insert($ef);

                BG_BaoGia_DAL::updateBanKy($baoGiaId, $fileId);

                // Upload đè: bản ghi file cũ chuyển sang đã xóa
                if ($fileCuId > 0) BG_File_DAL::softDelete($fileCuId, $u);

                Database::commit();
            } catch (Throwable $exDb) {
                Database::rollBack();
                @unlink($dich);   // DB hỏng thì bỏ luôn file vừa lưu, tránh mồ côi
                return ['success' => false, 'message' => 'Lỗi: ' . $exDb->getMessage()];
            }

            // Xóa file cũ trên đĩa SAU khi DB đã commit
            if ($fileCu && $fileCu->ten_file !== '' && $fileCu->ten_file !== $tenLuu) {
                $duongDanCu = $dir . DIRECTORY_SEPARATOR . basename($fileCu->ten_file);
                if (is_file($duongDanCu)) @unlink($duongDanCu);
            }

            DM_NhatKyHeThong_DAL::log(
                $u, self::MODULE_LOG,
                "Nhà thầu tải bản ký: {$bg->ten_cong_ty} (MST {$bg->ma_so_thue})",
                'bg_bao_gia', $baoGiaId
            );

            return [
                'success' => true,
                'message' => 'Đã tải lên bản báo giá có dấu và chữ ký. '
                           . 'Hãy hoàn tất Bước 5 rồi bấm "Hoàn thành báo giá" để nộp chính thức.',
                'data' => [
                    'ten_file_goc' => ExcelHelper::toText($file['name'], 255),
                ],
            ];
        } catch (Throwable $ex) {
            return ['success' => false, 'message' => 'Lỗi: ' . $ex->getMessage()];
        }
    }

    /**
     * Đường dẫn tuyệt đối tới file bản ký của 1 báo giá.
     * Trả '' nếu không có file hoặc file đã mất.
     *
     * Dùng basename() để chặn path traversal nếu DB bị chèn giá trị lạ.
     */
    public static function duongDanBanKy(int $baoGiaId): string
    {
        $bg = BG_BaoGia_DAL::getById($baoGiaId);
        if (!$bg || empty($bg->file_ban_ky_id)) return '';

        $f = BG_File_DAL::getById((int)$bg->file_ban_ky_id);
        if (!$f || $f->ten_file === '') return '';

        $p = self::thuMucBanKy() . DIRECTORY_SEPARATOR . basename($f->ten_file);
        return is_file($p) ? $p : '';
    }

    /** Bản ghi file bản ký của 1 báo giá (null nếu chưa có) */
    public static function fileBanKy(int $baoGiaId): ?BG_File_PUBLIC
    {
        $bg = BG_BaoGia_DAL::getById($baoGiaId);
        if (!$bg || empty($bg->file_ban_ky_id)) return null;
        return BG_File_DAL::getById((int)$bg->file_ban_ky_id);
    }

    /**
     * Tra cứu TẤT CẢ báo giá của 1 MST — mọi gói thầu, nhóm theo từng gói.
     *
     * Nhà thầu chào nhiều gói cùng lúc nên cần xem hết ở một chỗ.
     * Chỉ trả dữ liệu của đúng MST nhập vào → không lộ công ty khác.
     *
     * @return array ['success'=>bool, 'message'=>string, 'data'=>['tong_ket'=>..., 'nhom'=>[...]]]
     */
    public static function traCuuTatCaTheoMst(string $mst): array
    {
        $mst = trim($mst);
        if ($mst === '') {
            return ['success' => false, 'message' => 'Vui lòng nhập mã số thuế'];
        }
        if (!preg_match('/^\d{10}(-\d{3})?$/', $mst)) {
            return ['success' => false, 'message' => 'Mã số thuế không hợp lệ (10 số, hoặc dạng 0101234567-001)'];
        }

        $rows = BG_BaoGia_DAL::getAllByMst($mst);
        if (empty($rows)) {
            return ['success' => false, 'message' => 'Không tìm thấy báo giá nào của mã số thuế này.'];
        }

        $nhom = [];
        $tenCongTy = '';
        $soDaXacNhan = 0;
        $soChoXacNhan = 0;
        $tongTien = 0.0;

        foreach ($rows as $r) {
            $gtId = (int)$r['goi_thau_id'];
            $tt = (int)$r['trang_thai'];

            if ($tenCongTy === '') $tenCongTy = (string)$r['ten_cong_ty'];
            if ($tt === BG_BaoGia_PUBLIC::TT_DA_XAC_NHAN) $soDaXacNhan++;
            if ($tt === BG_BaoGia_PUBLIC::TT_CHO_XAC_NHAN) $soChoXacNhan++;
            $tongTien += (float)$r['tong_tien'];

            // Còn trong thời gian chào giá thì mới cho sửa/nộp lại
            $ttBaoGia = BG_GoiThau_PUBLIC::tinhTrangThaiBaoGia(
                (int)$r['gt_trang_thai'],
                $r['thoi_gian_mo_bao_gia'],
                $r['thoi_gian_dong_bao_gia']
            );

            if (!isset($nhom[$gtId])) {
                $nhom[$gtId] = [
                    'goi_thau_id'            => $gtId,
                    'so_thong_bao'           => $r['so_thong_bao'],
                    'ten_goi_thau'           => $r['ten_goi_thau'],
                    'thoi_gian_dong_bao_gia' => $r['thoi_gian_dong_bao_gia'],
                    'trang_thai_bao_gia'     => $ttBaoGia,
                    'ten_trang_thai_bao_gia' => BG_GoiThau_PUBLIC::tenTrangThaiBaoGia($ttBaoGia),
                    // Link vào cổng chào giá của gói đó (nhà thầu tự chuyển gói)
                    'url_portal'             => BG_GoiThau_BUS::urlPortal((string)$r['gt_token']),
                    'bao_gia'                => [],
                ];
            }

            $nhom[$gtId]['bao_gia'][] = [
                'id'                 => (int)$r['id'],
                'ten_cong_ty'        => $r['ten_cong_ty'],
                'ma_so_thue'         => $r['ma_so_thue'],
                'email'              => $r['email'],
                'dien_thoai'         => $r['dien_thoai'],
                'hieu_luc_bao_gia'   => (int)$r['hieu_luc_bao_gia'],
                'trang_thai'         => $tt,
                'ten_trang_thai'     => BG_BaoGia_PUBLIC::tenTrangThai($tt),
                'ngay_nop'           => $r['ngay_nop'],
                'ngay_xac_nhan'      => $r['ngay_xac_nhan'],
                'ly_do_tu_choi'      => $r['ly_do_tu_choi'],
                'tong_tien'          => (float)$r['tong_tien'],
                'so_dong_chao'       => (int)$r['so_dong_chao'],
                // ?? null: phòng trường hợp truy vấn nguồn chưa JOIN bg_file —
                // thiếu key sẽ in Warning ra giữa JSON làm hỏng response.
                'ten_file_goc'       => $r['ten_file_goc'] ?? null,
                'ngay_upload_ban_ky' => $r['ngay_upload_ban_ky'] ?? null,
                // Đã chốt hoàn thành thì trang tra cứu chỉ cho XEM, không cho tải đè
                'da_hoan_thanh'      => (int)($r['da_hoan_thanh'] ?? 0),
            ];
        }

        return [
            'success' => true,
            'message' => 'Tìm thấy ' . count($rows) . ' báo giá ở ' . count($nhom) . ' gói thầu',
            'data' => [
                'ma_so_thue' => $mst,
                'tong_ket'   => [
                    'ten_cong_ty'    => $tenCongTy,
                    'so_bao_gia'     => count($rows),
                    'so_goi_thau'    => count($nhom),
                    'da_xac_nhan'    => $soDaXacNhan,
                    'cho_xac_nhan'   => $soChoXacNhan,
                    'tong_tien'      => $tongTien,
                ],
                'nhom' => array_values($nhom),
            ],
        ];
    }

    /** 1 báo giá có đúng của MST này không (mọi gói thầu) */
    public static function baoGiaCuaMst(int $baoGiaId, string $mst): bool
    {
        return BG_BaoGia_DAL::baoGiaCuaMst($baoGiaId, $mst);
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

    /**
     * Xuat file Word BAO GIA de nha thau in ra ky + dong dau.
     *
     * Noi dung KHONG con hardcode trong code nua ma lay tu file mau
     * `MPS/bao_gia.docx`. Nguoi dung tu mo file do bang Word de sua font,
     * co chu, can le, them logo... — code chi thay cac {{KEY}} bang du lieu.
     *
     * Xem danh sach Key: php database/tao_mau_word.php
     */
    public static function xuatWordBanKy(int $baoGiaId): string
    {
        $bg = BG_BaoGia_DAL::getById($baoGiaId);
        if (!$bg) throw new RuntimeException('Không tìm thấy báo giá');

        $gt = BG_GoiThau_DAL::getById((int)$bg->goi_thau_id);
        if (!$gt) throw new RuntimeException('Không tìm thấy gói thầu');

        $dong = self::getBangChaoGia($baoGiaId);

        $ten  = trim((string)$bg->ten_cong_ty);
        $mst  = trim((string)($bg->ma_so_thue ?? ''));
        $dc   = trim((string)($bg->dia_chi ?? ''));
        $dt   = trim((string)($bg->dien_thoai ?? ''));
        $mail = trim((string)($bg->email ?? ''));

        // Dong tu gioi thieu - thay cho phan "....[ghi ten, dia chi...]" trong mau
        $gioiThieu = $ten;
        if ($mst !== '')  $gioiThieu .= ', MST: ' . $mst;
        if ($dc !== '')   $gioiThieu .= ', địa chỉ: ' . $dc;
        if ($dt !== '')   $gioiThieu .= ', ĐT: ' . $dt;
        if ($mail !== '') $gioiThieu .= ', Email: ' . $mail;

        // ----- Bang chao gia (Mau 2) + bang dap ung ky thuat (Mau 1) -----
        $chaoGia = [];
        $dapUng  = [];
        $tong    = 0.0;
        $stt     = 0;
        $sttKt = 0;   // STT rieng cho bang dap ung ky thuat
        foreach ($dong as $d) {
            $coGia = (float)$d['don_gia'] > 0;
            $coKyThuat = trim((string)$d['thong_so_chao_gia']) !== ''
                      || trim((string)$d['diem_khong_dat']) !== '';

            // Bang DAP UNG KY THUAT: giu ca hang da khai ky thuat nhung chua
            // chao gia — de ben moi biet cong ty co dap ung duoc mat hang do khong.
            if ($coGia || $coKyThuat) {
                $sttKt++;
                $dapUng[] = [
                    'STT'                => (string)$sttKt,
                    'MA_HH'              => (string)$d['ma_hh'],
                    'TEN_HANG_HOA'       => (string)$d['ten_hang_hoa'],
                    'YEU_CAU_KY_THUAT'   => (string)$d['thong_so_ky_thuat'],
                    'THONG_SO_CHAO_GIA'  => (string)$d['thong_so_chao_gia'],
                    'DIEM_KHONG_DAT'     => (string)$d['diem_khong_dat'],
                ];
            }

            // Bang CHAO GIA: CHI hang thuc su co don gia.
            // In ca hang khong chao vua thua giay vua de hieu nham la chao gia 0.
            if (!$coGia) continue;

            $stt++;
            $tong += (float)$d['thanh_tien'];

            $chaoGia[] = [
                'STT'                  => (string)$stt,
                'MA_HH'                => (string)$d['ma_hh'],
                'TEN_HANG_HOA'         => (string)$d['ten_hang_hoa'],
                'TEN_THUONG_MAI'       => (string)$d['ten_thuong_mai'],
                'MODEL'                => (string)$d['model'],
                'HANG_SAN_XUAT'        => (string)$d['hang_san_xuat'],
                'XUAT_XU'              => (string)$d['xuat_xu'],
                'SO_LUONG'             => self::soVN((float)$d['so_luong']),
                'QUY_CACH'             => (string)$d['quy_cach'],
                'DVT'                  => (string)$d['dvt'],
                'DON_GIA'              => self::soVN((float)$d['don_gia']),
                'THANH_TIEN'           => self::soVN((float)$d['thanh_tien']),
                'DON_GIA_TRUNG_THAU'   => self::soVN((float)$d['don_gia_trung_thau']),
                'TAI_LIEU_THAM_CHIEU'  => (string)$d['tai_lieu_tham_chieu'],
            ];

        }

        $hieuLuc = (int)$bg->hieu_luc_bao_gia > 0 ? (int)$bg->hieu_luc_bao_gia : 180;

        $data = [
            'GIOI_THIEU'   => $gioiThieu,
            'TEN_CONG_TY'  => $ten,
            'MST'          => $mst,
            'DIA_CHI'      => $dc,
            'DIEN_THOAI'   => $dt,
            'EMAIL'        => $mail,
            'SO_THONG_BAO' => (string)$gt->so_thong_bao,
            'TEN_GOI_THAU' => (string)$gt->ten_goi_thau,
            'HIEU_LUC'     => (string)$hieuLuc,
            'NGAY_NOP'     => $bg->ngay_nop ? date('d/m/Y', strtotime($bg->ngay_nop)) : '…/…/……',
            'TONG_TIEN'    => self::soVN($tong),
            'NGAY_IN'      => date('d/m/Y'),
        ];

        $path = BG_HangHoa_BUS::tempDir() . '/BaoGia_'
              . preg_replace('/[^0-9A-Za-z]/', '', $mst !== '' ? $mst : (string)$baoGiaId)
              . '_' . preg_replace('/[^0-9A-Za-z]/', '_', $gt->so_thong_bao)
              . '_' . date('Ymd_His') . '.docx';

        return WordTemplate::render('bao_gia.docx', $path, $data, [
            'CHAO_GIA' => $chaoGia,
            'DAP_UNG'  => $dapUng,
        ]);
    }

    /** So kieu Viet Nam: 1.234.567 - tra chuoi rong neu <= 0 */
    private static function soVN(float $n): string
    {
        if ($n <= 0) return '';
        return abs($n - round($n)) < 0.005
            ? number_format($n, 0, ',', '.')
            : number_format($n, 2, ',', '.');
    }


    // =====================================================================
    //  LUU HANG LOAT (Buoc 2 + Buoc 3)
    // =====================================================================

    /**
     * Luu NHIEU dong cung luc — dung cho nut "Luu va tiep tuc" o Buoc 2/3.
     *
     * Truoc day moi dong phai bam Luu rieng; gio nha thau dien het roi bam
     * 1 lan. Bao het trong 1 transaction: hoac an toan bo, hoac khong dong nao
     * — tranh tinh trang luu duoc nua chung roi bao loi.
     *
     * @param array $dong Mang cac dong, moi dong co 'hang_hoa_id' + cac truong
     */
    public static function luuNhieuDong(int $baoGiaId, array $dong, int $u): array
    {
        $bg = BG_BaoGia_DAL::getById($baoGiaId);
        if (!$bg || (int)$bg->da_xoa === 1) {
            return ['success' => false, 'message' => 'Không tìm thấy báo giá'];
        }
        // Upload bản ký ở Bước 4 đã đặt trạng thái "Đã xác nhận",
        // nhưng nhà thầu vẫn còn Bước 5 — chỉ khóa khi đã bấm HOÀN THÀNH.
        if ((int)($bg->da_hoan_thanh ?? 0) === 1) {
            return ['success' => false, 'message' => 'Báo giá đã hoàn thành — không chỉnh sửa được nữa'];
        }
        if (empty($dong)) {
            return ['success' => false, 'message' => 'Không có dữ liệu để lưu'];
        }

        // Chi nhan hang hoa THUOC goi thau cua bao gia nay
        $hopLe = [];
        foreach (BG_HangHoa_DAL::getByGoiThau((int)$bg->goi_thau_id) as $hh) {
            $hopLe[(int)$hh['id']] = $hh;
        }

        $soLuu = 0;
        try {
            Database::beginTransaction();

            foreach ($dong as $d) {
                $hhId = (int)($d['hang_hoa_id'] ?? 0);
                if ($hhId <= 0 || !isset($hopLe[$hhId])) continue;

                $hh     = $hopLe[$hhId];
                $donGia = ExcelHelper::toNumber($d['don_gia'] ?? 0);

                $ct = new BG_BaoGiaChiTiet_PUBLIC();
                $ct->bao_gia_id  = $baoGiaId;
                $ct->hang_hoa_id = $hhId;

                // ===== Mau 1 =====
                $ct->thong_so_chao_gia = self::nullIfEmpty($d['thong_so_chao_gia'] ?? '');
                $ct->diem_khong_dat    = self::nullIfEmpty($d['diem_khong_dat'] ?? '');

                // ===== Mau 2 =====
                $ct->ten_thuong_mai      = self::nullIfEmpty($d['ten_thuong_mai'] ?? '', 500);
                $ct->model               = self::nullIfEmpty($d['model'] ?? '', 500);
                $ct->hang_san_xuat       = self::nullIfEmpty($d['hang_san_xuat'] ?? '', 500);
                $ct->xuat_xu             = self::nullIfEmpty($d['xuat_xu'] ?? '', 500);
                $ct->quy_cach            = self::nullIfEmpty($d['quy_cach'] ?? '', 500);
                $ct->don_gia             = $donGia;
                // Thanh tien LUON tinh o server (10.2)
                $ct->thanh_tien          = round($donGia * (float)$hh['so_luong'], 2);
                $ct->don_gia_trung_thau  = ExcelHelper::toNumber($d['don_gia_trung_thau'] ?? 0);
                $ct->tai_lieu_tham_chieu = self::nullIfEmpty($d['tai_lieu_tham_chieu'] ?? '');

                BG_BaoGia_DAL::upsertChiTiet($ct);
                $soLuu++;
            }

            BG_BaoGia_DAL::updateTongTien($baoGiaId);
            Database::commit();
        } catch (Throwable $ex) {
            Database::rollBack();
            return ['success' => false, 'message' => 'Lỗi: ' . $ex->getMessage()];
        }

        return [
            'success' => true,
            'message' => 'Đã lưu ' . $soLuu . ' dòng',
            'data'    => ['so_dong' => $soLuu],
        ];
    }

    // =====================================================================
    //  BUOC 5 — CHI DAN VI TRI TAI LIEU (CATALOG)
    // =====================================================================

    /** Thu muc luu file catalog */
    public static function thuMucCatalog(): string
    {
        $dir = rtrim(AppConfig::UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . 'catalog';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Không tạo được thư mục catalog');
        }
        return $dir;
    }

    /**
     * Ten file catalog: catalog-<id-goi-thau>-<slug-nha-thau>.<ext>
     * Nhin ten la biet catalog cua goi nao, nha thau nao.
     */
    public static function tenFileCatalog(
        int $goiThauId,
        string $tenNhaThau,
        string $mst,
        string $ext,
        string $dir,
        string $tenHienTai = ''
    ): string {
        $slug = Helper::slug($tenNhaThau, 60);
        if ($slug === '') $slug = preg_replace('/[^0-9-]/', '', $mst);
        if ($slug === '') $slug = 'nha-thau';

        $goc = 'catalog-' . $goiThauId . '-' . $slug;
        $ten = $goc . '.' . $ext;

        if ($tenHienTai !== '' && $ten === $tenHienTai) return $ten;

        $i = 1;
        while (is_file($dir . DIRECTORY_SEPARATOR . $ten)) {
            $i++;
            $ten = $goc . '-' . $i . '.' . $ext;
            if ($i > 500) {
                $ten = $goc . '-' . Helper::randomString(8) . '.' . $ext;
                break;
            }
        }
        return $ten;
    }

    /**
     * Bang chi dan vi tri tai lieu (Buoc 5).
     * Cot: STT | Ma HH | Ten hang thuong mai | Trang catalog chung minh
     */
    public static function getBangCatalog(int $baoGiaId): array
    {
        $bg = BG_BaoGia_DAL::getById($baoGiaId);
        if (!$bg) return [];

        $hangHoa = BG_HangHoa_DAL::getByGoiThau((int)$bg->goi_thau_id);
        $chiTiet = BG_BaoGia_DAL::getChiTietMap($baoGiaId);
        $catalog = BG_Catalog_DAL::getMap($baoGiaId);

        $out = [];
        foreach ($hangHoa as $hh) {
            $id = (int)$hh['id'];
            $ct = $chiTiet[$id] ?? null;
            $out[] = [
                'hang_hoa_id'    => $id,
                'ma_hh'          => $hh['ma_hh'],
                'ten_hang_hoa'   => $hh['ten_hang_hoa'],
                // "Ten hang thuong mai" lay tu Mau 2 da khai o Buoc 3
                'ten_thuong_mai' => $ct['ten_thuong_mai'] ?? '',
                'trang_catalog'  => $catalog[$id] ?? '',
            ];
        }
        return $out;
    }

    /** Luu hang loat bang chi dan vi tri tai lieu */
    public static function luuCatalog(int $baoGiaId, array $dong, int $u): array
    {
        $bg = BG_BaoGia_DAL::getById($baoGiaId);
        if (!$bg || (int)$bg->da_xoa === 1) {
            return ['success' => false, 'message' => 'Không tìm thấy báo giá'];
        }
        if ((int)($bg->da_hoan_thanh ?? 0) === 1) {
            return ['success' => false, 'message' => 'Báo giá đã hoàn thành — không chỉnh sửa được nữa'];
        }

        $hopLe = [];
        foreach (BG_HangHoa_DAL::getByGoiThau((int)$bg->goi_thau_id) as $hh) {
            $hopLe[(int)$hh['id']] = true;
        }

        $soLuu = 0;
        try {
            Database::beginTransaction();
            foreach ($dong as $d) {
                $hhId = (int)($d['hang_hoa_id'] ?? 0);
                if ($hhId <= 0 || !isset($hopLe[$hhId])) continue;
                BG_Catalog_DAL::upsert($baoGiaId, $hhId,
                    self::nullIfEmpty($d['trang_catalog'] ?? '', 255), $u);
                $soLuu++;
            }
            Database::commit();
        } catch (Throwable $ex) {
            Database::rollBack();
            return ['success' => false, 'message' => 'Lỗi: ' . $ex->getMessage()];
        }

        return ['success' => true, 'message' => 'Đã lưu ' . $soLuu . ' dòng',
                'data' => ['so_dong' => $soLuu]];
    }

    /** Upload file catalog da ky (PDF/anh) */
    public static function uploadCatalog(int $baoGiaId, array $file, int $u): array
    {
        $bg = BG_BaoGia_DAL::getById($baoGiaId);
        if (!$bg || (int)$bg->da_xoa === 1) {
            return ['success' => false, 'message' => 'Không tìm thấy báo giá'];
        }
        if ((int)($bg->da_hoan_thanh ?? 0) === 1) {
            return ['success' => false, 'message' => 'Báo giá đã hoàn thành — không chỉnh sửa được nữa'];
        }

        // --- Kiem tra file (3B.9) ---
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $map = [
                UPLOAD_ERR_INI_SIZE  => 'File vượt quá giới hạn của server',
                UPLOAD_ERR_FORM_SIZE => 'File vượt quá giới hạn cho phép',
                UPLOAD_ERR_PARTIAL   => 'File tải lên chưa hoàn tất, hãy thử lại',
                UPLOAD_ERR_NO_FILE   => 'Chưa chọn file',
            ];
            return ['success' => false, 'message' => $map[$file['error']] ?? 'Lỗi tải file'];
        }
        if (!is_uploaded_file($file['tmp_name']) || (int)$file['size'] <= 0) {
            return ['success' => false, 'message' => 'File không hợp lệ hoặc rỗng'];
        }
        if ((int)$file['size'] > self::BAN_KY_MAX_SIZE) {
            return ['success' => false,
                    'message' => 'File tối đa ' . round(self::BAN_KY_MAX_SIZE / 1048576) . 'MB'];
        }

        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, BG_File_PUBLIC::EXT_CHO_PHEP, true)) {
            return ['success' => false, 'message' => 'Chỉ nhận file PDF hoặc ảnh (JPG, PNG)'];
        }

        $mime = null;
        if (function_exists('finfo_open')) {
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($fi, $file['tmp_name']);
            finfo_close($fi);
            if (!in_array($mime, BG_File_PUBLIC::MIME_CHO_PHEP, true)) {
                return ['success' => false, 'message' => 'Nội dung file không phải PDF/ảnh hợp lệ'];
            }
            if (!in_array($mime, BG_File_PUBLIC::EXT_MIME[$ext] ?? [], true)) {
                return ['success' => false, 'message' => 'Đuôi file không khớp nội dung thật của file'];
            }
        }

        try {
            $dir = self::thuMucCatalog();
            $tenLuu = self::tenFileCatalog(
                (int)$bg->goi_thau_id,
                (string)$bg->ten_cong_ty,
                (string)$bg->ma_so_thue,
                $ext,
                $dir
            );
            $dich = $dir . DIRECTORY_SEPARATOR . $tenLuu;

            if (!move_uploaded_file($file['tmp_name'], $dich)) {
                return ['success' => false, 'message' => 'Không lưu được file tải lên'];
            }

            $fileCuId = (int)($bg->file_catalog_id ?? 0);
            $fileCu   = $fileCuId > 0 ? BG_File_DAL::getById($fileCuId) : null;

            try {
                Database::beginTransaction();

                $ef = new BG_File_PUBLIC();
                $ef->ten_file     = $tenLuu;
                $ef->ten_file_goc = ExcelHelper::toText($file['name'], 255);
                $ef->duong_dan    = 'catalog';
                $ef->loai_file    = $ext;
                $ef->mime_type    = $mime;
                $ef->kich_thuoc   = (int)$file['size'];
                $ef->nhom_file    = BG_File_PUBLIC::NHOM_CATALOG;
                $ef->nguoi_tao    = $u;
                $fileId = BG_File_DAL::insert($ef);

                BG_BaoGia_DAL::updateCatalog($baoGiaId, $fileId);
                if ($fileCuId > 0) BG_File_DAL::softDelete($fileCuId, $u);

                Database::commit();
            } catch (Throwable $exDb) {
                Database::rollBack();
                @unlink($dich);
                return ['success' => false, 'message' => 'Lỗi: ' . $exDb->getMessage()];
            }

            if ($fileCu && $fileCu->ten_file !== '' && $fileCu->ten_file !== $tenLuu) {
                $cu = $dir . DIRECTORY_SEPARATOR . basename($fileCu->ten_file);
                if (is_file($cu)) @unlink($cu);
            }

            DM_NhatKyHeThong_DAL::log(
                $u, self::MODULE_LOG,
                "Nhà thầu tải catalog: {$bg->ten_cong_ty} (MST {$bg->ma_so_thue})",
                'bg_bao_gia', $baoGiaId
            );

            return [
                'success' => true,
                'message' => 'Đã tải lên file chỉ dẫn vị trí tài liệu.',
                'data'    => ['ten_file_goc' => ExcelHelper::toText($file['name'], 255)],
            ];
        } catch (Throwable $ex) {
            return ['success' => false, 'message' => 'Lỗi: ' . $ex->getMessage()];
        }
    }

    /** Duong dan file catalog tren dia (rong neu chua co) */
    public static function duongDanCatalog(int $baoGiaId): string
    {
        $bg = BG_BaoGia_DAL::getById($baoGiaId);
        if (!$bg || empty($bg->file_catalog_id)) return '';
        $f = BG_File_DAL::getById((int)$bg->file_catalog_id);
        if (!$f || $f->ten_file === '') return '';
        $p = self::thuMucCatalog() . DIRECTORY_SEPARATOR . basename($f->ten_file);
        return is_file($p) ? $p : '';
    }

    /** Ban ghi file catalog */
    public static function fileCatalog(int $baoGiaId): ?BG_File_PUBLIC
    {
        $bg = BG_BaoGia_DAL::getById($baoGiaId);
        if (!$bg || empty($bg->file_catalog_id)) return null;
        return BG_File_DAL::getById((int)$bg->file_catalog_id);
    }


    /**
     * Xuat Word "Chi dan vi tri tai lieu" (Buoc 5) tu mau MPS/chi_dan_tai_lieu.docx.
     * Bang: STT | Ma HH | Ten hang thuong mai | Trang catalog chung minh
     */
    public static function xuatWordCatalog(int $baoGiaId): string
    {
        $bg = BG_BaoGia_DAL::getById($baoGiaId);
        if (!$bg) throw new RuntimeException('Không tìm thấy báo giá');

        $gt = BG_GoiThau_DAL::getById((int)$bg->goi_thau_id);
        if (!$gt) throw new RuntimeException('Không tìm thấy gói thầu');

        $dong = self::getBangCatalog($baoGiaId);

        $rows = [];
        $stt  = 0;
        foreach ($dong as $d) {
            $stt++;
            $rows[] = [
                'STT'            => (string)$stt,
                'MA_HH'          => (string)$d['ma_hh'],
                'TEN_THUONG_MAI' => (string)($d['ten_thuong_mai'] ?: $d['ten_hang_hoa']),
                'TRANG_CATALOG'  => (string)$d['trang_catalog'],
            ];
        }

        $mst = trim((string)($bg->ma_so_thue ?? ''));
        $data = [
            'TEN_CONG_TY'  => (string)$bg->ten_cong_ty,
            'MST'          => $mst,
            'SO_THONG_BAO' => (string)$gt->so_thong_bao,
            'TEN_GOI_THAU' => (string)$gt->ten_goi_thau,
            'NGAY_IN'      => date('d/m/Y'),
        ];

        $path = BG_HangHoa_BUS::tempDir() . '/ChiDanTaiLieu_'
              . preg_replace('/[^0-9A-Za-z]/', '', $mst !== '' ? $mst : (string)$baoGiaId)
              . '_' . preg_replace('/[^0-9A-Za-z]/', '_', $gt->so_thong_bao)
              . '_' . date('Ymd_His') . '.docx';

        return WordTemplate::render('chi_dan_tai_lieu.docx', $path, $data, ['CATALOG' => $rows]);
    }


    /**
     * Nhà thầu chốt xong toàn bộ 5 bước — KHÓA mọi chỉnh sửa.
     *
     * Gọi từ Bước 5 sau khi đã upload file chỉ dẫn vị trí tài liệu.
     * Sau khi chốt, nhà thầu chỉ còn XEM lại, không sửa được nữa.
     */
    public static function hoanThanh(int $baoGiaId, int $u): array
    {
        $bg = BG_BaoGia_DAL::getById($baoGiaId);
        if (!$bg || (int)$bg->da_xoa === 1) {
            return ['success' => false, 'message' => 'Không tìm thấy báo giá'];
        }
        if ((int)($bg->da_hoan_thanh ?? 0) === 1) {
            return ['success' => false, 'message' => 'Báo giá này đã hoàn thành trước đó'];
        }
        if (empty($bg->ngay_nop)) {
            return ['success' => false, 'message' => 'Chưa nộp báo giá — hãy hoàn tất Bước 3 trước'];
        }
        if (empty($bg->file_ban_ky_id)) {
            return ['success' => false, 'message' => 'Chưa tải bản báo giá đã ký ở Bước 4'];
        }

        try {
            BG_BaoGia_DAL::updateHoanThanh($baoGiaId);
        } catch (Throwable $ex) {
            return ['success' => false, 'message' => 'Lỗi: ' . $ex->getMessage()];
        }

        DM_NhatKyHeThong_DAL::log(
            $u, self::MODULE_LOG,
            "Nhà thầu hoàn thành 5 bước → báo giá chuyển ĐÃ XÁC NHẬN: {$bg->ten_cong_ty} (MST {$bg->ma_so_thue})",
            'bg_bao_gia', $baoGiaId
        );

        return [
            'success' => true,
            'message' => 'Đã hoàn thành báo giá. Báo giá chuyển sang trạng thái ĐÃ XÁC NHẬN. '
                       . 'Từ giờ bạn chỉ còn xem lại, không chỉnh sửa được nữa.',
            'data'    => ['da_hoan_thanh' => 1],
        ];
    }


    /**
     * Upload file EXCEL chi dan vi tri tai lieu (Buoc 5).
     *
     * Tach rieng khoi uploadCatalog vi 2 loai file khac nhau:
     *   - catalog: ban scan PDF/anh co dau + chu ky
     *   - excel:   bang chi dan dang .xlsx/.xls de ben moi doc du lieu
     */
    public static function uploadCatalogExcel(int $baoGiaId, array $file, int $u): array
    {
        $bg = BG_BaoGia_DAL::getById($baoGiaId);
        if (!$bg || (int)$bg->da_xoa === 1) {
            return ['success' => false, 'message' => 'Không tìm thấy báo giá'];
        }
        if ((int)($bg->da_hoan_thanh ?? 0) === 1) {
            return ['success' => false, 'message' => 'Báo giá đã hoàn thành — không chỉnh sửa được nữa'];
        }

        // --- Kiem tra file (3B.9) ---
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $map = [
                UPLOAD_ERR_INI_SIZE  => 'File vượt quá giới hạn của server',
                UPLOAD_ERR_FORM_SIZE => 'File vượt quá giới hạn cho phép',
                UPLOAD_ERR_PARTIAL   => 'File tải lên chưa hoàn tất, hãy thử lại',
                UPLOAD_ERR_NO_FILE   => 'Chưa chọn file',
            ];
            return ['success' => false, 'message' => $map[$file['error']] ?? 'Lỗi tải file'];
        }
        if (!is_uploaded_file($file['tmp_name']) || (int)$file['size'] <= 0) {
            return ['success' => false, 'message' => 'File không hợp lệ hoặc rỗng'];
        }
        if ((int)$file['size'] > self::BAN_KY_MAX_SIZE) {
            return ['success' => false,
                    'message' => 'File tối đa ' . round(self::BAN_KY_MAX_SIZE / 1048576) . 'MB'];
        }

        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, BG_File_PUBLIC::EXT_CHI_DAN, true)) {
            return ['success' => false, 'message' => 'Chỉ nhận file Word (.docx, .doc) hoặc PDF'];
        }

        $mime = null;
        if (function_exists('finfo_open')) {
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($fi, $file['tmp_name']);
            finfo_close($fi);
            if (!in_array($mime, BG_File_PUBLIC::EXT_MIME_CHI_DAN[$ext] ?? [], true)) {
                return ['success' => false, 'message' => 'Nội dung file không khớp với đuôi file'];
            }
        }

        // .docx la file zip — mo thu de chac chan dung dinh dang, chan file gia
        if ($ext === 'docx') {
            $zip = new ZipArchive();
            if ($zip->open($file['tmp_name']) !== true
                || $zip->locateName('word/document.xml') === false) {
                if ($zip instanceof ZipArchive) @$zip->close();
                return ['success' => false, 'message' => 'File .docx hỏng hoặc không đúng định dạng'];
            }
            $zip->close();
        }

        try {
            $dir = self::thuMucCatalog();
            $tenLuu = self::tenFileCatalog(
                (int)$bg->goi_thau_id,
                (string)$bg->ten_cong_ty . '-chi-dan',
                (string)$bg->ma_so_thue,
                $ext,
                $dir
            );
            $dich = $dir . DIRECTORY_SEPARATOR . $tenLuu;

            if (!move_uploaded_file($file['tmp_name'], $dich)) {
                return ['success' => false, 'message' => 'Không lưu được file tải lên'];
            }

            $fileCuId = (int)($bg->file_catalog_excel_id ?? 0);
            $fileCu   = $fileCuId > 0 ? BG_File_DAL::getById($fileCuId) : null;

            try {
                Database::beginTransaction();

                $ef = new BG_File_PUBLIC();
                $ef->ten_file     = $tenLuu;
                $ef->ten_file_goc = ExcelHelper::toText($file['name'], 255);
                $ef->duong_dan    = 'catalog';
                $ef->loai_file    = $ext;
                $ef->mime_type    = $mime;
                $ef->kich_thuoc   = (int)$file['size'];
                $ef->nhom_file    = BG_File_PUBLIC::NHOM_CATALOG_EXCEL;
                $ef->nguoi_tao    = $u;
                $fileId = BG_File_DAL::insert($ef);

                BG_BaoGia_DAL::updateCatalogExcel($baoGiaId, $fileId);
                if ($fileCuId > 0) BG_File_DAL::softDelete($fileCuId, $u);

                Database::commit();
            } catch (Throwable $exDb) {
                Database::rollBack();
                @unlink($dich);
                return ['success' => false, 'message' => 'Lỗi: ' . $exDb->getMessage()];
            }

            if ($fileCu && $fileCu->ten_file !== '' && $fileCu->ten_file !== $tenLuu) {
                $cu = $dir . DIRECTORY_SEPARATOR . basename($fileCu->ten_file);
                if (is_file($cu)) @unlink($cu);
            }

            DM_NhatKyHeThong_DAL::log(
                $u, self::MODULE_LOG,
                "Nhà thầu tải bảng chỉ dẫn: {$bg->ten_cong_ty} (MST {$bg->ma_so_thue})",
                'bg_bao_gia', $baoGiaId
            );

            return [
                'success' => true,
                'message' => 'Đã tải lên bảng chỉ dẫn vị trí tài liệu.',
                'data'    => ['ten_file_goc' => ExcelHelper::toText($file['name'], 255)],
            ];
        } catch (Throwable $ex) {
            return ['success' => false, 'message' => 'Lỗi: ' . $ex->getMessage()];
        }
    }

    /** Duong dan file Excel chi dan tren dia (rong neu chua co) */
    public static function duongDanCatalogExcel(int $baoGiaId): string
    {
        $bg = BG_BaoGia_DAL::getById($baoGiaId);
        if (!$bg || empty($bg->file_catalog_excel_id)) return '';
        $f = BG_File_DAL::getById((int)$bg->file_catalog_excel_id);
        if (!$f || $f->ten_file === '') return '';
        $p = self::thuMucCatalog() . DIRECTORY_SEPARATOR . basename($f->ten_file);
        return is_file($p) ? $p : '';
    }

    /** Ban ghi file Excel chi dan */
    public static function fileCatalogExcel(int $baoGiaId): ?BG_File_PUBLIC
    {
        $bg = BG_BaoGia_DAL::getById($baoGiaId);
        if (!$bg || empty($bg->file_catalog_excel_id)) return null;
        return BG_File_DAL::getById((int)$bg->file_catalog_excel_id);
    }


    /**
     * Dong goi TAT CA file nha thau da nop thanh 1 file .zip:
     * ban ky + catalog + bang chi dan.
     *
     * Dung cho nut "Tai tat ca" o module Bao gia — ben moi khong phai bam
     * tung file mot.
     *
     * @return string Duong dan file zip tam (nguoi goi lo viec xoa sau khi gui)
     */
    public static function xuatZipTaiLieu(int $baoGiaId): string
    {
        $bg = BG_BaoGia_DAL::getById($baoGiaId);
        if (!$bg) throw new RuntimeException('Không tìm thấy báo giá');

        // [duong dan tren dia => ten hien trong file zip]
        $ds = [];

        $p1 = self::duongDanBanKy($baoGiaId);
        if ($p1 !== '') {
            $ds[$p1] = 'BanKyBaoGia.' . strtolower(pathinfo($p1, PATHINFO_EXTENSION));
        }

        $p2 = self::duongDanCatalog($baoGiaId);
        if ($p2 !== '') {
            $ds[$p2] = 'Catalog.' . strtolower(pathinfo($p2, PATHINFO_EXTENSION));
        }

        $p3 = self::duongDanCatalogExcel($baoGiaId);
        if ($p3 !== '') {
            $ds[$p3] = 'BangChiDanViTriTaiLieu.' . strtolower(pathinfo($p3, PATHINFO_EXTENSION));
        }

        if (empty($ds)) {
            throw new RuntimeException('Báo giá này chưa có file nào');
        }

        $mst = preg_replace('/[^0-9A-Za-z-]/', '', (string)($bg->ma_so_thue ?? '')) ?: (string)$baoGiaId;
        $path = BG_HangHoa_BUS::tempDir() . '/TaiLieu_' . $mst . '_' . date('Ymd_His') . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Không tạo được file zip');
        }
        foreach ($ds as $tren => $trong) {
            $zip->addFile($tren, $trong);
        }
        $zip->close();

        return $path;
    }

}
