<?php
require_once __DIR__ . '/../DAL/BG_GoiThau_DAL.php';
require_once __DIR__ . '/../DAL/BG_HangHoa_DAL.php';
require_once __DIR__ . '/../DAL/DM_NhatKyHeThong_DAL.php';

class BG_GoiThau_BUS
{
    const MODULE_KEY = 'BG_GoiThau';
    const MODULE_LOG = 'BaoGia';

    /** Sinh token QR không đoán được, đảm bảo không trùng */
    public static function sinhToken(): string
    {
        do {
            $token = bin2hex(random_bytes(16));   // 32 ký tự hex
        } while (BG_GoiThau_DAL::tokenExists($token));
        return $token;
    }

    /** URL cổng chào giá cho nhà thầu (nội dung nhúng vào QR) */
    public static function urlPortal(string $token): string
    {
        return AppConfig::baseUrl('GUI/portal/index.php') . '?t=' . urlencode($token);
    }

    private static function validate(BG_GoiThau_PUBLIC $e, bool $isUpdate = false): string
    {
        $e->so_thong_bao = trim($e->so_thong_bao);
        $e->ten_goi_thau = trim($e->ten_goi_thau);

        if ($e->so_thong_bao === '') return 'Số thông báo không được để trống';
        if (mb_strlen($e->so_thong_bao) > 100) return 'Số thông báo tối đa 100 ký tự';
        if ($e->ten_goi_thau === '') return 'Tên gói thầu không được để trống';
        if (mb_strlen($e->ten_goi_thau) > 500) return 'Tên gói thầu tối đa 500 ký tự';

        // Khoảng thời gian nhận báo giá
        if ($e->thoi_gian_mo_bao_gia && $e->thoi_gian_dong_bao_gia
            && $e->thoi_gian_dong_bao_gia <= $e->thoi_gian_mo_bao_gia) {
            return 'Thời gian đóng báo giá phải sau thời gian mở báo giá';
        }
        if ($e->trang_thai === BG_GoiThau_PUBLIC::TT_DANG_MO && empty($e->thoi_gian_dong_bao_gia)) {
            return 'Gói thầu đang mở phải có thời gian đóng báo giá';
        }
        if ($e->ngay_phat_hanh && $e->thoi_gian_mo_bao_gia
            && substr($e->thoi_gian_mo_bao_gia, 0, 10) < $e->ngay_phat_hanh) {
            return 'Thời gian mở báo giá không được trước ngày phát hành';
        }

        if ($e->ngay_phat_hanh && $e->han_cuoi && $e->han_cuoi < $e->ngay_phat_hanh) {
            return 'Hạn cuối phải sau ngày phát hành';
        }
        if ($e->thoi_gian_hop_dong < 0 || $e->thoi_gian_hop_dong > 600) {
            return 'Thời gian hợp đồng không hợp lệ (0-600 tháng)';
        }
        if ($e->hieu_luc_bao_gia < 0 || $e->hieu_luc_bao_gia > 3650) {
            return 'Hiệu lực báo giá không hợp lệ (0-3650 ngày)';
        }
        if (!array_key_exists($e->trang_thai, BG_GoiThau_PUBLIC::danhSachTrangThai())) {
            return 'Trạng thái không hợp lệ';
        }

        $excludeId = $isUpdate ? (int)$e->id : 0;
        if (BG_GoiThau_DAL::checkSoThongBaoExists($e->so_thong_bao, $excludeId)) {
            return 'Số thông báo này đã tồn tại';
        }
        return '';
    }

    public static function insert(BG_GoiThau_PUBLIC $e): array
    {
        $err = self::validate($e);
        if ($err !== '') return ['success' => false, 'message' => $err];

        try {
            $e->token = self::sinhToken();
            $id = BG_GoiThau_DAL::insert($e);
            DM_NhatKyHeThong_DAL::log(
                $e->nguoi_tao ?? 0, self::MODULE_LOG,
                "Thêm gói thầu: {$e->so_thong_bao}", 'bg_goi_thau', $id
            );
            return ['success' => true, 'message' => 'Thêm gói thầu thành công', 'data' => ['id' => $id]];
        } catch (Throwable $ex) {
            return ['success' => false, 'message' => 'Lỗi: ' . $ex->getMessage()];
        }
    }

    public static function update(BG_GoiThau_PUBLIC $e): array
    {
        if (!$e->id) return ['success' => false, 'message' => 'Thiếu ID'];
        $cu = BG_GoiThau_DAL::getById((int)$e->id);
        if (!$cu || $cu->da_xoa === 1) return ['success' => false, 'message' => 'Không tìm thấy gói thầu'];

        $err = self::validate($e, true);
        if ($err !== '') return ['success' => false, 'message' => $err];

        try {
            BG_GoiThau_DAL::update($e);
            DM_NhatKyHeThong_DAL::log(
                $e->nguoi_cap_nhat ?? 0, self::MODULE_LOG,
                "Sửa gói thầu: {$e->so_thong_bao}", 'bg_goi_thau', $e->id
            );
            return ['success' => true, 'message' => 'Cập nhật gói thầu thành công'];
        } catch (Throwable $ex) {
            return ['success' => false, 'message' => 'Lỗi: ' . $ex->getMessage()];
        }
    }

    /** Đổi trạng thái: mở / đóng nhận báo giá */
    public static function doiTrangThai(int $id, int $trangThai, int $u): array
    {
        if ($id <= 0) return ['success' => false, 'message' => 'Thiếu ID'];
        if (!array_key_exists($trangThai, BG_GoiThau_PUBLIC::danhSachTrangThai())) {
            return ['success' => false, 'message' => 'Trạng thái không hợp lệ'];
        }
        $gt = BG_GoiThau_DAL::getById($id);
        if (!$gt || $gt->da_xoa === 1) return ['success' => false, 'message' => 'Không tìm thấy gói thầu'];

        if ($trangThai === BG_GoiThau_PUBLIC::TT_DANG_MO && (int)$gt->so_hang_hoa === 0) {
            return ['success' => false, 'message' => 'Chưa có hàng hóa nào — không thể mở nhận báo giá'];
        }

        BG_GoiThau_DAL::updateTrangThai($id, $trangThai, $u);
        $ten = BG_GoiThau_PUBLIC::tenTrangThai($trangThai);
        DM_NhatKyHeThong_DAL::log(
            $u, self::MODULE_LOG,
            "Đổi trạng thái gói thầu {$gt->so_thong_bao} → {$ten}", 'bg_goi_thau', $id
        );
        return ['success' => true, 'message' => "Đã chuyển trạng thái sang: {$ten}"];
    }

    /** Sinh lại token → link QR cũ hết hiệu lực */
    public static function lamMoiToken(int $id, int $u): array
    {
        if ($id <= 0) return ['success' => false, 'message' => 'Thiếu ID'];
        $gt = BG_GoiThau_DAL::getById($id);
        if (!$gt || $gt->da_xoa === 1) return ['success' => false, 'message' => 'Không tìm thấy gói thầu'];

        $token = self::sinhToken();
        BG_GoiThau_DAL::updateToken($id, $token, $u);
        DM_NhatKyHeThong_DAL::log(
            $u, self::MODULE_LOG,
            "Làm mới link QR gói thầu {$gt->so_thong_bao}", 'bg_goi_thau', $id
        );
        return ['success' => true, 'message' => 'Đã tạo link QR mới, link cũ không còn dùng được', 'data' => [
            'token' => $token,
            'url'   => self::urlPortal($token),
        ]];
    }

    public static function trash(int $id, int $u): array
    {
        if ($id <= 0) return ['success' => false, 'message' => 'Thiếu ID'];
        $gt = BG_GoiThau_DAL::getById($id);
        if (!$gt) return ['success' => false, 'message' => 'Không tìm thấy gói thầu'];
        if ((int)$gt->so_bao_gia > 0) {
            return ['success' => false, 'message' => 'Gói thầu đã có báo giá — không thể xóa. Hãy đóng gói thầu.'];
        }

        BG_GoiThau_DAL::softDelete($id, $u);
        DM_NhatKyHeThong_DAL::log(
            $u, self::MODULE_LOG,
            "Xóa tạm gói thầu: {$gt->so_thong_bao}", 'bg_goi_thau', $id
        );
        return ['success' => true, 'message' => 'Đã chuyển vào thùng rác'];
    }

    public static function restore(int $id, int $u): array
    {
        if ($id <= 0) return ['success' => false, 'message' => 'Thiếu ID'];
        $gt = BG_GoiThau_DAL::getById($id);
        if (!$gt) return ['success' => false, 'message' => 'Không tìm thấy gói thầu'];
        // UNIQUE(so_thong_bao, da_xoa) → chặn nếu số thông báo đã bị dùng lại
        if (BG_GoiThau_DAL::checkSoThongBaoExists($gt->so_thong_bao, $id)) {
            return ['success' => false, 'message' => 'Số thông báo đã được dùng lại, không thể khôi phục'];
        }
        BG_GoiThau_DAL::restore($id, $u);
        DM_NhatKyHeThong_DAL::log(
            $u, self::MODULE_LOG, "Khôi phục gói thầu: {$gt->so_thong_bao}", 'bg_goi_thau', $id
        );
        return ['success' => true, 'message' => 'Đã khôi phục gói thầu'];
    }

    public static function delete(int $id, int $u): array
    {
        if ($id <= 0) return ['success' => false, 'message' => 'Thiếu ID'];
        $gt = BG_GoiThau_DAL::getById($id);
        if (!$gt) return ['success' => false, 'message' => 'Không tìm thấy gói thầu'];
        if ((int)$gt->so_bao_gia > 0) {
            return ['success' => false, 'message' => 'Gói thầu đã có báo giá — không thể xóa vĩnh viễn'];
        }

        // Ghi 2 bảng (hàng hóa + gói thầu) → bọc transaction
        try {
            Database::beginTransaction();
            BG_HangHoa_DAL::softDeleteByGoiThau($id, $u);
            $n = BG_GoiThau_DAL::delete($id);
            Database::commit();

            if ($n === 0) {
                return ['success' => false, 'message' => 'Chỉ xóa vĩnh viễn được bản ghi trong thùng rác'];
            }
            DM_NhatKyHeThong_DAL::log(
                $u, self::MODULE_LOG,
                "Xóa vĩnh viễn gói thầu: {$gt->so_thong_bao}", 'bg_goi_thau', $id
            );
            return ['success' => true, 'message' => 'Đã xóa vĩnh viễn'];
        } catch (Throwable $ex) {
            Database::rollBack();
            return ['success' => false, 'message' => 'Lỗi: ' . $ex->getMessage()];
        }
    }

    public static function getById(int $id): ?BG_GoiThau_PUBLIC
    {
        return BG_GoiThau_DAL::getById($id);
    }

    public static function getPaged(
        int $page,
        int $pageSize,
        string $search = '',
        int $daXoa = 0,
        int $trangThai = -1,
        string $trangThaiBaoGia = ''
    ): array {
        // Chỉ nhận mã trạng thái báo giá hợp lệ (whitelist) — không tin input
        if ($trangThaiBaoGia !== ''
            && !array_key_exists($trangThaiBaoGia, BG_GoiThau_PUBLIC::danhSachTrangThaiBaoGia())) {
            $trangThaiBaoGia = '';
        }

        $res = BG_GoiThau_DAL::getPaged($page, $pageSize, $search, $daXoa, $trangThai, $trangThaiBaoGia);

        // Bổ sung trạng thái báo giá đã tính sẵn để GUI khỏi lặp lại logic
        foreach ($res['data'] as &$r) {
            $r['trang_thai_bao_gia'] = BG_GoiThau_PUBLIC::tinhTrangThaiBaoGia(
                (int)$r['trang_thai'],
                $r['thoi_gian_mo_bao_gia'] ?? null,
                $r['thoi_gian_dong_bao_gia'] ?? null
            );
            $r['ten_trang_thai_bao_gia'] = BG_GoiThau_PUBLIC::tenTrangThaiBaoGia($r['trang_thai_bao_gia']);
        }
        unset($r);

        return $res;
    }

    public static function getCombo(): array
    {
        return BG_GoiThau_DAL::getCombo();
    }

    /**
     * Gói thầu có còn nhận báo giá được không (dùng ở cổng nhà thầu).
     *
     * Trạng thái tính bởi BG_GoiThau_PUBLIC::tinhTrangThaiBaoGia() — nguồn duy nhất.
     * Trả thêm `trang_thai_bao_gia` để cổng nhà thầu biết nên hiện form điền giá
     * hay chuyển sang chế độ chỉ tra cứu.
     *
     * @return array ['ok' => bool, 'message' => string, 'trang_thai_bao_gia' => string]
     */
    public static function kiemTraConNhan(BG_GoiThau_PUBLIC $gt): array
    {
        $tt = $gt->trangThaiBaoGia();

        $ket = static function (bool $ok, string $msg) use ($tt): array {
            return ['ok' => $ok, 'message' => $msg, 'trang_thai_bao_gia' => $tt];
        };

        switch ($tt) {
            case BG_GoiThau_PUBLIC::BG_CHUA_MO:
                return $ket(false, 'Chưa tới thời gian nhận báo giá. Thời gian mở: '
                    . Helper::formatDateTime($gt->thoi_gian_mo_bao_gia) . '.');

            case BG_GoiThau_PUBLIC::BG_HET_HAN:
                return $ket(false, 'Đã hết thời gian nhận báo giá (đóng lúc '
                    . Helper::formatDateTime($gt->thoi_gian_dong_bao_gia) . ').');

            case BG_GoiThau_PUBLIC::BG_KHONG_NHAN:
                return $ket(false, (int)$gt->trang_thai === BG_GoiThau_PUBLIC::TT_NHAP
                    ? 'Gói thầu chưa được mở nhận báo giá.'
                    : 'Gói thầu đã đóng, không còn nhận báo giá.');
        }

        // BG_DANG_MO — còn 1 điều kiện nghiệp vụ: phải có danh mục hàng hóa
        if ((int)$gt->so_hang_hoa === 0) {
            return $ket(false, 'Gói thầu chưa có danh mục hàng hóa.');
        }
        return $ket(true, '');
    }

    public static function thongKe(): array
    {
        return BG_GoiThau_DAL::thongKe();
    }
}
