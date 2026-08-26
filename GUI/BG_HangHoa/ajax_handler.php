<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../BUS/BG_HangHoa_BUS.php';
require_once __DIR__ . '/../../BUS/BG_GoiThau_BUS.php';

Helper::requireAjaxCsrf();

// ============================================================
// CHAN THEO PHAN QUYEN GOI THAU (3B.1)
// Dat o DAY, truoc switch, de MOI action co goi_thau_id deu bi kiem tra —
// vaao tung case thi de bo sot khi them action moi.
// An nut tren giao dien khong phai la bao mat.
// ============================================================
require_once __DIR__ . '/../../BUS/BG_QuyenGoiThau_BUS.php';
$gtQuyen = Helper::postInt('goi_thau_id', 0);
if ($gtQuyen > 0) {
    BG_QuyenGoiThau_BUS::requireXemAjax($gtQuyen);
}

$action = Helper::post('action', '');
$u = SessionHelper::userId();
$MODULE = BG_HangHoa_BUS::MODULE_KEY;

/**
 * Nhận file upload an toàn (§3B.9): whitelist đuôi, kiểm tra MIME thật,
 * giới hạn dung lượng, đổi tên khi lưu.
 * @return string đường dẫn file tạm đã lưu
 */
function nhanFileExcel(string $field): string
{
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
        throw new RuntimeException('Chưa chọn file');
    }
    $f = $_FILES[$field];

    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $map = [
            UPLOAD_ERR_INI_SIZE   => 'File vượt quá giới hạn của server',
            UPLOAD_ERR_FORM_SIZE  => 'File vượt quá giới hạn cho phép',
            UPLOAD_ERR_PARTIAL    => 'File tải lên chưa hoàn tất, hãy thử lại',
            UPLOAD_ERR_NO_FILE    => 'Chưa chọn file',
            UPLOAD_ERR_NO_TMP_DIR => 'Server thiếu thư mục tạm',
            UPLOAD_ERR_CANT_WRITE => 'Server không ghi được file tạm',
        ];
        throw new RuntimeException($map[$f['error']] ?? 'Lỗi tải file');
    }
    if (!is_uploaded_file($f['tmp_name'])) {
        throw new RuntimeException('File không hợp lệ');
    }
    if ((int)$f['size'] <= 0) {
        throw new RuntimeException('File rỗng');
    }
    if ((int)$f['size'] > AppConfig::UPLOAD_MAX_SIZE) {
        throw new RuntimeException('File tối đa ' . round(AppConfig::UPLOAD_MAX_SIZE / 1048576) . 'MB');
    }

    // Whitelist đuôi — chỉ nhận xlsx (reader chỉ đọc được Office Open XML)
    $ext = strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION));
    if ($ext !== 'xlsx') {
        throw new RuntimeException('Chỉ nhận file .xlsx (Excel 2007 trở lên). File .xls cũ hãy lưu lại thành .xlsx.');
    }

    // Kiểm tra MIME thật, KHÔNG tin $_FILES['type'] (client giả được)
    $mimeOk = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'];
    if (function_exists('finfo_open')) {
        $fi = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($fi, $f['tmp_name']);
        finfo_close($fi);
        if (!in_array($mime, $mimeOk, true)) {
            throw new RuntimeException('Nội dung file không phải Excel .xlsx hợp lệ');
        }
    }

    // Đổi tên khi lưu — không giữ tên gốc từ user
    $dest = BG_HangHoa_BUS::tempDir() . '/upload_' . Helper::randomString(20) . '.xlsx';
    if (!move_uploaded_file($f['tmp_name'], $dest)) {
        throw new RuntimeException('Không lưu được file tải lên');
    }
    return $dest;
}

try {
    switch ($action) {
        case 'getPaged':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XEM);
            $page = Helper::postInt('page', 1);
            $size = Helper::postInt('pageSize', AppConfig::DEFAULT_PAGE_SIZE);
            $res = BG_HangHoa_BUS::getPaged(
                $page,
                $size,
                Helper::postInt('goi_thau_id', 0),
                Helper::postStr('search'),
                Helper::postInt('da_xoa', 0)
            );
            ResponseHelper::paged($res['data'], $page, $size, $res['totalRecords']);
            break;

        case 'getById':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XEM);
            $hh = BG_HangHoa_BUS::getById(Helper::postInt('id'));
            if (!$hh) ResponseHelper::error('Không tìm thấy hàng hóa');
            ResponseHelper::success('OK', $hh);
            break;

        case 'insert':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_THEM);
            $e = new BG_HangHoa_PUBLIC();
            $e->goi_thau_id       = Helper::postInt('goi_thau_id');
            $e->ma_hh             = Helper::postStr('ma_hh');
            $e->ten_hang_hoa      = Helper::postStr('ten_hang_hoa');
            $e->thong_so_ky_thuat = (string)Helper::post('thong_so_ky_thuat', '');
            $e->dvt               = Helper::postStr('dvt');
            $e->so_luong          = (float)ExcelHelper::toNumber(Helper::post('so_luong', 0));
            $e->nguoi_tao         = $u;
            $res = BG_HangHoa_BUS::insert($e);
            $res['success']
                ? ResponseHelper::success($res['message'], $res['data'] ?? null)
                : ResponseHelper::error($res['message']);
            break;

        case 'update':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_SUA);
            $e = new BG_HangHoa_PUBLIC();
            $e->id                = Helper::postInt('id');
            $e->ma_hh             = Helper::postStr('ma_hh');
            $e->ten_hang_hoa      = Helper::postStr('ten_hang_hoa');
            $e->thong_so_ky_thuat = (string)Helper::post('thong_so_ky_thuat', '');
            $e->dvt               = Helper::postStr('dvt');
            $e->so_luong          = (float)ExcelHelper::toNumber(Helper::post('so_luong', 0));
            $e->thu_tu            = Helper::postInt('thu_tu', 0);
            $e->nguoi_cap_nhat    = $u;
            $res = BG_HangHoa_BUS::update($e);
            $res['success'] ? ResponseHelper::success($res['message']) : ResponseHelper::error($res['message']);
            break;

        /** Xem trước nội dung file Excel trước khi import thật */
        case 'previewImport':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_THEM);
            $path = nhanFileExcel('file');
            try {
                $res = BG_HangHoa_BUS::docFileExcel($path);
                if (!$res['success']) ResponseHelper::error($res['message']);
                // Chỉ trả 20 dòng đầu để xem trước, kèm tổng số
                ResponseHelper::success($res['message'], [
                    'tong_dong' => count($res['data']),
                    'xem_truoc' => array_slice($res['data'], 0, 20),
                    'canh_bao'  => $res['loi'] ?? [],
                ]);
            } finally {
                @unlink($path);
            }
            break;

        case 'import':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_THEM);
            $goiThauId = Helper::postInt('goi_thau_id');
            $ghiDe = Helper::postInt('ghi_de', 0) === 1;
            $path = nhanFileExcel('file');
            try {
                $res = BG_HangHoa_BUS::importExcel($goiThauId, $path, $ghiDe, $u);
                $res['success']
                    ? ResponseHelper::success($res['message'], $res['data'] ?? null)
                    : ResponseHelper::error($res['message']);
            } finally {
                @unlink($path);
            }
            break;

        case 'trash':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XOA);
            $res = BG_HangHoa_BUS::trash(Helper::postInt('id'), $u);
            $res['success'] ? ResponseHelper::success($res['message']) : ResponseHelper::error($res['message']);
            break;

        case 'restore':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_SUA);
            $res = BG_HangHoa_BUS::restore(Helper::postInt('id'), $u);
            $res['success'] ? ResponseHelper::success($res['message']) : ResponseHelper::error($res['message']);
            break;

        case 'getComboGoiThau':
            PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_XEM);
            ResponseHelper::success('OK', BG_GoiThau_BUS::getCombo());
            break;

        default:
            ResponseHelper::error('Action không hợp lệ');
    }
} catch (Throwable $ex) {
    ResponseHelper::error(AppConfig::APP_DEBUG ? $ex->getMessage() : 'Lỗi hệ thống', 500);
}
