<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../BUS/DM_NhomTaiKhoan_BUS.php';
require_once __DIR__ . '/../../BUS/DM_DanhSachForm_BUS.php';

Helper::requireLogin();
PhanQuyenHelper::requireQuyenView('DM_PhanQuyen');

$canEdit = PhanQuyenHelper::hasQuyen('DM_PhanQuyen', PhanQuyenHelper::QUYEN_SUA);

$nhomCombo = DM_NhomTaiKhoan_BUS::getCombo();
$formList  = DM_DanhSachForm_BUS::getAllActive();

// Cho phép mở thẳng ma trận của 1 nhóm qua ?nhom_id=... (từ trang Nhóm tài khoản).
// Chỉ nhận id thật sự nằm trong combo — tránh nhận id tùy ý từ URL.
$nhomIdChon = (int)Helper::get('nhom_id', 0);
if ($nhomIdChon > 0 && !in_array($nhomIdChon, array_map('intval', array_column($nhomCombo, 'id')), true)) {
    $nhomIdChon = 0;
}

$pageTitle = 'Quản lý phân quyền';
$activeMenu = 'DM_PhanQuyen';
$AJAX = AppConfig::baseUrl('GUI/DM_PhanQuyen/ajax_handler.php');
require __DIR__ . '/../layouts/header.php';
?>

<nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="<?= AppConfig::baseUrl('GUI/dashboard/index.php') ?>">Trang chủ</a>
    <span class="sep">›</span> Hệ thống
    <?php if ($nhomIdChon > 0 && PhanQuyenHelper::hasQuyen('DM_NhomTaiKhoan', PhanQuyenHelper::QUYEN_XEM)): ?>
        <span class="sep">›</span>
        <a href="<?= AppConfig::baseUrl('GUI/DM_NhomTaiKhoan/index.php') ?>">Nhóm tài khoản</a>
    <?php endif; ?>
    <span class="sep">›</span> <span>Phân quyền</span>
</nav>

<div class="card">
    <div class="toolbar">
        <div class="left">
            <select id="nhomSelect" class="form-select" style="min-width:260px" aria-label="Chọn nhóm tài khoản">
                <option value="">-- Chọn nhóm tài khoản --</option>
                <?php foreach ($nhomCombo as $n): ?>
                    <option value="<?= (int)$n['id'] ?>" <?= $nhomIdChon === (int)$n['id'] ? 'selected' : '' ?>>
                        <?= Helper::h($n['ten_nhom']) ?> (<?= Helper::h($n['ma_nhom']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="right">
            <?php if ($canEdit): ?>
                <button type="button" class="btn btn-secondary" id="btnGrantAll" onclick="grantAll()" disabled>
                    <?= IconHelper::svg('shield-check', 16) ?><span class="btn-label">Cấp toàn quyền</span>
                </button>
                <button type="button" class="btn btn-primary" id="btnSave" onclick="saveMatrix()" disabled>
                    <?= IconHelper::svg('save', 16) ?><span class="btn-label">Lưu thay đổi</span>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div id="hintBar" class="card-notice">
        <?= IconHelper::svg('info', 17) ?>
        <span>Chọn một nhóm tài khoản để xem và chỉnh sửa ma trận phân quyền.</span>
    </div>

    <div class="table-wrap" id="matrixWrap">
        <table class="table" id="matrixTable">
            <thead>
                <tr>
                    <th>Form</th>
                    <th class="check-cell">Xem</th>
                    <th class="check-cell">Thêm</th>
                    <th class="check-cell">Sửa</th>
                    <th class="check-cell">Xóa</th>
                </tr>
            </thead>
            <tbody id="tbody">
                <?php if (!$formList): ?>
                    <tr class="empty-row"><td colspan="5">
                        <div class="empty-state"><?= IconHelper::svg('inbox', 32) ?><p>Chưa khai báo form nào</p></div>
                    </td></tr>
                <?php else: foreach ($formList as $f): ?>
                    <tr data-form-id="<?= (int)$f['id'] ?>">
                        <td>
                            <span class="cell-with-icon">
                                <span class="cell-icon"><?= IconHelper::moduleIcon($f['modules_tuong_ung'], 17) ?></span>
                                <span>
                                    <span class="cell-main"><?= Helper::h($f['ten_form']) ?></span>
                                    <span class="cell-sub text-mono"><?= Helper::h($f['modules_tuong_ung']) ?></span>
                                </span>
                            </span>
                        </td>
                        <td class="check-cell"><input type="checkbox" class="perm-check" data-perm="1" disabled aria-label="Quyền xem"></td>
                        <td class="check-cell"><input type="checkbox" class="perm-check" data-perm="2" disabled aria-label="Quyền thêm"></td>
                        <td class="check-cell"><input type="checkbox" class="perm-check" data-perm="4" disabled aria-label="Quyền sửa"></td>
                        <td class="check-cell"><input type="checkbox" class="perm-check" data-perm="8" disabled aria-label="Quyền xóa"></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
var AJAX_URL = <?= json_encode($AJAX) ?>;
var CAN_EDIT = <?= $canEdit ? 'true' : 'false' ?>;
var BIT = { XEM: 1, THEM: 2, SUA: 4, XOA: 8 };
var currentNhomId = null;

function setEditable(on) {
    // Chỉ mở khóa checkbox khi user có quyền sửa VÀ đã chọn nhóm
    $('#tbody .perm-check').prop('disabled', !(on && CAN_EDIT));
    $('#btnSave, #btnGrantAll').prop('disabled', !(on && CAN_EDIT));
}

function loadMatrix(nhomId) {
    currentNhomId = nhomId || null;

    if (!currentNhomId) {
        $('#tbody .perm-check').prop('checked', false);
        setEditable(false);
        $('#hintBar').show().find('span').text('Chọn một nhóm tài khoản để xem và chỉnh sửa ma trận phân quyền.');
        return;
    }

    APP.showLoading('#matrixWrap');
    APP.ajax(AJAX_URL, { action: 'getMatrix', nhom_tai_khoan_id: currentNhomId }, {
        success: function (res) {
            var matrix = res.data || {};
            $('#tbody tr[data-form-id]').each(function () {
                var $tr = $(this);
                var mask = parseInt(matrix[$tr.data('form-id')] || 0, 10);
                $tr.find('.perm-check').each(function () {
                    $(this).prop('checked', (mask & parseInt($(this).data('perm'), 10)) !== 0);
                });
            });
            setEditable(true);
            if (CAN_EDIT) {
                $('#hintBar').hide();
            } else {
                $('#hintBar').show().find('span').text('Bạn chỉ có quyền xem ma trận phân quyền.');
            }
        },
        complete: function () { APP.hideLoading('#matrixWrap'); }
    });
}

function saveMatrix() {
    if (!currentNhomId) { APP.toast('Vui lòng chọn nhóm trước', 'warning'); return; }

    var permissions = {};
    $('#tbody tr[data-form-id]').each(function () {
        var $tr = $(this), mask = 0;
        $tr.find('.perm-check:checked').each(function () {
            mask |= parseInt($(this).data('perm'), 10);
        });
        permissions[$tr.data('form-id')] = mask;
    });

    APP.confirm('Lưu thay đổi phân quyền cho nhóm này?', function () {
        APP.ajax(AJAX_URL, {
            action: 'saveMatrix',
            nhom_tai_khoan_id: currentNhomId,
            permissions: permissions
        }, {
            success: function (res) {
                APP.toast(res.message, 'success');
                loadMatrix(currentNhomId);
            }
        });
    }, { yesClass: 'btn-primary', yesText: 'Lưu' });
}

function grantAll() {
    if (!currentNhomId) { APP.toast('Vui lòng chọn nhóm trước', 'warning'); return; }
    APP.confirm('Cấp toàn bộ quyền trên mọi form cho nhóm này?', function () {
        APP.ajax(AJAX_URL, { action: 'grantAll', nhom_tai_khoan_id: currentNhomId }, {
            success: function (res) {
                APP.toast(res.message, 'success');
                loadMatrix(currentNhomId);
            }
        });
    }, { yesClass: 'btn-primary', yesText: 'Cấp quyền' });
}

// Tick quyền thêm/sửa/xóa thì tự bật quyền xem (khớp luật ở BUS)
$('#tbody').on('change', '.perm-check', function () {
    var $tr = $(this).closest('tr');
    var perm = parseInt($(this).data('perm'), 10);
    if (perm !== BIT.XEM && $(this).is(':checked')) {
        $tr.find('.perm-check[data-perm="1"]').prop('checked', true);
    }
    if (perm === BIT.XEM && !$(this).is(':checked')) {
        $tr.find('.perm-check').not('[data-perm="1"]').prop('checked', false);
    }
});

$('#nhomSelect').on('change', function () {
    var id = $(this).val();
    loadMatrix(id);
    // Giữ URL khớp nhóm đang xem để F5 / chia sẻ link không mất lựa chọn
    if (window.history && history.replaceState) {
        history.replaceState(null, '', id ? ('?nhom_id=' + encodeURIComponent(id)) : location.pathname);
    }
});

// Mở sẵn ma trận khi vào từ trang Nhóm tài khoản (?nhom_id=...)
$(document).ready(function () {
    var preset = $('#nhomSelect').val();
    if (preset) loadMatrix(preset);
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
