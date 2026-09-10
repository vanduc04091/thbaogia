<?php
/**
 * tao_mau_thu_moi.php — Sinh MPS/thu_moi.docx từ file gốc bên mời cung cấp.
 *
 * Cách làm: COPY nguyên file docs/Thư mời báo giá chung 3 nhóm.docx rồi thay
 * các chỗ "………" bằng {{KEY}} — giữ NGUYÊN định dạng Word gốc (font, bảng,
 * canh lề, phụ lục). Dựng lại bằng WordHelper sẽ mất hết định dạng và phải
 * bảo trì 3 phụ lục dài bằng code, không đáng.
 *
 * Chạy:  php database/tao_mau_thu_moi.php
 *        php database/tao_mau_thu_moi.php --ghi-de   (tạo lại dù đã có)
 *
 * Sau khi chạy, muốn sửa nội dung/định dạng thì mở MPS/thu_moi.docx bằng Word.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Script nay chi chay bang dong lenh (CLI).\n");
}

require_once __DIR__ . '/../bootstrap.php';

$ghiDe = in_array('--ghi-de', $argv ?? [], true);

function say(string $m = ''): void { echo $m . "\n"; }

$nguon = __DIR__ . '/../docs/Thư mời báo giá chung 3 nhóm.docx';
$dich  = __DIR__ . '/../MPS/thu_moi.docx';

say('===========================================================');
say('  SINH MẪU THƯ MỜI TỪ FILE GỐC');
say('===========================================================');
say('');

if (!is_file($nguon)) {
    say('!!! Không tìm thấy file gốc: docs/Thư mời báo giá chung 3 nhóm.docx');
    exit(1);
}
if (is_file($dich) && !$ghiDe) {
    say('  = MPS/thu_moi.docx đã có, bỏ qua (dùng --ghi-de để tạo lại)');
    exit(0);
}

/**
 * Các chỗ cần thay trong document.xml.
 *
 * LƯU Ý: Word hay cắt 1 câu thành nhiều <w:r> nên chuỗi dài có thể không khớp.
 * Vì vậy chỉ thay những cụm NGẮN, đặc trưng — và kiểm lại số chỗ đã thay ở
 * cuối để biết cụm nào trượt.
 */
$thay = [
    // Số + ngày ban hành
    'Số:             /TM-BV'   => 'Số: {{SO_THONG_BAO}}/TM-BV',
    'Nghệ An, ngày       tháng     năm 2026'
                               => 'Nghệ An, {{NGAY_PHAT_HANH_CHU}}',

    // Tên gói thầu ở tiêu đề
    'chào giá ……….phục vụ công tác'
                               => 'chào giá {{TEN_GOI_THAU}} phục vụ công tác',

    // Người tiếp nhận báo giá
    '- Họ và tên: …………………..'   => '- Họ và tên: {{NGUOI_LIEN_HE}}',
    '- Chức vụ: …………………..'     => '- Chức vụ: {{CHUC_VU_LIEN_HE}}',
    '- Số điện thoại: ………………………..' => '- Số điện thoại: {{SDT_LIEN_HE}}',
    '- Email:………………………..'      => '- Email: {{EMAIL_LIEN_HE}}',
    '- Nhận trực tiếp tại địa chỉ…………………….. '
                               => '- Nhận trực tiếp tại địa chỉ: {{DIA_CHI_NHAN}}',

    // Thời hạn nhận báo giá + hiệu lực
    'Từ …h… ngày .../.../….. đến trước ..h.. ngày .../../…....'
                               => 'Từ {{THOI_GIAN_MO}} đến trước {{THOI_GIAN_DONG}}.',
    'Tối thiểu 150 ngày kể từ ngày …../…./……..'
                               => 'Tối thiểu {{HIEU_LUC}} ngày kể từ ngày {{NGAY_HET_HAN}}.',

    // Thời gian giao hàng
    '3. Thời gian giao hàng dự kiến: ………………………………..'
                               => '3. Thời gian giao hàng dự kiến: {{THOI_GIAN_GIAO_HANG}}',

    // Đường dẫn hệ thống — file gốc ghi cứng link Google Sites cũ,
    // phải thay bằng link cổng chào giá THẬT của gói thầu (kèm token).
    'https://sites.google.com/view/hmuh-vttbqt/qu%E1%BA%A3n-l%C3%BD-ch%C3%A0o-gi%C3%A1-v8'
                               => '{{DUONG_DAN}}',

    // Tài khoản truy cập hệ thống
    '- Tài khoản truy cập guest@123 mật khẩu...........'
                               => '- Tài khoản truy cập: {{TAI_KHOAN}} — Mật khẩu: {{MAT_KHAU}}',

    // Ngày ở các phụ lục
    'Kèm theo Thư mời số …../TM-BV ngày …/…/… của Bệnh viện'
                               => 'Kèm theo Thư mời số {{SO_THONG_BAO}}/TM-BV ngày {{NGAY_PHAT_HANH}} của Bệnh viện',
    'Đính kèm thư mời số       /TM-BV ngày     /9/2026 của Bệnh viện'
                               => 'Đính kèm thư mời số {{SO_THONG_BAO}}/TM-BV ngày {{NGAY_PHAT_HANH}} của Bệnh viện',
];

try {
    // --- 1. Copy file gốc ---
    if (!copy($nguon, $dich)) {
        throw new RuntimeException('Không copy được file gốc sang MPS/');
    }
    say('  + copy file gốc -> MPS/thu_moi.docx');

    // --- 2. Mở document.xml ra thay {{KEY}} ---
    $zip = new ZipArchive();
    if ($zip->open($dich) !== true) {
        throw new RuntimeException('Không mở được file .docx vừa copy');
    }
    $xml = $zip->getFromName('word/document.xml');
    if ($xml === false) {
        $zip->close();
        throw new RuntimeException('File .docx không có word/document.xml');
    }

    // KHONG dung regex gop run tren TOAN BO file: no xoa ca the dong
    // </w:t></w:r> nam trong bang, lam vo cau truc <w:tc>/<w:tr> va Word bao
    // "file bi loi". Chi gop trong PHAM VI TUNG DOAN VAN <w:p>...</w:p>,
    // va chi khi doan do KHONG chua the bang.
    $xml = preg_replace_callback('#<w:p[ >].*?</w:p>#us', static function ($m) {
        $p = $m[0];
        if (strpos($p, '<w:tbl') !== false) return $p;   // bo qua doan co bang
        $p = preg_replace('#</w:t></w:r><w:r(?:\s[^>]*)?><w:t(?:\s[^>]*)?>#u', '', $p);
        $p = preg_replace('#</w:t></w:r><w:r(?:\s[^>]*)?><w:rPr>.*?</w:rPr><w:t(?:\s[^>]*)?>#us', '', $p);
        return $p;
    }, $xml);

    $daThay = 0;
    $truot  = [];
    foreach ($thay as $cu => $moi) {
        // XML escape: & < > trong nội dung Word đã được mã hóa
        $cuX  = htmlspecialchars($cu, ENT_NOQUOTES, 'UTF-8');
        $moiX = htmlspecialchars($moi, ENT_NOQUOTES, 'UTF-8');

        if (mb_strpos($xml, $cuX) !== false) {
            $xml = str_replace($cuX, $moiX, $xml);
            $daThay++;
        } else {
            $truot[] = mb_substr($cu, 0, 55);
        }
    }

    // --- 3. Chèn ảnh QR sau dòng đường dẫn hệ thống ---
    // Đặt {{DUONG_DAN}} + {{@QR}} ngay sau câu "quét mã QR phía dưới"
    $neo = htmlspecialchars('hoặc quét mã QR phía dưới để truy cập.', ENT_NOQUOTES, 'UTF-8');
    if (mb_strpos($xml, $neo) !== false) {
        $xml = str_replace($neo, $neo . '</w:t></w:r></w:p>'
            . '<w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:t xml:space="preserve">{{@QR}}</w:t></w:r></w:p>'
            . '<w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:t xml:space="preserve">'
            . htmlspecialchars('Quét mã QR để vào cổng chào giá', ENT_NOQUOTES, 'UTF-8'),
            $xml);
        $daThay++;
        say('  + chèn chỗ đặt ảnh QR');
    } else {
        $truot[] = '(neo chèn QR)';
    }

    // Kiem XML truoc khi ghi — sinh ra file Word mo khong duoc thi
    // nguoi dung chi thay "file bi loi", rat kho lan ra nguyen nhan.
    $kiem = new DOMDocument();
    libxml_use_internal_errors(true);
    if (!$kiem->loadXML($xml)) {
        $e = libxml_get_errors();
        $zip->close();
        @unlink($dich);
        throw new RuntimeException('XML sinh ra khong hop le: '
            . ($e ? trim($e[0]->message) : 'khong ro') . ' -> da huy file');
    }
    libxml_clear_errors();

    $zip->addFromString('word/document.xml', $xml);
    $zip->close();
    say('  + kiểm tra XML: hợp lệ');

    say('  + thay ' . $daThay . '/' . (count($thay) + 1) . ' chỗ');
    if ($truot) {
        say('');
        say('  ! Các cụm KHÔNG khớp (Word cắt chuỗi thành nhiều đoạn):');
        foreach ($truot as $t) say('      - ' . $t);
        say('    -> mở MPS/thu_moi.docx bằng Word, sửa tay các chỗ đó thành {{KEY}}');
    }

    say('');
    say('  Các key dùng được trong mẫu:');
    say('    {{SO_THONG_BAO}} {{TEN_GOI_THAU}} {{NGAY_PHAT_HANH}} {{NGAY_PHAT_HANH_CHU}}');
    say('    {{THOI_GIAN_MO}} {{THOI_GIAN_DONG}} {{HIEU_LUC}} {{NGAY_HET_HAN}}');
    say('    {{NGUOI_LIEN_HE}} {{CHUC_VU_LIEN_HE}} {{SDT_LIEN_HE}} {{EMAIL_LIEN_HE}}');
    say('    {{DIA_CHI_NHAN}} {{THOI_GIAN_GIAO_HANG}} {{DUONG_DAN}} {{TAI_KHOAN}} {{MAT_KHAU}}');
    say('    {{@QR}} — ảnh mã QR');
    say('');
    say('===========================================================');
    say('  HOÀN TẤT: MPS/thu_moi.docx');
    say('===========================================================');

} catch (Throwable $ex) {
    say('');
    say('!!! LỖI: ' . $ex->getMessage());
    exit(1);
}
