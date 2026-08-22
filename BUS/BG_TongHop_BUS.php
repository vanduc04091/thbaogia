<?php
require_once __DIR__ . '/../DAL/BG_BaoGia_DAL.php';
require_once __DIR__ . '/../DAL/BG_HangHoa_DAL.php';
require_once __DIR__ . '/../DAL/BG_GoiThau_DAL.php';
require_once __DIR__ . '/../DAL/DM_NhatKyHeThong_DAL.php';
require_once __DIR__ . '/../PUBLIC/Common/ExcelHelper.php';
require_once __DIR__ . '/BG_HangHoa_BUS.php';

/**
 * BG_TongHop_BUS — Tổng hợp báo giá của các nhà thầu ĐÃ XÁC NHẬN bản giấy.
 *
 * Dạng bảng: mỗi (hàng hóa × nhà thầu) là 1 dòng, tên nhà thầu + MST là cột.
 * Chỉ báo giá có trang_thai = TT_DA_XAC_NHAN mới được đưa vào.
 */
class BG_TongHop_BUS
{
    const MODULE_KEY = 'BG_TongHop';
    const MODULE_LOG = 'BaoGia';

    /**
     * Dựng dữ liệu tổng hợp cho 1 gói thầu.
     *
     * @return array [
     *   'goi_thau'  => BG_GoiThau_PUBLIC,
     *   'nha_thau'  => [ ['id','ten_cong_ty','ma_so_thue','tong_tien','ngay_xac_nhan'], ... ],
     *   'hang_hoa'  => [ ['id','ten_hang_hoa',...,'chao' => [baoGiaId => [don_gia, thanh_tien, ...]],
     *                     'gia_min','nha_thau_min','so_nha_thau_chao'], ... ],
     *   'tong_ket'  => ['tong_gia_min' => float, 'so_hang_hoa_co_gia' => int, ...],
     * ]
     */
    public static function duLieuTongHop(int $goiThauId): array
    {
        $gt = BG_GoiThau_DAL::getById($goiThauId);
        if (!$gt || $gt->da_xoa === 1) {
            throw new RuntimeException('Không tìm thấy gói thầu');
        }

        $hangHoa = BG_HangHoa_DAL::getByGoiThau($goiThauId);
        $baoGia  = BG_BaoGia_DAL::getDaXacNhanByGoiThau($goiThauId);

        // Nạp chi tiết từng nhà thầu 1 lần, map theo hang_hoa_id
        $chiTietTheoBaoGia = [];
        foreach ($baoGia as $bg) {
            $chiTietTheoBaoGia[(int)$bg['id']] = BG_BaoGia_DAL::getChiTietMap((int)$bg['id']);
        }

        $rows = [];
        $tongGiaMin = 0.0;
        $soCoGia = 0;

        foreach ($hangHoa as $hh) {
            $hhId = (int)$hh['id'];
            $soLuong = (float)$hh['so_luong'];

            $chao = [];
            $giaMin = null;
            $nhaThauMin = null;
            $soChao = 0;

            foreach ($baoGia as $bg) {
                $bgId = (int)$bg['id'];
                $ct = $chiTietTheoBaoGia[$bgId][$hhId] ?? null;

                $donGia = $ct ? (float)$ct['don_gia'] : 0.0;
                // Giữ ĐỦ mọi cột nhà thầu điền (khớp bg_bao_gia_chi_tiet)
                // để bảng tổng hợp xuất ra không thiếu thông tin nào.
                // Giữ ĐỦ cột nhà thầu điền theo Phụ lục II (Mẫu 1 + Mẫu 2)
                $chao[$bgId] = [
                    'don_gia'               => $donGia,
                    'thanh_tien'            => $ct ? (float)$ct['thanh_tien'] : 0.0,
                    'ten_thuong_mai'        => $ct['ten_thuong_mai'] ?? '',
                    'model'                 => $ct['model'] ?? '',
                    'hang_san_xuat'         => $ct['hang_san_xuat'] ?? '',
                    'xuat_xu'               => $ct['xuat_xu'] ?? '',
                    'quy_cach'              => $ct['quy_cach'] ?? '',
                    'don_gia_trung_thau'    => $ct ? (float)$ct['don_gia_trung_thau'] : 0.0,
                    'tai_lieu_tham_chieu'   => $ct['tai_lieu_tham_chieu'] ?? '',
                    'thong_so_chao_gia'     => $ct['thong_so_chao_gia'] ?? '',
                    'diem_khong_dat'        => $ct['diem_khong_dat'] ?? '',
                    'co_chao'               => $donGia > 0,
                ];

                if ($donGia > 0) {
                    $soChao++;
                    if ($giaMin === null || $donGia < $giaMin) {
                        $giaMin = $donGia;
                        $nhaThauMin = $bgId;
                    }
                }
            }

            if ($giaMin !== null) {
                $soCoGia++;
                $tongGiaMin += $giaMin * $soLuong;
            }

            // Cột yêu cầu theo Phụ lục III (Mã HH thay cho cách đánh số cũ)
            $rows[] = [
                'id'                => $hhId,
                'ma_hh'             => $hh['ma_hh'],
                'ten_hang_hoa'      => $hh['ten_hang_hoa'],
                'thong_so_ky_thuat' => $hh['thong_so_ky_thuat'],
                'dvt'               => $hh['dvt'],
                'so_luong'          => $soLuong,
                'chao'              => $chao,
                'gia_min'           => $giaMin,
                'nha_thau_min'      => $nhaThauMin,
                'so_nha_thau_chao'  => $soChao,
            ];
        }

        return [
            'goi_thau' => $gt,
            'nha_thau' => array_map(static function (array $b): array {
                return [
                    'id'            => (int)$b['id'],
                    'ten_cong_ty'   => $b['ten_cong_ty'],
                    'ma_so_thue'    => $b['ma_so_thue'],
                    'email'         => $b['email'],
                    'dien_thoai'    => $b['dien_thoai'],
                    'dia_chi'       => $b['dia_chi'],
                    'tong_tien'     => (float)$b['tong_tien'],
                    'ngay_nop'      => $b['ngay_nop'],
                    'ngay_xac_nhan' => $b['ngay_xac_nhan'],
                ];
            }, $baoGia),
            'hang_hoa' => $rows,
            'tong_ket' => [
                'so_nha_thau'         => count($baoGia),
                'so_hang_hoa'         => count($rows),
                'so_hang_hoa_co_gia'  => $soCoGia,
                'tong_gia_min'        => $tongGiaMin,
            ],
        ];
    }

    /**
     * Xuất Excel tổng hợp — 3 sheet:
     *  1. "SoSanhGia": mỗi nhà thầu 1 dòng (có cột Nhà thầu + MST), tô đậm giá thấp nhất
     *  2. "GiaThapNhat": bảng gọn giá thấp nhất theo từng hàng hóa
     *  3. "DanhSachNhaThau": thông tin liên hệ + tổng tiền
     *
     * @return string đường dẫn file tạm
     */
    public static function xuatExcel(int $goiThauId, int $u): string
    {
        $d = self::duLieuTongHop($goiThauId);
        $gt = $d['goi_thau'];
        $nhaThau = $d['nha_thau'];
        $hangHoa = $d['hang_hoa'];

        if (empty($nhaThau)) {
            throw new RuntimeException('Chưa có báo giá nào được xác nhận bản giấy — không có gì để tổng hợp.');
        }

        $H  = ExcelHelper::S_HEADER;
        $HA = ExcelHelper::S_HEADER_ALT;
        $W  = ExcelHelper::S_TEXT_WRAP;
        $C  = ExcelHelper::S_CENTER;
        $N  = ExcelHelper::S_NUMBER;
        $M  = ExcelHelper::S_MONEY;
        $B  = ExcelHelper::S_BEST;

        // =============================================================
        // SHEET 1: SO SÁNH GIÁ — MỖI NHÀ THẦU MỘT DÒNG
        // =============================================================
        // Bố cục: 1 hàng hóa có N nhà thầu -> N dòng liên tiếp, tên nhà thầu và
        // MST nằm thành CỘT trên chính dòng đó. Đọc theo chiều ngang là ra ngay
        // ai chào bao nhiêu; lọc / sắp xếp / PivotTable trong Excel đều dùng
        // được (bố cục cũ gộp mỗi nhà thầu thành 1 nhóm cột thì không lọc nổi).
        $soCot = 19;   // 6 bên mời (Phụ lục III) + 13 nhà thầu (Mẫu 1+2)
        $colCuoi = ExcelHelper::colLetter($soCot - 1);
        $merges = [];

        // --- Tiêu đề ---
        $r1 = array_fill(0, $soCot, ['v' => '', 's' => ExcelHelper::S_TITLE]);
        $r1[0] = ['v' => 'BẢNG TỔNG HỢP BÁO GIÁ', 's' => ExcelHelper::S_TITLE];

        $r2 = array_fill(0, $soCot, ['v' => '', 's' => ExcelHelper::S_SUBTITLE]);
        $r2[0] = ['v' => 'Thông báo mời chào giá số ' . $gt->so_thong_bao . ' — ' . $gt->ten_goi_thau,
                  's' => ExcelHelper::S_SUBTITLE];

        $r3 = array_fill(0, $soCot, ['v' => '', 's' => ExcelHelper::S_SUBTITLE]);
        $r3[0] = ['v' => 'Số nhà thầu đã xác nhận bản giấy: ' . count($nhaThau)
                       . '   |   Số hàng hoá: ' . count($hangHoa)
                       . '   |   Xuất lúc: ' . date('d/m/Y H:i'),
                  's' => ExcelHelper::S_SUBTITLE];

        $r4 = array_fill(0, $soCot, null);

        $merges[] = 'A1:' . $colCuoi . '1';
        $merges[] = 'A2:' . $colCuoi . '2';
        $merges[] = 'A3:' . $colCuoi . '3';

        // --- Header 1 tầng, không gộp cột ---
        // Header: 6 cột bên mời (Phụ lục III) + 13 cột nhà thầu (Mẫu 1 + Mẫu 2)
        $rowHeader = [
            // --- Thông tin mời chào giá (Phụ lục III) ---
            ['v' => 'STT', 's' => $H],
            ['v' => 'Mã HH', 's' => $H],
            ['v' => 'Tên hàng hóa mời chào giá', 's' => $H],
            ['v' => 'Yêu cầu kỹ thuật mời chào giá', 's' => $H],
            ['v' => 'ĐVT', 's' => $H],
            ['v' => "Số lượng /\nkhối lượng", 's' => $H],
            // --- Nhà thầu ---
            ['v' => 'Nhà thầu', 's' => $HA],
            ['v' => 'Mã số thuế', 's' => $HA],
            // --- Mẫu 1: Bảng đáp ứng kỹ thuật (để cạnh yêu cầu kỹ thuật cho dễ đối chiếu) ---
            ['v' => 'Yêu cầu kỹ thuật chào giá', 's' => $HA],
            ['v' => 'Các điểm không đạt', 's' => $HA],
            // --- Mẫu 2: Bảng chào giá ---
            ['v' => 'Tên thương mại', 's' => $HA],
            ['v' => "Ký, mã, nhãn hiệu,\nmodel", 's' => $HA],
            ['v' => 'Hãng sản xuất', 's' => $HA],
            ['v' => 'Xuất xứ', 's' => $HA],
            ['v' => 'Quy cách', 's' => $HA],
            ['v' => "Đơn giá\n(VND)", 's' => $HA],
            ['v' => "Thành tiền\n(VND)", 's' => $HA],
            ['v' => "Đơn giá trúng thầu\ngần nhất (VNĐ)", 's' => $HA],
            ['v' => 'Tài liệu tham chiếu', 's' => $HA],
        ];

        $rows1 = [$r1, $r2, $r3, $r4, $rowHeader];
        $dongHienTai = count($rows1);   // dòng Excel cuối cùng đã ghi (header = dòng 5)

        // --- Dữ liệu: mỗi (hàng hóa × nhà thầu) = 1 dòng ---
        $stt = 0;
        foreach ($hangHoa as $hh) {
            $stt++;
            $dongDau = $dongHienTai + 1;
            $soDong = 0;

            foreach ($nhaThau as $nt) {
                $ch = $hh['chao'][$nt['id']] ?? null;
                $coChao = $ch && $ch['co_chao'];
                $laMin  = $coChao && $hh['nha_thau_min'] === $nt['id'];

                // 6 cột thông tin hàng hóa chỉ ghi ở dòng đầu rồi gộp dọc
                $laDongDau = ($soDong === 0);
                $rows1[] = [
                    $laDongDau ? ['v' => $stt, 's' => $C, 't' => 'n'] : ['v' => '', 's' => $C],
                    $laDongDau ? ['v' => (string)($hh['ma_hh'] ?? ''), 's' => $C] : ['v' => '', 's' => $C],
                    $laDongDau ? ['v' => (string)$hh['ten_hang_hoa'], 's' => $W] : ['v' => '', 's' => $W],
                    $laDongDau ? ['v' => (string)($hh['thong_so_ky_thuat'] ?? ''), 's' => $W] : ['v' => '', 's' => $W],
                    $laDongDau ? ['v' => (string)($hh['dvt'] ?? ''), 's' => $C] : ['v' => '', 's' => $C],
                    $laDongDau ? ['v' => (float)$hh['so_luong'], 's' => $N, 't' => 'n'] : ['v' => '', 's' => $N],
                    // --- Phần nhà thầu ---
                    ['v' => (string)$nt['ten_cong_ty'], 's' => $W],
                    ['v' => (string)($nt['ma_so_thue'] ?? ''), 's' => $C],
                    ['v' => (string)($ch['thong_so_chao_gia'] ?? ''), 's' => $W],
                    ['v' => (string)($ch['diem_khong_dat'] ?? ''), 's' => $W],
                    ['v' => (string)($ch['ten_thuong_mai'] ?? ''), 's' => $W],
                    ['v' => (string)($ch['model'] ?? ''), 's' => $W],
                    ['v' => (string)($ch['hang_san_xuat'] ?? ''), 's' => $W],
                    ['v' => (string)($ch['xuat_xu'] ?? ''), 's' => $W],
                    ['v' => (string)($ch['quy_cach'] ?? ''), 's' => $W],
                    // Đơn giá: tô vàng khi là GIÁ THẤP NHẤT của CHÍNH hàng hóa này
                    $coChao ? ['v' => $ch['don_gia'], 's' => $laMin ? $B : $M, 't' => 'n']
                            : ['v' => 'Không chào', 's' => $C],
                    $coChao ? ['v' => $ch['thanh_tien'], 's' => $M, 't' => 'n']
                            : ['v' => '', 's' => $M],
                    $coChao ? ['v' => (float)($ch['don_gia_trung_thau'] ?? 0), 's' => $M, 't' => 'n']
                            : ['v' => '', 's' => $M],
                    ['v' => (string)($ch['tai_lieu_tham_chieu'] ?? ''), 's' => $W],
                ];
                $soDong++;
                $dongHienTai++;
            }

            // Gộp dọc 6 cột thông tin hàng hóa khi có từ 2 nhà thầu trở lên
            if ($soDong > 1) {
                $dongCuoi = $dongDau + $soDong - 1;
                for ($c = 0; $c < 6; $c++) {
                    $L = ExcelHelper::colLetter($c);
                    $merges[] = $L . $dongDau . ':' . $L . $dongCuoi;
                }
            }
        }

        // --- Tổng cộng theo từng nhà thầu ---
        $rows1[] = array_fill(0, $soCot, null);

        $rowTieuDeTong = array_fill(0, $soCot, ['v' => '', 's' => $H]);
        $rowTieuDeTong[6]  = ['v' => 'TỔNG CỘNG THEO NHÀ THẦU', 's' => $H];
        $rowTieuDeTong[7]  = ['v' => 'Mã số thuế', 's' => $H];
        $rowTieuDeTong[16] = ['v' => "Tổng tiền\n(VND)", 's' => $H];
        $rows1[] = $rowTieuDeTong;

        foreach ($nhaThau as $nt) {
            $r = array_fill(0, $soCot, ['v' => '', 's' => $W]);
            $r[6]  = ['v' => (string)$nt['ten_cong_ty'], 's' => $W];
            $r[7]  = ['v' => (string)($nt['ma_so_thue'] ?? ''), 's' => $C];
            $r[16] = ['v' => (float)$nt['tong_tien'], 's' => ExcelHelper::S_TOTAL, 't' => 'n'];
            $rows1[] = $r;
        }

        // Độ rộng 19 cột — khớp thứ tự $rowHeader
        $cols1 = [
            6, 12, 36, 44, 8, 11,               // bên mời (Phụ lục III)
            30, 14,                             // nhà thầu: tên, MST
            36, 32,                             // Mẫu 1: YCKT chào giá, điểm không đạt
            26, 20, 22, 16, 18,                 // Mẫu 2: tên TM..quy cách
            17, 18, 20, 38,                     // đơn giá, thành tiền, giá TT, tài liệu (kèm số TB mời thầu)
        ];

        // =============================================================
        // SHEET 2: GIÁ THẤP NHẤT
        // =============================================================
        $rows2 = [
            [['v' => 'TỔNG HỢP GIÁ THẤP NHẤT THEO TỪNG HÀNG HOÁ', 's' => ExcelHelper::S_TITLE]],
            [['v' => 'Gói thầu: ' . $gt->so_thong_bao . ' — ' . $gt->ten_goi_thau, 's' => ExcelHelper::S_SUBTITLE]],
            [null],
            [
                ['v' => 'STT', 's' => $H],
                ['v' => 'Tên hàng hoá', 's' => $H],
                ['v' => 'ĐVT', 's' => $H],
                ['v' => 'Số lượng', 's' => $H],
                ['v' => 'Nhà thầu chào giá thấp nhất', 's' => $H],
                ['v' => "Đơn giá thấp nhất\n(VND)", 's' => $H],
                ['v' => "Thành tiền\n(VND)", 's' => $H],
                ['v' => 'Số NT tham gia', 's' => $H],
            ],
        ];

        $tenNhaThau = [];
        foreach ($nhaThau as $nt) $tenNhaThau[$nt['id']] = $nt['ten_cong_ty'];

        $stt = 0;
        foreach ($hangHoa as $hh) {
            $stt++;
            $coGia = $hh['gia_min'] !== null;
            $rows2[] = [
                ['v' => $stt, 's' => $C, 't' => 'n'],
                ['v' => (string)$hh['ten_hang_hoa'], 's' => $W],
                ['v' => (string)($hh['dvt'] ?? ''), 's' => $C],
                ['v' => (float)$hh['so_luong'], 's' => $N, 't' => 'n'],
                ['v' => $coGia ? (string)($tenNhaThau[$hh['nha_thau_min']] ?? '') : 'Chưa có nhà thầu chào', 's' => $W],
                $coGia ? ['v' => (float)$hh['gia_min'], 's' => $B, 't' => 'n'] : ['v' => '', 's' => $M],
                $coGia ? ['v' => (float)$hh['gia_min'] * (float)$hh['so_luong'], 's' => $M, 't' => 'n'] : ['v' => '', 's' => $M],
                ['v' => (int)$hh['so_nha_thau_chao'], 's' => $C, 't' => 'n'],
            ];
        }
        $rows2[] = [
            ['v' => '', 's' => $H],
            ['v' => 'TỔNG GIÁ TRỊ THEO GIÁ THẤP NHẤT', 's' => $H],
            ['v' => '', 's' => $H],
            ['v' => '', 's' => $H],
            ['v' => '', 's' => $H],
            ['v' => '', 's' => $H],
            ['v' => (float)$d['tong_ket']['tong_gia_min'], 's' => $B, 't' => 'n'],
            ['v' => '', 's' => $H],
        ];

        // =============================================================
        // SHEET 3: DANH SÁCH NHÀ THẦU
        // =============================================================
        $rows3 = [
            [['v' => 'DANH SÁCH NHÀ THẦU ĐÃ XÁC NHẬN BẢN GIẤY', 's' => ExcelHelper::S_TITLE]],
            [['v' => 'Gói thầu: ' . $gt->so_thong_bao, 's' => ExcelHelper::S_SUBTITLE]],
            [null],
            [
                ['v' => 'STT', 's' => $H],
                ['v' => 'Tên công ty', 's' => $H],
                ['v' => 'Mã số thuế', 's' => $H],
                ['v' => 'Email', 's' => $H],
                ['v' => 'Điện thoại', 's' => $H],
                ['v' => 'Địa chỉ', 's' => $H],
                ['v' => 'Ngày nộp online', 's' => $H],
                ['v' => 'Ngày xác nhận bản giấy', 's' => $H],
                ['v' => "Tổng tiền\n(VND)", 's' => $H],
            ],
        ];
        foreach ($nhaThau as $i => $nt) {
            $rows3[] = [
                ['v' => $i + 1, 's' => $C, 't' => 'n'],
                ['v' => (string)$nt['ten_cong_ty'], 's' => $W],
                ['v' => (string)($nt['ma_so_thue'] ?? ''), 's' => $C],
                ['v' => (string)($nt['email'] ?? ''), 's' => $W],
                ['v' => (string)($nt['dien_thoai'] ?? ''), 's' => $C],
                ['v' => (string)($nt['dia_chi'] ?? ''), 's' => $W],
                ['v' => Helper::formatDateTime($nt['ngay_nop']), 's' => $C],
                ['v' => Helper::formatDateTime($nt['ngay_xac_nhan']), 's' => $C],
                ['v' => (float)$nt['tong_tien'], 's' => $M, 't' => 'n'],
            ];
        }

        // =============================================================
        // GHI FILE
        // =============================================================
        $fileName = 'TongHopBaoGia_' . preg_replace('/[^A-Za-z0-9]/', '', $gt->so_thong_bao)
                  . '_' . date('Ymd_His') . '.xlsx';
        $path = BG_HangHoa_BUS::tempDir() . '/' . $fileName;

        ExcelHelper::write($path, [
            'SoSanhGia' => [
                'cols'    => $cols1,
                'freeze'  => 'D6',
                'merges'  => $merges,
                'heights' => [1 => 24, 5 => 44],
                'rows'    => $rows1,
            ],
            'GiaThapNhat' => [
                'cols'    => [6, 42, 8, 10, 34, 18, 20, 12],
                'freeze'  => 'A5',
                'heights' => [1 => 22, 4 => 40],
                'rows'    => $rows2,
            ],
            'DanhSachNhaThau' => [
                'cols'    => [6, 40, 16, 26, 16, 40, 18, 20, 18],
                'freeze'  => 'A5',
                'heights' => [1 => 22, 4 => 34],
                'rows'    => $rows3,
            ],
        ]);

        DM_NhatKyHeThong_DAL::log(
            $u, self::MODULE_LOG,
            "Xuất Excel tổng hợp báo giá gói {$gt->so_thong_bao} (" . count($nhaThau) . ' nhà thầu)',
            'bg_goi_thau', $goiThauId
        );

        return $path;
    }

    /** Xuất chi tiết 1 báo giá theo đúng 30 cột file mẫu — dùng để lưu hồ sơ */
    public static function xuatChiTietBaoGia(int $baoGiaId, int $u): string
    {
        $bg = BG_BaoGia_DAL::getById($baoGiaId);
        if (!$bg || $bg->da_xoa === 1) throw new RuntimeException('Không tìm thấy báo giá');

        $gt = BG_GoiThau_DAL::getById((int)$bg->goi_thau_id);
        $rows = BG_BaoGia_DAL::getChiTiet($baoGiaId);
        if (empty($rows)) throw new RuntimeException('Báo giá chưa có dòng chi tiết nào');

        $H = ExcelHelper::S_HEADER;
        $HA = ExcelHelper::S_HEADER_ALT;
        $W = ExcelHelper::S_TEXT_WRAP;
        $C = ExcelHelper::S_CENTER;
        $N = ExcelHelper::S_NUMBER;
        $M = ExcelHelper::S_MONEY;

        $out = [
            [['v' => 'BÁO GIÁ CHI TIẾT — ' . $bg->ten_cong_ty, 's' => ExcelHelper::S_TITLE]],
            [['v' => 'MST: ' . $bg->ma_so_thue . '   |   Gói thầu: ' . ($gt->so_thong_bao ?? '')
                   . '   |   Trạng thái: ' . BG_BaoGia_PUBLIC::tenTrangThai((int)$bg->trang_thai),
              's' => ExcelHelper::S_SUBTITLE]],
            [null],
            [
                ['v' => 'STT', 's' => $H],
                ['v' => 'Mã HH', 's' => $H],
                ['v' => 'Tên hàng hoá', 's' => $H],
                ['v' => 'Thông số KT yêu cầu', 's' => $H],
                ['v' => 'ĐVT', 's' => $H],
                ['v' => 'Số lượng', 's' => $H],
                ['v' => 'Tên thương mại', 's' => $HA],
                ['v' => "Ký, mã, nhãn hiệu,\nmodel", 's' => $HA],
                ['v' => 'Hãng sản xuất', 's' => $HA],
                ['v' => 'Xuất xứ', 's' => $HA],
                ['v' => 'Quy cách', 's' => $HA],
                ['v' => "Đơn giá\n(VND)", 's' => $HA],
                ['v' => "Thành tiền\n(VND)", 's' => $HA],
                ['v' => "Đơn giá trúng thầu\ngần nhất (VNĐ)", 's' => $HA],
                ['v' => 'Tài liệu tham chiếu', 's' => $HA],
                ['v' => 'Yêu cầu kỹ thuật chào giá', 's' => $HA],
                ['v' => 'Các điểm không đạt', 's' => $HA],
            ],
        ];

        $stt = 0;
        $tong = 0.0;
        foreach ($rows as $r) {
            $stt++;
            $tong += (float)$r['thanh_tien'];
            $out[] = [
                ['v' => $stt, 's' => $C, 't' => 'n'],
                ['v' => (string)($r['ma_hh'] ?? ''), 's' => $C],
                ['v' => (string)$r['ten_hang_hoa'], 's' => $W],
                ['v' => (string)($r['thong_so_ky_thuat'] ?? ''), 's' => $W],
                ['v' => (string)($r['dvt'] ?? ''), 's' => $C],
                ['v' => (float)$r['so_luong'], 's' => $N, 't' => 'n'],
                ['v' => (string)($r['ten_thuong_mai'] ?? ''), 's' => $W],
                ['v' => (string)($r['model'] ?? ''), 's' => $W],
                ['v' => (string)($r['hang_san_xuat'] ?? ''), 's' => $W],
                ['v' => (string)($r['xuat_xu'] ?? ''), 's' => $W],
                ['v' => (string)($r['quy_cach'] ?? ''), 's' => $W],
                ['v' => (float)$r['don_gia'], 's' => $M, 't' => 'n'],
                ['v' => (float)$r['thanh_tien'], 's' => $M, 't' => 'n'],
                ['v' => (float)$r['don_gia_trung_thau'], 's' => $M, 't' => 'n'],
                ['v' => (string)($r['tai_lieu_tham_chieu'] ?? ''), 's' => $W],
                ['v' => (string)($r['thong_so_chao_gia'] ?? ''), 's' => $W],
                ['v' => (string)($r['diem_khong_dat'] ?? ''), 's' => $W],
            ];
        }

        $rowTong = array_fill(0, 18, ['v' => '', 's' => $H]);
        $rowTong[2] = ['v' => 'TỔNG CỘNG', 's' => $H];
        $rowTong[12] = ['v' => $tong, 's' => ExcelHelper::S_BEST, 't' => 'n'];
        $out[] = $rowTong;

        $fileName = 'BaoGia_' . preg_replace('/[^A-Za-z0-9]/', '', (string)$bg->ma_so_thue)
                  . '_' . date('Ymd_His') . '.xlsx';
        $path = BG_HangHoa_BUS::tempDir() . '/' . $fileName;

        ExcelHelper::write($path, [
            'ChiTietBaoGia' => [
                'cols'    => [6, 12, 36, 42, 8, 11, 26, 20, 22, 16, 18, 17, 18, 20, 38, 36, 32],
                'freeze'  => 'C5',
                'heights' => [1 => 24, 4 => 44],
                'rows'    => $out,
            ],
        ]);

        DM_NhatKyHeThong_DAL::log(
            $u, self::MODULE_LOG, "Xuất chi tiết báo giá: {$bg->ten_cong_ty}", 'bg_bao_gia', $baoGiaId
        );
        return $path;
    }
}
