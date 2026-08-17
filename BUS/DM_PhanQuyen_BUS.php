<?php
require_once __DIR__ . '/../DAL/DM_PhanQuyen_DAL.php';
require_once __DIR__ . '/../DAL/DM_NhatKyHeThong_DAL.php';

class DM_PhanQuyen_BUS
{
    const MODULE_KEY = 'DM_PhanQuyen';

    // Bitmask dùng chung với frontend (data-perm ở GUI/DM_PhanQuyen)
    const BIT_XEM  = 1;
    const BIT_THEM = 2;
    const BIT_SUA  = 4;
    const BIT_XOA  = 8;

    /** Ma trận dạng thô: 1 dòng / form, kèm 4 cột quyền */
    public static function getMatrixByNhom(int $nhomId): array
    {
        if ($nhomId <= 0) return [];
        return DM_PhanQuyen_DAL::getMatrixByNhom($nhomId);
    }

    /**
     * Ma trận dạng bitmask cho frontend: [ form_id => bitmask ].
     * Đây là shape mà GUI/DM_PhanQuyen/index.php mong đợi.
     */
    public static function getBitmaskByNhom(int $nhomId): array
    {
        $out = [];
        foreach (self::getMatrixByNhom($nhomId) as $r) {
            $mask = 0;
            if (!empty($r['quyen_xem']))  $mask |= self::BIT_XEM;
            if (!empty($r['quyen_them'])) $mask |= self::BIT_THEM;
            if (!empty($r['quyen_sua']))  $mask |= self::BIT_SUA;
            if (!empty($r['quyen_xoa']))  $mask |= self::BIT_XOA;
            $out[(int)$r['form_id']] = $mask;
        }
        return $out;
    }

    /**
     * Lưu ma trận phân quyền. Chấp nhận 2 dạng $permissions:
     *   - [ form_id => bitmask ]                                  (frontend gửi lên)
     *   - [ form_id => ['xem'=>0/1,'them'=>..,'sua'=>..,'xoa'=>..] ]
     */
    public static function saveMatrix(int $nhomId, array $permissions, int $u): array
    {
        if ($nhomId <= 0) return ['success' => false, 'message' => 'Chưa chọn nhóm'];

        // Không cho nhóm tự khóa quyền phân quyền của chính mình khỏi hệ thống
        if (PhanQuyenHelper::isAdminNhom($nhomId)) {
            return ['success' => false, 'message' => 'Nhóm Admin đã có toàn quyền, không cần phân quyền'];
        }

        try {
            Database::beginTransaction();
            foreach ($permissions as $formId => $p) {
                $formId = (int)$formId;
                if ($formId <= 0) continue;

                if (is_array($p)) {
                    $xem  = !empty($p['xem'])  ? 1 : 0;
                    $them = !empty($p['them']) ? 1 : 0;
                    $sua  = !empty($p['sua'])  ? 1 : 0;
                    $xoa  = !empty($p['xoa'])  ? 1 : 0;
                } else {
                    $mask = (int)$p;
                    $xem  = ($mask & self::BIT_XEM)  ? 1 : 0;
                    $them = ($mask & self::BIT_THEM) ? 1 : 0;
                    $sua  = ($mask & self::BIT_SUA)  ? 1 : 0;
                    $xoa  = ($mask & self::BIT_XOA)  ? 1 : 0;
                }

                // Nếu có bất kỳ quyền thêm/sửa/xóa thì mặc định phải có quyền xem
                if (($them || $sua || $xoa) && !$xem) $xem = 1;
                DM_PhanQuyen_DAL::upsert($nhomId, $formId, $xem, $them, $sua, $xoa, $u);
            }
            Database::commit();
            PhanQuyenHelper::clearCache($nhomId);
            DM_NhatKyHeThong_DAL::log($u, Constants::MODULE_HE_THONG, "Cập nhật phân quyền nhóm id={$nhomId}", 'dm_phan_quyen', $nhomId);
            return ['success' => true, 'message' => 'Lưu phân quyền thành công'];
        } catch (Throwable $ex) {
            Database::rollBack();
            return ['success' => false, 'message' => 'Lỗi: ' . $ex->getMessage()];
        }
    }

    public static function grantAllToNhom(int $nhomId, int $u): array
    {
        DM_PhanQuyen_DAL::grantAllToNhom($nhomId, $u);
        PhanQuyenHelper::clearCache($nhomId);
        return ['success' => true, 'message' => 'Đã cấp toàn quyền'];
    }
}