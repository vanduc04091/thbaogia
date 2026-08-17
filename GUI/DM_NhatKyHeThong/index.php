<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../BUS/DM_NguoiDung_BUS.php';

Helper::requireLogin();
PhanQuyenHelper::requireQuyenView('DM_NhatKyHeThong');

$canDel = PhanQuyenHelper::hasQuyen('DM_NhatKyHeThong', PhanQuyenHelper::QUYEN_XOA);

$userCombo = DM_NguoiDung_BUS::getCombo();

$pageTitle = 'Nhật ký hệ thống';
$activeMenu = 'DM_NhatKyHeThong';
$AJAX = AppConfig::baseUrl('GUI/DM_NhatKyHeThong/ajax_handler.php');
require __DIR__ . '/../layouts/header.php';
?>

<nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="<?= AppConfig::baseUrl('GUI/dashboard/index.php') ?>">Trang chủ</a>
    <span class="sep">›</span> Hệ thống
    <span class="sep">›</span> <span>Nhật ký</span>
</nav>

<div class="card">
    <div class="toolbar">
        <div class="left">
            <span class="search-box" style="max-width:260px">
                <?= IconHelper::svg('search', 16) ?>
                <input type="text" id="search" class="form-control" placeholder="Tìm hành động, IP...">
            </span>
            <select id="filterModule" class="form-select" style="max-width:170px" aria-label="Lọc theo module">
                <option value="">Tất cả module</option>
            </select>
            <select id="filterUser" class="form-select" style="max-width:170px" aria-label="Lọc theo người dùng">
                <option value="0">Tất cả người dùng</option>
                <?php foreach ($userCombo as $u): ?>
                    <option value="<?= (int)$u['id'] ?>"><?= Helper::h($u['tai_khoan']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" id="fromDate" class="form-control" style="max-width:160px" aria-label="Từ ngày">
            <input type="date" id="toDate" class="form-control" style="max-width:160px" aria-label="Đến ngày">
            <button type="button" class="btn btn-secondary btn-sm" onclick="resetFilter()" title="Xóa bộ lọc">
                <?= IconHelper::svg('refresh', 15) ?>
            </button>
        </div>
        <div class="right">
            <?php if ($canDel): ?>
                <button type="button" class="btn btn-outline-danger" onclick="purge()">
                    <?= IconHelper::svg('trash', 16) ?><span class="btn-label">Xóa nhật ký cũ</span>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-wrap" id="tableWrap">
        <table class="table">
            <thead>
                <tr>
                    <th style="width:150px">Thời gian</th>
                    <th>Tài khoản</th>
                    <th>Module</th>
                    <th>Hành động</th>
                    <th>Nội dung</th>
                    <th style="width:130px">IP</th>
                </tr>
            </thead>
            <tbody id="tbody"></tbody>
        </table>
    </div>

    <div class="pagination-wrap" id="paginationWrap"></div>
</div>

<script>
var AJAX_URL = <?= json_encode($AJAX) ?>;
var currentPage = 1, pageSize = <?= (int)AppConfig::DEFAULT_PAGE_SIZE ?>, isLoading = false;
var firstLoad = true;   // lan tai dau tien -> skeleton thay vi spinner

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
        module: $('#filterModule').val(),
        nguoi_dung_id: $('#filterUser').val(),
        from_date: $('#fromDate').val(),
        to_date: $('#toDate').val()
    }, {
        success: function (res) {
            renderTable(res.data || []);
            $('#paginationWrap').html(APP.renderPagination(res.pagination));
        },
        complete: function () { isLoading = false; firstLoad = false; APP.hideLoading('#tableWrap'); }
    });
}

function renderTable(rows) {
    if (!rows.length) { $('#tbody').html(APP.emptyRow(6, 'Không có nhật ký nào khớp bộ lọc')); return; }

    var html = '';
    for (var i = 0; i < rows.length; i++) {
        var r = rows[i];
        var who = r.tai_khoan_hien_thi || r.tai_khoan;
        html += '<tr>' +
            '<td>' + APP.formatDateTime(r.thoi_gian) + '</td>' +
            '<td>' + (who ? APP.escape(who) : '<span class="text-muted">Hệ thống</span>') + '</td>' +
            '<td><span class="badge badge-neutral">' + APP.escape(r.module || '—') + '</span></td>' +
            '<td><span class="cell-main">' + APP.escape(r.hanh_dong || '') + '</span></td>' +
            '<td class="text-muted">' + APP.escape(r.noi_dung || '—') + '</td>' +
            '<td><span class="text-mono">' + APP.escape(r.ip_address || '—') + '</span></td>' +
            '</tr>';
    }
    $('#tbody').html(html);
}

function loadModules() {
    APP.ajax(AJAX_URL, { action: 'getModuleList' }, {
        success: function (res) {
            var list = res.data || [];
            var html = '<option value="">Tất cả module</option>';
            for (var i = 0; i < list.length; i++) {
                html += '<option value="' + APP.escape(list[i]) + '">' + APP.escape(list[i]) + '</option>';
            }
            $('#filterModule').html(html);
        }
    });
}

function purge() {
    APP.confirm('Xóa toàn bộ nhật ký cũ hơn 30 ngày? Thao tác này không thể hoàn tác.', function () {
        APP.ajax(AJAX_URL, { action: 'purge', days: 30 }, {
            success: function (res) {
                APP.toast(res.message, 'success');
                currentPage = 1;
                loadData();
            }
        });
    }, { yesText: 'Xóa' });
}

function resetFilter() {
    $('#search').val('');
    $('#filterModule').val('');
    $('#filterUser').val('0');
    $('#fromDate').val('');
    $('#toDate').val('');
    currentPage = 1;
    loadData();
}

$('#search').on('keyup', APP.debounce(function () { currentPage = 1; loadData(); }, 350));
$('#filterModule, #filterUser, #fromDate, #toDate').on('change', function () { currentPage = 1; loadData(); });

APP.bindPagination('#paginationWrap', function (p) { currentPage = p; loadData(); });

$(document).ready(function () { loadModules(); loadData(); });
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
