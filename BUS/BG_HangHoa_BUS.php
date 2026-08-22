<?php
require_once __DIR__ . '/../DAL/BG_HangHoa_DAL.php';
require_once __DIR__ . '/../DAL/BG_GoiThau_DAL.php';
require_once __DIR__ . '/../DAL/DM_NhatKyHeThong_DAL.php';
require_once __DIR__ . '/../PUBLIC/Common/ExcelHelper.php';
require_once __DIR__ . '/BG_GoiThau_BUS.php';   // kiemTraChuaChotSo()

class BG_HangHoa_BUS
{
    const MODULE_KEY = 'BG_HangHoa';
    const MODULE_LOG = 'BaoGia';

    /** Dòng đầu tiên chứa dữ liệu trong file mẫu (1=header, 2-4=hướng dẫn) */
    const EXCEL_DATA_ROW = 5;

    /** Số dòng insert mỗi lô — tránh vượt giới hạn placeholder của MySQL */
    const BATCH_SIZE = 100;

    /** Chỉ số cột (0-based) trong file mẫu — phần bên mời điền: A..K */
    // Cột file mẫu theo **Phụ lục III — Bảng mô tả yêu cầu kỹ thuật cơ bản**:
    //   A: STT | B: Mã HH | C: Tên hàng hóa chào giá
    //   D: Yêu cầu kỹ thuật mời chào giá | E: ĐVT | F: Số lượng
    const COL_STT           = 0;  // A
    const COL_MA_HH         = 1;  // B
    const COL_TEN_HANG_HOA  = 2;  // C
    const COL_THONG_SO      = 3;  // D
    const COL_DVT           = 4;  // E
    const COL_SO_LUONG      = 5;  // F

    private static function validate(BG_HangHoa_PUBLIC $e, bool $isUpdate = false): string
    {
        $e->ten_hang_hoa = trim($e->ten_hang_hoa);
        $e->ma_hh        = trim((string)$e->ma_hh);

        if ($e->goi_thau_id <= 0) return 'Chưa chọn gói thầu';
        if ($e->ten_hang_hoa === '') return 'Tên hàng hóa không được để trống';
        if (mb_strlen($e->ten_hang_hoa) > 1000) return 'Tên hàng hóa tối đa 1000 ký tự';
        if ($e->so_luong < 0) return 'Số lượng không được âm';
        if ($e->so_luong > 99999999) return 'Số lượng quá lớn';

        // Mã HH bỏ trống → tự sinh HH001, HH002... theo gói thầu
        if ($e->ma_hh === '') {
            $e->ma_hh = 'HH' . str_pad(
                (string)(BG_HangHoa_DAL::soThuTuMaLonNhat($e->goi_thau_id) + 1), 3, '0', STR_PAD_LEFT
            );
        }
        if (mb_strlen($e->ma_hh) > 50) return 'Mã HH tối đa 50 ký tự';

        // Trùng mã trong cùng gói thầu → nhà thầu không biết chào cho hàng nào
        $excludeId = $isUpdate ? (int)$e->id : 0;
        if (BG_HangHoa_DAL::maHhExists($e->ma_hh, $e->goi_thau_id, $excludeId)) {
            return 'Mã HH "' . $e->ma_hh . '" đã tồn tại trong gói thầu này';
        }
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

        $err = self::validate($e, true);
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

        $chot = BG_GoiThau_BUS::kiemTraChuaChotSo((int)$hh->goi_thau_id);
        if (!$chot['ok']) return ['success' => false, 'message' => $chot['message']];

        // Đã có nhà thầu chào giá thì KHÔNG cho xóa: dòng sẽ biến mất khỏi bảng
        // tổng hợp nhưng tiền vẫn nằm trong tổng của nhà thầu → sai số.
        $soChao = BG_HangHoa_DAL::demBaoGiaDaChao($id);
        if ($soChao > 0) {
            return [
                'success' => false,
                'message' => "Đã có {$soChao} nhà thầu chào giá cho hàng hóa này — không thể xóa. "
                           . 'Xóa sẽ làm lệch tổng tiền trong bảng tổng hợp.',
            ];
        }

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

        // Kiểm tra đúng định dạng Phụ lục III: dò header ở 5 dòng đầu
        // (file thật có thể có 1-2 dòng tiêu đề phía trên).
        $dongHeader = 0;
        for ($d = 1; $d <= 5; $d++) {
            $ten = ExcelHelper::toText($rows[$d][self::COL_TEN_HANG_HOA] ?? '');
            // So khớp sau khi BỎ DẤU. Trước đây dò chuỗi có dấu cứng
            // ('hàng ho') nên tiêu đề thật "Tên hàng hóa..." KHÔNG khớp
            // (chữ 'ó' có dấu), làm mọi file mẫu đều bị báo sai định dạng.
            if (mb_stripos(self::boDau($ten), 'hang hoa') !== false) {
                $dongHeader = $d;
                break;
            }
        }
        if ($dongHeader === 0) {
            return [
                'success' => false,
                'message' => 'File không đúng định dạng Phụ lục III. Cần các cột: '
                           . 'STT | Mã HH | Tên hàng hóa chào giá | Yêu cầu kỹ thuật | ĐVT | Số lượng. '
                           . 'Hãy tải file mẫu và điền theo đúng cấu trúc.',
            ];
        }
        // Dữ liệu bắt đầu ngay sau dòng header
        $dongBatDau = $dongHeader + 1;

        $data = [];
        $loi = [];
        foreach ($rows as $rowNo => $cells) {
            if ($rowNo < $dongBatDau) continue;   // bỏ tiêu đề + header

            $tenHangHoa = ExcelHelper::toText($cells[self::COL_TEN_HANG_HOA] ?? '', 1000);
            // Dòng trắng → bỏ qua im lặng
            if ($tenHangHoa === '') {
                $coDuLieu = false;
                foreach ([self::COL_MA_HH, self::COL_THONG_SO, self::COL_DVT, self::COL_SO_LUONG] as $c) {
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
                'ma_hh'             => ExcelHelper::toText($cells[self::COL_MA_HH] ?? '', 50),
                'ten_hang_hoa'      => $tenHangHoa,
                'thong_so_ky_thuat' => ExcelHelper::toText($cells[self::COL_THONG_SO] ?? ''),
                'dvt'               => ExcelHelper::toText($cells[self::COL_DVT] ?? '', 50),
                'so_luong'          => $soLuong,
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
            // Bộ đếm sinh Mã HH cho dòng bỏ trống mã
            $soTiepTheo = BG_HangHoa_DAL::soThuTuMaLonNhat($goiThauId);

            // Tính cả mã HHxxx do CHÍNH file này khai sẵn, nếu không dòng bỏ
            // trống mã sẽ sinh trùng với dòng đã ghi rõ mã trong cùng file.
            foreach ($rows as $r) {
                if (preg_match('/^HH(\d+)$/', (string)$r['ma_hh'], $m)) {
                    $soTiepTheo = max($soTiepTheo, (int)$m[1]);
                }
            }

            foreach ($rows as $r) {
                $e = new BG_HangHoa_PUBLIC();
                $e->goi_thau_id       = $goiThauId;
                // Mã HH bỏ trống → sinh tiếp theo mã lớn nhất đang có trong gói
                $maHh = $r['ma_hh'];
                if ($maHh === '') {
                    $soTiepTheo++;
                    $maHh = 'HH' . str_pad((string)$soTiepTheo, 3, '0', STR_PAD_LEFT);
                }
                $e->ma_hh             = $maHh;
                // THIẾU 2 dòng này -> mọi lần import đều ra tên rỗng và số lượng 0
                $e->ten_hang_hoa      = $r['ten_hang_hoa'];
                $e->so_luong          = (float)$r['so_luong'];
                $e->thong_so_ky_thuat = $r['thong_so_ky_thuat'] !== '' ? $r['thong_so_ky_thuat'] : null;
                $e->dvt               = $r['dvt'] !== '' ? $r['dvt'] : null;
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
     * Sinh file Excel mẫu cho nhà thầu — MỖI MẪU MỘT FILE RIÊNG.
     *
     * Tách riêng (thay vì 2 sheet trong 1 file) để nhà thầu tải đúng phần
     * đang làm ở bước hiện tại, đỡ nhầm sang sheet kia.
     *
     * @param string $mau 'mau1' = Bảng đáp ứng kỹ thuật (Mẫu 1)
     *                    'mau2' = Bảng chào giá (Mẫu 2)
     */
    /**
     * File Excel mau DANH MUC HANG HOA cho BEN MOI import len.
     *
     * KHAC voi xuatFileMau(): ham kia sinh Mau 1 / Mau 2 cho NHA THAU chao gia
     * nen bat buoc phai co san hang hoa. Con o day la mau de NAP hang hoa vao,
     * goi thau moi tao chua co dong nao la chuyen binh thuong â van phai tai duoc.
     *
     * Cot phai khop hang so COL_* dung khi import (Â§4.2).
     */
    /** Bỏ dấu tiếng Việt để so khớp tiêu đề cột không phụ thuộc dấu */
    private static function boDau(string $s): string
    {
        $s = mb_strtolower(trim($s), 'UTF-8');
        $map = [
            'a' => 'áàảãạăắằẳẵặâấầẩẫậ',
            'e' => 'éèẻẽẹêếềểễệ',
            'i' => 'íìỉĩị',
            'o' => 'óòỏõọôốồổỗộơớờởỡợ',
            'u' => 'úùủũụưứừửữự',
            'y' => 'ýỳỷỹỵ',
            'd' => 'đ',
        ];
        foreach ($map as $khong => $co) {
            foreach (preg_split('//u', $co, -1, PREG_SPLIT_NO_EMPTY) as $ch) {
                $s = str_replace($ch, $khong, $s);
            }
        }
        return $s;
    }

    public static function xuatFileMauDanhMuc(int $goiThauId): string
    {
        $gt = BG_GoiThau_DAL::getById($goiThauId);
        if (!$gt) throw new RuntimeException('Không tìm thấy gói thầu');

        $H = ExcelHelper::S_HEADER;
        $S = ExcelHelper::S_TEXT_WRAP;
        $C = ExcelHelper::S_CENTER;
        $N = ExcelHelper::S_NUMBER;

        $rows = [
            [['v' => 'DANH MỤC HÀNG HÓA MỜI CHÀO GIÁ', 's' => ExcelHelper::S_TITLE]],
            [['v' => 'Thư mời số ' . $gt->so_thong_bao . ' — ' . $gt->ten_goi_thau,
              's' => ExcelHelper::S_SUBTITLE]],
            [['v' => 'Bỏ trống cột Mã HH thì hệ thống tự sinh (HH001, HH002...). '
                   . 'Xóa các dòng ví dụ trước khi import.',
              's' => ExcelHelper::S_SUBTITLE]],
            [
                ['v' => 'STT', 's' => $H],
                ['v' => 'Mã HH', 's' => $H],
                ['v' => 'Tên hàng hóa mời chào giá', 's' => $H],
                ['v' => 'Yêu cầu kỹ thuật mời chào giá', 's' => $H],
                ['v' => 'ĐVT', 's' => $H],
                ['v' => 'Số lượng', 's' => $H],
            ],
        ];

        // Neu goi thau DA co hang hoa -> do san ra de sua; chua co thi cho 3 dong vi du
        $hangHoa = BG_HangHoa_DAL::getByGoiThau($goiThauId);
        if (!empty($hangHoa)) {
            $stt = 0;
            foreach ($hangHoa as $hh) {
                $stt++;
                $rows[] = [
                    ['v' => $stt, 's' => $C, 't' => 'n'],
                    ['v' => (string)($hh['ma_hh'] ?? ''), 's' => $C],
                    ['v' => (string)$hh['ten_hang_hoa'], 's' => $S],
                    ['v' => (string)($hh['thong_so_ky_thuat'] ?? ''), 's' => $S],
                    ['v' => (string)($hh['dvt'] ?? ''), 's' => $C],
                    ['v' => (float)$hh['so_luong'], 's' => $N, 't' => 'n'],
                ];
            }
        } else {
            $viDu = [
                [1, 'HH001', 'Ví dụ: Nẹp tạo hình bản sống cổ',
                    'Vật liệu: Hợp kim Titan; chiều dài ≥ 8mm', 'Cái', 100],
                [2, '', 'Ví dụ: Vít tạo hình (bỏ trống Mã HH → tự sinh)',
                    'Tự taro; đường kính ≥ 2,5mm', 'Cái', 300],
                [3, '', '', '', '', ''],
            ];
            foreach ($viDu as $v) {
                $rows[] = [
                    ['v' => $v[0], 's' => $C, 't' => 'n'],
                    ['v' => $v[1], 's' => $C],
                    ['v' => $v[2], 's' => $S],
                    ['v' => $v[3], 's' => $S],
                    ['v' => $v[4], 's' => $C],
                    ['v' => $v[5] === '' ? '' : (float)$v[5], 's' => $N, 't' => $v[5] === '' ? 's' : 'n'],
                ];
            }
        }

        $path = self::tempDir() . '/DanhMucHangHoa_'
              . preg_replace('/[^0-9A-Za-z]/', '_', (string)$gt->so_thong_bao)
              . '_' . date('Ymd_His') . '.xlsx';

        ExcelHelper::write($path, [
            'DanhMucHangHoa' => [
                'cols'    => [6, 14, 46, 60, 10, 12],
                'freeze'  => 'A5',
                'heights' => [4 => 34],
                'rows'    => $rows,
            ],
        ]);
        return $path;
    }

    public static function xuatFileMau(int $goiThauId, string $mau = 'mau2'): string
    {
        $gt = BG_GoiThau_DAL::getById($goiThauId);
        if (!$gt) throw new RuntimeException('Không tìm thấy gói thầu');

        $hangHoa = BG_HangHoa_DAL::getByGoiThau($goiThauId);
        if (empty($hangHoa)) throw new RuntimeException('Gói thầu chưa có danh mục hàng hóa');

        return $mau === 'mau1'
            ? self::xuatMau1($gt, $hangHoa)
            : self::xuatMau2($gt, $hangHoa);
    }

    /** MẪU 1 — Bảng đáp ứng kỹ thuật (Phụ lục II) */
    private static function xuatMau1(BG_GoiThau_PUBLIC $gt, array $hangHoa): string
    {
        $H  = ExcelHelper::S_HEADER;
        $HA = ExcelHelper::S_HEADER_ALT;
        $S  = ExcelHelper::S_TEXT_WRAP;
        $C  = ExcelHelper::S_CENTER;

        $rows = [
            [['v' => 'MẪU 1: BẢNG ĐÁP ỨNG KỸ THUẬT HÀNG HÓA CHÀO GIÁ', 's' => ExcelHelper::S_TITLE]],
            [['v' => 'Thư mời số ' . $gt->so_thong_bao . ' — ' . $gt->ten_goi_thau,
              's' => ExcelHelper::S_SUBTITLE]],
            [null],
            [
                ['v' => 'Mã HH', 's' => $H],
                ['v' => 'Tên hàng hóa mời chào giá', 's' => $H],
                ['v' => 'Yêu cầu kỹ thuật mời chào giá', 's' => $H],
                ['v' => 'Yêu cầu kỹ thuật chào giá', 's' => $HA],
                ['v' => 'Các điểm không đạt kèm thuyết minh', 's' => $HA],
            ],
            [
                ['v' => '', 's' => $S],
                ['v' => '', 's' => $S],
                ['v' => 'KHÔNG SỬA 3 cột đầu', 's' => $S],
                ['v' => 'Nêu các thông số kỹ thuật của hàng hóa tương ứng với yêu cầu kỹ thuật', 's' => $S],
                ['v' => 'Nêu rõ thông số không đáp ứng (nếu có) kèm thuyết minh/lý giải', 's' => $S],
            ],
        ];

        foreach ($hangHoa as $hh) {
            $rows[] = [
                ['v' => (string)($hh['ma_hh'] ?? ''), 's' => $C],
                ['v' => (string)$hh['ten_hang_hoa'], 's' => $S],
                ['v' => (string)($hh['thong_so_ky_thuat'] ?? ''), 's' => $S],
                ['v' => '', 's' => $S],
                ['v' => '', 's' => $S],
            ];
        }

        $path = self::tempDir() . '/Mau1_DapUngKyThuat_'
              . preg_replace('/[^0-9A-Za-z]/', '_', $gt->so_thong_bao) . '_' . date('Ymd_His') . '.xlsx';

        ExcelHelper::write($path, [
            'Mau1_DapUngKyThuat' => [
                'cols'    => [12, 40, 50, 46, 44],
                'freeze'  => 'A5',
                'heights' => [4 => 40, 5 => 44],
                'rows'    => $rows,
            ],
        ]);
        return $path;
    }

    /** MẪU 2 — Bảng chào giá (Phụ lục II) */
    private static function xuatMau2(BG_GoiThau_PUBLIC $gt, array $hangHoa): string
    {
        $H  = ExcelHelper::S_HEADER;
        $HA = ExcelHelper::S_HEADER_ALT;
        $S  = ExcelHelper::S_TEXT_WRAP;
        $C  = ExcelHelper::S_CENTER;
        $N  = ExcelHelper::S_NUMBER;

        $rows = [
            [['v' => 'MẪU 2: BẢNG CHÀO GIÁ', 's' => ExcelHelper::S_TITLE]],
            [['v' => 'Thư mời số ' . $gt->so_thong_bao . ' — ' . $gt->ten_goi_thau,
              's' => ExcelHelper::S_SUBTITLE]],
            [['v' => 'Đơn giá ĐÃ bao gồm thuế, phí, lệ phí và các dịch vụ liên quan (nếu có).',
              's' => ExcelHelper::S_SUBTITLE]],
            [
                ['v' => 'TT', 's' => $H],
                ['v' => 'Mã HH', 's' => $H],
                ['v' => 'Tên hàng hóa mời chào giá', 's' => $H],
                ['v' => 'Tên thương mại', 's' => $HA],
                ['v' => "Ký, mã, nhãn hiệu,\nmodel", 's' => $HA],
                ['v' => 'Hãng sản xuất', 's' => $HA],
                ['v' => 'Xuất xứ', 's' => $HA],
                ['v' => "Số lượng /\nkhối lượng", 's' => $H],
                ['v' => 'Quy cách', 's' => $HA],
                ['v' => 'Đơn vị tính', 's' => $H],
                ['v' => "Đơn giá\n(VND)", 's' => $HA],
                ['v' => "Thành tiền\n(VND)", 's' => $HA],
                ['v' => "Đơn giá trúng thầu\ngần nhất (VNĐ)", 's' => $HA],
                ['v' => "Tài liệu tham chiếu\nđơn giá trúng thầu", 's' => $HA],
                ['v' => "Số thông báo\nmời thầu", 's' => $HA],
            ],
        ];

        $stt = 0;
        foreach ($hangHoa as $hh) {
            $stt++;
            $rows[] = [
                ['v' => $stt, 's' => $C, 't' => 'n'],
                ['v' => (string)($hh['ma_hh'] ?? ''), 's' => $C],
                ['v' => (string)$hh['ten_hang_hoa'], 's' => $S],
                ['v' => '', 's' => $S],
                ['v' => '', 's' => $S],
                ['v' => '', 's' => $S],
                ['v' => '', 's' => $S],
                ['v' => (float)$hh['so_luong'], 's' => $N, 't' => 'n'],
                ['v' => '', 's' => $S],
                ['v' => (string)($hh['dvt'] ?? ''), 's' => $C],
                ['v' => '', 's' => ExcelHelper::S_MONEY],
                ['v' => '', 's' => ExcelHelper::S_MONEY],
                ['v' => '', 's' => ExcelHelper::S_MONEY],
                ['v' => '', 's' => $S],
                ['v' => '', 's' => $S],
            ];
        }

        $path = self::tempDir() . '/Mau2_BangChaoGia_'
              . preg_replace('/[^0-9A-Za-z]/', '_', $gt->so_thong_bao) . '_' . date('Ymd_His') . '.xlsx';

        ExcelHelper::write($path, [
            'Mau2_BangChaoGia' => [
                'cols'    => [6, 12, 38, 26, 20, 22, 16, 12, 18, 11, 18, 18, 20, 30, 18],
                'freeze'  => 'D5',
                'heights' => [4 => 46],
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
