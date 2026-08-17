<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../BUS/BG_BaoGia_BUS.php';
require_once __DIR__ . '/../../BUS/BG_GoiThau_BUS.php';

Helper::requireLogin();
PhanQuyenHelper::requireQuyenView('BG_BaoGia');

$canEdit = PhanQuyenHelper::hasQuyen('BG_BaoGia', PhanQuyenHelper::QUYEN_SUA);
$canDel  = PhanQuyenHelper::hasQuyen('BG_BaoGia', PhanQuyenHelper::QUYEN_XOA);

$goiThauCombo = BG_GoiThau_BUS::getCombo();
$goiThauId = (int)Helper::get('goi_thau_id', 0);

$pageTitle  = 'Báo giá nhà thầu';
$activeMenu = 'BG_BaoGia';
$AJAX = AppConfig::baseUrl('GUI/BG_BaoGia/ajax_handler.php');
require __DIR__ . '/../layouts/header.php';
?>

<nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="<?= AppConfig::baseUrl('GUI/dashboard/index.php') ?>">Trang chủ</a>
    <span class="sep">›</span> <a href="<?= AppConfig::baseUrl('GUI/BG_GoiThau/index.php') ?>">Gói thầu</a>
    <span class="sep">›</span> <span>Báo giá nhà thầu</span>
</nav>

<div class="card">
    <div class="toolbar">
        <div class="left">
            <select id="filterGoiThau" class="form-select" style="max-width:320px" aria-label="Lọc theo gói thầu">
                <option value="0">Tất cả gói thầu</option>
                <?php foreach ($goiThauCombo as $g): ?>
                    <option value="<?= (int)$g['id'] ?>" <?= (int)$g['id'] === $goiThauId ? 'selected' : '' ?>>
                        <?= Helper::h($g['so_thong_bao'] . ' — ' . mb_substr($g['ten_goi_thau'], 0, 50)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="search-box" style="max-width:280px">
                <?= IconHelper::svg('search', 16) ?>
                <input type="text" id="search" class="form-control" placeholder="Tìm công ty, MST, email...">
            </span>
            <select id="filterTrangThai" class="form-select" style="max-width:170px" aria-label="Lọc trạng thái">
                <option value="-1">Tất cả trạng thái</option>
                <?php foreach (BG_BaoGia_PUBLIC::danhSachTrangThai() as $v => $t): ?>
                    <option value="<?= (int)$v ?>"><?= Helper::h($t) ?></option>
                <?php endforeach; ?>
            </select>
            <select id="filterDaXoa" class="form-select" style="max-width:150px" aria-label="Lọc thùng rác">
                <option value="0">Đang hoạt động</option>
                <option value="1">Thùng rác</option>
            </select>
        </div>
        <div class="right">
            <a class="btn btn-outline-primary" id="btnTongHop" href="#">
                <?= IconHelper::svg('bar-chart', 16) ?><span class="btn-label">Tổng hợp</span>
            </a>
        </div>
    </div>

    <div class="table-wrap" id="tableWrap">
        <table class="table">
            <thead>
                <tr>
                    <th class="col-id">ID</th>
                    <th>Nhà thầu</th>
                    <th>Gói thầu</th>
                    <th>Dòng chào</th>
                    <th class="col-price">Tổng tiền</th>
                    <th>Ngày nộp</th>
                    <th>Trạng thái</th>
                    <th class="col-actions">Thao tác</th>
                </tr>
            </thead>
            <tbody id="tbody"></tbody>
        </table>
    </div>

    <div class="pagination-wrap" id="paginationWrap"></div>
</div>

<!-- ============ Modal chi tiết báo giá ============ -->
<div class="modal" id="ctModal">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="ctTitle" style="max-width:1180px">
        <div class="modal-header">
            <h3 id="ctTitle">Chi tiết báo giá</h3>
            <button type="button" class="close" onclick="closeCt()" aria-label="Đóng"><?= IconHelper::svg('x', 20) ?></button>
        </div>
        <div class="modal-body">
            <div id="ctThongTin"></div>
            <h4 style="font-size:14px;margin:20px 0 10px;color:var(--gray-700)">Các dòng chào giá</h4>
            <div class="table-wrap" id="ctTableWrap" style="max-height:420px;overflow:auto">
                <table class="table" id="ctTable">
                    <thead>
                        <tr>
                            <th class="col-id">STT</th>
                            <th>Hàng hóa</th>
                            <th>Tên thương mại / Model</th>
                            <th>Hãng SX / Xuất xứ</th>
                            <th class="col-qty">SL</th>
                            <th class="col-price">Đơn giá</th>
                            <th class="col-price">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody id="ctBody"></tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <a class="btn btn-outline-secondary" id="btnXuatBaoGia" href="#">
                <?= IconHelper::svg('download', 16) ?>Xuất Excel
            </a>
            <button type="button" class="btn btn-secondary" onclick="closeCt()">Đóng</button>
        </div>
    </div>
</div>

<!-- ============ Modal sửa thông tin công ty ============ -->
<div class="modal" id="editModal">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="editTitle" style="max-width:720px">
        <div class="modal-header">
            <h3 id="editTitle">Sửa thông tin nhà thầu</h3>
            <button type="button" class="close" onclick="closeEdit()" aria-label="Đóng"><?= IconHelper::svg('x', 20) ?></button>
        </div>
        <form id="editForm" onsubmit="return saveEdit()">
            <div class="modal-body">
                <input type="hidden" id="e_id" name="id">
                <div class="form-group">
                    <label for="e_ten_cong_ty">Tên công ty <span class="req">*</span></label>
                    <input type="text" id="e_ten_cong_ty" name="ten_cong_ty" class="form-control" required maxlength="500">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="e_ma_so_thue">Mã số thuế <span class="req">*</span></label>
                        <input type="text" id="e_ma_so_thue" name="ma_so_thue" class="form-control" required maxlength="14">
                        <div class="form-hint">10 số, hoặc dạng 0101234567-001.</div>
                    </div>
                    <div class="form-group">
                        <label for="e_hieu_luc_bao_gia">Hiệu lực báo giá (ngày)</label>
                        <input type="number" id="e_hieu_luc_bao_gia" name="hieu_luc_bao_gia"
                               class="form-control" min="0" max="3650">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="e_email">Email liên hệ</label>
                        <input type="email" id="e_email" name="email" class="form-control" maxlength="200">
                    </div>
                    <div class="form-group">
                        <label for="e_dien_thoai">Số điện thoại</label>
                        <input type="text" id="e_dien_thoai" name="dien_thoai" class="form-control" maxlength="50">
                    </div>
                </div>
                <div class="form-group">
                    <label for="e_dia_chi">Địa chỉ công ty</label>
                    <input type="text" id="e_dia_chi" name="dia_chi" class="form-control" maxlength="1000">
                </div>
                <div class="form-group">
                    <label for="e_ghi_chu">Ghi chú</label>
                    <textarea id="e_ghi_chu" name="ghi_chu" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEdit()">Hủy</button>
                <button type="submit" class="btn btn-primary"><?= IconHelper::svg('save', 16) ?>Lưu</button>
            </div>
        </form>
    </div>
</div>

<!-- ============ Modal từ chối ============ -->
<div class="modal" id="tuChoiModal">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="tcTitle" style="max-width:540px">
        <div class="modal-header">
            <h3 id="tcTitle">Từ chối báo giá</h3>
            <button type="button" class="close" onclick="closeTuChoi()" aria-label="Đóng"><?= IconHelper::svg('x', 20) ?></button>
        </div>
        <form id="tuChoiForm" onsubmit="return saveTuChoi()">
            <div class="modal-body">
                <input type="hidden" id="tc_id">
                <p style="font-size:13.5px;color:var(--gray-600);margin:0 0 14px">
                    Báo giá bị từ chối sẽ <strong>không</strong> được đưa vào bảng tổng hợp.
                    Nhà thầu có thể nộp lại bản mới bằng cùng mã số thuế.
                </p>
                <div class="form-group">
                    <label for="tc_ly_do">Lý do từ chối <span class="req">*</span></label>
                    <textarea id="tc_ly_do" class="form-control" rows="3" required maxlength="1000"
                              placeholder="VD: Không nhận được bản giấy trước hạn; thiếu chứng nhận CE"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeTuChoi()">Hủy</button>
                <button type="submit" class="btn btn-danger"><?= IconHelper::svg('x-circle', 16) ?>Từ chối</button>
            </div>
        </form>
    </div>
</div>

<script>
var AJAX_URL = <?= json_encode($AJAX) ?>;
var URL_XUAT_BG = <?= json_encode(AppConfig::baseUrl('GUI/BG_BaoGia/download.php')) ?>;
var URL_TONG_HOP = <?= json_encode(AppConfig::baseUrl('GUI/BG_TongHop/index.php')) ?>;
var CAN = { edit: <?= $canEdit ? 'true' : 'false' ?>, del: <?= $canDel ? 'true' : 'false' ?> };
var TT = <?= json_encode(BG_BaoGia_PUBLIC::danhSachTrangThai(), JSON_UNESCAPED_UNICODE) ?>;
var TT_CHO = <?= (int)BG_BaoGia_PUBLIC::TT_CHO_XAC_NHAN ?>;
var TT_XN  = <?= (int)BG_BaoGia_PUBLIC::TT_DA_XAC_NHAN ?>;
var TT_TC  = <?= (int)BG_BaoGia_PUBLIC::TT_TU_CHOI ?>;

var currentPage = 1, pageSize = <?= (int)AppConfig::DEFAULT_PAGE_SIZE ?>, isLoading = false;
var firstLoad = true;

function money(v) { return Number(v || 0).toLocaleString('vi-VN'); }
function currentTrash() { return $('#filterDaXoa').val() === '1'; }

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
        trang_thai: $('#filterTrangThai').val(),
        da_xoa: $('#filterDaXoa').val()
    }, {
        success: function (res) {
            renderTable(res.data || []);
            $('#paginationWrap').html(APP.renderPagination(res.pagination));
        },
        complete: function () { isLoading = false; firstLoad = false; APP.hideLoading('#tableWrap'); }
    });
}

function badgeTrangThai(tt) {
    tt = parseInt(tt, 10);
    var cls = 'badge-warning';
    if (tt === TT_XN) cls = 'badge-success';
    else if (tt === TT_TC) cls = 'badge-danger';
    return '<span class="badge ' + cls + '">' + APP.escape(TT[tt] || '—') + '</span>';
}

function renderTable(rows) {
    if (!rows.length) {
        $('#tbody').html(APP.emptyRow(8, currentTrash()
            ? 'Thùng rác trống'
            : 'Chưa có nhà thầu nào nộp báo giá. Nhà thầu nộp qua link QR của gói thầu.'));
        return;
    }
    var trash = currentTrash(), html = '';

    for (var i = 0; i < rows.length; i++) {
        var r = rows[i];
        var tt = parseInt(r.trang_thai, 10);
        var soDong = parseInt(r.so_dong_chao, 10) || 0;

        var actions = '';
        if (trash) {
            if (CAN.edit) actions += '<button class="btn btn-sm btn-outline-primary" onclick="restore(' + r.id + ')" title="Khôi phục">' + APP.icon('rotate-ccw', 15) + '</button>';
            if (CAN.del)  actions += '<button class="btn btn-sm btn-outline-danger" onclick="delForever(' + r.id + ')" title="Xóa vĩnh viễn">' + APP.icon('trash', 15) + '</button>';
        } else {
            actions += '<button class="btn btn-sm btn-outline-secondary" onclick="showCt(' + r.id + ')" title="Xem chi tiết">' + APP.icon('eye', 15) + '</button>';
            if (CAN.edit) {
                if (tt === TT_XN) {
                    actions += '<button class="btn btn-sm btn-outline-secondary" onclick="boXacNhan(' + r.id + ')" title="Bỏ xác nhận">' + APP.icon('rotate-ccw', 15) + '</button>';
                } else {
                    actions += '<button class="btn btn-sm btn-outline-primary" onclick="xacNhan(' + r.id + ')" title="Xác nhận đã nhận bản giấy">' + APP.icon('check-circle', 15) + '</button>';
                }
                actions += '<button class="btn btn-sm btn-outline-primary" onclick="edit(' + r.id + ')" title="Sửa thông tin">' + APP.icon('pencil', 15) + '</button>';
                if (tt !== TT_TC) {
                    actions += '<button class="btn btn-sm btn-outline-danger" onclick="openTuChoi(' + r.id + ')" title="Từ chối">' + APP.icon('x-circle', 15) + '</button>';
                }
            }
            if (CAN.del) actions += '<button class="btn btn-sm btn-outline-danger" onclick="del(' + r.id + ')" title="Xóa">' + APP.icon('trash', 15) + '</button>';
        }
        if (!actions) actions = '<span class="text-muted">—</span>';

        var lyDo = (tt === TT_TC && r.ly_do_tu_choi)
            ? '<span class="cell-sub" title="' + APP.escape(r.ly_do_tu_choi) + '">'
              + APP.escape(String(r.ly_do_tu_choi).substring(0, 45)) + '</span>'
            : '';

        html += '<tr>' +
            '<td class="col-id">' + r.id + '</td>' +
            '<td><span class="cell-main">' + APP.escape(r.ten_cong_ty) + '</span>' +
                '<span class="cell-sub">MST: ' + APP.escape(r.ma_so_thue || '—') + '</span></td>' +
            '<td><span class="text-mono">' + APP.escape(r.so_thong_bao || '—') + '</span></td>' +
            '<td>' + (soDong > 0
                ? soDong + ' dòng'
                : '<span class="badge badge-warning">Chưa chào</span>') + '</td>' +
            '<td class="col-price">' + money(r.tong_tien) + '</td>' +
            '<td>' + (r.ngay_nop
                ? APP.escape(APP.formatDateTime(r.ngay_nop))
                : '<span class="text-muted">Chưa nộp</span>') + '</td>' +
            '<td>' + badgeTrangThai(tt) + lyDo + '</td>' +
            '<td class="col-actions"><span class="row-actions">' + actions + '</span></td>' +
            '</tr>';
    }
    $('#tbody').html(html);
}

/* ============ CHI TIẾT ============ */
function showCt(id) {
    APP.ajax(AJAX_URL, { action: 'getChiTiet', id: id }, {
        success: function (res) {
            var bg = res.data.bao_gia, ct = res.data.chi_tiet || [];

            $('#ctTitle').text('Báo giá — ' + bg.ten_cong_ty);
            $('#btnXuatBaoGia').attr('href', URL_XUAT_BG + '?id=' + bg.id);

            var info = '<div class="detail-grid">' +
                dItem('Tên công ty', bg.ten_cong_ty, 'span-2') +
                dItem('Mã số thuế', bg.ma_so_thue) +
                dItem('Email', bg.email) +
                dItem('Điện thoại', bg.dien_thoai) +
                dItem('Địa chỉ', bg.dia_chi, 'span-2') +
                dItem('Hiệu lực báo giá', bg.hieu_luc_bao_gia ? bg.hieu_luc_bao_gia + ' ngày' : '') +
                dItem('Gói thầu', (bg.so_thong_bao || '') + ' — ' + (bg.ten_goi_thau || ''), 'span-2') +
                dItem('Ngày nộp online', bg.ngay_nop ? APP.formatDateTime(bg.ngay_nop) : '') +
                dItem('Ngày xác nhận bản giấy', bg.ngay_xac_nhan ? APP.formatDateTime(bg.ngay_xac_nhan) : '') +
                dItem('Người xác nhận', bg.tai_khoan_xac_nhan) +
                dItem('Tổng tiền', money(bg.tong_tien) + ' VND') +
                dItem('Trạng thái', TT[parseInt(bg.trang_thai, 10)]) +
                (bg.ly_do_tu_choi ? dItem('Lý do từ chối', bg.ly_do_tu_choi, 'span-2') : '') +
                (bg.ghi_chu ? dItem('Ghi chú', bg.ghi_chu, 'span-2') : '') +
                '</div>';
            $('#ctThongTin').html(info);

            var html = '';
            if (!ct.length) {
                html = APP.emptyRow(7, 'Nhà thầu chưa điền dòng chào giá nào');
            } else {
                for (var i = 0; i < ct.length; i++) {
                    var r = ct[i];
                    var coGia = Number(r.don_gia) > 0;
                    var tenModel = APP.escape(r.ten_thuong_mai || '');
                    if (r.model) tenModel += '<span class="cell-sub">' + APP.escape(r.model) + '</span>';
                    var hangXx = APP.escape(r.hang_san_xuat || '');
                    if (r.xuat_xu) hangXx += '<span class="cell-sub">' + APP.escape(r.xuat_xu) + '</span>';

                    html += '<tr>' +
                        '<td class="col-id">' + (i + 1) + '</td>' +
                        '<td><span class="cell-main">' + APP.escape(r.ten_hang_hoa) + '</span>' +
                            (r.stt_theo_phan ? '<span class="cell-sub">' + APP.escape(r.stt_theo_phan) + '</span>' : '') + '</td>' +
                        '<td>' + (tenModel || '<span class="text-muted">—</span>') + '</td>' +
                        '<td>' + (hangXx || '<span class="text-muted">—</span>') + '</td>' +
                        '<td class="col-qty">' + Number(r.so_luong || 0).toLocaleString('vi-VN') + '</td>' +
                        '<td class="cell-money">' + (coGia ? money(r.don_gia) : '<span class="text-muted">Không chào</span>') + '</td>' +
                        '<td class="cell-total">' + (coGia ? money(r.thanh_tien) : '—') + '</td>' +
                        '</tr>';
                }
            }
            $('#ctBody').html(html);
            $('#ctModal').addClass('open');
        }
    });
}

function dItem(label, value, cls) {
    var empty = (value === null || typeof value === 'undefined' || String(value).trim() === '' || String(value).trim() === '—');
    return '<div class="detail-item ' + (cls || '') + '">' +
        '<span class="detail-label">' + APP.escape(label) + '</span>' +
        '<span class="detail-value' + (empty ? ' is-empty' : '') + '">' +
        APP.escape(empty ? 'Chưa có' : value) + '</span></div>';
}

/* ============ XÁC NHẬN / TỪ CHỐI ============ */
function xacNhan(id) {
    APP.confirm('Xác nhận ĐÃ NHẬN bản giấy của nhà thầu này? Báo giá sẽ được đưa vào bảng tổng hợp.', function () {
        APP.ajax(AJAX_URL, { action: 'xacNhan', id: id }, {
            success: function (res) { APP.toast(res.message, 'success'); loadData(); }
        });
    }, { yesClass: 'btn-primary', yesText: 'Xác nhận' });
}

function boXacNhan(id) {
    APP.confirm('Bỏ xác nhận bản giấy? Báo giá sẽ bị loại khỏi bảng tổng hợp.', function () {
        APP.ajax(AJAX_URL, { action: 'boXacNhan', id: id }, {
            success: function (res) { APP.toast(res.message, 'success'); loadData(); }
        });
    }, { yesText: 'Bỏ xác nhận' });
}

function openTuChoi(id) {
    $('#tc_id').val(id);
    $('#tc_ly_do').val('');
    $('#tuChoiModal').addClass('open');
    $('#tc_ly_do').trigger('focus');
}

function saveTuChoi() {
    APP.ajax(AJAX_URL, {
        action: 'tuChoi',
        id: $('#tc_id').val(),
        ly_do: $('#tc_ly_do').val()
    }, {
        success: function (res) { APP.toast(res.message, 'success'); closeTuChoi(); loadData(); }
    });
    return false;
}

/* ============ SỬA THÔNG TIN ============ */
function edit(id) {
    APP.ajax(AJAX_URL, { action: 'getById', id: id }, {
        success: function (res) {
            var d = res.data;
            $('#e_id').val(d.id);
            $('#e_ten_cong_ty').val(d.ten_cong_ty || '');
            $('#e_ma_so_thue').val(d.ma_so_thue || '');
            $('#e_email').val(d.email || '');
            $('#e_dien_thoai').val(d.dien_thoai || '');
            $('#e_dia_chi').val(d.dia_chi || '');
            $('#e_hieu_luc_bao_gia').val(d.hieu_luc_bao_gia || 0);
            $('#e_ghi_chu').val(d.ghi_chu || '');
            APP.clearFieldErrors('#editForm');
            $('#editModal').addClass('open');
        }
    });
}

function saveEdit() {
    var data = APP.serializeForm('#editForm');
    data.action = 'update';
    APP.ajax(AJAX_URL, data, {
        success: function (res) { APP.toast(res.message, 'success'); closeEdit(); loadData(); }
    });
    return false;
}

function del(id) {
    APP.confirm('Chuyển báo giá này vào thùng rác?', function () {
        APP.ajax(AJAX_URL, { action: 'trash', id: id }, {
            success: function (res) { APP.toast(res.message, 'success'); loadData(); }
        });
    });
}

function restore(id) {
    APP.confirm('Khôi phục báo giá này?', function () {
        APP.ajax(AJAX_URL, { action: 'restore', id: id }, {
            success: function (res) { APP.toast(res.message, 'success'); loadData(); }
        });
    }, { yesClass: 'btn-primary', yesText: 'Khôi phục' });
}

function delForever(id) {
    APP.confirm('Xóa VĨNH VIỄN báo giá này cùng toàn bộ dòng chào giá? Không thể hoàn tác.', function () {
        APP.ajax(AJAX_URL, { action: 'delete', id: id }, {
            success: function (res) { APP.toast(res.message, 'success'); loadData(); }
        });
    }, { yesText: 'Xóa vĩnh viễn' });
}

function closeCt() { $('#ctModal').removeClass('open'); }
function closeEdit() { $('#editModal').removeClass('open'); }
function closeTuChoi() { $('#tuChoiModal').removeClass('open'); }

function capNhatLinkTongHop() {
    var g = $('#filterGoiThau').val();
    $('#btnTongHop').attr('href', URL_TONG_HOP + (g && g !== '0' ? '?goi_thau_id=' + encodeURIComponent(g) : ''));
}

$('#search').on('keyup', APP.debounce(function () { currentPage = 1; loadData(); }, 350));
$('#filterGoiThau').on('change', function () { currentPage = 1; capNhatLinkTongHop(); loadData(); });
$('#filterTrangThai, #filterDaXoa').on('change', function () { currentPage = 1; loadData(); });
$('#ctModal, #editModal, #tuChoiModal').on('click', function (e) { if (e.target === this) $(this).removeClass('open'); });
$(document).on('keydown', function (e) {
    if (e.key === 'Escape') { closeCt(); closeEdit(); closeTuChoi(); }
});

APP.bindPagination('#paginationWrap', function (p) { currentPage = p; loadData(); });

$(document).ready(function () { capNhatLinkTongHop(); loadData(); });
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
