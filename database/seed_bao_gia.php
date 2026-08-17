<?php
/**
 * seed_bao_gia.php — Nạp dữ liệu test cho nghiệp vụ báo giá.
 *
 * Chạy:  php database/seed_bao_gia.php               (thêm nếu chưa có)
 *        php database/seed_bao_gia.php --reset       (xóa sạch 4 bảng bg_* rồi seed lại)
 *
 * Tạo: 2 gói thầu, hàng hóa import từ docs/file mau bao gia.xlsx (nếu có),
 *      3 nhà thầu chào giá với mức giá khác nhau, 2 trong số đó đã xác nhận bản giấy.
 *
 * CHỈ chạy khi APP_DEBUG = true.
 */
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../BUS/BG_GoiThau_BUS.php';
require_once __DIR__ . '/../BUS/BG_HangHoa_BUS.php';
require_once __DIR__ . '/../BUS/BG_BaoGia_BUS.php';

if (!AppConfig::APP_DEBUG) {
    fwrite(STDERR, "Từ chối: seed chỉ chạy khi APP_DEBUG = true.\n");
    exit(1);
}

$isCli = PHP_SAPI === 'cli';
$doReset = $isCli && in_array('--reset', $argv ?? [], true);
if (!$isCli) header('Content-Type: text/plain; charset=utf-8');

function say(string $m): void { echo $m . "\n"; }

$pdo = Database::getConnection();
$admin = 1;

try {
    if ($doReset) {
        say('→ Xóa dữ liệu báo giá cũ...');
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['bg_bao_gia_chi_tiet', 'bg_bao_gia', 'bg_hang_hoa', 'bg_goi_thau'] as $t) {
            $pdo->exec("TRUNCATE TABLE `{$t}`");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        say('  Đã xóa 4 bảng bg_*.');
    }

    // ============ 1. GÓI THẦU ============
    $goiThauData = [
        [
            'so_thong_bao' => '5742/2026',
            'ten_goi_thau' => 'Mua vật tư tiêu hao phẫu thuật cột sống và thần kinh sọ não năm 2026',
            'noi_dung'     => 'Nẹp tạo hình bản sống cổ lối sau; Vít tạo hình bản sống cổ lối sau; '
                            . 'Van dẫn lưu dịch não tủy ổ bụng có kèm catheter phủ kháng sinh',
            'ngay_phat_hanh' => date('Y-m-d', strtotime('-3 days')),
            // ĐANG MỞ báo giá: mở 3 ngày trước, đóng 10 ngày nữa
            'thoi_gian_mo_bao_gia'   => date('Y-m-d H:i:s', strtotime('-3 days')),
            'thoi_gian_dong_bao_gia' => date('Y-m-d 17:00:00', strtotime('+10 days')),
            'han_cuoi'       => date('Y-m-d', strtotime('+10 days')),
            'thoi_gian_hop_dong' => 24,
            'hieu_luc_bao_gia'   => 180,
            'trang_thai'         => BG_GoiThau_PUBLIC::TT_DANG_MO,
        ],
        [
            'so_thong_bao' => '5800/2026',
            'ten_goi_thau' => 'Mua vật tư nha khoa và dụng cụ phẫu thuật răng miệng năm 2026',
            'noi_dung'     => 'Hộp đựng mũi khoan; Bộ que hàn Composite; Cây nạo nha chu GRACEY các số',
            'ngay_phat_hanh' => date('Y-m-d', strtotime('-20 days')),
            // HẾT THỜI GIAN báo giá (đóng 2 ngày trước) — để test chế độ chỉ tra cứu
            'thoi_gian_mo_bao_gia'   => date('Y-m-d H:i:s', strtotime('-20 days')),
            'thoi_gian_dong_bao_gia' => date('Y-m-d 17:00:00', strtotime('-2 days')),
            'han_cuoi'       => date('Y-m-d', strtotime('-2 days')),
            'thoi_gian_hop_dong' => 12,
            'hieu_luc_bao_gia'   => 120,
            'trang_thai'         => BG_GoiThau_PUBLIC::TT_DANG_MO,
        ],
    ];

    $goiThauIds = [];
    foreach ($goiThauData as $g) {
        $stmt = $pdo->prepare("SELECT id FROM bg_goi_thau WHERE so_thong_bao = :s AND da_xoa = 0");
        $stmt->execute([':s' => $g['so_thong_bao']]);
        $id = (int)$stmt->fetchColumn();

        if ($id === 0) {
            $e = new BG_GoiThau_PUBLIC();
            foreach ($g as $k => $v) $e->$k = $v;
            $e->nguoi_tao = $admin;
            // Insert trực tiếp qua DAL để đặt được trang_thai = Đang mở ngay
            $e->token = BG_GoiThau_BUS::sinhToken();
            $id = BG_GoiThau_DAL::insert($e);
            say("  + gói thầu {$g['so_thong_bao']} (id={$id})");
        } else {
            say("  = gói thầu {$g['so_thong_bao']} đã có (id={$id})");
        }
        $goiThauIds[$g['so_thong_bao']] = $id;
    }

    $gt1 = $goiThauIds['5742/2026'];
    $gt2 = $goiThauIds['5800/2026'];

    // ============ 2. HÀNG HÓA ============
    // Ưu tiên import từ file mẫu thật để dữ liệu test giống thực tế
    $fileMau = __DIR__ . '/../docs/file mau bao gia.xlsx';

    if (BG_HangHoa_DAL::countByGoiThau($gt1) === 0) {
        if (is_file($fileMau)) {
            say('→ Import hàng hóa gói 5742/2026 từ file mẫu...');
            $res = BG_HangHoa_BUS::importExcel($gt1, $fileMau, false, $admin);
            say($res['success']
                ? '  ✓ ' . $res['message']
                : '  ! ' . $res['message'] . ' → chuyển sang tạo dữ liệu tay');
        }
        // Fallback: tạo tay nếu không import được
        if (BG_HangHoa_DAL::countByGoiThau($gt1) === 0) {
            $mau = [
                ['Phần 1', 'P1.1', '1', 'Nẹp tạo hình bản sống cổ lối sau',
                 "Vật liệu: Hợp kim Titan hoặc Cobalt Chrome hoặc vật liệu tương đương về độ bền cơ lý\n"
                 . "Hình thái nẹp phù hợp với vị trí mở cung sau\nChiều dài các cỡ từ ≤ 8mm đến ≥ 16mm",
                 'FDA (Mỹ) hoặc MHLW/PMDA (Nhật Bản) hoặc CE (MDR)', 'Nhóm G7, EU, Úc', 'Cái', 100],
                ['Phần 1', 'P1.2', '2', 'Vít tạo hình bản sống cổ lối sau',
                 "Vật liệu: Hợp kim Titan hoặc Cobalt Chrome\nTự taro\nĐường kính các cỡ ≥ 2,5mm",
                 'FDA (Mỹ) hoặc MHLW/PMDA (Nhật Bản) hoặc CE (MDR)', 'Nhóm G7, EU, Úc', 'Cái', 300],
                ['Phần 2', 'P2.1', '3', 'Van dẫn lưu dịch não tủy ổ bụng có kèm catheter phủ kháng sinh',
                 "Bộ van dùng để dẫn lưu dịch não tủy ổ bụng, có kèm catheter phủ kháng sinh\n"
                 . "Đóng gói tiệt trùng bao gồm: 01 Van bằng Polysulfone hoặc tương đương",
                 'FDA (Mỹ) hoặc CE (MDR)', 'Nhóm G7, EU', 'Cái', 50],
                ['Phần 2', 'P2.2', '4', 'Bộ vật tư dùng trong điều trị đau thần kinh sử dụng sóng cao tần',
                 "Bộ kim và điện cực dùng cho máy phát sóng cao tần\nTương thích với hệ thống hiện có",
                 'FDA (Mỹ) hoặc CE (MDR)', 'Nhóm G7, EU', 'Bộ', 80],
                ['Phần 3', 'P3.1', '5', 'Lưới cắt đốt bằng sóng cao tần dùng trong nội soi cột sống 2 cổng',
                 "Loại dùng bên ngoài ống tủy sống\nĐầu uốn được\nTương thích hệ thống nội soi 2 cổng",
                 'CE (MDR) hoặc FDA (Mỹ)', 'Nhóm G7, EU', 'Cái', 120],
            ];
            $items = [];
            $tt = 0;
            foreach ($mau as [$phan, $sttP, $sttTb, $ten, $tskt, $cn, $xx, $dvt, $sl]) {
                $e = new BG_HangHoa_PUBLIC();
                $e->goi_thau_id       = $gt1;
                $e->ten_phan          = $phan;
                $e->stt_theo_phan     = $sttP;
                $e->stt_thong_bao     = $sttTb;
                $e->ten_hang_hoa      = $ten;
                $e->thong_so_ky_thuat = $tskt;
                $e->chung_nhan        = $cn;
                $e->yeu_cau_xuat_xu   = $xx;
                $e->dvt               = $dvt;
                $e->so_luong          = $sl;
                $e->yeu_cau_tro_cu    = 'Cam kết cung cấp trợ cụ đầy đủ, chính hãng đáp ứng nhu cầu sử dụng';
                $e->thu_tu            = ++$tt;
                $e->nguoi_tao         = $admin;
                $items[] = $e;
            }
            BG_HangHoa_DAL::insertBatch($items);
            say('  + ' . count($items) . ' hàng hóa (tạo tay) cho gói 5742/2026');
        }
    } else {
        say('  = gói 5742/2026 đã có hàng hóa');
    }

    if (BG_HangHoa_DAL::countByGoiThau($gt2) === 0) {
        $mau2 = [
            ['Phần 1', 'P1.1', '1', 'Hộp đựng mũi khoan', 'Chất liệu inox, có nắp, tiệt trùng được', 'ISO 13485', 'EU, Nhật', 'Cái', 40],
            ['Phần 1', 'P1.2', '2', 'Bộ que hàn Composite (Cây trám)', 'Bộ đầy đủ các cỡ, thép không gỉ', 'ISO 13485', 'EU, Nhật', 'Bộ', 25],
            ['Phần 2', 'P2.1', '3', 'Cây nạo nha chu GRACEY số 11-12', 'Thép không gỉ, tay cầm chống trượt', 'ISO 13485', 'EU, Mỹ', 'Cái', 60],
            ['Phần 2', 'P2.2', '4', 'Thước đo túi lợi', 'Có vạch chia mm rõ nét, thép không gỉ', 'ISO 13485', 'EU, Mỹ', 'Cái', 30],
        ];
        $items = [];
        $tt = 0;
        foreach ($mau2 as [$phan, $sttP, $sttTb, $ten, $tskt, $cn, $xx, $dvt, $sl]) {
            $e = new BG_HangHoa_PUBLIC();
            $e->goi_thau_id       = $gt2;
            $e->ten_phan          = $phan;
            $e->stt_theo_phan     = $sttP;
            $e->stt_thong_bao     = $sttTb;
            $e->ten_hang_hoa      = $ten;
            $e->thong_so_ky_thuat = $tskt;
            $e->chung_nhan        = $cn;
            $e->yeu_cau_xuat_xu   = $xx;
            $e->dvt               = $dvt;
            $e->so_luong          = $sl;
            $e->thu_tu            = ++$tt;
            $e->nguoi_tao         = $admin;
            $items[] = $e;
        }
        BG_HangHoa_DAL::insertBatch($items);
        say('  + ' . count($items) . ' hàng hóa cho gói 5800/2026');
    } else {
        say('  = gói 5800/2026 đã có hàng hóa');
    }

    // ============ 3. BÁO GIÁ NHÀ THẦU ============
    // [tên công ty, MST, email, dt, địa chỉ, hệ số giá, trạng thái]
    $nhaThau = [
        ['Công ty TNHH Thiết bị Y tế An Phát', '0101234567', 'kinhdoanh@anphat.vn', '0243 8765 432',
         'Số 12 Nguyễn Trãi, Thanh Xuân, Hà Nội', 1.00, BG_BaoGia_PUBLIC::TT_DA_XAC_NHAN],
        ['Công ty CP Vật tư Y tế Bình Minh', '0209876543', 'sales@binhminhmed.com.vn', '0283 5566 778',
         '45 Lê Lợi, Quận 1, TP Hồ Chí Minh', 0.92, BG_BaoGia_PUBLIC::TT_DA_XAC_NHAN],
        ['Công ty TNHH Dược phẩm và TBYT Hoàng Long', '0312345678', 'info@hoanglongmed.vn', '0236 3344 556',
         '88 Trần Phú, Hải Châu, Đà Nẵng', 1.08, BG_BaoGia_PUBLIC::TT_CHO_XAC_NHAN],
    ];

    $hangHoaGt1 = BG_HangHoa_DAL::getByGoiThau($gt1);
    // Giá cơ sở theo thứ tự hàng hóa — nhân với hệ số của từng nhà thầu
    $giaCoSo = [1250000, 385000, 12800000, 4500000, 3200000];

    foreach ($nhaThau as $i => [$ten, $mst, $email, $dt, $diaChi, $heSo, $trangThai]) {
        $stmt = $pdo->prepare("SELECT id FROM bg_bao_gia WHERE ma_so_thue = :m AND goi_thau_id = :g AND da_xoa = 0");
        $stmt->execute([':m' => $mst, ':g' => $gt1]);
        if ((int)$stmt->fetchColumn() > 0) {
            say("  = báo giá {$ten} đã có");
            continue;
        }

        $bg = new BG_BaoGia_PUBLIC();
        $bg->goi_thau_id      = $gt1;
        $bg->ten_cong_ty      = $ten;
        $bg->ma_so_thue       = $mst;
        $bg->email            = $email;
        $bg->dien_thoai       = $dt;
        $bg->dia_chi          = $diaChi;
        $bg->hieu_luc_bao_gia = 180;
        $bg->trang_thai       = BG_BaoGia_PUBLIC::TT_CHO_XAC_NHAN;
        $bg->ngay_nop         = date('Y-m-d H:i:s', strtotime('-' . (3 - $i) . ' days'));
        $bg->ip_nop           = '192.168.1.' . (20 + $i);
        $bg->nguoi_tao        = $admin;
        $bgId = BG_BaoGia_DAL::insert($bg);

        // Chi tiết chào giá
        $xuatXuList = [['Corin Ltd', 'Mỹ'], ['B.Braun', 'Đức'], ['Medtronic', 'Ireland']];
        foreach ($hangHoaGt1 as $k => $hh) {
            // Nhà thầu thứ 3 bỏ trống 1 dòng để test trường hợp "không chào"
            if ($i === 2 && $k === 2) continue;

            $base = $giaCoSo[$k % count($giaCoSo)];
            $donGia = round($base * $heSo / 1000) * 1000;
            $soLuong = (float)$hh['so_luong'];

            $ct = new BG_BaoGiaChiTiet_PUBLIC();
            $ct->bao_gia_id          = $bgId;
            $ct->hang_hoa_id         = (int)$hh['id'];
            $ct->ten_thuong_mai      = 'Model ' . chr(65 + $i) . '-' . ($k + 1) . ' Series';
            $ct->model               = 'REF-' . (694000 + $i * 100 + $k);
            $ct->ma_hs               = '9021';
            $ct->hang_san_xuat       = $xuatXuList[$i][0];
            $ct->xuat_xu             = $xuatXuList[$i][1];
            $ct->quy_cach            = '1 bộ/hộp';
            $ct->chi_phi_dich_vu     = 0;
            $ct->thue_vat            = 5;
            $ct->don_gia             = $donGia;
            $ct->thanh_tien          = round($donGia * $soLuong, 2);
            $ct->chung_nhan_chao     = $i === 0 ? 'FDA (510(k))/ISO13485' : ($i === 1 ? 'CE (MDR)/ISO13485' : 'CE (MDD)/ISO13485');
            $ct->don_gia_trung_thau  = round($base * 0.97 / 1000) * 1000;
            $ct->tai_lieu_tham_chieu = 'Hợp đồng số ' . (12 + $i) . '/HĐ-BV ngày 01/03/2025';
            $ct->ma_qr_hang_hoa      = 'Có QR/Barcode trên từng sản phẩm';
            $ct->thong_so_chao_gia   = 'Đáp ứng đầy đủ các thông số kỹ thuật yêu cầu của hồ sơ mời chào giá.';
            $ct->diem_khong_dat      = $i === 2 ? 'Chiều dài cỡ nhỏ nhất 9mm (yêu cầu ≤ 8mm) — đề nghị xem xét tương đương.' : null;
            BG_BaoGia_DAL::upsertChiTiet($ct);
        }
        BG_BaoGia_DAL::updateTongTien($bgId);

        // Xác nhận bản giấy cho 2 nhà thầu đầu
        if ($trangThai === BG_BaoGia_PUBLIC::TT_DA_XAC_NHAN) {
            BG_BaoGia_DAL::updateXacNhan($bgId, BG_BaoGia_PUBLIC::TT_DA_XAC_NHAN, null, $admin);
        }
        say("  + báo giá {$ten} (id={$bgId}, "
            . ($trangThai === BG_BaoGia_PUBLIC::TT_DA_XAC_NHAN ? 'đã xác nhận' : 'chờ xác nhận') . ')');
    }

    say('');
    say('HOÀN TẤT.');
    $tk1 = BG_GoiThau_DAL::thongKe();
    $tk2 = BG_BaoGia_DAL::thongKe();
    say("  Gói thầu: {$tk1['tong']} (đang mở {$tk1['dang_mo']})");
    say("  Báo giá:  {$tk2['tong']} (đã xác nhận {$tk2['da_xac_nhan']}, chờ {$tk2['cho_xac_nhan']})");
    say('');
    $gt = BG_GoiThau_DAL::getById($gt1);
    say('  Link chào giá gói 5742/2026:');
    say('  ' . BG_GoiThau_BUS::urlPortal($gt->token));
} catch (Throwable $ex) {
    say('LỖI: ' . $ex->getMessage());
    say($ex->getTraceAsString());
    exit(1);
}
