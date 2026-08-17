<?php
require_once __DIR__ . '/../DAL/DM_NhomTaiKhoan_DAL.php';
require_once __DIR__ . '/../DAL/DM_PhanQuyen_DAL.php';
require_once __DIR__ . '/../DAL/DM_NhatKyHeThong_DAL.php';

class DM_NhomTaiKhoan_BUS
{
    const MODULE_KEY = 'DM_NhomTaiKhoan';

    public static function insert(DM_NhomTaiKhoan_PUBLIC $e): array
    {
        $e->ma_nhom = trim($e->ma_nhom);
        $e->ten_nhom = trim($e->ten_nhom);
        if ($e->ma_nhom === '' || $e->ten_nhom === '') {
            return ['success' => false, 'message' => 'Mã và tên nhóm không được để trống'];
        }
        if (DM_NhomTaiKhoan_DAL::checkMaExists($e->ma_nhom)) {
            return ['success' => false, 'message' => 'Mã nhóm đã tồn tại'];
        }
        $id = DM_NhomTaiKhoan_DAL::insert($e);
        MemcachedHelper::deleteByPrefix('dm_nhom_tai_khoan:');
        DM_NhatKyHeThong_DAL::log($e->nguoi_tao ?? 0, Constants::MODULE_HE_THONG, "Thêm nhóm TK: {$e->ten_nhom}", 'dm_nhom_tai_khoan', $id);
        return ['success' => true, 'message' => 'Thêm nhóm thành công', 'data' => ['id' => $id]];
    }

    public static function update(DM_NhomTaiKhoan_PUBLIC $e): array
    {
        if (!$e->id) return ['success' => false, 'message' => 'Thiếu ID'];
        if (DM_NhomTaiKhoan_DAL::checkMaExists($e->ma_nhom, $e->id)) {
            return ['success' => false, 'message' => 'Mã nhóm đã tồn tại'];
        }
        DM_NhomTaiKhoan_DAL::update($e);
        MemcachedHelper::deleteByPrefix('dm_nhom_tai_khoan:');
        PhanQuyenHelper::clearCache();
        return ['success' => true, 'message' => 'Cập nhật thành công'];
    }

    /** Chuyển vào thùng rác */
    public static function trash(int $id, int $u): array
    {
        if ($id <= 0) return ['success' => false, 'message' => 'Thiếu ID'];
        if ($id === 1) return ['success' => false, 'message' => 'Không thể xóa nhóm Admin'];
        $soUser = DM_NhomTaiKhoan_DAL::countNguoiDung($id);
        if ($soUser > 0) {
            return ['success' => false, 'message' => "Nhóm đang có {$soUser} người dùng, không thể xóa"];
        }
        DM_NhomTaiKhoan_DAL::softDelete($id, $u);
        MemcachedHelper::deleteByPrefix('dm_nhom_tai_khoan:');
        PhanQuyenHelper::clearCache($id);
        DM_NhatKyHeThong_DAL::log($u, Constants::MODULE_HE_THONG, "Xóa tạm nhóm TK id={$id}", 'dm_nhom_tai_khoan', $id);
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
        $nhom = DM_NhomTaiKhoan_DAL::getById($id);
        if (!$nhom) return ['success' => false, 'message' => 'Không tìm thấy nhóm'];
        if (DM_NhomTaiKhoan_DAL::checkMaExists($nhom->ma_nhom, $id)) {
            return ['success' => false, 'message' => 'Mã nhóm đã được dùng lại, không thể khôi phục'];
        }
        DM_NhomTaiKhoan_DAL::restore($id, $u);
        MemcachedHelper::deleteByPrefix('dm_nhom_tai_khoan:');
        PhanQuyenHelper::clearCache($id);
        DM_NhatKyHeThong_DAL::log($u, Constants::MODULE_HE_THONG, "Khôi phục nhóm TK id={$id}", 'dm_nhom_tai_khoan', $id);
        return ['success' => true, 'message' => 'Đã khôi phục nhóm'];
    }

    /** Xóa vĩnh viễn: dọn phân quyền + nhóm trong 1 transaction */
    public static function delete(int $id, int $u = 0): array
    {
        if ($id <= 0) return ['success' => false, 'message' => 'Thiếu ID'];
        if ($id === 1) return ['success' => false, 'message' => 'Không thể xóa nhóm Admin'];
        if (DM_NhomTaiKhoan_DAL::countNguoiDung($id) > 0) {
            return ['success' => false, 'message' => 'Nhóm đang có người dùng, không thể xóa vĩnh viễn'];
        }
        try {
            Database::beginTransaction();
            DM_PhanQuyen_DAL::deleteByNhom($id);
            $n = DM_NhomTaiKhoan_DAL::delete($id);
            if ($n === 0) {
                Database::rollBack();
                return ['success' => false, 'message' => 'Chỉ xóa vĩnh viễn được nhóm trong thùng rác'];
            }
            Database::commit();
        } catch (Throwable $ex) {
            Database::rollBack();
            return ['success' => false, 'message' => 'Lỗi xóa nhóm: ' . $ex->getMessage()];
        }
        MemcachedHelper::deleteByPrefix('dm_nhom_tai_khoan:');
        PhanQuyenHelper::clearCache($id);
        DM_NhatKyHeThong_DAL::log($u, Constants::MODULE_HE_THONG, "Xóa vĩnh viễn nhóm TK id={$id}", 'dm_nhom_tai_khoan', $id);
        return ['success' => true, 'message' => 'Đã xóa vĩnh viễn'];
    }

    public static function getById(int $id): ?DM_NhomTaiKhoan_PUBLIC
    {
        return DM_NhomTaiKhoan_DAL::getById($id);
    }

    public static function getPaged(int $page, int $pageSize, string $search = '', int $daXoa = 0): array
    {
        return DM_NhomTaiKhoan_DAL::getPaged($page, $pageSize, $search, $daXoa);
    }

    public static function getCombo(): array
    {
        return DM_NhomTaiKhoan_DAL::getCombo();
    }
}