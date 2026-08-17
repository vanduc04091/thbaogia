<?php
require_once __DIR__ . '/../DAL/DM_NhatKyHeThong_DAL.php';

class DM_NhatKyHeThong_BUS
{
    const MODULE_KEY = 'DM_NhatKyHeThong';

    public static function getPaged(int $page, int $pageSize, string $search = '', string $module = '', int $nguoiDungId = 0, string $fromDate = '', string $toDate = ''): array
    {
        return DM_NhatKyHeThong_DAL::getPaged($page, $pageSize, $search, $module, $nguoiDungId, $fromDate, $toDate);
    }

    public static function getById(int $id): ?DM_NhatKyHeThong_PUBLIC
    {
        return DM_NhatKyHeThong_DAL::getById($id);
    }

    public static function getModuleList(): array
    {
        return DM_NhatKyHeThong_DAL::getModuleList();
    }

    public static function purgeOlderThan(int $days, int $u): array
    {
        if ($days < 7) return ['success' => false, 'message' => 'Chỉ được xóa log cũ hơn 7 ngày'];
        $n = DM_NhatKyHeThong_DAL::purgeOlderThan($days);
        DM_NhatKyHeThong_DAL::log($u, Constants::MODULE_HE_THONG, "Xóa {$n} log cũ hơn {$days} ngày", 'dm_nhat_ky_he_thong');
        return ['success' => true, 'message' => "Đã xóa {$n} bản ghi cũ hơn {$days} ngày"];
    }
}