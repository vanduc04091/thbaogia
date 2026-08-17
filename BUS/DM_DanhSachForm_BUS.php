<?php
require_once __DIR__ . '/../DAL/DM_DanhSachForm_DAL.php';
require_once __DIR__ . '/../DAL/DM_PhanQuyen_DAL.php';
require_once __DIR__ . '/../DAL/DM_NhatKyHeThong_DAL.php';

class DM_DanhSachForm_BUS
{
    const MODULE_KEY = 'DM_DanhSachForm';

    public static function insert(DM_DanhSachForm_PUBLIC $e): array
    {
        $e->modules_tuong_ung = trim($e->modules_tuong_ung);
        $e->ten_form = trim($e->ten_form);
        if ($e->modules_tuong_ung === '' || $e->ten_form === '') {
            return ['success' => false, 'message' => 'Module và tên form không được để trống'];
        }
        if (DM_DanhSachForm_DAL::checkModuleExists($e->modules_tuong_ung)) {
            return ['success' => false, 'message' => 'Module này đã tồn tại'];
        }
        $id = DM_DanhSachForm_DAL::insert($e);
        MemcachedHelper::deleteByPrefix('dm_danh_sach_form:');
        PhanQuyenHelper::clearCache();
        DM_NhatKyHeThong_DAL::log($e->nguoi_tao ?? 0, Constants::MODULE_HE_THONG, "Thêm form: {$e->ten_form}", 'dm_danh_sach_form', $id);
        return ['success' => true, 'message' => 'Thêm form thành công', 'data' => ['id' => $id]];
    }

    public static function update(DM_DanhSachForm_PUBLIC $e): array
    {
        if (!$e->id) return ['success' => false, 'message' => 'Thiếu ID'];
        if (DM_DanhSachForm_DAL::checkModuleExists($e->modules_tuong_ung, $e->id)) {
            return ['success' => false, 'message' => 'Module này đã tồn tại'];
        }
        DM_DanhSachForm_DAL::update($e);
        MemcachedHelper::deleteByPrefix('dm_danh_sach_form:');
        PhanQuyenHelper::clearCache();
        return ['success' => true, 'message' => 'Cập nhật thành công'];
    }

    /** Chuyển vào thùng rác */
    public static function trash(int $id, int $u): array
    {
        if ($id <= 0) return ['success' => false, 'message' => 'Thiếu ID'];
        DM_DanhSachForm_DAL::softDelete($id, $u);
        MemcachedHelper::deleteByPrefix('dm_danh_sach_form:');
        PhanQuyenHelper::clearCache();
        DM_NhatKyHeThong_DAL::log($u, Constants::MODULE_HE_THONG, "Xóa tạm form id={$id}", 'dm_danh_sach_form', $id);
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
        $form = DM_DanhSachForm_DAL::getById($id);
        if (!$form) return ['success' => false, 'message' => 'Không tìm thấy form'];
        if (DM_DanhSachForm_DAL::checkModuleExists($form->modules_tuong_ung, $id)) {
            return ['success' => false, 'message' => 'Module này đã được dùng lại, không thể khôi phục'];
        }
        DM_DanhSachForm_DAL::restore($id, $u);
        MemcachedHelper::deleteByPrefix('dm_danh_sach_form:');
        PhanQuyenHelper::clearCache();
        DM_NhatKyHeThong_DAL::log($u, Constants::MODULE_HE_THONG, "Khôi phục form id={$id}", 'dm_danh_sach_form', $id);
        return ['success' => true, 'message' => 'Đã khôi phục form'];
    }

    /** Xóa vĩnh viễn: dọn phân quyền + form trong 1 transaction */
    public static function delete(int $id, int $u = 0): array
    {
        if ($id <= 0) return ['success' => false, 'message' => 'Thiếu ID'];
        try {
            Database::beginTransaction();
            DM_PhanQuyen_DAL::deleteByForm($id);
            $n = DM_DanhSachForm_DAL::delete($id);
            if ($n === 0) {
                Database::rollBack();
                return ['success' => false, 'message' => 'Chỉ xóa vĩnh viễn được form trong thùng rác'];
            }
            Database::commit();
        } catch (Throwable $ex) {
            Database::rollBack();
            return ['success' => false, 'message' => 'Không thể xóa: ' . $ex->getMessage()];
        }
        MemcachedHelper::deleteByPrefix('dm_danh_sach_form:');
        PhanQuyenHelper::clearCache();
        DM_NhatKyHeThong_DAL::log($u, Constants::MODULE_HE_THONG, "Xóa vĩnh viễn form id={$id}", 'dm_danh_sach_form', $id);
        return ['success' => true, 'message' => 'Đã xóa vĩnh viễn'];
    }

    public static function getById(int $id): ?DM_DanhSachForm_PUBLIC
    {
        return DM_DanhSachForm_DAL::getById($id);
    }

    public static function getAll(int $daXoa = 0): array
    {
        return DM_DanhSachForm_DAL::getAll($daXoa);
    }

    /** Danh sách form đang hoạt động — dùng cho ma trận phân quyền */
    public static function getAllActive(): array
    {
        return DM_DanhSachForm_DAL::getAll(0);
    }

    public static function getPaged(int $page, int $pageSize, string $search = '', int $daXoa = 0): array
    {
        return DM_DanhSachForm_DAL::getPaged($page, $pageSize, $search, $daXoa);
    }
}