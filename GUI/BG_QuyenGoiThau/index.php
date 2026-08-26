<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../BUS/BG_QuyenGoiThau_BUS.php';
require_once __DIR__ . '/../../BUS/BG_GoiThau_BUS.php';

Helper::requireLogin();
PhanQuyenHelper::requireQuyenView(BG_QuyenGoiThau_BUS::MODULE_KEY);

$canEdit = PhanQuyenHelper::hasQuyen(BG_QuyenGoiThau_BUS::MODULE_KEY, PhanQuyenHelper::QUYEN_SUA);

$goiThauCombo = BG_GoiThau_BUS::getCombo();
$goiThauId    = (int)Helper::get('goi_thau_id', 0);

$pageTitle  = 'Phân quyền gói thầu';
$activeMenu = 'BG_QuyenGoiThau';
$AJAX = AppConfig::baseUrl('GUI/BG_QuyenGoiThau/ajax_handler.php');
require __DIR__ . '/../layouts/header.php';
?>

<nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="<?= AppConfig::baseUrl('GUI/dashboard/index.php') ?>">Trang chủ</a>
    <span class="sep">›</span> Nghiệp vụ báo giá
    <span class="sep">›</span> <span>Phân quyền gói thầu</span>
</nav>

<div class="callout-cach" style="margin-bottom:16px">
    <?= IconHelper::svg('info', 22) ?>
    <span>
        <strong class="chon-cach">Quy tắc:</strong>
        <span class="cach"><span class="cach-no">1</span>
            <strong>Quản trị viên</strong> và <strong>Quản lý</strong> xem được mọi gói thầu.</span>
        <span class="cach"><span class="cach-no">2</span>
            Người khác chỉ xem gói được <strong>tích chọn</strong> ở đây.</span>
        <span class="cach-chi-tiet">Gói thầu chưa tích ai thì chỉ Quản trị viên và Quản lý nhìn thấy.</span>
    </span>
</div>

<div class="card">
    <div class="toolbar" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:14px 18px">
        <label for="goiThau" style="font-weight:600;font-size:14px">Gói thầu</label>
        <select id="goiThau" class="form-control" style="max-width:520px">
            <option value="">— Chọn gói thầu —</option>
            <?php foreach ($goiThauCombo as $g): ?>
                <option value="<?= (int)$g['id'] ?>" <?= $goiThauId === (int)$g['id'] ? 'selected' : '' ?>>
                    <?= Helper::h($g['so_thong_bao'] . ' — ' . $g['ten_goi_thau']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <span class="tb-spacer" style="flex:1"></span>
        <?php if ($canEdit): ?>
        <button type="button" class="btn btn-primary" id="btnLuu" onclick="luuQuyen()" disabled>
            <?= IconHelper::svg('save', 16) ?>Lưu phân quyền
        </button>
        <?php endif; ?>
    </div>

    <div id="khungDs" style="padding:0 18px 18px">
        <p class="form-hint" id="goiY">Chọn một gói thầu ở trên để phân quyền.</p>

        <div class="table-wrap" id="bangWrap" hidden>
            <table class="table" id="bangNd">
                <thead>
                    <tr>
                        <th style="width:70px">Xem</th>
                        <th>Tài khoản</th>
                        <th>Nhóm</th>
                        <th style="width:150px">Trạng thái</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<script>
var AJAX_URL = <?= json_encode($AJAX) ?>;
var CAN_EDIT = <?= $canEdit ? 'true' : 'false' ?>;
var DS = [];

function loadDs() {
    var id = parseInt($('#goiThau').val(), 10) || 0;
    if (!id) {
        $('#bangWrap').prop('hidden', true);
        $('#goiY').prop('hidden', false).text('Chọn một gói thầu ở trên để phân quyền.');
        $('#btnLuu').prop('disabled', true);
        return;
    }

    APP.showLoading('#khungDs');
    APP.ajax(AJAX_URL, { action: 'getDanhSach', goi_thau_id: id }, {
        success: function (res) {
            APP.hideLoading('#khungDs');
            if (!res || !res.success) return;
            DS = (res.data && res.data.nguoi_dung) || [];
            render();
            $('#goiY').prop('hidden', true);
            $('#bangWrap').prop('hidden', false);
            $('#btnLuu').prop('disabled', !CAN_EDIT);
        },
        error: function () { APP.hideLoading('#khungDs'); }
    });
}

function render() {
    var html = '';
    for (var i = 0; i < DS.length; i++) {
        var r = DS[i];
        // Nhóm xem-tất-cả: khóa ô tích, tick hay không cũng không đổi được gì
        var dis = (r.xem_tat_ca || !CAN_EDIT) ? ' disabled' : '';
        var ghiChu = r.xem_tat_ca
            ? '<span class="badge badge-success">Xem mọi gói</span>'
            : (Number(r.trang_thai) === 1
                ? '<span class="badge badge-neutral">Hoạt động</span>'
                : '<span class="badge badge-warning">Đã khóa</span>');

        html += '<tr>' +
            '<td><input type="checkbox" class="js-tick" data-id="' + r.id + '"' +
                (r.duoc_xem ? ' checked' : '') + dis + '></td>' +
            '<td><span class="cell-main">' + APP.escape(r.tai_khoan) + '</span></td>' +
            '<td>' + APP.escape(r.ten_nhom) + '</td>' +
            '<td>' + ghiChu + '</td>' +
            '</tr>';
    }
    if (!DS.length) html = APP.emptyRow(4, 'Không có người dùng nào');
    $('#bangNd tbody').html(html);
}

function luuQuyen() {
    var id = parseInt($('#goiThau').val(), 10) || 0;
    if (!id) return;

    // Chỉ gửi người được tích VÀ không thuộc nhóm xem-tất-cả
    var ids = [];
    $('#bangNd .js-tick:checked').each(function () {
        if (!this.disabled) ids.push(parseInt($(this).data('id'), 10));
    });

    APP.showLoading('#khungDs');
    APP.ajax(AJAX_URL, {
        action: 'luu',
        goi_thau_id: id,
        nguoi_dung_ids: JSON.stringify(ids)
    }, {
        success: function (res) {
            APP.hideLoading('#khungDs');
            if (res && res.success) {
                APP.toast(res.message, 'success');
                loadDs();
            } else {
                APP.toast((res && res.message) || 'Lưu thất bại', 'error');
            }
        },
        error: function () {
            APP.hideLoading('#khungDs');
            APP.toast('Không lưu được, hãy thử lại', 'error');
        }
    });
}

$('#goiThau').on('change', loadDs);
$(document).ready(function () { if ($('#goiThau').val()) loadDs(); });
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
