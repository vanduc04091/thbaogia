<?php
/**
 * tao_mau_word.php — Sinh file mẫu Word gốc trong thư mục MPS/.
 *
 * Chạy 1 lần để tạo `MPS/bao_gia.docx`. Sau đó BẠN TỰ MỞ FILE ĐÓ BẰNG WORD
 * để chỉnh font, cỡ chữ, căn lề, thêm logo... — chỉ cần giữ nguyên các {{KEY}}.
 *
 * ⚠️ Chạy lại script này sẽ GHI ĐÈ file mẫu, mất các chỉnh sửa của bạn.
 *    Muốn tạo lại mà vẫn giữ bản cũ thì đổi tên file cũ trước.
 *    Script sẽ HỎI trước khi ghi đè (trừ khi thêm --ghi-de).
 *
 * Cách chạy:  php database/tao_mau_word.php
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../PUBLIC/Common/WordHelper.php';
require_once __DIR__ . '/../PUBLIC/Common/WordTemplate.php';

function say(string $s = ''): void { echo $s . PHP_EOL; }

$dir = WordTemplate::thuMuc();
if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
    say('LỖI: không tạo được thư mục ' . $dir);
    exit(1);
}

$dich = $dir . DIRECTORY_SEPARATOR . 'bao_gia.docx';
$ghiDe = in_array('--ghi-de', $argv ?? [], true);

// Mỗi mẫu kiểm tra riêng — file này đã có thì bỏ qua, vẫn tạo tiếp mẫu khác.
$boQuaBaoGia = is_file($dich) && !$ghiDe;
if ($boQuaBaoGia) {
    say('');
    say('  = MPS/bao_gia.docx đã có (sửa lúc ' . date('d/m/Y H:i', filemtime($dich)) . '), bỏ qua.');
    say('    Ghi đè sẽ MẤT chỉnh sửa trong Word — muốn tạo lại: --ghi-de');
}

// ---------------------------------------------------------------
// Nội dung mẫu — bám theo "1.THU MOI CHAO GIA.docx" từ mục BÁO GIÁ
// trở xuống, phần cuối là BẢNG ĐÁP ỨNG KỸ THUẬT (Mẫu 1).
// ---------------------------------------------------------------
$b = [];

$b[] = ['p' => 'BÁO GIÁ', 'style' => 'title', 'after' => 200];
$b[] = ['p' => 'Kính gửi: Bệnh viện Hữu nghị Đa khoa Nghệ An',
        'bold' => true, 'align' => 'center', 'after' => 200];
$b[] = ['p' => 'Trên cơ sở yêu cầu báo giá của Bệnh viện Hữu nghị Đa khoa Nghệ An, '
             . 'chúng tôi {{GIOI_THIEU}} báo giá cho các hàng hóa, dịch vụ như sau:',
        'align' => 'both', 'indent' => 567];

// ===== 1. BẢNG CHÀO GIÁ (Mẫu 2 — 14 cột) =====
$b[] = ['p' => '1. Báo giá cho các hàng hóa và dịch vụ liên quan', 'bold' => true, 'after' => 100];

// Cot bam theo Mau 2 Phu luc II ban MOI: bo "Quy cach",
// "Don gia trung thau gan nhat", "Tai lieu tham chieu"; them "Nam san xuat".
// 14 cot LAY NGUYEN VAN tu Mau 2 - Phu luc II "Thu moi bao gia chung 3 nhom.docx".
// KHONG tu dat lai ten cot: ben moi doi chieu voi ban giay in ra.
// Dong BO co STT bo + Ten bo; dong CHI TIET co STT chi tiet + ten hang hoa.
$b[] = [
    'tbl' => [
        [
            'Mã bộ/phần/hệ thống/hàng hóa/dụng cụ chi tiết',
            "STT bộ/phần/\nhệ thống",
            'Tên bộ/phần/hệ thống',
            'STT chi tiết',
            'Tên danh mục hàng hóa/dụng cụ chi tiết',
            'Tên thương mại chào giá',
            'Ký mã, nhãn hiệu, model',
            'Hãng sản xuất',
            'Năm sản xuất',
            'Xuất xứ',
            'Đơn vị tính',
            'Số lượng',
            'Đơn giá',
            'Thành tiền (VND)',
        ],
        [
            '{{#CHAO_GIA}}{{MA}}', '{{STT_BO}}', '{{TEN_BO}}', '{{STT_CT}}',
            '{{TEN_HANG_HOA}}', '{{TEN_THUONG_MAI}}', '{{MODEL}}', '{{HANG_SAN_XUAT}}',
            '{{NAM_SAN_XUAT}}', '{{XUAT_XU}}', '{{DVT}}', '{{SO_LUONG}}',
            '{{DON_GIA}}', '{{THANH_TIEN}}',
        ],
        ['', '', 'TỔNG CỘNG', '', '', '', '', '', '', '', '', '', '', '{{TONG_TIEN}}'],
    ],
    // Tong = 10115 twips = be rong long trang kho DOC (12240 - le trai 1275
    // - le phai 850). Vuot so nay thi bang tran ra ngoai le khi in.
    'widths' => [864, 466, 1128, 421, 1283, 902, 752, 714, 466, 601, 466, 421, 789, 842],
    'aligns' => ['center','center','left','center','left','left','left','left',
                 'center','left','center','center','right','right'],
];

$b[] = ['p' => '2. Báo giá này có hiệu lực trong vòng: {{HIEU_LUC}} ngày, kể từ ngày {{NGAY_NOP}}.',
        'align' => 'both', 'indent' => 567];

$b[] = ['p' => '3. Chúng tôi cam kết:', 'align' => 'both', 'indent' => 567];
$b[] = ['p' => '- Không đang trong quá trình thực hiện thủ tục giải thể hoặc bị thu hồi '
             . 'Giấy chứng nhận đăng ký doanh nghiệp hoặc Giấy chứng nhận đăng ký hộ kinh doanh '
             . 'hoặc các tài liệu tương đương khác; không thuộc trường hợp mất khả năng thanh toán '
             . 'theo quy định của pháp luật về doanh nghiệp.', 'align' => 'both'];
$b[] = ['p' => '- Giá trị của các hàng hóa, dịch vụ nêu trong báo giá là phù hợp, '
             . 'không vi phạm quy định của pháp luật về cạnh tranh, bán phá giá.', 'align' => 'both'];
$b[] = ['p' => '- Những thông tin nêu trong báo giá là trung thực.', 'align' => 'both', 'after' => 240];

$b[] = ['p' => '………., ngày      tháng      năm 202…', 'style' => 'italic', 'align' => 'right', 'after' => 60];
$b[] = ['p' => 'Đại diện hợp pháp của hãng sản xuất, nhà cung cấp',
        'bold' => true, 'align' => 'right', 'after' => 60];
$b[] = ['p' => '(Ký tên, đóng dấu)', 'style' => 'italic', 'align' => 'right', 'after' => 900];

// ===== Ghi chú (1)-(12) =====
$b[] = ['p' => 'Ghi chú:', 'bold' => true, 'after' => 60];
$ghiChu = [
    'Hãng sản xuất, nhà cung cấp điền đầy đủ các thông tin để báo giá theo Mẫu này',
    'Hãng sản xuất, nhà cung cấp ghi cụ thể tên thương mại của hàng hóa tương ứng với chủng loại hàng hóa ghi tại cột “Tên hàng hóa mời chào giá”',
    'Hãng sản xuất, nhà cung cấp ghi cụ thể ký hiệu, mã hiệu, model của hàng hóa chào.',
    'Hãng sản xuất, nhà cung cấp ghi cụ thể hãng sản xuất của hàng hóa chào.',
    'Hãng sản xuất, nhà cung cấp ghi cụ thể năm sản xuất của hàng hóa chào.',
    'Hãng sản xuất, nhà cung cấp ghi cụ thể xuất xứ của hàng hóa chào.',
    'Hãng sản xuất, nhà cung cấp ghi đơn vị tính của hàng hóa.',
    'Hãng sản xuất, nhà cung cấp ghi cụ thể số lượng, khối lượng theo đúng số lượng, khối lượng nêu trong Yêu cầu báo giá.',
    'Hãng sản xuất, nhà cung cấp ghi giá trị đơn giá của từng hàng hóa (đã bao gồm thuế, phí, lệ phí và dịch vụ liên quan (nếu có)) theo đúng yêu cầu nêu trong Yêu cầu báo giá.',
    'Hãng sản xuất, nhà cung cấp ghi giá trị báo giá cho từng hàng hóa. Giá trị ghi tại cột này được hiểu là toàn bộ chi phí của từng hàng hóa (bao gồm thuế, phí, lệ phí và dịch vụ liên quan (nếu có)) theo đúng yêu cầu nêu trong Yêu cầu báo giá.',
];
foreach ($ghiChu as $i => $gc) {
    $b[] = ['p' => '(' . ($i + 1) . ') ' . $gc, 'size' => 22, 'align' => 'both', 'after' => 40];
}

// ===== BẢNG ĐÁP ỨNG KỸ THUẬT (Mẫu 1) — thay cho Phụ lục III =====
// Ngat SECTION: het phan tren la kho DOC (giong Thu moi goc), tu day tro
// xuong chuyen sang kho NGANG cho bang dap ung 21 cot du cho.
// Khong can ['br'] nua — ngat section da tu sang trang moi.
$b[] = ['sect' => WordHelper::A4_DOC];
$b[] = ['p' => 'BẢNG ĐÁP ỨNG KỸ THUẬT HÀNG HÓA CHÀO GIÁ',
        'bold' => true, 'align' => 'center', 'size' => 28, 'after' => 60];
$b[] = ['p' => '(Kèm theo báo giá của {{TEN_CONG_TY}} — Thư mời số {{SO_THONG_BAO}})',
        'style' => 'italic', 'align' => 'center', 'after' => 200];

// 21 cot LAY NGUYEN VAN tu Mau 1 - Phu luc II: 10 cot "Yeu cau moi chao gia"
// (Phu luc III) + 11 cot nha thau dien. Cot nao khong ap dung cho nhom thi
// de trong — mau Word la tinh, phan an/hien theo nhom lam o file Excel.
$b[] = [
    'tbl' => [
        [
            'Mã bộ/phần/hệ thống/hàng hóa/ dụng cụ chi tiết',
            "STT bộ/phần/\nhệ thống",
            'Tên bộ/phần/hệ thống',
            'STT chi tiết',
            'Tên danh mục hàng hóa/dụng cụ chi tiết',
            'Yêu cầu chung',
            'Yêu cầu khác',
            'Yêu cầu cấu hình',
            'Yêu cầu kỹ thuật',
            'Yêu cầu về nhóm nước, vùng lãnh thổ (nếu có)',
            'Đáp ứng về yêu cầu chung',
            'Các điểm không đáp ứng về yêu cầu chung',
            'Đáp ứng về yêu cầu khác',
            'Các điểm không đáp ứng về yêu cầu khác',
            'Đáp ứng về yêu cầu cấu hình',
            'Các điểm không đáp ứng về yêu cầu cấu hình',
            'Đáp ứng về yêu cầu kĩ thuật',
            'Các điểm không đáp ứng về yêu cầu kỹ thuật',
            'Đáp ứng về nhóm nước, vùng lãnh thổ',
            'Không đáp ứng về nhóm nước, vùng lãnh thổ',
            'Tài liệu chứng minh (cam kết, catalog, hướng dẫn sử dụng..)',
        ],
        [
            '{{#DAP_UNG}}{{MA}}', '{{STT_BO}}', '{{TEN_BO}}', '{{STT_CT}}',
            '{{TEN_HANG_HOA}}',
            '{{YEU_CAU_CHUNG}}', '{{YEU_CAU_KHAC}}', '{{YEU_CAU_CAU_HINH}}',
            '{{YEU_CAU_KY_THUAT}}', '{{YEU_CAU_NHOM_NUOC}}',
            '{{DAP_UNG_CHUNG}}', '{{KHONG_DAT_CHUNG}}',
            '{{DAP_UNG_KHAC}}', '{{KHONG_DAT_KHAC}}',
            '{{DAP_UNG_CAU_HINH}}', '{{KHONG_DAT_CAU_HINH}}',
            '{{THONG_SO_CHAO_GIA}}', '{{DIEM_KHONG_DAT}}',
            '{{DAP_UNG_NHOM_NUOC}}', '{{KHONG_DAT_NHOM_NUOC}}',
            '{{TAI_LIEU_CHUNG_MINH}}',
        ],
    ],
    // Tong = 14140 twips = be rong long trang kho NGANG (15840 - 850 - 850).
    'widths' => [703, 429, 815, 403, 950, 669, 669, 669, 772, 617,
                 669, 669, 669, 669, 669, 669, 729, 729, 600, 600, 772],
    'aligns' => ['center','center','left','center','left','left','left','left','left','left',
                 'left','left','left','left','left','left','left','left','left','left','left'],
];

$b[] = ['p' => '', 'after' => 240];
$b[] = ['p' => '………., ngày      tháng      năm 202…', 'style' => 'italic', 'align' => 'right', 'after' => 60];
$b[] = ['p' => 'Đại diện hợp pháp của hãng sản xuất, nhà cung cấp',
        'bold' => true, 'align' => 'right', 'after' => 60];
$b[] = ['p' => '(Ký tên, đóng dấu)', 'style' => 'italic', 'align' => 'right'];

if (!$boQuaBaoGia) {
    WordHelper::write($dich, $b, WordHelper::A4_NGANG);
    say('');
    say('  ĐÃ TẠO: MPS/bao_gia.docx');
}

say('');
say('===========================================================');
say('  ĐÃ TẠO FILE MẪU: MPS/bao_gia.docx');
say('===========================================================');
say('');
say('  Mở file bằng Word để chỉnh font / cỡ chữ / căn lề / logo.');
say('  GIỮ NGUYÊN các {{KEY}} bên dưới thì hệ thống mới điền được:');
say('');
say('  --- Key thường (thay 1 giá trị) ---');
foreach (['GIOI_THIEU'    => 'Tên + MST + địa chỉ + ĐT + email của công ty',
          'TEN_CONG_TY'   => 'Tên công ty',
          'MST'           => 'Mã số thuế',
          'SO_THONG_BAO'  => 'Số thư mời của gói thầu',
          'TEN_GOI_THAU'  => 'Tên gói thầu',
          'HIEU_LUC'      => 'Số ngày hiệu lực báo giá',
          'NGAY_NOP'      => 'Ngày nộp báo giá (dd/mm/yyyy)',
          'TONG_TIEN'     => 'Tổng tiền đã định dạng 1.234.567',
          'NGAY_IN'       => 'Ngày in file'] as $k => $v) {
    say(sprintf('    {{%-22s %s', $k . '}}', $v));
}
say('');
say('  --- Nhóm dòng lặp trong bảng ---');
say('    {{#CHAO_GIA}} — bảng chào giá (Mẫu 2, 14 cột). Key con:');
say('        {{MA}} {{STT_BO}} {{TEN_BO}} {{STT_CT}} {{TEN_HANG_HOA}}');
say('        {{TEN_THUONG_MAI}} {{MODEL}} {{HANG_SAN_XUAT}} {{NAM_SAN_XUAT}}');
say('        {{XUAT_XU}} {{DVT}} {{SO_LUONG}} {{DON_GIA}} {{THANH_TIEN}}');
say('');
say('    {{#DAP_UNG}} — bảng đáp ứng kỹ thuật (Mẫu 1, 21 cột). Key con:');
say('        {{MA}} {{STT_BO}} {{TEN_BO}} {{STT_CT}} {{TEN_HANG_HOA}}');
say('        {{YEU_CAU_CHUNG}} {{YEU_CAU_KHAC}} {{YEU_CAU_CAU_HINH}}');
say('        {{YEU_CAU_KY_THUAT}} {{YEU_CAU_NHOM_NUOC}}');
say('        {{DAP_UNG_CHUNG}} {{KHONG_DAT_CHUNG}} {{DAP_UNG_KHAC}} {{KHONG_DAT_KHAC}}');
say('        {{DAP_UNG_CAU_HINH}} {{KHONG_DAT_CAU_HINH}}');
say('        {{THONG_SO_CHAO_GIA}} {{DIEM_KHONG_DAT}}');
say('        {{DAP_UNG_NHOM_NUOC}} {{KHONG_DAT_NHOM_NUOC}} {{TAI_LIEU_CHUNG_MINH}}');
say('');
say('  Dòng BỘ dùng: {{MA}} {{STT_BO}} {{TEN_BO}} + các cột yêu cầu.');
say('  Dòng CHI TIẾT dùng: {{STT_CT}} {{TEN_HANG_HOA}} + các cột còn lại.');
say('');
say('  Lưu ý: dòng chứa {{#...}} là DÒNG MẪU — sẽ được nhân bản cho mỗi');
say('  hàng hóa. Muốn đổi định dạng mọi dòng thì sửa đúng dòng mẫu đó.');
say('');
