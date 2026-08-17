<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../BUS/DM_NhomTaiKhoan_BUS.php';

Helper::requireLogin();
PhanQuyenHelper::requireQuyenView('DM_NhomTaiKhoan');

$canAdd  = PhanQuyenHelper::hasQuyen('DM_NhomTaiKhoan', PhanQuyenHelper::QUYEN_THEM);
$canEdit = PhanQuyenHelper::hasQuyen('DM_NhomTaiKhoan', PhanQuyenHelper::QUYEN_SUA);
$canDel  = PhanQuyenHelper::hasQuyen('DM_NhomTaiKhoan', PhanQuyenHelper::QUYEN_XOA);

// Chỉ hiện lối tắt sang Phân quyền nếu user có quyền xem form đó
$canXemPhanQuyen = PhanQuyenHelper::hasQuyen('DM_PhanQuyen', PhanQuyenHelper::QUYEN_XEM);
$urlPhanQuyen    = AppConfig::baseUrl('GUI/DM_PhanQuyen/index.php');

$pageTitle = 'Quản lý nhóm tài khoản';
$activeMenu = 'DM_NhomTaiKhoan';
$AJAX = AppConfig::baseUrl('GUI/DM_NhomTaiKhoan/ajax_handler.php');
require __DIR__ . '/../layouts/header.php';
?>

<nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="<?= AppConfig::baseUrl('GUI/dashboard/index.php') ?>">Trang chủ</a>
    <span class="sep">›</span> Hệ thống
    <span class="sep">›</span> <span>Nhóm tài khoản</span>
</nav>

<div class="card">
    <div class="toolbar">
        <div class="left">
            <span class="search-box" style="max-width:300px">
                <?= IconHelper::svg('search', 16) ?>
                <input type="text" id="search" class="form-control" placeholder="Tìm mã hoặc tên nhóm...">
            </span>
            <select id="filterDaXoa" class="form-select" style="max-width:170px" aria-label="Lọc trạng thái">
                <option value="0">Đang hoạt động</option>
                <option value="1">Thùng rác</option>
            </select>
        </div>
        <div class="right">
            <?php if ($canAdd): ?>
                <button type="button" class="btn btn-primary" onclick="openCreate()">
                    <?= IconHelper::svg('plus', 16) ?><span class="btn-label">Thêm mới</span>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-wrap" id="tableWrap">
        <table class="table">
            <thead>
                <tr>
                    <th class="col-id">ID</th>
                    <th>Mã nhóm</th>
                    <th>Tên nhóm</th>
                    <th class="col-desc">Mô tả</th>
                    <th>Loại</th>
                    <th>Trạng thái</th>
                    <th class="col-actions">Thao tác</th>
                </tr>
            </thead>
            <tbody id="tbody"></tbody>
        </table>
    </div>

    <div class="pagination-wrap" id="paginationWrap"></div>
</div>

<div class="modal" id="modal">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-header">
            <h3 id="modalTitle">Thêm nhóm tài khoản</h3>
            <button type="button" class="close" onclick="closeModal()" aria-label="Đóng"><?= IconHelper::svg('x', 20) ?></button>
        </div>
        <form id="form" onsubmit="return save()">
            <div class="modal-body">
                <input type="hidden" id="id" name="id">
                <div class="form-row">
                    <div class="form-group">
                        <label for="ma_nhom">Mã nhóm <span class="req">*</span></label>
                        <input type="text" id="ma_nhom" name="ma_nhom" class="form-control" required maxlength="20" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="ten_nhom">Tên nhóm <span class="req">*</span></label>
                        <input type="text" id="ten_nhom" name="ten_nhom" class="form-control" required maxlength="100">
                    </div>
                </div>
                <div class="form-group">
                    <label for="mo_ta">Mô tả</label>
                    <textarea id="mo_ta" name="mo_ta" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="trang_thai">Trạng thái</label>
                    <select id="trang_thai" name="trang_thai" class="form-select">
                        <option value="1">Hoạt động</option>
                        <option value="0">Ngừng</option>
                    </select>
                </div>
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="checkbox" id="la_admin" name="la_admin" value="1">
                        <span>Nhóm quản trị (toàn quyền)</span>
                    </label>
                    <div class="form-hint">Nhóm quản trị bỏ qua ma trận phân quyền và có mọi quyền trên hệ thống.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Hủy</button>
                <button type="submit" class="btn btn-primary"><?= IconHelper::svg('save', 16) ?>Lưu</button>
            </div>
        </form>
    </div>
</div>

<script>
var AJAX_URL = <?= json_encode($AJAX) ?>;
var CAN = {
    edit: <?= $canEdit ? 'true' : 'false' ?>,
    del:  <?= $canDel ? 'true' : 'false' ?>,
    phanQuyen: <?= $canXemPhanQuyen ? 'true' : 'false' ?>
};
var URL_PHAN_QUYEN = <?= json_encode($urlPhanQuyen) ?>;
var currentPage = 1, pageSize = <?= (int)AppConfig::DEFAULT_PAGE_SIZE ?>, isLoading = false;
var firstLoad = true;   // lan tai dau tien -> skeleton thay vi spinner

function currentTrash() { return $('#filterDaXoa').val() === '1'; }

function phanQuyenUrl(id) {
    return URL_PHAN_QUYEN + '?nhom_id=' + encodeURIComponent(id);
}

function loadData() {
    if (isLoading) return;
    isLoading = true;
    // Lan dau: skeleton khop hinh dang bang; cac lan sau: overlay mo
    if (firstLoad) { $('#tbody').html(APP.skeletonRows(7, 5)); }
    else { APP.showLoading('#tableWrap'); }
    APP.ajax(AJAX_URL, {
        action: 'getPaged',
        page: currentPage,
        pageSize: pageSize,
        search: $('#search').val(),
        da_xoa: $('#filterDaXoa').val()
    }, {
        success: function (res) {
            renderTable(res.data || []);
            $('#paginationWrap').html(APP.renderPagination(res.pagination));
        },
        complete: function () { isLoading = false; firstLoad = false; APP.hideLoading('#tableWrap'); }
    });
}

function renderTable(rows) {
    if (!rows.length) { $('#tbody').html(APP.emptyRow(7, 'Chưa có nhóm tài khoản nào')); return; }
    var trash = currentTrash(), html = '';

    for (var i = 0; i < rows.length; i++) {
        var r = rows[i];
        var loai = r.la_admin == 1
            ? '<span class="badge badge-warning">Quản trị</span>'
            : '<span class="badge badge-neutral">Thường</span>';
        var tt = r.trang_thai == 1
            ? '<span class="badge badge-success">Hoạt động</span>'
            : '<span class="badge badge-danger">Ngừng</span>';

        var actions = '';
        if (trash) {
            if (CAN.edit) actions += '<button class="btn btn-sm btn-outline-primary" onclick="restore(' + r.id + ')" title="Khôi phục">' + APP.icon('rotate-ccw', 15) + '</button>';
        } else {
            // Nhóm admin bỏ qua ma trận quyền → không có gì để phân
            if (CAN.phanQuyen && r.la_admin != 1) {
                actions += '<a class="btn btn-sm btn-outline-primary" href="' + phanQuyenUrl(r.id) +
                           '" title="Phân quyền nhóm này">' + APP.icon('shield-check', 15) + '</a>';
            }
            if (CAN.edit) actions += '<button class="btn btn-sm btn-outline-primary" onclick="edit(' + r.id + ')" title="Sửa">' + APP.icon('pencil', 15) + '</button>';
            if (CAN.del && r.id != 1) actions += '<button class="btn btn-sm btn-outline-danger" onclick="del(' + r.id + ')" title="Xóa">' + APP.icon('trash', 15) + '</button>';
        }
        if (!actions) actions = '<span class="text-muted">—</span>';

        // Tên nhóm bấm được → mở thẳng ma trận phân quyền của nhóm đó
        var tenNhom = (CAN.phanQuyen && !trash && r.la_admin != 1)
            ? '<a class="cell-link" href="' + phanQuyenUrl(r.id) + '" title="Xem phân quyền của nhóm này">' +
              APP.escape(r.ten_nhom) + '</a>'
            : '<span class="cell-main">' + APP.escape(r.ten_nhom) + '</span>';

        html += '<tr>' +
            '<td class="col-id">' + r.id + '</td>' +
            '<td><span class="text-mono">' + APP.escape(r.ma_nhom) + '</span></td>' +
            '<td>' + tenNhom + '</td>' +
            '<td class="text-muted col-desc" title="' + APP.escape(r.mo_ta || '') + '">' + APP.escape(r.mo_ta || '—') + '</td>' +
            '<td>' + loai + '</td>' +
            '<td>' + tt + '</td>' +
            '<td class="col-actions"><span class="row-actions">' + actions + '</span></td>' +
            '</tr>';
    }
    $('#tbody').html(html);
}

function openCreate() {
    $('#modalTitle').text('Thêm nhóm tài khoản');
    $('#form')[0].reset();
    $('#id').val('');
    $('#la_admin').prop('checked', false);
    $('#modal').addClass('open');
    $('#ma_nhom').trigger('focus');
}

function edit(id) {
    APP.ajax(AJAX_URL, { action: 'getById', id: id }, {
        success: function (res) {
            var d = res.data;
            $('#modalTitle').text('Sửa nhóm tài khoản');
            $('#id').val(d.id);
            $('#ma_nhom').val(d.ma_nhom);
            $('#ten_nhom').val(d.ten_nhom);
            $('#mo_ta').val(d.mo_ta || '');
            $('#trang_thai').val(d.trang_thai);
            $('#la_admin').prop('checked', d.la_admin == 1);
            $('#modal').addClass('open');
        }
    });
}

function save() {
    var data = APP.serializeForm('#form');
    data.action = data.id ? 'update' : 'insert';
    APP.ajax(AJAX_URL, data, {
        success: function (res) { APP.toast(res.message, 'success'); closeModal(); loadData(); }
    });
    return false;
}

function del(id) {
    APP.confirm('Chuyển nhóm này vào thùng rác?', function () {
        APP.ajax(AJAX_URL, { action: 'trash', id: id }, {
            success: function (res) { APP.toast(res.message, 'success'); loadData(); }
        });
    });
}

function restore(id) {
    APP.confirm('Khôi phục nhóm này?', function () {
        APP.ajax(AJAX_URL, { action: 'restore', id: id }, {
            success: function (res) { APP.toast(res.message, 'success'); loadData(); }
        });
    }, { yesClass: 'btn-primary', yesText: 'Khôi phục' });
}

function closeModal() { $('#modal').removeClass('open'); }

$('#search').on('keyup', APP.debounce(function () { currentPage = 1; loadData(); }, 350));
$('#filterDaXoa').on('change', function () { currentPage = 1; loadData(); });
$('#modal').on('click', function (e) { if (e.target === this) closeModal(); });
$(document).on('keydown', function (e) { if (e.key === 'Escape') closeModal(); });

APP.bindPagination('#paginationWrap', function (p) { currentPage = p; loadData(); });

$(document).ready(loadData);
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
