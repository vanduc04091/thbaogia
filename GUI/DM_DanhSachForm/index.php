<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../BUS/DM_DanhSachForm_BUS.php';

Helper::requireLogin();
PhanQuyenHelper::requireQuyenView('DM_DanhSachForm');

$canAdd  = PhanQuyenHelper::hasQuyen('DM_DanhSachForm', PhanQuyenHelper::QUYEN_THEM);
$canEdit = PhanQuyenHelper::hasQuyen('DM_DanhSachForm', PhanQuyenHelper::QUYEN_SUA);
$canDel  = PhanQuyenHelper::hasQuyen('DM_DanhSachForm', PhanQuyenHelper::QUYEN_XOA);

$formCombo = DM_DanhSachForm_BUS::getAllActive();

$pageTitle = 'Danh sách form';
$activeMenu = 'DM_DanhSachForm';
$AJAX = AppConfig::baseUrl('GUI/DM_DanhSachForm/ajax_handler.php');
require __DIR__ . '/../layouts/header.php';
?>

<nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="<?= AppConfig::baseUrl('GUI/dashboard/index.php') ?>">Trang chủ</a>
    <span class="sep">›</span> Hệ thống
    <span class="sep">›</span> <span>Danh sách form</span>
</nav>

<div class="card">
    <div class="toolbar">
        <div class="left">
            <span class="search-box" style="max-width:320px">
                <?= IconHelper::svg('search', 16) ?>
                <input type="text" id="search" class="form-control" placeholder="Tìm tên form hoặc module...">
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
                    <th>Tên form</th>
                    <th>Module</th>
                    <th>Form cha</th>
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
            <h3 id="modalTitle">Thêm form</h3>
            <button type="button" class="close" onclick="closeModal()" aria-label="Đóng"><?= IconHelper::svg('x', 20) ?></button>
        </div>
        <form id="form" onsubmit="return save()">
            <div class="modal-body">
                <input type="hidden" id="id" name="id">
                <div class="form-group">
                    <label for="ten_form">Tên form <span class="req">*</span></label>
                    <input type="text" id="ten_form" name="ten_form" class="form-control" required maxlength="200">
                </div>
                <div class="form-group">
                    <label for="modules_tuong_ung">Module tương ứng <span class="req">*</span></label>
                    <input type="text" id="modules_tuong_ung" name="modules_tuong_ung" class="form-control" required maxlength="100" autocomplete="off">
                    <div class="form-hint">Trùng với tên thư mục trong <code>GUI/</code>, ví dụ <code>DM_NguoiDung</code>.</div>
                </div>
                <div class="form-group">
                    <label for="form_cha_id">Form cha</label>
                    <select id="form_cha_id" name="form_cha_id" class="form-select">
                        <option value="0">-- Không có (form gốc) --</option>
                        <?php foreach ($formCombo as $f): ?>
                            <option value="<?= (int)$f['id'] ?>"><?= Helper::h($f['ten_form']) ?></option>
                        <?php endforeach; ?>
                    </select>
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
var CAN = { edit: <?= $canEdit ? 'true' : 'false' ?>, del: <?= $canDel ? 'true' : 'false' ?> };
var FORM_NAMES = <?= json_encode(array_column($formCombo, 'ten_form', 'id'), JSON_UNESCAPED_UNICODE) ?>;
var currentPage = 1, pageSize = <?= (int)AppConfig::DEFAULT_PAGE_SIZE ?>, isLoading = false;
var firstLoad = true;   // lan tai dau tien -> skeleton thay vi spinner

function currentTrash() { return $('#filterDaXoa').val() === '1'; }

function loadData() {
    if (isLoading) return;
    isLoading = true;
    // Lan dau: skeleton khop hinh dang bang; cac lan sau: overlay mo
    if (firstLoad) { $('#tbody').html(APP.skeletonRows(5, 5)); }
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
    if (!rows.length) { $('#tbody').html(APP.emptyRow(5, 'Chưa có form nào')); return; }
    var trash = currentTrash(), html = '';

    for (var i = 0; i < rows.length; i++) {
        var r = rows[i];
        var cha = (r.form_cha_id && FORM_NAMES[r.form_cha_id]) ? APP.escape(FORM_NAMES[r.form_cha_id]) : '<span class="text-muted">—</span>';

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
            '<td><span class="cell-main">' + APP.escape(r.ten_form) + '</span></td>' +
            '<td><span class="text-mono">' + APP.escape(r.modules_tuong_ung) + '</span></td>' +
            '<td>' + cha + '</td>' +
            '<td class="col-actions"><span class="row-actions">' + actions + '</span></td>' +
            '</tr>';
    }
    $('#tbody').html(html);
}

function openCreate() {
    $('#modalTitle').text('Thêm form');
    $('#form')[0].reset();
    $('#id').val('');
    $('#form_cha_id').val('0');
    $('#modal').addClass('open');
    $('#ten_form').trigger('focus');
}

function edit(id) {
    APP.ajax(AJAX_URL, { action: 'getById', id: id }, {
        success: function (res) {
            var d = res.data;
            $('#modalTitle').text('Sửa form');
            $('#id').val(d.id);
            $('#ten_form').val(d.ten_form);
            $('#modules_tuong_ung').val(d.modules_tuong_ung);
            $('#form_cha_id').val(d.form_cha_id || '0');
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
    APP.confirm('Chuyển form này vào thùng rác? Phân quyền liên quan vẫn được giữ.', function () {
        APP.ajax(AJAX_URL, { action: 'trash', id: id }, {
            success: function (res) { APP.toast(res.message, 'success'); loadData(); }
        });
    });
}

function restore(id) {
    APP.confirm('Khôi phục form này?', function () {
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
