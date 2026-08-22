<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../BUS/BG_GoiThau_BUS.php';

Helper::requireLogin();
PhanQuyenHelper::requireQuyenView('BG_GoiThau');

$canAdd  = PhanQuyenHelper::hasQuyen('BG_GoiThau', PhanQuyenHelper::QUYEN_THEM);
$canEdit = PhanQuyenHelper::hasQuyen('BG_GoiThau', PhanQuyenHelper::QUYEN_SUA);
$canDel  = PhanQuyenHelper::hasQuyen('BG_GoiThau', PhanQuyenHelper::QUYEN_XOA);

$pageTitle  = 'Gói thầu / Mời chào giá';
$activeMenu = 'BG_GoiThau';
$AJAX = AppConfig::baseUrl('GUI/BG_GoiThau/ajax_handler.php');
require __DIR__ . '/../layouts/header.php';
?>

<nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="<?= AppConfig::baseUrl('GUI/dashboard/index.php') ?>">Trang chủ</a>
    <span class="sep">›</span> Nghiệp vụ báo giá
    <span class="sep">›</span> <span>Gói thầu / Mời chào giá</span>
</nav>

<div class="card">
    <div class="toolbar">
        <div class="left">
            <span class="search-box" style="max-width:330px">
                <?= IconHelper::svg('search', 16) ?>
                <input type="text" id="search" class="form-control" placeholder="Tìm số thông báo, tên gói thầu...">
            </span>
            <select id="filterTrangThai" class="form-select" style="max-width:160px" aria-label="Lọc trạng thái gói thầu">
                <option value="-1">Mọi trạng thái</option>
                <?php foreach (BG_GoiThau_PUBLIC::danhSachTrangThai() as $v => $t): ?>
                    <option value="<?= (int)$v ?>"><?= Helper::h($t) ?></option>
                <?php endforeach; ?>
            </select>
            <select id="filterTrangThaiBaoGia" class="form-select" style="max-width:200px"
                    aria-label="Lọc trạng thái báo giá">
                <option value="">Mọi trạng thái báo giá</option>
                <?php foreach (BG_GoiThau_PUBLIC::danhSachTrangThaiBaoGia() as $v => $t): ?>
                    <option value="<?= Helper::h($v) ?>"><?= Helper::h($t) ?></option>
                <?php endforeach; ?>
            </select>
            <select id="filterDaXoa" class="form-select" style="max-width:160px" aria-label="Lọc thùng rác">
                <option value="0">Đang hoạt động</option>
                <option value="1">Thùng rác</option>
            </select>
        </div>
        <div class="right">
            <?php if ($canAdd): ?>
                <button type="button" class="btn btn-primary" onclick="openCreate()">
                    <?= IconHelper::svg('plus', 16) ?><span class="btn-label">Thêm gói thầu</span>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-wrap" id="tableWrap">
        <table class="table">
            <thead>
                <tr>
                    <th class="col-id">ID</th>
                    <th>Số thông báo</th>
                    <th>Tên gói thầu</th>
                    <th>Thời gian nhận báo giá</th>
                    <th>Trạng thái báo giá</th>
                    <th>Hàng hóa</th>
                    <th>Báo giá</th>
                    <th>Trạng thái</th>
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
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="modalTitle" style="max-width:760px">
        <div class="modal-header">
            <h3 id="modalTitle">Thêm gói thầu</h3>
            <button type="button" class="close" onclick="closeModal()" aria-label="Đóng"><?= IconHelper::svg('x', 20) ?></button>
        </div>
        <form id="form" onsubmit="return save()">
            <div class="modal-body">
                <input type="hidden" id="id" name="id">

                <div class="form-row">
                    <div class="form-group">
                        <label for="so_thong_bao">Số thông báo mời chào giá <span class="req">*</span></label>
                        <input type="text" id="so_thong_bao" name="so_thong_bao" class="form-control"
                               required maxlength="100" autocomplete="off" placeholder="VD: 5742/2026">
                        <div class="form-hint">Dùng để nhà thầu đối chiếu, phải là duy nhất.</div>
                    </div>
                    <div class="form-group">
                        <label for="trang_thai">Trạng thái</label>
                        <select id="trang_thai" name="trang_thai" class="form-select">
                            <?php foreach (BG_GoiThau_PUBLIC::danhSachTrangThai() as $v => $t): ?>
                                <option value="<?= (int)$v ?>"><?= Helper::h($t) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-hint">Chỉ trạng thái <strong>Đang mở</strong> mới nhận được báo giá.</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="ten_goi_thau">Tên gói thầu <span class="req">*</span></label>
                    <input type="text" id="ten_goi_thau" name="ten_goi_thau" class="form-control"
                           required maxlength="500" placeholder="VD: Mua vật tư tiêu hao phẫu thuật cột sống năm 2026">
                </div>

                <div class="form-group">
                    <label for="noi_dung">Nội dung / danh mục tóm tắt</label>
                    <textarea id="noi_dung" name="noi_dung" class="form-control" rows="3"
                              placeholder="Liệt kê ngắn các nhóm hàng hóa, cách nhau bởi dấu chấm phẩy"></textarea>
                    <div class="form-hint">Hiển thị cho nhà thầu ở trang chào giá. Không bắt buộc.</div>
                </div>

                <div class="form-group">
                    <label for="ngay_phat_hanh">Ngày phát hành</label>
                    <input type="date" id="ngay_phat_hanh" name="ngay_phat_hanh" class="form-control">
                </div>

                <fieldset style="border:1px solid var(--gray-200);border-radius:var(--radius);padding:14px 16px;margin-bottom:16px">
                    <legend style="font-size:13px;font-weight:600;color:var(--gray-700);padding:0 6px">
                        Thời gian nhận báo giá
                    </legend>
                    <div class="form-row" style="margin-bottom:0">
                        <div class="form-group">
                            <label for="thoi_gian_mo_bao_gia">Mở báo giá</label>
                            <input type="datetime-local" id="thoi_gian_mo_bao_gia"
                                   name="thoi_gian_mo_bao_gia" class="form-control">
                            <div class="form-hint">Trước mốc này, nhà thầu quét QR chỉ <strong>tra cứu</strong> được.</div>
                        </div>
                        <div class="form-group">
                            <label for="thoi_gian_dong_bao_gia">Đóng báo giá <span class="req">*</span></label>
                            <input type="datetime-local" id="thoi_gian_dong_bao_gia"
                                   name="thoi_gian_dong_bao_gia" class="form-control">
                            <div class="form-hint">Sau mốc này, cổng chào giá tự khóa, chỉ còn tra cứu.</div>
                        </div>
                    </div>
                    <div class="form-hint" style="margin-top:10px">
                        Bắt buộc có thời gian đóng khi gói thầu ở trạng thái <strong>Đang mở</strong>.
                    </div>
                </fieldset>

                <div class="form-group">
                    <label for="han_cuoi">Hạn cuối tiếp nhận (ghi trên thông báo)</label>
                    <input type="date" id="han_cuoi" name="han_cuoi" class="form-control">
                    <div class="form-hint">
                        Chỉ để hiển thị/in trên thông báo. Việc khóa cổng chào giá căn theo
                        <strong>thời gian đóng báo giá</strong> ở trên.
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="thoi_gian_hop_dong">Thời gian thực hiện hợp đồng (tháng)</label>
                        <input type="number" id="thoi_gian_hop_dong" name="thoi_gian_hop_dong"
                               class="form-control" min="0" max="600" step="1" value="0">
                    </div>
                    <div class="form-group">
                        <label for="hieu_luc_bao_gia">Hiệu lực báo giá tối thiểu (ngày)</label>
                        <input type="number" id="hieu_luc_bao_gia" name="hieu_luc_bao_gia"
                               class="form-control" min="0" max="3650" step="1" value="180">
                        <div class="form-hint">Nhà thầu phải cam kết tối thiểu bằng số ngày này.</div>
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

<!-- ============ Modal QR ============ -->
<div class="modal" id="qrModal">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="qrTitle" style="max-width:660px">
        <div class="modal-header">
            <h3 id="qrTitle">Mã QR chào giá</h3>
            <button type="button" class="close" onclick="closeQr()" aria-label="Đóng"><?= IconHelper::svg('x', 20) ?></button>
        </div>
        <div class="modal-body">
            <div id="qrWarn"></div>
            <div class="qr-panel">
                <!-- .qr-print-area: vùng DUY NHẤT được in (xem @media print trong style.css) -->
                <div class="qr-print-area" id="qrPrintArea">
                    <div class="qr-figure" id="qrFigure"></div>
                    <!-- Chú thích chỉ hiện khi in, để tờ giấy biết là của gói thầu nào -->
                    <div class="qr-print-caption" aria-hidden="true">
                        <strong id="qrPrintTitle"></strong>
                        <span id="qrPrintSub"></span>
                    </div>
                </div>
                <div class="qr-side">
                    <div class="detail-item" style="margin-bottom:14px">
                        <span class="detail-label">Gói thầu</span>
                        <span class="detail-value" id="qrGoiThau"></span>
                    </div>
                    <div class="detail-item" style="margin-bottom:14px">
                        <span class="detail-label">Đóng nhận báo giá</span>
                        <span class="detail-value" id="qrHanCuoi"></span>
                    </div>
                    <label class="detail-label" for="qrUrl">Đường dẫn chào giá</label>
                    <input type="text" class="qr-url" id="qrUrl" readonly
                           onclick="this.select()" aria-label="Đường dẫn chào giá">
                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="copyUrl()">
                            <?= IconHelper::svg('copy', 15) ?><span class="btn-label">Sao chép link</span>
                        </button>
                        <a class="btn btn-sm btn-primary" id="btnThuMoi" href="#">
                            <?= IconHelper::svg('printer', 15) ?><span class="btn-label">In Thư mời (Word)</span>
                        </a>
                        <?php if ($canEdit): ?>
                        <?php endif; ?>
                    </div>
                    <p class="form-hint" style="margin-top:12px">
                        Nhà thầu quét QR, đăng nhập bằng tài khoản chung, rồi tự khai thông tin công ty và điền giá.
                    </p>
                </div>
            </div>

        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeQr()">Đóng</button>
        </div>
    </div>
</div>

<script>
var AJAX_URL = <?= json_encode($AJAX) ?>;
var URL_HANG_HOA = <?= json_encode(AppConfig::baseUrl('GUI/BG_HangHoa/index.php')) ?>;
var URL_BAO_GIA  = <?= json_encode(AppConfig::baseUrl('GUI/BG_BaoGia/index.php')) ?>;
var URL_TONG_HOP = <?= json_encode(AppConfig::baseUrl('GUI/BG_TongHop/index.php')) ?>;
var URL_TAI_THU_MOI = <?= json_encode(AppConfig::baseUrl('GUI/BG_GoiThau/download.php')) ?>;
var CAN = { add: <?= $canAdd ? 'true' : 'false' ?>, edit: <?= $canEdit ? 'true' : 'false' ?>, del: <?= $canDel ? 'true' : 'false' ?> };
var TT = <?= json_encode(BG_GoiThau_PUBLIC::danhSachTrangThai(), JSON_UNESCAPED_UNICODE) ?>;
var TT_DANG_MO = <?= (int)BG_GoiThau_PUBLIC::TT_DANG_MO ?>;
var TT_DA_DONG = <?= (int)BG_GoiThau_PUBLIC::TT_DA_DONG ?>;
/* Trạng thái báo giá suy từ thời gian — server đã tính, JS chỉ hiển thị */
var BG_TT = {
    CHUA_MO:    <?= json_encode(BG_GoiThau_PUBLIC::BG_CHUA_MO) ?>,
    DANG_MO:    <?= json_encode(BG_GoiThau_PUBLIC::BG_DANG_MO) ?>,
    HET_HAN:    <?= json_encode(BG_GoiThau_PUBLIC::BG_HET_HAN) ?>,
    KHONG_NHAN: <?= json_encode(BG_GoiThau_PUBLIC::BG_KHONG_NHAN) ?>
};

var currentPage = 1, pageSize = <?= (int)AppConfig::DEFAULT_PAGE_SIZE ?>, isLoading = false;
var firstLoad = true;
var qrId = 0;

function currentTrash() { return $('#filterDaXoa').val() === '1'; }

/* Định dạng theo GIỜ ĐỊA PHƯƠNG — không dùng toISOString() vì nó trả UTC,
   lệch 7 tiếng ở Việt Nam (đặt 00:00 hôm nay sẽ thành 17:00 hôm trước). */
function pad2(n) { return (n < 10 ? '0' : '') + n; }

function dateLocal(d) {
    return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());
}

function dateTimeLocal(d) {
    return dateLocal(d) + 'T' + pad2(d.getHours()) + ':' + pad2(d.getMinutes());
}

/** DATETIME từ server ("Y-m-d H:i:s") → value cho input datetime-local */
function toInputDateTime(s) {
    if (!s) return '';
    return String(s).replace(' ', 'T').slice(0, 16);
}

function loadData() {
    if (isLoading) return;
    isLoading = true;
    if (firstLoad) { $('#tbody').html(APP.skeletonRows(6, 9)); }
    else { APP.showLoading('#tableWrap'); }

    APP.ajax(AJAX_URL, {
        action: 'getPaged',
        page: currentPage,
        pageSize: pageSize,
        search: $('#search').val(),
        trang_thai: $('#filterTrangThai').val(),
        trang_thai_bao_gia: $('#filterTrangThaiBaoGia').val(),
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
    var cls = 'badge-neutral';
    if (tt === TT_DANG_MO) cls = 'badge-success';
    else if (tt === TT_DA_DONG) cls = 'badge-warning';
    else if (tt === 3) cls = 'badge-info';
    return '<span class="badge ' + cls + '">' + APP.escape(TT[tt] || '—') + '</span>';
}

/** Khoảng thời gian nhận báo giá: mở → đóng */
function moTaThoiGian(r) {
    var mo = r.thoi_gian_mo_bao_gia ? APP.formatDateTime(r.thoi_gian_mo_bao_gia) : null;
    var dong = r.thoi_gian_dong_bao_gia ? APP.formatDateTime(r.thoi_gian_dong_bao_gia) : null;
    if (!mo && !dong) return '<span class="text-muted">Chưa đặt</span>';

    var html = '';
    if (mo)   html += '<span class="cell-sub" style="margin-top:0">Mở: ' + APP.escape(mo) + '</span>';
    if (dong) html += '<span class="cell-sub">Đóng: ' + APP.escape(dong) + '</span>';
    else      html += '<span class="cell-sub">Đóng: chưa đặt</span>';
    return html;
}

/** Badge trạng thái báo giá — dùng mã do server tính, KHÔNG tự tính lại ở JS */
function badgeTrangThaiBaoGia(r) {
    var ma = r.trang_thai_bao_gia;
    var ten = r.ten_trang_thai_bao_gia || '—';
    var cls = 'badge-neutral', ic = 'info';

    if (ma === BG_TT.DANG_MO)         { cls = 'badge-success'; ic = 'check-circle'; }
    else if (ma === BG_TT.CHUA_MO)    { cls = 'badge-info';    ic = 'clock'; }
    else if (ma === BG_TT.HET_HAN)    { cls = 'badge-danger';  ic = 'x-circle'; }
    else if (ma === BG_TT.KHONG_NHAN) { cls = 'badge-neutral'; ic = 'lock'; }

    return '<span class="badge badge-quote ' + cls + '">' + APP.icon(ic, 13) +
           APP.escape(ten) + '</span>';
}

function renderTable(rows) {
    if (!rows.length) {
        $('#tbody').html(APP.emptyRow(9, currentTrash()
            ? 'Thùng rác trống'
            : 'Chưa có gói thầu nào. Bấm "Thêm gói thầu" để tạo thông báo mời chào giá đầu tiên.'));
        return;
    }
    var trash = currentTrash(), html = '';

    for (var i = 0; i < rows.length; i++) {
        var r = rows[i];
        var soHH = parseInt(r.so_hang_hoa, 10) || 0;
        var soBG = parseInt(r.so_bao_gia, 10) || 0;
        var soXN = parseInt(r.so_bao_gia_xac_nhan, 10) || 0;

        var actions = '';
        if (trash) {
            if (CAN.edit) actions += '<button class="btn btn-sm btn-outline-primary" onclick="restore(' + r.id + ')" title="Khôi phục">' + APP.icon('rotate-ccw', 15) + '</button>';
            if (CAN.del)  actions += '<button class="btn btn-sm btn-outline-danger" onclick="delForever(' + r.id + ')" title="Xóa vĩnh viễn">' + APP.icon('trash', 15) + '</button>';
        } else {
            actions += '<button class="btn btn-sm btn-outline-secondary" onclick="showQr(' + r.id + ')" title="Mã QR cho nhà thầu">' + APP.icon('qr-code', 15) + '</button>';
            actions += '<a class="btn btn-sm btn-outline-secondary" href="' + URL_HANG_HOA + '?goi_thau_id=' + r.id + '" title="Danh mục hàng hóa">' + APP.icon('package', 15) + '</a>';
            actions += '<a class="btn btn-sm btn-outline-secondary" href="' + URL_BAO_GIA + '?goi_thau_id=' + r.id + '" title="Báo giá đã nhận">' + APP.icon('file-spreadsheet', 15) + '</a>';
            if (soXN > 0) {
                actions += '<a class="btn btn-sm btn-outline-primary" href="' + URL_TONG_HOP + '?goi_thau_id=' + r.id + '" title="Tổng hợp báo giá">' + APP.icon('bar-chart', 15) + '</a>';
            }
            if (CAN.edit) actions += '<button class="btn btn-sm btn-outline-primary" onclick="edit(' + r.id + ')" title="Sửa">' + APP.icon('pencil', 15) + '</button>';
            if (CAN.del)  actions += '<button class="btn btn-sm btn-outline-danger" onclick="del(' + r.id + ')" title="Xóa">' + APP.icon('trash', 15) + '</button>';
        }
        if (!actions) actions = '<span class="text-muted">—</span>';

        // Đếm hàng hóa: 0 thì nhắc vì gói thầu chưa mở được
        var cellHH = soHH > 0
            ? '<a class="cell-link" href="' + URL_HANG_HOA + '?goi_thau_id=' + r.id + '">' + soHH + ' mục</a>'
            : '<span class="badge badge-warning">Chưa có</span>';

        var cellBG = soBG > 0
            ? '<a class="cell-link" href="' + URL_BAO_GIA + '?goi_thau_id=' + r.id + '">' + soBG + ' bản'
              + (soXN > 0 ? ' · ' + soXN + ' đã XN' : '') + '</a>'
            : '<span class="text-muted">—</span>';

        html += '<tr>' +
            '<td class="col-id">' + r.id + '</td>' +
            '<td><span class="cell-main text-mono">' + APP.escape(r.so_thong_bao) + '</span></td>' +
            '<td><span class="cell-main">' + APP.escape(r.ten_goi_thau) + '</span>' +
                (r.noi_dung ? '<span class="cell-sub">' + APP.escape(String(r.noi_dung).substring(0, 90)) + (String(r.noi_dung).length > 90 ? '…' : '') + '</span>' : '') +
            '</td>' +
            '<td>' + moTaThoiGian(r) + '</td>' +
            '<td>' + badgeTrangThaiBaoGia(r) + '</td>' +
            '<td>' + cellHH + '</td>' +
            '<td>' + cellBG + '</td>' +
            '<td>' + badgeTrangThai(r.trang_thai) + '</td>' +
            '<td class="col-actions"><span class="row-actions">' + actions + '</span></td>' +
            '</tr>';
    }
    $('#tbody').html(html);
}

function openCreate() {
    $('#modalTitle').text('Thêm gói thầu');
    $('#form')[0].reset();
    $('#id').val('');
    $('#hieu_luc_bao_gia').val(180);
    $('#thoi_gian_hop_dong').val(0);
    $('#trang_thai').val('0');
    // Mặc định: phát hành hôm nay, mở báo giá ngay, đóng sau 14 ngày
    var now = new Date();
    $('#ngay_phat_hanh').val(dateLocal(now));
    $('#thoi_gian_mo_bao_gia').val(dateTimeLocal(now));
    var dong = new Date(now.getTime() + 14 * 86400000);
    dong.setHours(17, 0, 0, 0);
    $('#thoi_gian_dong_bao_gia').val(dateTimeLocal(dong));
    $('#han_cuoi').val(dateLocal(dong));
    APP.clearFieldErrors('#form');
    $('#modal').addClass('open');
    $('#so_thong_bao').trigger('focus');
}

function edit(id) {
    APP.ajax(AJAX_URL, { action: 'getById', id: id }, {
        success: function (res) {
            var d = res.data;
            $('#modalTitle').text('Sửa gói thầu');
            $('#id').val(d.id);
            $('#so_thong_bao').val(d.so_thong_bao);
            $('#ten_goi_thau').val(d.ten_goi_thau);
            $('#noi_dung').val(d.noi_dung || '');
            $('#ngay_phat_hanh').val(d.ngay_phat_hanh || '');
            $('#thoi_gian_mo_bao_gia').val(toInputDateTime(d.thoi_gian_mo_bao_gia));
            $('#thoi_gian_dong_bao_gia').val(toInputDateTime(d.thoi_gian_dong_bao_gia));
            $('#han_cuoi').val(d.han_cuoi || '');
            $('#thoi_gian_hop_dong').val(d.thoi_gian_hop_dong || 0);
            $('#hieu_luc_bao_gia').val(d.hieu_luc_bao_gia || 0);
            $('#trang_thai').val(String(d.trang_thai));
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
    APP.confirm('Chuyển gói thầu này vào thùng rác?', function () {
        APP.ajax(AJAX_URL, { action: 'trash', id: id }, {
            success: function (res) { APP.toast(res.message, 'success'); loadData(); }
        });
    });
}

function restore(id) {
    APP.confirm('Khôi phục gói thầu này?', function () {
        APP.ajax(AJAX_URL, { action: 'restore', id: id }, {
            success: function (res) { APP.toast(res.message, 'success'); loadData(); }
        });
    }, { yesClass: 'btn-primary', yesText: 'Khôi phục' });
}

function delForever(id) {
    APP.confirm('Xóa VĨNH VIỄN gói thầu này cùng danh mục hàng hóa? Không thể hoàn tác.', function () {
        APP.ajax(AJAX_URL, { action: 'delete', id: id }, {
            success: function (res) { APP.toast(res.message, 'success'); loadData(); }
        });
    }, { yesText: 'Xóa vĩnh viễn' });
}

/* ============ QR ============ */
function showQr(id) {
    qrId = id;
    APP.ajax(AJAX_URL, { action: 'getQr', id: id }, {
        success: function (res) {
            var d = res.data;
            // svg do server sinh (QrHelper), không phải dữ liệu người dùng nhập
            $('#qrFigure').html(d.svg);
            $('#qrGoiThau').text(d.so_thong_bao + ' — ' + d.ten_goi_thau);
            // Chú thích in kèm dưới mã QR
            $('#qrPrintTitle').text('Thông báo mời chào giá số ' + d.so_thong_bao);
            $('#qrPrintSub').text(d.ten_goi_thau);
            $('#qrHanCuoi').text(d.thoi_gian_dong_bao_gia
                ? APP.formatDateTime(d.thoi_gian_dong_bao_gia)
                : (d.han_cuoi ? APP.formatDate(d.han_cuoi) : 'Chưa đặt'));
            $('#qrUrl').val(d.url);

            // Nhắc rõ nhà thầu quét mã lúc này sẽ làm được gì
            var warn = '';
            if (d.trang_thai_bao_gia === BG_TT.CHUA_MO) {
                warn = '<div class="alert alert-info">' + APP.icon('clock', 16) +
                       ' Chưa tới thời gian mở báo giá' +
                       (d.thoi_gian_mo_bao_gia ? ' (' + APP.escape(APP.formatDateTime(d.thoi_gian_mo_bao_gia)) + ')' : '') +
                       '. Nhà thầu quét mã hiện chỉ <strong>tra cứu</strong> được.</div>';
            } else if (d.trang_thai_bao_gia === BG_TT.HET_HAN) {
                warn = '<div class="alert alert-warning">' + APP.icon('alert-triangle', 16) +
                       ' Đã hết thời gian nhận báo giá. Nhà thầu quét mã chỉ <strong>tra cứu</strong> được.</div>';
            } else if (d.trang_thai_bao_gia === BG_TT.KHONG_NHAN) {
                warn = '<div class="alert alert-warning">' + APP.icon('alert-triangle', 16) +
                       ' Gói thầu đang ở trạng thái <strong>' + APP.escape(TT[d.trang_thai] || '') +
                       '</strong> — chưa nhận báo giá. Hãy chuyển sang "Đang mở".</div>';
            }
            $('#qrWarn').html(warn);
            // Link tải Thư mời Word (đã tự điền QR, đường dẫn, tài khoản)
            $('#btnThuMoi').attr('href',
                URL_TAI_THU_MOI + '?goi_thau_id=' + id);
            $('#qrModal').addClass('open');
        }
    });
}

function copyUrl() {
    var el = document.getElementById('qrUrl');
    el.select();
    // navigator.clipboard cần HTTPS/localhost; execCommand là fallback cho http://thbg.bv
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(el.value).then(function () {
            APP.toast('Đã sao chép link chào giá', 'success');
        }, function () { fallbackCopy(); });
    } else {
        fallbackCopy();
    }
}

function fallbackCopy() {
    try {
        document.execCommand('copy');
        APP.toast('Đã sao chép link chào giá', 'success');
    } catch (e) {
        APP.toast('Không sao chép được — hãy chọn và copy thủ công', 'warning');
    }
}


function closeModal() { $('#modal').removeClass('open'); }
function closeQr() { $('#qrModal').removeClass('open'); }

$('#search').on('keyup', APP.debounce(function () { currentPage = 1; loadData(); }, 350));
$('#filterTrangThai, #filterTrangThaiBaoGia, #filterDaXoa').on('change', function () { currentPage = 1; loadData(); });
$('#modal, #qrModal').on('click', function (e) { if (e.target === this) $(this).removeClass('open'); });
$(document).on('keydown', function (e) {
    if (e.key === 'Escape') { closeModal(); closeQr(); }
});

APP.bindPagination('#paginationWrap', function (p) { currentPage = p; loadData(); });

$(document).ready(loadData);
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
