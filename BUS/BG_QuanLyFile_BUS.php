<?php
require_once __DIR__ . '/../DAL/BG_File_DAL.php';
require_once __DIR__ . '/../DAL/BG_BaoGia_DAL.php';
require_once __DIR__ . '/../DAL/DM_NhatKyHeThong_DAL.php';
require_once __DIR__ . '/BG_BaoGia_BUS.php';

/**
 * BG_QuanLyFile_BUS — Quản lý file người dùng tải lên (bảng `bg_file`).
 *
 * Việc module này làm: liệt kê, lọc, thống kê dung lượng, xem/tải,
 * xóa file, và dò file mồ côi (có trên đĩa nhưng DB không tham chiếu).
 *
 * File bản ký là TÀI LIỆU PHÁP LÝ nên mọi thao tác xóa đều ghi nhật ký.
 */
class BG_QuanLyFile_BUS
{
    const MODULE_KEY = 'BG_QuanLyFile';
    const MODULE_LOG = 'QuanLyFile';

    public static function getPaged(
        int $page,
        int $pageSize,
        int $goiThauId = 0,
        string $search = '',
        string $loaiFile = '',
        string $sapXep = 'moi_nhat'
    ): array {
        $res = BG_File_DAL::getPagedBanKy($page, $pageSize, $goiThauId, $search, $loaiFile, $sapXep);

        // Bổ sung thông tin suy ra để GUI khỏi tự tính
        $dir = BG_BaoGia_BUS::thuMucBanKy();
        foreach ($res['data'] as &$r) {
            $ten = basename((string)$r['ten_file']);
            $r['la_anh']         = in_array(strtolower((string)$r['loai_file']), ['jpg', 'jpeg', 'png'], true);
            $r['kich_thuoc_dep'] = BG_File_PUBLIC::dinhDangDungLuong((int)$r['kich_thuoc']);
            // Cảnh báo khi DB có bản ghi nhưng file đã biến mất khỏi đĩa
            $r['file_ton_tai']   = is_file($dir . DIRECTORY_SEPARATOR . $ten);
        }
        unset($r);

        return $res;
    }

    public static function thongKe(int $goiThauId = 0): array
    {
        $tk = BG_File_DAL::thongKeBanKy($goiThauId);
        $tk['dung_luong_dep'] = BG_File_PUBLIC::dinhDangDungLuong($tk['tong_dung_luong']);
        return $tk;
    }

    /** Byte → chuỗi dễ đọc (KB/MB) */
    public static function dinhDangDungLuong(int $byte): string
    {
        return BG_File_PUBLIC::dinhDangDungLuong($byte);
    }

    /**
     * Xóa file bản ký của 1 báo giá.
     *
     * Xóa file thì báo giá KHÔNG còn bằng chứng xác nhận nữa → đưa về
     * "Chờ xác nhận" nếu trước đó do chính bản ký xác nhận (nguoi_xac_nhan = NULL).
     * Nếu nhân viên đã tích tay xác nhận thì giữ nguyên trạng thái.
     */
    public static function xoaFile(int $baoGiaId, int $u): array
    {
        $bg = BG_BaoGia_DAL::getById($baoGiaId);
        if (!$bg || (int)$bg->da_xoa === 1) {
            return ['success' => false, 'message' => 'Không tìm thấy báo giá'];
        }
        $fileId = (int)($bg->file_ban_ky_id ?? 0);
        if ($fileId <= 0) {
            return ['success' => false, 'message' => 'Báo giá này không có file bản ký'];
        }

        $f = BG_File_DAL::getById($fileId);
        $tenFile = $f ? $f->ten_file : '';
        $tenGoc  = $f ? (string)$f->ten_file_goc : '';

        try {
            Database::beginTransaction();

            // Nhà thầu tự xác nhận bằng bản ký (nguoi_xac_nhan = NULL) → bỏ xác nhận
            $tuKy = $bg->nguoi_xac_nhan === null
                 && (int)$bg->trang_thai === BG_BaoGia_PUBLIC::TT_DA_XAC_NHAN;

            BG_BaoGia_DAL::xoaBanKy($baoGiaId, $u);
            BG_File_DAL::softDelete($fileId, $u);
            if ($tuKy) {
                BG_BaoGia_DAL::updateXacNhan($baoGiaId, BG_BaoGia_PUBLIC::TT_CHO_XAC_NHAN, null, $u);
            }

            DM_NhatKyHeThong_DAL::log(
                $u, self::MODULE_LOG,
                "Xóa file bản ký của {$bg->ten_cong_ty} (MST {$bg->ma_so_thue}), file: {$tenGoc}"
                . ($tuKy ? ' — báo giá trở lại Chờ xác nhận' : ''),
                'bg_file', $fileId
            );

            Database::commit();
        } catch (Throwable $ex) {
            Database::rollBack();
            return ['success' => false, 'message' => 'Lỗi: ' . $ex->getMessage()];
        }

        // Xóa file vật lý SAU khi DB đã commit — nếu DB lỗi thì file vẫn còn,
        // hơn là xóa file trước rồi DB rollback làm mất file mà bản ghi vẫn trỏ tới.
        if ($tenFile !== '') {
            $duongDan = BG_BaoGia_BUS::thuMucBanKy() . DIRECTORY_SEPARATOR . basename($tenFile);
            if (is_file($duongDan)) @unlink($duongDan);
        }

        return [
            'success' => true,
            'message' => $tuKy
                ? 'Đã xóa file bản ký. Báo giá trở lại trạng thái Chờ xác nhận.'
                : 'Đã xóa file bản ký.',
        ];
    }

    /**
     * Dò file mồ côi: nằm trong thư mục ban_ky nhưng KHÔNG bản ghi bg_file nào trỏ tới.
     *
     * Sinh ra khi: xóa báo giá vĩnh viễn, upload đè lỗi giữa chừng, hoặc
     * ai đó copy file vào thủ công.
     */
    public static function timFileMoCoi(): array
    {
        $dir = BG_BaoGia_BUS::thuMucBanKy();
        // Lấy CẢ bản ghi đã soft delete — file của chúng vẫn cần giữ để tra cứu,
        // không được coi là mồ côi.
        $dangDung = BG_File_DAL::tatCaTenFile();

        $moCoi = [];
        foreach ((array)glob($dir . DIRECTORY_SEPARATOR . '*') as $p) {
            if (!is_file($p)) continue;
            $ten = basename($p);
            if ($ten === '.htaccess' || $ten === 'index.html') continue;
            if (isset($dangDung[$ten])) continue;

            $moCoi[] = [
                'ten_file'       => $ten,
                'kich_thuoc'     => filesize($p),
                'kich_thuoc_dep' => BG_File_PUBLIC::dinhDangDungLuong((int)filesize($p)),
                'ngay_sua'       => date('Y-m-d H:i:s', (int)filemtime($p)),
            ];
        }

        usort($moCoi, static fn($a, $b) => strcmp($b['ngay_sua'], $a['ngay_sua']));
        return $moCoi;
    }

    /**
     * Xóa 1 file mồ côi.
     * Chỉ nhận tên file (basename) và phải nằm trong danh sách mồ côi thật sự
     * → không xóa nhầm file đang được bản ghi bg_file tham chiếu.
     */
    public static function xoaFileMoCoi(string $tenFile, int $u): array
    {
        $tenGoc = trim($tenFile);
        $ten = basename($tenGoc);

        // basename() đã chặn path traversal, nhưng nếu input KHÁC kết quả basename
        // thì rõ ràng có ý đồ đi ra ngoài thư mục → báo đúng lỗi, đừng để rơi
        // xuống nhánh dưới rồi trả message sai ("file đang được sử dụng").
        if ($ten === '' || $ten !== $tenGoc || $ten === '.htaccess') {
            return ['success' => false, 'message' => 'Tên file không hợp lệ'];
        }

        // Phải thật sự mồ côi — chặn truyền tên file đang dùng vào để xóa
        $dsMoCoi = array_column(self::timFileMoCoi(), 'ten_file');
        if (!in_array($ten, $dsMoCoi, true)) {
            return ['success' => false, 'message' => 'File này đang được một báo giá sử dụng, không thể xóa ở đây'];
        }

        $duongDan = BG_BaoGia_BUS::thuMucBanKy() . DIRECTORY_SEPARATOR . $ten;
        if (!is_file($duongDan)) {
            return ['success' => false, 'message' => 'File không còn trên hệ thống'];
        }
        if (!@unlink($duongDan)) {
            return ['success' => false, 'message' => 'Không xóa được file'];
        }

        DM_NhatKyHeThong_DAL::log(
            $u, self::MODULE_LOG, "Xóa file mồ côi: {$ten}", 'file', 0
        );
        return ['success' => true, 'message' => 'Đã xóa file mồ côi'];
    }

    /** Thông tin 1 file để hiển thị chi tiết (theo id BÁO GIÁ) */
    public static function getById(int $baoGiaId): ?array
    {
        $r = BG_File_DAL::getBanKyByBaoGia($baoGiaId);
        if (!$r) return null;

        $ten = basename((string)$r['ten_file']);
        $r['la_anh']         = in_array(strtolower((string)$r['loai_file']), ['jpg', 'jpeg', 'png'], true);
        $r['kich_thuoc_dep'] = BG_File_PUBLIC::dinhDangDungLuong((int)$r['kich_thuoc']);
        $r['file_ton_tai']   = is_file(BG_BaoGia_BUS::thuMucBanKy() . DIRECTORY_SEPARATOR . $ten);
        return $r;
    }
}
