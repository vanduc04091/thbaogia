<?php
/**
 * ExcelHelper — Đọc / ghi file .xlsx thuần PHP (ZipArchive + XML).
 *
 * Không cần Composer / PhpSpreadsheet. Chỉ dùng ZipArchive + SimpleXML có sẵn.
 *
 * Đọc:  ExcelHelper::readSheet($path)            → mảng 2 chiều [rowIndex => [colIndex => value]]
 * Ghi:  ExcelHelper::write($path, $sheets)        → sinh file xlsx nhiều sheet
 *
 * Giới hạn có chủ ý: không đọc/ghi công thức, không style phức tạp.
 * Style hỗ trợ: header đậm + nền, wrap text, số có phân cách nghìn, viền.
 */
class ExcelHelper
{
    // === Chỉ số style dùng khi ghi (khớp thứ tự cellXfs trong buildStyles) ===
    const S_DEFAULT   = 0;  // text thường
    const S_HEADER    = 1;  // header: đậm, nền xám, wrap, viền, canh giữa
    const S_TEXT_WRAP = 2;  // text wrap + viền + canh trên
    const S_NUMBER    = 3;  // số nguyên phân cách nghìn + viền
    const S_MONEY     = 4;  // tiền tệ phân cách nghìn + viền
    const S_TITLE     = 5;  // tiêu đề lớn, đậm, không viền
    const S_HEADER_ALT= 6;  // header nhóm nhà thầu: đậm, nền xanh nhạt
    const S_BEST      = 7;  // ô giá thấp nhất: nền vàng, đậm
    const S_SUBTITLE  = 8;  // dòng phụ đề, in nghiêng
    const S_CENTER    = 9;  // text canh giữa + viền
    const S_TOTAL     = 10; // ô tổng cộng: đậm, nền xám, có phân cách nghìn

    // =====================================================================
    // ĐỌC
    // =====================================================================

    /**
     * Đọc sheet đầu tiên (hoặc theo tên) của file xlsx.
     *
     * @return array [rowNumber(1-based) => [colIndex(0-based) => string]]
     */
    public static function readSheet(string $path, ?string $sheetName = null): array
    {
        if (!is_file($path)) {
            throw new RuntimeException('Không tìm thấy file: ' . basename($path));
        }
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('File không phải định dạng .xlsx hợp lệ.');
        }

        try {
            $shared = self::readSharedStrings($zip);
            $target = self::resolveSheetPath($zip, $sheetName);

            $xmlStr = $zip->getFromName($target);
            if ($xmlStr === false) {
                throw new RuntimeException('Không đọc được dữ liệu sheet trong file.');
            }

            return self::parseSheetXml($xmlStr, $shared);
        } finally {
            $zip->close();
        }
    }

    /** Danh sách tên sheet trong workbook */
    public static function sheetNames(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) return [];
        try {
            $wb = $zip->getFromName('xl/workbook.xml');
            if ($wb === false) return [];
            $names = [];
            if (preg_match_all('/<sheet[^>]*name="([^"]*)"/', $wb, $m)) {
                foreach ($m[1] as $n) $names[] = self::xmlDecode($n);
            }
            return $names;
        } finally {
            $zip->close();
        }
    }

    private static function readSharedStrings(ZipArchive $zip): array
    {
        $raw = $zip->getFromName('xl/sharedStrings.xml');
        if ($raw === false) return [];

        $strings = [];
        // Tách từng <si>...</si>; mỗi si có thể gồm nhiều <t> (rich text) → nối lại.
        if (preg_match_all('/<si>(.*?)<\/si>/s', $raw, $mSi)) {
            foreach ($mSi[1] as $si) {
                $text = '';
                if (preg_match_all('/<t(?:\s[^>]*)?>(.*?)<\/t>/s', $si, $mT)) {
                    foreach ($mT[1] as $t) $text .= self::xmlDecode($t);
                }
                $strings[] = $text;
            }
        }
        return $strings;
    }

    /** Tìm đường dẫn XML của sheet cần đọc */
    private static function resolveSheetPath(ZipArchive $zip, ?string $sheetName): string
    {
        $wb = $zip->getFromName('xl/workbook.xml');
        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');

        // Map rId → target path
        $relMap = [];
        if ($rels !== false && preg_match_all('/<Relationship[^>]*Id="([^"]+)"[^>]*Target="([^"]+)"/', $rels, $m)) {
            foreach ($m[1] as $i => $rid) {
                $t = $m[2][$i];
                if (strpos($t, '/') !== 0) $t = 'xl/' . ltrim($t, './');
                else $t = ltrim($t, '/');
                $relMap[$rid] = $t;
            }
        }

        if ($wb !== false && preg_match_all('/<sheet[^>]*>/', $wb, $mSheet)) {
            foreach ($mSheet[0] as $tag) {
                preg_match('/name="([^"]*)"/', $tag, $mn);
                preg_match('/r:id="([^"]*)"/', $tag, $mr);
                $name = isset($mn[1]) ? self::xmlDecode($mn[1]) : '';
                $rid  = $mr[1] ?? '';
                $path = $relMap[$rid] ?? '';

                if ($sheetName === null) {
                    // Sheet đầu tiên
                    if ($path !== '' && $zip->locateName($path) !== false) return $path;
                } elseif (mb_strtolower($name) === mb_strtolower($sheetName)) {
                    if ($path !== '' && $zip->locateName($path) !== false) return $path;
                }
            }
        }

        // Fallback: sheet1.xml
        if ($zip->locateName('xl/worksheets/sheet1.xml') !== false) {
            return 'xl/worksheets/sheet1.xml';
        }
        throw new RuntimeException('Không tìm thấy sheet dữ liệu trong file.');
    }

    /**
     * Parse XML sheet → mảng.
     * Dùng XMLReader để không nạp toàn bộ file lớn vào RAM.
     */
    private static function parseSheetXml(string $xmlStr, array $shared): array
    {
        $rows = [];
        $reader = new XMLReader();
        // LIBXML_NOENT: không mở rộng entity ngoài → chặn XXE
        if (!$reader->XML($xmlStr, null, LIBXML_NONET)) {
            throw new RuntimeException('Không đọc được cấu trúc XML của sheet.');
        }

        $curRow = 0;
        try {
            while ($reader->read()) {
                if ($reader->nodeType !== XMLReader::ELEMENT) continue;

                if ($reader->localName === 'row') {
                    $r = $reader->getAttribute('r');
                    $curRow = $r !== null ? (int)$r : $curRow + 1;
                    continue;
                }

                if ($reader->localName === 'c') {
                    $ref  = (string)$reader->getAttribute('r');
                    $type = (string)$reader->getAttribute('t');
                    $col  = self::colIndexFromRef($ref);

                    // Đọc nội dung ô
                    $node = $reader->expand();
                    if (!$node) continue;
                    $value = self::cellValue($node, $type, $shared);

                    if ($value !== '') {
                        $rowNo = $ref !== '' ? (int)preg_replace('/\D/', '', $ref) : $curRow;
                        $rows[$rowNo][$col] = $value;
                    }
                }
            }
        } finally {
            $reader->close();
        }

        ksort($rows);
        foreach ($rows as &$r) ksort($r);
        return $rows;
    }

    private static function cellValue(DOMNode $node, string $type, array $shared): string
    {
        $doc = new DOMDocument();
        $imported = $doc->importNode($node, true);
        $doc->appendChild($imported);

        // inlineStr: <c t="inlineStr"><is><t>...</t></is></c>
        if ($type === 'inlineStr') {
            $text = '';
            foreach ($doc->getElementsByTagName('t') as $t) $text .= $t->textContent;
            return $text;
        }

        $vList = $doc->getElementsByTagName('v');
        if ($vList->length === 0) return '';
        $v = $vList->item(0)->textContent;

        if ($type === 's') {
            $i = (int)$v;
            return $shared[$i] ?? '';
        }
        if ($type === 'b') {
            return $v === '1' ? '1' : '0';
        }
        // n (số), str (công thức trả chuỗi), e (lỗi) → trả nguyên văn
        return $v;
    }

    /** "AB12" → 27 (0-based) */
    public static function colIndexFromRef(string $ref): int
    {
        $letters = preg_replace('/[^A-Za-z]/', '', $ref);
        if ($letters === '') return 0;
        $letters = strtoupper($letters);
        $n = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $n = $n * 26 + (ord($letters[$i]) - 64);
        }
        return $n - 1;
    }

    /** 0 → "A", 27 → "AB" */
    public static function colLetter(int $index): string
    {
        $index++;
        $s = '';
        while ($index > 0) {
            $rem = ($index - 1) % 26;
            $s = chr(65 + $rem) . $s;
            $index = (int)(($index - $rem) / 26);
        }
        return $s;
    }

    private static function xmlDecode(string $s): string
    {
        return html_entity_decode($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    // =====================================================================
    // GHI
    // =====================================================================

    /**
     * Sinh file xlsx.
     *
     * $sheets = [
     *   'TênSheet' => [
     *      'cols' => [12, 30, 40, ...],                    // độ rộng cột (tùy chọn)
     *      'freeze' => 'A2',                               // đóng băng (tùy chọn)
     *      'merges' => ['A1:D1'],                          // gộp ô (tùy chọn)
     *      'rows' => [
     *         [ ['v'=>'Tên', 's'=>ExcelHelper::S_HEADER], ... ],   // mỗi ô: v + s(style) + t('n'|'s')
     *         [ 'chuỗi thường', 123, null ],                       // hoặc giá trị trực tiếp
     *      ],
     *      'heights' => [1 => 40],                         // chiều cao dòng (1-based, tùy chọn)
     *   ],
     * ];
     */
    public static function write(string $path, array $sheets): void
    {
        if (empty($sheets)) throw new InvalidArgumentException('Không có sheet nào để ghi.');

        $dir = dirname($path);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Không tạo được thư mục xuất file.');
        }

        // Gom chuỗi dùng chung để giảm dung lượng
        $sst = [];      // text => index
        $sstOrder = [];

        $sheetXmls = [];
        $index = 0;
        foreach ($sheets as $name => $def) {
            $index++;
            $sheetXmls[$index] = [
                'name' => self::safeSheetName((string)$name, $index),
                'xml'  => self::buildSheetXml($def, $sst, $sstOrder),
            ];
        }

        @unlink($path);
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Không tạo được file xuất.');
        }

        $zip->addFromString('[Content_Types].xml', self::buildContentTypes(count($sheetXmls)));
        $zip->addFromString('_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');

        $zip->addFromString('xl/workbook.xml', self::buildWorkbook($sheetXmls));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::buildWorkbookRels(count($sheetXmls)));
        $zip->addFromString('xl/styles.xml', self::buildStyles());
        $zip->addFromString('xl/sharedStrings.xml', self::buildSharedStrings($sstOrder));

        foreach ($sheetXmls as $i => $s) {
            $zip->addFromString("xl/worksheets/sheet{$i}.xml", $s['xml']);
        }

        if (!$zip->close()) {
            throw new RuntimeException('Không ghi được file xuất.');
        }
    }

    /** Excel: tên sheet ≤ 31 ký tự, không chứa : \ / ? * [ ] */
    private static function safeSheetName(string $name, int $index): string
    {
        $name = str_replace([':', '\\', '/', '?', '*', '[', ']'], '-', $name);
        $name = trim($name);
        if ($name === '') $name = 'Sheet' . $index;
        return mb_substr($name, 0, 31);
    }

    private static function buildSheetXml(array $def, array &$sst, array &$sstOrder): string
    {
        $rows    = $def['rows']    ?? [];
        $cols    = $def['cols']    ?? [];
        $freeze  = $def['freeze']  ?? '';
        $merges  = $def['merges']  ?? [];
        $heights = $def['heights'] ?? [];

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

        // Đóng băng dòng/cột
        $xml .= '<sheetViews><sheetView workbookViewId="0">';
        if ($freeze !== '') {
            $rowNo = (int)preg_replace('/\D/', '', $freeze);
            $colNo = self::colIndexFromRef($freeze);
            $split = [];
            if ($colNo > 0) $split[] = 'xSplit="' . $colNo . '"';
            if ($rowNo > 1) $split[] = 'ySplit="' . ($rowNo - 1) . '"';
            if ($split) {
                $xml .= '<pane ' . implode(' ', $split)
                      . ' topLeftCell="' . self::esc($freeze) . '" activePane="bottomRight" state="frozen"/>';
            }
        }
        $xml .= '</sheetView></sheetViews>';

        $xml .= '<sheetFormatPr defaultRowHeight="15"/>';

        if ($cols) {
            $xml .= '<cols>';
            foreach ($cols as $i => $w) {
                $n = $i + 1;
                $xml .= '<col min="' . $n . '" max="' . $n . '" width="' . (float)$w . '" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }

        $xml .= '<sheetData>';
        $rowNo = 0;
        foreach ($rows as $row) {
            $rowNo++;
            $h = isset($heights[$rowNo]) ? ' ht="' . (float)$heights[$rowNo] . '" customHeight="1"' : '';
            $xml .= '<row r="' . $rowNo . '"' . $h . '>';

            $colNo = -1;
            foreach ((array)$row as $cell) {
                $colNo++;
                if ($cell === null || $cell === '') {
                    // Vẫn ghi ô rỗng nếu có style (để giữ viền bảng)
                    if (is_array($cell) && isset($cell['s'])) {
                        $ref = self::colLetter($colNo) . $rowNo;
                        $xml .= '<c r="' . $ref . '" s="' . (int)$cell['s'] . '"/>';
                    }
                    continue;
                }

                if (is_array($cell)) {
                    $v     = $cell['v'] ?? '';
                    $style = (int)($cell['s'] ?? self::S_DEFAULT);
                    $type  = $cell['t'] ?? null;
                } else {
                    $v = $cell;
                    $style = self::S_DEFAULT;
                    $type = null;
                }

                if ($v === null || $v === '') {
                    $ref = self::colLetter($colNo) . $rowNo;
                    $xml .= '<c r="' . $ref . '" s="' . $style . '"/>';
                    continue;
                }

                // Tự suy kiểu nếu không chỉ định
                if ($type === null) {
                    $type = (is_int($v) || is_float($v)) ? 'n' : 's';
                }

                $ref = self::colLetter($colNo) . $rowNo;
                if ($type === 'n') {
                    $num = is_numeric($v) ? $v + 0 : 0;
                    $xml .= '<c r="' . $ref . '" s="' . $style . '"><v>'
                          . self::numToStr($num) . '</v></c>';
                } else {
                    $text = (string)$v;
                    if (!isset($sst[$text])) {
                        $sst[$text] = count($sstOrder);
                        $sstOrder[] = $text;
                    }
                    $xml .= '<c r="' . $ref . '" s="' . $style . '" t="s"><v>' . $sst[$text] . '</v></c>';
                }
            }
            $xml .= '</row>';
        }
        $xml .= '</sheetData>';

        if ($merges) {
            $xml .= '<mergeCells count="' . count($merges) . '">';
            foreach ($merges as $m) {
                $xml .= '<mergeCell ref="' . self::esc($m) . '"/>';
            }
            $xml .= '</mergeCells>';
        }

        $xml .= '</worksheet>';
        return $xml;
    }

    /** Số → chuỗi không dùng ký hiệu khoa học, dấu chấm thập phân */
    private static function numToStr($n): string
    {
        if (is_int($n)) return (string)$n;
        $s = rtrim(rtrim(number_format((float)$n, 6, '.', ''), '0'), '.');
        return $s === '' || $s === '-' ? '0' : $s;
    }

    private static function buildSharedStrings(array $order): string
    {
        $n = count($order);
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . $n . '" uniqueCount="' . $n . '">';
        foreach ($order as $s) {
            $xml .= '<si><t xml:space="preserve">' . self::esc($s) . '</t></si>';
        }
        return $xml . '</sst>';
    }

    private static function buildContentTypes(int $sheetCount): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
             . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
             . '<Default Extension="xml" ContentType="application/xml"/>'
             . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
             . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
             . '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $xml .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        return $xml . '</Types>';
    }

    private static function buildWorkbook(array $sheets): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
             . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
        foreach ($sheets as $i => $s) {
            $xml .= '<sheet name="' . self::esc($s['name']) . '" sheetId="' . $i . '" r:id="rId' . $i . '"/>';
        }
        return $xml . '</sheets></workbook>';
    }

    private static function buildWorkbookRels(int $sheetCount): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $xml .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
        }
        $next = $sheetCount + 1;
        $xml .= '<Relationship Id="rId' . $next . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        $next++;
        $xml .= '<Relationship Id="rId' . $next . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>';
        return $xml . '</Relationships>';
    }

    /**
     * Bảng style. Thứ tự cellXfs PHẢI khớp các hằng S_* ở đầu class.
     */
    private static function buildStyles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        // numFmt 164 = số nguyên phân cách nghìn; 165 = tiền tệ
        . '<numFmts count="2">'
        . '<numFmt numFmtId="164" formatCode="#,##0"/>'
        . '<numFmt numFmtId="165" formatCode="#,##0"/>'
        . '</numFmts>'
        // fonts: 0 thường, 1 đậm, 2 tiêu đề lớn, 3 nghiêng
        . '<fonts count="4">'
        . '<font><sz val="10"/><name val="Calibri"/></font>'
        . '<font><b/><sz val="10"/><name val="Calibri"/></font>'
        . '<font><b/><sz val="14"/><name val="Calibri"/></font>'
        . '<font><i/><sz val="10"/><color rgb="FF595959"/><name val="Calibri"/></font>'
        . '</fonts>'
        // fills: 0 none, 1 gray125 (bắt buộc), 2 xám header, 3 xanh nhạt, 4 vàng
        . '<fills count="5">'
        . '<fill><patternFill patternType="none"/></fill>'
        . '<fill><patternFill patternType="gray125"/></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFE8EDF2"/><bgColor indexed="64"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFDCFCE7"/><bgColor indexed="64"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFFEF3C7"/><bgColor indexed="64"/></patternFill></fill>'
        . '</fills>'
        // borders: 0 none, 1 mảnh 4 phía
        . '<borders count="2">'
        . '<border><left/><right/><top/><bottom/><diagonal/></border>'
        . '<border>'
        . '<left style="thin"><color rgb="FFBFBFBF"/></left>'
        . '<right style="thin"><color rgb="FFBFBFBF"/></right>'
        . '<top style="thin"><color rgb="FFBFBFBF"/></top>'
        . '<bottom style="thin"><color rgb="FFBFBFBF"/></bottom>'
        . '<diagonal/></border>'
        . '</borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        // === cellXfs: thứ tự khớp S_* ===
        . '<cellXfs count="11">'
        // 0 S_DEFAULT
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="top"/></xf>'
        // 1 S_HEADER
        . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'
        . '<alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
        // 2 S_TEXT_WRAP
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1">'
        . '<alignment vertical="top" wrapText="1"/></xf>'
        // 3 S_NUMBER
        . '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1">'
        . '<alignment horizontal="right" vertical="top"/></xf>'
        // 4 S_MONEY
        . '<xf numFmtId="165" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1">'
        . '<alignment horizontal="right" vertical="top"/></xf>'
        // 5 S_TITLE
        . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1">'
        . '<alignment horizontal="left" vertical="center"/></xf>'
        // 6 S_HEADER_ALT
        . '<xf numFmtId="0" fontId="1" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'
        . '<alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
        // 7 S_BEST
        . '<xf numFmtId="165" fontId="1" fillId="4" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'
        . '<alignment horizontal="right" vertical="top"/></xf>'
        // 8 S_SUBTITLE
        . '<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1">'
        . '<alignment horizontal="left" vertical="center"/></xf>'
        // 9 S_CENTER
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1">'
        . '<alignment horizontal="center" vertical="top" wrapText="1"/></xf>'
        // 10 S_TOTAL — như S_HEADER nhưng có định dạng số để tổng tiền hiện phân cách nghìn
        . '<xf numFmtId="165" fontId="1" fillId="2" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'
        . '<alignment horizontal="right" vertical="center"/></xf>'
        . '</cellXfs>'
        . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
        . '</styleSheet>';
    }

    private static function esc(string $s): string
    {
        // Loại ký tự điều khiển XML không cho phép (trừ tab/LF/CR)
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $s);
        return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * Gửi file xlsx xuống browser rồi xóa file tạm.
     * Gọi khi CHƯA có output nào khác.
     */
    public static function download(string $path, string $fileName): void
    {
        if (!is_file($path)) throw new RuntimeException('Không tìm thấy file xuất.');
        if (ob_get_level()) ob_end_clean();

        // Tên file an toàn cho header (RFC 5987 cho ký tự Unicode)
        $ascii = preg_replace('/[^A-Za-z0-9._-]/', '_', $fileName);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($fileName));
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        readfile($path);
        @unlink($path);
        exit;
    }

    // =====================================================================
    // TIỆN ÍCH PARSE GIÁ TRỊ TỪ Ô EXCEL
    // =====================================================================

    /**
     * Chuyển ô Excel về số. Xử lý các kiểu người dùng hay nhập:
     * "10.000,00" / "10,000.00" / "1000" / "5%" / "0,05" / " 1 000 "
     */
    public static function toNumber($raw): float
    {
        if ($raw === null || $raw === '') return 0.0;
        if (is_int($raw) || is_float($raw)) return (float)$raw;

        $s = trim((string)$raw);
        if ($s === '') return 0.0;

        $isPercent = strpos($s, '%') !== false;
        // Bỏ mọi ký tự không phải số, dấu phân cách, dấu âm
        $s = preg_replace('/[^0-9,.\-]/', '', $s);
        if ($s === '' || $s === '-') return 0.0;

        $lastDot   = strrpos($s, '.');
        $lastComma = strrpos($s, ',');

        if ($lastDot !== false && $lastComma !== false) {
            // Dấu xuất hiện sau cùng là dấu thập phân
            if ($lastComma > $lastDot) {
                $s = str_replace('.', '', $s);      // '.' là phân cách nghìn
                $s = str_replace(',', '.', $s);
            } else {
                $s = str_replace(',', '', $s);      // ',' là phân cách nghìn
            }
        } elseif ($lastComma !== false) {
            // Chỉ có ',': nếu đúng 3 số sau nó thì coi là phân cách nghìn
            $after = strlen($s) - $lastComma - 1;
            $s = ($after === 3 && substr_count($s, ',') >= 1 && strlen($s) > 4)
                ? str_replace(',', '', $s)
                : str_replace(',', '.', $s);
        } elseif ($lastDot !== false) {
            $after = strlen($s) - $lastDot - 1;
            // "10.000" → 10000 ; "10.5" → 10.5
            if ($after === 3 && substr_count($s, '.') >= 1 && strlen($s) > 4) {
                $s = str_replace('.', '', $s);
            }
        }

        $n = is_numeric($s) ? (float)$s : 0.0;
        // "5%" → 5 (giữ đơn vị %, không chia 100). "0,05" ở cột VAT xử lý riêng ở BUS.
        return $isPercent ? $n : $n;
    }

    /** Chuẩn hóa ô text: trim, gộp khoảng trắng đầu/cuối dòng */
    public static function toText($raw, int $maxLen = 0): string
    {
        $s = trim((string)($raw ?? ''));
        $s = str_replace(["\r\n", "\r"], "\n", $s);
        if ($maxLen > 0 && mb_strlen($s) > $maxLen) {
            $s = mb_substr($s, 0, $maxLen);
        }
        return $s;
    }
}
