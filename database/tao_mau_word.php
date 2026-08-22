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

$b[] = [
    'tbl' => [
        [
            'TT', 'Mã HH', 'Tên hàng hóa mời chào giá', 'Tên thương mại (2)',
            "Ký, mã, nhãn hiệu,\nmodel (3)", 'Hãng sản xuất (4)', 'Xuất xứ (5)',
            "Số lượng/\nkhối lượng (6)", 'Quy cách (7)', 'Đơn vị tính (8)',
            "Đơn giá (đã bao gồm thuế, phí, lệ phí và các dịch vụ liên quan (nếu có)) (9)\n(VND)",
            "Thành tiền (10)\n(VND)", "Đơn giá trúng thầu gần nhất (11)\n(VNĐ)",
            "Tài liệu tham chiếu đơn giá trúng thầu gần nhất (12)",
        ],
        [
            '{{#CHAO_GIA}}{{STT}}', '{{MA_HH}}', '{{TEN_HANG_HOA}}', '{{TEN_THUONG_MAI}}',
            '{{MODEL}}', '{{HANG_SAN_XUAT}}', '{{XUAT_XU}}', '{{SO_LUONG}}',
            '{{QUY_CACH}}', '{{DVT}}', '{{DON_GIA}}', '{{THANH_TIEN}}',
            '{{DON_GIA_TRUNG_THAU}}', '{{TAI_LIEU_THAM_CHIEU}}',
        ],
        ['', '', 'TỔNG CỘNG', '', '', '', '', '', '', '', '', '{{TONG_TIEN}}', '', ''],
    ],
    'widths' => [420, 700, 1600, 1150, 950, 950, 750, 700, 800, 620, 1150, 1150, 1000, 2200],
    'aligns' => ['center','center','left','left','left','left','left',
                 'center','left','center','right','right','right','left'],
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
    'Hãng sản xuất, nhà cung cấp ghi cụ thể xuất xứ của hàng hóa chào.',
    'Hãng sản xuất, nhà cung cấp ghi cụ thể số lượng, khối lượng theo đúng số lượng, khối lượng nêu trong Yêu cầu báo giá.',
    'Hãng sản xuất, nhà cung cấp ghi cụ thể quy cách của hàng hóa.',
    'Hãng sản xuất, nhà cung cấp ghi đơn vị tính của hàng hóa.',
    'Hãng sản xuất, nhà cung cấp ghi giá trị đơn giá của từng hàng hóa (đã bao gồm thuế, phí, lệ phí và dịch vụ liên quan (nếu có)) theo đúng yêu cầu nêu trong Yêu cầu báo giá.',
    'Hãng sản xuất, nhà cung cấp ghi giá trị báo giá cho từng hàng hóa. Giá trị ghi tại cột này được hiểu là toàn bộ chi phí của từng hàng hóa (bao gồm thuế, phí, lệ phí và dịch vụ liên quan (nếu có)) theo đúng yêu cầu nêu trong Yêu cầu báo giá.',
    'Công ty điền đơn giá trúng thầu gần nhất trong vòng 360 ngày (nếu có) của hàng hóa chào cho Bệnh viện.',
    'Công ty điền số thông báo mời thầu (Ví dụ: IB2500…)',
];
foreach ($ghiChu as $i => $gc) {
    $b[] = ['p' => '(' . ($i + 1) . ') ' . $gc, 'size' => 22, 'align' => 'both', 'after' => 40];
}

// ===== BẢNG ĐÁP ỨNG KỸ THUẬT (Mẫu 1) — thay cho Phụ lục III =====
$b[] = ['br' => true];
$b[] = ['p' => 'BẢNG ĐÁP ỨNG KỸ THUẬT HÀNG HÓA CHÀO GIÁ',
        'bold' => true, 'align' => 'center', 'size' => 28, 'after' => 60];
$b[] = ['p' => '(Kèm theo báo giá của {{TEN_CONG_TY}} — Thư mời số {{SO_THONG_BAO}})',
        'style' => 'italic', 'align' => 'center', 'after' => 200];

$b[] = [
    'tbl' => [
        [
            'Mã HH', 'Tên hàng hóa mời chào giá', 'Yêu cầu kỹ thuật mời chào giá',
            'Yêu cầu kỹ thuật chào giá', 'Các điểm không đạt kèm thuyết minh',
        ],
        [
            '{{#DAP_UNG}}{{MA_HH}}', '{{TEN_HANG_HOA}}', '{{YEU_CAU_KY_THUAT}}',
            '{{THONG_SO_CHAO_GIA}}', '{{DIEM_KHONG_DAT}}',
        ],
    ],
    'widths' => [900, 2600, 3400, 3200, 2900],
    'aligns' => ['center', 'left', 'left', 'left', 'left'],
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

// ---------------------------------------------------------------
// MẪU 2: Chỉ dẫn vị trí tài liệu (Bước 5)
// ---------------------------------------------------------------
$dich2 = $dir . DIRECTORY_SEPARATOR . 'chi_dan_tai_lieu.docx';
if (!is_file($dich2) || $ghiDe) {
    $c = [];
    $c[] = ['p' => 'CHỈ DẪN VỊ TRÍ TÀI LIỆU', 'style' => 'title', 'after' => 120];
    $c[] = ['p' => '(Kèm theo báo giá của {{TEN_CONG_TY}} — MST: {{MST}})',
            'style' => 'italic', 'align' => 'center', 'after' => 60];
    $c[] = ['p' => 'Thư mời số {{SO_THONG_BAO}} — {{TEN_GOI_THAU}}',
            'style' => 'italic', 'align' => 'center', 'after' => 200];
    $c[] = ['p' => 'Chúng tôi chỉ dẫn vị trí tài liệu chứng minh thông số kỹ thuật '
                 . 'của hàng hóa đã chào như sau:', 'align' => 'both', 'indent' => 567];

    $c[] = [
        'tbl' => [
            ['STT', 'Mã HH', 'Tên hàng thương mại', 'Trang catalog chứng minh'],
            ['{{#CATALOG}}{{STT}}', '{{MA_HH}}', '{{TEN_THUONG_MAI}}', '{{TRANG_CATALOG}}'],
        ],
        'widths' => [800, 1400, 4800, 2800],
        'aligns' => ['center', 'center', 'left', 'left'],
    ];

    $c[] = ['p' => '', 'after' => 300];
    $c[] = ['p' => '………., ngày      tháng      năm 202…',
            'style' => 'italic', 'align' => 'right', 'after' => 60];
    $c[] = ['p' => 'Đại diện hợp pháp của hãng sản xuất, nhà cung cấp',
            'bold' => true, 'align' => 'right', 'after' => 60];
    $c[] = ['p' => '(Ký tên, đóng dấu)', 'style' => 'italic', 'align' => 'right'];

    WordHelper::write($dich2, $c, WordHelper::A4_DOC);
    say('');
    say('  ĐÃ TẠO: MPS/chi_dan_tai_lieu.docx');
    say('    Key: {{TEN_CONG_TY}} {{MST}} {{SO_THONG_BAO}} {{TEN_GOI_THAU}} {{NGAY_IN}}');
    say('    Nhóm lặp {{#CATALOG}}: {{STT}} {{MA_HH}} {{TEN_THUONG_MAI}} {{TRANG_CATALOG}}');
} else {
    say('');
    say('  = MPS/chi_dan_tai_lieu.docx đã có, bỏ qua (dùng --ghi-de để tạo lại)');
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
say('    {{#CHAO_GIA}} — bảng chào giá (Mẫu 2). Key con:');
say('        {{STT}} {{MA_HH}} {{TEN_HANG_HOA}} {{TEN_THUONG_MAI}} {{MODEL}}');
say('        {{HANG_SAN_XUAT}} {{XUAT_XU}} {{SO_LUONG}} {{QUY_CACH}} {{DVT}}');
say('        {{DON_GIA}} {{THANH_TIEN}} {{DON_GIA_TRUNG_THAU}} {{TAI_LIEU_THAM_CHIEU}}');
say('');
say('    {{#DAP_UNG}} — bảng đáp ứng kỹ thuật (Mẫu 1). Key con:');
say('        {{STT}} {{MA_HH}} {{TEN_HANG_HOA}} {{YEU_CAU_KY_THUAT}}');
say('        {{THONG_SO_CHAO_GIA}} {{DIEM_KHONG_DAT}}');
say('');
say('  Lưu ý: dòng chứa {{#...}} là DÒNG MẪU — sẽ được nhân bản cho mỗi');
say('  hàng hóa. Muốn đổi định dạng mọi dòng thì sửa đúng dòng mẫu đó.');
say('');
