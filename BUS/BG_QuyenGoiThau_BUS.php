<?php
/**
 * BG_QuyenGoiThau_BUS — Phân quyền XEM theo từng gói thầu.
 *
 * Quy tắc (§3B.1): nhóm `la_admin` và nhóm Quản lý xem MỌI gói thầu, không
 * cần gán. Người dùng khác chỉ xem gói được gán đích danh; gói chưa gán ai
 * thì họ không thấy.
 */
require_once __DIR__ . '/../DAL/BG_QuyenGoiThau_DAL.php';
require_once __DIR__ . '/../DAL/BG_GoiThau_DAL.php';
require_once __DIR__ . '/../DAL/DM_NhatKyHeThong_DAL.php';

class BG_QuyenGoiThau_BUS
{
    const MODULE_KEY = 'BG_QuyenGoiThau';
    const MODULE_LOG = 'QuyenGoiThau';

    /**
     * Danh sách người dùng kèm cờ "được xem gói này".
     * Người thuộc nhóm xem-tất-cả được đánh dấu riêng để GUI khóa ô chọn lại —
     * tick hay không cũng không đổi được gì, tick được sẽ gây hiểu nhầm.
     */
    public static function danhSachPhanQuyen(int $goiThauId): array
    {
        $gt = BG_GoiThau_DAL::getById($goiThauId);
        if (!$gt) return [];

        $duocGan = array_flip(BG_QuyenGoiThau_DAL::nguoiDungCuaGoi($goiThauId));

        $sql = "SELECT nd.id, nd.tai_khoan, nd.trang_thai,
                       nt.ten_nhom, nt.ma_nhom, nt.la_admin
                FROM dm_nguoi_dung nd
                LEFT JOIN dm_nhom_tai_khoan nt ON nt.id = nd.nhom_tai_khoan_id
                WHERE nd.da_xoa = 0
                ORDER BY nt.la_admin DESC, nt.ten_nhom, nd.tai_khoan";
        $rows = Database::getConnection()->query($sql)->fetchAll();

        $out = [];
        foreach ($rows as $r) {
            // Nhà thầu dùng tài khoản chung, không thuộc diện phân quyền nội bộ
            if (($r['ma_nhom'] ?? '') === 'NHATHAU') continue;

            $xemTatCa = (int)($r['la_admin'] ?? 0) === 1
                     || ($r['ma_nhom'] ?? '') === BG_QuyenGoiThau_DAL::NHOM_XEM_TAT_CA;

            $out[] = [
                'id'         => (int)$r['id'],
                'tai_khoan'  => $r['tai_khoan'],
                'ten_nhom'   => $r['ten_nhom'] ?? '—',
                'trang_thai' => (int)$r['trang_thai'],
                'xem_tat_ca' => $xemTatCa,
                'duoc_xem'   => $xemTatCa || isset($duocGan[(int)$r['id']]),
            ];
        }
        return $out;
    }

    /** Lưu danh sách người được xem 1 gói thầu */
    public static function luu(int $goiThauId, array $nguoiDungIds, int $u): array
    {
        $gt = BG_GoiThau_DAL::getById($goiThauId);
        if (!$gt) {
            return ['success' => false, 'message' => 'Không tìm thấy gói thầu'];
        }

        // Chỉ nhận id người dùng CÓ THẬT và chưa xóa — không tin input
        $hopLe = [];
        $rows = Database::getConnection()
            ->query("SELECT id FROM dm_nguoi_dung WHERE da_xoa = 0")
            ->fetchAll(PDO::FETCH_COLUMN);
        foreach ($rows as $id) $hopLe[(int)$id] = true;

        $loc = [];
        foreach ($nguoiDungIds as $id) {
            $id = (int)$id;
            if ($id > 0 && isset($hopLe[$id])) $loc[] = $id;
        }

        try {
            BG_QuyenGoiThau_DAL::thayDanhSach($goiThauId, $loc, $u);
        } catch (Throwable $ex) {
            return ['success' => false, 'message' => 'Lỗi: ' . $ex->getMessage()];
        }

        DM_NhatKyHeThong_DAL::log(
            $u, self::MODULE_LOG,
            "Đổi phân quyền xem gói thầu {$gt->so_thong_bao}: " . count($loc) . " người dùng",
            'bg_goi_thau', $goiThauId
        );

        return [
            'success' => true,
            'message' => 'Đã lưu phân quyền cho ' . count($loc) . ' người dùng',
            'data'    => ['so_nguoi' => count($loc)],
        ];
    }

    /**
     * Chặn truy cập gói thầu không được phép — dùng ở trang GUI chi tiết.
     * Trả trang 403 giống PhanQuyenHelper::requireQuyenView().
     */
    public static function requireXem(int $goiThauId): void
    {
        if (BG_QuyenGoiThau_DAL::duocXem($goiThauId, (int)SessionHelper::userId())) {
            return;
        }

        http_response_code(403);
        $home = AppConfig::baseUrl('GUI/BG_GoiThau/index.php');
        $css  = AppConfig::baseUrl('assets/css/style.css');
        echo '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8">'
           . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
           . '<title>Không có quyền xem gói thầu</title>'
           . '<link rel="stylesheet" href="' . Helper::h($css) . '"></head><body>'
           . '<div style="max-width:460px;margin:12vh auto;padding:32px;text-align:center;'
           . 'background:#fff;border:1px solid #e2e8f0;border-radius:14px">'
           . '<div style="color:#dc2626;margin-bottom:12px">' . IconHelper::svg('lock', 40) . '</div>'
           . '<h1 style="font-size:18px;margin-bottom:8px">Không có quyền xem gói thầu này</h1>'
           . '<p style="color:#64748b;font-size:14px;margin:0 0 20px">'
           . 'Gói thầu này chưa được phân quyền cho tài khoản của bạn. '
           . 'Liên hệ quản trị viên nếu cần.</p>'
           . '<a class="btn btn-primary" href="' . Helper::h($home) . '">Về danh sách gói thầu</a>'
           . '</div></body></html>';
        exit;
    }

    /** Kiểm tra quyền cho AJAX — trả JSON 403 thay vì trang HTML */
    public static function requireXemAjax(int $goiThauId): void
    {
        if (BG_QuyenGoiThau_DAL::duocXem($goiThauId, (int)SessionHelper::userId())) {
            return;
        }
        ResponseHelper::error('Bạn không có quyền xem gói thầu này.', 403);
    }
}
