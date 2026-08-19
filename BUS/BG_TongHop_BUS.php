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
                $chao[$bgId] = [
                    'don_gia'             => $donGia,
                    'thanh_tien'          => $ct ? (float)$ct['thanh_tien'] : 0.0,
                    'thue_vat'            => $ct ? (float)$ct['thue_vat'] : 0.0,
                    'chi_phi_dich_vu'     => $ct ? (float)$ct['chi_phi_dich_vu'] : 0.0,
                    'ten_thuong_mai'      => $ct['ten_thuong_mai'] ?? '',
                    'model'               => $ct['model'] ?? '',
                    'ma_hs'               => $ct['ma_hs'] ?? '',
                    'hang_san_xuat'       => $ct['hang_san_xuat'] ?? '',
                    'xuat_xu'             => $ct['xuat_xu'] ?? '',
                    'quy_cach'            => $ct['quy_cach'] ?? '',
                    'chung_nhan_chao'     => $ct['chung_nhan_chao'] ?? '',
                    'don_gia_trung_thau'  => $ct ? (float)$ct['don_gia_trung_thau'] : 0.0,
                    'tai_lieu_tham_chieu' => $ct['tai_lieu_tham_chieu'] ?? '',
                    'ma_qr_hang_hoa'      => $ct['ma_qr_hang_hoa'] ?? '',
                    'thong_so_chao_gia'   => $ct['thong_so_chao_gia'] ?? '',
                    'diem_khong_dat'      => $ct['diem_khong_dat'] ?? '',
                    'co_chao'             => $donGia > 0,
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

            // Giữ ĐỦ cột yêu cầu của bên mời (khớp bg_hang_hoa, cột A-K file mẫu)
            $rows[] = [
                'id'                => $hhId,
                'ten_phan'          => $hh['ten_phan'],
                'stt_theo_phan'     => $hh['stt_theo_phan'],
                'stt_thong_bao'     => $hh['stt_thong_bao'],
                'ten_hang_hoa'      => $hh['ten_hang_hoa'],
                'thong_so_ky_thuat' => $hh['thong_so_ky_thuat'],
                'chung_nhan'        => $hh['chung_nhan'],
                'yeu_cau_xuat_xu'   => $hh['yeu_cau_xuat_xu'],
                'dvt'               => $hh['dvt'],
                'so_luong'          => $soLuong,
                'yeu_cau_tro_cu'    => $hh['yeu_cau_tro_cu'],
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
        $soCot = 30;   // khớp với $rowHeader bên dưới (12 bên mời + 18 nhà thầu)
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
        // Header: 6 cột bên mời + 18 cột nhà thầu (đủ mọi trường nhà thầu điền)
        $rowHeader = [
            // --- Thông tin mời chào giá (cột A-K file mẫu) ---
            ['v' => 'STT', 's' => $H],
            ['v' => 'Tên phần', 's' => $H],
            ['v' => 'STT phần', 's' => $H],
            ['v' => "STT TB
mời chào giá", 's' => $H],
            ['v' => 'Tên hàng hoá', 's' => $H],
            ['v' => 'Tính năng, thông số kỹ thuật yêu cầu', 's' => $H],
            ['v' => 'Chứng nhận', 's' => $H],
            ['v' => 'Yêu cầu xuất xứ', 's' => $H],
            ['v' => 'ĐVT', 's' => $H],
            ['v' => "Số lượng /\nKhối lượng", 's' => $H],
            ['v' => "Yêu cầu về trợ cụ /\nmáy phụ trợ", 's' => $H],
            ['v' => "Số thông báo\nmời chào giá", 's' => $H],
            // --- Nhà thầu ---
            ['v' => 'Nhà thầu', 's' => $HA],
            ['v' => 'Mã số thuế', 's' => $HA],
            ['v' => 'Tên thương mại', 's' => $HA],
            ['v' => "Ký, mã, nhãn hiệu, model", 's' => $HA],
            ['v' => 'Mã HS', 's' => $HA],
            ['v' => 'Hãng sản xuất', 's' => $HA],
            ['v' => 'Xuất xứ', 's' => $HA],
            ['v' => 'Quy cách', 's' => $HA],
            ['v' => "Chi phí dịch vụ\n(VND)", 's' => $HA],
            ['v' => "Thuế VAT\n(%)", 's' => $HA],
            ['v' => "Đơn giá\n(VND)", 's' => $HA],
            ['v' => "Thành tiền\n(VND)", 's' => $HA],
            ['v' => 'Chứng nhận hàng hoá chào', 's' => $HA],
            ['v' => "Đơn giá trúng thầu gần nhất\n(VND)", 's' => $HA],
            ['v' => 'Tài liệu tham chiếu', 's' => $HA],
            ['v' => 'Mã QR / Barcode', 's' => $HA],
            ['v' => 'Thông số kỹ thuật chào giá', 's' => $HA],
            ['v' => 'Các điểm không đạt', 's' => $HA],
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
                    $laDongDau ? ['v' => (string)($hh['ten_phan'] ?? ''), 's' => $C] : ['v' => '', 's' => $C],
                    $laDongDau ? ['v' => (string)($hh['stt_theo_phan'] ?? ''), 's' => $C] : ['v' => '', 's' => $C],
                    $laDongDau ? ['v' => (string)($hh['stt_thong_bao'] ?? ''), 's' => $C] : ['v' => '', 's' => $C],
                    $laDongDau ? ['v' => (string)$hh['ten_hang_hoa'], 's' => $W] : ['v' => '', 's' => $W],
                    $laDongDau ? ['v' => (string)($hh['thong_so_ky_thuat'] ?? ''), 's' => $W] : ['v' => '', 's' => $W],
                    $laDongDau ? ['v' => (string)($hh['chung_nhan'] ?? ''), 's' => $W] : ['v' => '', 's' => $W],
                    $laDongDau ? ['v' => (string)($hh['yeu_cau_xuat_xu'] ?? ''), 's' => $W] : ['v' => '', 's' => $W],
                    $laDongDau ? ['v' => (string)($hh['dvt'] ?? ''), 's' => $C] : ['v' => '', 's' => $C],
                    $laDongDau ? ['v' => (float)$hh['so_luong'], 's' => $N, 't' => 'n'] : ['v' => '', 's' => $N],
                    $laDongDau ? ['v' => (string)($hh['yeu_cau_tro_cu'] ?? ''), 's' => $W] : ['v' => '', 's' => $W],
                    // Cột K file mẫu = số thông báo của GÓI THẦU (mọi dòng như nhau),
                    // khác cột C là số thứ tự trong thông báo.
                    $laDongDau ? ['v' => 'Thông báo số ' . $gt->so_thong_bao, 's' => $W] : ['v' => '', 's' => $W],
                    // --- Phần nhà thầu: đủ mọi cột đã nộp ---
                    ['v' => (string)$nt['ten_cong_ty'], 's' => $W],
                    ['v' => (string)($nt['ma_so_thue'] ?? ''), 's' => $C],
                    ['v' => (string)($ch['ten_thuong_mai'] ?? ''), 's' => $W],
                    ['v' => (string)($ch['model'] ?? ''), 's' => $W],
                    ['v' => (string)($ch['ma_hs'] ?? ''), 's' => $C],
                    ['v' => (string)($ch['hang_san_xuat'] ?? ''), 's' => $W],
                    ['v' => (string)($ch['xuat_xu'] ?? ''), 's' => $W],
                    ['v' => (string)($ch['quy_cach'] ?? ''), 's' => $W],
                    $coChao ? ['v' => (float)($ch['chi_phi_dich_vu'] ?? 0), 's' => $M, 't' => 'n']
                            : ['v' => '', 's' => $M],
                    $coChao ? ['v' => (float)($ch['thue_vat'] ?? 0), 's' => $C, 't' => 'n']
                            : ['v' => '', 's' => $C],
                    // Đơn giá: tô vàng khi là GIÁ THẤP NHẤT của CHÍNH hàng hóa này
                    $coChao ? ['v' => $ch['don_gia'], 's' => $laMin ? $B : $M, 't' => 'n']
                            : ['v' => 'Không chào', 's' => $C],
                    $coChao ? ['v' => $ch['thanh_tien'], 's' => $M, 't' => 'n']
                            : ['v' => '', 's' => $M],
                    ['v' => (string)($ch['chung_nhan_chao'] ?? ''), 's' => $W],
                    $coChao ? ['v' => (float)($ch['don_gia_trung_thau'] ?? 0), 's' => $M, 't' => 'n']
                            : ['v' => '', 's' => $M],
                    ['v' => (string)($ch['tai_lieu_tham_chieu'] ?? ''), 's' => $W],
                    ['v' => (string)($ch['ma_qr_hang_hoa'] ?? ''), 's' => $W],
                    ['v' => (string)($ch['thong_so_chao_gia'] ?? ''), 's' => $W],
                    ['v' => (string)($ch['diem_khong_dat'] ?? ''), 's' => $W],
                ];
                $soDong++;
                $dongHienTai++;
            }

            // Gộp dọc 12 cột thông tin hàng hóa khi có từ 2 nhà thầu trở lên
            if ($soDong > 1) {
                $dongCuoi = $dongDau + $soDong - 1;
                for ($c = 0; $c < 12; $c++) {
                    $L = ExcelHelper::colLetter($c);
                    $merges[] = $L . $dongDau . ':' . $L . $dongCuoi;
                }
            }
        }

        // --- Tổng cộng theo từng nhà thầu ---
        $rows1[] = array_fill(0, $soCot, null);

        $rowTieuDeTong = array_fill(0, $soCot, ['v' => '', 's' => $H]);
        $rowTieuDeTong[12] = ['v' => 'TỔNG CỘNG THEO NHÀ THẦU', 's' => $H];
        $rowTieuDeTong[13] = ['v' => 'Mã số thuế', 's' => $H];
        $rowTieuDeTong[22] = ['v' => "Tổng tiền\n(VND)", 's' => $H];
        $rows1[] = $rowTieuDeTong;

        foreach ($nhaThau as $nt) {
            $r = array_fill(0, $soCot, ['v' => '', 's' => $W]);
            $r[12] = ['v' => (string)$nt['ten_cong_ty'], 's' => $W];
            $r[13] = ['v' => (string)($nt['ma_so_thue'] ?? ''), 's' => $C];
            $r[23] = ['v' => (float)$nt['tong_tien'], 's' => ExcelHelper::S_TOTAL, 't' => 'n'];
            $rows1[] = $r;
        }

        // Độ rộng 30 cột — khớp thứ tự $rowHeader
        $cols1 = [
            6, 12, 10, 11, 32, 40, 26, 24, 8, 11, 30, 20,   // bên mời (A-L)
            30, 14, 26, 20, 12, 22, 16, 18,             // nhà thầu: tên..quy cách
            14, 9, 16, 18,                              // chi phí, VAT, đơn giá, thành tiền
            26, 18, 30, 22, 36, 32,                     // chứng nhận .. điểm không đạt
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
                ['v' => 'STT phần', 's' => $H],
                ['v' => 'Tên hàng hoá', 's' => $H],
                ['v' => 'Thông số KT yêu cầu', 's' => $H],
                ['v' => 'ĐVT', 's' => $H],
                ['v' => 'Số lượng', 's' => $H],
                ['v' => 'Tên thương mại', 's' => $HA],
                ['v' => 'Model', 's' => $HA],
                ['v' => 'Mã HS', 's' => $HA],
                ['v' => 'Hãng sản xuất', 's' => $HA],
                ['v' => 'Xuất xứ', 's' => $HA],
                ['v' => 'Quy cách', 's' => $HA],
                ['v' => "Chi phí DV\n(VND)", 's' => $HA],
                ['v' => "VAT\n(%)", 's' => $HA],
                ['v' => "Đơn giá\n(VND)", 's' => $HA],
                ['v' => "Thành tiền\n(VND)", 's' => $HA],
                ['v' => 'Chứng nhận hàng chào', 's' => $HA],
                ['v' => "Giá trúng thầu gần nhất\n(VND)", 's' => $HA],
                ['v' => 'Tài liệu tham chiếu', 's' => $HA],
                ['v' => 'Mã QR/Barcode', 's' => $HA],
                ['v' => 'Thông số kỹ thuật chào giá', 's' => $HA],
                ['v' => 'Điểm không đạt', 's' => $HA],
            ],
        ];

        $stt = 0;
        $tong = 0.0;
        foreach ($rows as $r) {
            $stt++;
            $tong += (float)$r['thanh_tien'];
            $out[] = [
                ['v' => $stt, 's' => $C, 't' => 'n'],
                ['v' => (string)($r['stt_theo_phan'] ?? ''), 's' => $C],
                ['v' => (string)$r['ten_hang_hoa'], 's' => $W],
                ['v' => (string)($r['thong_so_ky_thuat'] ?? ''), 's' => $W],
                ['v' => (string)($r['dvt'] ?? ''), 's' => $C],
                ['v' => (float)$r['so_luong'], 's' => $N, 't' => 'n'],
                ['v' => (string)($r['ten_thuong_mai'] ?? ''), 's' => $W],
                ['v' => (string)($r['model'] ?? ''), 's' => $W],
                ['v' => (string)($r['ma_hs'] ?? ''), 's' => $C],
                ['v' => (string)($r['hang_san_xuat'] ?? ''), 's' => $W],
                ['v' => (string)($r['xuat_xu'] ?? ''), 's' => $W],
                ['v' => (string)($r['quy_cach'] ?? ''), 's' => $W],
                ['v' => (float)$r['chi_phi_dich_vu'], 's' => $M, 't' => 'n'],
                ['v' => (float)$r['thue_vat'], 's' => $C, 't' => 'n'],
                ['v' => (float)$r['don_gia'], 's' => $M, 't' => 'n'],
                ['v' => (float)$r['thanh_tien'], 's' => $M, 't' => 'n'],
                ['v' => (string)($r['chung_nhan_chao'] ?? ''), 's' => $W],
                ['v' => (float)$r['don_gia_trung_thau'], 's' => $M, 't' => 'n'],
                ['v' => (string)($r['tai_lieu_tham_chieu'] ?? ''), 's' => $W],
                ['v' => (string)($r['ma_qr_hang_hoa'] ?? ''), 's' => $W],
                ['v' => (string)($r['thong_so_chao_gia'] ?? ''), 's' => $W],
                ['v' => (string)($r['diem_khong_dat'] ?? ''), 's' => $W],
            ];
        }

        $rowTong = array_fill(0, 22, ['v' => '', 's' => $H]);
        $rowTong[2] = ['v' => 'TỔNG CỘNG', 's' => $H];
        $rowTong[15] = ['v' => $tong, 's' => ExcelHelper::S_BEST, 't' => 'n'];
        $out[] = $rowTong;

        $fileName = 'BaoGia_' . preg_replace('/[^A-Za-z0-9]/', '', (string)$bg->ma_so_thue)
                  . '_' . date('Ymd_His') . '.xlsx';
        $path = BG_HangHoa_BUS::tempDir() . '/' . $fileName;

        ExcelHelper::write($path, [
            'ChiTietBaoGia' => [
                'cols'    => [6, 10, 36, 40, 8, 10, 26, 18, 12, 22, 16, 18, 14, 8, 16, 18, 26, 18, 30, 20, 36, 32],
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
