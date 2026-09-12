<?php
/**
 * WordHelper — writer .docx thuần PHP (ZipArchive), không cần Composer.
 *
 * Cùng triết lý với ExcelHelper: chỉ dựng đúng phần OOXML tối thiểu mà Word
 * cần để mở được file, không cố làm thư viện tổng quát.
 *
 * Cách dùng: dựng mảng $blocks rồi gọi write().
 *   ['p'   => 'văn bản', 'style' => 'title|h1|h2|normal|italic|center|right', 'bold' => true]
 *   ['tbl' => [['ô A','ô B'], ...], 'widths' => [1000, 2000], 'header' => true]
 *   ['br']  — ngắt trang
 *   ['sect' => 'ngang'|'doc']  — ngắt SECTION, đổi khổ giấy từ đây trở đi
 *
 * Khổ giấy bám theo "Thư mời báo giá chung 3 nhóm.docx": Letter 12240x15840,
 * KHÔNG phải A4. Đổi ở đây thì mọi file mẫu sinh ra đều theo.
 */
class WordHelper
{
    /** Khổ ngang (landscape) — bảng đáp ứng 21 cột không đủ chỗ ở khổ dọc */
    public const A4_NGANG = 'ngang';
    public const A4_DOC   = 'doc';

    /** Khổ giấy + lề — lấy đúng theo file Thư mời gốc (Letter, không phải A4) */
    private const KHO_W   = 12240;
    private const KHO_H   = 15840;
    private const LE_TREN  = 380;
    private const LE_PHAI  = 850;
    private const LE_DUOI  = 280;
    private const LE_TRAI  = 1275;

    /** Escape ký tự đặc biệt của XML */
    private static function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * Một đoạn văn. Hỗ trợ xuống dòng bằng \n (thành <w:br/>).
     */
    private static function para(string $text, array $opt = []): string
    {
        $style  = $opt['style'] ?? 'normal';
        $bold   = !empty($opt['bold']);
        $italic = !empty($opt['italic']);
        $size   = (int)($opt['size'] ?? 26);          // half-point: 26 = 13pt
        $align  = $opt['align'] ?? null;
        $indent = (int)($opt['indent'] ?? 0);
        $after  = (int)($opt['after'] ?? 60);

        if ($style === 'title')  { $bold = true; $size = 32; $align = 'center'; }
        if ($style === 'h1')     { $bold = true; $size = 28; }
        if ($style === 'h2')     { $bold = true; $size = 26; }
        if ($style === 'italic') { $italic = true; }
        if ($style === 'center') { $align = 'center'; }
        if ($style === 'right')  { $align = 'right'; }

        $pPr = '<w:spacing w:after="' . $after . '" w:line="288" w:lineRule="auto"/>';
        if ($align)  $pPr .= '<w:jc w:val="' . $align . '"/>';
        if ($indent) $pPr .= '<w:ind w:firstLine="' . $indent . '"/>';

        $rPr = '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman"/>'
             . '<w:sz w:val="' . $size . '"/><w:szCs w:val="' . $size . '"/>';
        if ($bold)   $rPr .= '<w:b/>';
        if ($italic) $rPr .= '<w:i/>';

        // \n trong text -> <w:br/>
        $runs = '';
        foreach (explode("\n", $text) as $i => $line) {
            if ($i > 0) $runs .= '<w:r><w:rPr>' . $rPr . '</w:rPr><w:br/></w:r>';
            if ($line === '') continue;
            $runs .= '<w:r><w:rPr>' . $rPr . '</w:rPr>'
                   . '<w:t xml:space="preserve">' . self::esc($line) . '</w:t></w:r>';
        }
        if ($runs === '') $runs = '<w:r><w:rPr>' . $rPr . '</w:rPr></w:r>';

        return '<w:p><w:pPr>' . $pPr . '</w:pPr>' . $runs . '</w:p>';
    }

    /** Một ô của bảng */
    private static function cell(string $text, int $width, bool $header, string $align): string
    {
        $size = $header ? 20 : 20;   // 10pt — bảng 15 cột nên chữ phải nhỏ
        $rPr  = '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman"/>'
              . '<w:sz w:val="' . $size . '"/><w:szCs w:val="' . $size . '"/>'
              . ($header ? '<w:b/>' : '');

        $runs = '';
        foreach (explode("\n", $text) as $i => $line) {
            if ($i > 0) $runs .= '<w:r><w:rPr>' . $rPr . '</w:rPr><w:br/></w:r>';
            $runs .= '<w:r><w:rPr>' . $rPr . '</w:rPr>'
                   . '<w:t xml:space="preserve">' . self::esc($line) . '</w:t></w:r>';
        }

        // File Thư mời gốc KHÔNG tô nền dòng tiêu đề (chỉ nền trắng) — tiêu đề
        // phân biệt bằng in đậm. Tô nền xanh làm bản in khác bản giấy + tốn mực.
        $shd = '';

        return '<w:tc>'
             . '<w:tcPr><w:tcW w:w="' . $width . '" w:type="dxa"/>'
             . $shd
             . '<w:vAlign w:val="center"/></w:tcPr>'
             . '<w:p><w:pPr><w:spacing w:after="0" w:line="240" w:lineRule="auto"/>'
             . '<w:jc w:val="' . $align . '"/></w:pPr>' . $runs . '</w:p>'
             . '</w:tc>';
    }

    /**
     * Bảng có kẻ khung.
     *
     * @param array $rows    Mảng dòng, mỗi dòng là mảng ô (chuỗi)
     * @param array $widths  Bề rộng từng cột (dxa = 1/20 point)
     * @param array $aligns  Căn lề từng cột: left|center|right
     */
    private static function table(array $rows, array $widths, array $aligns = []): string
    {
        // Viền mảnh sz=4 — đúng như bảng trong file Thư mời gốc (trước đây sz=6).
        $borders = '<w:tblBorders>'
                 . '<w:top w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
                 . '<w:left w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
                 . '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
                 . '<w:right w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
                 . '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
                 . '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
                 . '</w:tblBorders>';

        $grid = '<w:tblGrid>';
        foreach ($widths as $w) $grid .= '<w:gridCol w:w="' . (int)$w . '"/>';
        $grid .= '</w:tblGrid>';

        $xml = '<w:tbl><w:tblPr>'
             . '<w:tblW w:w="' . array_sum($widths) . '" w:type="dxa"/>'
             . '<w:jc w:val="center"/>'
             . $borders
             . '<w:tblLayout w:type="fixed"/>'
             . '</w:tblPr>' . $grid;

        foreach ($rows as $ri => $row) {
            $header = ($ri === 0);
            // Dòng tiêu đề lặp lại ở mỗi trang khi bảng bị ngắt trang
            $xml .= '<w:tr>' . ($header ? '<w:trPr><w:tblHeader/></w:trPr>' : '');
            foreach (array_values($row) as $ci => $val) {
                $al = $header ? 'center' : ($aligns[$ci] ?? 'left');
                $xml .= self::cell((string)$val, (int)($widths[$ci] ?? 1000), $header, $al);
            }
            $xml .= '</w:tr>';
        }
        return $xml . '</w:tbl>';
    }

    /**
     * Thuộc tính section (khổ giấy + lề) cho 1 khổ.
     *
     * Dùng chung cho CẢ section giữa tài liệu lẫn section cuối, để hai chỗ
     * không bao giờ lệch nhau khi sửa lề.
     */
    private static function sectPr(string $kho): string
    {
        $ngang = ($kho === self::A4_NGANG);
        $w = $ngang ? self::KHO_H : self::KHO_W;
        $h = $ngang ? self::KHO_W : self::KHO_H;

        // Khổ ngang thì lề trên/dưới đổi vai cho trái/phải để mép in cân đối.
        $tren = $ngang ? self::LE_DUOI : self::LE_TREN;
        $duoi = $ngang ? self::LE_DUOI : self::LE_DUOI;
        $trai = $ngang ? self::LE_PHAI : self::LE_TRAI;
        $phai = $ngang ? self::LE_PHAI : self::LE_PHAI;

        return '<w:pgSz w:w="' . $w . '" w:h="' . $h . '"'
             . ($ngang ? ' w:orient="landscape"' : '') . '/>'
             . '<w:pgMar w:top="' . $tren . '" w:right="' . $phai . '"'
             . ' w:bottom="' . $duoi . '" w:left="' . $trai . '"'
             . ' w:header="720" w:footer="720" w:gutter="0"/>'
             . '<w:cols w:space="720"/>';
    }

    /**
     * Sinh file .docx.
     *
     * @param string $path   Đường dẫn file xuất
     * @param array  $blocks Danh sách block (xem chú thích đầu class)
     * @param string $kho    Khổ của section CUỐI (A4_NGANG hoặc A4_DOC)
     */
    public static function write(string $path, array $blocks, string $kho = self::A4_DOC): void
    {
        $body = '';
        foreach ($blocks as $b) {
            if (isset($b['sect'])) {
                // Ngắt SECTION: phần TRƯỚC đoạn này mang khổ giấy $b['sect'],
                // phần sau lấy khổ của section kế tiếp (hoặc sectPr cuối body).
                // Word quy định sectPr của 1 section nằm ở đoạn CUỐI section đó.
                $body .= '<w:p><w:pPr><w:sectPr>'
                       . self::sectPr((string)$b['sect'])
                       . '</w:sectPr></w:pPr></w:p>';
            } elseif (isset($b['br'])) {
                $body .= '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
            } elseif (isset($b['tbl'])) {
                $body .= self::table($b['tbl'], $b['widths'] ?? [], $b['aligns'] ?? []);
                // Word cần 1 đoạn trống sau bảng, nếu không 2 bảng liền nhau bị dính
                $body .= '<w:p><w:pPr><w:spacing w:after="0"/></w:pPr></w:p>';
            } else {
                $body .= self::para((string)($b['p'] ?? ''), $b);
            }
        }

        $sect = self::sectPr($kho);

        $document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:body>' . $body . '<w:sectPr>' . $sect . '</w:sectPr></w:body></w:document>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            . '</Types>';

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '</Relationships>';

        $docRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';

        // Mặc định Times New Roman 14pt + giãn dòng — lấy đúng docDefaults của
        // file Thư mời gốc (sz=28, spacing before/after=60, line=288).
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:docDefaults><w:rPrDefault><w:rPr>'
            . '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:eastAsia="Times New Roman" w:cs="Times New Roman"/>'
            . '<w:sz w:val="28"/><w:szCs w:val="28"/>'
            . '</w:rPr></w:rPrDefault>'
            . '<w:pPrDefault><w:pPr>'
            . '<w:spacing w:before="60" w:after="60" w:line="288" w:lineRule="auto"/>'
            . '</w:pPr></w:pPrDefault></w:docDefaults>'
            . '<w:style w:type="paragraph" w:default="1" w:styleId="Normal">'
            . '<w:name w:val="Normal"/><w:qFormat/></w:style>'
            . '</w:styles>';

        @unlink($path);
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE) !== true) {
            throw new RuntimeException('Không tạo được file Word: ' . $path);
        }
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('word/_rels/document.xml.rels', $docRels);
        $zip->addFromString('word/document.xml', $document);
        $zip->addFromString('word/styles.xml', $styles);
        $zip->close();
    }

    /** Gửi file .docx về trình duyệt rồi xóa file tạm */
    public static function download(string $path, string $fileName): void
    {
        if (!is_file($path)) throw new RuntimeException('Không tìm thấy file Word.');
        if (ob_get_level()) ob_end_clean();

        $ascii = preg_replace('/[^A-Za-z0-9._-]/', '_', $fileName);

        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($fileName));
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        readfile($path);
        @unlink($path);
        exit;
    }
}
