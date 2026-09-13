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

// Chan mo thang bang URL goi thau khong duoc phan quyen (3B.1):
// an tren giao dien khong phai la bao mat.
if ($goiThauId > 0) {
    require_once __DIR__ . '/../../BUS/BG_QuyenGoiThau_BUS.php';
    BG_QuyenGoiThau_BUS::requireXem($goiThauId);
}

// Không truyền gói thầu → chọn sẵn gói mới nhất để trang không rỗng vô nghĩa
if ($goiThauId === 0 && !empty($goiThauCombo)) {
    $goiThauId = (int)$goiThauCombo[0]['id'];
}
$goiThau = $goiThauId > 0 ? BG_GoiThau_BUS::getById($goiThauId) : null;

// Đếm bộ để hiện "cơ cấu" ở thanh thông tin — nhìn là biết gói này
// gồm mấy bộ, mỗi bộ bao nhiêu hàng hóa chi tiết.
require_once __DIR__ . '/../../DAL/BG_Bo_DAL.php';
$soBo = $goiThauId > 0 ? count(BG_Bo_DAL::getByGoiThau($goiThauId)) : 0;

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
                <?= IconHelper::svg('layout-list', 16) ?>
                <span class="ctx-label">Nhóm</span>
                <span class="ctx-value"><?= Helper::h(BG_Nhom_PUBLIC::tenNhom(BG_Nhom_PUBLIC::chuanHoa($goiThau->nhom ?? null))) ?></span>
            </span>
            <span class="ctx-item">
                <?= IconHelper::svg('package', 16) ?>
                <span class="ctx-label">Cơ cấu</span>
                <span class="ctx-value"><?= (int)$soBo ?> bộ / <?= (int)$goiThau->so_hang_hoa ?> hàng hóa</span>
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
                    <button type="button" class="btn btn-outline-primary" onclick="moThemBo()">
                        <?= IconHelper::svg('package', 16) ?><span class="btn-label">Thêm bộ</span>
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
                        <th class="col-id" style="width:64px">STT</th>
                        <th style="width:110px">Mã</th>
                        <th>Tên bộ / hàng hóa chi tiết</th>
                        <th>Yêu cầu kỹ thuật</th>
                        <th style="width:90px">Nhóm nước</th>
                        <th style="width:70px">ĐVT</th>
                        <th class="col-qty" style="width:80px">Số lượng</th>
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

                    <!-- Thuộc bộ nào — quyết định hàng này là chi tiết trong bộ
                         hay hàng lẻ. Yêu cầu chung/khác/cấu hình nằm ở BỘ, sửa
                         bằng nút "Sửa bộ" ở dòng bộ trong bảng. -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="bo_id">Thuộc bộ</label>
                            <select id="bo_id" name="bo_id" class="form-select">
                                <option value="">— Hàng lẻ (không thuộc bộ nào) —</option>
                            </select>
                            <div class="form-hint" id="boHint">
                                Vật tư, dược phần lớn là hàng lẻ. Bộ dụng cụ / hệ thống thiết bị
                                thì chọn bộ tương ứng.
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="stt_chi_tiet">STT trong bộ</label>
                            <input type="number" id="stt_chi_tiet" name="stt_chi_tiet" class="form-control"
                                   min="1" step="1" placeholder="Tự đánh nếu bỏ trống">
                            <div class="form-hint">Thứ tự của hàng hóa này trong bộ (cột D Phụ lục III).</div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="ma_hh">Mã hàng hóa</label>
                            <input type="text" id="ma_hh" name="ma_hh" class="form-control"
                                   maxlength="50" placeholder="VD: VT001">
                            <div class="form-hint">Bỏ trống để hệ thống tự sinh (HH001, HH002...).</div>
                        </div>
                        <div class="form-group">
                            <label for="nhom_nuoc">Nhóm nước, vùng lãnh thổ</label>
                            <input type="text" id="nhom_nuoc" name="nhom_nuoc" class="form-control"
                                   maxlength="500" placeholder="VD: Nhóm G7, EU">
                            <div class="form-hint">Bỏ trống nếu không yêu cầu xuất xứ cụ thể.</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="ten_hang_hoa">Tên hàng hóa <span class="req">*</span></label>
                        <input type="text" id="ten_hang_hoa" name="ten_hang_hoa" class="form-control"
                               required maxlength="1000">
                    </div>

                    <div class="form-group">
                        <label for="thong_so_ky_thuat">Yêu cầu kỹ thuật mời chào giá</label>
                        <textarea id="thong_so_ky_thuat" name="thong_so_ky_thuat" class="form-control" rows="5"></textarea>
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
                            <div class="form-hint" id="slHint">
                                Dùng để tính thành tiền = đơn giá × số lượng.
                            </div>
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


<!-- ============ Modal thêm / sửa BỘ ============
     Yêu cầu chung / khác / cấu hình gắn với BỘ (không gắn từng hàng hóa),
     và chỉ hiện phần NHÓM gói thầu này dùng — xem BG_Nhom_PUBLIC. -->
<div class="modal" id="boModal">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="boTitle" style="max-width:820px">
        <div class="modal-header">
            <h3 id="boTitle">Thêm bộ</h3>
            <button type="button" class="close" onclick="dongModalBo()" aria-label="Đóng"><?= IconHelper::svg('x', 20) ?></button>
        </div>
        <form id="formBo" onsubmit="return luuBo()">
            <div class="modal-body">
                <input type="hidden" id="bo_id_edit" name="id">
                <input type="hidden" name="goi_thau_id" value="<?= $goiThauId ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label for="bo_ma">Mã bộ</label>
                        <input type="text" id="bo_ma" name="ma_bo" class="form-control"
                               maxlength="50" placeholder="VD: BDC01">
                    </div>
                    <div class="form-group">
                        <label for="bo_stt">STT bộ</label>
                        <input type="number" id="bo_stt" name="stt_bo" class="form-control"
                               min="1" step="1" placeholder="Tự đánh nếu bỏ trống">
                    </div>
                </div>

                <div class="form-group">
                    <label for="bo_ten">Tên bộ / phần / hệ thống <span class="req">*</span></label>
                    <input type="text" id="bo_ten" name="ten_bo" class="form-control"
                           required maxlength="1000" placeholder="VD: BỘ DỤNG CỤ PHẪU THUẬT SỌ NÃO">
                </div>

                <!-- Chỉ hiện với nhóm bộ dụng cụ / hệ thống TBYT -->
                <div class="form-group" id="grYcChung" hidden>
                    <label for="bo_yc_chung">Yêu cầu chung</label>
                    <textarea id="bo_yc_chung" name="yeu_cau_chung" class="form-control" rows="3"></textarea>
                    <div class="form-hint">Áp dụng cho CẢ BỘ — mỗi tiêu chí một dòng.</div>
                </div>

                <div class="form-group" id="grYcKhac" hidden>
                    <label for="bo_yc_khac">Yêu cầu khác</label>
                    <textarea id="bo_yc_khac" name="yeu_cau_khac" class="form-control" rows="3"></textarea>
                    <div class="form-hint">VD: bảo hành, đào tạo vận hành, cung cấp vật tư thay thế.</div>
                </div>

                <!-- Chỉ hiện với nhóm hệ thống thiết bị y tế -->
                <div class="form-group" id="grYcCauHinh" hidden>
                    <label for="bo_yc_cau_hinh">Yêu cầu cấu hình</label>
                    <textarea id="bo_yc_cau_hinh" name="yeu_cau_cau_hinh" class="form-control" rows="3"></textarea>
                    <div class="form-hint">Liệt kê thành phần của cả hệ thống và số lượng từng thành phần.</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="bo_nhom_nuoc">Nhóm nước, vùng lãnh thổ</label>
                        <input type="text" id="bo_nhom_nuoc" name="nhom_nuoc" class="form-control"
                               maxlength="500" placeholder="VD: Nhóm G7, EU">
                    </div>
                    <div class="form-group">
                        <label for="bo_dvt">Đơn vị tính</label>
                        <input type="text" id="bo_dvt" name="dvt" class="form-control"
                               maxlength="50" placeholder="VD: Bộ, Hệ thống">
                    </div>
                    <div class="form-group">
                        <label for="bo_so_luong">Số lượng <span class="req">*</span></label>
                        <input type="number" id="bo_so_luong" name="so_luong" class="form-control"
                               min="0" step="0.001" required value="0">
                        <div class="form-hint" id="boSlHint"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="dongModalBo()">Hủy</button>
                <button type="submit" class="btn btn-primary"><?= IconHelper::svg('save', 16) ?>Lưu bộ</button>
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
/* Danh sách BỘ của gói thầu — nạp kèm mỗi lần loadData().
   Dùng để vẽ bộ RỖNG (chưa có hàng hóa), thứ mà truy vấn hàng hóa không trả về. */
var DS_BO = [];
/* Nhóm gói thầu — quyết định hiện cột yêu cầu chung/khác/cấu hình ở dòng BỘ */
var NHOM = <?= json_encode(BG_Nhom_PUBLIC::chuanHoa($goiThau->nhom ?? null)) ?>;
var CO_YC_CHUNG    = <?= BG_Nhom_PUBLIC::coYeuCauChung(BG_Nhom_PUBLIC::chuanHoa($goiThau->nhom ?? null)) ? 'true' : 'false' ?>;
var CO_YC_CAU_HINH = <?= BG_Nhom_PUBLIC::coYeuCauCauHinh(BG_Nhom_PUBLIC::chuanHoa($goiThau->nhom ?? null)) ? 'true' : 'false' ?>;
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
            // Nạp kèm danh sách BỘ để vẽ được cả bộ RỖNG. Danh sách hàng hóa
            // đi từ bg_hang_hoa nên bộ chưa có hàng nào không lọt ra dòng nào
            // -> trước đây bộ rỗng vô hình, không có nút sửa/xóa để bấm.
            APP.ajax(AJAX_URL, { action: 'getComboBo', goi_thau_id: GOI_THAU_ID }, {
                success: function (rb) { DS_BO = rb.data || []; },
                error:   function () { DS_BO = []; },
                complete: function () {
                    renderTable(res.data || []);
                    $('#paginationWrap').html(APP.renderPagination(res.pagination));
                }
            });
        },
        complete: function () { isLoading = false; firstLoad = false; APP.hideLoading('#tableWrap'); }
    });
}

/**
 * Vẽ bảng danh mục theo CÂY BỘ: mỗi bộ 1 dòng tiêu đề nền đậm, hàng hóa
 * chi tiết thụt vào bên dưới. Nhìn là biết ngay hàng nào thuộc bộ nào —
 * danh sách phẳng như trước không phân biệt được.
 *
 * Dòng bộ hiện thêm yêu cầu chung / khác / cấu hình tùy NHÓM gói thầu.
 */
function renderTable(rows) {
    var SO_COT = 8;
    var trash = currentTrash();

    // Bộ RỖNG (chưa có hàng hóa nào) — vẫn phải hiện để còn sửa/xóa được.
    // Không hiện ở thùng rác: ở đó đang xem hàng hóa đã xóa, không phải bộ.
    var boRong = [];
    if (!trash) {
        for (var b = 0; b < DS_BO.length; b++) {
            if (Number(DS_BO[b].so_chi_tiet || 0) === 0) boRong.push(DS_BO[b]);
        }
    }

    if (!rows.length && !boRong.length) {
        $('#tbody').html(APP.emptyRow(SO_COT, trash
            ? 'Thùng rác trống'
            : 'Gói thầu chưa có hàng hóa. Bấm "Import Excel" để nạp từ file mẫu, hoặc "Thêm hàng hóa" để nhập tay.'));
        return;
    }

    var html = '', boHienTai = null, sttBo = 0;

    for (var i = 0; i < rows.length; i++) {
        var r = rows[i];

        // ---- Dòng tiêu đề BỘ (chỉ in khi đổi sang bộ khác) ----
        var boId = r.bo_id || 0;
        if (boId !== boHienTai) {
            boHienTai = boId;
            sttBo++;
            html += dongBo(r, sttBo, SO_COT);
        }

        // ---- Dòng hàng hóa chi tiết ----
        var actions = '';
        if (trash) {
            if (CAN.edit) actions += '<button class="btn btn-sm btn-outline-primary" onclick="restore(' + r.id + ')" title="Khôi phục">' + APP.icon('rotate-ccw', 15) + '</button>';
        } else {
            if (CAN.edit) actions += '<button class="btn btn-sm btn-outline-primary" onclick="edit(' + r.id + ')" title="Sửa">' + APP.icon('pencil', 15) + '</button>';
            if (CAN.del)  actions += '<button class="btn btn-sm btn-outline-danger" onclick="del(' + r.id + ')" title="Xóa">' + APP.icon('trash', 15) + '</button>';
        }
        if (!actions) actions = '<span class="text-muted">—</span>';

        var tskt = r.thong_so_ky_thuat
            ? '<div class="spec-box">' + APP.escape(r.thong_so_ky_thuat) + '</div>'
            : '<span class="text-muted">—</span>';

        html += '<tr class="row-ct">' +
            '<td class="col-id">' + (r.stt_chi_tiet || (i + 1)) + '</td>' +
            '<td>' + (r.ma_hh
                ? '<span class="text-mono">' + APP.escape(r.ma_hh) + '</span>'
                : '<span class="text-muted">—</span>') + '</td>' +
            '<td><span class="cell-main cell-thut">' + APP.escape(r.ten_hang_hoa) + '</span></td>' +
            '<td>' + tskt + '</td>' +
            '<td>' + (r.nhom_nuoc ? APP.escape(r.nhom_nuoc) : '<span class="text-muted">—</span>') + '</td>' +
            '<td>' + (r.dvt ? APP.escape(r.dvt) : '<span class="text-muted">—</span>') + '</td>' +
            '<td class="col-qty">' + Number(r.so_luong || 0).toLocaleString('vi-VN') + '</td>' +
            '<td class="col-actions"><span class="row-actions">' + actions + '</span></td>' +
            '</tr>';
    }

    // Bộ rỗng xếp cuối bảng, kèm ghi chú để bên mời biết còn thiếu hàng hóa.
    // dongBo() nhận khóa theo tên cột của bg_hang_hoa (bo_id, dvt_bo...) nên
    // phải ánh xạ lại từ bản ghi bg_bo.
    for (var k = 0; k < boRong.length; k++) {
        var bo = boRong[k];
        sttBo++;
        html += dongBo({
            bo_id:            bo.id,
            ma_bo:            bo.ma_bo,
            stt_bo:           bo.stt_bo,
            ten_bo:           bo.ten_bo,
            dvt_bo:           bo.dvt,
            so_luong_bo:      bo.so_luong,
            yeu_cau_chung:    bo.yeu_cau_chung,
            yeu_cau_khac:     bo.yeu_cau_khac,
            yeu_cau_cau_hinh: bo.yeu_cau_cau_hinh,
            nhom_nuoc_bo:     bo.nhom_nuoc
        }, sttBo, SO_COT, true);
    }

    $('#tbody').html(html);
}

/** Dòng tiêu đề của 1 BỘ — gộp cả hàng, kèm yêu cầu chung/khác/cấu hình */
function dongBo(r, stt, soCot, rong) {
    // Hàng LẺ (bo_id rỗng) là hợp lệ — vật tư, dược phần lớn không thuộc bộ.
    // Không in dòng tiêu đề gì cả, hàng hiện thẳng như một dòng bình thường.
    if (!r.bo_id) return '';

    var ten = r.ten_bo || '(chưa đặt tên bộ)';
    var h = '<tr class="row-bo">' +
        '<td class="col-id">' + (r.stt_bo || stt) + '</td>' +
        '<td>' + (r.ma_bo ? '<span class="text-mono">' + APP.escape(r.ma_bo) + '</span>' : '') + '</td>' +
        '<td colspan="' + (soCot - 4) + '">' +
            '<span class="ten-bo">' + APP.icon('package', 14) + ' ' + APP.escape(ten) + '</span>' +
            (rong ? ' <span class="badge badge-warning">Chưa có hàng hóa</span>' : '') +
            yeuCauBo(r) +
        '</td>' +
        '<td>' + (r.dvt_bo ? APP.escape(r.dvt_bo) : '') + '</td>' +
        '<td class="col-qty">' + (r.so_luong_bo ? Number(r.so_luong_bo).toLocaleString('vi-VN') : '') + '</td>' +
        '<td class="col-actions"><span class="row-actions">' + nutBo(r) + '</span></td>' +
        '</tr>';
    return h;
}

/** Yêu cầu cấp BỘ — chỉ hiện phần nhóm gói thầu này thực sự dùng */
/** Nút sửa / xóa trên dòng BỘ — ẩn khi ở thùng rác hoặc không có quyền */
function nutBo(r) {
    if (currentTrash() || !r.bo_id) return '';
    var h = '';
    if (CAN.edit) {
        h += '<button class="btn btn-sm btn-outline-primary" onclick="suaBo(' + r.bo_id +
             ')" title="Sửa thông tin bộ">' + APP.icon('pencil', 15) + '</button>';
    }
    if (CAN.del) {
        // Tên bộ có dấu nháy sẽ cắt đứt thuộc tính onclick -> dùng data-* rồi
        // bắt sự kiện bằng delegate.
        h += '<button class="btn btn-sm btn-outline-danger js-xoa-bo"' +
             ' data-id="' + r.bo_id + '" data-ten="' + APP.escape(r.ten_bo || '') + '"' +
             ' title="Xóa bộ">' + APP.icon('trash', 15) + '</button>';
    }
    return h;
}

function yeuCauBo(r) {
    var h = '';
    if (CO_YC_CHUNG && r.yeu_cau_chung) {
        h += '<div class="yc-bo"><span class="yc-nhan">Yêu cầu chung</span>' + APP.escape(r.yeu_cau_chung) + '</div>';
    }
    if (CO_YC_CHUNG && r.yeu_cau_khac) {
        h += '<div class="yc-bo"><span class="yc-nhan">Yêu cầu khác</span>' + APP.escape(r.yeu_cau_khac) + '</div>';
    }
    if (CO_YC_CAU_HINH && r.yeu_cau_cau_hinh) {
        h += '<div class="yc-bo"><span class="yc-nhan">Yêu cầu cấu hình</span>' + APP.escape(r.yeu_cau_cau_hinh) + '</div>';
    }
    if (r.nhom_nuoc_bo) {
        h += '<div class="yc-bo"><span class="yc-nhan">Nhóm nước</span>' + APP.escape(r.nhom_nuoc_bo) + '</div>';
    }
    return h;
}

/* ---------- Ô chọn BỘ ----------
   Nạp mỗi lần mở form vì bộ có thể vừa được import xong. */
function napComboBo(chon, xong) {
    APP.ajax(AJAX_URL, { action: 'getComboBo', goi_thau_id: GOI_THAU_ID }, {
        success: function (res) {
            var ds = res.data || [];
            var h = '<option value="">— Hàng lẻ (không thuộc bộ nào) —</option>';
            for (var i = 0; i < ds.length; i++) {
                var nhan = (ds[i].stt_bo ? ds[i].stt_bo + '. ' : '') +
                           (ds[i].ten_bo || '(chưa đặt tên)') +
                           (ds[i].ma_bo ? ' [' + ds[i].ma_bo + ']' : '');
                h += '<option value="' + ds[i].id + '">' + APP.escape(nhan) + '</option>';
            }
            $('#bo_id').html(h).val(chon || '');

            // Chưa có bộ nào -> nói rõ để người dùng biết phải import trước
            $('#boHint').text(ds.length
                ? 'Vật tư, dược phần lớn là hàng lẻ. Bộ dụng cụ / hệ thống thì chọn bộ tương ứng.'
                : 'Gói thầu chưa có bộ nào — import danh mục theo Phụ lục III để tạo bộ.');
            if (xong) xong();
        },
        error: function () { if (xong) xong(); }
    });
}

function openCreate() {
    $('#modalTitle').text('Thêm hàng hóa');
    $('#form')[0].reset();
    $('#id').val('');
    $('#thu_tu').val('0');
    $('#goi_thau_id').val(GOI_THAU_ID);
    $('#so_luong').val(0);
    napComboBo('');
    capNhatGoiYSL();
    APP.clearFieldErrors('#form');
    $('#modal').addClass('open');
    $('#ten_hang_hoa').trigger('focus');
}

/* Nhóm mua theo BỘ thì SL bắt buộc > 0 — nói trước để khỏi bị chặn lúc lưu */
function capNhatGoiYSL() {
    var ep = NHOM !== 'vat_tu_duoc';
    $('#slHint').html(ep
        ? '<strong>Bắt buộc lớn hơn 0</strong> — nhóm này mua theo bộ, thiếu số lượng '
          + 'của một chi tiết là không dựng được giá cả bộ.'
        : 'Dùng để tính thành tiền = đơn giá × số lượng.');
}

function edit(id) {
    APP.ajax(AJAX_URL, { action: 'getById', id: id }, {
        success: function (res) {
            var d = res.data;
            $('#modalTitle').text('Sửa hàng hóa');
            $('#id').val(d.id);
            $('#goi_thau_id').val(d.goi_thau_id);
            $('#thu_tu').val(d.thu_tu || 0);
            $('#ma_hh').val(d.ma_hh || '');
            $('#ten_hang_hoa').val(d.ten_hang_hoa || '');
            $('#thong_so_ky_thuat').val(d.thong_so_ky_thuat || '');
            $('#nhom_nuoc').val(d.nhom_nuoc || '');
            $('#stt_chi_tiet').val(d.stt_chi_tiet || '');
            $('#dvt').val(d.dvt || '');
            $('#so_luong').val(d.so_luong || 0);
            napComboBo(d.bo_id || '');
            capNhatGoiYSL();
            APP.clearFieldErrors('#form');
            $('#modal').addClass('open');
        }
    });
}

/* ============ THÊM / SỬA BỘ ============
   Yêu cầu chung / khác / cấu hình gắn với BỘ. Ô nào hiện là do NHÓM gói thầu
   quyết định — cùng nguồn với file mẫu và bảng tổng hợp (BG_Nhom_PUBLIC). */

function moThemBo() {
    $('#boTitle').text('Thêm bộ');
    $('#formBo')[0].reset();
    $('#bo_id_edit').val('');
    $('#bo_so_luong').val(0);
    hienOTheoNhom();
    APP.clearFieldErrors('#formBo');
    $('#boModal').addClass('open');
    $('#bo_ten').trigger('focus');
}

function suaBo(id) {
    APP.ajax(AJAX_URL, { action: 'getBo', id: id }, {
        success: function (res) {
            var d = res.data;
            $('#boTitle').text('Sửa bộ');
            $('#bo_id_edit').val(d.id);
            $('#bo_ma').val(d.ma_bo || '');
            $('#bo_stt').val(d.stt_bo || '');
            $('#bo_ten').val(d.ten_bo || '');
            $('#bo_yc_chung').val(d.yeu_cau_chung || '');
            $('#bo_yc_khac').val(d.yeu_cau_khac || '');
            $('#bo_yc_cau_hinh').val(d.yeu_cau_cau_hinh || '');
            $('#bo_nhom_nuoc').val(d.nhom_nuoc || '');
            $('#bo_dvt').val(d.dvt || '');
            $('#bo_so_luong').val(d.so_luong || 0);
            hienOTheoNhom();
            APP.clearFieldErrors('#formBo');
            $('#boModal').addClass('open');
        }
    });
}

/** Ẩn/hiện ô yêu cầu theo nhóm + gợi ý số lượng */
function hienOTheoNhom() {
    $('#grYcChung').prop('hidden', !CO_YC_CHUNG);
    $('#grYcKhac').prop('hidden', !CO_YC_CHUNG);
    $('#grYcCauHinh').prop('hidden', !CO_YC_CAU_HINH);

    $('#boSlHint').html(NHOM !== 'vat_tu_duoc'
        ? '<strong>Bắt buộc lớn hơn 0</strong>'
        : 'Số lượng của cả bộ.');
}

function dongModalBo() { $('#boModal').removeClass('open'); }

function luuBo() {
    var data = APP.serializeForm('#formBo');
    data.action = 'luuBo';
    APP.ajax(AJAX_URL, data, {
        success: function (res) {
            APP.toast(res.message, 'success');
            dongModalBo();
            loadData();
        }
    });
    return false;
}

$(document).on('click', '.js-xoa-bo', function () {
    var $b = $(this);
    xoaBo(parseInt($b.data('id'), 10), String($b.data('ten') || ''));
});

function xoaBo(id, ten) {
    APP.confirm('Xóa bộ "' + ten + '"?\n\nChỉ xóa được khi bộ không còn hàng hóa nào.',
        function () {
            APP.ajax(AJAX_URL, { action: 'xoaBo', id: id }, {
                success: function (res) { APP.toast(res.message, 'success'); loadData(); }
            });
        }, { yesText: 'Xóa bộ', yesClass: 'btn-danger' });
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
                '<td>' + APP.escape(r.ma_hh || '—') + '</td>' +
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
