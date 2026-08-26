<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../BUS/BG_QuanLyFile_BUS.php';
require_once __DIR__ . '/../../BUS/BG_GoiThau_BUS.php';

Helper::requireLogin();
PhanQuyenHelper::requireQuyenView('BG_QuanLyFile');

$canDel = PhanQuyenHelper::hasQuyen('BG_QuanLyFile', PhanQuyenHelper::QUYEN_XOA);

$goiThauCombo = BG_GoiThau_BUS::getCombo();
$goiThauId    = (int)Helper::get('goi_thau_id', 0);

// Chan mo thang bang URL goi thau khong duoc phan quyen (3B.1):
// an tren giao dien khong phai la bao mat.
if ($goiThauId > 0) {
    require_once __DIR__ . '/../../BUS/BG_QuyenGoiThau_BUS.php';
    BG_QuyenGoiThau_BUS::requireXem($goiThauId);
}

$pageTitle  = 'Quản lý file bản ký';
$activeMenu = 'BG_QuanLyFile';
$AJAX = AppConfig::baseUrl('GUI/BG_QuanLyFile/ajax_handler.php');
require __DIR__ . '/../layouts/header.php';
?>

<nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="<?= AppConfig::baseUrl('GUI/dashboard/index.php') ?>">Trang chủ</a>
    <span class="sep">›</span> Nghiệp vụ báo giá
    <span class="sep">›</span> <span>Quản lý file bản ký</span>
</nav>

<!-- ============ Thống kê ============ -->
<div class="stat-row" id="thongKe"></div>

<div class="card">
    <div class="toolbar">
        <div class="left">
            <select id="filterGoiThau" class="form-select" style="max-width:300px" aria-label="Lọc theo gói thầu">
                <option value="0">Tất cả gói thầu</option>
                <?php foreach ($goiThauCombo as $g): ?>
                    <option value="<?= (int)$g['id'] ?>" <?= (int)$g['id'] === $goiThauId ? 'selected' : '' ?>>
                        <?= Helper::h($g['so_thong_bao'] . ' — ' . mb_substr($g['ten_goi_thau'], 0, 45)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="search-box" style="max-width:280px">
                <?= IconHelper::svg('search', 16) ?>
                <input type="text" id="search" class="form-control" placeholder="Tìm công ty, MST, tên file...">
            </span>
            <select id="filterLoai" class="form-select" style="max-width:150px" aria-label="Lọc loại file">
                <option value="">Mọi loại file</option>
                <option value="pdf">PDF</option>
                <option value="anh">Ảnh</option>
            </select>
            <select id="sapXep" class="form-select" style="max-width:180px" aria-label="Sắp xếp">
                <option value="moi_nhat">Mới nhất</option>
                <option value="cu_nhat">Cũ nhất</option>
                <option value="lon_nhat">Dung lượng lớn nhất</option>
                <option value="ten_cty">Tên công ty (A→Z)</option>
                <option value="goi_thau">Theo gói thầu</option>
            </select>
        </div>
        <div class="right">
            <button type="button" class="btn btn-outline-secondary" onclick="xemMoCoi()">
                <?= IconHelper::svg('alert-triangle', 16) ?><span class="btn-label">File mồ côi</span>
            </button>
        </div>
    </div>

    <div class="table-wrap" id="tableWrap">
        <table class="table">
            <thead>
                <tr>
                    <th class="col-id">ID</th>
                    <th>Nhà thầu</th>
                    <th>Gói thầu</th>
                    <th>Tên file lưu trữ</th>
                    <th>Nhóm</th>
                    <th>Loại</th>
                    <th>Dung lượng</th>
                    <th>Ngày tải lên</th>
                    <th class="col-actions">Thao tác</th>
                </tr>
            </thead>
            <tbody id="tbody"></tbody>
        </table>
    </div>

    <div class="pagination-wrap" id="paginationWrap"></div>
</div>

<!-- ============ Modal xem file ============ -->
<div class="modal" id="viewModal">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="vTitle" style="max-width:1000px">
        <div class="modal-header">
            <h3 id="vTitle">Bản báo giá có dấu và chữ ký</h3>
            <button type="button" class="close" onclick="closeView()" aria-label="Đóng"><?= IconHelper::svg('x', 20) ?></button>
        </div>
        <div class="modal-body">
            <div id="vThongTin" class="detail-grid" style="margin-bottom:14px"></div>
            <div id="vKhung"></div>
        </div>
        <div class="modal-footer">
            <a class="btn btn-outline-secondary" id="vTaiVe" href="#">
                <?= IconHelper::svg('download', 16) ?>Tải về máy
            </a>
            <a class="btn btn-outline-secondary" id="vTabMoi" href="#" target="_blank" rel="noopener">
                <?= IconHelper::svg('external-link', 16) ?>Mở tab mới
            </a>
            <button type="button" class="btn btn-secondary" onclick="closeView()">Đóng</button>
        </div>
    </div>
</div>

<!-- ============ Modal file mồ côi ============ -->
<div class="modal" id="mcModal">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="mcTitle" style="max-width:800px">
        <div class="modal-header">
            <h3 id="mcTitle">File mồ côi</h3>
            <button type="button" class="close" onclick="closeMoCoi()" aria-label="Đóng"><?= IconHelper::svg('x', 20) ?></button>
        </div>
        <div class="modal-body">
            <div class="alert alert-info">
                <?= IconHelper::svg('info', 16) ?>
                <span>
                    File nằm trong thư mục lưu trữ nhưng <strong>không báo giá nào tham chiếu</strong>.
                    Thường sinh ra khi xóa báo giá vĩnh viễn hoặc tải lên bị gián đoạn.
                </span>
            </div>
            <div id="mcDanhSach"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeMoCoi()">Đóng</button>
        </div>
    </div>
</div>

<script>
var AJAX_URL = <?= json_encode($AJAX) ?>;
var URL_XEM  = <?= json_encode(AppConfig::baseUrl('GUI/BG_QuanLyFile/xem_file.php')) ?>;
var URL_BAO_GIA = <?= json_encode(AppConfig::baseUrl('GUI/BG_BaoGia/index.php')) ?>;
var CAN = { del: <?= $canDel ? 'true' : 'false' ?> };
var currentPage = 1, pageSize = <?= (int)AppConfig::DEFAULT_PAGE_SIZE ?>, isLoading = false;
var firstLoad = true;

function loadThongKe() {
    APP.ajax(AJAX_URL, { action: 'thongKe', goi_thau_id: $('#filterGoiThau').val() }, {
        success: function (res) {
            var d = res.data || {};
            $('#thongKe').html(
                statBox('file-spreadsheet', d.tong_file, 'Tổng số file') +
                statBox('package', d.dung_luong_dep, 'Dung lượng') +
                statBox('file-clock', d.so_pdf + ' / ' + d.so_anh, 'PDF / Ảnh') +
                statBox('building', d.so_nha_thau, 'Nhà thầu') +
                statBox('clipboard-list', d.so_goi_thau, 'Gói thầu')
            );
        }
    });
}

function statBox(icon, giaTri, nhan) {
    return '<div class="stat-box">' +
        '<span class="stat-icon">' + APP.icon(icon, 20) + '</span>' +
        '<span class="stat-body"><b>' + APP.escape(String(giaTri == null ? '—' : giaTri)) + '</b>' +
        '<span>' + APP.escape(nhan) + '</span></span></div>';
}

function loadData() {
    if (isLoading) return;
    isLoading = true;
    if (firstLoad) { $('#tbody').html(APP.skeletonRows(6, 8)); }
    else { APP.showLoading('#tableWrap'); }

    APP.ajax(AJAX_URL, {
        action: 'getPaged',
        page: currentPage,
        pageSize: pageSize,
        goi_thau_id: $('#filterGoiThau').val(),
        search: $('#search').val(),
        loai_file: $('#filterLoai').val(),
        sap_xep: $('#sapXep').val()
    }, {
        success: function (res) {
            renderTable(res.data || []);
            $('#paginationWrap').html(APP.renderPagination(res.pagination));
        },
        complete: function () { isLoading = false; firstLoad = false; APP.hideLoading('#tableWrap'); }
    });
}

function renderTable(rows) {
    if (!rows.length) {
        $('#tbody').html(APP.emptyRow(8, 'Chưa có file bản ký nào'));
        return;
    }

    var html = '';
    for (var i = 0; i < rows.length; i++) {
        var r = rows[i];

        var loai = r.la_anh
            ? '<span class="badge badge-info">Ảnh</span>'
            : (['xlsx', 'xls'].indexOf(String(r.loai_file).toLowerCase()) > -1
                ? '<span class="badge badge-success">Excel</span>'
                : '<span class="badge badge-neutral">PDF</span>');

        // Nhóm file: nhà thầu tải lên ở bước nào
        var NHOM = {
            ban_ky:        ['Bản ký', 'badge-success'],
            catalog:       ['Catalog', 'badge-info'],
            catalog_excel: ['Excel chỉ dẫn', 'badge-warning']
        };
        var nh = NHOM[r.nhom_file] || ['—', 'badge-neutral'];
        var nhom = '<span class="badge ' + nh[1] + '">' + nh[0] + '</span>';

        // File mất trên đĩa nhưng DB vẫn trỏ tới -> cảnh báo rõ
        var canhBao = r.file_ton_tai ? '' :
            '<span class="badge badge-danger badge-quote" title="Bản ghi còn nhưng file đã mất khỏi thư mục">' +
            APP.icon('alert-triangle', 12) + 'Mất file</span>';

        // Dùng ID FILE (r.id) — 1 báo giá giờ có nhiều file (bản ký, catalog,
        // Excel chỉ dẫn) nên không định danh theo id báo giá được nữa.
        var bgId = r.bao_gia_id;

        var actions = '';
        if (r.file_ton_tai) {
            actions += '<button type="button" class="btn btn-sm btn-outline-primary js-xem"' +
                ' data-id="' + r.id + '" title="Xem file">' + APP.icon('eye', 15) + '</button>';
            actions += '<a class="btn btn-sm btn-outline-secondary" href="' + URL_XEM + '?id=' + r.id +
                '&tai_ve=1" title="Tải về máy">' + APP.icon('download', 15) + '</a>';
        }
        actions += '<a class="btn btn-sm btn-outline-secondary" href="' + URL_BAO_GIA +
            '?goi_thau_id=' + r.goi_thau_id + '" title="Xem báo giá gốc">' +
            APP.icon('external-link', 15) + '</a>';
        if (CAN.del) {
            actions += '<button type="button" class="btn btn-sm btn-outline-danger js-xoa"' +
                ' data-id="' + r.id + '" data-cty="' + APP.escape(r.ten_cong_ty) + '"' +
                ' title="Xóa file">' + APP.icon('trash', 15) + '</button>';
        }

        html += '<tr>' +
            '<td class="col-id" title="Mã file trong kho lưu trữ">' + r.id + '</td>' +
            '<td><span class="cell-main">' + APP.escape(r.ten_cong_ty) + '</span>' +
                '<span class="cell-sub">MST: ' + APP.escape(r.ma_so_thue || '—') + '</span></td>' +
            '<td><span class="text-mono">' + APP.escape(r.so_thong_bao || '—') + '</span></td>' +
            '<td><span class="text-mono" style="font-size:12px;word-break:break-all">' +
                APP.escape(r.ten_file) + '</span>' + canhBao + '</td>' +
            '<td>' + nhom + '</td>' +
            '<td>' + loai + '</td>' +
            '<td>' + APP.escape(r.kich_thuoc_dep || '—') + '</td>' +
            '<td>' + (r.ngay_tao ? APP.escape(APP.formatDateTime(r.ngay_tao)) : '—') + '</td>' +
            '<td class="col-actions"><span class="row-actions">' + actions + '</span></td>' +
            '</tr>';
    }
    $('#tbody').html(html);
}

/* ============ XEM FILE ============ */
function xemFile(id) {
    APP.ajax(AJAX_URL, { action: 'getFile', id: id }, {
        success: function (res) {
            var d = res.data;
            var u = URL_XEM + '?id=' + d.id;   // endpoint nhận ID FILE

            $('#vThongTin').html(
                dItem('Nhà thầu', d.ten_cong_ty) +
                dItem('Mã số thuế', d.ma_so_thue) +
                dItem('Gói thầu', d.so_thong_bao) +
                dItem('Dung lượng', d.kich_thuoc_dep) +
                dItem('Mã file', '#' + d.id) +
                dItem('Loại', d.ten_nhom || '—') +
                dItem('Tên file lưu trữ', d.ten_file, 'span-2') +
                dItem('Tên file gốc nhà thầu đặt', d.ten_file_goc, 'span-2')
            );
            $('#vTaiVe').attr('href', u + '&tai_ve=1');
            $('#vTabMoi').attr('href', u);

            if (d.la_excel) {
                // Trình duyệt không hiển thị được Excel — chỉ mời tải về
                $('#vKhung').html(
                    '<div class="state-card" style="margin:0">' +
                    '<span class="state-icon">' + APP.icon('file-spreadsheet', 40) + '</span>' +
                    '<h2 style="font-size:16px">File Excel chỉ dẫn vị trí tài liệu</h2>' +
                    '<p>Không xem trực tiếp được trên trình duyệt. Bấm <strong>Tải về máy</strong> ' +
                    'rồi mở bằng Excel.</p></div>'
                );
            } else if (d.la_anh) {
                $('#vKhung').html('<img src="' + u + '" alt="Bản ký" style="max-width:100%;display:block;' +
                    'margin:0 auto;border:1px solid var(--gray-200);border-radius:var(--radius-sm)">');
            } else {
                $('#vKhung').html(
                    '<iframe src="' + u + '" title="Bản ký" style="width:100%;height:70vh;' +
                    'border:1px solid var(--gray-200);border-radius:var(--radius-sm);background:var(--gray-50)"></iframe>' +
                    '<p class="form-hint" style="margin-top:10px;text-align:center">' +
                    'Không thấy nội dung? Bấm <strong>Mở tab mới</strong> hoặc <strong>Tải về máy</strong>.</p>'
                );
            }
            $('#viewModal').addClass('open');
        }
    });
}

function closeView() {
    $('#viewModal').removeClass('open');
    $('#vKhung').empty();   // gỡ iframe/img để dừng tải file
}

function dItem(label, value, cls) {
    var empty = (value === null || typeof value === 'undefined' || String(value).trim() === '');
    return '<div class="detail-item ' + (cls || '') + '">' +
        '<span class="detail-label">' + APP.escape(label) + '</span>' +
        '<span class="detail-value' + (empty ? ' is-empty' : '') + '">' +
        APP.escape(empty ? 'Chưa có' : value) + '</span></div>';
}

/* ============ XÓA FILE ============ */
function xoaFile(id, tenCty) {
    APP.confirm(
        'Xóa file bản ký của <strong>' + APP.escape(tenCty) + '</strong>?<br>' +
        'File là tài liệu pháp lý — xóa rồi không khôi phục được. ' +
        'Nếu báo giá được xác nhận bằng chính bản ký này thì sẽ trở lại <strong>Chờ xác nhận</strong>.',
        function () {
            APP.ajax(AJAX_URL, { action: 'xoaFile', id: id }, {
                success: function (res) {
                    APP.toast(res.message, 'success');
                    loadData(); loadThongKe();
                }
            });
        },
        { yesClass: 'btn-danger', yesText: 'Xóa file' }
    );
}

/* ============ FILE MỒ CÔI ============ */
function xemMoCoi() {
    $('#mcDanhSach').html('<p class="form-hint">Đang kiểm tra...</p>');
    $('#mcModal').addClass('open');

    APP.ajax(AJAX_URL, { action: 'fileMoCoi' }, {
        success: function (res) {
            var list = res.data || [];
            if (!list.length) {
                $('#mcDanhSach').html('<div class="empty-state">' + APP.icon('check-circle', 32) +
                    '<p>Không có file mồ côi. Thư mục lưu trữ sạch sẽ.</p></div>');
                return;
            }
            var h = '<table class="table"><thead><tr><th>Tên file</th><th>Dung lượng</th>' +
                    '<th>Ngày sửa</th>' + (CAN.del ? '<th class="col-actions">Xóa</th>' : '') + '</tr></thead><tbody>';
            for (var i = 0; i < list.length; i++) {
                var f = list[i];
                h += '<tr>' +
                    '<td><span class="text-mono" style="font-size:12px;word-break:break-all">' +
                        APP.escape(f.ten_file) + '</span></td>' +
                    '<td>' + APP.escape(f.kich_thuoc_dep) + '</td>' +
                    '<td>' + APP.escape(APP.formatDateTime(f.ngay_sua)) + '</td>' +
                    (CAN.del ? '<td class="col-actions"><button type="button" class="btn btn-sm btn-outline-danger js-xoa-mc"' +
                        ' data-ten="' + APP.escape(f.ten_file) + '">' + APP.icon('trash', 15) + '</button></td>' : '') +
                    '</tr>';
            }
            $('#mcDanhSach').html(h + '</tbody></table>');
        }
    });
}

function closeMoCoi() { $('#mcModal').removeClass('open'); }

/* ============ Sự kiện (nội dung render động → delegate) ============ */
$(document).on('click', '.js-xem', function () {
    xemFile(parseInt($(this).data('id'), 10));
});
$(document).on('click', '.js-xoa', function () {
    var $b = $(this);
    xoaFile(parseInt($b.data('id'), 10), String($b.data('cty') || ''));
});
$(document).on('click', '.js-xoa-mc', function () {
    var ten = String($(this).data('ten') || '');
    APP.confirm('Xóa vĩnh viễn file <strong>' + APP.escape(ten) + '</strong>?', function () {
        APP.ajax(AJAX_URL, { action: 'xoaMoCoi', ten_file: ten }, {
            success: function (res) { APP.toast(res.message, 'success'); xemMoCoi(); }
        });
    }, { yesClass: 'btn-danger', yesText: 'Xóa' });
});

$('#search').on('keyup', APP.debounce(function () { currentPage = 1; loadData(); }, 350));
$('#filterGoiThau').on('change', function () { currentPage = 1; loadData(); loadThongKe(); });
$('#filterLoai, #sapXep').on('change', function () { currentPage = 1; loadData(); });

$('#viewModal, #mcModal').on('click', function (e) { if (e.target === this) $(this).removeClass('open'); });
$(document).on('keydown', function (e) {
    if (e.key !== 'Escape') return;
    if ($('#viewModal').hasClass('open')) { closeView(); return; }
    if ($('#mcModal').hasClass('open')) closeMoCoi();
});

APP.bindPagination('#paginationWrap', function (p) { currentPage = p; loadData(); });

$(document).ready(function () { loadThongKe(); loadData(); });
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
