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
        $nhomGt  = BG_Nhom_PUBLIC::chuanHoa($gt->nhom ?? null);

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
                    'nam_san_xuat'          => $ct['nam_san_xuat'] ?? '',
                    // Mau 1 — cac cap dap ung / khong dat theo nhom
                    'thong_so_chao_gia'     => $ct['thong_so_chao_gia'] ?? '',
                    'diem_khong_dat'        => $ct['diem_khong_dat'] ?? '',
                    'dap_ung_chung'         => $ct['dap_ung_chung'] ?? '',
                    'khong_dat_chung'       => $ct['khong_dat_chung'] ?? '',
                    'dap_ung_khac'          => $ct['dap_ung_khac'] ?? '',
                    'khong_dat_khac'        => $ct['khong_dat_khac'] ?? '',
                    'dap_ung_cau_hinh'      => $ct['dap_ung_cau_hinh'] ?? '',
                    'khong_dat_cau_hinh'    => $ct['khong_dat_cau_hinh'] ?? '',
                    'dap_ung_nhom_nuoc'     => $ct['dap_ung_nhom_nuoc'] ?? '',
                    'khong_dat_nhom_nuoc'   => $ct['khong_dat_nhom_nuoc'] ?? '',
                    'tai_lieu_chung_minh'   => $ct['tai_lieu_chung_minh'] ?? '',
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
                // Thong tin BO — de bang tong hop gom theo bo, nhin ro co cau
                'bo_id'             => (int)($hh['bo_id'] ?? 0),
                'stt_bo'            => $hh['stt_bo'] ?? null,
                'ten_bo'            => $hh['ten_bo'] ?? null,
                'ma_bo'             => $hh['ma_bo'] ?? null,
                'stt_chi_tiet'      => $hh['stt_chi_tiet'] ?? null,
                // Thong so cua BO — hien duoi ten bo o dong tieu de
                'yeu_cau_chung'     => $hh['yeu_cau_chung'] ?? null,
                'yeu_cau_khac'      => $hh['yeu_cau_khac'] ?? null,
                'yeu_cau_cau_hinh'  => $hh['yeu_cau_cau_hinh'] ?? null,
                'nhom_nuoc_bo'      => $hh['nhom_nuoc_bo'] ?? null,
                'dvt_bo'            => $hh['dvt_bo'] ?? null,
                'so_luong_bo'       => isset($hh['so_luong_bo']) ? (float)$hh['so_luong_bo'] : null,
                'thong_so_ky_thuat' => $hh['thong_so_ky_thuat'],
                'nhom_nuoc'         => $hh['nhom_nuoc'] ?? null,
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
            // Nhom quyet dinh hien yeu cau chung/khac/cau hinh nao o dong BO
            'nhom'     => $nhomGt,
            'ten_nhom' => BG_Nhom_PUBLIC::tenNhom($nhomGt),
            'co_yc_chung'    => BG_Nhom_PUBLIC::coYeuCauChung($nhomGt),
            'co_yc_cau_hinh' => BG_Nhom_PUBLIC::coYeuCauCauHinh($nhomGt),
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
        $nhom    = (string)($d['nhom'] ?? BG_Nhom_PUBLIC::MAC_DINH);

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
        // SHEET 1: SO SÁNH GIÁ — MỖI NHÀ THẦU MỘT DÒNG, GOM THEO BỘ
        // =============================================================
        // Bố cục: 1 hàng hóa có N nhà thầu -> N dòng liên tiếp, tên nhà thầu và
        // MST nằm thành CỘT trên chính dòng đó. Đọc ngang là ra ai chào bao
        // nhiêu; lọc / PivotTable trong Excel đều dùng được.
        //
        // Mỗi BỘ có 1 dòng tiêu đề riêng mang yêu cầu chung/khác/cấu hình —
        // các yêu cầu này gắn với BỘ chứ không gắn với từng hàng hóa (Phụ lục III).

        // --- Dựng header động theo NHÓM ---
        $capDapUng = BG_Nhom_PUBLIC::capDapUng($nhom);

        $hdr  = [];
        $cols = [];
        $them = function (string $ten, string $style, int $rong) use (&$hdr, &$cols) {
            $hdr[]  = ['v' => $ten, 's' => $style];
            $cols[] = $rong;
        };

        // Cột bên mời (Phụ lục III)
        $them('STT', $H, 6);
        $them('Mã', $H, 13);
        $them('Tên bộ / hàng hóa chi tiết', $H, 40);
        $them('Yêu cầu kỹ thuật mời chào giá', $H, 42);
        $them('Nhóm nước, vùng lãnh thổ', $H, 18);
        $them('ĐVT', $H, 8);
        $them("Số lượng /\nkhối lượng", $H, 11);
        $soCotMoi = count($hdr);   // số cột bên mời — dùng để gộp dọc

        // Cột nhà thầu
        $them('Nhà thầu', $HA, 30);
        $them('Mã số thuế', $HA, 14);

        // Mẫu 1 — các cặp đáp ứng, SỐ LƯỢNG THAY ĐỔI THEO NHÓM
        foreach ($capDapUng as $c) {
            $them('Đáp ứng về ' . mb_strtolower($c[0]), $HA, 34);
            $them('Không đáp ứng về ' . mb_strtolower($c[0]), $HA, 30);
        }
        $them('Tài liệu chứng minh', $HA, 30);

        // Mẫu 2 — thông tin chào giá
        $them('Tên thương mại', $HA, 26);
        $them("Ký, mã, nhãn hiệu,\nmodel", $HA, 20);
        $them('Hãng sản xuất', $HA, 22);
        $them('Năm sản xuất', $HA, 12);
        $them('Xuất xứ', $HA, 16);
        $them("Đơn giá\n(VND)", $HA, 17);
        $them("Thành tiền\n(VND)", $HA, 18);

        $soCot   = count($hdr);
        $colCuoi = ExcelHelper::colLetter($soCot - 1);
        $merges  = [];

        // Vị trí 2 cột tiền — dùng cho dòng TỔNG CỘNG ở cuối
        $iDonGia    = $soCot - 2;
        $iThanhTien = $soCot - 1;

        // --- Tiêu đề ---
        $r1 = array_fill(0, $soCot, ['v' => '', 's' => ExcelHelper::S_TITLE]);
        $r1[0] = ['v' => 'BẢNG TỔNG HỢP BÁO GIÁ', 's' => ExcelHelper::S_TITLE];

        $r2 = array_fill(0, $soCot, ['v' => '', 's' => ExcelHelper::S_SUBTITLE]);
        $r2[0] = ['v' => 'Thông báo mời chào giá số ' . $gt->so_thong_bao . ' — ' . $gt->ten_goi_thau,
                  's' => ExcelHelper::S_SUBTITLE];

        $r3 = array_fill(0, $soCot, ['v' => '', 's' => ExcelHelper::S_SUBTITLE]);
        $r3[0] = ['v' => 'Nhóm: ' . BG_Nhom_PUBLIC::tenNhom($nhom)
                       . '   |   Số nhà thầu đã duyệt: ' . count($nhaThau)
                       . '   |   Số hàng hoá: ' . count($hangHoa)
                       . '   |   Xuất lúc: ' . date('d/m/Y H:i'),
                  's' => ExcelHelper::S_SUBTITLE];

        $r4 = array_fill(0, $soCot, null);

        $merges[] = 'A1:' . $colCuoi . '1';
        $merges[] = 'A2:' . $colCuoi . '2';
        $merges[] = 'A3:' . $colCuoi . '3';

        $rows1 = [$r1, $r2, $r3, $r4, $hdr];
        $dongHienTai = count($rows1);   // dòng Excel cuối đã ghi (header = dòng 5)

        // --- Dữ liệu ---
        $stt = 0;
        $boHienTai = null;

        foreach ($hangHoa as $hh) {
            // ===== Dòng tiêu đề BỘ =====
            $boId = (int)($hh['bo_id'] ?? 0);
            if ($boId !== $boHienTai) {
                $boHienTai = $boId;
                if ($boId > 0 && !empty($hh['ten_bo'])) {
                    // Gộp yêu cầu cấp bộ vào 1 ô — chỉ lấy phần nhóm này dùng
                    $yc = [];
                    if (BG_Nhom_PUBLIC::coYeuCauChung($nhom) && !empty($hh['yeu_cau_chung'])) {
                        $yc[] = 'YÊU CẦU CHUNG: ' . $hh['yeu_cau_chung'];
                    }
                    if (BG_Nhom_PUBLIC::coYeuCauKhac($nhom) && !empty($hh['yeu_cau_khac'])) {
                        $yc[] = 'YÊU CẦU KHÁC: ' . $hh['yeu_cau_khac'];
                    }
                    if (BG_Nhom_PUBLIC::coYeuCauCauHinh($nhom) && !empty($hh['yeu_cau_cau_hinh'])) {
                        $yc[] = 'YÊU CẦU CẤU HÌNH: ' . $hh['yeu_cau_cau_hinh'];
                    }

                    $rBo = array_fill(0, $soCot, ['v' => '', 's' => $H]);
                    $rBo[0] = ['v' => (string)($hh['stt_bo'] ?? ''), 's' => $C];
                    $rBo[1] = ['v' => (string)($hh['ma_bo'] ?? ''), 's' => $C];
                    $rBo[2] = ['v' => (string)$hh['ten_bo'], 's' => $H];
                    $rBo[3] = ['v' => implode("\n", $yc), 's' => $W];
                    $rBo[4] = ['v' => (string)($hh['nhom_nuoc_bo'] ?? ''), 's' => $C];
                    $rBo[5] = ['v' => (string)($hh['dvt_bo'] ?? ''), 's' => $C];
                    $rBo[6] = isset($hh['so_luong_bo'])
                        ? ['v' => (float)$hh['so_luong_bo'], 's' => $N, 't' => 'n']
                        : ['v' => '', 's' => $N];

                    $rows1[] = $rBo;
                    $dongHienTai++;
                }
            }

            // ===== Các dòng chào giá của hàng hóa =====
            $stt++;
            $dongDau = $dongHienTai + 1;
            $soDong  = 0;

            foreach ($nhaThau as $nt) {
                $ch = $hh['chao'][$nt['id']] ?? null;
                $coChao = $ch && $ch['co_chao'];
                $laMin  = $coChao && $hh['nha_thau_min'] === $nt['id'];

                // Cột bên mời chỉ ghi ở dòng đầu rồi gộp dọc
                $laDongDau = ($soDong === 0);
                $o = static fn($v, $s) => ['v' => $v, 's' => $s];

                $dong = [
                    $laDongDau ? ['v' => $stt, 's' => $C, 't' => 'n'] : $o('', $C),
                    $laDongDau ? $o((string)($hh['ma_hh'] ?? ''), $C) : $o('', $C),
                    $laDongDau ? $o((string)$hh['ten_hang_hoa'], $W) : $o('', $W),
                    $laDongDau ? $o((string)($hh['thong_so_ky_thuat'] ?? ''), $W) : $o('', $W),
                    $laDongDau ? $o((string)($hh['nhom_nuoc'] ?? ''), $C) : $o('', $C),
                    $laDongDau ? $o((string)($hh['dvt'] ?? ''), $C) : $o('', $C),
                    $laDongDau ? ['v' => (float)$hh['so_luong'], 's' => $N, 't' => 'n'] : $o('', $N),
                    // --- Nhà thầu ---
                    $o((string)$nt['ten_cong_ty'], $W),
                    $o((string)($nt['ma_so_thue'] ?? ''), $C),
                ];

                // Mẫu 1 — các cặp đáp ứng theo nhóm
                foreach ($capDapUng as $c) {
                    $dong[] = $o((string)($ch[$c[1]] ?? ''), $W);
                    $dong[] = $o((string)($ch[$c[2]] ?? ''), $W);
                }
                $dong[] = $o((string)($ch['tai_lieu_chung_minh'] ?? ''), $W);

                // Mẫu 2
                $dong[] = $o((string)($ch['ten_thuong_mai'] ?? ''), $W);
                $dong[] = $o((string)($ch['model'] ?? ''), $W);
                $dong[] = $o((string)($ch['hang_san_xuat'] ?? ''), $W);
                $dong[] = $o((string)($ch['nam_san_xuat'] ?? ''), $C);
                $dong[] = $o((string)($ch['xuat_xu'] ?? ''), $W);
                // Đơn giá: tô vàng khi là GIÁ THẤP NHẤT của chính hàng hóa này
                $dong[] = $coChao ? ['v' => $ch['don_gia'], 's' => $laMin ? $B : $M, 't' => 'n']
                                  : $o('Không chào', $C);
                $dong[] = $coChao ? ['v' => $ch['thanh_tien'], 's' => $M, 't' => 'n']
                                  : $o('', $M);

                $rows1[] = $dong;
                $soDong++;
                $dongHienTai++;
            }

            // Gộp dọc các cột bên mời khi có từ 2 nhà thầu trở lên
            if ($soDong > 1) {
                $dongCuoi = $dongDau + $soDong - 1;
                for ($c = 0; $c < $soCotMoi; $c++) {
                    $L = ExcelHelper::colLetter($c);
                    $merges[] = $L . $dongDau . ':' . $L . $dongCuoi;
                }
            }
        }

        // --- Tổng cộng theo từng nhà thầu ---
        $rows1[] = array_fill(0, $soCot, null);

        $rowTieuDeTong = array_fill(0, $soCot, ['v' => '', 's' => $H]);
        $rowTieuDeTong[$soCotMoi]     = ['v' => 'TỔNG CỘNG THEO NHÀ THẦU', 's' => $H];
        $rowTieuDeTong[$soCotMoi + 1] = ['v' => 'Mã số thuế', 's' => $H];
        $rowTieuDeTong[$iThanhTien]   = ['v' => "Tổng tiền\n(VND)", 's' => $H];
        $rows1[] = $rowTieuDeTong;

        foreach ($nhaThau as $nt) {
            $r = array_fill(0, $soCot, ['v' => '', 's' => $W]);
            $r[$soCotMoi]     = ['v' => (string)$nt['ten_cong_ty'], 's' => $W];
            $r[$soCotMoi + 1] = ['v' => (string)($nt['ma_so_thue'] ?? ''), 's' => $C];
            $r[$iThanhTien]   = ['v' => (float)$nt['tong_tien'], 's' => ExcelHelper::S_TOTAL, 't' => 'n'];
            $rows1[] = $r;
        }

        $cols1 = $cols;

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
