<?php
require_once __DIR__ . '/../DAL/BG_HangHoa_DAL.php';
require_once __DIR__ . '/../DAL/BG_GoiThau_DAL.php';
require_once __DIR__ . '/../DAL/DM_NhatKyHeThong_DAL.php';
require_once __DIR__ . '/../PUBLIC/Common/ExcelHelper.php';

class BG_HangHoa_BUS
{
    const MODULE_KEY = 'BG_HangHoa';
    const MODULE_LOG = 'BaoGia';

    /** Dòng đầu tiên chứa dữ liệu trong file mẫu (1=header, 2-4=hướng dẫn) */
    const EXCEL_DATA_ROW = 5;

    /** Số dòng insert mỗi lô — tránh vượt giới hạn placeholder của MySQL */
    const BATCH_SIZE = 100;

    /** Chỉ số cột (0-based) trong file mẫu — phần bên mời điền: A..K */
    const COL_TEN_PHAN      = 0;  // A
    const COL_STT_PHAN      = 1;  // B
    const COL_STT_TB        = 2;  // C
    const COL_TEN_HANG_HOA  = 3;  // D
    const COL_THONG_SO      = 4;  // E
    const COL_CHUNG_NHAN    = 5;  // F
    const COL_XUAT_XU       = 6;  // G
    const COL_DVT           = 7;  // H
    const COL_SO_LUONG      = 8;  // I
    const COL_TRO_CU        = 9;  // J

    private static function validate(BG_HangHoa_PUBLIC $e): string
    {
        $e->ten_hang_hoa = trim($e->ten_hang_hoa);
        if ($e->goi_thau_id <= 0) return 'Chưa chọn gói thầu';
        if ($e->ten_hang_hoa === '') return 'Tên hàng hóa không được để trống';
        if (mb_strlen($e->ten_hang_hoa) > 1000) return 'Tên hàng hóa tối đa 1000 ký tự';
        if ($e->so_luong < 0) return 'Số lượng không được âm';
        if ($e->so_luong > 99999999) return 'Số lượng quá lớn';
        return '';
    }

    public static function insert(BG_HangHoa_PUBLIC $e): array
    {
        $err = self::validate($e);
        if ($err !== '') return ['success' => false, 'message' => $err];

        $gt = BG_GoiThau_DAL::getById($e->goi_thau_id);
        if (!$gt || $gt->da_xoa === 1) return ['success' => false, 'message' => 'Gói thầu không tồn tại'];

        try {
            if ($e->thu_tu <= 0) {
                $e->thu_tu = BG_HangHoa_DAL::maxThuTu($e->goi_thau_id) + 1;
            }
            $id = BG_HangHoa_DAL::insert($e);
            DM_NhatKyHeThong_DAL::log(
                $e->nguoi_tao ?? 0, self::MODULE_LOG,
                "Thêm hàng hóa vào gói {$gt->so_thong_bao}: {$e->ten_hang_hoa}", 'bg_hang_hoa', $id
            );
            return ['success' => true, 'message' => 'Thêm hàng hóa thành công', 'data' => ['id' => $id]];
        } catch (Throwable $ex) {
            return ['success' => false, 'message' => 'Lỗi: ' . $ex->getMessage()];
        }
    }

    public static function update(BG_HangHoa_PUBLIC $e): array
    {
        if (!$e->id) return ['success' => false, 'message' => 'Thiếu ID'];
        $cu = BG_HangHoa_DAL::getById((int)$e->id);
        if (!$cu || $cu->da_xoa === 1) return ['success' => false, 'message' => 'Không tìm thấy hàng hóa'];

        // Không cho chuyển hàng hóa sang gói thầu khác (giữ toàn vẹn chi tiết báo giá đã có)
        $e->goi_thau_id = (int)$cu->goi_thau_id;

        $err = self::validate($e);
        if ($err !== '') return ['success' => false, 'message' => $err];

        try {
            if ($e->thu_tu <= 0) $e->thu_tu = (int)$cu->thu_tu;
            BG_HangHoa_DAL::update($e);
            DM_NhatKyHeThong_DAL::log(
                $e->nguoi_cap_nhat ?? 0, self::MODULE_LOG,
                "Sửa hàng hóa: {$e->ten_hang_hoa}", 'bg_hang_hoa', $e->id
            );
            return ['success' => true, 'message' => 'Cập nhật hàng hóa thành công'];
        } catch (Throwable $ex) {
            return ['success' => false, 'message' => 'Lỗi: ' . $ex->getMessage()];
        }
    }

    public static function trash(int $id, int $u): array
    {
        if ($id <= 0) return ['success' => false, 'message' => 'Thiếu ID'];
        $hh = BG_HangHoa_DAL::getById($id);
        if (!$hh) return ['success' => false, 'message' => 'Không tìm thấy hàng hóa'];

        BG_HangHoa_DAL::softDelete($id, $u);
        DM_NhatKyHeThong_DAL::log(
            $u, self::MODULE_LOG, "Xóa tạm hàng hóa: {$hh->ten_hang_hoa}", 'bg_hang_hoa', $id
        );
        return ['success' => true, 'message' => 'Đã chuyển vào thùng rác'];
    }

    public static function restore(int $id, int $u): array
    {
        if ($id <= 0) return ['success' => false, 'message' => 'Thiếu ID'];
        $hh = BG_HangHoa_DAL::getById($id);
        if (!$hh) return ['success' => false, 'message' => 'Không tìm thấy hàng hóa'];

        BG_HangHoa_DAL::restore($id, $u);
        DM_NhatKyHeThong_DAL::log(
            $u, self::MODULE_LOG, "Khôi phục hàng hóa: {$hh->ten_hang_hoa}", 'bg_hang_hoa', $id
        );
        return ['success' => true, 'message' => 'Đã khôi phục hàng hóa'];
    }

    public static function getById(int $id): ?BG_HangHoa_PUBLIC
    {
        return BG_HangHoa_DAL::getById($id);
    }

    public static function getPaged(int $page, int $pageSize, int $goiThauId, string $search = '', int $daXoa = 0): array
    {
        return BG_HangHoa_DAL::getPaged($page, $pageSize, $goiThauId, $search, $daXoa);
    }

    public static function getByGoiThau(int $goiThauId): array
    {
        return BG_HangHoa_DAL::getByGoiThau($goiThauId);
    }

    // =====================================================================
    // IMPORT EXCEL
    // =====================================================================

    /**
     * Đọc file Excel mẫu → mảng hàng hóa (chỉ parse, chưa ghi DB).
     * Cho phép xem trước trước khi import thật.
     *
     * @return array ['success'=>bool, 'message'=>string, 'data'=>[...], 'loi'=>[...]]
     */
    public static function docFileExcel(string $filePath): array
    {
        try {
            $rows = ExcelHelper::readSheet($filePath);
        } catch (Throwable $ex) {
            return ['success' => false, 'message' => 'Không đọc được file: ' . $ex->getMessage()];
        }

        if (empty($rows)) {
            return ['success' => false, 'message' => 'File không có dữ liệu'];
        }

        // Kiểm tra file có đúng định dạng mẫu: dòng 1 phải có header quen thuộc
        $header = $rows[1] ?? [];
        $tenCotD = ExcelHelper::toText($header[self::COL_TEN_HANG_HOA] ?? '');
        if (mb_stripos($tenCotD, 'hàng ho') === false && mb_stripos($tenCotD, 'hang ho') === false) {
            return [
                'success' => false,
                'message' => 'File không đúng định dạng mẫu. Cột D (dòng 1) phải là "Tên hàng hoá". '
                           . 'Hãy tải file mẫu và điền theo đúng cấu trúc.',
            ];
        }

        $data = [];
        $loi = [];
        foreach ($rows as $rowNo => $cells) {
            if ($rowNo < self::EXCEL_DATA_ROW) continue;   // bỏ header + 3 dòng hướng dẫn

            $tenHangHoa = ExcelHelper::toText($cells[self::COL_TEN_HANG_HOA] ?? '', 1000);
            // Dòng trắng → bỏ qua im lặng
            if ($tenHangHoa === '') {
                $coDuLieu = false;
                foreach ([self::COL_TEN_PHAN, self::COL_STT_PHAN, self::COL_THONG_SO, self::COL_SO_LUONG] as $c) {
                    if (ExcelHelper::toText($cells[$c] ?? '') !== '') { $coDuLieu = true; break; }
                }
                if ($coDuLieu) {
                    $loi[] = "Dòng {$rowNo}: có dữ liệu nhưng thiếu Tên hàng hoá (cột D) → đã bỏ qua";
                }
                continue;
            }

            // Bỏ dòng còn sót text hướng dẫn
            if (mb_stripos($tenHangHoa, 'HDSD') !== false) continue;

            $soLuong = ExcelHelper::toNumber($cells[self::COL_SO_LUONG] ?? 0);
            if ($soLuong < 0) {
                $loi[] = "Dòng {$rowNo}: số lượng âm → đặt về 0";
                $soLuong = 0;
            }

            $data[] = [
                'row'               => $rowNo,
                'ten_phan'          => ExcelHelper::toText($cells[self::COL_TEN_PHAN] ?? '', 200),
                'stt_theo_phan'     => ExcelHelper::toText($cells[self::COL_STT_PHAN] ?? '', 50),
                'stt_thong_bao'     => self::chuanHoaStt($cells[self::COL_STT_TB] ?? ''),
                'ten_hang_hoa'      => $tenHangHoa,
                'thong_so_ky_thuat' => ExcelHelper::toText($cells[self::COL_THONG_SO] ?? ''),
                'chung_nhan'        => ExcelHelper::toText($cells[self::COL_CHUNG_NHAN] ?? ''),
                'yeu_cau_xuat_xu'   => ExcelHelper::toText($cells[self::COL_XUAT_XU] ?? ''),
                'dvt'               => ExcelHelper::toText($cells[self::COL_DVT] ?? '', 50),
                'so_luong'          => $soLuong,
                'yeu_cau_tro_cu'    => ExcelHelper::toText($cells[self::COL_TRO_CU] ?? ''),
            ];
        }

        if (empty($data)) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy dòng hàng hóa nào. Dữ liệu phải bắt đầu từ dòng '
                           . self::EXCEL_DATA_ROW . ' và cột D (Tên hàng hoá) phải có giá trị.',
                'loi' => $loi,
            ];
        }

        return [
            'success' => true,
            'message' => 'Đọc được ' . count($data) . ' dòng hàng hóa',
            'data' => $data,
            'loi' => $loi,
        ];
    }

    /** "1.0" → "1" ; giữ nguyên nếu là chuỗi khác */
    private static function chuanHoaStt($raw): string
    {
        $s = ExcelHelper::toText($raw, 50);
        if ($s === '') return '';
        // Excel hay lưu 1 thành "1.0"
        if (preg_match('/^(\d+)\.0+$/', $s, $m)) return $m[1];
        return $s;
    }

    /**
     * Import hàng hóa từ file Excel vào gói thầu.
     *
     * @param bool $ghiDe true = xóa mềm hàng hóa cũ trước khi nạp mới
     */
    public static function importExcel(int $goiThauId, string $filePath, bool $ghiDe, int $u): array
    {
        if ($goiThauId <= 0) return ['success' => false, 'message' => 'Chưa chọn gói thầu'];

        $gt = BG_GoiThau_DAL::getById($goiThauId);
        if (!$gt || $gt->da_xoa === 1) return ['success' => false, 'message' => 'Gói thầu không tồn tại'];

        // Đã có báo giá → đổi danh mục hàng hóa sẽ làm lệch dữ liệu đã chào
        if ((int)$gt->so_bao_gia > 0 && $ghiDe) {
            return [
                'success' => false,
                'message' => 'Gói thầu đã có ' . (int)$gt->so_bao_gia . ' báo giá — không thể ghi đè danh mục hàng hóa. '
                           . 'Hãy tạo gói thầu mới nếu cần thay đổi danh mục.',
            ];
        }

        $doc = self::docFileExcel($filePath);
        if (!$doc['success']) return $doc;

        $rows = $doc['data'];

        // Ghi nhiều bảng / nhiều lô → bọc transaction
        try {
            Database::beginTransaction();

            if ($ghiDe) {
                BG_HangHoa_DAL::softDeleteByGoiThau($goiThauId, $u);
                $thuTu = 0;
            } else {
                $thuTu = BG_HangHoa_DAL::maxThuTu($goiThauId);
            }

            $tong = 0;
            $lo = [];
            foreach ($rows as $r) {
                $e = new BG_HangHoa_PUBLIC();
                $e->goi_thau_id       = $goiThauId;
                $e->ten_phan          = $r['ten_phan'] !== '' ? $r['ten_phan'] : null;
                $e->stt_theo_phan     = $r['stt_theo_phan'] !== '' ? $r['stt_theo_phan'] : null;
                $e->stt_thong_bao     = $r['stt_thong_bao'] !== '' ? $r['stt_thong_bao'] : null;
                $e->ten_hang_hoa      = $r['ten_hang_hoa'];
                $e->thong_so_ky_thuat = $r['thong_so_ky_thuat'] !== '' ? $r['thong_so_ky_thuat'] : null;
                $e->chung_nhan        = $r['chung_nhan'] !== '' ? $r['chung_nhan'] : null;
                $e->yeu_cau_xuat_xu   = $r['yeu_cau_xuat_xu'] !== '' ? $r['yeu_cau_xuat_xu'] : null;
                $e->dvt               = $r['dvt'] !== '' ? $r['dvt'] : null;
                $e->so_luong          = (float)$r['so_luong'];
                $e->yeu_cau_tro_cu    = $r['yeu_cau_tro_cu'] !== '' ? $r['yeu_cau_tro_cu'] : null;
                $e->thu_tu            = ++$thuTu;
                $e->nguoi_tao         = $u;

                $lo[] = $e;
                if (count($lo) >= self::BATCH_SIZE) {
                    $tong += BG_HangHoa_DAL::insertBatch($lo);
                    $lo = [];
                }
            }
            if ($lo) $tong += BG_HangHoa_DAL::insertBatch($lo);

            Database::commit();

            DM_NhatKyHeThong_DAL::log(
                $u, self::MODULE_LOG,
                "Import {$tong} hàng hóa vào gói thầu {$gt->so_thong_bao}"
                . ($ghiDe ? ' (ghi đè)' : ' (thêm tiếp)'),
                'bg_hang_hoa', $goiThauId
            );

            $msg = "Đã import {$tong} hàng hóa" . ($ghiDe ? ' (đã thay danh mục cũ)' : '');
            return [
                'success' => true,
                'message' => $msg,
                'data' => ['so_dong' => $tong, 'canh_bao' => $doc['loi'] ?? []],
            ];
        } catch (Throwable $ex) {
            Database::rollBack();
            return ['success' => false, 'message' => 'Lỗi khi import: ' . $ex->getMessage()];
        }
    }

    /**
     * Sinh file Excel mẫu cho nhà thầu tải xuống — giữ đúng 30 cột của file mẫu gốc,
     * cột A-K đã điền sẵn dữ liệu yêu cầu, cột L-AD để trống cho nhà thầu điền.
     *
     * @return string đường dẫn file tạm đã tạo
     */
    public static function xuatFileMau(int $goiThauId): string
    {
        $gt = BG_GoiThau_DAL::getById($goiThauId);
        if (!$gt) throw new RuntimeException('Không tìm thấy gói thầu');

        $hangHoa = BG_HangHoa_DAL::getByGoiThau($goiThauId);
        if (empty($hangHoa)) throw new RuntimeException('Gói thầu chưa có hàng hóa');

        $H = ExcelHelper::S_HEADER;      // cột bên mời (A-K)
        $HA = ExcelHelper::S_HEADER_ALT; // cột nhà thầu điền (L-AD)

        // === Dòng 1: header 30 cột ===
        $header = [
            ['v' => 'Tên phần', 's' => $H],
            ['v' => 'STT theo phần', 's' => $H],
            ['v' => 'STT TB mời chào giá', 's' => $H],
            ['v' => 'Tên hàng hoá', 's' => $H],
            ['v' => 'Tính năng, thông số kỹ thuật', 's' => $H],
            ['v' => 'Chứng nhận', 's' => $H],
            ['v' => 'Yêu cầu xuất xứ', 's' => $H],
            ['v' => 'ĐVT', 's' => $H],
            ['v' => "Số lượng/ Khối lượng", 's' => $H],
            ['v' => "Yêu cầu về trợ cụ/ máy phụ trợ", 's' => $H],
            ['v' => 'Số thông báo mời chào giá', 's' => $H],
            // --- Nhà thầu điền từ đây ---
            ['v' => 'Tên thương mại', 's' => $HA],
            ['v' => "Ký, mã, nhãn hiệu, model", 's' => $HA],
            ['v' => 'Mã HS', 's' => $HA],
            ['v' => 'Hãng sản xuất', 's' => $HA],
            ['v' => 'Xuất xứ', 's' => $HA],
            ['v' => "Số lượng/ khối lượng", 's' => $HA],
            ['v' => 'Quy cách', 's' => $HA],
            ['v' => 'Đơn vị tính', 's' => $HA],
            ['v' => "Chi phí cho các dịch vụ liên quan\n(VND)", 's' => $HA],
            ['v' => "Thuế, VAT (nếu có)\n(%)", 's' => $HA],
            ['v' => "Đơn giá (đã bao gồm thuế phí, lệ phí và các dịch vụ liên quan (nếu có))\n(VND)", 's' => $HA],
            ['v' => "Thành tiền\n(VND)", 's' => $HA],
            ['v' => 'Chứng nhận hàng hoá chào', 's' => $HA],
            ['v' => "Đơn giá trúng thầu gần nhất\n(VNĐ)", 's' => $HA],
            ['v' => 'Tài liệu tham chiếu đơn giá trúng thầu gần nhất', 's' => $HA],
            ['v' => 'Mã QR hoặc BarCode định danh hàng hóa', 's' => $HA],
            ['v' => 'Tính năng, thông số Mời chào giá', 's' => $H],
            ['v' => 'Thông số kỹ thuật chào giá', 's' => $HA],
            ['v' => 'Các điểm không đạt kèm thuyết minh', 's' => $HA],
        ];

        // === Dòng 2: hướng dẫn ngắn ===
        $S = ExcelHelper::S_TEXT_WRAP;
        $huongDan = array_fill(0, 30, ['v' => '', 's' => $S]);
        $huongDan[0]  = ['v' => 'KHÔNG SỬA các cột A-K (thông tin mời chào giá)', 's' => $S];
        $huongDan[11] = ['v' => 'Điền tên thương mại đầy đủ (nếu có)', 's' => $S];
        $huongDan[12] = ['v' => 'Điền mã tham chiếu (REF) hoặc model', 's' => $S];
        $huongDan[13] = ['v' => 'Điền mã HS theo đúng cú pháp', 's' => $S];
        $huongDan[14] = ['v' => 'Theo CO / giấy phép NK / FSC / ISO13485', 's' => $S];
        $huongDan[15] = ['v' => 'Theo CO / giấy phép NK / FSC / ISO13485', 's' => $S];
        $huongDan[16] = ['v' => 'Không cần điền', 's' => $S];
        $huongDan[17] = ['v' => 'Quy cách đóng gói thực tế', 's' => $S];
        $huongDan[18] = ['v' => 'Không cần điền', 's' => $S];
        $huongDan[19] = ['v' => 'Chi phí lắp đặt, vận chuyển... Ghi 10000, KHÔNG ghi 10.000,00', 's' => $S];
        $huongDan[20] = ['v' => 'Tỷ lệ VAT %. VD: 0 hoặc 5 hoặc 10', 's' => $S];
        $huongDan[21] = ['v' => 'Đơn giá đã gồm thuế, phí. Ghi 10000, KHÔNG ghi 10.000,00', 's' => $S];
        $huongDan[22] = ['v' => 'Không cần điền — hệ thống tự tính', 's' => $S];
        $huongDan[23] = ['v' => 'VD: FDA (510(k)) / CE (MDD) / CE (MDR) / ISO13485', 's' => $S];
        $huongDan[24] = ['v' => 'Đơn giá trúng thầu trong 360 ngày (nếu có)', 's' => $S];
        $huongDan[25] = ['v' => 'Loại VB; số VB; ngày; tên cơ sở y tế ban hành', 's' => $S];
        $huongDan[26] = ['v' => 'TH1: QR trên từng SP / TH2: QR trên hộp / TH3: Không có', 's' => $S];
        $huongDan[28] = ['v' => 'Nêu thông số kỹ thuật tương ứng yêu cầu', 's' => $S];
        $huongDan[29] = ['v' => 'Nêu rõ thông số không đáp ứng (nếu có) kèm thuyết minh', 's' => $S];

        $rows = [$header, $huongDan];

        // === Từ dòng 3: dữ liệu hàng hóa ===
        foreach ($hangHoa as $hh) {
            $r = array_fill(0, 30, ['v' => '', 's' => $S]);
            $r[0]  = ['v' => (string)($hh['ten_phan'] ?? ''), 's' => $S];
            $r[1]  = ['v' => (string)($hh['stt_theo_phan'] ?? ''), 's' => ExcelHelper::S_CENTER];
            $r[2]  = ['v' => (string)($hh['stt_thong_bao'] ?? ''), 's' => ExcelHelper::S_CENTER];
            $r[3]  = ['v' => (string)$hh['ten_hang_hoa'], 's' => $S];
            $r[4]  = ['v' => (string)($hh['thong_so_ky_thuat'] ?? ''), 's' => $S];
            $r[5]  = ['v' => (string)($hh['chung_nhan'] ?? ''), 's' => $S];
            $r[6]  = ['v' => (string)($hh['yeu_cau_xuat_xu'] ?? ''), 's' => $S];
            $r[7]  = ['v' => (string)($hh['dvt'] ?? ''), 's' => ExcelHelper::S_CENTER];
            $r[8]  = ['v' => (float)$hh['so_luong'], 's' => ExcelHelper::S_NUMBER, 't' => 'n'];
            $r[9]  = ['v' => (string)($hh['yeu_cau_tro_cu'] ?? ''), 's' => $S];
            $r[10] = ['v' => 'Thông báo số ' . $gt->so_thong_bao, 's' => $S];
            // AB (index 27): nhắc lại thông số mời chào giá để nhà thầu đối chiếu
            $r[27] = ['v' => (string)($hh['thong_so_ky_thuat'] ?? ''), 's' => $S];
            // Cột dành cho nhà thầu điền: để trống nhưng giữ viền
            $rows[] = $r;
        }

        $cols = [14, 12, 12, 34, 45, 22, 20, 8, 11, 30, 20,
                 26, 20, 12, 20, 16, 12, 18, 11, 16, 12, 20, 16, 24, 16, 28, 24, 40, 34, 34];

        $fileName = 'BaoGia_' . preg_replace('/[^A-Za-z0-9]/', '', $gt->so_thong_bao)
                  . '_' . date('Ymd_His') . '.xlsx';
        $path = self::tempDir() . '/' . $fileName;

        ExcelHelper::write($path, [
            'BảngGiá' => [
                'cols'    => $cols,
                'freeze'  => 'E3',
                'heights' => [1 => 60, 2 => 46],
                'rows'    => $rows,
            ],
        ]);

        return $path;
    }

    /** Thư mục tạm cho file xuất — tự tạo nếu chưa có */
    public static function tempDir(): string
    {
        $dir = rtrim(AppConfig::UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . 'temp';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return sys_get_temp_dir();
        }
        return $dir;
    }
}
