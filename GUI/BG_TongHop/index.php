<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../BUS/BG_TongHop_BUS.php';
require_once __DIR__ . '/../../BUS/BG_GoiThau_BUS.php';

Helper::requireLogin();
PhanQuyenHelper::requireQuyenView('BG_TongHop');

$goiThauCombo = BG_GoiThau_BUS::getCombo();
$goiThauId = (int)Helper::get('goi_thau_id', 0);
if ($goiThauId === 0 && !empty($goiThauCombo)) {
    $goiThauId = (int)$goiThauCombo[0]['id'];
}

$pageTitle  = 'Tổng hợp báo giá';
$activeMenu = 'BG_TongHop';
$AJAX = AppConfig::baseUrl('GUI/BG_TongHop/ajax_handler.php');
require __DIR__ . '/../layouts/header.php';
?>

<nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="<?= AppConfig::baseUrl('GUI/dashboard/index.php') ?>">Trang chủ</a>
    <span class="sep">›</span> <a href="<?= AppConfig::baseUrl('GUI/BG_GoiThau/index.php') ?>">Gói thầu</a>
    <span class="sep">›</span> <span>Tổng hợp báo giá</span>
</nav>

<?php if (empty($goiThauCombo)): ?>
    <div class="card">
        <div class="empty-state">
            <?= IconHelper::svg('clipboard-list', 40) ?>
            <h3>Chưa có gói thầu nào</h3>
            <p>Tạo gói thầu, nhập hàng hóa và nhận báo giá trước khi tổng hợp.</p>
            <a class="btn btn-primary" href="<?= AppConfig::baseUrl('GUI/BG_GoiThau/index.php') ?>">
                <?= IconHelper::svg('plus', 16) ?>Tạo gói thầu
            </a>
        </div>
    </div>
<?php else: ?>

    <div class="card" style="margin-bottom:16px">
        <div class="toolbar">
            <div class="left">
                <select id="filterGoiThau" class="form-select" style="max-width:400px" aria-label="Chọn gói thầu">
                    <?php foreach ($goiThauCombo as $g): ?>
                        <option value="<?= (int)$g['id'] ?>" <?= (int)$g['id'] === $goiThauId ? 'selected' : '' ?>>
                            <?= Helper::h($g['so_thong_bao'] . ' — ' . mb_substr($g['ten_goi_thau'], 0, 60)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label class="check-cell" style="display:inline-flex;align-items:center;gap:7px;cursor:pointer;font-size:13px">
                    <input type="checkbox" id="chkChiGia" checked>
                    <span>Chỉ hiện cột giá</span>
                </label>
            </div>
            <div class="right">
                <a class="btn btn-primary" id="btnXuatExcel" href="#">
                    <?= IconHelper::svg('download', 16) ?><span class="btn-label">Xuất Excel tổng hợp</span>
                </a>
            </div>
        </div>
    </div>

    <div id="ctxWrap"></div>
    <div id="statWrap"></div>

    <div class="card">
        <div class="table-wrap has-sticky" id="tableWrap">
            <table class="table table-compare" id="table">
                <thead id="thead"></thead>
                <tbody id="tbody"></tbody>
            </table>
        </div>
    </div>

<?php endif; ?>

<script>
var AJAX_URL = <?= json_encode($AJAX) ?>;
var URL_XUAT = <?= json_encode(AppConfig::baseUrl('GUI/BG_TongHop/download.php')) ?>;
var URL_BAO_GIA = <?= json_encode(AppConfig::baseUrl('GUI/BG_BaoGia/index.php')) ?>;
var GOI_THAU_ID = <?= (int)$goiThauId ?>;
var DATA = null;

function money(v) { return Number(v || 0).toLocaleString('vi-VN'); }

function loadData() {
    if (!GOI_THAU_ID) return;
    APP.showLoading('#tableWrap');
    $('#thead').empty();
    $('#tbody').html(APP.skeletonRows(6, 6));

    APP.ajax(AJAX_URL, { action: 'getTongHop', goi_thau_id: GOI_THAU_ID }, {
        success: function (res) {
            DATA = res.data;
            render();
        },
        error: function () { $('#tbody').html(APP.emptyRow(6, 'Không tải được dữ liệu tổng hợp')); },
        complete: function () { APP.hideLoading('#tableWrap'); }
    });
}

function render() {
    if (!DATA) return;
    var gt = DATA.goi_thau, nt = DATA.nha_thau || [], hh = DATA.hang_hoa || [], tk = DATA.tong_ket || {};

    // ---- Thanh ngữ cảnh ----
    var quaHan = gt.han_cuoi && gt.han_cuoi < new Date().toISOString().slice(0, 10);
    $('#ctxWrap').html(
        '<div class="context-bar' + (quaHan ? ' is-warning' : '') + '">' +
        ctxItem('clipboard-list', 'Gói thầu', gt.so_thong_bao) +
        ctxItem('building', 'Nhà thầu đã xác nhận', nt.length + ' / ' + gt.so_bao_gia + ' bản nộp') +
        ctxItem('package', 'Hàng hóa', tk.so_hang_hoa + ' mục (' + tk.so_hang_hoa_co_gia + ' mục có giá)') +
        ctxItem('calendar', 'Hạn cuối', gt.han_cuoi ? APP.formatDate(gt.han_cuoi) : 'Không đặt') +
        '</div>'
    );

    // ---- Thẻ số liệu ----
    if (nt.length) {
        var reNhat = null;
        for (var i = 0; i < nt.length; i++) {
            if (Number(nt[i].tong_tien) > 0 && (!reNhat || Number(nt[i].tong_tien) < Number(reNhat.tong_tien))) {
                reNhat = nt[i];
            }
        }
        $('#statWrap').html(
            '<div class="stat-grid" style="margin-bottom:16px">' +
            statCard('building', '', nt.length, 'Nhà thầu đưa vào tổng hợp') +
            statCard('bar-chart', 'blue', money(tk.tong_gia_min), 'Tổng giá trị theo giá thấp nhất (VND)') +
            statCard('file-spreadsheet', 'amber',
                reNhat ? money(reNhat.tong_tien) : '—',
                reNhat ? 'Tổng thấp nhất: ' + reNhat.ten_cong_ty : 'Chưa có báo giá') +
            '</div>'
        );
    } else {
        $('#statWrap').empty();
    }

    // ---- Chưa có nhà thầu xác nhận ----
    if (!nt.length) {
        $('#thead').empty();
        $('#tbody').html('<tr><td colspan="6"><div class="empty-state">' +
            APP.icon('check-circle', 40) +
            '<h3>Chưa có báo giá nào được xác nhận</h3>' +
            '<p>Bảng tổng hợp chỉ gộp các nhà thầu đã được tích <strong>xác nhận nhận bản giấy</strong>. ' +
            'Hãy vào mục Báo giá nhà thầu để xác nhận.</p>' +
            '<a class="btn btn-primary" href="' + URL_BAO_GIA + '?goi_thau_id=' + GOI_THAU_ID + '">' +
            'Tới danh sách báo giá</a>' +
            '</div></td></tr>');
        return;
    }

    var chiGia = $('#chkChiGia').is(':checked');

    // ---- Header 1 tầng: mỗi nhà thầu là 1 DÒNG, không phải nhóm cột ----
    // Bố cục giống hệt file Excel xuất ra: 1 hàng hóa có N nhà thầu chào
    // -> N dòng liên tiếp, tên nhà thầu + MST nằm thành CỘT trên chính dòng đó.
    var h = '<tr>' +
        '<th class="col-id">STT</th>' +
        '<th>Phần</th>' +
        '<th class="sticky-col">Tên hàng hóa</th>' +
        '<th>ĐVT</th>' +
        '<th class="col-qty">SL</th>' +
        '<th class="th-vendor">Nhà thầu</th>' +
        '<th class="th-vendor">Mã số thuế</th>';
    if (!chiGia) {
        h += '<th class="th-vendor">Tên TM / Model</th>' +
             '<th class="th-vendor">Hãng SX / Xuất xứ</th>';
    }
    h += '<th class="th-vendor col-price">Đơn giá</th>' +
         '<th class="th-vendor col-price">Thành tiền</th>' +
         '</tr>';
    $('#thead').html(h);

    // Số cột cố định (phần hàng hóa) — dùng cho rowspan & dòng tổng cộng
    var colsFix = 5;
    var soCotNt = chiGia ? 4 : 6;

    // ---- Dữ liệu ----
    var html = '';
    var stt = 0;

    for (var k = 0; k < hh.length; k++) {
        var r = hh[k];

        // CHỈ lấy nhà thầu THỰC SỰ có chào giá cho hàng hóa này
        var dsChao = [];
        for (var m = 0; m < nt.length; m++) {
            var c = r.chao[nt[m].id];
            if (c && c.co_chao) dsChao.push({ nt: nt[m], ch: c });
        }

        stt++;

        // Không nhà thầu nào chào -> 1 dòng báo trống, không lặp tên nhà thầu
        if (!dsChao.length) {
            html += '<tr>' +
                '<td class="col-id">' + stt + '</td>' +
                '<td>' + APP.escape(r.ma_hh || '—') + '</td>' +
                '<td class="sticky-col"><span class="cell-main">' + APP.escape(r.ten_hang_hoa) + '</span></td>' +
                '<td>' + APP.escape(r.dvt || '—') + '</td>' +
                '<td class="col-qty">' + Number(r.so_luong || 0).toLocaleString('vi-VN') + '</td>' +
                '<td class="no-quote" colspan="' + soCotNt + '">Chưa có nhà thầu nào chào giá</td>' +
                '</tr>';
            continue;
        }

        // Nhiều nhà thầu -> gộp dọc 5 cột hàng hóa bằng rowspan
        for (var i2 = 0; i2 < dsChao.length; i2++) {
            var x = dsChao[i2], ch = x.ch, nhaThau = x.nt;
            var laMin = r.nha_thau_min === nhaThau.id;
            var laDongDau = (i2 === 0);
            var rs = dsChao.length > 1 ? ' rowspan="' + dsChao.length + '"' : '';

            html += '<tr' + (laMin && dsChao.length > 1 ? ' class="row-best"' : '') + '>';

            if (laDongDau) {
                html += '<td class="col-id"' + rs + '>' + stt + '</td>' +
                    '<td' + rs + '>' + APP.escape(r.ma_hh || '—') + '</td>' +
                    '<td class="sticky-col"' + rs + '><span class="cell-main">' +
                        APP.escape(r.ten_hang_hoa) + '</span>' +
                        '<span class="cell-sub">' + dsChao.length + '/' + nt.length + ' nhà thầu chào</span></td>' +
                    '<td' + rs + '>' + APP.escape(r.dvt || '—') + '</td>' +
                    '<td class="col-qty"' + rs + '>' +
                        Number(r.so_luong || 0).toLocaleString('vi-VN') + '</td>';
            }

            html += '<td><span class="cell-main">' + APP.escape(nhaThau.ten_cong_ty) + '</span></td>' +
                    '<td><span class="text-mono">' + APP.escape(nhaThau.ma_so_thue || '—') + '</span></td>';

            if (!chiGia) {
                var tenModel = APP.escape(ch.ten_thuong_mai || '');
                if (ch.model) tenModel += '<span class="cell-sub">' + APP.escape(ch.model) + '</span>';
                var hangXx = APP.escape(ch.hang_san_xuat || '');
                if (ch.xuat_xu) hangXx += '<span class="cell-sub">' + APP.escape(ch.xuat_xu) + '</span>';
                html += '<td>' + (tenModel || '<span class="text-muted">—</span>') + '</td>' +
                        '<td>' + (hangXx || '<span class="text-muted">—</span>') + '</td>';
            }

            html += '<td class="col-price' + (laMin ? ' is-best' : '') + '"' +
                    (laMin ? ' title="Giá thấp nhất cho hàng hóa này"' : '') + '>' +
                    money(ch.don_gia) + '</td>' +
                    '<td class="col-price">' + money(ch.thanh_tien) + '</td>' +
                    '</tr>';
        }
    }

    // ---- Tổng cộng theo từng nhà thầu ----
    html += '<tr class="row-sep"><td colspan="' + (colsFix + soCotNt) + '"></td></tr>';
    html += '<tr style="background:var(--gray-100);font-weight:600">' +
        '<td colspan="' + colsFix + '" class="sticky-col" style="text-align:right;background:var(--gray-100)">' +
        'TỔNG CỘNG THEO NHÀ THẦU</td>' +
        '<td colspan="' + (soCotNt - 1) + '"></td>' +
        '<td class="col-price">Tổng tiền</td></tr>';

    for (var n = 0; n < nt.length; n++) {
        html += '<tr style="background:var(--gray-50)">' +
            '<td colspan="' + colsFix + '" class="sticky-col" style="background:var(--gray-50)"></td>' +
            '<td><span class="cell-main">' + APP.escape(nt[n].ten_cong_ty) + '</span></td>' +
            '<td><span class="text-mono">' + APP.escape(nt[n].ma_so_thue || '—') + '</span></td>' +
            (chiGia ? '' : '<td></td><td></td>') +
            '<td></td>' +
            '<td class="col-price" style="font-weight:700">' + money(nt[n].tong_tien) + '</td>' +
            '</tr>';
    }

    $('#tbody').html(html);
}

function ctxItem(icon, label, value) {
    return '<span class="ctx-item">' + APP.icon(icon, 16) +
        '<span class="ctx-label">' + APP.escape(label) + '</span>' +
        '<span class="ctx-value">' + APP.escape(value) + '</span></span>';
}

function statCard(icon, cls, value, label) {
    return '<div class="stat-card">' +
        '<span class="stat-icon ' + cls + '">' + APP.icon(icon, 20) + '</span>' +
        '<span class="stat-value">' + APP.escape(String(value)) + '</span>' +
        '<span class="stat-label">' + APP.escape(label) + '</span>' +
        '</div>';
}

$('#filterGoiThau').on('change', function () {
    window.location.href = '?goi_thau_id=' + encodeURIComponent($(this).val());
});
$('#chkChiGia').on('change', render);
$('#btnXuatExcel').attr('href', URL_XUAT + '?goi_thau_id=' + GOI_THAU_ID);

$(document).ready(loadData);
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
