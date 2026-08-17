<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../BUS/BG_HangHoa_BUS.php';
require_once __DIR__ . '/../../BUS/BG_GoiThau_BUS.php';

Helper::requireLogin();
PhanQuyenHelper::requireQuyenView('BG_HangHoa');

$canAdd  = PhanQuyenHelper::hasQuyen('BG_HangHoa', PhanQuyenHelper::QUYEN_THEM);
$canEdit = PhanQuyenHelper::hasQuyen('BG_HangHoa', PhanQuyenHelper::QUYEN_SUA);
$canDel  = PhanQuyenHelper::hasQuyen('BG_HangHoa', PhanQuyenHelper::QUYEN_XOA);

$goiThauCombo = BG_GoiThau_BUS::getCombo();
$goiThauId = (int)Helper::get('goi_thau_id', 0);

// Không truyền gói thầu → chọn sẵn gói mới nhất để trang không rỗng vô nghĩa
if ($goiThauId === 0 && !empty($goiThauCombo)) {
    $goiThauId = (int)$goiThauCombo[0]['id'];
}
$goiThau = $goiThauId > 0 ? BG_GoiThau_BUS::getById($goiThauId) : null;

$pageTitle  = 'Hàng hóa gói thầu';
$activeMenu = 'BG_HangHoa';
$AJAX = AppConfig::baseUrl('GUI/BG_HangHoa/ajax_handler.php');
require __DIR__ . '/../layouts/header.php';
?>

<nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="<?= AppConfig::baseUrl('GUI/dashboard/index.php') ?>">Trang chủ</a>
    <span class="sep">›</span> <a href="<?= AppConfig::baseUrl('GUI/BG_GoiThau/index.php') ?>">Gói thầu</a>
    <span class="sep">›</span> <span>Hàng hóa gói thầu</span>
</nav>

<?php if (empty($goiThauCombo)): ?>
    <div class="card">
        <div class="empty-state">
            <?= IconHelper::svg('clipboard-list', 40) ?>
            <h3>Chưa có gói thầu nào</h3>
            <p>Hàng hóa phải thuộc về một gói thầu. Hãy tạo thông báo mời chào giá trước.</p>
            <a class="btn btn-primary" href="<?= AppConfig::baseUrl('GUI/BG_GoiThau/index.php') ?>">
                <?= IconHelper::svg('plus', 16) ?>Tạo gói thầu
            </a>
        </div>
    </div>
<?php else: ?>

    <?php if ($goiThau):
        $quaHan = !empty($goiThau->han_cuoi) && $goiThau->han_cuoi < date('Y-m-d');
        $ctxClass = (int)$goiThau->trang_thai === BG_GoiThau_PUBLIC::TT_DANG_MO
            ? ($quaHan ? 'is-warning' : '')
            : 'is-muted';
    ?>
        <div class="context-bar <?= $ctxClass ?>">
            <span class="ctx-item">
                <?= IconHelper::svg('clipboard-list', 16) ?>
                <span class="ctx-label">Gói thầu</span>
                <span class="ctx-value"><?= Helper::h($goiThau->so_thong_bao) ?></span>
            </span>
            <span class="ctx-item">
                <?= IconHelper::svg('package', 16) ?>
                <span class="ctx-label">Hàng hóa</span>
                <span class="ctx-value"><?= (int)$goiThau->so_hang_hoa ?> mục</span>
            </span>
            <span class="ctx-item">
                <?= IconHelper::svg('calendar', 16) ?>
                <span class="ctx-label">Hạn cuối</span>
                <span class="ctx-value">
                    <?= $goiThau->han_cuoi ? Helper::h(Helper::formatDate($goiThau->han_cuoi)) : 'Không đặt' ?>
                    <?= $quaHan ? ' (đã quá hạn)' : '' ?>
                </span>
            </span>
            <span class="ctx-item">
                <?= IconHelper::svg('info', 16) ?>
                <span class="ctx-label">Trạng thái</span>
                <span class="ctx-value"><?= Helper::h(BG_GoiThau_PUBLIC::tenTrangThai((int)$goiThau->trang_thai)) ?></span>
            </span>
            <span class="ctx-spacer"></span>
            <?php if ((int)$goiThau->so_bao_gia > 0): ?>
                <span class="ctx-item">
                    <?= IconHelper::svg('alert-triangle', 16) ?>
                    <span>Đã có <?= (int)$goiThau->so_bao_gia ?> báo giá — không thể ghi đè danh mục</span>
                </span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="toolbar">
            <div class="left">
                <select id="filterGoiThau" class="form-select" style="max-width:330px" aria-label="Chọn gói thầu">
                    <?php foreach ($goiThauCombo as $g): ?>
                        <option value="<?= (int)$g['id'] ?>" <?= (int)$g['id'] === $goiThauId ? 'selected' : '' ?>>
                            <?= Helper::h($g['so_thong_bao'] . ' — ' . mb_substr($g['ten_goi_thau'], 0, 55)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="search-box" style="max-width:290px">
                    <?= IconHelper::svg('search', 16) ?>
                    <input type="text" id="search" class="form-control" placeholder="Tìm tên hàng hóa, thông số...">
                </span>
                <select id="filterDaXoa" class="form-select" style="max-width:160px" aria-label="Lọc thùng rác">
                    <option value="0">Đang hoạt động</option>
                    <option value="1">Thùng rác</option>
                </select>
            </div>
            <div class="right">
                <a class="btn btn-outline-secondary" id="btnTaiMau" href="#">
                    <?= IconHelper::svg('download', 16) ?><span class="btn-label">Tải file mẫu</span>
                </a>
                <?php if ($canAdd): ?>
                    <button type="button" class="btn btn-outline-primary" onclick="openImport()">
                        <?= IconHelper::svg('upload', 16) ?><span class="btn-label">Import Excel</span>
                    </button>
                    <button type="button" class="btn btn-primary" onclick="openCreate()">
                        <?= IconHelper::svg('plus', 16) ?><span class="btn-label">Thêm hàng hóa</span>
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="table-wrap" id="tableWrap">
            <table class="table">
                <thead>
                    <tr>
                        <th class="col-id">STT</th>
                        <th>Phần</th>
                        <th>Tên hàng hóa</th>
                        <th>Thông số kỹ thuật</th>
                        <th>ĐVT</th>
                        <th class="col-qty">Số lượng</th>
                        <th class="col-actions">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="tbody"></tbody>
            </table>
        </div>

        <div class="pagination-wrap" id="paginationWrap"></div>
    </div>

    <!-- ============ Modal thêm / sửa ============ -->
    <div class="modal" id="modal">
        <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="modalTitle" style="max-width:820px">
            <div class="modal-header">
                <h3 id="modalTitle">Thêm hàng hóa</h3>
                <button type="button" class="close" onclick="closeModal()" aria-label="Đóng"><?= IconHelper::svg('x', 20) ?></button>
            </div>
            <form id="form" onsubmit="return save()">
                <div class="modal-body">
                    <input type="hidden" id="id" name="id">
                    <input type="hidden" id="goi_thau_id" name="goi_thau_id" value="<?= $goiThauId ?>">
                    <input type="hidden" id="thu_tu" name="thu_tu" value="0">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="ten_phan">Tên phần</label>
                            <input type="text" id="ten_phan" name="ten_phan" class="form-control"
                                   maxlength="200" placeholder="VD: Phần 1">
                        </div>
                        <div class="form-group">
                            <label for="stt_theo_phan">STT theo phần</label>
                            <input type="text" id="stt_theo_phan" name="stt_theo_phan" class="form-control"
                                   maxlength="50" placeholder="VD: P1.1">
                        </div>
                        <div class="form-group">
                            <label for="stt_thong_bao">STT thông báo</label>
                            <input type="text" id="stt_thong_bao" name="stt_thong_bao" class="form-control"
                                   maxlength="50" placeholder="VD: 1">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="ten_hang_hoa">Tên hàng hóa <span class="req">*</span></label>
                        <input type="text" id="ten_hang_hoa" name="ten_hang_hoa" class="form-control"
                               required maxlength="1000">
                    </div>

                    <div class="form-group">
                        <label for="thong_so_ky_thuat">Tính năng, thông số kỹ thuật</label>
                        <textarea id="thong_so_ky_thuat" name="thong_so_ky_thuat" class="form-control" rows="4"></textarea>
                        <div class="form-hint">Mỗi tiêu chí một dòng — nhà thầu sẽ đối chiếu từng dòng khi chào giá.</div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="dvt">Đơn vị tính</label>
                            <input type="text" id="dvt" name="dvt" class="form-control" maxlength="50" placeholder="VD: Cái">
                        </div>
                        <div class="form-group">
                            <label for="so_luong">Số lượng <span class="req">*</span></label>
                            <input type="number" id="so_luong" name="so_luong" class="form-control"
                                   min="0" step="0.001" required value="0">
                            <div class="form-hint">Dùng để tính thành tiền = đơn giá × số lượng.</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="chung_nhan">Chứng nhận yêu cầu</label>
                        <textarea id="chung_nhan" name="chung_nhan" class="form-control" rows="2"
                                  placeholder="VD: FDA (Mỹ) hoặc MHLW/PMDA (Nhật Bản) hoặc CE (MDR)"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="yeu_cau_xuat_xu">Yêu cầu xuất xứ</label>
                        <textarea id="yeu_cau_xuat_xu" name="yeu_cau_xuat_xu" class="form-control" rows="2"
                                  placeholder="VD: Nhóm G7, Liên minh Châu Âu (EU), Úc"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="yeu_cau_tro_cu">Yêu cầu về trợ cụ / máy phụ trợ</label>
                        <textarea id="yeu_cau_tro_cu" name="yeu_cau_tro_cu" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Hủy</button>
                    <button type="submit" class="btn btn-primary"><?= IconHelper::svg('save', 16) ?>Lưu</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============ Modal import ============ -->
    <div class="modal" id="importModal">
        <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="importTitle" style="max-width:760px">
            <div class="modal-header">
                <h3 id="importTitle">Import hàng hóa từ Excel</h3>
                <button type="button" class="close" onclick="closeImport()" aria-label="Đóng"><?= IconHelper::svg('x', 20) ?></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <?= IconHelper::svg('info', 16) ?>
                    <span>
                        File phải theo đúng cấu trúc mẫu: dòng 1 là tiêu đề, dữ liệu bắt đầu từ
                        <strong>dòng <?= (int)BG_HangHoa_BUS::EXCEL_DATA_ROW ?></strong>.
                        Hệ thống đọc các cột <strong>A–K</strong> (Tên phần, STT, Tên hàng hoá, Thông số,
                        Chứng nhận, Xuất xứ, ĐVT, Số lượng, Trợ cụ). Cột D bắt buộc có giá trị.
                    </span>
                </div>

                <label class="dropzone" id="dropzone" for="fileExcel">
                    <span class="dz-icon"><?= IconHelper::svg('file-spreadsheet', 34) ?></span>
                    <span class="dz-main">Chọn file Excel hoặc kéo thả vào đây</span>
                    <span class="dz-sub">Chỉ nhận .xlsx, tối đa <?= round(AppConfig::UPLOAD_MAX_SIZE / 1048576) ?>MB</span>
                    <input type="file" id="fileExcel" accept=".xlsx" onchange="onFileChosen(this)">
                </label>

                <div id="fileInfo"></div>
                <div id="previewBox"></div>

                <div class="form-group" style="margin-top:16px">
                    <label class="check-cell" style="display:flex;align-items:flex-start;gap:9px;cursor:pointer">
                        <input type="checkbox" id="ghiDe" <?= ($goiThau && (int)$goiThau->so_bao_gia > 0) ? 'disabled' : '' ?>>
                        <span>
                            <strong>Ghi đè danh mục hiện tại</strong>
                            <span class="form-hint" style="margin-top:2px">
                                <?php if ($goiThau && (int)$goiThau->so_bao_gia > 0): ?>
                                    Không dùng được vì gói thầu đã có báo giá — đổi danh mục sẽ làm lệch dữ liệu đã chào.
                                <?php else: ?>
                                    Chuyển toàn bộ hàng hóa cũ vào thùng rác rồi nạp danh sách mới.
                                    Bỏ trống thì hàng hóa mới được thêm tiếp vào cuối.
                                <?php endif; ?>
                            </span>
                        </span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeImport()">Hủy</button>
                <button type="button" class="btn btn-outline-primary" id="btnPreview" onclick="doPreview()" disabled>
                    <?= IconHelper::svg('eye', 16) ?>Xem trước
                </button>
                <button type="button" class="btn btn-primary" id="btnImport" onclick="doImport()" disabled>
                    <?= IconHelper::svg('upload', 16) ?>Import
                </button>
            </div>
        </div>
    </div>

<?php endif; ?>

<script>
var AJAX_URL = <?= json_encode($AJAX) ?>;
var URL_DOWNLOAD = <?= json_encode(AppConfig::baseUrl('GUI/BG_HangHoa/download.php')) ?>;
var CAN = { add: <?= $canAdd ? 'true' : 'false' ?>, edit: <?= $canEdit ? 'true' : 'false' ?>, del: <?= $canDel ? 'true' : 'false' ?> };
var GOI_THAU_ID = <?= (int)$goiThauId ?>;
var currentPage = 1, pageSize = <?= (int)AppConfig::DEFAULT_PAGE_SIZE ?>, isLoading = false;
var firstLoad = true;

function currentTrash() { return $('#filterDaXoa').val() === '1'; }

function loadData() {
    if (isLoading || !GOI_THAU_ID) return;
    isLoading = true;
    if (firstLoad) { $('#tbody').html(APP.skeletonRows(6, 7)); }
    else { APP.showLoading('#tableWrap'); }

    APP.ajax(AJAX_URL, {
        action: 'getPaged',
        page: currentPage,
        pageSize: pageSize,
        goi_thau_id: GOI_THAU_ID,
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
    if (!rows.length) {
        $('#tbody').html(APP.emptyRow(7, currentTrash()
            ? 'Thùng rác trống'
            : 'Gói thầu chưa có hàng hóa. Bấm "Import Excel" để nạp từ file mẫu, hoặc "Thêm hàng hóa" để nhập tay.'));
        return;
    }
    var trash = currentTrash(), html = '';

    for (var i = 0; i < rows.length; i++) {
        var r = rows[i];

        var actions = '';
        if (trash) {
            if (CAN.edit) actions += '<button class="btn btn-sm btn-outline-primary" onclick="restore(' + r.id + ')" title="Khôi phục">' + APP.icon('rotate-ccw', 15) + '</button>';
        } else {
            if (CAN.edit) actions += '<button class="btn btn-sm btn-outline-primary" onclick="edit(' + r.id + ')" title="Sửa">' + APP.icon('pencil', 15) + '</button>';
            if (CAN.del)  actions += '<button class="btn btn-sm btn-outline-danger" onclick="del(' + r.id + ')" title="Xóa">' + APP.icon('trash', 15) + '</button>';
        }
        if (!actions) actions = '<span class="text-muted">—</span>';

        var phan = '';
        if (r.ten_phan) phan += APP.escape(r.ten_phan);
        if (r.stt_theo_phan) phan += (phan ? '<span class="cell-sub">' + APP.escape(r.stt_theo_phan) + '</span>' : APP.escape(r.stt_theo_phan));
        if (!phan) phan = '<span class="text-muted">—</span>';

        var tskt = r.thong_so_ky_thuat
            ? '<div class="spec-box">' + APP.escape(r.thong_so_ky_thuat) + '</div>'
            : '<span class="text-muted">—</span>';

        html += '<tr>' +
            '<td class="col-id">' + (r.thu_tu || (i + 1)) + '</td>' +
            '<td>' + phan + '</td>' +
            '<td><span class="cell-main">' + APP.escape(r.ten_hang_hoa) + '</span></td>' +
            '<td>' + tskt + '</td>' +
            '<td>' + (r.dvt ? APP.escape(r.dvt) : '<span class="text-muted">—</span>') + '</td>' +
            '<td class="col-qty">' + Number(r.so_luong || 0).toLocaleString('vi-VN') + '</td>' +
            '<td class="col-actions"><span class="row-actions">' + actions + '</span></td>' +
            '</tr>';
    }
    $('#tbody').html(html);
}

function openCreate() {
    $('#modalTitle').text('Thêm hàng hóa');
    $('#form')[0].reset();
    $('#id').val('');
    $('#thu_tu').val('0');
    $('#goi_thau_id').val(GOI_THAU_ID);
    $('#so_luong').val(0);
    APP.clearFieldErrors('#form');
    $('#modal').addClass('open');
    $('#ten_hang_hoa').trigger('focus');
}

function edit(id) {
    APP.ajax(AJAX_URL, { action: 'getById', id: id }, {
        success: function (res) {
            var d = res.data;
            $('#modalTitle').text('Sửa hàng hóa');
            $('#id').val(d.id);
            $('#goi_thau_id').val(d.goi_thau_id);
            $('#thu_tu').val(d.thu_tu || 0);
            $('#ten_phan').val(d.ten_phan || '');
            $('#stt_theo_phan').val(d.stt_theo_phan || '');
            $('#stt_thong_bao').val(d.stt_thong_bao || '');
            $('#ten_hang_hoa').val(d.ten_hang_hoa || '');
            $('#thong_so_ky_thuat').val(d.thong_so_ky_thuat || '');
            $('#dvt').val(d.dvt || '');
            $('#so_luong').val(d.so_luong || 0);
            $('#chung_nhan').val(d.chung_nhan || '');
            $('#yeu_cau_xuat_xu').val(d.yeu_cau_xuat_xu || '');
            $('#yeu_cau_tro_cu').val(d.yeu_cau_tro_cu || '');
            APP.clearFieldErrors('#form');
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
    APP.confirm('Chuyển hàng hóa này vào thùng rác?', function () {
        APP.ajax(AJAX_URL, { action: 'trash', id: id }, {
            success: function (res) { APP.toast(res.message, 'success'); loadData(); }
        });
    });
}

function restore(id) {
    APP.confirm('Khôi phục hàng hóa này?', function () {
        APP.ajax(AJAX_URL, { action: 'restore', id: id }, {
            success: function (res) { APP.toast(res.message, 'success'); loadData(); }
        });
    }, { yesClass: 'btn-primary', yesText: 'Khôi phục' });
}

/* ============ IMPORT ============ */
function openImport() {
    $('#fileExcel').val('');
    $('#fileInfo').empty();
    $('#previewBox').empty();
    $('#ghiDe').prop('checked', false);
    $('#btnPreview, #btnImport').prop('disabled', true);
    $('#importModal').addClass('open');
}

function closeImport() { $('#importModal').removeClass('open'); }

function onFileChosen(input) {
    var f = input.files && input.files[0];
    $('#previewBox').empty();
    if (!f) {
        $('#fileInfo').empty();
        $('#btnPreview, #btnImport').prop('disabled', true);
        return;
    }
    var kb = f.size < 1048576
        ? (f.size / 1024).toFixed(0) + ' KB'
        : (f.size / 1048576).toFixed(1) + ' MB';
    $('#fileInfo').html(
        '<div class="file-chosen">' + APP.icon('file-spreadsheet', 17) +
        '<span class="fc-name">' + APP.escape(f.name) + '</span>' +
        '<span class="fc-size">' + kb + '</span></div>'
    );
    $('#btnPreview, #btnImport').prop('disabled', false);
}

/** Gửi file qua FormData — vẫn phải tự gắn CSRF vì không đi qua APP.ajax */
function uploadFile(action, extra, onDone) {
    var f = document.getElementById('fileExcel').files[0];
    if (!f) { APP.toast('Chưa chọn file', 'warning'); return; }

    var fd = new FormData();
    fd.append('action', action);
    fd.append('file', f);
    fd.append('goi_thau_id', GOI_THAU_ID);
    for (var k in extra) { if (Object.prototype.hasOwnProperty.call(extra, k)) fd.append(k, extra[k]); }

    APP.showLoading('#importModal .modal-body');
    $.ajax({
        url: AJAX_URL,
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        dataType: 'json',
        headers: { 'X-CSRF-Token': CSRF_TOKEN },
        success: function (res) {
            if (res && res.success) { onDone(res); }
            else { APP.toast((res && res.message) || 'Có lỗi xảy ra', 'error'); }
        },
        error: function (xhr) {
            var msg = 'Lỗi tải file';
            try { msg = JSON.parse(xhr.responseText).message || msg; } catch (e) {}
            APP.toast(msg, 'error');
        },
        complete: function () { APP.hideLoading('#importModal .modal-body'); }
    });
}

function doPreview() {
    uploadFile('previewImport', {}, function (res) {
        var d = res.data || {};
        var rows = d.xem_truoc || [];
        var html = '<div class="alert alert-success" style="margin-top:14px">' + APP.icon('check-circle', 16) +
                   ' Đọc được <strong>' + d.tong_dong + '</strong> dòng hàng hóa' +
                   (rows.length < d.tong_dong ? ' (xem trước ' + rows.length + ' dòng đầu)' : '') + '</div>';

        html += '<div class="table-wrap" style="max-height:280px;overflow:auto;margin-top:10px">' +
                '<table class="table"><thead><tr>' +
                '<th>Dòng</th><th>Phần</th><th>Tên hàng hóa</th><th>ĐVT</th><th class="col-qty">SL</th>' +
                '</tr></thead><tbody>';
        for (var i = 0; i < rows.length; i++) {
            var r = rows[i];
            html += '<tr>' +
                '<td class="col-id">' + APP.escape(r.row) + '</td>' +
                '<td>' + APP.escape(r.stt_theo_phan || r.ten_phan || '—') + '</td>' +
                '<td>' + APP.escape(r.ten_hang_hoa) + '</td>' +
                '<td>' + APP.escape(r.dvt || '—') + '</td>' +
                '<td class="col-qty">' + Number(r.so_luong || 0).toLocaleString('vi-VN') + '</td>' +
                '</tr>';
        }
        html += '</tbody></table></div>';

        if (d.canh_bao && d.canh_bao.length) {
            html += renderCanhBao(d.canh_bao);
        }
        $('#previewBox').html(html);
    });
}

function renderCanhBao(list) {
    var h = '<div class="import-warnings"><strong style="font-size:12.5px;color:#78350f">' +
            'Cảnh báo (' + list.length + '):</strong><ul>';
    for (var i = 0; i < list.length; i++) h += '<li>' + APP.escape(list[i]) + '</li>';
    return h + '</ul></div>';
}

function doImport() {
    var ghiDe = $('#ghiDe').is(':checked') ? 1 : 0;
    var msg = ghiDe
        ? 'Ghi đè danh mục: toàn bộ hàng hóa hiện tại sẽ vào thùng rác rồi nạp danh sách mới. Tiếp tục?'
        : 'Thêm hàng hóa từ file vào cuối danh mục hiện tại. Tiếp tục?';

    APP.confirm(msg, function () {
        uploadFile('import', { ghi_de: ghiDe }, function (res) {
            APP.toast(res.message, 'success');
            var d = res.data || {};
            if (d.canh_bao && d.canh_bao.length) {
                // Còn cảnh báo thì giữ modal để người dùng đọc
                $('#previewBox').html(renderCanhBao(d.canh_bao));
                APP.toast('Có ' + d.canh_bao.length + ' cảnh báo — xem chi tiết trong hộp thoại', 'warning');
            } else {
                closeImport();
            }
            firstLoad = true;
            currentPage = 1;
            loadData();
        });
    }, { yesClass: 'btn-primary', yesText: 'Import' });
}

/* Kéo thả file */
var $dz = $('#dropzone');
$dz.on('dragover dragenter', function (e) { e.preventDefault(); e.stopPropagation(); $dz.addClass('is-dragover'); });
$dz.on('dragleave dragend drop', function (e) { e.preventDefault(); e.stopPropagation(); $dz.removeClass('is-dragover'); });
$dz.on('drop', function (e) {
    var files = e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files;
    if (files && files.length) {
        document.getElementById('fileExcel').files = files;
        onFileChosen(document.getElementById('fileExcel'));
    }
});

function closeModal() { $('#modal').removeClass('open'); }

/* Đổi gói thầu → tải lại trang để context-bar và quyền ghi đè cập nhật đúng */
$('#filterGoiThau').on('change', function () {
    window.location.href = '?goi_thau_id=' + encodeURIComponent($(this).val());
});
$('#btnTaiMau').attr('href', URL_DOWNLOAD + '?goi_thau_id=' + GOI_THAU_ID);

$('#search').on('keyup', APP.debounce(function () { currentPage = 1; loadData(); }, 350));
$('#filterDaXoa').on('change', function () { currentPage = 1; loadData(); });
$('#modal, #importModal').on('click', function (e) { if (e.target === this) $(this).removeClass('open'); });
$(document).on('keydown', function (e) { if (e.key === 'Escape') { closeModal(); closeImport(); } });

APP.bindPagination('#paginationWrap', function (p) { currentPage = p; loadData(); });

$(document).ready(loadData);
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
