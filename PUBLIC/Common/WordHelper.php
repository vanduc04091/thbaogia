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
 */
class WordHelper
{
    /** Khổ A4 ngang (landscape) — bảng chào giá 15 cột không đủ chỗ ở khổ dọc */
    public const A4_NGANG = 'ngang';
    public const A4_DOC   = 'doc';

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

        $shd = $header ? '<w:shd w:val="clear" w:color="auto" w:fill="D9E2F3"/>' : '';

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
        $borders = '<w:tblBorders>'
                 . '<w:top w:val="single" w:sz="6" w:color="000000"/>'
                 . '<w:left w:val="single" w:sz="6" w:color="000000"/>'
                 . '<w:bottom w:val="single" w:sz="6" w:color="000000"/>'
                 . '<w:right w:val="single" w:sz="6" w:color="000000"/>'
                 . '<w:insideH w:val="single" w:sz="6" w:color="000000"/>'
                 . '<w:insideV w:val="single" w:sz="6" w:color="000000"/>'
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
     * Sinh file .docx.
     *
     * @param string $path   Đường dẫn file xuất
     * @param array  $blocks Danh sách block (xem chú thích đầu class)
     * @param string $kho    A4_NGANG hoặc A4_DOC
     */
    public static function write(string $path, array $blocks, string $kho = self::A4_DOC): void
    {
        $body = '';
        foreach ($blocks as $b) {
            if (isset($b['br'])) {
                $body .= '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
            } elseif (isset($b['tbl'])) {
                $body .= self::table($b['tbl'], $b['widths'] ?? [], $b['aligns'] ?? []);
                // Word cần 1 đoạn trống sau bảng, nếu không 2 bảng liền nhau bị dính
                $body .= '<w:p><w:pPr><w:spacing w:after="0"/></w:pPr></w:p>';
            } else {
                $body .= self::para((string)($b['p'] ?? ''), $b);
            }
        }

        // A4: 11906 x 16838 twips
        $sect = $kho === self::A4_NGANG
            ? '<w:pgSz w:w="16838" w:h="11906" w:orient="landscape"/>'
              . '<w:pgMar w:top="850" w:right="850" w:bottom="850" w:left="850"'
              . ' w:header="708" w:footer="708" w:gutter="0"/>'
            : '<w:pgSz w:w="11906" w:h="16838"/>'
              . '<w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1418"'
              . ' w:header="708" w:footer="708" w:gutter="0"/>';

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

        // Font mặc định Times New Roman 13pt cho toàn tài liệu
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:docDefaults><w:rPrDefault><w:rPr>'
            . '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:eastAsia="Times New Roman" w:cs="Times New Roman"/>'
            . '<w:sz w:val="26"/><w:szCs w:val="26"/>'
            . '</w:rPr></w:rPrDefault></w:docDefaults>'
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
