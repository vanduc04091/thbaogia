<?php
/**
 * seed_nhom_bo.php — Dữ liệu test cho 2 nhóm gói thầu còn lại.
 *
 * seed_bao_gia.php cũ chỉ tạo gói nhóm `vat_tu_duoc` (và tạo hàng hóa KHÔNG
 * thuộc bộ nào — viết trước khi có cấu trúc bộ). File này bổ sung:
 *
 *   1. BỘ Y DỤNG CỤ      — 2 bộ dụng cụ phẫu thuật, có yêu cầu chung + khác
 *   2. HỆ THỐNG TBYT     — 2 hệ thống thiết bị, thêm yêu cầu cấu hình
 *
 * Mỗi gói kèm 3 nhà thầu chào giá ở các mức khác nhau, đủ trạng thái để test
 * bảng tổng hợp: đã duyệt / chờ duyệt / có dòng không chào.
 *
 * Chạy:  php database/seed_nhom_bo.php
 *        php database/seed_nhom_bo.php --reset   (xóa 2 gói này rồi tạo lại)
 *
 * CHỈ chạy khi APP_DEBUG = true. Chạy nhiều lần an toàn (bỏ qua nếu đã có).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Script nay chi chay bang dong lenh (CLI).\n");
}

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../BUS/BG_GoiThau_BUS.php';
require_once __DIR__ . '/../BUS/BG_HangHoa_BUS.php';
require_once __DIR__ . '/../BUS/BG_BaoGia_BUS.php';
require_once __DIR__ . '/../DAL/BG_Bo_DAL.php';

if (!AppConfig::APP_DEBUG) {
    fwrite(STDERR, "Từ chối: seed chỉ chạy khi APP_DEBUG = true.\n");
    exit(1);
}

$doReset = in_array('--reset', $argv ?? [], true);
$pdo   = Database::getConnection();
$admin = 1;

function say(string $m = ''): void { echo $m . "\n"; }

// =====================================================================
// ĐỊNH NGHĨA DỮ LIỆU
// =====================================================================

/**
 * Cấu trúc 1 gói: thông tin gói + danh sách bộ, mỗi bộ có hàng hóa chi tiết.
 * Yêu cầu chung/khác/cấu hình đặt ở BỘ; yêu cầu kỹ thuật đặt ở CHI TIẾT
 * — đúng như Phụ lục III Thư mời.
 */
$goiThau = [
    // ---------------------------------------------------------------
    // NHÓM 1: BỘ Y DỤNG CỤ
    // ---------------------------------------------------------------
    [
        'so_thong_bao' => '5901/2026',
        'ten_goi_thau' => 'Mua bộ dụng cụ phẫu thuật sọ não và chi dưới năm 2026',
        'nhom'         => BG_Nhom_PUBLIC::BO_DUNG_CU,
        'noi_dung'     => 'Bộ dụng cụ phẫu thuật sọ não; Bộ dụng cụ phẫu thuật chi dưới',
        'ngay_mo'      => '-5 days',
        'ngay_dong'    => '+15 days',
        'hop_dong'     => 24,
        'hieu_luc'     => 180,
        'bo' => [
            [
                'ma_bo'  => 'BDC01',
                'ten_bo' => 'BỘ DỤNG CỤ PHẪU THUẬT SỌ NÃO',
                'yeu_cau_chung' => "Bộ dụng cụ đồng bộ, cùng một hãng sản xuất.\n"
                                 . "Chất liệu thép không gỉ y tế, tiệt trùng được bằng hấp ướt 134°C.\n"
                                 . "Có khay/hộp đựng chuyên dụng kèm theo.",
                'yeu_cau_khac'  => "Bảo hành tối thiểu 12 tháng.\n"
                                 . "Có tài liệu hướng dẫn sử dụng và vệ sinh tiệt trùng bằng tiếng Việt.",
                'nhom_nuoc' => 'Nhóm G7, EU',
                'dvt' => 'Bộ', 'so_luong' => 2,
                'chi_tiet' => [
                    ['Khớp nối cố định thanh đỡ hệ thống vén não với ray bên bàn mổ',
                     "Chất liệu thép không gỉ.\nKẹp chắc vào ray bàn mổ tiêu chuẩn.\nXoay được 360°.", 'Cái', 2],
                    ['Thanh đỡ hệ thống vén não',
                     "Chiều dài ≥ 30cm.\nCó khớp mềm dẻo điều chỉnh nhiều hướng, khóa cố định chắc chắn.", 'Cái', 2],
                    ['Van vén não các cỡ',
                     "Bộ gồm ít nhất 3 cỡ khác nhau.\nBề mặt nhẵn, bo tròn không gây tổn thương mô não.", 'Bộ', 2],
                    ['Kìm gặm xương Kerrison',
                     "Góc 40°, bản rộng 2mm và 3mm.\nLưỡi cắt sắc, tháo rời vệ sinh được.", 'Cái', 4],
                ],
            ],
            [
                'ma_bo'  => 'BDC02',
                'ten_bo' => 'BỘ DỤNG CỤ PHẪU THUẬT CHI DƯỚI',
                'yeu_cau_chung' => "Bộ dụng cụ đồng bộ, cùng một hãng sản xuất.\n"
                                 . "Chất liệu thép không gỉ y tế, chịu được hấp tiệt trùng lặp lại.",
                'yeu_cau_khac'  => "Bảo hành tối thiểu 12 tháng.\nGiao hàng kèm khay đựng inox.",
                'nhom_nuoc' => 'Nhóm G7, EU, Úc',
                'dvt' => 'Bộ', 'so_luong' => 3,
                'chi_tiet' => [
                    ['Cán dao mổ số 3', "Thép không gỉ.\nKhía chống trượt.\nLắp vừa lưỡi dao số 10-15.", 'Cái', 3],
                    ['Cán dao mổ số 4', "Thép không gỉ.\nKhía chống trượt.\nLắp vừa lưỡi dao số 20-24.", 'Cái', 3],
                    ['Ống hút Yankauer', "Thép không gỉ.\nĐầu bo tròn, có lỗ bên.\nDài ≥ 25cm.", 'Cái', 6],
                    ['Kẹp phẫu tích có mấu', "Dài 16cm và 20cm.\nĐầu kẹp khít, không lệch.", 'Cái', 6],
                    ['Kéo phẫu thuật Mayo cong', "Dài 17cm.\nLưỡi cong, cắt ngọt, không kẹt.", 'Cái', 3],
                ],
            ],
        ],
        'gia_co_so' => [2400000, 3800000, 5200000, 4100000],
    ],

    // ---------------------------------------------------------------
    // NHÓM 2: HỆ THỐNG MÁY, THIẾT BỊ Y TẾ
    // ---------------------------------------------------------------
    [
        'so_thong_bao' => '5902/2026',
        'ten_goi_thau' => 'Mua hệ thống máy siêu âm và hệ thống nội soi tiêu hóa năm 2026',
        'nhom'         => BG_Nhom_PUBLIC::HE_THONG_TBYT,
        'noi_dung'     => 'Hệ thống máy siêu âm màu 4D; Hệ thống nội soi tiêu hóa ống mềm',
        'ngay_mo'      => '-2 days',
        'ngay_dong'    => '+20 days',
        'hop_dong'     => 36,
        'hieu_luc'     => 180,
        'bo' => [
            [
                'ma_bo'  => 'HT01',
                'ten_bo' => 'HỆ THỐNG MÁY SIÊU ÂM MÀU 4D',
                'yeu_cau_chung' => "Hàng mới 100%, sản xuất từ năm 2025 trở lại đây.\n"
                                 . "Đồng bộ nguyên hệ thống của cùng một hãng.\n"
                                 . "Có giấy phép lưu hành tại Việt Nam còn hiệu lực.",
                'yeu_cau_khac'  => "Bảo hành tối thiểu 24 tháng tại nơi sử dụng.\n"
                                 . "Đào tạo vận hành cho tối thiểu 05 nhân viên.\n"
                                 . "Cam kết cung cấp vật tư thay thế tối thiểu 10 năm.",
                'yeu_cau_cau_hinh' => "Hệ thống hoàn chỉnh gồm: 01 máy chính; 01 màn hình ≥ 21 inch;\n"
                                    . "03 đầu dò (convex, linear, phased array);\n"
                                    . "01 phần mềm đo tim mạch; 01 xe đẩy chuyên dụng.",
                'nhom_nuoc' => 'Nhóm G7, EU',
                'dvt' => 'Hệ thống', 'so_luong' => 1,
                'chi_tiet' => [
                    ['Máy chính siêu âm màu',
                     "Màn hình cảm ứng điều khiển ≥ 10 inch.\nỔ cứng lưu trữ ≥ 500GB.\n"
                     . "Tối thiểu 3 cổng kết nối đầu dò.\nDải tần số 1-18 MHz.", 'Cái', 1],
                    ['Màn hình hiển thị chính',
                     "Kích thước ≥ 21 inch, độ phân giải Full HD trở lên.\nXoay/nghiêng điều chỉnh được.", 'Cái', 1],
                    ['Đầu dò Convex',
                     "Dải tần 1-6 MHz.\nDùng cho siêu âm ổ bụng, sản khoa.", 'Cái', 1],
                    ['Đầu dò Linear',
                     "Dải tần 5-15 MHz.\nDùng cho mạch máu, phần mềm, tuyến giáp.", 'Cái', 1],
                    ['Phần mềm đo và phân tích tim mạch',
                     "Đo tự động EF, Doppler mô.\nBản quyền vĩnh viễn kèm máy.", 'Bộ', 1],
                    ['Xe đẩy chuyên dụng',
                     "Có bánh xe khóa được.\nNgăn đựng đầu dò và phụ kiện.", 'Cái', 1],
                ],
            ],
            [
                'ma_bo'  => 'HT02',
                'ten_bo' => 'HỆ THỐNG NỘI SOI TIÊU HÓA ỐNG MỀM',
                'yeu_cau_chung' => "Hàng mới 100%, sản xuất từ năm 2025 trở lại đây.\n"
                                 . "Đồng bộ nguyên hệ thống của cùng một hãng.",
                'yeu_cau_khac'  => "Bảo hành tối thiểu 24 tháng.\n"
                                 . "Có sẵn linh kiện thay thế trong nước.",
                'yeu_cau_cau_hinh' => "Hệ thống gồm: 01 nguồn sáng LED; 01 bộ xử lý hình ảnh;\n"
                                    . "01 dây soi dạ dày; 01 dây soi đại tràng; 01 màn hình y tế.",
                'nhom_nuoc' => 'Nhật Bản, EU',
                'dvt' => 'Hệ thống', 'so_luong' => 1,
                'chi_tiet' => [
                    ['Nguồn sáng LED', "Tuổi thọ ≥ 20.000 giờ.\nĐiều chỉnh cường độ sáng theo cấp độ.", 'Cái', 1],
                    ['Bộ xử lý hình ảnh', "Độ phân giải Full HD trở lên.\nCó chức năng nhuộm màu ảo.", 'Cái', 1],
                    ['Dây soi dạ dày', "Đường kính ngoài ≤ 9,8mm.\nKênh thủ thuật ≥ 2,8mm.\nGập 4 hướng.", 'Cái', 1],
                    ['Dây soi đại tràng', "Đường kính ngoài ≤ 13mm.\nChiều dài làm việc ≥ 1.300mm.", 'Cái', 1],
                    ['Màn hình y tế', "Kích thước ≥ 26 inch.\nĐạt chuẩn hiển thị dùng trong y tế.", 'Cái', 1],
                ],
            ],
        ],
        'gia_co_so' => [1850000000, 45000000, 120000000, 95000000, 60000000, 25000000],
    ],
];

/** Nhà thầu chào giá — hệ số giá khác nhau để bảng tổng hợp có cái so sánh */
$nhaThau = [
    ['Công ty CP Thiết bị Y tế Trường Sơn', '0104567890', 'sales@truongson-med.vn', '024 3762 1188',
     'Số 27 Láng Hạ, Đống Đa, Hà Nội', 1.00, 'Olympus', 'Nhật Bản', true],
    ['Công ty TNHH Y tế Đông Dương', '0305678901', 'info@dongduong-medical.vn', '028 3925 4477',
     '119 Nguyễn Đình Chiểu, Quận 3, TP Hồ Chí Minh', 0.94, 'B.Braun', 'Đức', true],
    ['Công ty CP Đầu tư Thiết bị Y tế Việt Nhật', '0206789012', 'contact@vietnhat-med.com.vn', '0225 3852 663',
     '58 Điện Biên Phủ, Hồng Bàng, Hải Phòng', 1.07, 'Fujifilm', 'Nhật Bản', false],
];

// =====================================================================
// CHẠY
// =====================================================================
say('===========================================================');
say('  SEED DỮ LIỆU TEST — 2 NHÓM GÓI THẦU CÒN LẠI');
say('  DB: ' . AppConfig::DB_NAME);
say('===========================================================');
say('');

try {
    // ---- Reset: xóa hẳn 2 gói này (không đụng dữ liệu khác) ----
    if ($doReset) {
        say('→ Xóa dữ liệu cũ của 2 gói seed...');
        foreach ($goiThau as $g) {
            $st = $pdo->prepare("SELECT id FROM bg_goi_thau WHERE so_thong_bao = :s");
            $st->execute([':s' => $g['so_thong_bao']]);
            foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $id) {
                $id = (int)$id;
                $pdo->exec("DELETE FROM bg_bao_gia_chi_tiet WHERE bao_gia_id IN
                            (SELECT id FROM bg_bao_gia WHERE goi_thau_id = {$id})");
                $pdo->exec("DELETE FROM bg_bao_gia  WHERE goi_thau_id = {$id}");
                $pdo->exec("DELETE FROM bg_hang_hoa WHERE goi_thau_id = {$id}");
                $pdo->exec("DELETE FROM bg_bo       WHERE goi_thau_id = {$id}");
                $pdo->exec("DELETE FROM bg_goi_thau WHERE id = {$id}");
                say("  - xóa gói {$g['so_thong_bao']} (id={$id})");
            }
        }
        say('');
    }

    foreach ($goiThau as $g) {
        say('→ ' . BG_Nhom_PUBLIC::tenNhom($g['nhom']) . ' — ' . $g['so_thong_bao']);

        $st = $pdo->prepare("SELECT id FROM bg_goi_thau WHERE so_thong_bao = :s AND da_xoa = 0");
        $st->execute([':s' => $g['so_thong_bao']]);
        if ((int)$st->fetchColumn() > 0) {
            say('  = đã có, bỏ qua (dùng --reset để tạo lại)');
            say('');
            continue;
        }

        Database::beginTransaction();

        // ---- 1. Gói thầu ----
        $e = new BG_GoiThau_PUBLIC();
        $e->so_thong_bao           = $g['so_thong_bao'];
        $e->ten_goi_thau           = $g['ten_goi_thau'];
        $e->nhom                   = $g['nhom'];
        $e->noi_dung               = $g['noi_dung'];
        $e->ngay_phat_hanh         = date('Y-m-d', strtotime($g['ngay_mo']));
        $e->thoi_gian_mo_bao_gia   = date('Y-m-d H:i:s', strtotime($g['ngay_mo']));
        $e->thoi_gian_dong_bao_gia = date('Y-m-d 17:00:00', strtotime($g['ngay_dong']));
        $e->han_cuoi               = date('Y-m-d', strtotime($g['ngay_dong']));
        $e->thoi_gian_hop_dong     = $g['hop_dong'];
        $e->hieu_luc_bao_gia       = $g['hieu_luc'];
        $e->trang_thai             = BG_GoiThau_PUBLIC::TT_DANG_MO;
        $e->token                  = BG_GoiThau_BUS::sinhToken();
        $e->nguoi_tao              = $admin;
        $gtId = BG_GoiThau_DAL::insert($e);
        say("  + gói thầu id={$gtId}");

        // ---- 2. Bộ + hàng hóa chi tiết ----
        $soHh = 0;
        $thuTuHh = 0;
        foreach ($g['bo'] as $iBo => $b) {
            $eb = new BG_Bo_PUBLIC();
            $eb->goi_thau_id      = $gtId;
            $eb->ma_bo            = $b['ma_bo'];
            $eb->stt_bo           = $iBo + 1;
            $eb->ten_bo           = $b['ten_bo'];
            $eb->yeu_cau_chung    = $b['yeu_cau_chung'] ?? null;
            $eb->yeu_cau_khac     = $b['yeu_cau_khac'] ?? null;
            $eb->yeu_cau_cau_hinh = $b['yeu_cau_cau_hinh'] ?? null;
            $eb->nhom_nuoc        = $b['nhom_nuoc'] ?? null;
            $eb->dvt              = $b['dvt'];
            $eb->so_luong         = $b['so_luong'];
            $eb->thu_tu           = $iBo + 1;
            $eb->nguoi_tao        = $admin;
            $boId = BG_Bo_DAL::insert($eb);

            $items = [];
            foreach ($b['chi_tiet'] as $iCt => [$ten, $tskt, $dvt, $sl]) {
                $soHh++;
                $eh = new BG_HangHoa_PUBLIC();
                $eh->goi_thau_id       = $gtId;
                $eh->bo_id             = $boId;
                $eh->stt_chi_tiet      = $iCt + 1;
                $eh->ma_hh             = $b['ma_bo'] . '.' . ($iCt + 1);
                $eh->ten_hang_hoa      = $ten;
                $eh->thong_so_ky_thuat = $tskt;
                $eh->dvt               = $dvt;
                $eh->so_luong          = $sl;
                $eh->thu_tu            = ++$thuTuHh;
                $eh->nguoi_tao         = $admin;
                $items[] = $eh;
            }
            BG_HangHoa_DAL::insertBatch($items);
            say("    + bộ {$b['ma_bo']}: " . count($items) . ' hàng hóa chi tiết');
        }

        // ---- 3. Báo giá của nhà thầu ----
        $hangHoa = BG_HangHoa_DAL::getByGoiThau($gtId);
        $giaCoSo = $g['gia_co_so'];

        foreach ($nhaThau as $i => [$ten, $mst, $email, $dt, $diaChi, $heSo, $hangSx, $xuatXu, $daDuyet]) {
            $bg = new BG_BaoGia_PUBLIC();
            $bg->goi_thau_id      = $gtId;
            $bg->ten_cong_ty      = $ten;
            $bg->ma_so_thue       = $mst;
            $bg->email            = $email;
            $bg->dien_thoai       = $dt;
            $bg->dia_chi          = $diaChi;
            $bg->hieu_luc_bao_gia = $g['hieu_luc'];
            $bg->trang_thai       = BG_BaoGia_PUBLIC::TT_CHO_XAC_NHAN;
            $bg->ngay_nop         = date('Y-m-d H:i:s', strtotime('-' . (3 - $i) . ' days'));
            $bg->ip_nop           = '192.168.10.' . (30 + $i);
            $bg->nguoi_tao        = $admin;
            $bgId = BG_BaoGia_DAL::insert($bg);

            foreach ($hangHoa as $k => $hh) {
                // Nhà thầu thứ 3 bỏ 1 dòng — để test cột "Chưa chào" ở tổng hợp
                if ($i === 2 && $k === 1) continue;

                $base    = $giaCoSo[$k % count($giaCoSo)];
                $donGia  = round($base * $heSo / 1000) * 1000;
                $soLuong = (float)$hh['so_luong'];

                $ct = new BG_BaoGiaChiTiet_PUBLIC();
                $ct->bao_gia_id     = $bgId;
                $ct->hang_hoa_id    = (int)$hh['id'];
                $ct->ten_thuong_mai = $hangSx . ' ' . strtoupper(substr(md5($hh['ma_hh']), 0, 4));
                $ct->model          = 'MDL-' . strtoupper(substr(md5($hh['ma_hh'] . $i), 0, 6));
                $ct->hang_san_xuat  = $hangSx;
                $ct->nam_san_xuat   = (string)(2025 + ($i % 2));
                $ct->xuat_xu        = $xuatXu;
                $ct->don_gia        = $donGia;
                $ct->thanh_tien     = round($donGia * $soLuong, 2);

                // --- Mẫu 1: các cặp đáp ứng, chỉ điền cặp NHÓM NÀY có ---
                $ct->thong_so_chao_gia = 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.';
                $ct->diem_khong_dat    = ($i === 2 && $k === 0)
                    ? 'Một số thông số đạt mức tối thiểu — đề nghị xem xét tương đương.' : null;

                if (BG_Nhom_PUBLIC::coYeuCauChung($g['nhom'])) {
                    $ct->dap_ung_chung   = 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.';
                    $ct->dap_ung_khac    = 'Bảo hành ' . (24 + $i * 6) . ' tháng, đào tạo vận hành tại chỗ.';
                    $ct->khong_dat_chung = null;
                    $ct->khong_dat_khac  = null;
                }
                if (BG_Nhom_PUBLIC::coYeuCauCauHinh($g['nhom'])) {
                    $ct->dap_ung_cau_hinh   = 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.';
                    $ct->khong_dat_cau_hinh = ($i === 1 && $k === 2)
                        ? 'Đầu dò Linear dải tần 5-14 MHz (yêu cầu 5-15 MHz).' : null;
                }
                $ct->dap_ung_nhom_nuoc   = $xuatXu;
                $ct->tai_lieu_chung_minh = 'Catalog trang ' . (1 + $k * 3) . '-' . (3 + $k * 3)
                                         . ', giấy phép lưu hành số ' . (2400 + $i * 17 + $k);

                BG_BaoGia_DAL::upsertChiTiet($ct);
            }

            BG_BaoGia_DAL::updateTongTien($bgId);

            // Chốt hoàn thành + duyệt để có dữ liệu cho bảng tổng hợp
            $pdo->prepare(
                "UPDATE bg_bao_gia SET da_hoan_thanh = 1, ngay_hoan_thanh = ngay_nop WHERE id = :id"
            )->execute([':id' => $bgId]);

            if ($daDuyet) {
                BG_BaoGia_DAL::updateXacNhan($bgId, BG_BaoGia_PUBLIC::TT_DA_XAC_NHAN, null, $admin);
            }

            say("    + báo giá: {$ten} " . ($daDuyet ? '(đã duyệt)' : '(chờ duyệt)'));
        }

        Database::commit();
        say("  → xong: " . count($g['bo']) . " bộ / {$soHh} hàng hóa / " . count($nhaThau) . " báo giá");
        say('');
    }

    // ---- Tổng kết ----
    say('→ Hiện trạng toàn hệ thống');
    foreach ($pdo->query(
        "SELECT nhom, COUNT(*) n FROM bg_goi_thau WHERE da_xoa = 0 GROUP BY nhom"
    ) as $r) {
        printf("    %-16s %d gói\n", $r['nhom'], $r['n']);
    }
    say('    bộ:      ' . (int)$pdo->query("SELECT COUNT(*) FROM bg_bo WHERE da_xoa = 0")->fetchColumn());
    say('    hàng hóa: ' . (int)$pdo->query("SELECT COUNT(*) FROM bg_hang_hoa WHERE da_xoa = 0")->fetchColumn());
    say('    báo giá: ' . (int)$pdo->query("SELECT COUNT(*) FROM bg_bao_gia WHERE da_xoa = 0")->fetchColumn());

    say('');
    say('===========================================================');
    say('  HOÀN TẤT');
    say('===========================================================');

} catch (Throwable $ex) {
    if (Database::getConnection()->inTransaction()) Database::rollBack();
    say('');
    say('!!! LỖI: ' . $ex->getMessage());
    say('    ' . $ex->getFile() . ':' . $ex->getLine());
    exit(1);
}
