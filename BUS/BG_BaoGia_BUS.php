<?php
require_once __DIR__ . '/../DAL/BG_BaoGia_DAL.php';
require_once __DIR__ . '/../DAL/BG_BaoGiaBo_DAL.php';
require_once __DIR__ . '/../DAL/BG_HangHoa_DAL.php';
require_once __DIR__ . '/../DAL/BG_Bo_DAL.php';
require_once __DIR__ . '/../DAL/BG_GoiThau_DAL.php';
require_once __DIR__ . '/../DAL/DM_NhatKyHeThong_DAL.php';
require_once __DIR__ . '/../DAL/BG_File_DAL.php';
require_once __DIR__ . '/../PUBLIC/Common/ExcelHelper.php';
require_once __DIR__ . '/../PUBLIC/Common/WordHelper.php';
require_once __DIR__ . '/../PUBLIC/Common/WordTemplate.php';
require_once __DIR__ . '/BG_GoiThau_BUS.php';
require_once __DIR__ . '/BG_HangHoa_BUS.php';

class BG_BaoGia_BUS
{
    const MODULE_KEY = 'BG_BaoGia';
    const MODULE_LOG = 'BaoGia';

    // ===== Cột sheet "Mau1_DapUngKyThuat" (Phụ lục II — Mẫu 1) =====
    //   A: Mã HH | B: Tên HH mời | C: YCKT mời | D: YCKT chào giá | E: Điểm không đạt

    // ===== Cột sheet "Mau2_BangChaoGia" (Phụ lục II — Mẫu 2) =====
    //   A: TT | B: Mã HH | C: Tên HH mời | D: Tên TM | E: Model | F: Hãng SX
    //   G: Xuất xứ | H: SL | I: Quy cách | J: ĐVT | K: Đơn giá | L: Thành tiền
    //   M: Giá trúng thầu gần nhất | N: Tài liệu TC | O: Số TB mời thầu

    /** Dòng bắt đầu dữ liệu trong file mẫu (1-3 tiêu đề, 4 header, 5 hướng dẫn) */

    /** Dòng bắt đầu dữ liệu trong file mẫu do hệ thống sinh ra (1=header, 2=HD) */
    const EXCEL_DATA_ROW = 3;

    // =====================================================================
    // VALIDATE THÔNG TIN CÔNG TY
    // =====================================================================

    private static function validateThongTin(BG_BaoGia_PUBLIC $e): string
    {
        $e->ten_cong_ty = trim($e->ten_cong_ty);
        $e->ma_so_thue  = trim((string)$e->ma_so_thue);
        $e->email       = trim((string)$e->email);
        $e->dien_thoai  = trim((string)$e->dien_thoai);

        if ($e->ten_cong_ty === '') return 'Tên công ty không được để trống';
        if (mb_strlen($e->ten_cong_ty) > 500) return 'Tên công ty tối đa 500 ký tự';

        if ($e->ma_so_thue === '') return 'Mã số thuế không được để trống';
        // MST Việt Nam: 10 số, hoặc 10 số - 3 số (đơn vị trực thuộc)
        if (!preg_match('/^\d{10}(-\d{3})?$/', $e->ma_so_thue)) {
            return 'Mã số thuế không hợp lệ (10 số, hoặc dạng 0101234567-001)';
        }

        if ($e->email !== '' && !Helper::isEmail($e->email)) {
            return 'Email không hợp lệ';
        }
        if ($e->dien_thoai !== '' && !Helper::isPhone($e->dien_thoai)) {
            return 'Số điện thoại không hợp lệ';
        }
        if ($e->hieu_luc_bao_gia < 0 || $e->hieu_luc_bao_gia > 3650) {
            return 'Hiệu lực báo giá không hợp lệ';
        }
        return '';
    }

    /**
     * Liệt kê các trường THAY ĐỔI giữa bản cũ và bản mới, dạng
     * "nhãn: cũ → mới". Dùng ghi vào nhật ký để có tranh chấp còn truy được
     * ai sửa gì, chứ không chỉ biết "đã sửa".
     *
     * KHÔNG ghi mật khẩu / token / dữ liệu nhạy cảm (§3B.11).
     */
    private static function soSanhThayDoi(array $cu, array $moi, array $nhan): string
    {
        $doi = [];
        foreach ($nhan as $truong => $ten) {
            $a = trim((string)($cu[$truong] ?? ''));
            $b = trim((string)($moi[$truong] ?? ''));
            if ($a === $b) continue;
            $doi[] = $ten . ': ' . ($a === '' ? '(trống)' : $a) . ' → ' . ($b === '' ? '(trống)' : $b);
        }
        return implode('; ', $doi);
    }

    // =====================================================================
    // NHÀ THẦU: TẠO / CẬP NHẬT BÁO GIÁ QUA CỔNG QR
    // =====================================================================

    /**
     * Nhà thầu khai thông tin công ty → tạo phiếu báo giá nháp.
     * Trả về id báo giá để tiếp tục điền giá.
     */
    public static function taoBaoGia(BG_BaoGia_PUBLIC $e, int $u): array
    {
        $gt = BG_GoiThau_DAL::getById($e->goi_thau_id);
        if (!$gt || $gt->da_xoa === 1) return ['success' => false, 'message' => 'Gói thầu không tồn tại'];

        $conNhan = BG_GoiThau_BUS::kiemTraConNhan($gt);
        if (!$conNhan['ok']) return ['success' => false, 'message' => $conNhan['message']];

        $err = self::validateThongTin($e);
        if ($err !== '') return ['success' => false, 'message' => $err];

        if (BG_BaoGia_DAL::existsMstTrongGoiThau((string)$e->ma_so_thue, $e->goi_thau_id)) {
            return [
                'success' => false,
                'message' => 'Mã số thuế này đã nộp báo giá cho gói thầu. Liên hệ bên mời chào giá nếu cần nộp lại.',
            ];
        }

        // Hiệu lực nhà thầu cam kết không được thấp hơn yêu cầu của gói thầu
        if ((int)$gt->hieu_luc_bao_gia > 0 && $e->hieu_luc_bao_gia < (int)$gt->hieu_luc_bao_gia) {
            return [
                'success' => false,
                'message' => 'Hiệu lực báo giá tối thiểu ' . (int)$gt->hieu_luc_bao_gia . ' ngày',
            ];
        }

        try {
            $e->trang_thai = BG_BaoGia_PUBLIC::TT_CHO_XAC_NHAN;
            $e->ip_nop = Helper::getClientIp();
            $e->nguoi_tao = $u;
            $id = BG_BaoGia_DAL::insert($e);

            DM_NhatKyHeThong_DAL::log(
                $u, self::MODULE_LOG,
                "Nhà thầu tạo báo giá gói {$gt->so_thong_bao}: {$e->ten_cong_ty} (MST {$e->ma_so_thue})",
                'bg_bao_gia', $id
            );
            return ['success' => true, 'message' => 'Đã lưu thông tin công ty', 'data' => ['id' => $id]];
        } catch (Throwable $ex) {
            return ['success' => false, 'message' => 'Lỗi: ' . $ex->getMessage()];
        }
    }

    /** Cập nhật thông tin công ty (khi chưa được xác nhận bản giấy) */
    public static function capNhatThongTin(BG_BaoGia_PUBLIC $e, int $u): array
    {
        if (!$e->id) return ['success' => false, 'message' => 'Thiếu ID'];
        $cu = BG_BaoGia_DAL::getById((int)$e->id);
        if (!$cu || $cu->da_xoa === 1) return ['success' => false, 'message' => 'Không tìm thấy báo giá'];
        // KHÔNG khóa theo "đã xác nhận" nữa: nhà thầu tự ký là đã thành
        // "Đã xác nhận", nhưng còn trong thời gian chào giá thì vẫn được sửa.
        // Chỉ chặn khi đã chốt hoàn thành (xem bên dưới).
        if ((int)($cu->da_hoan_thanh ?? 0) === 1) {
            return ['success' => false, 'message' => 'Báo giá đã hoàn thành — không chỉnh sửa được nữa'];
        }

        $err = self::validateThongTin($e);
        if ($err !== '') return ['success' => false, 'message' => $err];

        if (BG_BaoGia_DAL::existsMstTrongGoiThau((string)$e->ma_so_thue, (int)$cu->goi_thau_id, (int)$e->id)) {
            return ['success' => false, 'message' => 'Mã số thuế này đã có báo giá khác trong gói thầu'];
        }

        try {
            // Ghi lại GIÁ TRỊ CŨ → MỚI trước khi update, để nhật ký truy được
            $thayDoi = self::soSanhThayDoi(
                [
                    'ten_cong_ty' => $cu->ten_cong_ty, 'ma_so_thue' => $cu->ma_so_thue,
                    'email' => $cu->email, 'dien_thoai' => $cu->dien_thoai,
                    'dia_chi' => $cu->dia_chi, 'hieu_luc_bao_gia' => $cu->hieu_luc_bao_gia,
                ],
                [
                    'ten_cong_ty' => $e->ten_cong_ty, 'ma_so_thue' => $e->ma_so_thue,
                    'email' => $e->email, 'dien_thoai' => $e->dien_thoai,
                    'dia_chi' => $e->dia_chi, 'hieu_luc_bao_gia' => $e->hieu_luc_bao_gia,
                ],
                [
                    'ten_cong_ty' => 'Tên công ty', 'ma_so_thue' => 'MST',
                    'email' => 'Email', 'dien_thoai' => 'Điện thoại',
                    'dia_chi' => 'Địa chỉ', 'hieu_luc_bao_gia' => 'Hiệu lực',
                ]
            );

            $e->nguoi_cap_nhat = $u;
            BG_BaoGia_DAL::update($e);
            DM_NhatKyHeThong_DAL::log(
                $u, self::MODULE_LOG,
                "Cập nhật thông tin báo giá: {$e->ten_cong_ty}"
                . ($thayDoi !== '' ? ' | ' . $thayDoi : ' (không có gì đổi)'),
                'bg_bao_gia', $e->id
            );
            return ['success' => true, 'message' => 'Cập nhật thành công'];
        } catch (Throwable $ex) {
            return ['success' => false, 'message' => 'Lỗi: ' . $ex->getMessage()];
        }
    }

    private static function nullIfEmpty($v, int $maxLen = 0): ?string
    {
        $s = ExcelHelper::toText($v, $maxLen);
        return $s === '' ? null : $s;
    }

    /**
     * Nhà thầu nộp báo giá: chốt lại, đánh dấu ngày nộp.
     * Yêu cầu đã chào giá ít nhất 1 dòng.
     */
    public static function nopBaoGia(int $baoGiaId, int $u): array
    {
        $bg = BG_BaoGia_DAL::getById($baoGiaId);
        if (!$bg || $bg->da_xoa === 1) return ['success' => false, 'message' => 'Không tìm thấy báo giá'];
        if ((int)($bg->da_hoan_thanh ?? 0) === 1) {
            return ['success' => false, 'message' => 'Báo giá đã hoàn thành — không nộp lại được'];
        }
        if ((int)$bg->so_dong_chao === 0) {
            return ['success' => false, 'message' => 'Chưa chào giá dòng nào — hãy điền đơn giá hoặc import file trước'];
        }

        $gt = BG_GoiThau_DAL::getById((int)$bg->goi_thau_id);
        if ($gt) {
            $conNhan = BG_GoiThau_BUS::kiemTraConNhan($gt);
            if (!$conNhan['ok']) return ['success' => false, 'message' => $conNhan['message']];
        }

        BG_BaoGia_DAL::markNop($baoGiaId);
        BG_BaoGia_DAL::updateTongTien($baoGiaId);
        DM_NhatKyHeThong_DAL::log(
            $u, self::MODULE_LOG,
            "Nhà thầu nộp báo giá: {$bg->ten_cong_ty} ({$bg->so_dong_chao} dòng)", 'bg_bao_gia', $baoGiaId
        );
        return [
            'success' => true,
            'message' => 'Đã nộp báo giá. Vui lòng gửi bản giấy tới bên mời chào giá để được xác nhận.',
        ];
    }

    // =====================================================================
    // IMPORT FILE BÁO GIÁ CỦA NHÀ THẦU
    // =====================================================================

    /**
     * Import file Excel báo giá do nhà thầu điền.
     * Khớp dòng theo thứ tự hàng hóa của gói thầu (file mẫu sinh ra theo đúng thứ tự đó),
     * đồng thời đối chiếu tên hàng hóa để phát hiện lệch.
     */
    /**
     * Nhà thầu import file Mẫu 1 (bảng đáp ứng) hoặc Mẫu 2 (bảng chào giá).
     *
     * Số cột Mẫu 1 THAY ĐỔI theo nhóm gói thầu (12 / 18 / 21 cột) nên KHÔNG
     * dò theo vị trí cột cố định nữa: đọc dòng tiêu đề rồi map "tên cột → chỉ
     * số". Nhà thầu chèn/xóa cột hay đổi nhóm cũng không lệch dữ liệu.
     *
     * Khớp dòng theo **Mã** (cột A) — mã do bên mời phát, nhà thầu không sửa.
     */
    public static function importFileBaoGia(int $baoGiaId, string $filePath, int $u): array
    {
        $bg = BG_BaoGia_DAL::getById($baoGiaId);
        if (!$bg || $bg->da_xoa === 1) return ['success' => false, 'message' => 'Không tìm thấy báo giá'];
        if ((int)($bg->da_hoan_thanh ?? 0) === 1) {
            return ['success' => false, 'message' => 'Báo giá đã hoàn thành — không import được nữa'];
        }

        $gt = BG_GoiThau_DAL::getById((int)$bg->goi_thau_id);
        if (!$gt) return ['success' => false, 'message' => 'Không tìm thấy gói thầu'];

        $conNhan = BG_GoiThau_BUS::kiemTraConNhan($gt);
        if (!$conNhan['ok']) return ['success' => false, 'message' => $conNhan['message']];

        $hangHoa = BG_HangHoa_DAL::getByGoiThau((int)$bg->goi_thau_id);
        if (empty($hangHoa)) return ['success' => false, 'message' => 'Gói thầu chưa có danh mục hàng hóa'];

        // Map Mã HH (chuẩn hóa hoa/thường) → hàng hóa
        $theoMa = [];
        foreach ($hangHoa as $hh) {
            $ma = mb_strtoupper(trim((string)($hh['ma_hh'] ?? '')));
            if ($ma !== '') $theoMa[$ma] = $hh;
        }

        $nhom     = BG_Nhom_PUBLIC::chuanHoa($gt->nhom ?? null);
        $canhBao  = [];
        $duLieu   = [];
        /** Giá nhà thầu chào cho CẢ BỘ: [bo_id => các cột Mẫu 2] */
        $duLieuBo = [];

        try {
            $tenSheet = ExcelHelper::sheetNames($filePath);
        } catch (Throwable $ex) {
            return ['success' => false, 'message' => 'Không đọc được file: ' . $ex->getMessage()];
        }

        // ---------- MẪU 1 — Bảng đáp ứng ----------
        if (in_array('Mau1_BangDapUng', $tenSheet, true)) {
            $rows = ExcelHelper::readSheet($filePath, 'Mau1_BangDapUng');
            [$cot, $dongDau] = self::doCotTheoTieuDe($rows);

            if ($dongDau === 0) {
                $canhBao[] = 'Mẫu 1: không tìm thấy dòng tiêu đề — bỏ qua sheet này';
            } else {
                // Cặp (đáp ứng, không đạt) đúng theo nhóm — xem BG_Nhom_PUBLIC
                $cap = BG_Nhom_PUBLIC::capDapUng($nhom);

                foreach ($rows as $rowNo => $cells) {
                    if ($rowNo <= $dongDau) continue;
                    $ma = mb_strtoupper(ExcelHelper::toText($cells[$cot['ma'] ?? 0] ?? '', 50));
                    if (!isset($theoMa[$ma]) || $ma === '') {
                        // DÒNG BỘ: yêu cầu chung/khác/cấu hình gắn với BỘ nên
                        // phần ĐÁP ỨNG cho chúng cũng điền ở dòng bộ. Trước đây
                        // bỏ qua im lặng → nhà thầu điền xong mà Bước 4 in ra
                        // bảng đáp ứng trống trơn.
                        //
                        // KHÔNG chặn sớm bằng `$ma === ''`: bộ có ma_bo NULL thì
                        // ô Mã trống, chặn sớm là mất trắng dữ liệu dòng bộ.
                        $boId = self::nhanDangDongBo($cells, $cot, $ma, (int)$bg->goi_thau_id);
                        if ($boId > 0) {
                            foreach ($cap as $khoa => $c) {
                                $iDu    = $cot['dap_ung_' . $khoa]   ?? null;
                                $iKhong = $cot['khong_dat_' . $khoa] ?? null;
                                if ($iDu !== null) {
                                    $duLieuBo[$boId][$c[1]] = ExcelHelper::toText($cells[$iDu] ?? '');
                                }
                                if ($iKhong !== null) {
                                    $duLieuBo[$boId][$c[2]] = ExcelHelper::toText($cells[$iKhong] ?? '');
                                }
                            }
                            if (isset($cot['tai_lieu_chung_minh'])) {
                                $duLieuBo[$boId]['tai_lieu_chung_minh'] =
                                    ExcelHelper::toText($cells[$cot['tai_lieu_chung_minh']] ?? '');
                            }
                        } elseif ($ma !== '') {
                            $canhBao[] = "Mẫu 1 dòng {$rowNo}: Mã \"{$ma}\" không có trong gói thầu — bỏ qua";
                        }
                        continue;
                    }

                    foreach ($cap as $khoa => $c) {
                        // $c = [nhãn, cột đáp ứng, cột không đạt]
                        $iDu    = $cot['dap_ung_' . $khoa]   ?? null;
                        $iKhong = $cot['khong_dat_' . $khoa] ?? null;
                        if ($iDu !== null) {
                            $duLieu[$ma][$c[1]] = ExcelHelper::toText($cells[$iDu] ?? '');
                        }
                        if ($iKhong !== null) {
                            $duLieu[$ma][$c[2]] = ExcelHelper::toText($cells[$iKhong] ?? '');
                        }
                    }
                    if (isset($cot['tai_lieu_chung_minh'])) {
                        $duLieu[$ma]['tai_lieu_chung_minh'] =
                            ExcelHelper::toText($cells[$cot['tai_lieu_chung_minh']] ?? '');
                    }
                }
            }
        }

        // ---------- MẪU 2 — Bảng chào giá ----------
        if (in_array('Mau2_BangChaoGia', $tenSheet, true)) {
            $rows = ExcelHelper::readSheet($filePath, 'Mau2_BangChaoGia');
            [$cot, $dongDau] = self::doCotTheoTieuDe($rows);

            if ($dongDau === 0) {
                $canhBao[] = 'Mẫu 2: không tìm thấy dòng tiêu đề — bỏ qua sheet này';
            } else {
                foreach ($rows as $rowNo => $cells) {
                    if ($rowNo <= $dongDau) continue;
                    $ma = mb_strtoupper(ExcelHelper::toText($cells[$cot['ma'] ?? 0] ?? '', 50));
                    if (!isset($theoMa[$ma]) || $ma === '') {
                        // DÒNG BỘ: nhà thầu chào giá cho CẢ BỘ ở đây (2 nhóm mua
                        // theo bộ). Trước đây bỏ qua im lặng vì giá bộ là tổng
                        // cộng dồn của chi tiết — giờ chính dòng này mang giá.
                        //
                        // Nhận dạng theo Mã, thiếu mã thì theo Tên bộ — xem
                        // nhanDangDongBo(). Chặn sớm bằng `$ma === ''` sẽ làm
                        // mất giá của mọi bộ không đặt mã.
                        $boId = self::nhanDangDongBo($cells, $cot, $ma, (int)$bg->goi_thau_id);
                        if ($boId > 0) {
                            $layB = function (string $k, int $max = 0) use ($cot, $cells): string {
                                return isset($cot[$k]) ? ExcelHelper::toText($cells[$cot[$k]] ?? '', $max) : '';
                            };
                            $dgBo = isset($cot['don_gia'])
                                ? ExcelHelper::toNumber($cells[$cot['don_gia']] ?? 0) : 0;
                            if ($dgBo < 0) {
                                $canhBao[] = "Mẫu 2 dòng {$rowNo}: đơn giá bộ âm → đặt về 0";
                                $dgBo = 0;
                            }
                            // Thành tiền của BỘ lấy NGUYÊN số nhà thầu ghi (ngoại
                            // lệ §10.2 — giá trọn gói không phải lúc nào cũng
                            // chia đều theo số lượng bộ). Xem BG_BaoGiaBo_PUBLIC.
                            $ttBo = isset($cot['thanh_tien'])
                                ? ExcelHelper::toNumber($cells[$cot['thanh_tien']] ?? 0) : 0;
                            if ($ttBo < 0) {
                                $canhBao[] = "Mẫu 2 dòng {$rowNo}: thành tiền bộ âm → đặt về 0";
                                $ttBo = 0;
                            }

                            $duLieuBo[$boId] = [
                                'ten_thuong_mai' => $layB('ten_thuong_mai', 1000),
                                'model'          => $layB('model', 500),
                                'hang_san_xuat'  => $layB('hang_san_xuat', 500),
                                'nam_san_xuat'   => $layB('nam_san_xuat', 20),
                                'xuat_xu'        => $layB('xuat_xu', 500),
                                'don_gia'        => $dgBo,
                                'thanh_tien'     => $ttBo,
                            ];
                        } elseif ($ma !== '') {
                            $canhBao[] = "Mẫu 2 dòng {$rowNo}: Mã \"{$ma}\" không có trong gói thầu — bỏ qua";
                        }
                        continue;
                    }

                    $donGia = isset($cot['don_gia'])
                        ? ExcelHelper::toNumber($cells[$cot['don_gia']] ?? 0) : 0;
                    if ($donGia < 0) {
                        $canhBao[] = "Mẫu 2 dòng {$rowNo}: đơn giá âm → đặt về 0";
                        $donGia = 0;
                    }

                    $lay = function (string $k, int $max = 0) use ($cot, $cells): string {
                        return isset($cot[$k]) ? ExcelHelper::toText($cells[$cot[$k]] ?? '', $max) : '';
                    };

                    $duLieu[$ma]['ten_thuong_mai'] = $lay('ten_thuong_mai', 1000);
                    $duLieu[$ma]['model']          = $lay('model', 500);
                    $duLieu[$ma]['hang_san_xuat']  = $lay('hang_san_xuat', 500);
                    $duLieu[$ma]['nam_san_xuat']   = $lay('nam_san_xuat', 20);
                    $duLieu[$ma]['xuat_xu']        = $lay('xuat_xu', 500);
                    $duLieu[$ma]['don_gia']        = $donGia;
                }
            }
        }

        // Nhóm mua theo bộ có thể CHỈ điền dòng BỘ (chi tiết bỏ trống) — khi đó
        // $duLieu rỗng nhưng $duLieuBo có dữ liệu, không được coi là file sai.
        if (empty($duLieu) && empty($duLieuBo)) {
            return [
                'success' => false,
                'message' => 'Không đọc được dòng nào khớp Mã của gói thầu. Hãy tải lại '
                           . 'file mẫu và giữ nguyên cột Mã cùng tên sheet '
                           . '(Mau1_BangDapUng / Mau2_BangChaoGia).',
                'data'    => ['canh_bao' => $canhBao],
            ];
        }

        // ---------- Ghi vào DB ----------
        try {
            Database::beginTransaction();

            // Đọc 1 LẦN trước vòng lặp — gọi trong lặp là N+1 truy vấn
            $chiTietCu = BG_BaoGia_DAL::getChiTietMap($baoGiaId);

            $soDong = 0;
            foreach ($duLieu as $ma => $v) {
                $hh = $theoMa[$ma];
                $soLuong = (float)$hh['so_luong'];

                // Giữ giá trị cũ khi file lần này không có cột đó (import Mẫu 1
                // rồi Mẫu 2 ở 2 lần khác nhau thì không được xóa dữ liệu lần trước)
                $cu = $chiTietCu[(int)$hh['id']] ?? null;
                $giu = function (string $k) use ($v, $cu) {
                    if (array_key_exists($k, $v)) return self::nullIfEmpty((string)$v[$k]);
                    return $cu[$k] ?? null;
                };

                $donGia = array_key_exists('don_gia', $v)
                    ? (float)$v['don_gia']
                    : (float)($cu['don_gia'] ?? 0);

                $ct = new BG_BaoGiaChiTiet_PUBLIC();
                $ct->bao_gia_id  = $baoGiaId;
                $ct->hang_hoa_id = (int)$hh['id'];

                // Mẫu 1 — các cặp đáp ứng / không đạt
                $ct->thong_so_chao_gia     = $giu('thong_so_chao_gia');
                $ct->diem_khong_dat        = $giu('diem_khong_dat');
                $ct->dap_ung_chung         = $giu('dap_ung_chung');
                $ct->khong_dat_chung       = $giu('khong_dat_chung');
                $ct->dap_ung_khac          = $giu('dap_ung_khac');
                $ct->khong_dat_khac        = $giu('khong_dat_khac');
                $ct->dap_ung_cau_hinh      = $giu('dap_ung_cau_hinh');
                $ct->khong_dat_cau_hinh    = $giu('khong_dat_cau_hinh');
                $ct->dap_ung_nhom_nuoc     = $giu('dap_ung_nhom_nuoc');
                $ct->khong_dat_nhom_nuoc   = $giu('khong_dat_nhom_nuoc');
                $ct->tai_lieu_chung_minh   = $giu('tai_lieu_chung_minh');

                // Mẫu 2 — thông tin chào giá
                $ct->ten_thuong_mai = $giu('ten_thuong_mai');
                $ct->model          = $giu('model');
                $ct->hang_san_xuat  = $giu('hang_san_xuat');
                $ct->nam_san_xuat   = $giu('nam_san_xuat');
                $ct->xuat_xu        = $giu('xuat_xu');
                $ct->don_gia        = $donGia;
                // Thành tiền LUÔN tính ở server, không tin cột Thành tiền trong file
                $ct->thanh_tien     = round($donGia * $soLuong, 2);

                BG_BaoGia_DAL::upsertChiTiet($ct);
                $soDong++;
            }

            // ---- Giá chào cho CẢ BỘ (2 nhóm mua theo bộ) ----
            // thanh_tien lấy NGUYÊN số nhà thầu ghi; nếu bỏ trống thì suy ra
            // = đơn giá × số lượng bộ để bên mời không phải nhìn ô rỗng.
            $boCuaGoi = [];
            foreach (BG_Bo_DAL::getByGoiThau((int)$bg->goi_thau_id) as $b) {
                $boCuaGoi[(int)$b['id']] = $b;
            }
            // Giá/đáp ứng bộ đã lưu từ lần import trước — đọc 1 lần trước vòng lặp
            $giaBoCu = BG_BaoGiaBo_DAL::getMap($baoGiaId);
            foreach ($duLieuBo as $boId => $v) {
                // Import Mẫu 1 chỉ mang cột ĐÁP ỨNG, không có giá → 2 khóa này
                // vắng mặt là chuyện bình thường, phải ?? chứ không đọc thẳng.
                $slBo = (float)($boCuaGoi[$boId]['so_luong'] ?? 0);
                $dgV  = (float)($v['don_gia'] ?? 0);
                $tt   = (float)($v['thanh_tien'] ?? 0);
                if ($tt <= 0 && $dgV > 0) {
                    $tt = round($dgV * $slBo, 2);
                }

                // Import Mẫu 1 rồi Mẫu 2 ở 2 lần khác nhau: lần sau không được
                // xóa dữ liệu lần trước → thiếu khóa nào thì giữ giá trị cũ.
                $cuBo = $giaBoCu[$boId] ?? null;
                $giuBo = function (string $k) use ($v, $cuBo) {
                    if (array_key_exists($k, $v)) return self::nullIfEmpty((string)$v[$k]);
                    return $cuBo[$k] ?? null;
                };

                $eb = new BG_BaoGiaBo_PUBLIC();
                $eb->bao_gia_id     = $baoGiaId;
                $eb->bo_id          = (int)$boId;
                $eb->ten_thuong_mai = $giuBo('ten_thuong_mai');
                $eb->model          = $giuBo('model');
                $eb->hang_san_xuat  = $giuBo('hang_san_xuat');
                $eb->nam_san_xuat   = $giuBo('nam_san_xuat');
                $eb->xuat_xu        = $giuBo('xuat_xu');
                $eb->don_gia        = array_key_exists('don_gia', $v)
                    ? (float)$v['don_gia'] : (float)($cuBo['don_gia'] ?? 0);
                $eb->thanh_tien     = $tt > 0 ? $tt : (float)($cuBo['thanh_tien'] ?? 0);

                // ---- Đáp ứng cấp BỘ (Mẫu 1, dòng BỘ) ----
                $eb->dap_ung_chung       = $giuBo('dap_ung_chung');
                $eb->khong_dat_chung     = $giuBo('khong_dat_chung');
                $eb->dap_ung_khac        = $giuBo('dap_ung_khac');
                $eb->khong_dat_khac      = $giuBo('khong_dat_khac');
                $eb->dap_ung_cau_hinh    = $giuBo('dap_ung_cau_hinh');
                $eb->khong_dat_cau_hinh  = $giuBo('khong_dat_cau_hinh');
                $eb->thong_so_chao_gia   = $giuBo('thong_so_chao_gia');
                $eb->diem_khong_dat      = $giuBo('diem_khong_dat');
                $eb->dap_ung_nhom_nuoc   = $giuBo('dap_ung_nhom_nuoc');
                $eb->khong_dat_nhom_nuoc = $giuBo('khong_dat_nhom_nuoc');
                $eb->tai_lieu_chung_minh = $giuBo('tai_lieu_chung_minh');

                BG_BaoGiaBo_DAL::upsert($eb);
                $soDong++;
            }

            BG_BaoGia_DAL::updateTongTien($baoGiaId);
            Database::commit();

            DM_NhatKyHeThong_DAL::log(
                $u, self::MODULE_LOG,
                "Nhà thầu import file báo giá: {$bg->ten_cong_ty} — {$soDong} dòng",
                'bg_bao_gia', $baoGiaId
            );

            return [
                'success' => true,
                'message' => "Đã đọc {$soDong} dòng từ file",
                'data'    => ['so_dong' => $soDong, 'canh_bao' => $canhBao],
            ];
        } catch (Throwable $ex) {
            Database::rollBack();
            return ['success' => false, 'message' => 'Lỗi khi import: ' . $ex->getMessage()];
        }
    }

    /**
     * Dò dòng tiêu đề của sheet rồi map "khóa cột → chỉ số cột".
     *
     * Dò theo TÊN CỘT thay vì vị trí cố định vì Mẫu 1 có số cột khác nhau
     * giữa 3 nhóm gói thầu. So khớp sau khi bỏ dấu để không phụ thuộc dấu
     * tiếng Việt hay hoa/thường.
     *
     * @return array [mảng khóa=>chỉ số, số hiệu dòng tiêu đề (0 = không thấy)]
     */
    private static function doCotTheoTieuDe(array $rows): array
    {
        // Thứ tự QUAN TRỌNG: khóa dài/đặc trưng đặt TRƯỚC khóa ngắn, vì so khớp
        // bằng "chứa chuỗi" — 'dap ung ve yeu cau chung' cũng chứa 'yeu cau chung'.
        $mau = [
            'khong_dat_chung'     => ['khong dap ung ve yeu cau chung'],
            'khong_dat_khac'      => ['khong dap ung ve yeu cau khac'],
            'khong_dat_cau_hinh'  => ['khong dap ung ve yeu cau cau hinh'],
            'khong_dat_ky_thuat'  => ['khong dap ung ve yeu cau ky thuat'],
            'khong_dat_nhom_nuoc' => ['khong dap ung ve nhom nuoc'],
            'dap_ung_chung'       => ['dap ung ve yeu cau chung'],
            'dap_ung_khac'        => ['dap ung ve yeu cau khac'],
            'dap_ung_cau_hinh'    => ['dap ung ve yeu cau cau hinh'],
            'dap_ung_ky_thuat'    => ['dap ung ve yeu cau ky thuat'],
            'dap_ung_nhom_nuoc'   => ['dap ung ve nhom nuoc'],
            'tai_lieu_chung_minh' => ['tai lieu chung minh'],
            'ten_thuong_mai'      => ['ten thuong mai'],
            'model'               => ['ky ma, nhan hieu', 'nhan hieu, model', 'model'],
            'hang_san_xuat'       => ['hang san xuat'],
            'nam_san_xuat'        => ['nam san xuat'],
            'xuat_xu'             => ['xuat xu'],
            'don_gia'             => ['don gia'],
            'thanh_tien'          => ['thanh tien'],
            'ma'                  => ['ma bo/hang hoa', 'ma hh', 'ma hang hoa'],
            // Cần để nhận dạng DÒNG BỘ khi bộ không có Mã (ma_bo cho phép NULL).
            // Đặt SAU 'ma' vì 'ma bo/hang hoa' phải giành được cột A trước.
            'ten_bo'              => ['ten bo/phan/he thong', 'ten bo'],
            'stt_bo'              => ['stt bo'],
        ];

        for ($d = 1; $d <= 10; $d++) {
            if (!isset($rows[$d])) continue;

            $cot = [];
            foreach ($rows[$d] as $i => $v) {
                $t = self::boDauChuoi(ExcelHelper::toText($v));
                if ($t === '') continue;
                foreach ($mau as $khoa => $tuKhoa) {
                    if (isset($cot[$khoa])) continue;
                    foreach ($tuKhoa as $tk) {
                        if (mb_strpos($t, $tk) !== false) { $cot[$khoa] = (int)$i; break 2; }
                    }
                }
            }

            // Đủ điều kiện là dòng tiêu đề: có cột Mã + ít nhất 1 cột nhà thầu điền
            if (isset($cot['ma']) && count($cot) >= 2) {
                return [$cot, $d];
            }
        }
        return [[], 0];
    }

    /**
     * Mã này có phải mã của một BỘ trong gói thầu không?
     *
     * Dòng bộ ở Mẫu 1/2 cũng mang mã (ma_bo) nhưng không phải hàng hóa chi tiết
     * nên không khớp $theoMa. Phân biệt để không cảnh báo nhầm cho nhà thầu.
     */
    private static function laMaBo(string $ma, int $goiThauId): bool
    {
        return self::timBoTheoMa($ma, $goiThauId) > 0;
    }

    /**
     * Mã này là mã của BỘ nào trong gói thầu? Trả 0 nếu không phải mã bộ.
     *
     * Dùng khi import Mẫu 2: dòng BỘ mang giá trọn bộ nên phải biết ghi vào
     * bo_id nào, không chỉ cần biết "có phải mã bộ không" như trước.
     */
    private static function timBoTheoMa(string $ma, int $goiThauId): int
    {
        static $cache = [];
        if (!isset($cache[$goiThauId])) {
            $cache[$goiThauId] = [];
            foreach (BG_Bo_DAL::getByGoiThau($goiThauId) as $b) {
                $m = mb_strtoupper(trim((string)($b['ma_bo'] ?? '')));
                if ($m !== '') $cache[$goiThauId][$m] = (int)$b['id'];
            }
        }
        return $cache[$goiThauId][$ma] ?? 0;
    }

    /**
     * Tìm BỘ theo TÊN (+ STT nếu trùng tên). Trả 0 nếu không thấy.
     *
     * VÌ SAO CẦN: bg_bo.ma_bo cho phép NULL. Bộ không có mã thì file mẫu sinh
     * ra với ô Mã TRỐNG, mà import lại bỏ qua mọi dòng có mã rỗng → nhà thầu
     * điền đáp ứng/giá ở dòng bộ xong vẫn mất sạch, không một cảnh báo nào.
     * Nhận dạng theo tên là đường dự phòng, giống cách parser Phụ lục III
     * nhận dòng bộ bằng "có Tên bộ" chứ không bằng mã.
     */
    private static function timBoTheoTen(string $ten, $sttBo, int $goiThauId): int
    {
        $ten = self::boDauChuoi(trim($ten));
        if ($ten === '') return 0;

        static $cache = [];
        if (!isset($cache[$goiThauId])) {
            $cache[$goiThauId] = [];
            foreach (BG_Bo_DAL::getByGoiThau($goiThauId) as $b) {
                $t = self::boDauChuoi(trim((string)($b['ten_bo'] ?? '')));
                if ($t === '') continue;
                // Nhiều bộ có thể trùng tên → gom theo tên rồi lọc tiếp bằng STT
                $cache[$goiThauId][$t][] = [
                    'id'     => (int)$b['id'],
                    'stt_bo' => $b['stt_bo'] !== null ? (int)$b['stt_bo'] : null,
                ];
            }
        }

        $ds = $cache[$goiThauId][$ten] ?? [];
        if (!$ds) return 0;
        if (count($ds) === 1) return $ds[0]['id'];

        // Trùng tên: phải có STT bộ mới phân biệt được, không thì bỏ qua cho an toàn
        $stt = ($sttBo === null || $sttBo === '') ? null : (int)ExcelHelper::toNumber((string)$sttBo);
        if ($stt === null) return 0;
        foreach ($ds as $b) {
            if ($b['stt_bo'] === $stt) return $b['id'];
        }
        return 0;
    }

    /**
     * Dòng này là DÒNG BỘ của gói thầu nào? Trả bo_id, hoặc 0 nếu không phải.
     *
     * Thử theo MÃ trước (chắc chắn nhất), không có mã thì theo TÊN + STT.
     * Dùng chung cho cả 2 nhánh import Mẫu 1 / Mẫu 2 để chúng không lệch nhau.
     */
    private static function nhanDangDongBo(array $cells, array $cot, string $ma, int $goiThauId): int
    {
        if ($ma !== '') {
            $boId = self::timBoTheoMa($ma, $goiThauId);
            if ($boId > 0) return $boId;
        }
        $tenBo = isset($cot['ten_bo'])
            ? ExcelHelper::toText($cells[$cot['ten_bo']] ?? '', 1000) : '';
        if ($tenBo === '') return 0;

        $sttBo = isset($cot['stt_bo'])
            ? ExcelHelper::toText($cells[$cot['stt_bo']] ?? '') : '';
        return self::timBoTheoTen($tenBo, $sttBo, $goiThauId);
    }

    /** Bỏ dấu tiếng Việt + hạ chữ thường để so khớp tiêu đề cột */
    private static function boDauChuoi(string $s): string
    {
        $s = mb_strtolower(trim($s), 'UTF-8');
        $map = [
            'a' => 'áàảãạăắằẳẵặâấầẩẫậ',
            'e' => 'éèẻẽẹêếềểễệ',
            'i' => 'íìỉĩị',
            'o' => 'óòỏõọôốồổỗộơớờởỡợ',
            'u' => 'úùủũụưứừửữự',
            'y' => 'ýỳỷỹỵ',
            'd' => 'đ',
        ];
        foreach ($map as $khong => $co) {
            foreach (preg_split('//u', $co, -1, PREG_SPLIT_NO_EMPTY) as $ch) {
                $s = str_replace($ch, $khong, $s);
            }
        }
        return preg_replace('/\s+/u', ' ', $s);
    }


    // =====================================================================
    // BÊN MỜI: XÁC NHẬN BẢN GIẤY
    // =====================================================================

    /** Tích xác nhận đã nhận bản giấy → báo giá được đưa vào tổng hợp */
    public static function xacNhan(int $id, int $u): array
    {
        $bg = BG_BaoGia_DAL::getById($id);
        if (!$bg || $bg->da_xoa === 1) return ['success' => false, 'message' => 'Không tìm thấy báo giá'];

        $chot = BG_GoiThau_BUS::kiemTraChuaChotSo((int)$bg->goi_thau_id);
        if (!$chot['ok']) return ['success' => false, 'message' => $chot['message']];
        if ((int)$bg->trang_thai === BG_BaoGia_PUBLIC::TT_DA_XAC_NHAN) {
            return ['success' => false, 'message' => 'Báo giá này đã được xác nhận'];
        }
        if ((int)$bg->so_dong_chao === 0) {
            return ['success' => false, 'message' => 'Báo giá chưa có dòng nào có đơn giá — không thể xác nhận'];
        }
        // Chua chot 5 buoc thi con dang lam do — duyet bay gio la duyet ban nhap,
        // ma nha thau van sua tiep duoc sau do (§10.2).
        if ((int)($bg->da_hoan_thanh ?? 0) !== 1) {
            return [
                'success' => false,
                'message' => 'Nhà thầu chưa hoàn thành nộp báo giá — chưa thể duyệt',
            ];
        }

        // Chỉ ghi 1 bảng nghiệp vụ (bg_bao_gia) nên KHÔNG cần transaction.
        // Nhật ký cố ý để ngoài: log hỏng thì chỉ mất vết, không đáng rollback
        // việc duyệt — bọc chung sẽ biến lỗi phụ thành lỗi chặn nghiệp vụ.
        BG_BaoGia_DAL::updateXacNhan($id, BG_BaoGia_PUBLIC::TT_DA_XAC_NHAN, null, $u);
        DM_NhatKyHeThong_DAL::log(
            $u, self::MODULE_LOG,
            "Xác nhận bản giấy báo giá: {$bg->ten_cong_ty} (MST {$bg->ma_so_thue})", 'bg_bao_gia', $id
        );
        return ['success' => true, 'message' => 'Đã xác nhận nhận bản giấy'];
    }

    /** Từ chối báo giá (không đưa vào tổng hợp) */
    public static function tuChoi(int $id, string $lyDo, int $u): array
    {
        $lyDo = trim($lyDo);
        if ($lyDo === '') return ['success' => false, 'message' => 'Vui lòng nhập lý do từ chối'];
        if (mb_strlen($lyDo) > 1000) return ['success' => false, 'message' => 'Lý do tối đa 1000 ký tự'];

        $bg = BG_BaoGia_DAL::getById($id);
        if (!$bg || $bg->da_xoa === 1) return ['success' => false, 'message' => 'Không tìm thấy báo giá'];

        BG_BaoGia_DAL::updateXacNhan($id, BG_BaoGia_PUBLIC::TT_TU_CHOI, $lyDo, $u);
        DM_NhatKyHeThong_DAL::log(
            $u, self::MODULE_LOG,
            "Từ chối báo giá: {$bg->ten_cong_ty} — {$lyDo}", 'bg_bao_gia', $id
        );
        return ['success' => true, 'message' => 'Đã từ chối báo giá'];
    }

    /** Bỏ xác nhận → về trạng thái chờ */
    public static function boXacNhan(int $id, int $u): array
    {
        $bg = BG_BaoGia_DAL::getById($id);
        if (!$bg || $bg->da_xoa === 1) return ['success' => false, 'message' => 'Không tìm thấy báo giá'];

        $chot = BG_GoiThau_BUS::kiemTraChuaChotSo((int)$bg->goi_thau_id);
        if (!$chot['ok']) return ['success' => false, 'message' => $chot['message']];

        BG_BaoGia_DAL::updateXacNhan($id, BG_BaoGia_PUBLIC::TT_CHO_XAC_NHAN, null, $u);
        DM_NhatKyHeThong_DAL::log(
            $u, self::MODULE_LOG, "Bỏ xác nhận báo giá: {$bg->ten_cong_ty}", 'bg_bao_gia', $id
        );
        return ['success' => true, 'message' => 'Đã chuyển về trạng thái chờ xác nhận'];
    }

    public static function trash(int $id, int $u): array
    {
        if ($id <= 0) return ['success' => false, 'message' => 'Thiếu ID'];
        $bg = BG_BaoGia_DAL::getById($id);
        if (!$bg) return ['success' => false, 'message' => 'Không tìm thấy báo giá'];

        BG_BaoGia_DAL::softDelete($id, $u);
        DM_NhatKyHeThong_DAL::log(
            $u, self::MODULE_LOG, "Xóa tạm báo giá: {$bg->ten_cong_ty}", 'bg_bao_gia', $id
        );
        return ['success' => true, 'message' => 'Đã chuyển vào thùng rác'];
    }

    public static function restore(int $id, int $u): array
    {
        if ($id <= 0) return ['success' => false, 'message' => 'Thiếu ID'];
        $bg = BG_BaoGia_DAL::getById($id);
        if (!$bg) return ['success' => false, 'message' => 'Không tìm thấy báo giá'];

        BG_BaoGia_DAL::restore($id, $u);
        DM_NhatKyHeThong_DAL::log(
            $u, self::MODULE_LOG, "Khôi phục báo giá: {$bg->ten_cong_ty}", 'bg_bao_gia', $id
        );
        return ['success' => true, 'message' => 'Đã khôi phục báo giá'];
    }

    public static function delete(int $id, int $u): array
    {
        if ($id <= 0) return ['success' => false, 'message' => 'Thiếu ID'];
        $bg = BG_BaoGia_DAL::getById($id);
        if (!$bg) return ['success' => false, 'message' => 'Không tìm thấy báo giá'];

        // Chi tiết + giá bộ xóa theo (ON DELETE không khai báo) → xóa tay,
        // 3 bảng, bọc transaction. Bỏ sót bg_bao_gia_bo sẽ để lại dòng giá
        // mồ côi trỏ tới báo giá không còn tồn tại.
        try {
            Database::beginTransaction();
            $stmt = Database::getConnection()->prepare("DELETE FROM bg_bao_gia_chi_tiet WHERE bao_gia_id = :id");
            $stmt->execute([':id' => $id]);
            BG_BaoGiaBo_DAL::deleteByBaoGia($id);
            $n = BG_BaoGia_DAL::delete($id);
            Database::commit();

            if ($n === 0) {
                return ['success' => false, 'message' => 'Chỉ xóa vĩnh viễn được bản ghi trong thùng rác'];
            }
            DM_NhatKyHeThong_DAL::log(
                $u, self::MODULE_LOG, "Xóa vĩnh viễn báo giá: {$bg->ten_cong_ty}", 'bg_bao_gia', $id
            );
            return ['success' => true, 'message' => 'Đã xóa vĩnh viễn'];
        } catch (Throwable $ex) {
            Database::rollBack();
            return ['success' => false, 'message' => 'Lỗi: ' . $ex->getMessage()];
        }
    }

    public static function getById(int $id): ?BG_BaoGia_PUBLIC
    {
        return BG_BaoGia_DAL::getById($id);
    }

    public static function getPaged(
        int $page,
        int $pageSize,
        int $goiThauId = 0,
        string $search = '',
        int $trangThai = -1,
        int $daXoa = 0,
        int $coBanKy = -1
    ): array {
        return BG_BaoGia_DAL::getPaged($page, $pageSize, $goiThauId, $search, $trangThai, $daXoa, $coBanKy);
    }

    public static function getChiTiet(int $baoGiaId): array
    {
        return BG_BaoGia_DAL::getChiTiet($baoGiaId);
    }

    /**
     * Dữ liệu TÓM TẮT cho cổng nhà thầu — gom theo BỘ, kèm cấu hình cột theo nhóm.
     *
     * Nhà thầu không điền tay trên web nữa (bảng Mẫu 1 có tới 21 cột, điền trên
     * trình duyệt rất khó): tải file mẫu về điền rồi import, màn hình chỉ hiện
     * tóm tắt để đối chiếu.
     *
     * @return array{nhom:string, cap:array, bo:array, tong:array}
     */
    public static function getBangChaoGia(int $baoGiaId): array
    {
        $bg = BG_BaoGia_DAL::getById($baoGiaId);
        if (!$bg) return ['nhom' => '', 'cap' => [], 'bo' => [], 'tong' => []];

        $gt   = BG_GoiThau_DAL::getById((int)$bg->goi_thau_id);
        $nhom = BG_Nhom_PUBLIC::chuanHoa($gt->nhom ?? null);

        $dsBo    = BG_Bo_DAL::getByGoiThau((int)$bg->goi_thau_id);
        $hangHoa = BG_HangHoa_DAL::getByGoiThau((int)$bg->goi_thau_id);
        $daChao  = BG_BaoGia_DAL::getChiTietMap($baoGiaId);

        // Gom hàng hóa theo bộ
        $theoBo = [];
        foreach ($hangHoa as $hh) {
            $theoBo[(int)($hh['bo_id'] ?? 0)][] = $hh;
        }

        $soDaDapUng = 0;
        $soDaChao   = 0;
        $tongTien   = 0.0;

        // Dựng danh sách dòng chi tiết của 1 nhóm hàng hóa (dùng cho cả bộ
        // lẫn hàng lẻ) — gom vào closure để 2 nhánh không lệch nhau.
        $dungDong = function (array $ds) use ($daChao, $nhom, &$soDaDapUng, &$soDaChao, &$tongTien): array {
            $dong = [];
            foreach ($ds as $hh) {
                $id = (int)$hh['id'];
                $c  = $daChao[$id] ?? null;

                $daDapUng = trim((string)($c['thong_so_chao_gia'] ?? '')) !== '';
                $giaTri   = (float)($c['don_gia'] ?? 0);
                if ($daDapUng) $soDaDapUng++;
                if ($giaTri > 0) {
                    $soDaChao++;
                    // Khớp đúng công thức BG_BaoGia_DAL::updateTongTien():
                    //   - hàng LẺ: luôn cộng.
                    //   - hàng trong BỘ: chỉ cộng khi nhóm KHÔNG nhập giá bộ
                    //     tay (bo_dung_cu — tiền của bộ chính là tổng chi tiết).
                    //     Với he_thong_tbyt tiền nằm ở dòng BỘ, cộng thêm ở đây
                    //     sẽ NHÂN ĐÔI.
                    if (empty($hh['bo_id']) || !BG_Nhom_PUBLIC::giaBoNhapTay($nhom)) {
                        $tongTien += (float)($c['thanh_tien'] ?? 0);
                    }
                }

                $dong[] = [
                    'hang_hoa_id'       => $id,
                    'ma_hh'             => $hh['ma_hh'],
                    'stt_chi_tiet'      => $hh['stt_chi_tiet'],
                    'ten_hang_hoa'      => $hh['ten_hang_hoa'],
                    'thong_so_ky_thuat' => $hh['thong_so_ky_thuat'],
                    'dvt'               => $hh['dvt'],
                    'so_luong'          => (float)$hh['so_luong'],

                    // Mẫu 1 — chỉ trả các cặp nhóm này dùng, GUI khỏi tự lọc
                    'dap_ung'           => self::gomDapUng($c, $nhom),
                    'da_dap_ung'        => $daDapUng,

                    // Mẫu 2
                    'ten_thuong_mai'    => $c['ten_thuong_mai'] ?? '',
                    'model'             => $c['model'] ?? '',
                    'hang_san_xuat'     => $c['hang_san_xuat'] ?? '',
                    'nam_san_xuat'      => $c['nam_san_xuat'] ?? '',
                    'xuat_xu'           => $c['xuat_xu'] ?? '',
                    'don_gia'           => $giaTri,
                    'thanh_tien'        => (float)($c['thanh_tien'] ?? 0),
                    'da_chao'           => $giaTri > 0,
                ];
            }
            return $dong;
        };

        $boOut = [];

        // ---- HÀNG LẺ (bo_id rỗng) đứng TRƯỚC các bộ ----
        // Vật tư, dược phần lớn là hàng lẻ. Trước đây chỉ duyệt $dsBo nên
        // nhóm này rơi vào $theoBo[0] và KHÔNG bao giờ hiện ra — bảng đáp ứng
        // và bảng chào giá trống trơn dù danh mục có hàng.
        $hangLe = $theoBo[0] ?? [];
        if ($hangLe) {
            $boOut[] = [
                'id'               => 0,
                'la_hang_le'       => true,   // GUI dùng cờ này để KHÔNG in dòng tiêu đề bộ
                'ma_bo'            => null,
                'stt_bo'           => null,
                'ten_bo'           => null,
                'yeu_cau_chung'    => null,
                'yeu_cau_khac'     => null,
                'yeu_cau_cau_hinh' => null,
                'nhom_nuoc'        => null,
                'dvt'              => null,
                'so_luong'         => 0.0,
                'chi_tiet'         => $dungDong($hangLe),
            ];
        }

        // Giá nhà thầu chào cho CẢ BỘ — nạp 1 lần, tra theo bo_id.
        // Nhóm mua theo bộ: giá nằm ở đây chứ không cộng dồn từ chi tiết nữa.
        $giaBo = BG_BaoGiaBo_DAL::getMap($baoGiaId);

        // bo_dung_cu: tiền của BỘ = tổng thành tiền các chi tiết bên dưới,
        // nhà thầu không nhập tay. he_thong_tbyt thì ngược lại — xem
        // BG_Nhom_PUBLIC::giaBoNhapTay().
        $boNhapTay = BG_Nhom_PUBLIC::giaBoNhapTay($nhom);

        foreach ($dsBo as $b) {
            $boId = (int)$b['id'];
            $g    = $giaBo[$boId] ?? null;
            $dgBo = (float)($g['don_gia'] ?? 0);

            $ctCuaBo = $dungDong($theoBo[$boId] ?? []);

            if ($boNhapTay) {
                if ($dgBo > 0) {
                    $soDaChao++;
                    $tongTien += (float)($g['thanh_tien'] ?? 0);
                }
                $tienBo = (float)($g['thanh_tien'] ?? 0);
            } else {
                // Cộng dồn từ chi tiết. $tongTien đã được $dungDong() cộng cho
                // từng chi tiết rồi nên KHÔNG cộng lại ở đây (sẽ nhân đôi).
                $tienBo = 0.0;
                foreach ($ctCuaBo as $d) $tienBo += (float)$d['thanh_tien'];
                $dgBo = 0.0;   // nhóm này bộ không có đơn giá riêng
            }

            $boOut[] = [
                'id'               => $boId,
                'la_hang_le'       => false,
                'ma_bo'            => $b['ma_bo'],
                'stt_bo'           => $b['stt_bo'],
                'ten_bo'           => $b['ten_bo'],
                'yeu_cau_chung'    => $b['yeu_cau_chung'],
                'yeu_cau_khac'     => $b['yeu_cau_khac'],
                'yeu_cau_cau_hinh' => $b['yeu_cau_cau_hinh'],
                'nhom_nuoc'        => $b['nhom_nuoc'],
                'dvt'              => $b['dvt'],
                'so_luong'         => (float)$b['so_luong'],

                // ---- Nhà thầu chào cho cả bộ ----
                'ten_thuong_mai'   => (string)($g['ten_thuong_mai'] ?? ''),
                'model'            => (string)($g['model'] ?? ''),
                'hang_san_xuat'    => (string)($g['hang_san_xuat'] ?? ''),
                'nam_san_xuat'     => (string)($g['nam_san_xuat'] ?? ''),
                'xuat_xu'          => (string)($g['xuat_xu'] ?? ''),
                'don_gia'          => $dgBo,
                'thanh_tien'       => $tienBo,
                'da_chao'          => $boNhapTay ? ($dgBo > 0) : ($tienBo > 0),

                // Đáp ứng cấp BỘ — cùng cấu trúc với chi tiết để GUI/Word dùng
                // chung một đường vẽ, không phải phân nhánh.
                'dap_ung'          => self::gomDapUng($g, $nhom),

                // DÙNG LẠI $ctCuaBo đã dựng ở trên — KHÔNG gọi $dungDong() lần
                // nữa: closure đó cộng dồn $tongTien / $soDaChao qua tham chiếu,
                // gọi 2 lần là tổng tiền và số dòng đã chào bị nhân đôi.
                'chi_tiet'         => $ctCuaBo,
            ];
        }

        // Nhãn các cặp đáp ứng để GUI dựng cột — không hardcode ở JS
        $cap = [];
        foreach (BG_Nhom_PUBLIC::capDapUng($nhom) as $khoa => $c) {
            $cap[] = ['khoa' => $khoa, 'nhan' => $c[0]];
        }

        return [
            'nhom'     => $nhom,
            'ten_nhom' => BG_Nhom_PUBLIC::tenNhom($nhom),
            'cap'      => $cap,
            'bo'       => $boOut,
            'tong'     => [
                'so_hang_hoa' => count($hangHoa),
                'so_bo'       => count($dsBo),
                'so_dap_ung'  => $soDaDapUng,
                'so_chao'     => $soDaChao,
                'tong_tien'   => $tongTien,
            ],
        ];
    }

    /**
     * Gom các cặp (đáp ứng / không đạt) của 1 dòng chi tiết theo nhóm gói thầu.
     * Trả mảng khóa => ['dap_ung' => ..., 'khong_dat' => ...] để GUI in thẳng.
     */
    private static function gomDapUng(?array $ct, string $nhom): array
    {
        $out = [];
        foreach (BG_Nhom_PUBLIC::capDapUng($nhom) as $khoa => $c) {
            // $c = [nhãn, cột đáp ứng, cột không đạt]
            $out[$khoa] = [
                'dap_ung'   => (string)($ct[$c[1]] ?? ''),
                'khong_dat' => (string)($ct[$c[2]] ?? ''),
            ];
        }
        // $ct có thể là NULL (bộ / hàng hóa chưa có dòng chào giá nào) — truy
        // cập offset trên null là warning ở PHP 8, phải chặn trước.
        $tl = is_array($ct) ? (string)($ct['tai_lieu_chung_minh'] ?? '') : '';
        if ($tl !== '') {
            $out['tai_lieu'] = ['dap_ung' => $tl, 'khong_dat' => ''];
        }
        return $out;
    }

    public static function thongKe(): array
    {
        return BG_BaoGia_DAL::thongKe();
    }

    // =====================================================================
    // TRA CỨU BÁO GIÁ THEO MÃ SỐ THUẾ
    // =====================================================================

    /**
     * Nhà thầu tra cứu báo giá của chính mình bằng MST.
     *
     * Chỉ trả báo giá khớp CHÍNH XÁC mã số thuế trong đúng gói thầu → nhà thầu
     * không xem được của nhau. Không trả `ip_nop`, `nguoi_tao` (thông tin nội bộ).
     *
     * @return array ['success'=>bool, 'message'=>string, 'data'=>[...]]
     */
    public static function traCuuTheoMst(string $mst, int $goiThauId): array
    {
        $mst = trim($mst);
        if ($mst === '') {
            return ['success' => false, 'message' => 'Vui lòng nhập mã số thuế'];
        }
        if (!preg_match('/^\d{10}(-\d{3})?$/', $mst)) {
            return ['success' => false, 'message' => 'Mã số thuế không hợp lệ (10 số, hoặc dạng 0101234567-001)'];
        }
        if ($goiThauId <= 0) {
            return ['success' => false, 'message' => 'Thiếu mã gói thầu'];
        }

        $rows = BG_BaoGia_DAL::getByMstTrongGoiThau($mst, $goiThauId);
        if (empty($rows)) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy báo giá nào của mã số thuế này trong gói thầu.',
            ];
        }

        $out = [];
        foreach ($rows as $r) {
            $id = (int)$r['id'];
            $out[] = [
                'id'              => $id,
                'ten_cong_ty'     => $r['ten_cong_ty'],
                'ma_so_thue'      => $r['ma_so_thue'],
                'email'           => $r['email'],
                'dien_thoai'      => $r['dien_thoai'],
                'dia_chi'         => $r['dia_chi'],
                'hieu_luc_bao_gia'=> (int)$r['hieu_luc_bao_gia'],
                'ghi_chu'         => $r['ghi_chu'],
                'trang_thai'      => (int)$r['trang_thai'],
                'ten_trang_thai'  => BG_BaoGia_PUBLIC::tenTrangThai((int)$r['trang_thai']),
                'ngay_nop'        => $r['ngay_nop'],
                'ngay_xac_nhan'   => $r['ngay_xac_nhan'],
                'ly_do_tu_choi'   => $r['ly_do_tu_choi'],
                // Bản có dấu + chữ ký: chỉ trả tên gốc để hiển thị, KHÔNG trả
                // tên file thật trên đĩa (tránh lộ đường dẫn lưu trữ)
                // ?? null: phòng trường hợp truy vấn nguồn chưa JOIN bg_file —
                // thiếu key sẽ in Warning ra giữa JSON làm hỏng response.
                'ten_file_goc'       => $r['ten_file_goc'] ?? null,
                'ngay_upload_ban_ky' => $r['ngay_upload_ban_ky'] ?? null,
                'tong_tien'       => (float)$r['tong_tien'],
                'so_dong_chao'    => (int)$r['so_dong_chao'],
                'chi_tiet'        => BG_BaoGia_DAL::getChiTiet($id),
            ];
        }

        return [
            'success' => true,
            'message' => 'Tìm thấy ' . count($out) . ' báo giá',
            'data'    => $out,
        ];
    }

    // =====================================================================
    // BẢN BÁO GIÁ CÓ DẤU & CHỮ KÝ
    // =====================================================================

    /** Dung lượng tối đa cho bản ký (20MB — ảnh chụp/scan thường lớn) */
    const BAN_KY_MAX_SIZE = 20971520;

    // Đuôi/MIME cho phép khai báo ở BG_File_PUBLIC (dùng chung cho mọi loại file)

    /** Thư mục lưu bản ký */
    public static function thuMucBanKy(): string
    {
        $dir = rtrim(AppConfig::UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . 'ban_ky';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Không tạo được thư mục lưu file');
        }
        return $dir;
    }

    /**
     * Sinh tên file bản ký theo quy tắc: <mst>_<slug-goi-thau>.<ext>
     *
     * VD: 0101234567_mua-vat-tu-tieu-hao-phau-thuat-cot-song.pdf
     *
     * Nhìn tên là biết ngay của công ty nào, gói thầu nào — không phải tra DB.
     * Vẫn an toàn: MST chỉ chứa số và dấu '-', slug chỉ chứa [a-z0-9-], nên
     * không thể chèn '/' hay '..' để thoát khỏi thư mục.
     *
     * Trùng tên (1 MST nộp lại, hoặc 2 gói cùng slug) → thêm hậu tố -2, -3...
     *
     * @param string $mst        Mã số thuế nhà thầu
     * @param string $tenGoiThau Tên gói thầu (dùng làm slug)
     * @param string $soThongBao Số thông báo — dự phòng khi tên gói rỗng
     * @param string $ext        Đuôi file đã kiểm tra (pdf/jpg/jpeg/png)
     * @param string $dir        Thư mục lưu, để kiểm tra trùng
     * @param array  $daDung     Tên đã dùng trong cùng lượt xử lý (cho migration)
     * @param string $tenHienTai  Tên file bản ghi này ĐANG dùng — bỏ qua khi kiểm
     *                            trùng, nếu không migration chạy lần 2 sẽ thấy
     *                            chính nó trên đĩa rồi cứ thêm -2, -3... mãi.
     */
    public static function tenFileBanKy(
        string $mst,
        string $tenGoiThau,
        string $soThongBao,
        string $ext,
        string $dir,
        array $daDung = [],
        string $tenHienTai = ''
    ): string {
        // MST: chỉ giữ số và dấu '-' (dạng 0101234567-001)
        $mstSach = preg_replace('/[^0-9-]/', '', trim($mst));
        if ($mstSach === '') $mstSach = 'khong-mst';

        $slug = Helper::slug($tenGoiThau, 60);
        if ($slug === '') $slug = Helper::slug($soThongBao, 30);   // dự phòng
        if ($slug === '') $slug = 'goi-thau';

        $goc = $mstSach . '_' . $slug;
        $ten = $goc . '.' . $ext;

        // Tên đang dùng của chính bản ghi này thì coi như hợp lệ, không cần đổi
        if ($tenHienTai !== '' && $ten === $tenHienTai) {
            return $ten;
        }

        // Tránh ghi đè file của lần nộp trước
        $i = 1;
        while (isset($daDung[$ten]) || is_file($dir . DIRECTORY_SEPARATOR . $ten)) {
            $i++;
            $ten = $goc . '-' . $i . '.' . $ext;
            if ($i > 500) {   // chặn vòng lặp vô hạn
                $ten = $goc . '-' . Helper::randomString(8) . '.' . $ext;
                break;
            }
        }
        return $ten;
    }

    /**
     * Nhà thầu upload bản báo giá có dấu + chữ ký.
     *
     * Upload thành công thì báo giá TỰ CHUYỂN sang "Đã xác nhận" — bản ký chính
     * là bằng chứng thay cho việc bên mời tích tay khi nhận bản giấy.
     *
     * Chỉ cho upload khi báo giá ĐÃ NỘP (có ngay_nop) và có ít nhất 1 dòng giá,
     * tránh trường hợp lách bằng cách upload file rồi thành "đã xác nhận" mà
     * chưa hề chào giá.
     *
     * @param array $file phần tử của $_FILES
     */
    public static function uploadBanKy(int $baoGiaId, array $file, int $u): array
    {
        $bg = BG_BaoGia_DAL::getById($baoGiaId);
        if (!$bg || (int)$bg->da_xoa === 1) {
            return ['success' => false, 'message' => 'Không tìm thấy báo giá'];
        }
        if ((int)($bg->da_hoan_thanh ?? 0) === 1) {
            return ['success' => false, 'message' => 'Báo giá đã hoàn thành — không chỉnh sửa được nữa'];
        }
        if (empty($bg->ngay_nop)) {
            return ['success' => false, 'message' => 'Chưa nộp báo giá — hãy nộp báo giá trước khi tải bản ký lên'];
        }
        if ((int)$bg->so_dong_chao === 0) {
            return ['success' => false, 'message' => 'Báo giá chưa có dòng nào có đơn giá'];
        }

        // --- Kiểm tra file (§3B.9) ---
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $map = [
                UPLOAD_ERR_INI_SIZE  => 'File vượt quá giới hạn của server',
                UPLOAD_ERR_FORM_SIZE => 'File vượt quá giới hạn cho phép',
                UPLOAD_ERR_PARTIAL   => 'File tải lên chưa hoàn tất, hãy thử lại',
                UPLOAD_ERR_NO_FILE   => 'Chưa chọn file',
            ];
            return ['success' => false, 'message' => $map[$file['error']] ?? 'Lỗi tải file'];
        }
        if (!is_uploaded_file($file['tmp_name']) || (int)$file['size'] <= 0) {
            return ['success' => false, 'message' => 'File không hợp lệ hoặc rỗng'];
        }
        if ((int)$file['size'] > self::BAN_KY_MAX_SIZE) {
            return ['success' => false, 'message' => 'File tối đa ' . round(self::BAN_KY_MAX_SIZE / 1048576) . 'MB'];
        }

        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, BG_File_PUBLIC::EXT_CHO_PHEP, true)) {
            return ['success' => false, 'message' => 'Chỉ nhận file PDF hoặc ảnh (JPG, PNG)'];
        }

        // MIME thật, không tin phần mở rộng lẫn $_FILES['type']
        $mime = null;
        if (function_exists('finfo_open')) {
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($fi, $file['tmp_name']);
            finfo_close($fi);
            if (!in_array($mime, BG_File_PUBLIC::MIME_CHO_PHEP, true)) {
                return ['success' => false, 'message' => 'Nội dung file không phải PDF/ảnh hợp lệ'];
            }
            // Đuôi phải khớp nội dung thật (chặn đổi tên .php thành .pdf)
            if (!in_array($mime, BG_File_PUBLIC::EXT_MIME[$ext] ?? [], true)) {
                return ['success' => false, 'message' => 'Đuôi file không khớp nội dung thật của file'];
            }
        }

        try {
            $dir = self::thuMucBanKy();

            // Đổi tên khi lưu — KHÔNG giữ tên gốc từ user (§3B.9).
            // Quy tắc: <mst>_<slug-goi-thau>.<ext> để nhìn tên là biết của ai, gói nào.
            $gt = BG_GoiThau_DAL::getById((int)$bg->goi_thau_id);
            $tenLuu = self::tenFileBanKy(
                (string)$bg->ma_so_thue,
                (string)($gt->ten_goi_thau ?? ''),
                (string)($gt->so_thong_bao ?? ''),
                $ext,
                $dir
            );
            $dich = $dir . DIRECTORY_SEPARATOR . $tenLuu;

            if (!move_uploaded_file($file['tmp_name'], $dich)) {
                return ['success' => false, 'message' => 'Không lưu được file tải lên'];
            }

            // Ghi vào bảng file rồi gán id sang báo giá — 2 bảng nên bọc transaction (§3.7)
            $fileCuId = (int)($bg->file_ban_ky_id ?? 0);
            $fileCu   = $fileCuId > 0 ? BG_File_DAL::getById($fileCuId) : null;

            try {
                Database::beginTransaction();

                $ef = new BG_File_PUBLIC();
                $ef->ten_file     = $tenLuu;
                $ef->ten_file_goc = ExcelHelper::toText($file['name'], 255);
                $ef->duong_dan    = 'ban_ky';
                $ef->loai_file    = $ext;
                $ef->mime_type    = $mime;
                $ef->kich_thuoc   = (int)$file['size'];
                $ef->nhom_file    = BG_File_PUBLIC::NHOM_BAN_KY;
                $ef->nguoi_tao    = $u;
                $fileId = BG_File_DAL::insert($ef);

                BG_BaoGia_DAL::updateBanKy($baoGiaId, $fileId);

                // Upload đè: bản ghi file cũ chuyển sang đã xóa
                if ($fileCuId > 0) BG_File_DAL::softDelete($fileCuId, $u);

                Database::commit();
            } catch (Throwable $exDb) {
                Database::rollBack();
                @unlink($dich);   // DB hỏng thì bỏ luôn file vừa lưu, tránh mồ côi
                return ['success' => false, 'message' => 'Lỗi: ' . $exDb->getMessage()];
            }

            // Xóa file cũ trên đĩa SAU khi DB đã commit
            if ($fileCu && $fileCu->ten_file !== '' && $fileCu->ten_file !== $tenLuu) {
                $duongDanCu = $dir . DIRECTORY_SEPARATOR . basename($fileCu->ten_file);
                if (is_file($duongDanCu)) @unlink($duongDanCu);
            }

            DM_NhatKyHeThong_DAL::log(
                $u, self::MODULE_LOG,
                "Nhà thầu tải bản ký: {$bg->ten_cong_ty} (MST {$bg->ma_so_thue})",
                'bg_bao_gia', $baoGiaId
            );

            return [
                'success' => true,
                'message' => 'Đã tải lên bản báo giá có dấu và chữ ký. '
                           . 'Hãy hoàn tất Bước 5 rồi bấm "Hoàn thành báo giá" để nộp chính thức.',
                'data' => [
                    'ten_file_goc' => ExcelHelper::toText($file['name'], 255),
                ],
            ];
        } catch (Throwable $ex) {
            return ['success' => false, 'message' => 'Lỗi: ' . $ex->getMessage()];
        }
    }

    /**
     * Đường dẫn tuyệt đối tới file bản ký của 1 báo giá.
     * Trả '' nếu không có file hoặc file đã mất.
     *
     * Dùng basename() để chặn path traversal nếu DB bị chèn giá trị lạ.
     */
    public static function duongDanBanKy(int $baoGiaId): string
    {
        $bg = BG_BaoGia_DAL::getById($baoGiaId);
        if (!$bg || empty($bg->file_ban_ky_id)) return '';

        $f = BG_File_DAL::getById((int)$bg->file_ban_ky_id);
        if (!$f || $f->ten_file === '') return '';

        $p = self::thuMucBanKy() . DIRECTORY_SEPARATOR . basename($f->ten_file);
        return is_file($p) ? $p : '';
    }

    /** Bản ghi file bản ký của 1 báo giá (null nếu chưa có) */
    public static function fileBanKy(int $baoGiaId): ?BG_File_PUBLIC
    {
        $bg = BG_BaoGia_DAL::getById($baoGiaId);
        if (!$bg || empty($bg->file_ban_ky_id)) return null;
        return BG_File_DAL::getById((int)$bg->file_ban_ky_id);
    }

    /**
     * Tra cứu TẤT CẢ báo giá của 1 MST — mọi gói thầu, nhóm theo từng gói.
     *
     * Nhà thầu chào nhiều gói cùng lúc nên cần xem hết ở một chỗ.
     * Chỉ trả dữ liệu của đúng MST nhập vào → không lộ công ty khác.
     *
     * @return array ['success'=>bool, 'message'=>string, 'data'=>['tong_ket'=>..., 'nhom'=>[...]]]
     */
    public static function traCuuTatCaTheoMst(string $mst): array
    {
        $mst = trim($mst);
        if ($mst === '') {
            return ['success' => false, 'message' => 'Vui lòng nhập mã số thuế'];
        }
        if (!preg_match('/^\d{10}(-\d{3})?$/', $mst)) {
            return ['success' => false, 'message' => 'Mã số thuế không hợp lệ (10 số, hoặc dạng 0101234567-001)'];
        }

        $rows = BG_BaoGia_DAL::getAllByMst($mst);
        if (empty($rows)) {
            return ['success' => false, 'message' => 'Không tìm thấy báo giá nào của mã số thuế này.'];
        }

        $nhom = [];
        $tenCongTy = '';
        $soDaXacNhan = 0;
        $soChoXacNhan = 0;
        $tongTien = 0.0;

        foreach ($rows as $r) {
            $gtId = (int)$r['goi_thau_id'];
            $tt = (int)$r['trang_thai'];

            if ($tenCongTy === '') $tenCongTy = (string)$r['ten_cong_ty'];
            if ($tt === BG_BaoGia_PUBLIC::TT_DA_XAC_NHAN) $soDaXacNhan++;
            if ($tt === BG_BaoGia_PUBLIC::TT_CHO_XAC_NHAN) $soChoXacNhan++;
            $tongTien += (float)$r['tong_tien'];

            // Còn trong thời gian chào giá thì mới cho sửa/nộp lại
            $ttBaoGia = BG_GoiThau_PUBLIC::tinhTrangThaiBaoGia(
                (int)$r['gt_trang_thai'],
                $r['thoi_gian_mo_bao_gia'],
                $r['thoi_gian_dong_bao_gia']
            );

            if (!isset($nhom[$gtId])) {
                $nhom[$gtId] = [
                    'goi_thau_id'            => $gtId,
                    'so_thong_bao'           => $r['so_thong_bao'],
                    'ten_goi_thau'           => $r['ten_goi_thau'],
                    'thoi_gian_dong_bao_gia' => $r['thoi_gian_dong_bao_gia'],
                    'trang_thai_bao_gia'     => $ttBaoGia,
                    'ten_trang_thai_bao_gia' => BG_GoiThau_PUBLIC::tenTrangThaiBaoGia($ttBaoGia),
                    // Link vào cổng chào giá của gói đó (nhà thầu tự chuyển gói)
                    'url_portal'             => BG_GoiThau_BUS::urlPortal((string)$r['gt_token']),
                    'bao_gia'                => [],
                ];
            }

            $nhom[$gtId]['bao_gia'][] = [
                'id'                 => (int)$r['id'],
                'ten_cong_ty'        => $r['ten_cong_ty'],
                'ma_so_thue'         => $r['ma_so_thue'],
                'email'              => $r['email'],
                'dien_thoai'         => $r['dien_thoai'],
                'hieu_luc_bao_gia'   => (int)$r['hieu_luc_bao_gia'],
                'trang_thai'         => $tt,
                'ten_trang_thai'     => BG_BaoGia_PUBLIC::tenTrangThai($tt),
                'ngay_nop'           => $r['ngay_nop'],
                'ngay_xac_nhan'      => $r['ngay_xac_nhan'],
                'ly_do_tu_choi'      => $r['ly_do_tu_choi'],
                'tong_tien'          => (float)$r['tong_tien'],
                'so_dong_chao'       => (int)$r['so_dong_chao'],
                // ?? null: phòng trường hợp truy vấn nguồn chưa JOIN bg_file —
                // thiếu key sẽ in Warning ra giữa JSON làm hỏng response.
                'ten_file_goc'       => $r['ten_file_goc'] ?? null,
                'ngay_upload_ban_ky' => $r['ngay_upload_ban_ky'] ?? null,
                // Đã chốt hoàn thành thì trang tra cứu chỉ cho XEM, không cho tải đè
                'da_hoan_thanh'      => (int)($r['da_hoan_thanh'] ?? 0),
            ];
        }

        return [
            'success' => true,
            'message' => 'Tìm thấy ' . count($rows) . ' báo giá ở ' . count($nhom) . ' gói thầu',
            'data' => [
                'ma_so_thue' => $mst,
                'tong_ket'   => [
                    'ten_cong_ty'    => $tenCongTy,
                    'so_bao_gia'     => count($rows),
                    'so_goi_thau'    => count($nhom),
                    'da_xac_nhan'    => $soDaXacNhan,
                    'cho_xac_nhan'   => $soChoXacNhan,
                    'tong_tien'      => $tongTien,
                ],
                'nhom' => array_values($nhom),
            ],
        ];
    }

    /** 1 báo giá có đúng của MST này không (mọi gói thầu) */
    public static function baoGiaCuaMst(int $baoGiaId, string $mst): bool
    {
        return BG_BaoGia_DAL::baoGiaCuaMst($baoGiaId, $mst);
    }

    /**
     * Kiểm tra 1 báo giá có thuộc MST + gói thầu đang tra cứu không.
     * Dùng trước khi cho tải Excel ở cổng tra cứu (chặn dò id báo giá của người khác).
     */
    public static function baoGiaThuocMst(int $baoGiaId, string $mst, int $goiThauId): bool
    {
        if ($baoGiaId <= 0) return false;
        foreach (BG_BaoGia_DAL::getByMstTrongGoiThau($mst, $goiThauId) as $r) {
            if ((int)$r['id'] === $baoGiaId) return true;
        }
        return false;
    }

    /**
     * Xuat file Word BAO GIA de nha thau in ra ky + dong dau.
     *
     * Noi dung KHONG con hardcode trong code nua ma lay tu file mau
     * `MPS/bao_gia.docx`. Nguoi dung tu mo file do bang Word de sua font,
     * co chu, can le, them logo... — code chi thay cac {{KEY}} bang du lieu.
     *
     * Xem danh sach Key: php database/tao_mau_word.php
     */
    
    public static function xuatWordBanKy(int $baoGiaId): string
    {
        $bg = BG_BaoGia_DAL::getById($baoGiaId);
        if (!$bg) throw new RuntimeException('Không tìm thấy báo giá');

        $gt = BG_GoiThau_DAL::getById((int)$bg->goi_thau_id);
        if (!$gt) throw new RuntimeException('Không tìm thấy gói thầu');

        // Nhóm quyết định: cột nào bị bỏ khỏi bảng đáp ứng, và tiền của BỘ
        // là do nhà thầu nhập hay cộng dồn từ chi tiết.
        $nhomGt = BG_Nhom_PUBLIC::chuanHoa($gt->nhom ?? null);

        // getBangChaoGia() tra CAY BO — GIU NGUYEN cay, khong trai phang:
        // Mau 1/Mau 2 trong Thu moi co DONG BO rieng (STT bo + Ten bo + yeu
        // cau chung/khac/cau hinh), dong chi tiet chi mang yeu cau ky thuat.
        $tt    = self::getBangChaoGia($baoGiaId);
        $cayBo = $tt['bo'] ?? [];

        $ten  = trim((string)$bg->ten_cong_ty);
        $mst  = trim((string)($bg->ma_so_thue ?? ''));
        $dc   = trim((string)($bg->dia_chi ?? ''));
        $dt   = trim((string)($bg->dien_thoai ?? ''));
        $mail = trim((string)($bg->email ?? ''));

        // Dong tu gioi thieu - thay cho phan "....[ghi ten, dia chi...]" trong mau
        $gioiThieu = $ten;
        if ($mst !== '')  $gioiThieu .= ', MST: ' . $mst;
        if ($dc !== '')   $gioiThieu .= ', địa chỉ: ' . $dc;
        if ($dt !== '')   $gioiThieu .= ', ĐT: ' . $dt;
        if ($mail !== '') $gioiThieu .= ', Email: ' . $mail;

        // ----- Bang chao gia (Mau 2, 14 cot) + bang dap ung (Mau 1, 21 cot) -----
        // Cot lay NGUYEN VAN tu Phu luc II Thu mopi; xem database/tao_mau_word.php.
        $chaoGia = [];
        $dapUng  = [];
        $tong    = 0.0;

        /** O rong cho moi khoa cua 1 bang — tranh thieu khoa lam vo dong */
        $rong = static function (array $khoa): array {
            return array_fill_keys($khoa, '');
        };
        $khoaGia = ['MA','STT_BO','TEN_BO','STT_CT','TEN_HANG_HOA','TEN_THUONG_MAI',
                    'MODEL','HANG_SAN_XUAT','NAM_SAN_XUAT','XUAT_XU','DVT','SO_LUONG',
                    'DON_GIA','THANH_TIEN'];
        $khoaDu  = ['MA','STT_BO','TEN_BO','STT_CT','TEN_HANG_HOA',
                    'YEU_CAU_CHUNG','YEU_CAU_KHAC','YEU_CAU_CAU_HINH','YEU_CAU_KY_THUAT',
                    'YEU_CAU_NHOM_NUOC',
                    'DAP_UNG_CHUNG','KHONG_DAT_CHUNG','DAP_UNG_KHAC','KHONG_DAT_KHAC',
                    'DAP_UNG_CAU_HINH','KHONG_DAT_CAU_HINH','THONG_SO_CHAO_GIA',
                    'DIEM_KHONG_DAT','DAP_UNG_NHOM_NUOC','KHONG_DAT_NHOM_NUOC',
                    'TAI_LIEU_CHUNG_MINH'];

        foreach ($cayBo as $b) {
            $laHangLe = !empty($b['la_hang_le']);
            $ct       = $b['chi_tiet'] ?? [];

            // Chi tiet co du lieu de in o tung bang
            // Nhóm mua theo BỘ và nhập giá bộ TAY (he_thong_tbyt): hàng hóa
            // chi tiết KHÔNG có đơn giá — ô giá của chúng bị khóa ở Mẫu 2.
            // Lọc theo don_gia > 0 sẽ vứt sạch chi tiết, bảng chào giá chỉ còn
            // trơ dòng BỘ. Với nhóm đó phải in MỌI chi tiết thuộc bộ.
            // Hàng LẺ thì vẫn lọc theo giá: không chào thì không in.
            $inHetCt = !$laHangLe && BG_Nhom_PUBLIC::giaBoNhapTay($nhomGt);

            $ctGia = [];
            $ctDu  = [];
            foreach ($ct as $d) {
                $kt = $d['dap_ung']['ky_thuat'] ?? [];
                $coKyThuat = trim((string)($kt['dap_ung'] ?? '')) !== ''
                          || trim((string)($kt['khong_dat'] ?? '')) !== '';
                $coGia = (float)$d['don_gia'] > 0;

                if ($inHetCt || $coGia)              $ctGia[] = $d;
                if ($inHetCt || $coGia || $coKyThuat) $ctDu[] = $d;
            }

            // ---- DONG BO (bo qua voi hang le) ----
            // Chi in khi bo do thuc su co dong chi tiet ben duoi, neu khong
            // se tho ra mot dong tieu de trong tron giua bang.
            // Giá của BỘ do NHÀ THẦU CHÀO (bg_bao_gia_bo), KHÔNG còn cộng dồn
            // từ chi tiết nữa. Với nhóm mua theo bộ, chi tiết bên dưới thường
            // bỏ trống giá nên điều kiện in không thể dựa vào $ctGia — bộ có
            // giá thì phải in, kể cả khi mọi chi tiết đều rỗng.
            $dgBo = (float)($b['don_gia'] ?? 0);
            if (!$laHangLe && ($dgBo > 0 || $ctGia)) {
                $tienBo = (float)($b['thanh_tien'] ?? 0);
                // he_thong_tbyt: tiền nằm ở dòng BỘ → cộng ở đây, chi tiết bên
                // dưới không cộng. bo_dung_cu: tiền nằm ở TỪNG CHI TIẾT → cộng
                // ở vòng chi tiết, ở đây chỉ hiển thị tổng của bộ.
                if (BG_Nhom_PUBLIC::giaBoNhapTay($nhomGt)) $tong += $tienBo;

                $r = $rong($khoaGia);
                $r['MA']             = (string)($b['ma_bo'] ?? '');
                $r['STT_BO']         = (string)($b['stt_bo'] ?? '');
                $r['TEN_BO']         = (string)($b['ten_bo'] ?? '');
                $r['TEN_THUONG_MAI'] = (string)($b['ten_thuong_mai'] ?? '');
                $r['MODEL']          = (string)($b['model'] ?? '');
                $r['HANG_SAN_XUAT']  = (string)($b['hang_san_xuat'] ?? '');
                $r['NAM_SAN_XUAT']   = (string)($b['nam_san_xuat'] ?? '');
                $r['XUAT_XU']        = (string)($b['xuat_xu'] ?? '');
                $r['DVT']            = (string)($b['dvt'] ?? '');
                $r['SO_LUONG']       = self::soVN((float)($b['so_luong'] ?? 0));
                $r['DON_GIA']        = self::soVN($dgBo);
                $r['THANH_TIEN']     = self::soVN($tienBo);
                $chaoGia[] = $r;
            }
            // Dòng BỘ ở bảng đáp ứng: mang CẢ yêu cầu của bên mời LẪN phần đáp
            // ứng nhà thầu điền cho cả bộ. In cả khi không còn chi tiết nào —
            // nhóm mua theo bộ có thể chỉ điền ở dòng bộ.
            $duBo = $b['dap_ung'] ?? [];
            $coDuBo = false;
            foreach ($duBo as $x) {
                if (trim((string)($x['dap_ung'] ?? '')) !== ''
                 || trim((string)($x['khong_dat'] ?? '')) !== '') { $coDuBo = true; break; }
            }
            if (!$laHangLe && ($ctDu || $coDuBo)) {
                $r = $rong($khoaDu);
                $r['MA']               = (string)($b['ma_bo'] ?? '');
                $r['STT_BO']           = (string)($b['stt_bo'] ?? '');
                $r['TEN_BO']           = (string)($b['ten_bo'] ?? '');
                $r['YEU_CAU_CHUNG']    = (string)($b['yeu_cau_chung'] ?? '');
                $r['YEU_CAU_KHAC']     = (string)($b['yeu_cau_khac'] ?? '');
                $r['YEU_CAU_CAU_HINH'] = (string)($b['yeu_cau_cau_hinh'] ?? '');
                $r['YEU_CAU_NHOM_NUOC'] = (string)($b['nhom_nuoc'] ?? '');

                $r['DAP_UNG_CHUNG']      = (string)($duBo['chung']['dap_ung'] ?? '');
                $r['KHONG_DAT_CHUNG']    = (string)($duBo['chung']['khong_dat'] ?? '');
                $r['DAP_UNG_KHAC']       = (string)($duBo['khac']['dap_ung'] ?? '');
                $r['KHONG_DAT_KHAC']     = (string)($duBo['khac']['khong_dat'] ?? '');
                $r['DAP_UNG_CAU_HINH']   = (string)($duBo['cau_hinh']['dap_ung'] ?? '');
                $r['KHONG_DAT_CAU_HINH'] = (string)($duBo['cau_hinh']['khong_dat'] ?? '');
                $r['THONG_SO_CHAO_GIA']  = (string)($duBo['ky_thuat']['dap_ung'] ?? '');
                $r['DIEM_KHONG_DAT']     = (string)($duBo['ky_thuat']['khong_dat'] ?? '');
                $r['DAP_UNG_NHOM_NUOC']  = (string)($duBo['nhom_nuoc']['dap_ung'] ?? '');
                $r['KHONG_DAT_NHOM_NUOC'] = (string)($duBo['nhom_nuoc']['khong_dat'] ?? '');
                $r['TAI_LIEU_CHUNG_MINH'] = (string)($duBo['tai_lieu']['dap_ung'] ?? '');
                $dapUng[] = $r;
            }

            // ---- DONG CHI TIET ----
            foreach ($ctGia as $d) {
                // Khớp đúng công thức BG_BaoGia_DAL::updateTongTien():
                //   - hàng LẺ: luôn cộng.
                //   - hàng trong BỘ: chỉ cộng khi nhóm KHÔNG nhập giá bộ tay
                //     (bo_dung_cu — tiền của bộ chính là tổng các chi tiết).
                //     Với he_thong_tbyt thì tiền đã cộng ở dòng BỘ, cộng thêm
                //     ở đây sẽ NHÂN ĐÔI.
                if ($laHangLe || !BG_Nhom_PUBLIC::giaBoNhapTay($nhomGt)) {
                    $tong += (float)$d['thanh_tien'];
                }
                $r = $rong($khoaGia);
                $r['MA']             = (string)$d['ma_hh'];
                $r['STT_CT']         = (string)($d['stt_chi_tiet'] ?? '');
                $r['TEN_HANG_HOA']   = (string)$d['ten_hang_hoa'];
                $r['TEN_THUONG_MAI'] = (string)$d['ten_thuong_mai'];
                $r['MODEL']          = (string)$d['model'];
                $r['HANG_SAN_XUAT']  = (string)$d['hang_san_xuat'];
                $r['NAM_SAN_XUAT']   = (string)($d['nam_san_xuat'] ?? '');
                $r['XUAT_XU']        = (string)$d['xuat_xu'];
                $r['DVT']            = (string)$d['dvt'];
                $r['SO_LUONG']       = self::soVN((float)$d['so_luong']);
                $r['DON_GIA']        = self::soVN((float)$d['don_gia']);
                $r['THANH_TIEN']     = self::soVN((float)$d['thanh_tien']);
                $chaoGia[] = $r;
            }

            foreach ($ctDu as $d) {
                $du = $d['dap_ung'] ?? [];
                $r = $rong($khoaDu);
                $r['MA']                = (string)$d['ma_hh'];
                $r['STT_CT']            = (string)($d['stt_chi_tiet'] ?? '');
                $r['TEN_HANG_HOA']      = (string)$d['ten_hang_hoa'];
                $r['YEU_CAU_KY_THUAT']  = (string)$d['thong_so_ky_thuat'];
                $r['DAP_UNG_CHUNG']     = (string)($du['chung']['dap_ung'] ?? '');
                $r['KHONG_DAT_CHUNG']   = (string)($du['chung']['khong_dat'] ?? '');
                $r['DAP_UNG_KHAC']      = (string)($du['khac']['dap_ung'] ?? '');
                $r['KHONG_DAT_KHAC']    = (string)($du['khac']['khong_dat'] ?? '');
                $r['DAP_UNG_CAU_HINH']  = (string)($du['cau_hinh']['dap_ung'] ?? '');
                $r['KHONG_DAT_CAU_HINH'] = (string)($du['cau_hinh']['khong_dat'] ?? '');
                $r['THONG_SO_CHAO_GIA'] = (string)($du['ky_thuat']['dap_ung'] ?? '');
                $r['DIEM_KHONG_DAT']    = (string)($du['ky_thuat']['khong_dat'] ?? '');
                $r['DAP_UNG_NHOM_NUOC'] = (string)($du['nhom_nuoc']['dap_ung'] ?? '');
                $r['KHONG_DAT_NHOM_NUOC'] = (string)($du['nhom_nuoc']['khong_dat'] ?? '');
                $r['TAI_LIEU_CHUNG_MINH'] = (string)($du['tai_lieu']['dap_ung'] ?? '');
                $dapUng[] = $r;
            }
        }

        $hieuLuc = (int)$bg->hieu_luc_bao_gia > 0 ? (int)$bg->hieu_luc_bao_gia : 180;

        $data = [
            'GIOI_THIEU'   => $gioiThieu,
            'TEN_CONG_TY'  => $ten,
            'MST'          => $mst,
            'DIA_CHI'      => $dc,
            'DIEN_THOAI'   => $dt,
            'EMAIL'        => $mail,
            'SO_THONG_BAO' => (string)$gt->so_thong_bao,
            'TEN_GOI_THAU' => (string)$gt->ten_goi_thau,
            'HIEU_LUC'     => (string)$hieuLuc,
            // Mẫu dùng key này ở câu "hiệu lực trong vòng N ngày, KỂ TỪ NGÀY …".
            // Mốc tính hiệu lực là NGÀY ĐÓNG nhận báo giá của gói thầu, KHÔNG
            // phải ngày nhà thầu bấm nộp: mỗi nhà thầu nộp một lúc khác nhau,
            // lấy ngày nộp thì hiệu lực của họ lệch nhau, không so sánh được.
            // Cùng công thức với NGAY_HET_HAN ở Thư mời (BG_GoiThau_BUS).
            'NGAY_NOP'     => self::ngayHetHanChaoGia($gt),
            'TONG_TIEN'    => self::soVN($tong),
            'NGAY_IN'      => date('d/m/Y'),
        ];

        $path = BG_HangHoa_BUS::tempDir() . '/BaoGia_'
              . preg_replace('/[^0-9A-Za-z]/', '', $mst !== '' ? $mst : (string)$baoGiaId)
              . '_' . preg_replace('/[^0-9A-Za-z]/', '_', $gt->so_thong_bao)
              . '_' . date('Ymd_His') . '.docx';

        // ----- Bỏ cột KHÔNG áp dụng cho nhóm ở bảng ĐÁP ỨNG (bảng 2) -----
        // Mẫu Word tĩnh 21 cột; để cột rỗng thì bản in bị ép ngang, không đọc
        // nổi. Chỉ số 0-based theo thứ tự cột trong database/tao_mau_word.php:
        //   5,6,7    = Yêu cầu chung / khác / cấu hình
        //   10,11    = Đáp ứng + Không đáp ứng yêu cầu CHUNG
        //   12,13    = ... yêu cầu KHÁC
        //   14,15    = ... yêu cầu CẤU HÌNH
        $boCot = [];
        if (!BG_Nhom_PUBLIC::coYeuCauChung($nhomGt)) {
            // vật tư dược: không có chung / khác / cấu hình
            $boCot = [5, 6, 7, 10, 11, 12, 13, 14, 15];
        } elseif (!BG_Nhom_PUBLIC::coYeuCauCauHinh($nhomGt)) {
            // bộ dụng cụ: có chung + khác, KHÔNG có cấu hình
            $boCot = [7, 14, 15];
        }

        return WordTemplate::render('bao_gia.docx', $path, $data, [
            'CHAO_GIA' => $chaoGia,
            'DAP_UNG'  => $dapUng,
        ], [], $boCot ? [2 => $boCot] : []);
    }

    /** So kieu Viet Nam: 1.234.567 - tra chuoi rong neu <= 0 */
    /**
     * Ngày ĐÓNG nhận báo giá của gói thầu, dạng dd/mm/yyyy.
     *
     * Dùng làm mốc tính hiệu lực báo giá ("có hiệu lực N ngày, kể từ ngày …").
     * Lấy thoi_gian_dong_bao_gia; gói cũ chưa có thì lùi về han_cuoi. Cùng
     * công thức với NGAY_HET_HAN ở Thư mời (BG_GoiThau_BUS::xuatThuMoi) —
     * hai chỗ lệch nhau thì bản giấy nhà thầu ký sẽ khác thư mời đã phát.
     */
    private static function ngayHetHanChaoGia(BG_GoiThau_PUBLIC $gt): string
    {
        foreach ([$gt->thoi_gian_dong_bao_gia ?? null, $gt->han_cuoi ?? null] as $moc) {
            if (empty($moc)) continue;
            $t = strtotime((string)$moc);
            if ($t) return date('d/m/Y', $t);
        }
        return '…/…/……';
    }

    private static function soVN(float $n): string
    {
        if ($n <= 0) return '';
        return abs($n - round($n)) < 0.005
            ? number_format($n, 0, ',', '.')
            : number_format($n, 2, ',', '.');
    }


    /**
     * Nhà thầu chốt xong toàn bộ 4 bước — KHÓA mọi chỉnh sửa.
     *
     * Gọi từ Bước 4 sau khi đã tải lên bản báo giá có ký và đóng dấu.
     * Sau khi chốt, nhà thầu chỉ còn XEM lại, không sửa được nữa; báo giá
     * chuyển sang CHỜ BÊN MỜI DUYỆT (trang_thai vẫn 0 — §10.2).
     *
     * Ghi nhật ký đặt NGOÀI phần ghi dữ liệu và cố ý KHÔNG bọc chung
     * transaction: log hỏng là chuyện phụ, không đáng để rollback việc nhà
     * thầu đã hoàn thành nộp báo giá.
     */
    public static function hoanThanh(int $baoGiaId, int $u): array
    {
        $bg = BG_BaoGia_DAL::getById($baoGiaId);
        if (!$bg || (int)$bg->da_xoa === 1) {
            return ['success' => false, 'message' => 'Không tìm thấy báo giá'];
        }
        if ((int)($bg->da_hoan_thanh ?? 0) === 1) {
            return ['success' => false, 'message' => 'Báo giá này đã hoàn thành trước đó'];
        }
        if (empty($bg->ngay_nop)) {
            return ['success' => false, 'message' => 'Chưa nộp báo giá — hãy hoàn tất Bước 3 trước'];
        }
        if (empty($bg->file_ban_ky_id)) {
            return ['success' => false, 'message' => 'Chưa tải bản báo giá đã ký ở Bước 4'];
        }

        try {
            BG_BaoGia_DAL::updateHoanThanh($baoGiaId);
        } catch (Throwable $ex) {
            return ['success' => false, 'message' => 'Lỗi: ' . $ex->getMessage()];
        }

        DM_NhatKyHeThong_DAL::log(
            $u, self::MODULE_LOG,
            "Nhà thầu hoàn thành 5 bước → báo giá CHỜ DUYỆT: {$bg->ten_cong_ty} (MST {$bg->ma_so_thue})",
            'bg_bao_gia', $baoGiaId
        );

        return [
            'success' => true,
            'message' => 'Đã nộp báo giá thành công. Báo giá đang CHỜ BÊN MỜI DUYỆT. '
                       . 'Từ giờ bạn không chỉnh sửa được nữa.',
            'data'    => ['da_hoan_thanh' => 1],
        ];
    }






    /**
     * Dong goi file nha thau da nop thanh 1 file .zip.
     *
     * Tu khi bo Buoc 5 thi chi con ban ky, nhung giu ham zip de sau them
     * loai file moi khong phai sua lai cho goi.
     *
     * Dung cho nut "Tai tat ca" o module Bao gia — ben moi khong phai bam
     * tung file mot.
     *
     * @return string Duong dan file zip tam (nguoi goi lo viec xoa sau khi gui)
     */
    public static function xuatZipTaiLieu(int $baoGiaId): string
    {
        $bg = BG_BaoGia_DAL::getById($baoGiaId);
        if (!$bg) throw new RuntimeException('Không tìm thấy báo giá');

        // [duong dan tren dia => ten hien trong file zip]
        $ds = [];

        $p1 = self::duongDanBanKy($baoGiaId);
        if ($p1 !== '') {
            $ds[$p1] = 'BanKyBaoGia.' . strtolower(pathinfo($p1, PATHINFO_EXTENSION));
        }

        if (empty($ds)) {
            throw new RuntimeException('Báo giá này chưa có file nào');
        }

        $mst = preg_replace('/[^0-9A-Za-z-]/', '', (string)($bg->ma_so_thue ?? '')) ?: (string)$baoGiaId;
        $path = BG_HangHoa_BUS::tempDir() . '/TaiLieu_' . $mst . '_' . date('Ymd_His') . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Không tạo được file zip');
        }
        foreach ($ds as $tren => $trong) {
            $zip->addFile($tren, $trong);
        }
        $zip->close();

        return $path;
    }

    /**
     * Dọn báo giá làm dở bị bỏ quá lâu.
     *
     * Nhà thầu chưa chốt "Hoàn thành" thì báo giá chưa tính là đã nộp: bên mời
     * không thấy, nhà thầu cũng không tra cứu lại được (§10.2). Bản ghi vẫn
     * phải tồn tại lúc đang làm vì Bước 4-5 cần chỗ chứa file đã upload —
     * không giữ file trong trình duyệt được.
     *
     * Quá $soGio mà vẫn chưa hoàn thành ⇒ coi như bỏ dở, xóa HẲN cả bản ghi,
     * chi tiết dòng giá và file trên đĩa, để DB không tích rác.
     *
     * Chỉ tính theo `ngay_cap_nhat` — nhà thầu còn thao tác thì cột này còn
     * mới, nên người làm chậm trong nhiều giờ vẫn không bị xóa oan.
     *
     * @param int  $soGio  Bỏ dở quá bao nhiêu giờ thì xóa
     * @param bool $thu    true = chỉ liệt kê, không xóa (xem trước cho an toàn)
     * @return array{so_xoa:int, so_file_xoa:int, danh_sach:array}
     */
    public static function donBaoGiaBoDo(int $soGio = 24, bool $thu = false): array
    {
        $soGio = max(1, $soGio);

        $stmt = Database::getConnection()->prepare(
            "SELECT id, ten_cong_ty, ma_so_thue, goi_thau_id, ngay_tao, ngay_cap_nhat
             FROM bg_bao_gia
             WHERE da_xoa = 0
               AND da_hoan_thanh = 0
               AND ngay_cap_nhat < DATE_SUB(NOW(), INTERVAL :gio HOUR)
             ORDER BY id"
        );
        $stmt->execute([':gio' => $soGio]);
        $rows = $stmt->fetchAll();

        $soXoa = 0;
        $soFile = 0;
        $ds = [];

        foreach ($rows as $r) {
            $id = (int)$r['id'];
            $ds[] = [
                'id'            => $id,
                'ten_cong_ty'   => $r['ten_cong_ty'],
                'ma_so_thue'    => $r['ma_so_thue'],
                'ngay_cap_nhat' => $r['ngay_cap_nhat'],
            ];
            if ($thu) continue;

            // Lấy file TRƯỚC khi xóa bản ghi — xóa rồi thì không truy ngược được
            $files = BG_File_DAL::getAllByBaoGia($id);

            try {
                Database::beginTransaction();
                $s = Database::getConnection()->prepare(
                    "DELETE FROM bg_bao_gia_chi_tiet WHERE bao_gia_id = :id"
                );
                $s->execute([':id' => $id]);

                foreach ($files as $f) {
                    $s = Database::getConnection()->prepare("DELETE FROM bg_file WHERE id = :id");
                    $s->execute([':id' => (int)$f['id']]);
                }

                $s = Database::getConnection()->prepare("DELETE FROM bg_bao_gia WHERE id = :id");
                $s->execute([':id' => $id]);
                Database::commit();
            } catch (Throwable $ex) {
                Database::rollBack();
                continue;   // bỏ qua bản ghi lỗi, vẫn dọn tiếp các bản ghi khác
            }

            // Xóa file trên đĩa SAU khi DB đã commit — commit hỏng thì file còn
            // nguyên, chứ không mất file mà bản ghi vẫn còn.
            foreach ($files as $f) {
                $p = rtrim(AppConfig::UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR
                   . trim((string)$f['duong_dan'], '/\\') . DIRECTORY_SEPARATOR
                   . (string)$f['ten_file'];
                if (is_file($p) && @unlink($p)) $soFile++;
            }

            $soXoa++;
        }

        return ['so_xoa' => $soXoa, 'so_file_xoa' => $soFile, 'danh_sach' => $ds];
    }

}
