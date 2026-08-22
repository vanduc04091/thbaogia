<?php
/**
 * QrHelper — Sinh mã QR thuần PHP, xuất SVG.
 *
 * Không phụ thuộc CDN / thư viện ngoài (theo §3.8: tránh rủi ro supply chain
 * và phụ thuộc mạng). Cài đặt QR Model 2, mã hóa Byte mode, mức sửa lỗi M.
 *
 * Dùng: echo QrHelper::svg('http://thbg.bv/portal/?t=abc', 220);
 *
 * Phạm vi hỗ trợ: version 1-10 (tối đa ~213 byte ở mức M) — đủ cho URL.
 */
class QrHelper
{
    /** Số codeword dữ liệu theo version, mức sửa lỗi M */
    private const DATA_CODEWORDS_M = [
        1 => 16, 2 => 28, 3 => 44, 4 => 64, 5 => 86,
        6 => 108, 7 => 124, 8 => 154, 9 => 182, 10 => 216,
    ];

    /** [số EC codeword mỗi block, số block nhóm 1, số block nhóm 2] — mức M */
    private const EC_BLOCKS_M = [
        1  => [10, 1, 0],
        2  => [16, 1, 0],
        3  => [26, 1, 0],
        4  => [18, 2, 0],
        5  => [24, 2, 0],
        6  => [16, 4, 0],
        7  => [18, 4, 0],
        8  => [22, 2, 2],
        9  => [22, 3, 2],
        10 => [26, 4, 1],
    ];

    /** Vị trí tâm alignment pattern theo version */
    private const ALIGN_POS = [
        1 => [], 2 => [6, 18], 3 => [6, 22], 4 => [6, 26], 5 => [6, 30],
        6 => [6, 34], 7 => [6, 22, 38], 8 => [6, 24, 42], 9 => [6, 26, 46], 10 => [6, 28, 50],
    ];

    /** Chuỗi bit thông tin version (version >= 7) */
    private const VERSION_INFO = [
        7 => '000111110010010100', 8 => '001000010110111100', 9 => '001001101010011001',
        10 => '001010010011010011',
    ];

    private static ?array $expTable = null;
    private static ?array $logTable = null;

    /**
     * Trả về mã QR dạng SVG.
     *
     * @param string $text    Nội dung (URL)
     * @param int    $size    Kích thước ảnh (px)
     * @param int    $margin  Lề trắng tính theo số module (chuẩn: 4)
     */
    public static function svg(string $text, int $size = 220, int $margin = 4): string
    {
        $matrix = self::matrix($text);
        $n = count($matrix);
        $total = $n + $margin * 2;

        // Gộp các module đen cùng dòng thành 1 <rect> để SVG gọn
        $rects = '';
        for ($y = 0; $y < $n; $y++) {
            $x = 0;
            while ($x < $n) {
                if (!$matrix[$y][$x]) { $x++; continue; }
                $run = 1;
                while ($x + $run < $n && $matrix[$y][$x + $run]) $run++;
                $rects .= '<rect x="' . ($x + $margin) . '" y="' . ($y + $margin)
                        . '" width="' . $run . '" height="1"/>';
                $x += $run;
            }
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . (int)$size . '" height="' . (int)$size
             . '" viewBox="0 0 ' . $total . ' ' . $total . '" shape-rendering="crispEdges" role="img"'
             . ' aria-label="Mã QR">'
             . '<rect width="' . $total . '" height="' . $total . '" fill="#ffffff"/>'
             . '<g fill="#0f172a">' . $rects . '</g></svg>';
    }

    /** Trả SVG dạng data URI — dùng cho <img src> hoặc nhúng vào file khác */
    public static function dataUri(string $text, int $size = 220): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode(self::svg($text, $size));
    }

    /**
     * Sinh ma trận module (true = đen).
     * @return array<int, array<int, bool>>
     */
    public static function matrix(string $text): array
    {
        $bytes = array_values(unpack('C*', $text) ?: []);
        $len = count($bytes);

        $version = self::pickVersion($len);
        $capacity = self::DATA_CODEWORDS_M[$version];

        // === 1. Chuỗi bit dữ liệu: mode (0100) + độ dài + dữ liệu ===
        $bits = '0100';
        $lenBits = $version <= 9 ? 8 : 16;
        $bits .= str_pad(decbin($len), $lenBits, '0', STR_PAD_LEFT);
        foreach ($bytes as $b) {
            $bits .= str_pad(decbin($b), 8, '0', STR_PAD_LEFT);
        }

        // Terminator + padding tới biên byte
        $maxBits = $capacity * 8;
        $bits .= str_repeat('0', min(4, $maxBits - strlen($bits)));
        if (strlen($bits) % 8 !== 0) {
            $bits .= str_repeat('0', 8 - (strlen($bits) % 8));
        }

        // Pad bytes 0xEC, 0x11 luân phiên
        $data = [];
        for ($i = 0; $i < strlen($bits); $i += 8) {
            $data[] = bindec(substr($bits, $i, 8));
        }
        $padToggle = true;
        while (count($data) < $capacity) {
            $data[] = $padToggle ? 0xEC : 0x11;
            $padToggle = !$padToggle;
        }

        // === 2. Chia block + tính Reed-Solomon ===
        $final = self::interleave($data, $version);

        // === 3. Dựng ma trận ===
        $n = 17 + $version * 4;
        $mod = array_fill(0, $n, array_fill(0, $n, null));   // null = chưa gán
        self::placeFunctionPatterns($mod, $n, $version);

        // Bit stream cuối (kèm remainder bits)
        $stream = '';
        foreach ($final as $b) $stream .= str_pad(decbin($b), 8, '0', STR_PAD_LEFT);
        $stream .= str_repeat('0', self::remainderBits($version));

        self::placeData($mod, $n, $stream);

        // === 4. Chọn mask tốt nhất ===
        $best = null; $bestPenalty = PHP_INT_MAX; $bestMask = 0;
        for ($mask = 0; $mask < 8; $mask++) {
            $cand = self::applyMask($mod, $n, $mask);
            self::placeFormatInfo($cand, $n, $mask);
            $p = self::penalty($cand, $n);
            if ($p < $bestPenalty) { $bestPenalty = $p; $best = $cand; $bestMask = $mask; }
        }

        // Chuẩn hóa null → false
        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                $best[$y][$x] = (bool)$best[$y][$x];
            }
        }
        return $best;
    }

    private static function pickVersion(int $byteLen): int
    {
        foreach (self::DATA_CODEWORDS_M as $v => $cap) {
            // trừ overhead: 4 bit mode + 8/16 bit length + 4 bit terminator
            $overheadBits = 4 + ($v <= 9 ? 8 : 16);
            $usable = (int)floor(($cap * 8 - $overheadBits) / 8);
            if ($byteLen <= $usable) return $v;
        }
        throw new RuntimeException('Nội dung QR quá dài (tối đa ~200 ký tự).');
    }

    private static function remainderBits(int $version): int
    {
        if ($version === 1) return 0;
        if ($version >= 2 && $version <= 6) return 7;
        return 0; // version 7-13 → 0
    }

    // ---------------- Reed-Solomon (GF(256)) ----------------

    private static function initGf(): void
    {
        if (self::$expTable !== null) return;
        $exp = array_fill(0, 512, 0);
        $log = array_fill(0, 256, 0);
        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            $exp[$i] = $x;
            $log[$x] = $i;
            $x <<= 1;
            if ($x & 0x100) $x ^= 0x11D;   // đa thức nguyên thủy QR
        }
        for ($i = 255; $i < 512; $i++) $exp[$i] = $exp[$i - 255];
        self::$expTable = $exp;
        self::$logTable = $log;
    }

    private static function gfMul(int $a, int $b): int
    {
        if ($a === 0 || $b === 0) return 0;
        self::initGf();
        return self::$expTable[self::$logTable[$a] + self::$logTable[$b]];
    }

    /** Đa thức sinh bậc $degree */
    private static function generatorPoly(int $degree): array
    {
        self::initGf();
        $poly = [1];
        for ($i = 0; $i < $degree; $i++) {
            $next = array_fill(0, count($poly) + 1, 0);
            foreach ($poly as $j => $c) {
                $next[$j]     ^= $c;
                $next[$j + 1] ^= self::gfMul($c, self::$expTable[$i]);
            }
            $poly = $next;
        }
        return $poly;
    }

    /** Tính EC codewords cho 1 block */
    private static function ecCodewords(array $block, int $ecLen): array
    {
        $gen = self::generatorPoly($ecLen);
        $rem = array_fill(0, $ecLen, 0);

        foreach ($block as $byte) {
            $factor = $byte ^ $rem[0];
            array_shift($rem);
            $rem[] = 0;
            for ($i = 0; $i < $ecLen; $i++) {
                $rem[$i] ^= self::gfMul($gen[$i + 1], $factor);
            }
        }
        return $rem;
    }

    /** Chia data thành block, tính EC, rồi trộn (interleave) theo chuẩn */
    private static function interleave(array $data, int $version): array
    {
        [$ecLen, $b1, $b2] = self::EC_BLOCKS_M[$version];
        $totalBlocks = $b1 + $b2;
        $totalData = count($data);

        $shortLen = (int)floor($totalData / $totalBlocks);
        // b2 = số block DÀI hơn 1 byte
        $numLong = $totalData - $shortLen * $totalBlocks;

        $blocks = [];
        $ecBlocks = [];
        $pos = 0;
        for ($i = 0; $i < $totalBlocks; $i++) {
            $len = $shortLen + ($i >= $totalBlocks - $numLong ? 1 : 0);
            $block = array_slice($data, $pos, $len);
            $pos += $len;
            $blocks[] = $block;
            $ecBlocks[] = self::ecCodewords($block, $ecLen);
        }

        // Trộn data codewords
        $out = [];
        $maxLen = max(array_map('count', $blocks));
        for ($i = 0; $i < $maxLen; $i++) {
            foreach ($blocks as $b) {
                if (isset($b[$i])) $out[] = $b[$i];
            }
        }
        // Trộn EC codewords
        for ($i = 0; $i < $ecLen; $i++) {
            foreach ($ecBlocks as $b) {
                if (isset($b[$i])) $out[] = $b[$i];
            }
        }
        return $out;
    }

    // ---------------- Dựng ma trận ----------------

    private static function placeFunctionPatterns(array &$mod, int $n, int $version): void
    {
        // Finder pattern 7x7 tại 3 góc + separator
        foreach ([[0, 0], [$n - 7, 0], [0, $n - 7]] as [$ox, $oy]) {
            for ($y = -1; $y <= 7; $y++) {
                for ($x = -1; $x <= 7; $x++) {
                    $px = $ox + $x; $py = $oy + $y;
                    if ($px < 0 || $py < 0 || $px >= $n || $py >= $n) continue;
                    $inRing = ($x >= 0 && $x <= 6 && ($y === 0 || $y === 6))
                           || ($y >= 0 && $y <= 6 && ($x === 0 || $x === 6));
                    $inCore = $x >= 2 && $x <= 4 && $y >= 2 && $y <= 4;
                    $mod[$py][$px] = ($inRing || $inCore) ? 1 : 0;
                }
            }
        }

        // Timing pattern
        for ($i = 8; $i < $n - 8; $i++) {
            $v = ($i % 2 === 0) ? 1 : 0;
            if ($mod[6][$i] === null) $mod[6][$i] = $v;
            if ($mod[$i][6] === null) $mod[$i][6] = $v;
        }

        // Alignment pattern 5x5
        $pos = self::ALIGN_POS[$version] ?? [];
        foreach ($pos as $cy) {
            foreach ($pos as $cx) {
                // Bỏ 3 vị trí trùng finder
                if (($cx === 6 && $cy === 6)
                 || ($cx === 6 && $cy === $n - 7)
                 || ($cx === $n - 7 && $cy === 6)) continue;
                for ($y = -2; $y <= 2; $y++) {
                    for ($x = -2; $x <= 2; $x++) {
                        $ring = max(abs($x), abs($y));
                        $mod[$cy + $y][$cx + $x] = ($ring === 1) ? 0 : 1;
                    }
                }
            }
        }

        // Dark module
        $mod[$n - 8][8] = 1;

        // Chỗ dành cho format info → đặt tạm 0 để không bị ghi dữ liệu lên
        for ($i = 0; $i <= 8; $i++) {
            if ($mod[8][$i] === null)      $mod[8][$i] = 0;
            if ($mod[$i][8] === null)      $mod[$i][8] = 0;
        }
        for ($i = 0; $i < 8; $i++) {
            if ($mod[8][$n - 1 - $i] === null) $mod[8][$n - 1 - $i] = 0;
            if ($mod[$n - 1 - $i][8] === null) $mod[$n - 1 - $i][8] = 0;
        }

        // Version info (version >= 7): 2 khối 3x6
        if ($version >= 7 && isset(self::VERSION_INFO[$version])) {
            $vi = self::VERSION_INFO[$version];
            $bits = array_reverse(str_split($vi));   // LSB first
            for ($i = 0; $i < 18; $i++) {
                $b = (int)$bits[$i];
                $r = (int)floor($i / 3);
                $c = $i % 3;
                $mod[$n - 11 + $c][$r] = $b;
                $mod[$r][$n - 11 + $c] = $b;
            }
        }
    }

    /** Rải bit dữ liệu theo đường zigzag 2 cột từ phải sang trái */
    private static function placeData(array &$mod, int $n, string $stream): void
    {
        $len = strlen($stream);
        $idx = 0;
        $upward = true;

        for ($right = $n - 1; $right > 0; $right -= 2) {
            if ($right === 6) $right--;   // bỏ cột timing
            for ($v = 0; $v < $n; $v++) {
                $y = $upward ? ($n - 1 - $v) : $v;
                for ($c = 0; $c < 2; $c++) {
                    $x = $right - $c;
                    if ($mod[$y][$x] !== null) continue;
                    $mod[$y][$x] = $idx < $len ? (int)$stream[$idx] : 0;
                    $idx++;
                }
            }
            $upward = !$upward;
        }
    }

    /** Áp mask lên vùng dữ liệu (chỉ ô đã được placeData ghi) */
    private static function applyMask(array $mod, int $n, int $mask): array
    {
        // Xác định ô là function pattern hay không → dựng lại bản đồ reserved
        $reserved = self::reservedMap($n, self::versionFromSize($n));

        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                if ($reserved[$y][$x]) continue;
                if (self::maskBit($mask, $x, $y)) {
                    $mod[$y][$x] = $mod[$y][$x] ? 0 : 1;
                }
            }
        }
        return $mod;
    }

    private static function versionFromSize(int $n): int
    {
        return (int)(($n - 17) / 4);
    }

    /** Bản đồ ô KHÔNG được mask (function pattern + format/version info) */
    private static function reservedMap(int $n, int $version): array
    {
        $r = array_fill(0, $n, array_fill(0, $n, false));

        foreach ([[0, 0], [$n - 7, 0], [0, $n - 7]] as [$ox, $oy]) {
            for ($y = -1; $y <= 7; $y++) {
                for ($x = -1; $x <= 7; $x++) {
                    $px = $ox + $x; $py = $oy + $y;
                    if ($px < 0 || $py < 0 || $px >= $n || $py >= $n) continue;
                    $r[$py][$px] = true;
                }
            }
        }
        for ($i = 0; $i < $n; $i++) { $r[6][$i] = true; $r[$i][6] = true; }

        foreach ((self::ALIGN_POS[$version] ?? []) as $cy) {
            foreach ((self::ALIGN_POS[$version] ?? []) as $cx) {
                if (($cx === 6 && $cy === 6)
                 || ($cx === 6 && $cy === $n - 7)
                 || ($cx === $n - 7 && $cy === 6)) continue;
                for ($y = -2; $y <= 2; $y++) {
                    for ($x = -2; $x <= 2; $x++) $r[$cy + $y][$cx + $x] = true;
                }
            }
        }

        for ($i = 0; $i <= 8; $i++) { $r[8][$i] = true; $r[$i][8] = true; }
        for ($i = 0; $i < 8; $i++) { $r[8][$n - 1 - $i] = true; $r[$n - 1 - $i][8] = true; }
        $r[$n - 8][8] = true;

        if ($version >= 7) {
            for ($i = 0; $i < 18; $i++) {
                $rr = (int)floor($i / 3); $c = $i % 3;
                $r[$n - 11 + $c][$rr] = true;
                $r[$rr][$n - 11 + $c] = true;
            }
        }
        return $r;
    }

    private static function maskBit(int $mask, int $x, int $y): bool
    {
        switch ($mask) {
            case 0: return ($x + $y) % 2 === 0;
            case 1: return $y % 2 === 0;
            case 2: return $x % 3 === 0;
            case 3: return ($x + $y) % 3 === 0;
            case 4: return ((int)floor($y / 2) + (int)floor($x / 3)) % 2 === 0;
            case 5: return (($x * $y) % 2) + (($x * $y) % 3) === 0;
            case 6: return ((($x * $y) % 2) + (($x * $y) % 3)) % 2 === 0;
            default: return ((($x + $y) % 2) + (($x * $y) % 3)) % 2 === 0;
        }
    }

    /** Ghi 15 bit format info (mức M + mask) */
    private static function placeFormatInfo(array &$mod, int $n, int $mask): void
    {
        // Mức M = 00; data = 5 bit (2 bit EC + 3 bit mask)
        $data = (0b00 << 3) | $mask;

        // BCH(15,5)
        $rem = $data;
        for ($i = 0; $i < 10; $i++) {
            $rem <<= 1;
            if ($rem & 0x400) $rem ^= 0x537;
        }
        $bits = (($data << 10) | ($rem & 0x3FF)) ^ 0x5412;

        // Vị trí chuẩn của 15 bit format.
        //
        // THỨ TỰ BIT: spec ghi bit CAO trước (MSB-first) — ô đầu tiên nhận bit 14,
        // không phải bit 0. Ghi ngược (LSB-first) vẫn tạo ra hình QR "trông đúng",
        // 2 bản copy vẫn khớp nhau, và bộ giải mã tự viết cũng đọc lại được
        // (vì sai giống nhau ở cả 2 chiều) — NHƯNG máy quét thật đọc sai mức sửa
        // lỗi + mask nên không giải mã nổi. Đây là lỗi từng gặp: QR không quét được
        // dù dữ liệu và mask hoàn toàn chính xác.
        for ($i = 0; $i < 15; $i++) {
            $b = ($bits >> (14 - $i)) & 1;

            // Bản copy 1: quanh finder trên-trái
            if ($i < 6)        { $mod[8][$i] = $b; }
            elseif ($i === 6)  { $mod[8][7] = $b; }
            elseif ($i === 7)  { $mod[8][8] = $b; }
            elseif ($i === 8)  { $mod[7][8] = $b; }
            else               { $mod[14 - $i][8] = $b; }

            // Bản copy 2 — ranh giới là i < 7, KHÔNG phải i < 8.
            // Bit 0..6 nằm ở dải DỌC dưới finder trên-trái; bit 7..14 nằm ở dải
            // NGANG bên phải. Đặt nhầm i < 8 làm bit 7 rơi vào (n-8,8) — vốn là
            // ô dark module — và đẩy lệch toàn bộ bit 7..14 đi 1 vị trí, khiến
            // máy quét đọc sai mask rồi giải mã hỏng (dữ liệu vẫn đúng nhưng vô dụng).
            if ($i < 7)        { $mod[$n - 1 - $i][8] = $b; }
            else               { $mod[8][$n - 15 + $i] = $b; }
        }
        $mod[$n - 8][8] = 1;   // dark module
    }

    /** Điểm phạt để chọn mask (chuẩn ISO 18004) */
    private static function penalty(array $m, int $n): int
    {
        $p = 0;

        // N1: dãy >= 5 module cùng màu
        for ($y = 0; $y < $n; $y++) {
            $runC = -1; $runLen = 0;
            for ($x = 0; $x < $n; $x++) {
                $c = (int)$m[$y][$x];
                if ($c === $runC) { $runLen++; }
                else { if ($runLen >= 5) $p += 3 + ($runLen - 5); $runC = $c; $runLen = 1; }
            }
            if ($runLen >= 5) $p += 3 + ($runLen - 5);
        }
        for ($x = 0; $x < $n; $x++) {
            $runC = -1; $runLen = 0;
            for ($y = 0; $y < $n; $y++) {
                $c = (int)$m[$y][$x];
                if ($c === $runC) { $runLen++; }
                else { if ($runLen >= 5) $p += 3 + ($runLen - 5); $runC = $c; $runLen = 1; }
            }
            if ($runLen >= 5) $p += 3 + ($runLen - 5);
        }

        // N2: khối 2x2 cùng màu
        for ($y = 0; $y < $n - 1; $y++) {
            for ($x = 0; $x < $n - 1; $x++) {
                $c = (int)$m[$y][$x];
                if ($c === (int)$m[$y][$x + 1] && $c === (int)$m[$y + 1][$x] && $c === (int)$m[$y + 1][$x + 1]) {
                    $p += 3;
                }
            }
        }

        // N3: mẫu 1:1:3:1:1 (giống finder)
        $pat1 = [1,0,1,1,1,0,1,0,0,0,0];
        $pat2 = [0,0,0,0,1,0,1,1,1,0,1];
        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x <= $n - 11; $x++) {
                $ok1 = true; $ok2 = true;
                for ($k = 0; $k < 11; $k++) {
                    $c = (int)$m[$y][$x + $k];
                    if ($c !== $pat1[$k]) $ok1 = false;
                    if ($c !== $pat2[$k]) $ok2 = false;
                }
                if ($ok1) $p += 40;
                if ($ok2) $p += 40;
            }
        }
        for ($x = 0; $x < $n; $x++) {
            for ($y = 0; $y <= $n - 11; $y++) {
                $ok1 = true; $ok2 = true;
                for ($k = 0; $k < 11; $k++) {
                    $c = (int)$m[$y + $k][$x];
                    if ($c !== $pat1[$k]) $ok1 = false;
                    if ($c !== $pat2[$k]) $ok2 = false;
                }
                if ($ok1) $p += 40;
                if ($ok2) $p += 40;
            }
        }

        // N4: tỷ lệ đen/trắng lệch 50%
        $dark = 0;
        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) if ($m[$y][$x]) $dark++;
        }
        $ratio = (int)floor(abs($dark * 100 / ($n * $n) - 50) / 5);
        $p += $ratio * 10;

        return $p;
    }

    /**
     * Xuat QR ra anh PNG (chuoi nhi phan) — dung de nhung vao file Word.
     *
     * Tu viet encoder PNG vi may chu khong bat extension GD. QR chi co 2 mau
     * nen dung anh xam 8-bit, nen bang gzcompress (zlib co san trong PHP).
     *
     * @param string $text   Noi dung QR
     * @param int    $scale  So diem anh cho moi o QR
     * @param int    $margin So o trong vien (chuan ISO khuyen nghi 4)
     */
    public static function png(string $text, int $scale = 8, int $margin = 4): string
    {
        $m = self::matrix($text);
        $n = count($m);
        if ($n === 0) throw new RuntimeException('Khong sinh duoc ma QR');

        $scale  = max(1, min(40, $scale));
        $margin = max(0, min(16, $margin));

        $o = $n + $margin * 2;           // so o ke ca vien
        $px = $o * $scale;               // kich thuoc anh (diem anh)

        // --- Du lieu tho: moi dong bat dau bang 1 byte filter (0 = None) ---
        $raw = '';
        for ($y = 0; $y < $o; $y++) {
            $oy = $y - $margin;                       // toa do o theo truc doc
            $dong = '';
            for ($x = 0; $x < $o; $x++) {
                $ox = $x - $margin;
                $den = ($oy >= 0 && $oy < $n && $ox >= 0 && $ox < $n && $m[$oy][$ox]);
                $dong .= str_repeat($den ? "\x00" : "\xFF", $scale);
            }
            // Lap lai dong do $scale lan (phong to theo chieu doc)
            for ($k = 0; $k < $scale; $k++) {
                $raw .= "\x00" . $dong;
            }
        }

        // --- Ghep cac chunk PNG ---
        $png = "\x89PNG\r\n\x1a\n";

        // IHDR: rong, cao, 8 bit, mau xam (type 0)
        $ihdr = pack('NN', $px, $px) . "\x08\x00\x00\x00\x00";
        $png .= self::chunkPng('IHDR', $ihdr);
        $png .= self::chunkPng('IDAT', gzcompress($raw, 9));
        $png .= self::chunkPng('IEND', '');

        return $png;
    }

    /** Dong goi 1 chunk PNG: [do dai][ten][du lieu][CRC] */
    private static function chunkPng(string $ten, string $data): string
    {
        return pack('N', strlen($data)) . $ten . $data
             . pack('N', crc32($ten . $data));
    }

    /** Ghi thang QR ra file PNG, tra ve duong dan */
    public static function pngFile(string $text, string $path, int $scale = 8, int $margin = 4): string
    {
        if (file_put_contents($path, self::png($text, $scale, $margin)) === false) {
            throw new RuntimeException('Khong ghi duoc file QR: ' . $path);
        }
        return $path;
    }

}
