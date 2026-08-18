<?php
/**
 * kiem_tra_qr.php — Tự kiểm tra bộ sinh mã QR.
 *
 * Chạy:  php database/kiem_tra_qr.php
 *
 * LÝ DO CÓ FILE NÀY: QR sai vẫn "trông như QR thật" — vẫn có 3 ô định vị,
 * vẫn vuông vắn, bộ giải mã tự viết vẫn đọc lại được (vì sai giống nhau ở cả
 * 2 chiều). Chỉ máy quét thật mới phát hiện. Đã gặp 2 lỗi thuộc loại này:
 *   1. Format info ghi LSB-first thay vì MSB-first
 *   2. Copy 2 của format info lệch 1 ô (ranh giới i<8 thay vì i<7)
 * Cả 2 đều khiến máy quét đọc sai mask → không giải mã nổi, dù dữ liệu đúng.
 *
 * Script kiểm bằng cách GIẢI MÃ NGƯỢC hoàn toàn độc lập với lúc mã hóa:
 *   - Format info phải nằm trong bảng chuẩn ISO/IEC 18004 (mức M)
 *   - 2 bản copy format info phải giống nhau
 *   - Bỏ mask theo đúng mask khai báo phải ra lại đúng URL gốc
 *   - Syndrome Reed-Solomon của mọi block phải bằng 0
 *
 * Exit code 1 nếu có lỗi → dùng được trong CI.
 */
require_once __DIR__ . '/../PUBLIC/Common/QrHelper.php';

$isCli = PHP_SAPI === 'cli';
if (!$isCli) header('Content-Type: text/plain; charset=utf-8');
function say(string $m): void { echo $m . "\n"; }

/** Bảng format info chuẩn mức M (ISO/IEC 18004 Bảng C.1) */
function bangFormatM(): array
{
    $G = 0b10100110111;   // đa thức sinh BCH(15,5)
    $X = 0b101010000010010;
    $t = [];
    for ($mask = 0; $mask < 8; $mask++) {
        $d = (0b00 << 3) | $mask;     // 00 = mức M
        $v = $d << 10;
        for ($i = 4; $i >= 0; $i--) {
            if ($v & (1 << ($i + 10))) $v ^= $G << $i;
        }
        $t[((($d << 10) | $v) ^ $X)] = $mask;
    }
    return $t;
}

$refl   = new ReflectionClass('QrHelper');
$consts = $refl->getConstants();
$rmMap  = $refl->getMethod('reservedMap');  $rmMap->setAccessible(true);
$mBit   = $refl->getMethod('maskBit');      $mBit->setAccessible(true);
$gfMul  = $refl->getMethod('gfMul');        $gfMul->setAccessible(true);
$initGf = $refl->getMethod('initGf');       $initGf->setAccessible(true);
$initGf->invoke(null);
$expP = $refl->getProperty('expTable'); $expP->setAccessible(true);
$exp  = $expP->getValue();

$bangM = bangFormatM();
$soLoi = 0;

/** URL test: nhiều độ dài để ép nhiều version khác nhau */
$mauTest = [
    'http://thbg.bv/p?t=abc123',
    'http://thbg.bv/GUI/portal/index.php?t=' . str_repeat('a', 32),
    'http://thbg.bv/GUI/portal/index.php?t=' . str_repeat('b', 32) . '&x=1',
    'http://thbg.bv/GUI/portal/index.php?t=' . str_repeat('c', 64),
];

foreach ($mauTest as $url) {
    $loiUrl = [];
    $m = QrHelper::matrix($url);
    $n = count($m);
    $ver = (int)(($n - 17) / 4);

    // --- 1. Đọc format info từ CẢ 2 bản copy (MSB-first) ---
    $cells1 = [[8,0],[8,1],[8,2],[8,3],[8,4],[8,5],[8,7],[8,8],[7,8],[5,8],[4,8],[3,8],[2,8],[1,8],[0,8]];
    $cells2 = [];
    for ($i = 0; $i < 7; $i++)  $cells2[] = [$n - 1 - $i, 8];
    for ($i = 7; $i < 15; $i++) $cells2[] = [8, $n - 15 + $i];

    $s1 = ''; foreach ($cells1 as [$y, $x]) $s1 .= $m[$y][$x] ? '1' : '0';
    $s2 = ''; foreach ($cells2 as [$y, $x]) $s2 .= $m[$y][$x] ? '1' : '0';

    if ($s1 !== $s2) $loiUrl[] = '2 bản copy format info KHÁC NHAU';

    $val = bindec($s1);
    if (!isset($bangM[$val])) {
        $loiUrl[] = sprintf('format info 0x%04X không có trong bảng chuẩn mức M', $val);
        $mask = null;
    } else {
        $mask = $bangM[$val];
    }

    // --- 2. Bỏ mask + giải mã ngược ---
    if ($mask !== null) {
        $reserved = $rmMap->invoke(null, $n, $ver);
        $t = $m;
        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                if ($reserved[$y][$x]) continue;
                if ($mBit->invoke(null, $mask, $x, $y)) $t[$y][$x] = $t[$y][$x] ? 0 : 1;
            }
        }

        $bits = ''; $up = true;
        for ($right = $n - 1; $right > 0; $right -= 2) {
            if ($right === 6) $right--;
            for ($v = 0; $v < $n; $v++) {
                $y = $up ? ($n - 1 - $v) : $v;
                for ($c = 0; $c < 2; $c++) {
                    $x = $right - $c;
                    if ($reserved[$y][$x]) continue;
                    $bits .= $t[$y][$x] ? '1' : '0';
                }
            }
            $up = !$up;
        }

        [$ecLen, $b1, $b2] = $consts['EC_BLOCKS_M'][$ver];
        $cap = $consts['DATA_CODEWORDS_M'][$ver];
        $tb  = $b1 + $b2;

        $cw = [];
        for ($i = 0; $i + 8 <= strlen($bits); $i += 8) $cw[] = bindec(substr($bits, $i, 8));

        $sl = intdiv($cap, $tb);
        $nl = $cap - $sl * $tb;
        $lens = [];
        for ($i = 0; $i < $tb; $i++) $lens[] = $sl + ($i >= $tb - $nl ? 1 : 0);

        $blocks = array_fill(0, $tb, []);
        $idx = 0; $ml = max($lens);
        for ($i = 0; $i < $ml; $i++)
            for ($b = 0; $b < $tb; $b++)
                if ($i < $lens[$b]) $blocks[$b][] = $cw[$idx++];

        $ecBlocks = array_fill(0, $tb, []);
        for ($i = 0; $i < $ecLen; $i++)
            for ($b = 0; $b < $tb; $b++)
                $ecBlocks[$b][] = $cw[$idx++];

        // Syndrome RS phải = 0 với mọi block
        $rsLoi = 0;
        foreach ($blocks as $bi => $blk) {
            $full = array_merge($blk, $ecBlocks[$bi]);
            for ($j = 0; $j < $ecLen; $j++) {
                $syn = 0;
                foreach ($full as $p => $c) {
                    $pow = ($j * (count($full) - 1 - $p)) % 255;
                    $syn ^= $gfMul->invoke(null, $c, $exp[$pow]);
                }
                if ($syn !== 0) { $rsLoi++; break; }
            }
        }
        if ($rsLoi > 0) $loiUrl[] = "Reed-Solomon sai ở {$rsLoi}/{$tb} block";

        // Nội dung giải ra phải khớp URL gốc
        $data = [];
        foreach ($blocks as $b) foreach ($b as $x) $data[] = $x;
        $bs = '';
        foreach ($data as $b) $bs .= str_pad(decbin($b), 8, '0', STR_PAD_LEFT);
        $mode = bindec(substr($bs, 0, 4));
        $lenBits = $ver <= 9 ? 8 : 16;
        $len = bindec(substr($bs, 4, $lenBits));
        $out = '';
        for ($i = 0; $i < $len; $i++) $out .= chr(bindec(substr($bs, 4 + $lenBits + $i * 8, 8)));

        if ($mode !== 4)        $loiUrl[] = "mode = {$mode} (phải là 4 = byte mode)";
        if ($out !== $url)      $loiUrl[] = 'nội dung giải ra KHÔNG khớp URL gốc';
    }

    $ok = empty($loiUrl);
    if (!$ok) $soLoi++;
    printf("  len=%-4d version %-2d mask %-4s %s\n",
        strlen($url), $ver, $mask === null ? '?' : $mask,
        $ok ? 'ĐẠT' : 'LỖI: ' . implode('; ', $loiUrl));
}

say('');
if ($soLoi === 0) {
    say('KẾT QUẢ: ĐẠT — mã QR đúng chuẩn ISO/IEC 18004, máy quét đọc được.');
    exit(0);
}
say("KẾT QUẢ: {$soLoi} mẫu LỖI — QR sẽ không quét được.");
exit(1);
