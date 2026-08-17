<?php
/**
 * kiem_tra_icon.php — Kiểm tra tính đồng bộ của bộ icon.
 *
 * Chạy:  php database/kiem_tra_icon.php
 *
 * LÝ DO CÓ FILE NÀY: `APP.icon('ten-khong-ton-tai')` trả về chuỗi RỖNG,
 * không báo lỗi JS → nút hiện ra trống trơn mà không ai biết. Đã gặp thật với
 * icon 'shield-check' ở GUI/DM_NhomTaiKhoan (nút Phân quyền không có icon).
 *
 * Script kiểm 3 việc:
 *   1. Mọi tên gọi qua APP.icon() trong GUI đều tồn tại trong app.js
 *   2. Mọi icon module (IconHelper::moduleIconMap) đều có trong app.js
 *   3. Icon cùng tên ở app.js và IconHelper có path GIỐNG NHAU
 *
 * Exit code 1 nếu có lỗi → dùng được trong CI/pre-commit.
 */
require_once __DIR__ . '/../PUBLIC/Common/IconHelper.php';

$root = dirname(__DIR__);
$isCli = PHP_SAPI === 'cli';
if (!$isCli) header('Content-Type: text/plain; charset=utf-8');

function say(string $m): void { echo $m . "\n"; }

// ============ Nạp bộ icon của app.js ============
$jsFile = $root . '/assets/js/app.js';
if (!is_file($jsFile)) {
    say('LỖI: không tìm thấy assets/js/app.js');
    exit(1);
}
$js = file_get_contents($jsFile);
if (!preg_match('/var ICONS = \{(.*?)\n    \};/s', $js, $mm)) {
    say('LỖI: không tách được khối "var ICONS = {...}" trong app.js');
    exit(1);
}
$block = $mm[1];

// Cặp ten => path (path có thể chứa \' nên dùng mẫu cho phép escape)
preg_match_all("/'?([a-zA-Z0-9-]+)'?\s*:\s*'((?:[^'\\\\]|\\\\.)*)'/", $block, $mJs, PREG_SET_ORDER);
$iconJs = [];
foreach ($mJs as $x) {
    $iconJs[$x[1]] = $x[2];
}

$r = new ReflectionClass('IconHelper');
$iconPhp = $r->getConstant('PATHS');

/** Tên chỉ dùng nội bộ trong app.js (toast/pagination), không cần có ở IconHelper */
$aliasJs = ['alert', 'prev', 'next'];

$soLoi = 0;

// ============ 1. APP.icon() gọi tên không tồn tại ============
say('1) Tên gọi qua APP.icon() trong GUI:');
$goi = [];
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/GUI'));
foreach ($rii as $f) {
    if ($f->isDir() || strtolower($f->getExtension()) !== 'php') continue;
    $s = file_get_contents($f->getPathname());
    if (preg_match_all("/APP\.icon\(\s*'([a-zA-Z0-9-]+)'/", $s, $m)) {
        foreach ($m[1] as $ten) {
            $goi[$ten][] = basename(dirname($f->getPathname())) . '/' . $f->getBasename();
        }
    }
}
$thieu = [];
foreach ($goi as $ten => $files) {
    if (!isset($iconJs[$ten])) {
        $thieu[$ten] = array_unique($files);
    }
}
if (empty($thieu)) {
    say('   OK — ' . count($goi) . ' tên icon, tất cả đều có trong app.js');
} else {
    $soLoi += count($thieu);
    foreach ($thieu as $ten => $files) {
        say("   THIẾU '{$ten}' (dùng ở: " . implode(', ', $files) . ')');
    }
    say('   → Nút gọi icon này sẽ hiện RỖNG. Thêm vào mảng ICONS trong app.js.');
}

// ============ 2. Icon module phải có ở app.js ============
say('');
say('2) Icon theo module (IconHelper::moduleIconMap):');
$thieuMod = [];
foreach (IconHelper::moduleIconMap() as $mod => $ic) {
    if (!isset($iconJs[$ic])) $thieuMod[] = "{$mod} => '{$ic}'";
    if (!isset($iconPhp[$ic])) $thieuMod[] = "{$mod} => '{$ic}' (thiếu cả ở IconHelper)";
}
if (empty($thieuMod)) {
    say('   OK — ' . count(IconHelper::moduleIconMap()) . ' module đều có icon ở cả 2 nơi');
} else {
    $soLoi += count($thieuMod);
    foreach ($thieuMod as $t) say("   THIẾU: {$t}");
}

// ============ 3. Cùng tên nhưng path khác nhau ============
say('');
say('3) So khớp path giữa app.js và IconHelper:');
$lech = [];
$khop = 0;
foreach ($iconJs as $ten => $path) {
    if (in_array($ten, $aliasJs, true)) continue;
    if (!isset($iconPhp[$ten])) {
        $lech[] = "'{$ten}' có ở app.js nhưng KHÔNG có ở IconHelper";
        continue;
    }
    if ($iconPhp[$ten] !== $path) {
        $lech[] = "'{$ten}' path KHÁC NHAU giữa 2 file (icon sẽ hiển thị lệch)";
    } else {
        $khop++;
    }
}
if (empty($lech)) {
    say("   OK — {$khop} icon trùng tên đều có path giống nhau");
} else {
    $soLoi += count($lech);
    foreach ($lech as $l) say("   LỆCH: {$l}");
}

// ============ Kết luận ============
say('');
if ($soLoi === 0) {
    say('KẾT QUẢ: ĐẠT — bộ icon đồng bộ.');
    exit(0);
}
say("KẾT QUẢ: CÓ {$soLoi} VẤN ĐỀ — xem chi tiết ở trên.");
exit(1);
