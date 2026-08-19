<?php
require_once __DIR__ . '/../DAL/DM_NguoiDung_DAL.php';
require_once __DIR__ . '/../DAL/DM_DangNhapThatBai_DAL.php';
require_once __DIR__ . '/../DAL/DM_NhatKyHeThong_DAL.php';

class DM_NguoiDung_BUS
{
    const MODULE_KEY = 'DM_NguoiDung';

    /** Độ dài mật khẩu tối thiểu */
    const MIN_PASSWORD_LENGTH = 8;

    /**
     * Kiểm tra độ mạnh mật khẩu.
     * Trả '' nếu hợp lệ, ngược lại trả thông báo lỗi.
     */
    public static function validatePassword(string $pass): string
    {
        if (strlen($pass) < self::MIN_PASSWORD_LENGTH) {
            return 'Mật khẩu tối thiểu ' . self::MIN_PASSWORD_LENGTH . ' ký tự';
        }
        if (!preg_match('/[A-Za-z]/', $pass) || !preg_match('/[0-9]/', $pass)) {
            return 'Mật khẩu phải gồm cả chữ và số';
        }
        return '';
    }

    public static function insert(DM_NguoiDung_PUBLIC $e, string $matKhauThuong): array
    {
        $e->tai_khoan = trim($e->tai_khoan);
        if ($e->tai_khoan === '') return ['success' => false, 'message' => 'Tài khoản không được để trống'];
        if (!preg_match('/^[a-zA-Z0-9._]{3,50}$/', $e->tai_khoan)) {
            return ['success' => false, 'message' => 'Tài khoản chỉ gồm chữ, số, dấu chấm/gạch dưới (3-50 ký tự)'];
        }
        $errPass = self::validatePassword($matKhauThuong);
        if ($errPass !== '') return ['success' => false, 'message' => $errPass];
        if (DM_NguoiDung_DAL::checkTaiKhoanExists($e->tai_khoan)) {
            return ['success' => false, 'message' => 'Tài khoản đã tồn tại'];
        }
        if ((int)$e->nhom_tai_khoan_id <= 0) return ['success' => false, 'message' => 'Chưa chọn nhóm tài khoản'];

        $e->mat_khau = password_hash($matKhauThuong, AppConfig::PASSWORD_ALGO, ['cost' => AppConfig::PASSWORD_COST]);

        try {
            $id = DM_NguoiDung_DAL::insert($e);
            MemcachedHelper::deleteByPrefix('dm_nguoi_dung:');
            DM_NhatKyHeThong_DAL::log($e->nguoi_tao ?? 0, Constants::MODULE_HE_THONG, "Thêm người dùng: {$e->tai_khoan}", 'dm_nguoi_dung', $id);
            return ['success' => true, 'message' => 'Thêm người dùng thành công', 'data' => ['id' => $id]];
        } catch (Throwable $ex) {
            return ['success' => false, 'message' => 'Lỗi: ' . $ex->getMessage()];
        }
    }

    public static function update(DM_NguoiDung_PUBLIC $e): array
    {
        if (!$e->id) return ['success' => false, 'message' => 'Thiếu ID'];
        $e->tai_khoan = trim($e->tai_khoan);
        if ($e->tai_khoan === '') return ['success' => false, 'message' => 'Tài khoản không được để trống'];
        if (DM_NguoiDung_DAL::checkTaiKhoanExists($e->tai_khoan, $e->id)) {
            return ['success' => false, 'message' => 'Tài khoản đã tồn tại'];
        }
        try {
            DM_NguoiDung_DAL::update($e);
            MemcachedHelper::deleteByPrefix('dm_nguoi_dung:');
            PhanQuyenHelper::clearCache();
            DM_NhatKyHeThong_DAL::log($e->nguoi_cap_nhat ?? 0, Constants::MODULE_HE_THONG, "Sửa người dùng: {$e->tai_khoan}", 'dm_nguoi_dung', $e->id);
            return ['success' => true, 'message' => 'Cập nhật thành công'];
        } catch (Throwable $ex) {
            return ['success' => false, 'message' => 'Lỗi: ' . $ex->getMessage()];
        }
    }

    /** Chuyển vào thùng rác (soft delete) */
    public static function trash(int $id, int $u): array
    {
        if ($id <= 0) return ['success' => false, 'message' => 'Thiếu ID'];
        if ($id === 1) return ['success' => false, 'message' => 'Không thể xóa tài khoản admin gốc'];
        if ($id === $u) return ['success' => false, 'message' => 'Không thể tự xóa tài khoản đang đăng nhập'];
        DM_NguoiDung_DAL::softDelete($id, $u);
        MemcachedHelper::deleteByPrefix('dm_nguoi_dung:');
        DM_NhatKyHeThong_DAL::log($u, Constants::MODULE_HE_THONG, "Xóa tạm người dùng id={$id}", 'dm_nguoi_dung', $id);
        return ['success' => true, 'message' => 'Đã chuyển vào thùng rác'];
    }

    /** Giữ tên cũ cho tương thích ngược */
    public static function softDelete(int $id, int $u): array
    {
        return self::trash($id, $u);
    }

    /** Khôi phục từ thùng rác */
    public static function restore(int $id, int $u): array
    {
        if ($id <= 0) return ['success' => false, 'message' => 'Thiếu ID'];
        $user = DM_NguoiDung_DAL::getById($id);
        if (!$user) return ['success' => false, 'message' => 'Không tìm thấy người dùng'];
        // UNIQUE(tai_khoan, da_xoa) → chặn khôi phục nếu tài khoản đã bị dùng lại
        if (DM_NguoiDung_DAL::checkTaiKhoanExists($user->tai_khoan, $id)) {
            return ['success' => false, 'message' => 'Tài khoản này đã được dùng lại, không thể khôi phục'];
        }
        DM_NguoiDung_DAL::restore($id, $u);
        MemcachedHelper::deleteByPrefix('dm_nguoi_dung:');
        DM_NhatKyHeThong_DAL::log($u, Constants::MODULE_HE_THONG, "Khôi phục người dùng id={$id}", 'dm_nguoi_dung', $id);
        return ['success' => true, 'message' => 'Đã khôi phục người dùng'];
    }

    /** Xóa vĩnh viễn (chỉ bản ghi trong thùng rác) */
    public static function delete(int $id, int $u = 0): array
    {
        if ($id <= 0) return ['success' => false, 'message' => 'Thiếu ID'];
        if ($id === 1) return ['success' => false, 'message' => 'Không thể xóa tài khoản admin gốc'];
        $n = DM_NguoiDung_DAL::delete($id);
        if ($n === 0) return ['success' => false, 'message' => 'Chỉ xóa vĩnh viễn được bản ghi trong thùng rác'];
        MemcachedHelper::deleteByPrefix('dm_nguoi_dung:');
        DM_NhatKyHeThong_DAL::log($u, Constants::MODULE_HE_THONG, "Xóa vĩnh viễn người dùng id={$id}", 'dm_nguoi_dung', $id);
        return ['success' => true, 'message' => 'Đã xóa vĩnh viễn'];
    }

    public static function getById(int $id): ?DM_NguoiDung_PUBLIC
    {
        return DM_NguoiDung_DAL::getById($id);
    }

    public static function getPaged(int $page, int $pageSize, string $search = '', int $daXoa = 0, int $nhomId = 0): array
    {
        return DM_NguoiDung_DAL::getPaged($page, $pageSize, $search, $daXoa, $nhomId);
    }

    /** Combo tài khoản đang hoạt động — dùng cho bộ lọc */
    public static function getCombo(): array
    {
        return DM_NguoiDung_DAL::getCombo();
    }

    /** Số lần đăng nhập sai tối đa trước khi bị tạm khóa */
    const MAX_LOGIN_ATTEMPTS = 5;
    /** Thời gian tạm khóa sau khi vượt ngưỡng (giây) */
    const LOGIN_LOCKOUT_SECONDS = 900; // 15 phút

    public static function login(string $taiKhoan, string $matKhau): array
    {
        $taiKhoan = trim($taiKhoan);
        if ($taiKhoan === '' || $matKhau === '') {
            return ['success' => false, 'message' => 'Vui lòng nhập tài khoản và mật khẩu'];
        }

        // Chống brute force: đếm số lần sai theo tài khoản + IP, LƯU Ở DB.
        // Không dùng session vì xóa cookie là mất bộ đếm — bot dò mật khẩu
        // luôn gửi request không kèm cookie nên session chặn được số 0.
        $ip = Helper::getClientIp();
        $throttleKey = DM_DangNhapThatBai_DAL::taoKhoa($taiKhoan, $ip);
        $fail = DM_DangNhapThatBai_DAL::layTrangThai($throttleKey, self::LOGIN_LOCKOUT_SECONDS);
        if ($fail['so_lan'] >= self::MAX_LOGIN_ATTEMPTS) {
            $conLai = (int)ceil((self::LOGIN_LOCKOUT_SECONDS - (time() - $fail['lan_cuoi_ts'])) / 60);
            $conLai = max(1, $conLai);
            DM_NhatKyHeThong_DAL::log(0, Constants::MODULE_HE_THONG, "Đăng nhập bị chặn (quá số lần): {$taiKhoan}", 'dm_nguoi_dung');
            return ['success' => false, 'message' => "Bạn đã nhập sai quá nhiều lần. Thử lại sau {$conLai} phút."];
        }

        // Thông báo lỗi giống nhau cho mọi trường hợp sai → không lộ tài khoản nào tồn tại
        $loiChung = 'Tài khoản hoặc mật khẩu không đúng';

        $user = DM_NguoiDung_DAL::getByTaiKhoan($taiKhoan);
        if (!$user) {
            DM_DangNhapThatBai_DAL::ghiNhanSai($throttleKey, $ip, $taiKhoan, self::LOGIN_LOCKOUT_SECONDS);
            DM_NhatKyHeThong_DAL::log(0, Constants::MODULE_HE_THONG, "Đăng nhập thất bại: {$taiKhoan}", 'dm_nguoi_dung', null, 'Tài khoản không tồn tại');
            return ['success' => false, 'message' => $loiChung];
        }
        if ((int)$user->trang_thai !== 1) {
            DM_DangNhapThatBai_DAL::ghiNhanSai($throttleKey, $ip, $taiKhoan, self::LOGIN_LOCKOUT_SECONDS);
            DM_NhatKyHeThong_DAL::log($user->id, Constants::MODULE_HE_THONG, "Đăng nhập thất bại: {$taiKhoan}", 'dm_nguoi_dung', $user->id, 'Tài khoản bị khóa');
            return ['success' => false, 'message' => 'Tài khoản đã bị khóa'];
        }
        if (!password_verify($matKhau, $user->mat_khau)) {
            DM_DangNhapThatBai_DAL::ghiNhanSai($throttleKey, $ip, $taiKhoan, self::LOGIN_LOCKOUT_SECONDS);
            DM_NhatKyHeThong_DAL::log($user->id, Constants::MODULE_HE_THONG, "Đăng nhập thất bại: {$taiKhoan}", 'dm_nguoi_dung', $user->id, 'Sai mật khẩu');
            return ['success' => false, 'message' => $loiChung];
        }

        // Thành công → xóa bộ đếm
        DM_DangNhapThatBai_DAL::xoa($throttleKey);

        // Nâng cấp hash nếu cost/thuật toán đã đổi
        if (password_needs_rehash($user->mat_khau, AppConfig::PASSWORD_ALGO, ['cost' => AppConfig::PASSWORD_COST])) {
            DM_NguoiDung_DAL::updatePassword(
                $user->id,
                password_hash($matKhau, AppConfig::PASSWORD_ALGO, ['cost' => AppConfig::PASSWORD_COST]),
                $user->id
            );
        }

        DM_NguoiDung_DAL::updateLastLogin($user->id);
        DM_NhatKyHeThong_DAL::log($user->id, Constants::MODULE_HE_THONG, "Đăng nhập: {$user->tai_khoan}", 'dm_nguoi_dung', $user->id);

        return ['success' => true, 'message' => 'Đăng nhập thành công', 'data' => [
            'id' => $user->id,
            'tai_khoan' => $user->tai_khoan,
            'ho_ten' => $user->tai_khoan,
            'nhom_tai_khoan_id' => (int)$user->nhom_tai_khoan_id,
            'ten_nhom' => $user->ten_nhom ?? '',
        ]];
    }

    public static function changePassword(int $id, string $oldPass, string $newPass, string $confirmPass): array
    {
        if ($newPass !== $confirmPass) return ['success' => false, 'message' => 'Mật khẩu xác nhận không khớp'];
        $errPass = self::validatePassword($newPass);
        if ($errPass !== '') return ['success' => false, 'message' => $errPass];

        // Cần hash mật khẩu để verify → dùng bản có mat_khau
        $user = DM_NguoiDung_DAL::getByIdWithPassword($id);
        if (!$user) return ['success' => false, 'message' => 'Không tìm thấy người dùng'];
        if (!password_verify($oldPass, $user->mat_khau)) {
            return ['success' => false, 'message' => 'Mật khẩu hiện tại không đúng'];
        }
        $hash = password_hash($newPass, AppConfig::PASSWORD_ALGO, ['cost' => AppConfig::PASSWORD_COST]);
        DM_NguoiDung_DAL::updatePassword($id, $hash, $id);
        DM_NhatKyHeThong_DAL::log($id, Constants::MODULE_HE_THONG, 'Đổi mật khẩu', 'dm_nguoi_dung', $id);
        return ['success' => true, 'message' => 'Đổi mật khẩu thành công'];
    }

    public static function resetPassword(int $id, string $newPass, int $u): array
    {
        $errPass = self::validatePassword($newPass);
        if ($errPass !== '') return ['success' => false, 'message' => $errPass];
        $hash = password_hash($newPass, AppConfig::PASSWORD_ALGO, ['cost' => AppConfig::PASSWORD_COST]);
        DM_NguoiDung_DAL::updatePassword($id, $hash, $u);
        DM_NhatKyHeThong_DAL::log($u, Constants::MODULE_HE_THONG, "Reset mật khẩu người dùng id={$id}", 'dm_nguoi_dung', $id);
        return ['success' => true, 'message' => 'Reset mật khẩu thành công'];
    }
}