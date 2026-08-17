<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../BUS/DM_NhomTaiKhoan_BUS.php';

Helper::requireLogin();
PhanQuyenHelper::requireQuyenView('DM_NguoiDung');

$canAdd  = PhanQuyenHelper::hasQuyen('DM_NguoiDung', PhanQuyenHelper::QUYEN_THEM);
$canEdit = PhanQuyenHelper::hasQuyen('DM_NguoiDung', PhanQuyenHelper::QUYEN_SUA);
$canDel  = PhanQuyenHelper::hasQuyen('DM_NguoiDung', PhanQuyenHelper::QUYEN_XOA);

$nhomCombo = DM_NhomTaiKhoan_BUS::getCombo();

$pageTitle = 'Quản lý người dùng';
$activeMenu = 'DM_NguoiDung';
$AJAX = AppConfig::baseUrl('GUI/DM_NguoiDung/ajax_handler.php');
require __DIR__ . '/../layouts/header.php';
?>

<nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="<?= AppConfig::baseUrl('GUI/dashboard/index.php') ?>">Trang chủ</a>
    <span class="sep">›</span> Hệ thống
    <span class="sep">›</span> <span>Người dùng</span>
</nav>

<div class="card">
    <div class="toolbar">
        <div class="left">
            <span class="search-box" style="max-width:300px">
                <?= IconHelper::svg('search', 16) ?>
                <input type="text" id="search" class="form-control" placeholder="Tìm tài khoản...">
            </span>
            <select id="filterNhom" class="form-select" style="max-width:190px" aria-label="Lọc theo nhóm">
                <option value="0">Tất cả nhóm</option>
                <?php foreach ($nhomCombo as $n): ?>
                    <option value="<?= (int)$n['id'] ?>"><?= Helper::h($n['ten_nhom']) ?></option>
                <?php endforeach; ?>
            </select>
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
                    <th>Tài khoản</th>
                    <th>Nhóm</th>
                    <th>Trạng thái</th>
                    <th>Đăng nhập cuối</th>
                    <th class="col-actions">Thao tác</th>
                </tr>
            </thead>
            <tbody id="tbody"></tbody>
        </table>
    </div>

    <div class="pagination-wrap" id="paginationWrap"></div>
</div>

<!-- Modal thêm/sửa -->
<div class="modal" id="modal">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-header">
            <h3 id="modalTitle">Thêm người dùng</h3>
            <button type="button" class="close" onclick="closeModal()" aria-label="Đóng"><?= IconHelper::svg('x', 20) ?></button>
        </div>
        <form id="form" onsubmit="return save()">
            <div class="modal-body">
                <input type="hidden" id="id" name="id">
                <div class="form-group">
                    <label for="tai_khoan">Tài khoản <span class="req">*</span></label>
                    <input type="text" id="tai_khoan" name="tai_khoan" class="form-control" required autocomplete="off">
                    <div class="form-hint">3-50 ký tự, chỉ gồm chữ, số, dấu chấm hoặc gạch dưới.</div>
                </div>
                <div class="form-group">
                    <label for="mat_khau">Mật khẩu <span class="req" id="passReq">*</span></label>
                    <input type="password" id="mat_khau" name="mat_khau" class="form-control" autocomplete="new-password">
                    <div class="form-hint" id="passHint">Tối thiểu 8 ký tự.</div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="nhom_tai_khoan_id">Nhóm tài khoản <span class="req">*</span></label>
                        <select id="nhom_tai_khoan_id" name="nhom_tai_khoan_id" class="form-select" required>
                            <option value="">-- Chọn nhóm --</option>
                            <?php foreach ($nhomCombo as $n): ?>
                                <option value="<?= (int)$n['id'] ?>"><?= Helper::h($n['ten_nhom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="trang_thai">Trạng thái</label>
                        <select id="trang_thai" name="trang_thai" class="form-select">
                            <option value="1">Hoạt động</option>
                            <option value="0">Khóa</option>
                        </select>
                    </div>
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
    del:  <?= $canDel ? 'true' : 'false' ?>
};

var currentPage = 1, pageSize = <?= (int)AppConfig::DEFAULT_PAGE_SIZE ?>, isLoading = false;
var firstLoad = true;   // lan tai dau tien -> skeleton thay vi spinner

function currentTrash() { return $('#filterDaXoa').val() === '1'; }

function loadData() {
    if (isLoading) return;
    isLoading = true;
    // Lan dau: skeleton khop hinh dang bang; cac lan sau: overlay mo
    if (firstLoad) { $('#tbody').html(APP.skeletonRows(6, 5)); }
    else { APP.showLoading('#tableWrap'); }

    APP.ajax(AJAX_URL, {
        action: 'getPaged',
        page: currentPage,
        pageSize: pageSize,
        search: $('#search').val(),
        nhom_tai_khoan_id: $('#filterNhom').val(),
        da_xoa: $('#filterDaXoa').val()
    }, {
        success: function (res) {
            renderTable(res.data || []);
            $('#paginationWrap').html(APP.renderPagination(res.pagination));
        },
        complete: function () {
            isLoading = false;
            firstLoad = false;
            APP.hideLoading('#tableWrap');
        }
    });
}

function renderTable(rows) {
    if (!rows.length) {
        $('#tbody').html(APP.emptyRow(6, 'Không tìm thấy người dùng nào'));
        return;
    }
    var trash = currentTrash();
    var html = '';

    for (var i = 0; i < rows.length; i++) {
        var r = rows[i];
        var badge = r.trang_thai == 1
            ? '<span class="badge badge-success">Hoạt động</span>'
            : '<span class="badge badge-danger">Khóa</span>';

        var actions = '';
        if (trash) {
            if (CAN.edit) actions += '<button class="btn btn-sm btn-outline-primary" onclick="restore(' + r.id + ')" title="Khôi phục">' + APP.icon('rotate-ccw', 15) + '</button>';
        } else {
            if (CAN.edit) actions += '<button class="btn btn-sm btn-outline-primary" onclick="edit(' + r.id + ')" title="Sửa">' + APP.icon('pencil', 15) + '</button>';
            if (CAN.del)  actions += '<button class="btn btn-sm btn-outline-danger" onclick="del(' + r.id + ')" title="Xóa">' + APP.icon('trash', 15) + '</button>';
        }
        if (!actions) actions = '<span class="text-muted">—</span>';

        html += '<tr>' +
            '<td class="col-id">' + r.id + '</td>' +
            '<td><span class="cell-main">' + APP.escape(r.tai_khoan) + '</span></td>' +
            '<td>' + APP.escape(r.ten_nhom || '—') + '</td>' +
            '<td>' + badge + '</td>' +
            '<td>' + (r.lan_dang_nhap_cuoi ? APP.formatDateTime(r.lan_dang_nhap_cuoi) : '<span class="text-muted">Chưa đăng nhập</span>') + '</td>' +
            '<td class="col-actions"><span class="row-actions">' + actions + '</span></td>' +
            '</tr>';
    }
    $('#tbody').html(html);
}

function openCreate() {
    $('#modalTitle').text('Thêm người dùng');
    $('#form')[0].reset();
    $('#id').val('');
    $('#mat_khau').prop('required', true);
    $('#passReq').show();
    $('#passHint').text('Tối thiểu 8 ký tự.');
    $('#modal').addClass('open');
    $('#tai_khoan').trigger('focus');
}

function edit(id) {
    APP.ajax(AJAX_URL, { action: 'getById', id: id }, {
        success: function (res) {
            var d = res.data;
            $('#modalTitle').text('Sửa người dùng');
            $('#id').val(d.id);
            $('#tai_khoan').val(d.tai_khoan);
            $('#mat_khau').val('').prop('required', false);
            $('#passReq').hide();
            $('#passHint').text('Để trống nếu không đổi mật khẩu.');
            $('#nhom_tai_khoan_id').val(d.nhom_tai_khoan_id);
            $('#trang_thai').val(d.trang_thai);
            $('#modal').addClass('open');
        }
    });
}

function save() {
    var data = APP.serializeForm('#form');
    data.action = data.id ? 'update' : 'insert';
    APP.ajax(AJAX_URL, data, {
        success: function (res) {
            APP.toast(res.message, 'success');
            closeModal();
            loadData();
        }
    });
    return false;
}

function del(id) {
    APP.confirm('Chuyển người dùng này vào thùng rác?', function () {
        APP.ajax(AJAX_URL, { action: 'trash', id: id }, {
            success: function (res) { APP.toast(res.message, 'success'); loadData(); }
        });
    });
}

function restore(id) {
    APP.confirm('Khôi phục người dùng này?', function () {
        APP.ajax(AJAX_URL, { action: 'restore', id: id }, {
            success: function (res) { APP.toast(res.message, 'success'); loadData(); }
        });
    }, { yesClass: 'btn-primary', yesText: 'Khôi phục' });
}

function closeModal() { $('#modal').removeClass('open'); }

$('#search').on('keyup', APP.debounce(function () { currentPage = 1; loadData(); }, 350));
$('#filterNhom, #filterDaXoa').on('change', function () { currentPage = 1; loadData(); });
$('#modal').on('click', function (e) { if (e.target === this) closeModal(); });
$(document).on('keydown', function (e) { if (e.key === 'Escape') closeModal(); });

APP.bindPagination('#paginationWrap', function (p) { currentPage = p; loadData(); });

$(document).ready(loadData);
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
