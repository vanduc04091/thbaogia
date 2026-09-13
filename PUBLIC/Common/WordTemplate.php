<?php
/**
 * WordTemplate — điền dữ liệu vào file .docx mẫu trong thư mục MPS/.
 *
 * Ý tưởng: KHÔNG dựng văn bản bằng code nữa. Người dùng tự mở file mẫu trong
 * Word, sửa chữ / cỡ chữ / căn lề / logo tùy ý, chỉ cần giữ nguyên các Key.
 * Code chỉ việc thay Key bằng dữ liệu thật.
 *
 * ===== 2 LOẠI KEY =====
 *
 * 1. Key thường — thay bằng 1 giá trị:
 *        {{TEN_CONG_TY}}, {{MST}}, {{TONG_TIEN}}...
 *
 * 2. Key dòng lặp — đặt trong 1 dòng của BẢNG, dòng đó sẽ được nhân bản
 *    cho mỗi bản ghi:
 *        {{#HANG_HOA}}  ... {{STT}} | {{MA_HH}} | {{DON_GIA}} ...
 *    Chỉ cần đặt các key con trong cùng dòng bảng; hệ thống tự nhận ra dòng
 *    nào là dòng mẫu nhờ có key thuộc nhóm đó.
 *
 * ===== VÌ SAO PHẢI GOM RUN =====
 * Word hay cắt 1 chuỗi thành nhiều <w:r> (do sửa chữ, bật kiểm tra chính tả...).
 * Ví dụ "{{MST}}" có thể nằm rải ở 3 run: "{{M", "S", "T}}". Nếu thay thẳng
 * trên XML sẽ không khớp. Vì vậy phải gom các run trong cùng đoạn lại trước.
 */
class WordTemplate
{
    /** Thư mục chứa file mẫu */
    public static function thuMuc(): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'MPS';
    }

    /** Đường dẫn 1 file mẫu; ném lỗi nếu không có */
    public static function duongDan(string $ten): string
    {
        // Chặn path traversal — chỉ cho tên file thuần
        $ten = basename($ten);
        $path = self::thuMuc() . DIRECTORY_SEPARATOR . $ten;
        if (!is_file($path)) {
            throw new RuntimeException('Không tìm thấy file mẫu: MPS/' . $ten);
        }
        return $path;
    }

    /** Liệt kê các file mẫu đang có */
    public static function danhSach(): array
    {
        $dir = self::thuMuc();
        if (!is_dir($dir)) return [];
        $out = [];
        foreach (scandir($dir) as $f) {
            if (strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'docx' && $f[0] !== '~') {
                $out[] = $f;
            }
        }
        sort($out);
        return $out;
    }

    private static function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * Gom các <w:r> liền nhau trong 1 <w:p> thành 1 run duy nhất,
     * để key bị Word cắt rời vẫn ghép lại được.
     *
     * Giữ lại <w:rPr> của run ĐẦU TIÊN có chứa key (định dạng do người dùng đặt).
     */
    private static function gomRun(string $xml): string
    {
        return preg_replace_callback('/<w:p(?:\s[^>]*)?>.*?<\/w:p>/s', function ($m) {
            $p = $m[0];
            // Chỉ xử lý đoạn có dấu hiệu của key
            if (strpos($p, '{{') === false && strpos($p, '{') === false) return $p;

            // Lấy toàn bộ text của đoạn
            $text = '';
            preg_match_all('/<w:t(?:\s[^>]*)?>(.*?)<\/w:t>/s', $p, $mm);
            foreach ($mm[1] as $t) $text .= $t;

            if (strpos($text, '{{') === false) return $p;

            // rPr của run đầu tiên
            $rPr = '';
            if (preg_match('/<w:r(?:\s[^>]*)?>\s*(<w:rPr>.*?<\/w:rPr>)/s', $p, $r)) {
                $rPr = $r[1];
            }

            // pPr giữ nguyên
            $pPr = '';
            if (preg_match('/<w:pPr>.*?<\/w:pPr>/s', $p, $r)) $pPr = $r[0];

            // Thẻ mở <w:p ...>
            preg_match('/^<w:p(?:\s[^>]*)?>/', $p, $open);

            return $open[0] . $pPr
                 . '<w:r>' . $rPr . '<w:t xml:space="preserve">' . $text . '</w:t></w:r>'
                 . '</w:p>';
        }, $xml);
    }

    /**
     * Thay 1 key thường trong đoạn XML.
     * Hỗ trợ xuống dòng: \n trong giá trị -> <w:br/>
     */
    private static function thayKey(string $xml, string $key, string $val): string
    {
        $val = (string)$val;

        if (strpos($val, "\n") !== false) {
            // Tách dòng bằng <w:br/> — phải chèn giữa 2 thẻ <w:t>
            $parts = array_map([self::class, 'esc'], explode("\n", $val));
            $rep = implode('</w:t><w:br/><w:t xml:space="preserve">', $parts);
        } else {
            $rep = self::esc($val);
        }

        return str_replace('{{' . $key . '}}', $rep, $xml);
    }

    /**
     * Nhân bản dòng bảng chứa nhóm key lặp.
     *
     * @param string $xml   XML document
     * @param string $nhom  Tên nhóm, vd 'HANG_HOA' (đánh dấu bằng {{#HANG_HOA}})
     * @param array  $rows  Mỗi phần tử là map [KEY => giá trị]
     */
    private static function lapDong(string $xml, string $nhom, array $rows): string
    {
        $moc = '{{#' . $nhom . '}}';
        if (strpos($xml, $moc) === false) return $xml;

        // Tìm <w:tr> chứa mốc.
        // PHẢI khớp đúng thẻ <w:tr> mở — KHÔNG được dùng strrpos('<w:tr') vì
        // nó bắt nhầm <w:trPr> (thuộc tính dòng) mà Word tự chèn khi người dùng
        // sửa file. Cắt từ giữa <w:trPr> ra sẽ sinh XML hỏng và Word gộp mọi
        // dòng lặp thành 1. Đã gặp thật khi mở file mẫu bằng Word rồi lưu lại.
        $viTri = strpos($xml, $moc);
        $truoc = substr($xml, 0, $viTri);
        if (!preg_match_all('/<w:tr(?:\s[^>]*)?>/', $truoc, $mm, PREG_OFFSET_CAPTURE)) {
            return $xml;
        }
        $cuoiCung = end($mm[0]);
        $trDau = $cuoiCung[1];

        $trCuoi = strpos($xml, '</w:tr>', $viTri);
        if ($trCuoi === false) return $xml;
        $trCuoi += strlen('</w:tr>');

        $mauDong = substr($xml, $trDau, $trCuoi - $trDau);
        $mauSach = str_replace($moc, '', $mauDong);

        $ra = '';
        foreach ($rows as $r) {
            $d = $mauSach;
            foreach ($r as $k => $v) {
                $d = self::thayKey($d, $k, (string)$v);
            }
            // Key thừa trong mẫu mà dữ liệu không có -> xóa
            $d = preg_replace('/\{\{[A-Z0-9_]+\}\}/u', '', $d);
            $ra .= $d;
        }

        // Không có dữ liệu -> vẫn bỏ dòng mẫu đi
        return substr($xml, 0, $trDau) . $ra . substr($xml, $trCuoi);
    }

    /**
     * Sinh file .docx từ mẫu.
     *
     * @param string $tenMau  Tên file trong MPS/, vd 'bao_gia.docx'
     * @param string $dich    Đường dẫn file kết quả
     * @param array  $data    Key thường: ['TEN_CONG_TY' => 'ABC', ...]
     * @param array  $bang    Nhóm lặp:   ['HANG_HOA' => [ [KEY=>val], ... ], ...]
     */
    /**
     * @param array $anh Anh chen vao: ['QR' => ['path' => '...', 'w' => 40, 'h' => 40]]
     *                   Trong mau dat key dang {{@QR}}. Kich thuoc tinh bang mm.
     */
    /**
     * Bỏ hẳn một số CỘT khỏi bảng trong file .docx.
     *
     * Mẫu Word là TĨNH: bảng đáp ứng luôn 21 cột. Nhưng nhóm vật tư dược không
     * có yêu cầu chung/khác/cấu hình, nhóm bộ dụng cụ không có yêu cầu cấu
     * hình — để cột trống thì bản in ra thừa 9 (hoặc 3) cột rỗng, ép bề ngang
     * các cột còn lại nhỏ đến mức không đọc nổi.
     *
     * PHẢI cắt ĐỒNG THỜI 3 chỗ, thiếu một là Word báo file hỏng:
     *   1. <w:gridCol> trong <w:tblGrid>  (khai báo lưới cột)
     *   2. <w:tc> tương ứng trên MỌI <w:tr>
     *   3. w:w của <w:tblW>               (tổng bề ngang bảng)
     *
     * Chỉ dùng được khi bảng KHÔNG có gridSpan / vMerge — ô gộp làm chỉ số cột
     * lệch khỏi thứ tự <w:tc>. Hàm tự kiểm và bỏ qua bảng có ô gộp thay vì
     * sinh ra file hỏng.
     *
     * @param int    $soBang Thứ tự bảng trong tài liệu (1-based)
     * @param int[]  $boCot  Chỉ số cột cần bỏ (0-based), theo bảng GỐC
     */
    public static function boCot(string $xml, int $soBang, array $boCot): string
    {
        if (!$boCot) return $xml;

        // Duyệt từng <w:tbl> để lấy đúng bảng thứ $soBang
        $viTri = 0;
        $dem   = 0;
        while (($dau = strpos($xml, '<w:tbl>', $viTri)) !== false) {
            $cuoi = strpos($xml, '</w:tbl>', $dau);
            if ($cuoi === false) break;
            $cuoi += strlen('</w:tbl>');
            $dem++;

            if ($dem === $soBang) {
                $tbl = substr($xml, $dau, $cuoi - $dau);
                $moi = self::boCotTrongBang($tbl, $boCot);
                return substr($xml, 0, $dau) . $moi . substr($xml, $cuoi);
            }
            $viTri = $cuoi;
        }
        return $xml;
    }

    /**
     * Cắt cột trong XML của MỘT bảng — xem boCot().
     *
     * XỬ LÝ Ô GỘP NGANG (gridSpan): một <w:tc> có thể phủ nhiều cột, nên chỉ
     * số cột KHÔNG trùng thứ tự <w:tc>. Phải cộng dồn gridSpan để biết mỗi ô
     * phủ những cột nào:
     *   - Ô phủ TOÀN BỘ cột bị bỏ  -> xóa hẳn ô.
     *   - Ô phủ MỘT PHẦN           -> giữ ô, GIẢM gridSpan đúng số cột bỏ đi.
     * Ví dụ tiêu đề 2 tầng của Phụ lục I: ô "Yêu cầu mời chào giá" gridSpan=10
     * phủ cột 0-9; bỏ cột 5,6,7 thì ô đó còn gridSpan=7.
     *
     * vMerge (gộp DỌC) không ảnh hưởng chỉ số cột ngang -> bỏ qua an toàn.
     */
    private static function boCotTrongBang(string $tbl, array $boCot): string
    {
        $bo = array_flip($boCot);

        // --- 1 + 3. <w:tblGrid>: bỏ gridCol, cộng lại bề ngang ---
        $rongBo   = 0;   // tổng bề ngang các cột BỊ BỎ
        $tongGrid = 0;   // tổng bề ngang MỌI cột trước khi cắt
        $tbl = preg_replace_callback(
            '/<w:tblGrid>(.*?)<\/w:tblGrid>/s',
            function ($m) use ($bo, &$rongBo, &$tongGrid) {
                $i = 0;
                $ra = preg_replace_callback(
                    '/<w:gridCol\b[^>]*\/>/',
                    function ($g) use ($bo, &$i, &$rongBo, &$tongGrid) {
                        $rong = preg_match('/w:w="(\d+)"/', $g[0], $w) ? (int)$w[1] : 0;
                        $tongGrid += $rong;

                        $giu = !isset($bo[$i]);
                        if (!$giu) $rongBo += $rong;
                        $i++;
                        return $giu ? $g[0] : '';
                    },
                    $m[1]
                );
                return '<w:tblGrid>' . $ra . '</w:tblGrid>';
            },
            $tbl,
            1
        );

        // Cập nhật <w:tblW> cho khớp lưới cột mới.
        //
        // CHỈ trừ khi tblW đang ĐÚNG BẰNG tổng gridCol cũ. Nhiều mẫu Word đặt
        // tblW là con số tượng trưng (vd 5000) hoặc 0 = "tự co theo nội dung";
        // trừ mù vào đó ra số vô nghĩa (5000 - 1907 = 3093) làm Word dựng bảng
        // sai bề ngang. Gặp thật ở mẫu thu_moi.docx: tblW=5000 nhưng tổng
        // gridCol là 14788.
        if ($rongBo > 0) {
            $tongCu = $tongGrid;              // tổng gridCol TRƯỚC khi cắt
            $tbl = preg_replace_callback(
                '/<w:tblW\b[^>]*\/>/',
                function ($m) use ($rongBo, $tongCu) {
                    return preg_replace_callback(
                        '/w:w="(\d+)"/',
                        function ($w) use ($rongBo, $tongCu) {
                            $cu = (int)$w[1];
                            // Không phải bề rộng thật -> để nguyên
                            if ($cu === 0 || $cu !== $tongCu) return $w[0];
                            return 'w:w="' . max(0, $cu - $rongBo) . '"';
                        },
                        $m[0]
                    );
                },
                $tbl,
                1
            );
        }

        // --- 2. Mỗi <w:tr>: bỏ <w:tc> theo đúng chỉ số CỘT (không phải thứ tự ô) ---
        return preg_replace_callback('/<w:tr(?:\s[^>]*)?>.*?<\/w:tr>/s', function ($m) use ($bo) {
            $tr     = $m[0];
            $cotDau = 0;   // cột đầu tiên mà ô hiện tại phủ
            $ra     = '';
            $vt     = 0;

            // Cắt thủ công theo cặp <w:tc> ... </w:tc> — regex lồng nhau không
            // dùng được vì trong ô còn có <w:tcPr>, <w:p>, thậm chí bảng con.
            while (($d = strpos($tr, '<w:tc>', $vt)) !== false) {
                $c = strpos($tr, '</w:tc>', $d);
                if ($c === false) break;
                $c += strlen('</w:tc>');

                $o    = substr($tr, $d, $c - $d);
                $span = 1;
                if (preg_match('/<w:gridSpan\s+w:val="(\d+)"/', $o, $g)) {
                    $span = max(1, (int)$g[1]);
                }

                // Đếm xem ô này phủ bao nhiêu cột nằm trong danh sách bỏ
                $soBo = 0;
                for ($k = 0; $k < $span; $k++) {
                    if (isset($bo[$cotDau + $k])) $soBo++;
                }

                $ra .= substr($tr, $vt, $d - $vt);   // phần giữa các ô

                if ($soBo === 0) {
                    $ra .= $o;                        // giữ nguyên
                } elseif ($soBo < $span) {
                    // Ô gộp bị bỏ một phần -> thu hẹp gridSpan, giữ nội dung
                    $conLai = $span - $soBo;
                    if ($conLai > 1) {
                        $o = preg_replace(
                            '/<w:gridSpan\s+w:val="\d+"\s*\/>/',
                            '<w:gridSpan w:val="' . $conLai . '"/>',
                            $o,
                            1
                        );
                    } else {
                        // Còn đúng 1 cột thì bỏ hẳn thẻ gridSpan cho sạch
                        $o = preg_replace('/<w:gridSpan\s+w:val="\d+"\s*\/>/', '', $o, 1);
                    }
                    $ra .= $o;
                }
                // $soBo === $span -> ô phủ toàn cột bị bỏ -> xóa hẳn

                $cotDau += $span;
                $vt = $c;
            }
            $ra .= substr($tr, $vt);
            return $ra;
        }, $tbl);
    }

    public static function render(string $tenMau, string $dich, array $data,
                                  array $bang = [], array $anh = [],
                                  array $boCot = []): string
    {
        $nguon = self::duongDan($tenMau);

        // Copy nguyên file mẫu rồi chỉ sửa document.xml → giữ toàn bộ định dạng,
        // font, logo, header/footer mà người dùng đã đặt trong Word.
        @unlink($dich);
        if (!copy($nguon, $dich)) {
            throw new RuntimeException('Không tạo được file từ mẫu: ' . $tenMau);
        }

        $zip = new ZipArchive();
        if ($zip->open($dich) !== true) {
            throw new RuntimeException('Không mở được file docx: ' . $dich);
        }

        $xml = $zip->getFromName('word/document.xml');
        if ($xml === false) {
            $zip->close();
            throw new RuntimeException('File mẫu hỏng (thiếu document.xml): ' . $tenMau);
        }

        $xml = self::gomRun($xml);

        // Bảng lặp trước (vì dòng mẫu có thể chứa key thường)
        foreach ($bang as $nhom => $rows) {
            $xml = self::lapDong($xml, $nhom, $rows);
        }

        foreach ($data as $k => $v) {
            $xml = self::thayKey($xml, $k, (string)$v);
        }

        // Chèn ảnh (nếu có) — làm sau bảng lặp, trước khi dọn key thừa
        if (!empty($anh)) {
            $xml = self::chenAnh($zip, $xml, $anh);
        }

        // Bỏ cột KHÔNG áp dụng cho nhóm — làm SAU khi lặp dòng để mọi <w:tr>
        // (kể cả dòng vừa nhân bản) đều bị cắt cùng một chỉ số cột.
        // $boCot = [số bảng (1-based) => [chỉ số cột 0-based cần bỏ]]
        foreach ($boCot as $soBang => $cot) {
            $xml = self::boCot($xml, (int)$soBang, (array)$cot);
        }

        // Dọn key còn sót để file không lộ {{...}}
        $xml = preg_replace('/\{\{[@#]?[A-Z0-9_]+\}\}/u', '', $xml);

        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

        return $dich;
    }

    /**
     * Thay {{@KEY}} bang anh.
     *
     * Anh duoc them vao word/media/ + khai bao trong document.xml.rels,
     * dong thoi bao dam [Content_Types].xml co khai bao duoi png.
     */
    private static function chenAnh(ZipArchive $zip, string $xml, array $anh): string
    {
        $rels = $zip->getFromName('word/_rels/document.xml.rels');
        if ($rels === false) {
            $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                  . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                  . '</Relationships>';
        }

        // Tim so rId lon nhat de khong trung
        preg_match_all('/Id="rId(\d+)"/', $rels, $mm);
        $maxId = 0;
        foreach ($mm[1] as $v) $maxId = max($maxId, (int)$v);

        $themRels = '';
        $docPrId  = 1000;

        foreach ($anh as $key => $cfg) {
            $moc = '{{@' . $key . '}}';
            if (strpos($xml, $moc) === false) continue;

            $path = is_array($cfg) ? ($cfg['path'] ?? '') : (string)$cfg;
            if ($path === '' || !is_file($path)) {
                $xml = str_replace($moc, '', $xml);
                continue;
            }

            $maxId++;
            $rId    = 'rId' . $maxId;
            $tenAnh = 'anh' . $maxId . '.png';
            $zip->addFromString('word/media/' . $tenAnh, file_get_contents($path));

            $themRels .= '<Relationship Id="' . $rId . '"'
                       . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image"'
                       . ' Target="media/' . $tenAnh . '"/>';

            // mm -> EMU (1 mm = 36000 EMU)
            $wMm = (float)(is_array($cfg) ? ($cfg['w'] ?? 40) : 40);
            $hMm = (float)(is_array($cfg) ? ($cfg['h'] ?? $wMm) : $wMm);
            $cx  = (int)round($wMm * 36000);
            $cy  = (int)round($hMm * 36000);
            $docPrId++;

            $drawing =
                '<w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0"'
                . ' xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing">'
                . '<wp:extent cx="' . $cx . '" cy="' . $cy . '"/>'
                . '<wp:docPr id="' . $docPrId . '" name="Anh' . $docPrId . '"/>'
                . '<a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">'
                . '<a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
                . '<pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'
                . '<pic:nvPicPr><pic:cNvPr id="' . $docPrId . '" name="Anh' . $docPrId . '"/>'
                . '<pic:cNvPicPr/></pic:nvPicPr>'
                . '<pic:blipFill>'
                . '<a:blip xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"'
                . ' r:embed="' . $rId . '"/><a:stretch><a:fillRect/></a:stretch>'
                . '</pic:blipFill>'
                . '<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm>'
                . '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>'
                . '</pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing>';

            // Moc nam trong <w:t>...</w:t> -> phai dong <w:t> truoc khi chen drawing
            $xml = str_replace($moc, '</w:t></w:r><w:r>' . $drawing . '</w:r><w:r><w:t xml:space="preserve">', $xml);
        }

        if ($themRels !== '') {
            $rels = str_replace('</Relationships>', $themRels . '</Relationships>', $rels);
            $zip->addFromString('word/_rels/document.xml.rels', $rels);

            // Bao dam [Content_Types].xml biet duoi png
            $ct = $zip->getFromName('[Content_Types].xml');
            if ($ct !== false && strpos($ct, 'Extension="png"') === false) {
                $ct = str_replace('</Types>',
                    '<Default Extension="png" ContentType="image/png"/></Types>', $ct);
                $zip->addFromString('[Content_Types].xml', $ct);
            }
        }

        return $xml;
    }

}
