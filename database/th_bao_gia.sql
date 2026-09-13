-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th9 13, 2026 lúc 07:15 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `th_bao_gia`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bg_bao_gia`
--

CREATE TABLE `bg_bao_gia` (
  `id` int(11) NOT NULL,
  `goi_thau_id` int(11) NOT NULL,
  `ten_cong_ty` varchar(500) NOT NULL,
  `ma_so_thue` varchar(50) DEFAULT NULL,
  `email` varchar(200) DEFAULT NULL,
  `dien_thoai` varchar(50) DEFAULT NULL,
  `dia_chi` varchar(1000) DEFAULT NULL,
  `hieu_luc_bao_gia` int(11) DEFAULT 0 COMMENT 'Số ngày hiệu lực nhà thầu cam kết',
  `ghi_chu` text DEFAULT NULL,
  `trang_thai` int(11) DEFAULT 0 COMMENT '0=Cho xac nhan, 1=Da xac nhan ban giay, 2=Tu choi',
  `da_hoan_thanh` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Nhà thầu đã chốt xong 5 bước — khóa mọi chỉnh sửa',
  `ngay_hoan_thanh` datetime DEFAULT NULL COMMENT 'Thời điểm nhà thầu bấm hoàn thành',
  `ngay_nop` datetime DEFAULT NULL COMMENT 'Thời điểm nhà thầu nộp online',
  `ngay_xac_nhan` datetime DEFAULT NULL COMMENT 'Thời điểm tích xác nhận bản giấy',
  `nguoi_xac_nhan` int(11) DEFAULT NULL,
  `ly_do_tu_choi` varchar(1000) DEFAULT NULL,
  `file_ban_ky_id` int(11) DEFAULT NULL COMMENT 'Trỏ tới bg_file.id — bản báo giá có dấu & chữ ký',
  `tong_tien` decimal(20,2) DEFAULT 0.00 COMMENT 'Cache tổng thành tiền',
  `ip_nop` varchar(45) DEFAULT NULL,
  `ngay_tao` datetime DEFAULT current_timestamp(),
  `ngay_cap_nhat` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `nguoi_tao` int(11) DEFAULT NULL,
  `nguoi_cap_nhat` int(11) DEFAULT NULL,
  `da_xoa` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `bg_bao_gia`
--

INSERT INTO `bg_bao_gia` (`id`, `goi_thau_id`, `ten_cong_ty`, `ma_so_thue`, `email`, `dien_thoai`, `dia_chi`, `hieu_luc_bao_gia`, `ghi_chu`, `trang_thai`, `da_hoan_thanh`, `ngay_hoan_thanh`, `ngay_nop`, `ngay_xac_nhan`, `nguoi_xac_nhan`, `ly_do_tu_choi`, `file_ban_ky_id`, `tong_tien`, `ip_nop`, `ngay_tao`, `ngay_cap_nhat`, `nguoi_tao`, `nguoi_cap_nhat`, `da_xoa`) VALUES
(1, 1, 'Công ty TNHH Thiết bị Y tế An Phát', '0101234567', 'kinhdoanh@anphat.vn', '0243 8765 432', 'Số 12 Nguyễn Trãi, Thanh Xuân, Hà Nội', 180, NULL, 1, 1, '2026-08-20 23:11:59', '2026-08-17 23:11:59', '2026-08-20 23:11:59', 1, NULL, NULL, 1624500000.00, '192.168.1.20', '2026-08-20 23:11:59', '2026-08-20 23:11:59', 1, 1, 0),
(2, 1, 'Công ty CP Vật tư Y tế Bình Minh', '0209876543', 'sales@binhminhmed.com.vn', '0283 5566 778', '45 Lê Lợi, Quận 1, TP Hồ Chí Minh', 180, NULL, 1, 1, '2026-08-20 23:11:59', '2026-08-18 23:11:59', '2026-08-20 23:11:59', 1, NULL, NULL, 1494480000.00, '192.168.1.21', '2026-08-20 23:11:59', '2026-08-20 23:11:59', 1, 1, 0),
(27, 2, 'Nhà thầu test4444', '1234567891', 'ducnvit@gmail.com', '', '', 120, '', 1, 1, '2026-08-21 23:32:17', '2026-08-21 22:33:40', '2026-08-21 23:32:17', NULL, NULL, 15, 4000000.00, '127.0.0.1', '2026-08-21 22:33:17', '2026-08-21 23:32:17', 1, 1, 0),
(31, 1, 'Nhà thầu mới', '1234567891', '', '', '', 180, '', 2, 1, '2026-08-21 23:27:22', '2026-08-21 23:21:54', '2026-08-22 07:25:51', 1, 'thiếu catolog', 13, 1000000.00, '127.0.0.1', '2026-08-21 23:21:34', '2026-08-22 07:25:51', 1, 1, 0),
(34, 2, 'Nhà thầu test 123', '1234567441', '', '', '', 120, '', 1, 1, '2026-08-22 07:10:13', '2026-08-22 07:09:09', '2026-08-22 07:09:32', NULL, NULL, 17, 400000.00, '127.0.0.1', '2026-08-22 07:08:43', '2026-08-22 07:25:30', 1, 1, 1),
(35, 1, 'Nhà thầu test4444', '1234517891', '', '', '', 180, '', 1, 1, '2026-08-22 09:44:45', '2026-08-22 09:44:28', '2026-08-22 09:44:45', NULL, NULL, 19, 1000000.00, '127.0.0.1', '2026-08-22 09:44:16', '2026-08-22 09:45:01', 1, 1, 0),
(72, 36, 'Công ty CP Thiết bị Y tế Trường Sơn', '0104567890', 'sales@truongson-med.vn', '024 3762 1188', 'Số 27 Láng Hạ, Đống Đa, Hà Nội', 180, NULL, 1, 1, '2026-09-07 20:26:31', '2026-09-07 20:26:31', '2026-09-10 20:26:31', 1, NULL, NULL, 120800000.00, '192.168.10.30', '2026-09-10 20:26:31', '2026-09-10 20:26:31', 1, 1, 0),
(73, 36, 'Công ty TNHH Y tế Đông Dương', '0305678901', 'info@dongduong-medical.vn', '028 3925 4477', '119 Nguyễn Đình Chiểu, Quận 3, TP Hồ Chí Minh', 180, NULL, 1, 1, '2026-09-08 20:26:31', '2026-09-08 20:26:31', '2026-09-10 20:26:31', 1, NULL, NULL, 113552000.00, '192.168.10.31', '2026-09-10 20:26:31', '2026-09-10 20:26:31', 1, 1, 0),
(74, 36, 'Công ty CP Đầu tư Thiết bị Y tế Việt Nhật', '0206789012', 'contact@vietnhat-med.com.vn', '0225 3852 663', '58 Điện Biên Phủ, Hồng Bàng, Hải Phòng', 180, NULL, 0, 1, '2026-09-09 20:26:31', '2026-09-09 20:26:31', NULL, NULL, NULL, NULL, 121124000.00, '192.168.10.32', '2026-09-10 20:26:31', '2026-09-10 20:26:31', 1, 1, 0),
(75, 37, 'Công ty CP Thiết bị Y tế Trường Sơn', '0104567890', 'sales@truongson-med.vn', '024 3762 1188', 'Số 27 Láng Hạ, Đống Đa, Hà Nội', 180, NULL, 1, 1, '2026-09-07 20:26:31', '2026-09-07 20:26:31', '2026-09-10 20:26:31', 1, NULL, NULL, 4365000000.00, '192.168.10.30', '2026-09-10 20:26:31', '2026-09-10 20:26:31', 1, 1, 0),
(76, 37, 'Công ty TNHH Y tế Đông Dương', '0305678901', 'info@dongduong-medical.vn', '028 3925 4477', '119 Nguyễn Đình Chiểu, Quận 3, TP Hồ Chí Minh', 180, NULL, 1, 1, '2026-09-08 20:26:31', '2026-09-08 20:26:31', '2026-09-10 20:26:31', 1, NULL, NULL, 4103100000.00, '192.168.10.31', '2026-09-10 20:26:31', '2026-09-10 20:26:31', 1, 1, 0),
(77, 37, 'Công ty CP Đầu tư Thiết bị Y tế Việt Nhật', '0206789012', 'contact@vietnhat-med.com.vn', '0225 3852 663', '58 Điện Biên Phủ, Hồng Bàng, Hải Phòng', 180, NULL, 0, 1, '2026-09-09 20:26:31', '2026-09-09 20:26:31', NULL, NULL, NULL, NULL, 4622400000.00, '192.168.10.32', '2026-09-10 20:26:31', '2026-09-10 20:26:31', 1, 1, 0),
(84, 37, 'Nhà thầu test', '1232267891', '', '', '', 180, '', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, '127.0.0.1', '2026-09-10 23:09:22', '2026-09-10 23:09:22', 1, 1, 0),
(85, 51, 'công ty TNHH A', '2901788301', 'nguyenthiluong20071988@gmail.com', '0934420788', 'số 4 tôn thất tùng', 180, '', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 1000000.00, '192.168.103.154', '2026-09-11 09:47:11', '2026-09-11 09:55:52', 1, 1, 0),
(86, 49, 'công ty TNHH A', '2900621000', 'nguyenthiluong20071988@gmail.com', '0934420788', 'số 4 tôn thất tùng', 180, '', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, '192.168.103.154', '2026-09-11 10:38:24', '2026-09-11 10:38:24', 1, 1, 0),
(87, 52, 'công ty TNHH A', '2900621000', 'nguyenthiluong20071988@gmail.com', '0934420788', 'số 4 tôn thất tùng', 180, '', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 25348000.00, '192.168.103.154', '2026-09-11 10:58:41', '2026-09-11 11:14:32', 1, 1, 0),
(88, 49, 'công ty TNHH B', '2900621006', 'nguyenthiluong20071988@gmail.com', '0934420788', '', 180, '', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 50060000.00, '192.168.103.154', '2026-09-11 11:26:34', '2026-09-11 11:33:30', 1, 1, 0),
(89, 2, 'Nhà thầu mới', '0111234567', '', '', '', 120, '', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, '171.253.51.216', '2026-09-12 16:50:48', '2026-09-12 16:50:48', 1, 1, 0),
(90, 51, 'Công Ty BCV', '2900621156', 'nguyenthiluong20071988@gmail.com', '0934420788', 'Nghệ an', 180, '', 0, 0, NULL, '2026-09-13 09:45:25', NULL, NULL, NULL, NULL, 240000000.00, '14.182.246.15', '2026-09-13 08:55:26', '2026-09-13 09:45:25', 1, 1, 0),
(91, 51, 'Nhà thầu Đức', '1234564991', '', '', '', 180, '', 0, 0, NULL, '2026-09-13 10:48:04', NULL, NULL, NULL, NULL, 160000.00, '171.253.51.216', '2026-09-13 10:40:28', '2026-09-13 10:48:04', 1, 1, 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bg_bao_gia_chi_tiet`
--

CREATE TABLE `bg_bao_gia_chi_tiet` (
  `id` int(11) NOT NULL,
  `bao_gia_id` int(11) NOT NULL,
  `hang_hoa_id` int(11) NOT NULL,
  `ten_thuong_mai` varchar(1000) DEFAULT NULL COMMENT 'L',
  `model` varchar(500) DEFAULT NULL COMMENT 'M: Ký, mã, nhãn hiệu, model',
  `hang_san_xuat` varchar(500) DEFAULT NULL COMMENT 'O',
  `nam_san_xuat` varchar(20) DEFAULT NULL COMMENT 'Mẫu 2 — năm sản xuất',
  `xuat_xu` varchar(500) DEFAULT NULL COMMENT 'P',
  `quy_cach` varchar(500) DEFAULT NULL COMMENT 'R',
  `don_gia` decimal(20,2) DEFAULT 0.00 COMMENT 'V: đã gồm thuế phí',
  `thanh_tien` decimal(20,2) DEFAULT 0.00 COMMENT 'W: tính = don_gia * so_luong',
  `don_gia_trung_thau` decimal(20,2) DEFAULT 0.00 COMMENT 'Y',
  `tai_lieu_tham_chieu` text DEFAULT NULL COMMENT 'Z',
  `thong_so_chao_gia` text DEFAULT NULL COMMENT 'AC: Thông số kỹ thuật chào giá',
  `diem_khong_dat` text DEFAULT NULL COMMENT 'AD: Các điểm không đạt kèm thuyết minh',
  `ngay_tao` datetime DEFAULT current_timestamp(),
  `ngay_cap_nhat` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `da_xoa` int(11) DEFAULT 0,
  `dap_ung_chung` text DEFAULT NULL COMMENT 'Cột (11) Mẫu 1',
  `khong_dat_chung` text DEFAULT NULL COMMENT 'Cột (12)',
  `dap_ung_khac` text DEFAULT NULL COMMENT 'Cột (13)',
  `khong_dat_khac` text DEFAULT NULL COMMENT 'Cột (14)',
  `dap_ung_cau_hinh` text DEFAULT NULL COMMENT 'Cột (15)',
  `khong_dat_cau_hinh` text DEFAULT NULL COMMENT 'Cột (16)',
  `dap_ung_nhom_nuoc` text DEFAULT NULL COMMENT 'Cột (19)',
  `khong_dat_nhom_nuoc` text DEFAULT NULL COMMENT 'Cột (20)',
  `tai_lieu_chung_minh` text DEFAULT NULL COMMENT 'Cột (21) — cam kết, catalog, HDSD'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `bg_bao_gia_chi_tiet`
--

INSERT INTO `bg_bao_gia_chi_tiet` (`id`, `bao_gia_id`, `hang_hoa_id`, `ten_thuong_mai`, `model`, `hang_san_xuat`, `nam_san_xuat`, `xuat_xu`, `quy_cach`, `don_gia`, `thanh_tien`, `don_gia_trung_thau`, `tai_lieu_tham_chieu`, `thong_so_chao_gia`, `diem_khong_dat`, `ngay_tao`, `ngay_cap_nhat`, `da_xoa`, `dap_ung_chung`, `khong_dat_chung`, `dap_ung_khac`, `khong_dat_khac`, `dap_ung_cau_hinh`, `khong_dat_cau_hinh`, `dap_ung_nhom_nuoc`, `khong_dat_nhom_nuoc`, `tai_lieu_chung_minh`) VALUES
(1, 1, 1, 'Model A-1 Series', 'REF-694000', 'Corin Ltd', NULL, 'Mỹ', '1 bộ/hộp', 1250000.00, 125000000.00, 1213000.00, 'Hợp đồng số 12/HĐ-BV ngày 01/03/2025', 'Đáp ứng đầy đủ các thông số kỹ thuật yêu cầu của hồ sơ mời chào giá.', NULL, '2026-08-20 23:11:59', '2026-08-21 17:11:04', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 1, 2, 'Model A-2 Series', 'REF-694001', 'Corin Ltd', NULL, 'Mỹ', '1 bộ/hộp', 385000.00, 115500000.00, 373000.00, 'Hợp đồng số 12/HĐ-BV ngày 01/03/2025', 'Đáp ứng đầy đủ các thông số kỹ thuật yêu cầu của hồ sơ mời chào giá.', NULL, '2026-08-20 23:11:59', '2026-08-20 23:11:59', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 1, 3, 'Model A-3 Series', 'REF-694002', 'Corin Ltd', NULL, 'Mỹ', '1 bộ/hộp', 12800000.00, 640000000.00, 12416000.00, 'Hợp đồng số 12/HĐ-BV ngày 01/03/2025', 'Đáp ứng đầy đủ các thông số kỹ thuật yêu cầu của hồ sơ mời chào giá.', NULL, '2026-08-20 23:11:59', '2026-08-20 23:11:59', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 1, 4, 'Model A-4 Series', 'REF-694003', 'Corin Ltd', NULL, 'Mỹ', '1 bộ/hộp', 4500000.00, 360000000.00, 4365000.00, 'Hợp đồng số 12/HĐ-BV ngày 01/03/2025', 'Đáp ứng đầy đủ các thông số kỹ thuật yêu cầu của hồ sơ mời chào giá.', NULL, '2026-08-20 23:11:59', '2026-08-20 23:11:59', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 1, 5, 'Model A-5 Series', 'REF-694004', 'Corin Ltd', NULL, 'Mỹ', '1 bộ/hộp', 3200000.00, 384000000.00, 3104000.00, 'Hợp đồng số 12/HĐ-BV ngày 01/03/2025', 'Đáp ứng đầy đủ các thông số kỹ thuật yêu cầu của hồ sơ mời chào giá.', NULL, '2026-08-20 23:11:59', '2026-08-20 23:11:59', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 2, 1, 'Model B-1 Series', 'REF-694100', 'B.Braun', NULL, 'Đức', '1 bộ/hộp', 1150000.00, 115000000.00, 1213000.00, 'Hợp đồng số 13/HĐ-BV ngày 01/03/2025', 'Đáp ứng đầy đủ các thông số kỹ thuật yêu cầu của hồ sơ mời chào giá.', NULL, '2026-08-20 23:11:59', '2026-08-20 23:11:59', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 2, 2, 'Model B-2 Series', 'REF-694101', 'B.Braun', NULL, 'Đức', '1 bộ/hộp', 354000.00, 106200000.00, 373000.00, 'Hợp đồng số 13/HĐ-BV ngày 01/03/2025', 'Đáp ứng đầy đủ các thông số kỹ thuật yêu cầu của hồ sơ mời chào giá.', NULL, '2026-08-20 23:11:59', '2026-08-20 23:11:59', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 2, 3, 'Model B-3 Series', 'REF-694102', 'B.Braun', NULL, 'Đức', '1 bộ/hộp', 11776000.00, 588800000.00, 12416000.00, 'Hợp đồng số 13/HĐ-BV ngày 01/03/2025', 'Đáp ứng đầy đủ các thông số kỹ thuật yêu cầu của hồ sơ mời chào giá.', NULL, '2026-08-20 23:11:59', '2026-08-20 23:11:59', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, 2, 4, 'Model B-4 Series', 'REF-694103', 'B.Braun', NULL, 'Đức', '1 bộ/hộp', 4140000.00, 331200000.00, 4365000.00, 'Hợp đồng số 13/HĐ-BV ngày 01/03/2025', 'Đáp ứng đầy đủ các thông số kỹ thuật yêu cầu của hồ sơ mời chào giá.', NULL, '2026-08-20 23:11:59', '2026-08-20 23:11:59', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, 2, 5, 'Model B-5 Series', 'REF-694104', 'B.Braun', NULL, 'Đức', '1 bộ/hộp', 2944000.00, 353280000.00, 3104000.00, 'Hợp đồng số 13/HĐ-BV ngày 01/03/2025', 'Đáp ứng đầy đủ các thông số kỹ thuật yêu cầu của hồ sơ mời chào giá.', NULL, '2026-08-20 23:11:59', '2026-08-20 23:11:59', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(79, 27, 6, 'a', 'a', 'a', NULL, 'a', 'a', 100000.00, 4000000.00, 0.00, NULL, 'ccc', 'ccc', '2026-08-21 22:33:26', '2026-08-21 22:33:39', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(80, 27, 7, NULL, 'a', 'a', NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, 'c', 'c', '2026-08-21 22:33:26', '2026-08-21 22:33:39', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(81, 27, 8, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, 'c', 'c', '2026-08-21 22:33:26', '2026-08-21 22:33:39', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(82, 27, 9, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, NULL, '2026-08-21 22:33:26', '2026-08-21 22:33:39', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(117, 31, 1, NULL, NULL, NULL, NULL, NULL, '1', 10000.00, 1000000.00, 0.00, NULL, 'c', NULL, '2026-08-21 23:21:41', '2026-08-21 23:21:52', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(118, 31, 2, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, 'q', NULL, '2026-08-21 23:21:41', '2026-08-21 23:21:52', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(119, 31, 3, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, 'q', NULL, '2026-08-21 23:21:41', '2026-08-21 23:21:52', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(120, 31, 4, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, 'q', NULL, '2026-08-21 23:21:41', '2026-08-21 23:21:52', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(121, 31, 5, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, NULL, '2026-08-21 23:21:41', '2026-08-21 23:21:52', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(147, 34, 6, NULL, NULL, NULL, NULL, NULL, NULL, 10000.00, 400000.00, 0.00, NULL, '1', NULL, '2026-08-22 07:09:00', '2026-08-22 07:09:08', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(148, 34, 7, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, NULL, '2026-08-22 07:09:00', '2026-08-22 07:09:08', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(149, 34, 8, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, NULL, '2026-08-22 07:09:00', '2026-08-22 07:09:08', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(150, 34, 9, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, NULL, '2026-08-22 07:09:00', '2026-08-22 07:09:08', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(155, 35, 1, NULL, NULL, NULL, NULL, NULL, NULL, 10000.00, 1000000.00, 0.00, NULL, '123123', NULL, '2026-08-22 09:44:22', '2026-08-22 09:44:27', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(156, 35, 2, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, NULL, '2026-08-22 09:44:22', '2026-08-22 09:44:27', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(157, 35, 3, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, NULL, '2026-08-22 09:44:22', '2026-08-22 09:44:27', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(158, 35, 4, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, NULL, '2026-08-22 09:44:22', '2026-08-22 09:44:27', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(159, 35, 5, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, NULL, '2026-08-22 09:44:22', '2026-08-22 09:44:27', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(246, 56, 1, NULL, NULL, NULL, NULL, NULL, NULL, 50000.00, 5000000.00, 0.00, NULL, NULL, NULL, '2026-09-06 09:41:48', '2026-09-06 09:41:48', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(247, 56, 2, NULL, NULL, NULL, NULL, NULL, NULL, 50000.00, 15000000.00, 0.00, NULL, NULL, NULL, '2026-09-06 09:41:48', '2026-09-06 09:41:48', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(248, 56, 3, NULL, NULL, NULL, NULL, NULL, NULL, 50000.00, 2500000.00, 0.00, NULL, NULL, NULL, '2026-09-06 09:41:48', '2026-09-06 09:41:48', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(249, 56, 4, NULL, NULL, NULL, NULL, NULL, NULL, 50000.00, 4000000.00, 0.00, NULL, NULL, NULL, '2026-09-06 09:41:48', '2026-09-06 09:41:48', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(250, 56, 5, NULL, NULL, NULL, NULL, NULL, NULL, 50000.00, 6000000.00, 0.00, NULL, NULL, NULL, '2026-09-06 09:41:48', '2026-09-06 09:41:48', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(358, 72, 96, 'Olympus 3E05', 'MDL-223DB9', 'Olympus', '2025', 'Nhật Bản', NULL, 2400000.00, 4800000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 24 tháng, đào tạo vận hành tại chỗ.', NULL, NULL, NULL, 'Nhật Bản', NULL, 'Catalog trang 1-3, giấy phép lưu hành số 2400'),
(359, 72, 97, 'Olympus EF76', 'MDL-02BFA1', 'Olympus', '2025', 'Nhật Bản', NULL, 3800000.00, 7600000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 24 tháng, đào tạo vận hành tại chỗ.', NULL, NULL, NULL, 'Nhật Bản', NULL, 'Catalog trang 4-6, giấy phép lưu hành số 2401'),
(360, 72, 98, 'Olympus 9E49', 'MDL-836881', 'Olympus', '2025', 'Nhật Bản', NULL, 5200000.00, 10400000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 24 tháng, đào tạo vận hành tại chỗ.', NULL, NULL, NULL, 'Nhật Bản', NULL, 'Catalog trang 7-9, giấy phép lưu hành số 2402'),
(361, 72, 99, 'Olympus B049', 'MDL-1D8F31', 'Olympus', '2025', 'Nhật Bản', NULL, 4100000.00, 16400000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 24 tháng, đào tạo vận hành tại chỗ.', NULL, NULL, NULL, 'Nhật Bản', NULL, 'Catalog trang 10-12, giấy phép lưu hành số 2403'),
(362, 72, 100, 'Olympus 4493', 'MDL-F3D43C', 'Olympus', '2025', 'Nhật Bản', NULL, 2400000.00, 7200000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 24 tháng, đào tạo vận hành tại chỗ.', NULL, NULL, NULL, 'Nhật Bản', NULL, 'Catalog trang 13-15, giấy phép lưu hành số 2404'),
(363, 72, 101, 'Olympus FB04', 'MDL-DB1539', 'Olympus', '2025', 'Nhật Bản', NULL, 3800000.00, 11400000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 24 tháng, đào tạo vận hành tại chỗ.', NULL, NULL, NULL, 'Nhật Bản', NULL, 'Catalog trang 16-18, giấy phép lưu hành số 2405'),
(364, 72, 102, 'Olympus 6B9A', 'MDL-8840C7', 'Olympus', '2025', 'Nhật Bản', NULL, 5200000.00, 31200000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 24 tháng, đào tạo vận hành tại chỗ.', NULL, NULL, NULL, 'Nhật Bản', NULL, 'Catalog trang 19-21, giấy phép lưu hành số 2406'),
(365, 72, 103, 'Olympus 546A', 'MDL-B09BC4', 'Olympus', '2025', 'Nhật Bản', NULL, 4100000.00, 24600000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 24 tháng, đào tạo vận hành tại chỗ.', NULL, NULL, NULL, 'Nhật Bản', NULL, 'Catalog trang 22-24, giấy phép lưu hành số 2407'),
(366, 72, 104, 'Olympus 2343', 'MDL-845A77', 'Olympus', '2025', 'Nhật Bản', NULL, 2400000.00, 7200000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 24 tháng, đào tạo vận hành tại chỗ.', NULL, NULL, NULL, 'Nhật Bản', NULL, 'Catalog trang 25-27, giấy phép lưu hành số 2408'),
(367, 73, 96, 'B.Braun 3E05', 'MDL-032A02', 'B.Braun', '2026', 'Đức', NULL, 2256000.00, 4512000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 30 tháng, đào tạo vận hành tại chỗ.', NULL, NULL, NULL, 'Đức', NULL, 'Catalog trang 1-3, giấy phép lưu hành số 2417'),
(368, 73, 97, 'B.Braun EF76', 'MDL-8AE7EF', 'B.Braun', '2026', 'Đức', NULL, 3572000.00, 7144000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 30 tháng, đào tạo vận hành tại chỗ.', NULL, NULL, NULL, 'Đức', NULL, 'Catalog trang 4-6, giấy phép lưu hành số 2418'),
(369, 73, 98, 'B.Braun 9E49', 'MDL-DE09EA', 'B.Braun', '2026', 'Đức', NULL, 4888000.00, 9776000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 30 tháng, đào tạo vận hành tại chỗ.', NULL, NULL, NULL, 'Đức', NULL, 'Catalog trang 7-9, giấy phép lưu hành số 2419'),
(370, 73, 99, 'B.Braun B049', 'MDL-AD2D74', 'B.Braun', '2026', 'Đức', NULL, 3854000.00, 15416000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 30 tháng, đào tạo vận hành tại chỗ.', NULL, NULL, NULL, 'Đức', NULL, 'Catalog trang 10-12, giấy phép lưu hành số 2420'),
(371, 73, 100, 'B.Braun 4493', 'MDL-46BA23', 'B.Braun', '2026', 'Đức', NULL, 2256000.00, 6768000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 30 tháng, đào tạo vận hành tại chỗ.', NULL, NULL, NULL, 'Đức', NULL, 'Catalog trang 13-15, giấy phép lưu hành số 2421'),
(372, 73, 101, 'B.Braun FB04', 'MDL-8D261B', 'B.Braun', '2026', 'Đức', NULL, 3572000.00, 10716000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 30 tháng, đào tạo vận hành tại chỗ.', NULL, NULL, NULL, 'Đức', NULL, 'Catalog trang 16-18, giấy phép lưu hành số 2422'),
(373, 73, 102, 'B.Braun 6B9A', 'MDL-F84575', 'B.Braun', '2026', 'Đức', NULL, 4888000.00, 29328000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 30 tháng, đào tạo vận hành tại chỗ.', NULL, NULL, NULL, 'Đức', NULL, 'Catalog trang 19-21, giấy phép lưu hành số 2423'),
(374, 73, 103, 'B.Braun 546A', 'MDL-AFB8B1', 'B.Braun', '2026', 'Đức', NULL, 3854000.00, 23124000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 30 tháng, đào tạo vận hành tại chỗ.', NULL, NULL, NULL, 'Đức', NULL, 'Catalog trang 22-24, giấy phép lưu hành số 2424'),
(375, 73, 104, 'B.Braun 2343', 'MDL-38F680', 'B.Braun', '2026', 'Đức', NULL, 2256000.00, 6768000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 30 tháng, đào tạo vận hành tại chỗ.', NULL, NULL, NULL, 'Đức', NULL, 'Catalog trang 25-27, giấy phép lưu hành số 2425'),
(376, 74, 96, 'Fujifilm 3E05', 'MDL-C3E51B', 'Fujifilm', '2025', 'Nhật Bản', NULL, 2568000.00, 5136000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', 'Một số thông số đạt mức tối thiểu — đề nghị xem xét tương đương.', '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 36 tháng, đào tạo vận hành tại chỗ.', NULL, NULL, NULL, 'Nhật Bản', NULL, 'Catalog trang 1-3, giấy phép lưu hành số 2434'),
(377, 74, 98, 'Fujifilm 9E49', 'MDL-6EB663', 'Fujifilm', '2025', 'Nhật Bản', NULL, 5564000.00, 11128000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 36 tháng, đào tạo vận hành tại chỗ.', NULL, NULL, NULL, 'Nhật Bản', NULL, 'Catalog trang 7-9, giấy phép lưu hành số 2436'),
(378, 74, 99, 'Fujifilm B049', 'MDL-D380D4', 'Fujifilm', '2025', 'Nhật Bản', NULL, 4387000.00, 17548000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 36 tháng, đào tạo vận hành tại chỗ.', NULL, NULL, NULL, 'Nhật Bản', NULL, 'Catalog trang 10-12, giấy phép lưu hành số 2437'),
(379, 74, 100, 'Fujifilm 4493', 'MDL-CCAF67', 'Fujifilm', '2025', 'Nhật Bản', NULL, 2568000.00, 7704000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 36 tháng, đào tạo vận hành tại chỗ.', NULL, NULL, NULL, 'Nhật Bản', NULL, 'Catalog trang 13-15, giấy phép lưu hành số 2438'),
(380, 74, 101, 'Fujifilm FB04', 'MDL-77D597', 'Fujifilm', '2025', 'Nhật Bản', NULL, 4066000.00, 12198000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 36 tháng, đào tạo vận hành tại chỗ.', NULL, NULL, NULL, 'Nhật Bản', NULL, 'Catalog trang 16-18, giấy phép lưu hành số 2439'),
(381, 74, 102, 'Fujifilm 6B9A', 'MDL-1498D8', 'Fujifilm', '2025', 'Nhật Bản', NULL, 5564000.00, 33384000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 36 tháng, đào tạo vận hành tại chỗ.', NULL, NULL, NULL, 'Nhật Bản', NULL, 'Catalog trang 19-21, giấy phép lưu hành số 2440'),
(382, 74, 103, 'Fujifilm 546A', 'MDL-4F9FAC', 'Fujifilm', '2025', 'Nhật Bản', NULL, 4387000.00, 26322000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 36 tháng, đào tạo vận hành tại chỗ.', NULL, NULL, NULL, 'Nhật Bản', NULL, 'Catalog trang 22-24, giấy phép lưu hành số 2441'),
(383, 74, 104, 'Fujifilm 2343', 'MDL-CFB0E1', 'Fujifilm', '2025', 'Nhật Bản', NULL, 2568000.00, 7704000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 36 tháng, đào tạo vận hành tại chỗ.', NULL, NULL, NULL, 'Nhật Bản', NULL, 'Catalog trang 25-27, giấy phép lưu hành số 2442'),
(384, 75, 105, 'Olympus 65D1', 'MDL-48AA74', 'Olympus', '2025', 'Nhật Bản', NULL, 1850000000.00, 1850000000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 24 tháng, đào tạo vận hành tại chỗ.', NULL, 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.', NULL, 'Nhật Bản', NULL, 'Catalog trang 1-3, giấy phép lưu hành số 2400'),
(385, 75, 106, 'Olympus 8BD2', 'MDL-6C72FA', 'Olympus', '2025', 'Nhật Bản', NULL, 45000000.00, 45000000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 24 tháng, đào tạo vận hành tại chỗ.', NULL, 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.', NULL, 'Nhật Bản', NULL, 'Catalog trang 4-6, giấy phép lưu hành số 2401'),
(386, 75, 107, 'Olympus CA38', 'MDL-173C88', 'Olympus', '2025', 'Nhật Bản', NULL, 120000000.00, 120000000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 24 tháng, đào tạo vận hành tại chỗ.', NULL, 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.', NULL, 'Nhật Bản', NULL, 'Catalog trang 7-9, giấy phép lưu hành số 2402'),
(387, 75, 108, 'Olympus 42A5', 'MDL-9AE127', 'Olympus', '2025', 'Nhật Bản', NULL, 95000000.00, 95000000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 24 tháng, đào tạo vận hành tại chỗ.', NULL, 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.', NULL, 'Nhật Bản', NULL, 'Catalog trang 10-12, giấy phép lưu hành số 2403'),
(388, 75, 109, 'Olympus E4D0', 'MDL-8AED92', 'Olympus', '2025', 'Nhật Bản', NULL, 60000000.00, 60000000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 24 tháng, đào tạo vận hành tại chỗ.', NULL, 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.', NULL, 'Nhật Bản', NULL, 'Catalog trang 13-15, giấy phép lưu hành số 2404'),
(389, 75, 110, 'Olympus EC67', 'MDL-A4E2E3', 'Olympus', '2025', 'Nhật Bản', NULL, 25000000.00, 25000000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 24 tháng, đào tạo vận hành tại chỗ.', NULL, 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.', NULL, 'Nhật Bản', NULL, 'Catalog trang 16-18, giấy phép lưu hành số 2405'),
(390, 75, 111, 'Olympus 28D0', 'MDL-F74C61', 'Olympus', '2025', 'Nhật Bản', NULL, 1850000000.00, 1850000000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 24 tháng, đào tạo vận hành tại chỗ.', NULL, 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.', NULL, 'Nhật Bản', NULL, 'Catalog trang 19-21, giấy phép lưu hành số 2406'),
(391, 75, 112, 'Olympus 2D3B', 'MDL-667769', 'Olympus', '2025', 'Nhật Bản', NULL, 45000000.00, 45000000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 24 tháng, đào tạo vận hành tại chỗ.', NULL, 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.', NULL, 'Nhật Bản', NULL, 'Catalog trang 22-24, giấy phép lưu hành số 2407'),
(392, 75, 113, 'Olympus 1285', 'MDL-49DAC7', 'Olympus', '2025', 'Nhật Bản', NULL, 120000000.00, 120000000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 24 tháng, đào tạo vận hành tại chỗ.', NULL, 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.', NULL, 'Nhật Bản', NULL, 'Catalog trang 25-27, giấy phép lưu hành số 2408'),
(393, 75, 114, 'Olympus 162A', 'MDL-72AE02', 'Olympus', '2025', 'Nhật Bản', NULL, 95000000.00, 95000000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 24 tháng, đào tạo vận hành tại chỗ.', NULL, 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.', NULL, 'Nhật Bản', NULL, 'Catalog trang 28-30, giấy phép lưu hành số 2409'),
(394, 75, 115, 'Olympus 208B', 'MDL-4D1606', 'Olympus', '2025', 'Nhật Bản', NULL, 60000000.00, 60000000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 24 tháng, đào tạo vận hành tại chỗ.', NULL, 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.', NULL, 'Nhật Bản', NULL, 'Catalog trang 31-33, giấy phép lưu hành số 2410'),
(395, 76, 105, 'B.Braun 65D1', 'MDL-CC2A5F', 'B.Braun', '2026', 'Đức', NULL, 1739000000.00, 1739000000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 30 tháng, đào tạo vận hành tại chỗ.', NULL, 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.', NULL, 'Đức', NULL, 'Catalog trang 1-3, giấy phép lưu hành số 2417'),
(396, 76, 106, 'B.Braun 8BD2', 'MDL-3EE0EF', 'B.Braun', '2026', 'Đức', NULL, 42300000.00, 42300000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 30 tháng, đào tạo vận hành tại chỗ.', NULL, 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.', NULL, 'Đức', NULL, 'Catalog trang 4-6, giấy phép lưu hành số 2418'),
(397, 76, 107, 'B.Braun CA38', 'MDL-E5DA79', 'B.Braun', '2026', 'Đức', NULL, 112800000.00, 112800000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 30 tháng, đào tạo vận hành tại chỗ.', NULL, 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.', 'Đầu dò Linear dải tần 5-14 MHz (yêu cầu 5-15 MHz).', 'Đức', NULL, 'Catalog trang 7-9, giấy phép lưu hành số 2419'),
(398, 76, 108, 'B.Braun 42A5', 'MDL-545341', 'B.Braun', '2026', 'Đức', NULL, 89300000.00, 89300000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 30 tháng, đào tạo vận hành tại chỗ.', NULL, 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.', NULL, 'Đức', NULL, 'Catalog trang 10-12, giấy phép lưu hành số 2420'),
(399, 76, 109, 'B.Braun E4D0', 'MDL-D1E115', 'B.Braun', '2026', 'Đức', NULL, 56400000.00, 56400000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 30 tháng, đào tạo vận hành tại chỗ.', NULL, 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.', NULL, 'Đức', NULL, 'Catalog trang 13-15, giấy phép lưu hành số 2421'),
(400, 76, 110, 'B.Braun EC67', 'MDL-E72277', 'B.Braun', '2026', 'Đức', NULL, 23500000.00, 23500000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 30 tháng, đào tạo vận hành tại chỗ.', NULL, 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.', NULL, 'Đức', NULL, 'Catalog trang 16-18, giấy phép lưu hành số 2422'),
(401, 76, 111, 'B.Braun 28D0', 'MDL-BA6B20', 'B.Braun', '2026', 'Đức', NULL, 1739000000.00, 1739000000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 30 tháng, đào tạo vận hành tại chỗ.', NULL, 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.', NULL, 'Đức', NULL, 'Catalog trang 19-21, giấy phép lưu hành số 2423'),
(402, 76, 112, 'B.Braun 2D3B', 'MDL-EA46B0', 'B.Braun', '2026', 'Đức', NULL, 42300000.00, 42300000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 30 tháng, đào tạo vận hành tại chỗ.', NULL, 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.', NULL, 'Đức', NULL, 'Catalog trang 22-24, giấy phép lưu hành số 2424'),
(403, 76, 113, 'B.Braun 1285', 'MDL-44D072', 'B.Braun', '2026', 'Đức', NULL, 112800000.00, 112800000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 30 tháng, đào tạo vận hành tại chỗ.', NULL, 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.', NULL, 'Đức', NULL, 'Catalog trang 25-27, giấy phép lưu hành số 2425'),
(404, 76, 114, 'B.Braun 162A', 'MDL-C1EA5D', 'B.Braun', '2026', 'Đức', NULL, 89300000.00, 89300000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 30 tháng, đào tạo vận hành tại chỗ.', NULL, 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.', NULL, 'Đức', NULL, 'Catalog trang 28-30, giấy phép lưu hành số 2426'),
(405, 76, 115, 'B.Braun 208B', 'MDL-E6CA7C', 'B.Braun', '2026', 'Đức', NULL, 56400000.00, 56400000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 30 tháng, đào tạo vận hành tại chỗ.', NULL, 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.', NULL, 'Đức', NULL, 'Catalog trang 31-33, giấy phép lưu hành số 2427'),
(406, 77, 105, 'Fujifilm 65D1', 'MDL-B73E35', 'Fujifilm', '2025', 'Nhật Bản', NULL, 1979500000.00, 1979500000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', 'Một số thông số đạt mức tối thiểu — đề nghị xem xét tương đương.', '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 36 tháng, đào tạo vận hành tại chỗ.', NULL, 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.', NULL, 'Nhật Bản', NULL, 'Catalog trang 1-3, giấy phép lưu hành số 2434'),
(407, 77, 107, 'Fujifilm CA38', 'MDL-719D08', 'Fujifilm', '2025', 'Nhật Bản', NULL, 128400000.00, 128400000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 36 tháng, đào tạo vận hành tại chỗ.', NULL, 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.', NULL, 'Nhật Bản', NULL, 'Catalog trang 7-9, giấy phép lưu hành số 2436'),
(408, 77, 108, 'Fujifilm 42A5', 'MDL-E5270F', 'Fujifilm', '2025', 'Nhật Bản', NULL, 101650000.00, 101650000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 36 tháng, đào tạo vận hành tại chỗ.', NULL, 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.', NULL, 'Nhật Bản', NULL, 'Catalog trang 10-12, giấy phép lưu hành số 2437'),
(409, 77, 109, 'Fujifilm E4D0', 'MDL-BEC47B', 'Fujifilm', '2025', 'Nhật Bản', NULL, 64200000.00, 64200000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 36 tháng, đào tạo vận hành tại chỗ.', NULL, 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.', NULL, 'Nhật Bản', NULL, 'Catalog trang 13-15, giấy phép lưu hành số 2438'),
(410, 77, 110, 'Fujifilm EC67', 'MDL-4D6F6E', 'Fujifilm', '2025', 'Nhật Bản', NULL, 26750000.00, 26750000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 36 tháng, đào tạo vận hành tại chỗ.', NULL, 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.', NULL, 'Nhật Bản', NULL, 'Catalog trang 16-18, giấy phép lưu hành số 2439'),
(411, 77, 111, 'Fujifilm 28D0', 'MDL-AF5C26', 'Fujifilm', '2025', 'Nhật Bản', NULL, 1979500000.00, 1979500000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 36 tháng, đào tạo vận hành tại chỗ.', NULL, 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.', NULL, 'Nhật Bản', NULL, 'Catalog trang 19-21, giấy phép lưu hành số 2440'),
(412, 77, 112, 'Fujifilm 2D3B', 'MDL-206710', 'Fujifilm', '2025', 'Nhật Bản', NULL, 48150000.00, 48150000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 36 tháng, đào tạo vận hành tại chỗ.', NULL, 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.', NULL, 'Nhật Bản', NULL, 'Catalog trang 22-24, giấy phép lưu hành số 2441'),
(413, 77, 113, 'Fujifilm 1285', 'MDL-B93E40', 'Fujifilm', '2025', 'Nhật Bản', NULL, 128400000.00, 128400000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 36 tháng, đào tạo vận hành tại chỗ.', NULL, 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.', NULL, 'Nhật Bản', NULL, 'Catalog trang 25-27, giấy phép lưu hành số 2442'),
(414, 77, 114, 'Fujifilm 162A', 'MDL-67C709', 'Fujifilm', '2025', 'Nhật Bản', NULL, 101650000.00, 101650000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 36 tháng, đào tạo vận hành tại chỗ.', NULL, 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.', NULL, 'Nhật Bản', NULL, 'Catalog trang 28-30, giấy phép lưu hành số 2443'),
(415, 77, 115, 'Fujifilm 208B', 'MDL-FF680D', 'Fujifilm', '2025', 'Nhật Bản', NULL, 64200000.00, 64200000.00, 0.00, NULL, 'Đáp ứng đầy đủ yêu cầu kỹ thuật của hồ sơ mời chào giá.', NULL, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 0, 'Đáp ứng: hàng mới 100%, đồng bộ cùng hãng, có giấy phép lưu hành.', NULL, 'Bảo hành 36 tháng, đào tạo vận hành tại chỗ.', NULL, 'Cấu hình chào đúng/cao hơn yêu cầu, kèm đầy đủ phụ kiện.', NULL, 'Nhật Bản', NULL, 'Catalog trang 31-33, giấy phép lưu hành số 2444'),
(439, 85, 168, 'Màn hình', 'ABC', 'Quảng Đông', '2025', 'Trung Quốc', NULL, 125000.00, 250000.00, 0.00, NULL, 'Màn hình\nCấu hình:\n- Màn hình: 01 cái\n- Cáp nguồn: 01 cái\n- Adapter: 01 cái\n- Cáp tín hiệu DVI: 01 cái\n- Cáp tín hiệu DP: 01 cái\n- Cáp tín hiệu BNC: 01 cái\n- Chân màn hình: 01 cái\nThông số kỹ thuật:\n- Kích thước màn hình:  ≥ 24 inch\n- Tỷ lệ hiển thị / tỷ lệ khung hình: 16:10\n- Màu sắc:  ≥ 1 triệu màu\n- Độ sáng:  ≥ 200 cd/m2\n- Tỷ lệ tương phản:  ≥ 800:1\n- Góc nhìn: ≥ 170°/170°\n- Trọng lượng:  ≤ 9kg (Không bao gồm chân màn hình)\n- Cổng tính hiệu đầu vào:\n+ DVI connector (x1),\n+ SDI connector(x1),\n+ S-Video connector(x2),\n+ RGB/ YPbPr connector(x3).\nỨng dụng: Hệ thống nội soi kỹ thuật số.\n- Loại đèn nền: LED\n- Nguồn điện sử dụng: 24V', '- Cáp tín hiệu HDMI: 01 cái', '2026-09-11 09:53:16', '2026-09-11 09:55:52', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Catalog trang 11-25'),
(440, 85, 169, 'Bộ xử lý hình ảnh camera Full HD', 'ABC', 'Quảng Đông', '2025', 'Trung Quốc', NULL, 125000.00, 250000.00, 0.00, NULL, 'Cấu hình\n- Bộ xử lý hình ảnh: 01 cái\n- Đầu camera: 01 cái\n- Cáp nguồn: 01 cái\n- Cáp tín hiệu DVI-D: 01 cái\n- Cáp tín hiệu HDMI: 01 cái\n- Cầu chì dự phòng: 02 cái\nTính năng\n- Áp dụng cho các chuyên khoa: Phẫu thuật tiết niệu, phẫu thuật tổng quát\n- Tái tạo hình ảnh HD\n- Hỗ trợ chụp và lưu trữ hình ảnh.\nThông số kỹ thuật\n- Màn hình cảm ứng\n- Cảm biến hình ảnh: Cảm biến CMOS \n- Cổng kết nối USB:  ≥ 2.0 x 2\n- Nguồn điện: AC100- 240V 50Hz \n- Tín hiệu đầu ra: HDMI, 3G-SDI\n- Kích thước:  ≥ 360 x 300 x 85 mm\n- Công suất đầu vào:  ≥ 45VA\n- Đầu camera có 2 nút chức năng, dùng để dừng hình, ghi hình và cân bằng trắng.\n- Chiều dài dây cáp đầu camera:  ≥ 2.8m', NULL, '2026-09-11 09:53:16', '2026-09-11 09:55:52', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Catalog trang 26-30'),
(441, 85, 170, 'Nguồn sáng lạnh', 'ABC', 'Quảng Đông', '2025', 'Trung Quốc', NULL, 125000.00, 250000.00, 0.00, NULL, 'Cấu hình\n- Nguồn sáng lạnh LED: 01 cái\n- Cáp nguồn: 01 cái\n- Dây dẫn sáng: 01 cái\n- Cầu chì dự phòng: 02 cái\nTính năng\n- Màn hình: ≥ 5 inch\n- Chỉ số hoàn màu cao\nThông số kỹ thuật\n- Độ sáng: ≥ 700lm\n- Nhiệt độ màu: ≥ 5700K\n- Tuổi thọ của bóng LED: ≥ 20000 giờ\n33\n- Công suất tiêu thụ: ≥100VA\n- Kích thước sản phẩm: ≥ 360 x 300 x 85 mm', NULL, '2026-09-11 09:53:16', '2026-09-11 09:55:52', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Catalog trang 31-45'),
(442, 85, 171, 'Hệ thống xe đẩy nội soi', 'ABC', 'Quảng Đông', '2025', 'Trung Quốc', NULL, 125000.00, 250000.00, 0.00, NULL, 'Xe đẩy chuyên dụng đi kèm hệ thống nội soi, có cánh tay màn hình, , có ổ cắm điện.', 'Bánh xe  khôngcó khóa', '2026-09-11 09:53:16', '2026-09-11 09:55:52', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Catalog trang 46'),
(447, 87, 172, 'Khớp nối cố định thanh đỡ hệ thống vén não với ray bên bàn mổ', 'A001', 'Grang', '2025', 'Mỹ', NULL, 10000.00, 20000.00, 0.00, NULL, 'Chủng loại: Khớp nối cố định thanh đỡ hệ thống vén não với ray bên bàn mổ\nChất liệu: Thép không gỉ\nHình dáng: Dạng khớp cầu, xoay điều chỉnh góc được trước khi khóa cố định\nKích thước: Tương thích thanh ray bàn mổ rộng tối thiểu 10×25mm\nĐặc điểm khác: Dùng kèm thanh/trục giữ dụng cụ tương ứng trong hệ thống;', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(448, 87, 173, 'Thanh đỡ hệ thống vén não', 'A002', 'Grang', '2025', 'Mỹ', NULL, 12000.00, 24000.00, 0.00, NULL, 'Chủng loại: Thanh đỡ hệ thống vén não\nChất liệu: Thép không gỉ\nHình dáng: dạng thanh/trục thẳng\nĐặc điểm khác: Tương thích với khớp nối cố định và đầu nối tay đỡ mềm của hệ thống vén não', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(449, 87, 174, 'Đầu nối tay đỡ mềm hệ thống vén não', 'A003', 'Grang', '2025', 'Mỹ', NULL, 14000.00, 56000.00, 0.00, NULL, 'Chủng loại: Đầu nối tay đỡ mềm hệ thống vén não\nChất liệu: Thép không gỉ\nHình dáng: Đầu nối tự giữ vị trí khi điều chỉnh góc\nĐặc điểm khác: Kết nối đồng thời tối thiểu từ 1–5 tay giữ dụng cụ hệ thống vén não.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(450, 87, 175, 'Tay đỡ dụng cụ hệ thống vén não', 'A004', 'Grang', '2025', 'Mỹ', NULL, 16000.00, 64000.00, 0.00, NULL, 'Chủng loại: Tay đỡ dụng cụ hệ thống vén não\nChất liệu: Thép\nHình dáng: Dạng tay đòn linh hoạt, có khả năng uốn chỉnh\nĐặc điểm khác: Tương thích với đầu nối tay đỡ mềm hệ thống vén não', 'Chất liệu: Thép không gỉ', '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(451, 87, 176, 'Giá đỡ dụng cụ vén não', 'A005', 'Grang', '2025', 'Mỹ', NULL, 18000.00, 72000.00, 0.00, NULL, 'Chủng loại: Giá đỡ dụng cụ vén não\nChất liệu: Thép không gỉ\nHình dáng: Dạng khung kẹp, khớp với trục tròn của vén não\nKích thước: Giữ được vén não có trục tròn đường kính tối đa 5.5mm\nĐặc điểm khác: Lắp vào tay đỡ dụng cụ hệ thống vén não.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(452, 87, 177, 'Vén não Heifetz uốn định hình, trục tròn, loại nhỏ', 'A006', 'Grang', '2025', 'Mỹ', NULL, 20000.00, 80000.00, 0.00, NULL, 'Chủng loại: Vén não Heifetz\nChất liệu: Thép không gỉ\nHình dáng: đầu lưới dạng dẹt, thân trục tròn\nKích thước: Chiều dài tổng thể từ 150mm đến 160mm, độ rộng lưỡi từ 7.7mm đến 8.3mm\nĐặc điểm khác: có thể uốn định hình;', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(453, 87, 178, 'Vén não Heifetz uốn định hình, trục tròn, loại lớn', 'A007', 'Grang', '2025', 'Mỹ', NULL, 22000.00, 88000.00, 0.00, NULL, 'Chủng loại: Vén não Heifetz\nChất liệu: Thép không gỉ\nHình dáng: đầu lưỡi dạng dẹt, thân trục tròn\nKích thước: Chiều dài tổng thể từ 195mm đến 205mm; chiều dài hoạt động từ 95mm đến 100mm; đường kính trục từ 4.8mm đến 5.5mm; độ rộng lưỡi từ 13.5mm đến 14.5mm\nĐặc điểm khác: có thể uốn định hình;', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(454, 87, 179, 'Vén não uốn định hình hai đầu, loại nhỏ', 'A008', 'Grang', '2025', 'Mỹ', NULL, 24000.00, 48000.00, 0.00, NULL, 'Vén não uốn định hình hai đầu\nChất liệu: Thép không gỉ\nHình dáng: Thiết kế bản dẹt thuôn/nón, hai đầu thao tác với kích thước khác nhau.\nKích thước: Chiều dài tổng thế từ 195mm đến 205mm; bề rộng hai đầu vén dao động trong khoảng 7.5mm -8.5mm (đầu lớn) và 3.5mm đến 4.5mm (đầu nhỏ)\nĐặc điểm khác: Có thể uốn định hình; bề mặt được xử lý bằng lớp phủ tối màu/mờ giúp tăng độ cứng và chống chói lóa.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(455, 87, 180, 'Vén não uốn định hình hai đầu, loại vừa', 'A009', 'Grang', '2025', 'Mỹ', NULL, 26000.00, 52000.00, 0.00, NULL, 'Chủng loại: Vén não uốn định hình hai đầu\nChất liệu: Thép không gỉ\nHình dáng: Thiết kế bản dẹt thuôn/nón, hai đầu thao tác với kích thước khác nhau.\nKích thước: Chiều dài tổng thể từ 195mm đến 205mm; bề rộng hai đầu vén từ 12.5mm đến 13.5mm (đầu lớn) và từ 5.5mm đến 6.5mm (đầu nhỏ).\nĐặc điểm khác: Có thể uốn định hình; bề mặt được xử lý bằng lớp phủ tối màu/mờ giúp tăng độ cứng và chống chói lóa.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(456, 87, 181, 'Vén não uốn định hình hai đầu, loại lớn', 'A010', 'Grang', '2025', 'Mỹ', NULL, 28000.00, 56000.00, 0.00, NULL, 'Chủng loại: Vén não uốn định hình hai đầu\nChất liệu: Thép không gỉ\nHình dáng: Thiết kế bản dẹt thuôn/nón, hai đầu thao tác với kích thước khác nhau.\nKích thước: Chiều dài tổng thể từ 195mm đến 205mm; bề rộng hai đầu vén từ 16.5mm đến 17.5mm (đầu lớn) và từ 6.5mm đến 9.5mm (đầu nhỏ).\nĐặc điểm khác: Có thể uốn định hình; bề mặt được xử lý bằng lớp phủ tối màu/mờ giúp tăng độ cứng và chống chói lóa.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(457, 87, 182, 'Vén não uốn định hình hai đầu, bản dẹt', 'A011', 'Grang', '2025', 'Mỹ', NULL, 30000.00, 120000.00, 0.00, NULL, 'Chủng loại: Vén não uốn định hình hai đầu\nChất liệu: Thép không gỉ\nHình dáng: Thiết kế bản dẹt\nKích thước: Chiều dài tổng thể từ 195mm đến 205mm; bề rộng lưỡi từ 7.5mm đến 9.5mm.\nĐặc điểm khác: Có thể uốn định hình', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(458, 87, 183, 'Dây cưa xương phẫu thuật kiểu Gigli', 'A012', 'Grang', '2025', 'Mỹ', NULL, 32000.00, 128000.00, 0.00, NULL, 'Chủng loại: Dây cưa xương phẫu thuật kiểu Gigli\nChất liệu: Thép không gỉ\nHình dạng: Dạng dây cáp xoắn, hai đầu có khuyên tròn\nKích thước: Chiều dài tổng thể từ 390 mm đến 410mm\nĐặc điểm khác: Dòng sợi mảnh', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(459, 87, 184, 'Cán kéo dây cưa Gigli có móc giữ', 'A013', 'Grang', '2025', 'Mỹ', NULL, 34000.00, 136000.00, 0.00, NULL, 'Chủng loại: Cán/tay cầm dùng cho dây cưa Gigli\nChất liệu: Thép không gỉ\nHình dáng: Thiết kế cán cầm ngang dạng chữ T, có móc giữ.\nKích thước: Chiều dài tổng thể từ 65mm đến 75mm.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(460, 87, 185, 'Kéo vi phẫu Yasargil thẳng, thân dạng lưỡi lê, loại ngắn', 'A014', 'Grang', '2025', 'Mỹ', NULL, 36000.00, 72000.00, 0.00, NULL, 'Chủng loại: Kéo vi phẫu Yasargil\nChất liệu: Thép không gỉ\nHình dáng: Thân kéo gập góc dạng lưỡi lê, lưỡi cắt thẳng\nKích thước: Chiều dài tổng thể từ 195mm đến 205mm;', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(461, 87, 186, 'Kéo vi phẫu Yasargil thẳng, thân dạng lưỡi lê, loại dài', 'A015', 'Grang', '2025', 'Mỹ', NULL, 38000.00, 76000.00, 0.00, NULL, 'Chủng loại: Kéo vi phẫu Yasargil\nChất liệu: Thép không gỉ\nHình dáng: Thân kéo gập góc dạng lưỡi lê, lưỡi cắt thẳng\nKích thước: Chiều dài tổng thể từ 220mm đến 230mm;', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(462, 87, 187, 'Kẹp gắp bông gạc Forster-Ballenger thẳng, ngàm có khía', 'A016', 'Grang', '2025', 'Mỹ', NULL, 40000.00, 80000.00, 0.00, NULL, 'Kẹp gắp bông gạc kiểu Forster-Ballenger\nChất liệu: Thép không gỉ\nHình dáng: Dạng kìm thẳng, có khía\nKích thước: Chiều dài tổng thể từ 240 đến 255 mm', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(463, 87, 188, 'Kẹp khăn mổ Backhaus', 'A017', 'Grang', '2025', 'Mỹ', NULL, 42000.00, 672000.00, 0.00, NULL, 'Chủng loại: Kẹp khăn mổ kiểu Backhaus\nChất liệu: Thép không gỉ\nHình dáng: Dạng kìm cong, đầu nhọn\nKích thước: Chiều dài tổng thể từ 105mm đến 115mm.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(464, 87, 189, 'Cán dao phẫu thuật số 3', 'A018', 'Grang', '2025', 'Mỹ', NULL, 44000.00, 88000.00, 0.00, NULL, 'Chủng loại: Cán dao phẫu thuật số 3\nChất liệu: Thép không gỉ\nHình dáng: Dạng thân dẹt, ngàm gắn lưỡi dao chuẩn số 3\nKích thước: Chiều dài tổng thể từ 115 mm đến 130mm\nĐặc điểm khác: Loại tiêu chuẩn', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(465, 87, 190, 'Cán dao phẫu thuật số 4', 'A019', 'Grang', '2025', 'Mỹ', NULL, 46000.00, 92000.00, 0.00, NULL, 'Chủng loại: Cán dao phẫu thuật số 4\nChất liệu: Thép không gỉ\nHình dáng: Dạng thân dẹt, ngàm gắn lưỡi dao chuẩn số 4\nKích thước: Chiều dài tổng thể từ 130 mm đến 140mm\nĐặc điểm khác: Loại tiêu chuẩn', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(466, 87, 191, 'Cán dao phẫu thuật số 3L,', 'A020', 'Grang', '2025', 'Mỹ', NULL, 48000.00, 96000.00, 0.00, NULL, 'Chủng loại: Cán dao phẫu thuật số 3L\nChất liệu: Thép không gỉ\nHình dáng: Dạng thân thẳng, ngàm gắn lưỡi dao chuẩn số 3\nKích thước: Chiều dài tổng thể từ 205mm đến 215mm\nĐặc điểm khác: Gấp góc hình lưỡi lê', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(467, 87, 192, 'Kéo phẫu tích Metzenbaum, loại ngắn', 'A021', 'Grang', '2025', 'Mỹ', NULL, 50000.00, 100000.00, 0.00, NULL, 'Chủng loại: Kéo phẫu tích kiểu Metzenbaum\nChất liệu: Thép không gỉ,\nHình dáng: Dạng cong, hai chuôi kéo được mạ vàng\nKích thước: Chiều dài tổng thể từ 175mm đến 185mm.\nĐặc điểm khác: Đầu mũi kéo thiết kế kiểu tù/tù', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(468, 87, 193, 'Kéo phẫu tích Metzenbaum, loại dài', 'A022', 'Grang', '2025', 'Mỹ', NULL, 52000.00, 104000.00, 0.00, NULL, 'Chủng loại: Kéo phẫu tích kiểu Metzenbaum\nChất liệu: Thép không gỉ,\nHình dáng: Dạng cong, hai chuôi kéo được mạ vàng\nKích thước: Chiều dài tổng thể từ 195mm đến 205mm.\nĐặc điểm khác: Đầu mũi kéo thiết kế kiểu tù/tù', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(469, 87, 194, 'Kéo phẫu tích Tonnis-Adson', 'A023', 'Grang', '2025', 'Mỹ', NULL, 54000.00, 108000.00, 0.00, NULL, 'Chủng loại: Kéo phẫu tích kiểu Tonnis-Adson\nChất liệu: Thép không gỉ,\nHình dáng: Dạng cong, thiết kế lưỡi kéo dạng thanh mảnh, hai chuôi kéo được mạ vàng\nKích thước: Chiều dài tổng thể từ 170mm đến 180mm.\nĐặc điểm khác: Đầu mũi kéo thiết kế kiểu tù/tù', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(470, 87, 195, 'Kéo phẫu thuật Schmieden-Taylor dạng gập góc', 'A024', 'Grang', '2025', 'Mỹ', NULL, 56000.00, 112000.00, 0.00, NULL, 'Chủng loại: Kéo phẫu thuật kiểu Schmieden-Taylor.\nChất liệu: Thép không gỉ,\nHình dáng: Thân kéo uốn cong gập góc, một lưỡi cắt có thiết kế đầu dò dạng bi tròn\nKích thước: Chiều dài tổng thể từ 150mm đến 175mm.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `bg_bao_gia_chi_tiet` (`id`, `bao_gia_id`, `hang_hoa_id`, `ten_thuong_mai`, `model`, `hang_san_xuat`, `nam_san_xuat`, `xuat_xu`, `quy_cach`, `don_gia`, `thanh_tien`, `don_gia_trung_thau`, `tai_lieu_tham_chieu`, `thong_so_chao_gia`, `diem_khong_dat`, `ngay_tao`, `ngay_cap_nhat`, `da_xoa`, `dap_ung_chung`, `khong_dat_chung`, `dap_ung_khac`, `khong_dat_khac`, `dap_ung_cau_hinh`, `khong_dat_cau_hinh`, `dap_ung_nhom_nuoc`, `khong_dat_nhom_nuoc`, `tai_lieu_chung_minh`) VALUES
(471, 87, 196, 'Kéo phẫu thuật thẳng, đầu tù/nhọn', 'A025', 'Grang', '2025', 'Mỹ', NULL, 58000.00, 116000.00, 0.00, NULL, 'Chủng loại: Kéo phẫu thuật thẳng, đầu tù/nhọn\nChất liệu: Thép không gỉ\nHình dáng: lưỡi cắt thẳng; đầu mũi kéo thiết kế một đầu nhọn, một đầu tù, thiết kế tiêu chuẩn\nKích thước: Chiều dài tổng thể từ 135mm đến 150mm.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(472, 87, 197, 'Kéo cắt mô Mayo cong', 'A026', 'Grang', '2025', 'Mỹ', NULL, 60000.00, 120000.00, 0.00, NULL, 'Chủng loại: Kéo cắt mô Mayo\nChất liệu: Thép không gỉ,\nHình dáng: lưỡi cắt uốn cong; đầu mũi kéo thiết kế kiểu hai đầu tù\nKích thước: Chiều dài tổng thể từ 165mm đến 175mm.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(473, 87, 198, 'Kẹp phẫu tích thẳng, không mấu', 'A027', 'Grang', '2025', 'Mỹ', NULL, 62000.00, 248000.00, 0.00, NULL, 'Chủng loại: Kẹp phẫu tích không mấu\nChất liệu: Thép không gỉ,\nHình dáng: Dạng kẹp thẳng, bản tiêu chuẩn, thân kẹp có khía nhám chống trượt, ngàm có khía\nKích thước: Chiều dài tổng thể từ 195mm đến 205mm.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(474, 87, 199, 'Kẹp mô Waugh thẳng, ngàm răng (1x2)', 'A028', 'Grang', '2025', 'Mỹ', NULL, 64000.00, 128000.00, 0.00, NULL, 'Chủng loại: Kẹp mô, thiết kế kiểu Waugh\nChất liệu: Thép không gỉ,\nHình dáng: Dạng kẹp thẳng, thiết kế thanh mảnh\nKích thước: Chiều dài tổng thể từ 175mm đến 185mm.\nĐặc điểm khác: Đầu kẹp có mấu kiểu 1x2 răng', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(475, 87, 200, 'Kẹp mô Gillies thẳng, ngàm răng (1x2)', 'A029', 'Grang', '2025', 'Mỹ', NULL, 66000.00, 132000.00, 0.00, NULL, 'Chủng loại: Kẹp mô, kiểu GILLIES\nChất liệu: Thép không gỉ\nHình dáng: Dạng kẹp thẳng, thiết kế thanh mảnh, có chốt/chân định vị mặt trong\nKích thước: Chiều dài tổng thể từ 145mm đến 160mm.\nĐặc điểm khác: Đầu kẹp có mấu kiểu 1x2 răng', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(476, 87, 201, 'Kẹp phẫu tích thẳng, không mấu, đầu phủ hợp kim cứng (TC)', 'A030', 'Grang', '2025', 'Mỹ', NULL, 68000.00, 136000.00, 0.00, NULL, 'Chủng loại: Kẹp phẫu tích không mấu\nChất liệu: Thép không gỉ, phần đầu/ngàm kẹp được hàn/tích hợp hợp kim cứng Tungsten Carbide (TC)\nHình dáng: Dạng kẹp thẳng, thanh mảnh; phần chuôi kẹp mạ vàng; thân kẹp có khía.\nKích thước: Chiều dài tổng thể từ 175mm đến 185mm.\nĐặc điểm khác: Mặt trong của đầu ngàm kẹp tạo nhám dạng chéo caro/ô vuông;', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(477, 87, 202, 'Kẹp phẫu tích không mấu, thân dạng lưỡi lê', 'A031', 'Grang', '2025', 'Mỹ', NULL, 70000.00, 140000.00, 0.00, NULL, 'Chủng loại: Kẹp phẫu tích không mấu, kiểu Gruenwald (hoặc Grünwald-Jansen).\nChất liệu: Thép không gỉ\nHình dáng: Thân kẹp gập góc dạng lưỡi lê; đầu kẹp thẳng.\nKích thước: Chiều dài tổng thể từ 195mm đến 205mm.\nĐặc điểm khác: Ngàm có các rãnh/khía ngang', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(478, 87, 203, 'Kẹp mô Gerald, không mấu,', 'A032', 'Grang', '2025', 'Mỹ', NULL, 72000.00, 144000.00, 0.00, NULL, 'Chủng loại: Kẹp phẫu tích không mấu, kiểu GERALD.\nChất liệu: Thép không gỉ\nHình dáng: Dạng kẹp thẳng, thiết kế thanh mảnh; phần cán có khía.\nKích thước: Chiều dài tổng thể từ 170mm đến 185mm.\nĐặc điểm khác: Mặt trong của mũi kẹp có các rãnh/khía ngang', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(479, 87, 204, 'Kẹp cầm máu Halsted-Mosquito cong', 'A033', 'Grang', '2025', 'Mỹ', NULL, 74000.00, 1184000.00, 0.00, NULL, 'Chủng loại: Kẹp cầm máu, kiểu HALSTED-MOSQUITO.\nChất liệu: Thép không gỉ.\nHình dáng: Dạng kìm cong, thiết kế thanh mảnh ; có khóa hãm ở cán cầm.\nKích thước: Chiều dài tổng thể từ 120mm đến 130mm.\nĐặc điểm khác: Ngàm kẹp không có mấu, mặt trong ngàm có các rãnh/khía;', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(480, 87, 205, 'Kẹp cầm máu da đầu Dandy cong sang bên, đầu tù', 'A034', 'Grang', '2025', 'Mỹ', NULL, 76000.00, 1520000.00, 0.00, NULL, 'Chủng loại: Kẹp cầm máu, kiểu DANDY.\nChất liệu: Thép không gỉ.\nHình dáng: Dạng kìm, ngàm kẹp uốn cong gập sang bên;\nKích thước: Chiều dài tổng thể từ 135mm đến 145mm.\nĐặc điểm khác: Ngàm kẹp có các rãnh/khía ngang; loại tái sử dụng nhiều lần.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(481, 87, 206, 'Kẹp cầm máu Ochsner-Kocher cong, ngàm răng 1x2', 'A035', 'Grang', '2025', 'Mỹ', NULL, 78000.00, 312000.00, 0.00, NULL, 'Chủng loại: Kẹp cầm máu, kiểu KOCHER-OCHSNER.\nChất liệu: Thép không gỉ\nHình dáng: Dạng kìm cong;\nKích thước: Chiều dài tổng thể từ 195mm đến 205mm.\nĐặc điểm khác: Đầu mũi kẹp có mấu nhọn kiểu răng (1x2); mặt trong ngàm kẹp có khía', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(482, 87, 207, 'Kẹp vi phẫu/khối u Yasargil, ngàm nhỏ', 'A036', 'Grang', '2025', 'Mỹ', NULL, 80000.00, 160000.00, 0.00, NULL, 'Chủng loại: Kẹp vi phẫu/Kẹp khối u, kiểu YASARGIL.\nChất liệu: Thép không gỉ\nHình dáng: Thân kẹp gập góc dạng lưỡi lê, mũi kẹp thẳng;\nKích thước: Chiều dài tổng thể từ 215mm đến 225mm. Đường kính ngoài của ngàm kẹp từ 2.5mm đến 3.5mm.\nĐặc điểm khác: Đầu kẹp thiết kế dạng khuyên tròn/hình thìa', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(483, 87, 208, 'Kẹp vi phẫu/khối u Yasargil ngàm lớn', 'A037', 'Grang', '2025', 'Mỹ', NULL, 82000.00, 164000.00, 0.00, NULL, 'Chủng loại: Kẹp vi phẫu/Kẹp khối u, kiểu YASARGIL.\nChất liệu: Thép không gỉ\nHình dáng: Thân kẹp gập góc dạng lưỡi lê, mũi kẹp thẳng;\nKích thước: Chiều dài tổng thể từ 215mm đến 225mm. Đường kính ngoài của ngàm kẹp từ 4.5mm đến 5.5mm.\nĐặc điểm khác: Đầu kẹp thiết kế dạng khuyên tròn/hình thìa.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(484, 87, 209, 'Dụng cụ tháo/lắp kẹp da đầu Raney', 'A038', 'Grang', '2025', 'Mỹ', NULL, 84000.00, 336000.00, 0.00, NULL, 'Chủng loại: Kìm lắp và tháo kẹp da đầu, kiểu RANEY.\nChất liệu: Thép không gỉ\nHình dáng: Dạng kìm thẳng; có cơ cấu khóa hãm.\nKích thước: Chiều dài tổng thể từ 155mm đến 165mm.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(485, 87, 210, 'Kẹp cầm máu da đầu Raney', 'A039', 'Grang', '2025', 'Mỹ', NULL, 86000.00, 3440000.00, 0.00, NULL, 'Chủng loại: Kẹp cầm máu da đầu, kiểu RANEY.\nChất liệu: Nhựa y tế hoặc thép không gỉ\nHình dáng: Dạng kẹp chữ U uốn lượn, tương thích với dụng cụ đặt/tháo kẹp cầm máu da đầu kiểu Raney.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(486, 87, 211, 'Que thăm dò Jacobson gập góc, đầu tròn', 'A040', 'Grang', '2025', 'Mỹ', NULL, 88000.00, 176000.00, 0.00, NULL, 'Chủng loại: Que thăm dò vi phẫu kiểu JACOBSON.\nChất liệu: Thép không gỉ\nHình dáng: Dạng que thẳng, phần đầu bẻ gập góc; cán cầm dạng trụ tròn có khía nhám.\nKích thước: Chiều dài tổng thể từ 180mm đến 190mm.\nĐặc điểm khác: Đầu dạng hạt tròn.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(487, 87, 212, 'Móc vén vi phẫu Fisch gập góc, đầu nhọn', 'A041', 'Grang', '2025', 'Mỹ', NULL, 90000.00, 360000.00, 0.00, NULL, 'Chủng loại: Móc vi phẫu / Móc vén màng cứng, kiểu FISCH.\nChất liệu: Thép không gỉ\nHình dáng: Cán cầm trụ tròn có khía nhám; phần đầu gập góc 90°.\nKích thước: Chiều dài tổng thể từ 180mm đến 190mm.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(488, 87, 213, 'Dụng cụ phẫu tích Davis, hai đầu', 'A042', 'Grang', '2025', 'Mỹ', NULL, 92000.00, 184000.00, 0.00, NULL, 'Chủng loại: Dụng cụ phẫu tích kiểu DAVIS.\nChất liệu: Thép không gỉ.\nHình dáng: Thiết kế 2 đầu làm việc; phần đầu uốn cong nhẹ; cán cầm dạng trụ tròn có khía nhám.\nKích thước: Chiều dài tổng thể từ 240mm đến 250mm.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(489, 87, 214, 'Dụng cụ bóc tách Freer, hai đầu', 'A043', 'Grang', '2025', 'Mỹ', NULL, 94000.00, 188000.00, 0.00, NULL, 'Chủng loại: Cây bóc tách màng cứng, kiểu FREER.\nChất liệu: Thép không gỉ.\nHình dáng: Thiết kế 2 đầu làm việc, dạng đầu cong; một đầu nhọn và một đầu tù; cán cầm có khía nhám.\nKích thước: Chiều dài tổng thể từ 175mm đến 190mm. Bề rộng đầu từ 3.5mm đến 5.5mm.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(490, 87, 215, 'Thìa nạo xương Williger', 'A044', 'Grang', '2025', 'Mỹ', NULL, 96000.00, 192000.00, 0.00, NULL, 'Chủng loại: Thìa nạo xương, kiểu WILLIGER.\nChất liệu: Thép không gỉ\nHình dáng: Dạng thẳng; phần đầu làm việc hình thìa múc.\nKích thước: Chiều dài tổng thể từ 170mm đến 180mm. Bề rộng đầu từ 4.8mm đến 5.6mm.\nĐặc điểm khác: Loại tái sử dụng nhiều lần, chưa tiệt trùng.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(491, 87, 216, 'Dụng cụ bóc tách màng xương Adson, đầu lưỡi vuông', 'A045', 'Grang', '2025', 'Mỹ', NULL, 98000.00, 196000.00, 0.00, NULL, 'Chủng loại: Dụng cụ bóc tách màng xương kiểu ADSON.\nChất liệu: Thép không gỉ\nHình dáng: Dạng thẳng; thiết kế 1 đầu làm việc\nKích thước: Chiều dài tổng thể từ 165mm đến 175mm. Bề rộng đầu bóc tách từ 7.5mm đến 8.5mm.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(492, 87, 217, 'Dụng cụ bóc tách màng xương Adson, đầu lưỡi tròn', 'A046', 'Grang', '2025', 'Mỹ', NULL, 100000.00, 200000.00, 0.00, NULL, 'Chủng loại: Dụng cụ bóc tách màng xương kiểu ADSON.\nChất liệu: Thép không gỉ\nHình dáng: Dạng thẳng; thiết kế 1 đầu làm việc, phần đầu cong tròn\nKích thước: Chiều dài tổng thể từ 165mm đến 175mm. Bề rộng đầu bóc tách từ 6.5mm đến 7.5mm.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(493, 87, 218, 'Dụng cụ bóc tách/bẩy xương Langenbeck, đầu tù, bản hẹp', 'A047', 'Grang', '2025', 'Mỹ', NULL, 102000.00, 204000.00, 0.00, NULL, 'Chủng loại: Cây bóc tách / bẩy xương, kiểu LANGENBECK.\nChất liệu: Thép không gỉ\nHình dáng: Dạng cong, đầu tù\nKích thước: Chiều dài tổng thể từ 190mm đến 200mm. Bề rộng đầu làm việc từ 7.0mm đến 8.5mm.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(494, 87, 219, 'Dụng cụ bóc tách/ bẩy xương Langenbeck, đầu tù, bản rộng', 'A048', 'Grang', '2025', 'Mỹ', NULL, 104000.00, 208000.00, 0.00, NULL, 'Chủng loại: Cây bóc tách / bẩy xương, kiểu LANGENBECK.\nChất liệu: Thép không gỉ\nHình dáng: Cán liền khối dạng vòng khuyên, dạng cong, đầu tù\nKích thước: Chiều dài tổng thể từ 225mm đến 235mm. Bề rộng đầu làm việc từ 10.5mm đến 11.5mm.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(495, 87, 220, 'Đục xương thẳng Stille, lưỡi vát một bên', 'A049', 'Grang', '2025', 'Mỹ', NULL, 106000.00, 212000.00, 0.00, NULL, 'Chủng loại: Đục xương, kiểu STILLE.\nChất liệu: Thép không gỉ\nHình dáng: Dạng đục thẳng; phần lưỡi đục vát một bên.\nKích thước: Chiều dài tổng thể từ 200mm đến 210mm. Bề rộng lưỡi đục từ 9.5mm đến 10.5mm.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(496, 87, 221, 'Kìm gặm xương cong', 'A050', 'Grang', '2025', 'Mỹ', NULL, 108000.00, 432000.00, 0.00, NULL, 'Chủng loại: Kìm gặm xương, kiểu LUER / RUSKIN.\nChất liệu: Thép không gỉ\nHình dáng: Dạng kìm cong;\nKích thước: Chiều dài tổng thể từ 175mm đến 185mm. Bề rộng đầu ngàm gặm từ 4.0mm đến 5.5mm.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(497, 87, 222, 'Kìm gặm xương cong Olivecrona', 'A051', 'Grang', '2025', 'Mỹ', NULL, 110000.00, 220000.00, 0.00, NULL, 'Chủng loại: Kìm gặm xương, kiểu OLIVECRONA.\nChất liệu: Thép không gỉ\nHình dáng: Dạng kìm cong; đầu gặm dạng thìa múc xương.\nKích thước: Chiều dài tổng thể từ 195mm đến 210mm. Bề rộng ngàm gặm từ 5.5mm đến 6.5mm.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(498, 87, 223, 'Tay khoan sọ não Hudson', 'A052', 'Grang', '2025', 'Mỹ', NULL, 112000.00, 224000.00, 0.00, NULL, 'Chủng loại: Tay khoan kiểu HUDSON.\nChất liệu: Thép không gỉ\nHình dáng: Thiết kế tay quay hình chữ U.\nĐặc điểm khác: Phần đầu tương thích với các mũi khoan kiểu Hudson;', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(499, 87, 224, 'Đầu nối Tay khoan Hudson', 'A053', 'Grang', '2025', 'Mỹ', NULL, 114000.00, 228000.00, 0.00, NULL, 'Chủng loại: Đầu nối / Cây nối dài tay quay khoan sọ, kiểu HUDSON.\nChất liệu: Thép không gỉ\nHình dáng: Thiết kế dạng trục thẳng;\nĐặc điểm khác: tương thích để lắp ghép vào cán quay khoan tay kiểu Hudson', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(500, 87, 225, 'Mũi khoan Hudson, loại nhỏ', 'A054', 'Grang', '2025', 'Mỹ', NULL, 116000.00, 232000.00, 0.00, NULL, 'Chủng loại: Mũi khoan sọ nã, kiểu HUDSON.\nChất liệu: Thép không gỉ\nKích thước: Đường kính đầu mũi khoan từ 8.5mm đến 9.5mm.\nĐặc điểm khác: Tương thích với cán khoan sọ tay quay;', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(501, 87, 226, 'Mũi khoan Hudson, loại vừa', 'A055', 'Grang', '2025', 'Mỹ', NULL, 118000.00, 236000.00, 0.00, NULL, 'Chủng loại: Mũi khoan sọ não, kiểu HUDSON.\nChất liệu: Thép không gỉ\nHình dáng: Dạng mũi khoan sọ kiểu chóp/nón;\nKích thước: Đường kính đầu mũi khoan từ 13.5mm đến 14.5mm.\nĐặc điểm khác: Tương thích với cán khoan sọ tay quay kiểu Hudson;', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(502, 87, 227, 'Mũi khoan Hudson, loại lớn', 'A056', 'Grang', '2025', 'Mỹ', NULL, 120000.00, 240000.00, 0.00, NULL, 'Chủng loại: Mũi khoan sọ não, kiểu HUDSON.\nChất liệu: Thép không gỉ\nHình dáng: Dạng mũi khoan sọ não hình cầu;\nKích thước: Đường kính đầu mũi khoan từ 15.5mm đến 16.5mm.\nĐặc điểm khác: Tương thích với cán khoan sọ tay quay kiểu Hudson;', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(503, 87, 228, 'Mũi khoan Hudson, loại cực lớn', 'A057', 'Grang', '2025', 'Mỹ', NULL, 122000.00, 244000.00, 0.00, NULL, 'Chủng loại: Mũi khoan sọ não, kiểu HUDSON.\nChất liệu: Thép không gỉ\nHình dáng: Dạng mũi khoan sọ não hình cầu;\nKích thước: Đường kính đầu mũi khoan từ 21.5mm đến 22.5mm.\nĐặc điểm khác: Tương thích với cán khoan sọ tay quay kiểu Hudson;', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(504, 87, 229, 'Mũi khoan sọ tự dừng, loại nhỏ', 'A058', 'Grang', '2025', 'Mỹ', NULL, 124000.00, 248000.00, 0.00, NULL, 'Chủng loại: Mũi khoan sọ tự dừng,\nChất liệu: Thép không gỉ\nKích thước: đầu mũi 7/11 mm', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(505, 87, 230, 'Mũi khoan sọ tự dừng, loại vừa', 'A059', 'Grang', '2025', 'Mỹ', NULL, 126000.00, 252000.00, 0.00, NULL, 'Chủng loại: Mũi khoan sọ tự dừng,\nChất liệu: Thép không gỉ\nKích thước: đầu mũi 11/14 mm', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(506, 87, 231, 'Cây dẫn dây cưa De Martel', 'A060', 'Grang', '2025', 'Mỹ', NULL, 128000.00, 256000.00, 0.00, NULL, 'Chủng loại: Cây dẫn cưa dây kiểu DE MARTEL.\nHình dáng: Dạng thanh dẹt, mảnh;\nKích thước: Chiều dài tổng thể từ 325mm đến 355mm.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(507, 87, 232, 'Banh tự giữ Mollison gập góc, răng nhọn', 'A061', 'Grang', '2025', 'Mỹ', NULL, 130000.00, 260000.00, 0.00, NULL, 'Chủng loại: Banh tự giữ kiểu MOLLISON\nChất liệu: Thép không gỉ.\nHình dáng: Dạng banh có cơ cấu khóa tự giữ; hai nhánh vén được cấu tạo kiểu 4 x 4 răng, dạng răng nhọn.\nKích thước: Chiều dài tổng thể từ 145mm đến 160mm.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(508, 87, 233, 'Banh tự giữ Anderson-Adson, răng nhọn', 'A062', 'Grang', '2025', 'Mỹ', NULL, 132000.00, 264000.00, 0.00, NULL, 'Chủng loại: Banh vén vết thương tự giữ, kiểu ANDERSON-ADSON.\nChất liệu: Thép không gỉ\nHình dáng: Dạng banh vén có cơ cấu tự giữ; cấu tạo ngàm vén kiểu 4 x 4 răng, dạng răng sắc nhọn.\nKích thước: Chiều dài tổng thể từ 185mm đến 205mm.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(509, 87, 234, 'Dụng cụ móc màng não Frazier đầu nhọn', 'A063', 'Grang', '2025', 'Mỹ', NULL, 134000.00, 536000.00, 0.00, NULL, 'Chủng loại: Móc vén màng não, kiểu FRAZIER.\nChất liệu: Thép không gỉ.\nHình dáng: Dụng cụ cán thẳng; đầu làm việc dạng 1 móc, thiết kế mũi nhọn/sắc.\nKích thước: Chiều dài tổng thể từ 125mm đến 135mm.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(510, 87, 235, 'Móc vén mạch máu và rễ thần kinh Crile', 'A064', 'Grang', '2025', 'Mỹ', NULL, 136000.00, 272000.00, 0.00, NULL, 'Chủng loại: Móc vén / Banh vén, kiểu CRILE.\nChất liệu: Thép không gỉ\nHình dáng: Dụng cụ cán thẳng; đầu làm việc dạng 1 móc gập góc, đầu tù.\nKích thước: Chiều dài tổng thể từ 195mm đến 205mm.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(511, 87, 236, 'Dụng cụ vén não Olivecrona hai đầu, loại nhỏ', 'A065', 'Grang', '2025', 'Mỹ', NULL, 138000.00, 276000.00, 0.00, NULL, 'Chủng loại: Dụng cụ vén não, kiểu OLIVECRONA.\nHình dáng: Thiết kế dạng thanh dẹt; có tính uốn dẻo hoặc đàn hồi; hai đầu làm việc.\nKích thước: Chiều dài tổng thể từ 175mm đến 185mm.\nBề rộng hai đầu làm việc lần lượt là: 7mm và 9mm (dung sai ± 0.5mm).', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(512, 87, 237, 'Vén não Olivecrona hai đầu, loại vừa', 'A066', 'Grang', '2025', 'Mỹ', NULL, 140000.00, 280000.00, 0.00, NULL, 'Chủng loại: Dụng cụ vén não, kiểu OLIVECRONA.\nHình dáng: Thiết kế dạng thanh dẹt; có tính uốn dẻo hoặc đàn hồi; hai đầu làm việc.\nKích thước: Chiều dài tổng thể từ 175mm đến 185mm.\nBề rộng hai đầu làm việc lần lượt là: 11mm và 13mm (dung sai ± 0.5mm).', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(513, 87, 238, 'Vén não Olivecrona hai đầu, loại lớn', 'A067', 'Grang', '2025', 'Mỹ', NULL, 142000.00, 568000.00, 0.00, NULL, 'Chủng loại: Dụng cụ vén não, kiểu OLIVECRONA.\nHình dáng: Thiết kế dạng thanh dẹt; có tính uốn dẻo hoặc đàn hồi; hai đầu làm việc.\nKích thước: Chiều dài tổng thể từ 175mm đến 185mm.\nBề rộng hai đầu làm việc lần lượt là: 18mm và 22mm (dung sai ± 0.5mm).', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(514, 87, 239, 'Ống hút phẫu thuật Fergusson/Frazier, loại nhỏ', 'A068', 'Grang', '2025', 'Mỹ', NULL, 144000.00, 288000.00, 0.00, NULL, 'Chủng loại: Ống hút phẫu thuật, kiểu FERGUSSON / FRAZIER.\nChất liệu: Thép không gỉ\nHình dáng: Hình dáng: Ống hút thân cứng, đầu uốn cong góc 45 độ, có lỗ điều chỉnh áp lực.\nKích thước: Chiều dài làm việc từ 105mm đến 115mm. Đường kính đầu ống hút tương đương khoảng 2.3mm đến 2.6mm.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(515, 87, 240, 'Ống hút phẫu thuật Fergusson/Frazier, loại vừa', 'A069', 'Grang', '2025', 'Mỹ', NULL, 146000.00, 292000.00, 0.00, NULL, 'Chủng loại: Ống hút phẫu thuật, kiểu FERGUSSON / FRAZIER.\nChất liệu: Thép không gỉ\nHình dáng: Ống hút thân cứng, đầu uốn cong góc 45 độ, có lỗ điều chỉnh áp lực.\nKích thước: Chiều dài làm việc từ 105mm đến 115mm. Đường kính đầu ống hút tương đương khoảng 2.8 mm đến 3.2 mm.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(516, 87, 241, 'Ống hút phẫu thuật Frazier gập góc, loại lớn', 'A070', 'Grang', '2025', 'Mỹ', NULL, 148000.00, 296000.00, 0.00, NULL, 'Chủng loại: Ống hút phẫu thuật, kiểu FERGUSSON / FRAZIER.\nChất liệu: Thép không gỉ\nHình dáng: Ống hút thân cứng, đầu uốn cong góc 45 độ, có lỗ điều chỉnh áp lực.\nKích thước: Chiều dài làm việc từ 105 mm đến 115mm. Đường kính đầu ống hút tương đương khoảng 3.8 mm đến 4.2 mm.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(517, 87, 242, 'Ống hút Fukushima, loại lớn', 'A071', 'Grang', '2025', 'Mỹ', NULL, 150000.00, 300000.00, 0.00, NULL, 'Chủng loại: Ống hút vi phẫu, kiểu FUKUSHIMA.\nChất liệu: Thép không gỉ.\nHình dáng: Ống hút đầu uốn cong góc khoảng 30°; đầu ống thuôn; có tính uốn dẻo;\nKích thước: Chiều dài làm việc từ 160 mm đến 170 mm. Đường kính đầu ống hút tương đương khoảng 3.8mm đến 4.2 mm (hoặc cỡ 12 Charr / 12FR).', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(518, 87, 243, 'Ống hút Fukushima, loại vừa', 'A072', 'Grang', '2025', 'Mỹ', NULL, 152000.00, 304000.00, 0.00, NULL, 'Chủng loại: Ống hút vi phẫu, kiểu FUKUSHIMA.\nChất liệu: Thép không gỉ.\nHình dáng: Ống hút đầu uốn cong góc khoảng 30°;đầu ống thuôn; có tính uốn dẻo;\nKích thước: Chiều dài làm việc từ 135mm đến 145mm. Đường kính đầu ống hút tương đương khoảng 2.5mm đến 2.9 mm (hoặc cỡ 8 Charr / 8FR).', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(519, 87, 244, 'Ống hút vi phẫu Fukushima, loại nhỏ', 'A073', 'Grang', '2025', 'Mỹ', NULL, 154000.00, 616000.00, 0.00, NULL, 'Chủng loại: Ống hút vi phẫu, kiểu FUKUSHIMA.\nChất liệu: Thép không gỉ.\nHình dáng: Ống hút đầu uốn cong góc khoảng 30°;đầu ống thuôn; có tính uốn dẻo;\nKích thước: Chiều dài làm việc từ 135mm đến 145mm. Đường kính đầu ống hút tương đương khoảng 2.1mm đến 2.5 mm (hoặc cỡ 7 Charr / 7FR).', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(520, 87, 245, 'Kìm cắt xương sọ Dahlgren, kèm móc giữ', 'A074', 'Grang', '2025', 'Mỹ', NULL, 156000.00, 312000.00, 0.00, NULL, 'Chủng loại: Kìm cắt xương sọ / Kìm bấm sọ, kiểu DAHLGREN.\nHình dáng: Dạng kìm; có móc giữ.\nKích thước: Chiều dài tổng thể từ 195mm đến 215mm.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(521, 87, 246, 'Dụng cụ dẫn hướng khoan và bảo vệ màng cứng Adson', 'A075', 'Grang', '2025', 'Mỹ', NULL, 158000.00, 316000.00, 0.00, NULL, 'Chủng loại: Dụng cụ dẫn hướng khoan và bảo vệ màng cứng Adson\nKích thước: Chiều dài tổng thể từ 150mm đến 180mm.\nVật liệu: thép không gỉ', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(522, 87, 247, 'Kẹp cầm máu vi phẫu lưỡng cực, mũi thẳng', 'A076', 'Grang', '2025', 'Mỹ', NULL, 160000.00, 320000.00, 0.00, NULL, 'Chủng loại: Kẹp phẫu thuật lưỡng cực\nĐặc tính kỹ thuật: Đầu kẹp có lớp phủ/công nghệ chống dính mô.\nHình dáng: Dạng kẹp; thân kẹp gập góc; lưỡi kẹp thẳng.\nCấu tạo kết nối: Sử dụng chuẩn kết nối dạng chân cắm đôi kiểu Mỹ.\nKích thước: Chiều dài tổng thể từ 215mm đến 235mm. Chiều dài làm việc từ 110 mm đến 130 mm. Kích thước mỏ kẹp tương đương khoảng 0.9 mm - 1.1 mm.\nĐặc điểm chung: thân kẹp có vỏ bọc cách điện.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(523, 87, 248, 'Kẹp cầm máu vi phẫu, lưỡng cực, chống dính, mũi cong gập góc', 'A077', 'Grang', '2025', 'Mỹ', NULL, 162000.00, 324000.00, 0.00, NULL, 'Chủng loại: Kẹp phẫu thuật lưỡng cực\nĐặc tính kỹ thuật: Đầu kẹp có lớp phủ/công nghệ chống dính mô.\nHình dáng: Dạng kẹp; thân kẹp gập góc; phần mũi kẹp hướng lên.\nCấu tạo kết nối: Sử dụng chuẩn kết nối dạng chân cắm đôi kiểu Mỹ.\nKích thước: Chiều dài tổng thể từ 215mm đến 225mm. Chiều dài làm việc từ 90 mm đến 120 mm. Kích thước mỏ kẹp tương đương khoảng 0.4 mm đến 0.5 mm.\nĐặc điểm chung: thân kẹp có vỏ bọc cách điện.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(524, 87, 249, 'Kẹp mang kim Mayo-Hegar thẳng', 'A078', 'Grang', '2025', 'Mỹ', NULL, 164000.00, 328000.00, 0.00, NULL, 'Chủng loại: Kìm kẹp kim phẫu thuật, kiểu MAYO-HEGAR.\nChất liệu: Thép không gỉ; ngàm chèn hợp kim cứng (TC).\nHình dạng: Mũi thẳng; tay cầm mạ màu vàng; Bước răng ngàm kẹp từ 0.45 mm đến 0.55mm.\nKích thước: Chiều dài tổng thể từ 175 mm đến 190 mm.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(525, 87, 250, 'Kẹp mang kim De Bakey thẳng', 'A079', 'Grang', '2025', 'Mỹ', NULL, 166000.00, 664000.00, 0.00, NULL, 'Chủng loại: Kìm kẹp kim kiểu De Bakey.\nChất liệu: Thép không gỉ, ngàm chèn hợp kim cứng (TC).\nHình dạng: Mũi thẳng, tay cầm mạ vàng. Bước răng ngàm kẹp khoảng từ 0.35 mm đến 0.45mm.\nKích thước: Chiều dài từ 200 mm đến 215 mm.\nĐặc điểm khác: Phù hợp kẹp chỉ cỡ 6/0-4/0.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(526, 87, 251, 'Kẹp mang kim thẳng, loại dài', 'A080', 'Grang', '2025', 'Mỹ', NULL, 168000.00, 336000.00, 0.00, NULL, 'Chủng loại: Kìm kẹp kim kiểu Mayo-Hegar hoặc tương đương.\nChất liệu: Thép không gỉ, ngàm chèn hợp kim cứng (TC).\nHình dạng: Mũi thẳng, tay cầm mạ vàng. Bước răng ngàm kẹp từ 0.4 mm đến 0.5 mm.\nKích thước: Chiều dài từ 195 mm đến 205 mm.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(527, 87, 252, 'Kẹp mang kim Crile-Wood thẳng', 'A081', 'Grang', '2025', 'Mỹ', NULL, 170000.00, 680000.00, 0.00, NULL, 'Chủng loại: Kìm kẹp kim kiểu Crile-Wood.\nChất liệu: Thép không gỉ, ngàm chèn hợp kim cứng (TC).\nHình dạng: Mũi thẳng, tay cầm mạ vàng. Bước răng ngàm kẹp khoảng từ 0.35mm đến 0.45 mm.\nKích thước: Chiều dài từ 195 mm đến 205 mm.\nĐặc điểm khác: Phù hợp kẹp chỉ cỡ 6/0 -4/0.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(528, 87, 253, 'Khay quả thận', 'A082', 'Grang', '2025', 'Mỹ', NULL, 172000.00, 344000.00, 0.00, NULL, 'Chủng loại: Khay hạt đậu / Khay quả thận\nChất liệu: Thép không gỉ.\nHình dạng: Khay hình hạt đậu/quả thận.\nKích thước: Chiều dài khoảng từ 245mm đến 255mm. Chiều rộng từ 135mm đến 165mm. Chiều cao từ 35 mm đến 45 mm.\nĐặc điểm khác: Dung tích chứa tối thiểu 500 ml.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(529, 87, 254, 'Bát đựng bệnh phẩm loại nhỏ', 'A083', 'Grang', '2025', 'Mỹ', NULL, 174000.00, 348000.00, 0.00, NULL, 'Chủng loại: Bát tròn / Cốc tròn đựng dung dịch.\nChất liệu: Thép không gỉ.\nHình dạng: Thiết kế dạng bát/cốc tròn sâu lòng.\nKích thước: Đường kính miệng bát từ 80mm đến 85mm. Chiều cao từ 40mm đến 45mm.\nĐặc điểm khác: Dung tích chứa trong khoảng từ 150ml đến 170ml.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(530, 87, 255, 'Bát đựng bệnh phẩm loại vừa', 'A084', 'Grang', '2025', 'Mỹ', NULL, 176000.00, 352000.00, 0.00, NULL, 'Chủng loại: Bát tròn / Cốc tròn đựng dung dịch.\nChất liệu: Thép không gỉ.\nHình dạng: Thiết kế dạng bát/cốc tròn sâu lòng.\nKích thước: Đường kính miệng bát từ 125mm đến 130mm. Chiều cao từ 50mm đến 60mm.\nĐặc điểm khác: Dung tích chứa trong khoảng từ 300ml đến 450ml.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(531, 87, 256, 'Bát đựng bệnh phẩm loại lớn', 'A085', 'Grang', '2025', 'Mỹ', NULL, 178000.00, 356000.00, 0.00, NULL, 'Chủng loại: Bát tròn / Cốc tròn đựng dung dịch\nChất liệu: Thép không gỉ.\nHình dạng: Thiết kế dạng bát tròn sâu lòng.\nKích thước: Đường kính miệng bát từ 150mm đến 175mm. Chiều cao từ 70 mm đến 80 mm.\nĐặc điểm khác: Dung tích chứa trong khoảng từ 950ml đến 1050ml.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(532, 87, 257, 'Bộ hộp hấp tiệt trùng và bảo quản dụng cụ phẫu thuật', 'A086', 'Grang', '2025', 'Mỹ', NULL, 180000.00, 360000.00, 0.00, NULL, 'Chủng loại: Bộ hộp đựng dụng cụ tiệt trùng đồng bộ, chuẩn kích cỡ 3/4.\nChất liệu: Thân hộp bằng hợp kim nhôm hoặc thép không gỉ; nắp đậy bằng nhựa chịu lực hoặc Hợp kim; Khay đựng bằng thép không gỉ; thảm lót bằng silicone y tế.\nHình dạng: Đáy hộp kín; Sử dụng màng/đĩa lọc cản khuẩn tái sử dụng. Khay lưới cho phép tích hợp giá/kẹp silicone\nKích thước:\nHộp ngoài: Chiều dài từ 460 mm đến 475 mm. Chiều rộng từ 270 mm đến 290 mm. Chiều cao tổng thể từ 140 mm đến 160 mm.\nRổ lưới / Thảm silicone lót đáy: Tương thích chuẩn cỡ 3/4. Chiều dài tấm silicone từ 390 mm đến 415 mm. Chiều rộng từ 240 mm đến 255 mm.\nĐặc điểm khác: Cấu hình đồng bộ trọn bộ bao gồm: 01 Thân hộp kín. 01 Nắp đậy màu xám. 01 Hệ thống lọc cản khuẩn tái sử dụng. 01 Khay lưới tương thích. 01 Thảm lót silicone. Hệ thống gá/giá đỡ cố định dụng cụ. Phụ kiện tiêu hao đi kèm.', NULL, '2026-09-11 11:07:44', '2026-09-11 11:14:32', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(619, 88, 165, 'Dây dẫn đường', 'A001', 'gggggggggggg', '2026', 'Mỹ', NULL, 10000000.00, 20000000.00, 0.00, NULL, 'Chiều dài 3m, chất liệu PVC', NULL, '2026-09-11 11:29:49', '2026-09-11 11:33:30', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Catalog trang 1-3'),
(620, 88, 166, 'Máy tạo nhịp tim', 'A001', 'gggggggggggg', '2026', 'Mỹ', NULL, 10000.00, 20000.00, 0.00, NULL, 'Máy tạo nhịp yêu cầu họatj động trong môi trường có nhiệt độ từ 20-40 độ C. \nI15Có đáp ứng tần số (Rate Response hoặc Rate Modulated hoặc Rate Adaptation)\nKết nối IS4/ IS2.\nTương thích hoặc cho phép chụp được cộng hưởng từ.', 'Kết nối IS4/ IS1.', '2026-09-11 11:29:49', '2026-09-11 11:33:30', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Catalog trang4-10'),
(621, 88, 167, 'Điện cực tạo nhịp', 'A001', 'gggggggggggg', '2026', 'Mỹ', NULL, 10000.00, 40000.00, 0.00, NULL, 'Chất liệu ABC.\nMàu trắng.', NULL, '2026-09-11 11:29:49', '2026-09-11 11:33:30', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Catalog trang 11-15'),
(622, 88, 164, 'Bơm tiêm MPV', 'BVP001', 'MPV', '2026', 'Việt Nam', NULL, 15000.00, 30000000.00, 0.00, NULL, 'Thể tích 10ml, có vạch chia thể tích. \nVỏ bằng PVC.\nMàu xanh', 'Màu trắng', '2026-09-11 11:29:49', '2026-09-11 11:33:30', 0, NULL, NULL, NULL, NULL, NULL, NULL, 'Việt Nam', NULL, 'Câtlog'),
(627, 90, 168, 'Màn hình', 'ABC1', 'AD', '2026', 'China', NULL, 50000000.00, 100000000.00, 0.00, NULL, 'Màn hình\nCấu hình:\n- Màn hình: 01 cái\n- Cáp nguồn: 01 cái\n- Adapter: 01 cái\n- Cáp tín hiệu DVI: 01 cái\n- Cáp tín hiệu HDMI: 01 cái\n- Cáp tín hiệu DP: 01 cái\n- Cáp tín hiệu BNC: 01 cái\n- Chân màn hình: 01 cái\nThông số kỹ thuật:\n- Kích thước màn hình:  ≥ 24 inch\n- Tỷ lệ hiển thị / tỷ lệ khung hình: 16:10\n- Màu sắc:  ≥ 1 triệu màu\n- Độ sáng:  ≥ 200 cd/m2\n- Tỷ lệ tương phản:  ≥ 800:1\n- Góc nhìn: ≥ 170°/170°\n- Trọng lượng:  ≤ 9kg (Không bao gồm chân màn hình)\n- Cổng tính hiệu đầu vào:\n+ DVI connector (x1),\n+ SDI connector(x1),\n+ S-Video connector(x2),\n+ RGB/ YPbPr connector(x3).\nỨng dụng: Hệ thống nội soi kỹ thuật số.\n- Loại đèn nền: LED\n- Nguồn điện sử dụng: 24V', NULL, '2026-09-13 08:58:14', '2026-09-13 09:45:15', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Catalog'),
(628, 90, 169, 'Bộ xử lý hình ảnh camera Full HD', 'ABC2', 'AD', '2026', 'China', NULL, 20000000.00, 40000000.00, 0.00, NULL, 'Cấu hình\n- Bộ xử lý hình ảnh: 01 cái\n- Đầu camera: 01 cái\n- Cáp nguồn: 01 cái\n- Cáp tín hiệu DVI-D: 01 cái\n- Cáp tín hiệu HDMI: 01 cái\n- Cầu chì dự phòng: 02 cái\nTính năng\n- Áp dụng cho các chuyên khoa: Phẫu thuật tiết niệu, phẫu thuật tổng quát\n- Tái tạo hình ảnh HD\n- Hỗ trợ chụp và lưu trữ hình ảnh.\nThông số kỹ thuật\n- Màn hình cảm ứng\n- Cảm biến hình ảnh: Cảm biến CMOS \n- Cổng kết nối USB:  ≥ 2.0 x 2\n- Nguồn điện: AC100- 240V 50Hz \n- Tín hiệu đầu ra: HDMI, 3G-SDI\n- Kích thước:  ≥ 360 x 300 x 85 mm\n- Công suất đầu vào:  ≥ 45VA\n- Đầu camera có 2 nút chức năng, dùng để dừng hình, ghi hình và cân bằng trắng.\n- Chiều dài dây cáp đầu camera:  ≥ 2.8m', NULL, '2026-09-13 08:58:14', '2026-09-13 09:45:15', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Catalog'),
(629, 90, 170, 'Nguồn sáng lạnh', 'ABC3', 'AD', '2026', 'China', NULL, 10000000.00, 20000000.00, 0.00, NULL, 'Cấu hình\n- Nguồn sáng lạnh LED: 01 cái\n- Cáp nguồn: 01 cái\n- Dây dẫn sáng: 01 cái\n- Cầu chì dự phòng: 02 cái\nTính năng\n- Màn hình: ≥ 5 inch\n- Chỉ số hoàn màu cao\nThông số kỹ thuật\n- Độ sáng: ≥ 700lm\n- Nhiệt độ màu: ≥ 5700K\n- Tuổi thọ của bóng LED: ≥ 20000 giờ\n33\n- Công suất tiêu thụ: ≥100VA\n- Kích thước sản phẩm: ≥ 360 x 300 x 85 mm', NULL, '2026-09-13 08:58:14', '2026-09-13 09:45:15', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Catalog'),
(630, 90, 171, 'Hệ thống xe đẩy nội soi', 'ABC4', 'AD', '2026', 'China', NULL, 40000000.00, 80000000.00, 0.00, NULL, '- Xe đẩy chuyên dụng đi kèm hệ thống nội soi, có cánh tay màn hình, bánh xe có khóa, có ổ cắm điện.', NULL, '2026-09-13 08:58:14', '2026-09-13 09:45:15', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Catalog'),
(639, 91, 168, 'Màn hình 1', 'Màn hình 2', 'Màn hình 3', 'Màn hình 4', 'Màn hình 5', NULL, 50000.00, 100000.00, 0.00, NULL, 'Đáp ứng về yêu cầu kỹ thuật 1', 'Các điểm KHÔNG đáp ứng về yêu cầu kỹ thuật 1', '2026-09-13 10:45:16', '2026-09-13 10:48:02', 0, NULL, NULL, NULL, NULL, NULL, NULL, 'Đáp ứng về nhóm nước, vùng lãnh thổ 1', 'Các điểm KHÔNG đáp ứng về nhóm nước, vùng lãnh thổ 1', 'Tài liệu chứng minh (cam kết, catalog, HDSD...)1'),
(640, 91, 169, 'Bộ xử lý hình ảnh camera Full HD 1', 'Bộ xử lý hình ảnh camera Full HD 2', 'Bộ xử lý hình ảnh camera Full HD 3', 'Bộ xử lý hình ảnh ca', 'Bộ xử lý hình ảnh camera Full HD 5', NULL, 10000.00, 20000.00, 0.00, NULL, 'Đáp ứng về yêu cầu kỹ thuật 2', 'Các điểm KHÔNG đáp ứng về yêu cầu kỹ thuật2', '2026-09-13 10:45:16', '2026-09-13 10:48:02', 0, NULL, NULL, NULL, NULL, NULL, NULL, 'Đáp ứng về nhóm nước, vùng lãnh thổ2', 'Các điểm KHÔNG đáp ứng về nhóm nước, vùng lãnh thổ 2', 'Tài liệu chứng minh (cam kết, catalog, HDSD...) 2'),
(641, 91, 170, 'Nguồn sáng lạnh 1', 'Nguồn sáng lạnh 2', 'Nguồn sáng lạnh 3', 'Nguồn sáng lạnh 4', 'Nguồn sáng lạnh 5', NULL, 10000.00, 20000.00, 0.00, NULL, 'Đáp ứng về yêu cầu kỹ thuật 3', 'Các điểm KHÔNG đáp ứng về yêu cầu kỹ thuật 3', '2026-09-13 10:45:16', '2026-09-13 10:48:02', 0, NULL, NULL, NULL, NULL, NULL, NULL, 'Đáp ứng về nhóm nước, vùng lãnh thổ 3', 'Các điểm KHÔNG đáp ứng về nhóm nước, vùng lãnh thổ 3', 'Tài liệu chứng minh (cam kết, catalog, HDSD...) 3'),
(642, 91, 171, 'Hệ thống xe đẩy nội soi 1', 'Hệ thống xe đẩy nội soi 2', 'Hệ thống xe đẩy nội soi 3', 'Hệ thống xe đẩy nội', 'Hệ thống xe đẩy nội soi 5', NULL, 10000.00, 20000.00, 0.00, NULL, NULL, NULL, '2026-09-13 10:45:16', '2026-09-13 10:48:02', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bg_bo`
--

CREATE TABLE `bg_bo` (
  `id` int(11) NOT NULL,
  `goi_thau_id` int(11) NOT NULL,
  `ma_bo` varchar(50) DEFAULT NULL COMMENT 'Mã bộ/phần/hệ thống — cột (1) Phụ lục III',
  `stt_bo` int(11) DEFAULT NULL COMMENT 'STT bộ — cột (2)',
  `ten_bo` varchar(1000) DEFAULT NULL COMMENT 'Tên bộ/phần/hệ thống — cột (3)',
  `yeu_cau_chung` text DEFAULT NULL COMMENT 'Chỉ bộ dụng cụ + hệ thống TBYT',
  `yeu_cau_khac` text DEFAULT NULL COMMENT 'Chỉ bộ dụng cụ + hệ thống TBYT',
  `yeu_cau_cau_hinh` text DEFAULT NULL COMMENT 'CHỈ hệ thống TBYT',
  `nhom_nuoc` varchar(500) DEFAULT NULL COMMENT 'Yêu cầu nhóm nước, vùng lãnh thổ',
  `dvt` varchar(50) DEFAULT NULL COMMENT 'ĐVT của bộ (Bộ, Hệ thống...)',
  `so_luong` decimal(18,3) NOT NULL DEFAULT 0.000,
  `thu_tu` int(11) NOT NULL DEFAULT 0,
  `ngay_tao` datetime DEFAULT current_timestamp(),
  `ngay_cap_nhat` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `nguoi_tao` int(11) DEFAULT NULL,
  `nguoi_cap_nhat` int(11) DEFAULT NULL,
  `da_xoa` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bộ/phần/hệ thống trong 1 gói thầu — Phụ lục III Thư mời';

--
-- Đang đổ dữ liệu cho bảng `bg_bo`
--

INSERT INTO `bg_bo` (`id`, `goi_thau_id`, `ma_bo`, `stt_bo`, `ten_bo`, `yeu_cau_chung`, `yeu_cau_khac`, `yeu_cau_cau_hinh`, `nhom_nuoc`, `dvt`, `so_luong`, `thu_tu`, `ngay_tao`, `ngay_cap_nhat`, `nguoi_tao`, `nguoi_cap_nhat`, `da_xoa`) VALUES
(39, 36, 'BDC01', 1, 'BỘ DỤNG CỤ PHẪU THUẬT SỌ NÃO', 'Bộ dụng cụ đồng bộ, cùng một hãng sản xuất.\nChất liệu thép không gỉ y tế, tiệt trùng được bằng hấp ướt 134°C.\nCó khay/hộp đựng chuyên dụng kèm theo.', 'Bảo hành tối thiểu 12 tháng.\nCó tài liệu hướng dẫn sử dụng và vệ sinh tiệt trùng bằng tiếng Việt.', NULL, 'Nhóm G7, EU', 'Bộ', 2.000, 1, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 1, 1, 0),
(40, 36, 'BDC02', 2, 'BỘ DỤNG CỤ PHẪU THUẬT CHI DƯỚI', 'Bộ dụng cụ đồng bộ, cùng một hãng sản xuất.\nChất liệu thép không gỉ y tế, chịu được hấp tiệt trùng lặp lại.', 'Bảo hành tối thiểu 12 tháng.\nGiao hàng kèm khay đựng inox.', NULL, 'Nhóm G7, EU, Úc', 'Bộ', 3.000, 2, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 1, 1, 0),
(41, 37, 'HT01', 1, 'HỆ THỐNG MÁY SIÊU ÂM MÀU 4D', 'Hàng mới 100%, sản xuất từ năm 2025 trở lại đây.\nĐồng bộ nguyên hệ thống của cùng một hãng.\nCó giấy phép lưu hành tại Việt Nam còn hiệu lực.', 'Bảo hành tối thiểu 24 tháng tại nơi sử dụng.\nĐào tạo vận hành cho tối thiểu 05 nhân viên.\nCam kết cung cấp vật tư thay thế tối thiểu 10 năm.', 'Hệ thống hoàn chỉnh gồm: 01 máy chính; 01 màn hình ≥ 21 inch;\n03 đầu dò (convex, linear, phased array);\n01 phần mềm đo tim mạch; 01 xe đẩy chuyên dụng.', 'Nhóm G7, EU', 'Hệ thống', 1.000, 1, '2026-09-10 20:26:31', '2026-09-10 21:39:18', 1, 1, 0),
(42, 37, 'HT02', 2, 'HỆ THỐNG NỘI SOI TIÊU HÓA ỐNG MỀM', 'Hàng mới 100%, sản xuất từ năm 2025 trở lại đây.\nĐồng bộ nguyên hệ thống của cùng một hãng.', 'Bảo hành tối thiểu 24 tháng.\nCó sẵn linh kiện thay thế trong nước.', 'Hệ thống gồm: 01 nguồn sáng LED; 01 bộ xử lý hình ảnh;\n01 dây soi dạ dày; 01 dây soi đại tràng; 01 màn hình y tế.', 'Nhật Bản, EU', 'Hệ thống', 1.000, 2, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 1, 1, 0),
(61, 49, 'VT002', 1, 'Bộ máy tạo nhịp', NULL, NULL, NULL, 'G7', 'Bộ', 2.000, 1, '2026-09-11 08:43:00', '2026-09-11 08:43:00', 1, 1, 0),
(62, 51, NULL, 1, 'Hệ thống nội soi tiết niệu', 'Năm sản xuất 2026 trở về sau, hàng mới 100%', 'Thời gian giao hàng trong vòng 60 ngày kể từ ngày ký kết hợp đồng:\nThời gian bảo hành: 12 tháng.\nCó cam kết cung cấp vật tư tiêu hao tương thích máy ít nhất 8 năm.\nCam kết cung cấp phụ tùng, linh kiện thay thế chính hãng trong vòng 08 năm.\nCó chứng chỉ CO do cơ quan có thẩm quyền cấp và chứng chỉ CQ do nhà sản xuất cấp.\nCó ủy quyền của nhà sản xuất hoặc đại lý hợp pháp của nhà sản xuất.\nCó giấy phép nhập khẩu thiết bị do Bộ Y Tế cấp đối với các thiết bị phải xin phép nhập khẩu theo quy định (nếu có).', 'Cấu hình máy chính:\n- Màn hình nội soi: 01 cái\n- Bộ xử lý hình ảnh camera Full HD, kèm phụ kiện: 01 cái\n- Nguồn sáng lạnh LED, kèm dây dẫn sáng: 01 cái\n- Xe đẩy hệ thống nội soi: 01 cái\n- Bộ dụng cụ: 2 bộ\nCấu hình bộ dụng cụ\n+ Ống soi tiết niệu đường kính 4mm, 30 độ: 02 cái\n+ Ống soi tiết niệu đường kính 4mm, 0 độ: 01 cái \n+ Vỏ đặt soi khám bàng quang 21 Charr: 01 cái\n+ Nòng dẫn hướng 21 Charr: 01 cái\n+ Vỏ đặt soi khám bàng quang 23 Charr: 01 cái\n+ Nòng dẫn hướng 23 Charr: 01 cái\n+ Cầu nối ống soi với 1 kênh dụng cụ: 01 cái\n+ Forceps rút sonde JJ bàng quang thân mềm cỡ 7 Charr: 02 cái\nBộ hướng dẫn sử dụng tiếng Anh + tiếng Việt: 1 bộ', NULL, 'Hệ thống', 2.000, 1, '2026-09-11 09:44:43', '2026-09-11 09:44:43', 1, 1, 0),
(63, 52, NULL, 1, 'BỘ DỤNG CỤ PHẪU THUẬT SỌ NÃO', 'Mới 100%\nNăm sản xuất: 2025 trở về sau\nNhà sản xuất phải đạt tiêu chuẩn chất lượng: ISO 13485, ISO 7153-1, ISO 5832-2...\nSản phẩm đạt chứng nhận CE hoặc EU MDR hoặc FDA\nTất cả dụng cụ đều hấp tiết trùng được ở nhiệt độ ≥134ºC\nHàng hóa phải có mã vạch/mã code/link... hoặc các phương pháp tương đương thể hiện đầy đủ thông tin hàng hóa.', 'Số lưu hành hoặc số công bố tiêu chuẩn áp dụng còn hiệu lực do Bộ Y Tế cấp (nếu có) khi giao hàng đối với các thiết bị phải xin phép nhập khẩu\nCung cấp đầy đủ CO, CQ, tờ khai hải quan (đối với các thiết bị nhập khẩu),… khi bàn giao, lắp đặt\nKhi có yêu cầu kiểm tra, sửa chữa đột xuất, trong vòng: ≤ 48 giờ nhà thầu phải cử Kỹ sư đến kiểm tra sự cố và có kế hoạch sửa chữa (không tính thứ 7 chủ nhật và ngày lễ và trừ trường hợp bất khả kháng như: thiên tai, thảm họa, dịch bệnh…).\nCam kết cung cấp và có báo giá phụ tùng thay thế ≥ 8 năm\nThời gian bảo hành thiết bị ≥ 12 tháng\nLắp đặt, hướng dẫn sử dụng tại Bệnh viện HNĐK Nghệ An – Nghệ An\nThực hiện bảo trì, bảo dưỡng theo tiêu chuẩn của nhà sản xuất', NULL, 'G7', 'Bộ', 2.000, 1, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(64, 52, NULL, 2, 'BỘ DỤNG CỤ PHẪU THUẬT CHI DƯỚI', 'Mới 100%\nNăm sản xuất: 2025 trở về sau\nĐạt tiêu chuẩn chất lượng: ISO 13485, ISO 7153-1, ISO 5832-2...\nSản phẩm đạt chứng nhận CE hoặc EU MDR hoặc FDA\nTất cả dụng cụ đều hấp tiết trùng được ở nhiệt độ ≥134ºC\nHàng hóa phải có mã vạch/mã code/link,... hoặc các phương pháp tương đương thể hiện đầy đủ thông tin hàng hóa.', 'Số lưu hành hoặc số công bố tiêu chuẩn áp dụng còn hiệu lực do Bộ Y Tế cấp (nếu có) khi giao hàng đối với các thiết bị phải xin phép nhập khẩu\nCung cấp đầy đủ CO, CQ, tờ khai hải quan (đối với các thiết bị nhập khẩu),… khi bàn giao, lắp đặt\nKhi có yêu cầu kiểm tra, sửa chữa đột xuất, trong vòng: ≤ 48 giờ nhà thầu phải cử Kỹ sư đến kiểm tra sự cố và có kế hoạch sửa chữa (không tính thứ 7 chủ nhật và ngày lễ và trừ trường hợp bất khả kháng như: thiên tai, thảm họa, dịch bệnh…).\nCam kết cung cấp và có báo giá phụ tùng thay thế ≥ 8 năm\nThời gian bảo hành thiết bị ≥ 12 tháng\nLắp đặt, hướng dẫn sử dụng tại Bệnh viện HNĐK Nghệ An – Nghệ An\nThực hiện bảo trì, bảo dưỡng theo tiêu chuẩn của nhà sản xuất', NULL, 'G7', 'Bộ', 1.000, 2, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bg_file`
--

CREATE TABLE `bg_file` (
  `id` int(11) NOT NULL,
  `ten_file` varchar(255) NOT NULL COMMENT 'Tên file trên đĩa: <mst>_<slug-goi-thau>.<ext>',
  `ten_file_goc` varchar(255) DEFAULT NULL COMMENT 'Tên gốc nhà thầu đặt, chỉ để hiển thị',
  `duong_dan` varchar(100) NOT NULL DEFAULT 'ban_ky' COMMENT 'Thư mục con trong assets/uploads',
  `loai_file` varchar(20) DEFAULT NULL COMMENT 'Đuôi file: pdf, jpg, png',
  `mime_type` varchar(100) DEFAULT NULL COMMENT 'MIME thật đọc bằng finfo',
  `kich_thuoc` int(11) DEFAULT 0 COMMENT 'Dung lượng (byte)',
  `nhom_file` varchar(50) NOT NULL DEFAULT 'ban_ky' COMMENT 'Phân loại nghiệp vụ: ban_ky, ...',
  `ngay_tao` datetime DEFAULT current_timestamp(),
  `ngay_cap_nhat` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `nguoi_tao` int(11) DEFAULT NULL,
  `nguoi_cap_nhat` int(11) DEFAULT NULL,
  `da_xoa` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `bg_file`
--

INSERT INTO `bg_file` (`id`, `ten_file`, `ten_file_goc`, `duong_dan`, `loai_file`, `mime_type`, `kich_thuoc`, `nhom_file`, `ngay_tao`, `ngay_cap_nhat`, `nguoi_tao`, `nguoi_cap_nhat`, `da_xoa`) VALUES
(1, '0101234527_mua-vat-tu-tieu-hao-phau-thuat-cot-song-va-than-kinh-so-nao.jpg', '1786630501291_8080164154538739234_8080164154538739234_b208af94d5cfb388d9579d9645f09e4d.jpg', 'ban_ky', 'jpg', 'image/jpeg', 741648, 'ban_ky', '2026-08-18 21:11:55', '2026-08-19 07:01:24', 1, 1, 0),
(4, '0209876543_mua-vat-tu-tieu-hao-phau-thuat-cot-song-va-than-kinh-so-nao.png', 'logo-nentrang.png', 'ban_ky', 'png', 'image/png', 12441, 'ban_ky', '2026-08-19 07:36:51', '2026-08-19 07:36:51', 1, 1, 0),
(6, '1234567891_mua-vat-tu-nha-khoa-va-dung-cu-phau-thuat-rang-mieng-nam.jpg', 'bg_header3.jpg', 'ban_ky', 'jpg', 'image/jpeg', 88098, 'ban_ky', '2026-08-21 22:35:05', '2026-08-21 23:32:17', 1, 1, 1),
(13, '1234567891_mua-vat-tu-tieu-hao-phau-thuat-cot-song-va-than-kinh-so-nao.jpg', 'bg_header3.jpg', 'ban_ky', 'jpg', 'image/jpeg', 88098, 'ban_ky', '2026-08-21 23:22:21', '2026-08-21 23:22:21', 1, 1, 0),
(15, '1234567891_mua-vat-tu-nha-khoa-va-dung-cu-phau-thuat-rang-mieng-nam.png', 'logo-nentrang.png', 'ban_ky', 'png', 'image/png', 12441, 'ban_ky', '2026-08-21 23:32:17', '2026-08-21 23:32:17', 1, 1, 0),
(17, '1234567441_mua-vat-tu-nha-khoa-va-dung-cu-phau-thuat-rang-mieng-nam.jpg', 'bg_header1.jpg', 'ban_ky', 'jpg', 'image/jpeg', 130378, 'ban_ky', '2026-08-22 07:09:32', '2026-08-22 07:09:32', 1, 1, 0),
(19, '1234517891_mua-vat-tu-tieu-hao-phau-thuat-cot-song-va-than-kinh-so-nao.png', 'logo-nentrang.png', 'ban_ky', 'png', 'image/png', 12441, 'ban_ky', '2026-08-22 09:44:45', '2026-08-22 09:44:45', 1, 1, 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bg_goi_thau`
--

CREATE TABLE `bg_goi_thau` (
  `id` int(11) NOT NULL,
  `so_thong_bao` varchar(100) NOT NULL COMMENT 'Số thông báo mời chào giá, VD: 5742/2026',
  `ten_goi_thau` varchar(500) NOT NULL,
  `nhom` varchar(20) NOT NULL DEFAULT 'vat_tu_duoc' COMMENT 'bo_dung_cu | he_thong_tbyt | vat_tu_duoc',
  `noi_dung` text DEFAULT NULL COMMENT 'Mô tả/danh mục hàng hóa tóm tắt',
  `ngay_phat_hanh` date DEFAULT NULL,
  `thoi_gian_mo_bao_gia` datetime DEFAULT NULL COMMENT 'Bắt đầu nhận báo giá. NULL = nhận ngay khi mở',
  `thoi_gian_dong_bao_gia` datetime DEFAULT NULL COMMENT 'Kết thúc nhận báo giá. NULL = không giới hạn giờ',
  `han_cuoi` date DEFAULT NULL COMMENT 'Hạn cuối tiếp nhận báo giá',
  `thoi_gian_hop_dong` int(11) DEFAULT 0 COMMENT 'Thời gian thực hiện hợp đồng (tháng)',
  `hieu_luc_bao_gia` int(11) DEFAULT 180 COMMENT 'Hiệu lực báo giá tối thiểu (ngày)',
  `token` varchar(64) NOT NULL COMMENT 'Token public dùng cho link QR',
  `trang_thai` int(11) DEFAULT 1 COMMENT '0=Nhap, 1=Dang mo, 2=Da dong, 3=Da tong hop',
  `ngay_tao` datetime DEFAULT current_timestamp(),
  `ngay_cap_nhat` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `nguoi_tao` int(11) DEFAULT NULL,
  `nguoi_cap_nhat` int(11) DEFAULT NULL,
  `da_xoa` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `bg_goi_thau`
--

INSERT INTO `bg_goi_thau` (`id`, `so_thong_bao`, `ten_goi_thau`, `nhom`, `noi_dung`, `ngay_phat_hanh`, `thoi_gian_mo_bao_gia`, `thoi_gian_dong_bao_gia`, `han_cuoi`, `thoi_gian_hop_dong`, `hieu_luc_bao_gia`, `token`, `trang_thai`, `ngay_tao`, `ngay_cap_nhat`, `nguoi_tao`, `nguoi_cap_nhat`, `da_xoa`) VALUES
(1, '5742/2026', 'Mua vật tư tiêu hao phẫu thuật cột sống và thần kinh sọ não năm 2026', 'vat_tu_duoc', 'Nẹp tạo hình bản sống cổ lối sau; Vít tạo hình bản sống cổ lối sau; Van dẫn lưu dịch não tủy ổ bụng có kèm catheter phủ kháng sinh', '2026-08-17', '2026-08-17 23:11:59', '2026-08-30 17:00:00', '2026-08-30', 24, 180, '6e6bd76dd01e7582dd400a27398460b4', 1, '2026-08-20 23:11:59', '2026-09-10 21:47:30', 1, 1, 0),
(2, '5800/2026', 'Mua vật tư nha khoa và dụng cụ phẫu thuật răng miệng năm 2026', 'vat_tu_duoc', 'Hộp đựng mũi khoan; Bộ que hàn Composite; Cây nạo nha chu GRACEY các số', '2026-07-31', '2026-07-31 23:11:00', '2026-09-30 17:00:00', '2026-08-18', 12, 120, '967d5f6f79530ed70500cb8c0fb8d3b6', 1, '2026-08-20 23:11:59', '2026-09-10 21:38:45', 1, 1, 0),
(3, 'qr123', '123', 'vat_tu_duoc', '123', '2026-08-22', '2026-08-22 13:31:00', '2026-09-05 17:00:00', '2026-09-05', 0, 180, '10ac86516689916a12154a55d5dda05e', 0, '2026-08-22 13:31:58', '2026-08-22 13:31:58', 1, 1, 0),
(36, '5901/2026', 'Mua bộ dụng cụ phẫu thuật sọ não và chi dưới năm 2026', 'bo_dung_cu', 'Bộ dụng cụ phẫu thuật sọ não; Bộ dụng cụ phẫu thuật chi dưới', '2026-09-05', '2026-09-05 20:26:31', '2026-09-25 17:00:00', '2026-09-25', 24, 180, '173a120bef6db8ec6f9be0ef87c9d205', 1, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 1, 1, 0),
(37, '5902/2026', 'Mua hệ thống máy siêu âm và hệ thống nội soi tiêu hóa năm 2026', 'he_thong_tbyt', 'Hệ thống máy siêu âm màu 4D; Hệ thống nội soi tiêu hóa ống mềm', '2026-09-08', '2026-09-08 20:26:31', '2026-09-30 17:00:00', '2026-09-30', 36, 180, 'c929a18c5105116a2e485c4c4baf56a3', 1, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 1, 1, 0),
(49, '1192026', 'LUONG-VT DƯƠC', 'vat_tu_duoc', '', '2026-09-11', '2026-09-11 08:11:00', '2026-09-25 17:00:00', '2026-09-25', 0, 180, 'b3e010c724cb2ad3888dac8de7e2b983', 1, '2026-09-11 08:15:46', '2026-09-11 10:38:03', 1, 1, 0),
(50, '11.192026', 'LUONG-HE THONG SIEU AM', 'he_thong_tbyt', '', '2026-09-11', '2026-09-11 08:51:00', '2026-09-25 17:00:00', '2026-09-25', 0, 180, '1e8262d3b04e89c42ad7d4f2e7876903', 0, '2026-09-11 08:52:13', '2026-09-11 09:27:10', 1, 1, 1),
(51, '11.192026', 'LUONG-Mua hệ thống nội soi tiết niệu', 'he_thong_tbyt', '', '2026-09-11', '2026-09-11 09:27:00', '2026-09-25 17:00:00', '2026-09-25', 0, 180, 'a02bb80223d1375dccb903dd1acb9b3e', 1, '2026-09-11 09:27:45', '2026-09-11 10:34:06', 1, 1, 0),
(52, '11.292026', 'LUONG- BO DỤNG CU', 'bo_dung_cu', '', '2026-09-11', '2026-09-11 10:46:00', '2026-09-25 17:00:00', '2026-09-25', 0, 180, '03548b12c5fdefeaa341f1cfdc7c4c77', 1, '2026-09-11 10:46:26', '2026-09-11 10:46:26', 1, 1, 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bg_hang_hoa`
--

CREATE TABLE `bg_hang_hoa` (
  `id` int(11) NOT NULL,
  `goi_thau_id` int(11) NOT NULL,
  `bo_id` int(11) DEFAULT NULL COMMENT 'Thuộc bộ nào (bg_bo.id)',
  `stt_chi_tiet` int(11) DEFAULT NULL COMMENT 'STT chi tiết trong bộ — cột (4)',
  `ma_hh` varchar(50) DEFAULT NULL COMMENT 'Mã hàng hóa (VD: VT001) — Phụ lục III & Mẫu 1, Mẫu 2',
  `ten_hang_hoa` varchar(1000) NOT NULL COMMENT 'D: Tên hàng hoá',
  `thong_so_ky_thuat` text DEFAULT NULL COMMENT 'E: Tính năng, thông số kỹ thuật',
  `nhom_nuoc` varchar(500) DEFAULT NULL COMMENT 'Yêu cầu nhóm nước riêng cho hàng chi tiết',
  `dvt` varchar(50) DEFAULT NULL COMMENT 'H: Đơn vị tính',
  `so_luong` decimal(18,3) DEFAULT 0.000 COMMENT 'I: Số lượng/khối lượng',
  `thu_tu` int(11) DEFAULT 0 COMMENT 'Thứ tự hiển thị',
  `ngay_tao` datetime DEFAULT current_timestamp(),
  `ngay_cap_nhat` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `nguoi_tao` int(11) DEFAULT NULL,
  `nguoi_cap_nhat` int(11) DEFAULT NULL,
  `da_xoa` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `bg_hang_hoa`
--

INSERT INTO `bg_hang_hoa` (`id`, `goi_thau_id`, `bo_id`, `stt_chi_tiet`, `ma_hh`, `ten_hang_hoa`, `thong_so_ky_thuat`, `nhom_nuoc`, `dvt`, `so_luong`, `thu_tu`, `ngay_tao`, `ngay_cap_nhat`, `nguoi_tao`, `nguoi_cap_nhat`, `da_xoa`) VALUES
(1, 1, NULL, 1, 'HH001', 'Nẹp tạo hình bản sống cổ lối sau', 'Vật liệu: Hợp kim Titan hoặc Cobalt Chrome hoặc vật liệu tương đương về độ bền cơ lý\nHình thái nẹp phù hợp với vị trí mở cung sau\nChiều dài các cỡ từ ≤ 8mm đến ≥ 16mm', NULL, 'Cái', 100.000, 1, '2026-08-20 23:11:59', '2026-09-10 21:40:29', 1, 1, 0),
(2, 1, NULL, 1, 'HH002', 'Vít tạo hình bản sống cổ lối sau', 'Vật liệu: Hợp kim Titan hoặc Cobalt Chrome\nTự taro\nĐường kính các cỡ ≥ 2,5mm', NULL, 'Cái', 300.000, 2, '2026-08-20 23:11:59', '2026-09-10 21:40:29', 1, 1, 0),
(3, 1, NULL, NULL, 'HH003', 'Van dẫn lưu dịch não tủy ổ bụng có kèm catheter phủ kháng sinh', 'Bộ van dùng để dẫn lưu dịch não tủy ổ bụng, có kèm catheter phủ kháng sinh\nĐóng gói tiệt trùng bao gồm: 01 Van bằng Polysulfone hoặc tương đương', NULL, 'Cái', 50.000, 3, '2026-08-20 23:11:59', '2026-09-10 21:45:37', 1, 1, 0),
(4, 1, NULL, NULL, 'HH004', 'Bộ vật tư dùng trong điều trị đau thần kinh sử dụng sóng cao tần', 'Bộ kim và điện cực dùng cho máy phát sóng cao tần\nTương thích với hệ thống hiện có', NULL, 'Bộ', 80.000, 4, '2026-08-20 23:11:59', '2026-09-10 21:45:37', 1, 1, 0),
(5, 1, NULL, NULL, 'HH005', 'Lưới cắt đốt bằng sóng cao tần dùng trong nội soi cột sống 2 cổng', 'Loại dùng bên ngoài ống tủy sống\nĐầu uốn được\nTương thích hệ thống nội soi 2 cổng', NULL, 'Cái', 120.000, 5, '2026-08-20 23:11:59', '2026-09-10 21:45:37', 1, 1, 0),
(6, 2, NULL, NULL, 'HH001', 'Hộp đựng mũi khoan', 'Chất liệu inox, có nắp, tiệt trùng được', NULL, 'Cái', 40.000, 1, '2026-08-20 23:11:59', '2026-09-10 21:45:37', 1, 1, 0),
(7, 2, NULL, NULL, 'HH002', 'Bộ que hàn Composite (Cây trám)', 'Bộ đầy đủ các cỡ, thép không gỉ', NULL, 'Bộ', 25.000, 2, '2026-08-20 23:11:59', '2026-09-10 21:45:37', 1, 1, 0),
(8, 2, NULL, NULL, 'HH003', 'Cây nạo nha chu GRACEY số 11-12', 'Thép không gỉ, tay cầm chống trượt', NULL, 'Cái', 60.000, 3, '2026-08-20 23:11:59', '2026-09-10 21:45:37', 1, 1, 0),
(9, 2, NULL, NULL, 'HH004', 'Thước đo túi lợi', 'Có vạch chia mm rõ nét, thép không gỉ', NULL, 'Cái', 30.000, 4, '2026-08-20 23:11:59', '2026-09-10 21:45:37', 1, 1, 0),
(18, 3, NULL, NULL, 'HH001', 'Ví dụ: Nẹp tạo hình bản sống cổ', 'Vật liệu: Hợp kim Titan; chiều dài ≥ 8mm', NULL, 'Cái', 100.000, 1, '2026-08-22 14:05:32', '2026-09-10 21:45:37', 1, 1, 0),
(19, 3, NULL, NULL, 'h002', 'Ví dụ: Vít tạo hình (bỏ trống Mã HH → tự sinh)', 'Tự taro; đường kính ≥ 2,5mm', NULL, 'Cái', 300.000, 2, '2026-08-22 14:05:32', '2026-09-10 21:45:37', 1, 1, 0),
(96, 36, 39, 1, 'BDC01.1', 'Khớp nối cố định thanh đỡ hệ thống vén não với ray bên bàn mổ', 'Chất liệu thép không gỉ.\nKẹp chắc vào ray bàn mổ tiêu chuẩn.\nXoay được 360°.', NULL, 'Cái', 2.000, 1, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 1, 1, 0),
(97, 36, 39, 2, 'BDC01.2', 'Thanh đỡ hệ thống vén não', 'Chiều dài ≥ 30cm.\nCó khớp mềm dẻo điều chỉnh nhiều hướng, khóa cố định chắc chắn.', NULL, 'Cái', 2.000, 2, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 1, 1, 0),
(98, 36, 39, 3, 'BDC01.3', 'Van vén não các cỡ', 'Bộ gồm ít nhất 3 cỡ khác nhau.\nBề mặt nhẵn, bo tròn không gây tổn thương mô não.', NULL, 'Bộ', 2.000, 3, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 1, 1, 0),
(99, 36, 39, 4, 'BDC01.4', 'Kìm gặm xương Kerrison', 'Góc 40°, bản rộng 2mm và 3mm.\nLưỡi cắt sắc, tháo rời vệ sinh được.', NULL, 'Cái', 4.000, 4, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 1, 1, 0),
(100, 36, 40, 1, 'BDC02.1', 'Cán dao mổ số 3', 'Thép không gỉ.\nKhía chống trượt.\nLắp vừa lưỡi dao số 10-15.', NULL, 'Cái', 3.000, 5, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 1, 1, 0),
(101, 36, 40, 2, 'BDC02.2', 'Cán dao mổ số 4', 'Thép không gỉ.\nKhía chống trượt.\nLắp vừa lưỡi dao số 20-24.', NULL, 'Cái', 3.000, 6, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 1, 1, 0),
(102, 36, 40, 3, 'BDC02.3', 'Ống hút Yankauer', 'Thép không gỉ.\nĐầu bo tròn, có lỗ bên.\nDài ≥ 25cm.', NULL, 'Cái', 6.000, 7, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 1, 1, 0),
(103, 36, 40, 4, 'BDC02.4', 'Kẹp phẫu tích có mấu', 'Dài 16cm và 20cm.\nĐầu kẹp khít, không lệch.', NULL, 'Cái', 6.000, 8, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 1, 1, 0),
(104, 36, 40, 5, 'BDC02.5', 'Kéo phẫu thuật Mayo cong', 'Dài 17cm.\nLưỡi cong, cắt ngọt, không kẹt.', NULL, 'Cái', 3.000, 9, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 1, 1, 0),
(105, 37, 41, 1, 'HT01.1', 'Máy chính siêu âm màu', 'Màn hình cảm ứng điều khiển ≥ 10 inch.\nỔ cứng lưu trữ ≥ 500GB.\nTối thiểu 3 cổng kết nối đầu dò.\nDải tần số 1-18 MHz.', NULL, 'Cái', 1.000, 1, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 1, 1, 0),
(106, 37, 41, 2, 'HT01.2', 'Màn hình hiển thị chính', 'Kích thước ≥ 21 inch, độ phân giải Full HD trở lên.\nXoay/nghiêng điều chỉnh được.', NULL, 'Cái', 1.000, 2, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 1, 1, 0),
(107, 37, 41, 3, 'HT01.3', 'Đầu dò Convex', 'Dải tần 1-6 MHz.\nDùng cho siêu âm ổ bụng, sản khoa.', NULL, 'Cái', 1.000, 3, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 1, 1, 0),
(108, 37, 41, 4, 'HT01.4', 'Đầu dò Linear', 'Dải tần 5-15 MHz.\nDùng cho mạch máu, phần mềm, tuyến giáp.', NULL, 'Cái', 1.000, 4, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 1, 1, 0),
(109, 37, 41, 5, 'HT01.5', 'Phần mềm đo và phân tích tim mạch', 'Đo tự động EF, Doppler mô.\nBản quyền vĩnh viễn kèm máy.', NULL, 'Bộ', 1.000, 5, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 1, 1, 0),
(110, 37, 41, 6, 'HT01.6', 'Xe đẩy chuyên dụng', 'Có bánh xe khóa được.\nNgăn đựng đầu dò và phụ kiện.', NULL, 'Cái', 1.000, 6, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 1, 1, 0),
(111, 37, 42, 1, 'HT02.1', 'Nguồn sáng LED', 'Tuổi thọ ≥ 20.000 giờ.\nĐiều chỉnh cường độ sáng theo cấp độ.', NULL, 'Cái', 1.000, 7, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 1, 1, 0),
(112, 37, 42, 2, 'HT02.2', 'Bộ xử lý hình ảnh', 'Độ phân giải Full HD trở lên.\nCó chức năng nhuộm màu ảo.', NULL, 'Cái', 1.000, 8, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 1, 1, 0),
(113, 37, 42, 3, 'HT02.3', 'Dây soi dạ dày', 'Đường kính ngoài ≤ 9,8mm.\nKênh thủ thuật ≥ 2,8mm.\nGập 4 hướng.', NULL, 'Cái', 1.000, 9, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 1, 1, 0),
(114, 37, 42, 4, 'HT02.4', 'Dây soi đại tràng', 'Đường kính ngoài ≤ 13mm.\nChiều dài làm việc ≥ 1.300mm.', NULL, 'Cái', 1.000, 10, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 1, 1, 0),
(115, 37, 42, 5, 'HT02.5', 'Màn hình y tế', 'Kích thước ≥ 26 inch.\nĐạt chuẩn hiển thị dùng trong y tế.', NULL, 'Cái', 1.000, 11, '2026-09-10 20:26:31', '2026-09-10 20:26:31', 1, 1, 0),
(164, 49, NULL, NULL, 'VT001', 'Bơm tiêm 10ml', 'Thể tích 10ml, có vạch chia thể tích. \nVỏ bằng PVC.\n Màu trắng', 'Việt Nam', 'Cái', 2000.000, 1, '2026-09-11 08:43:00', '2026-09-11 08:43:00', 1, 1, 0),
(165, 49, 61, 1, 'VT002.1', 'Dây dẫn đường', 'Chiều dài 3m, chất liệu PVC', NULL, 'Cái', 2.000, 2, '2026-09-11 08:43:00', '2026-09-11 08:43:00', 1, 1, 0),
(166, 49, 61, 2, 'VT002.2', 'Máy tạo nhịp tim', 'Máy tạo nhịp yêu cầu họatj động trong môi trường có nhiệt độ từ 20-40 độ C. \nI15Có đáp ứng tần số (Rate Response hoặc Rate Modulated hoặc Rate Adaptation)\nKết nối IS4/ IS1.\nTương thích hoặc cho phép chụp được cộng hưởng từ.', NULL, 'Cái', 2.000, 3, '2026-09-11 08:43:00', '2026-09-11 08:43:00', 1, 1, 0),
(167, 49, 61, 3, 'VT002.3', 'Điện cực tạo nhịp', 'Chất liệu ABC.\nMàu trắng.', NULL, 'Cái', 4.000, 4, '2026-09-11 08:43:00', '2026-09-11 08:43:00', 1, 1, 0),
(168, 51, 62, 1, 'HH001', 'Màn hình', 'Màn hình\nCấu hình:\n- Màn hình: 01 cái\n- Cáp nguồn: 01 cái\n- Adapter: 01 cái\n- Cáp tín hiệu DVI: 01 cái\n- Cáp tín hiệu HDMI: 01 cái\n- Cáp tín hiệu DP: 01 cái\n- Cáp tín hiệu BNC: 01 cái\n- Chân màn hình: 01 cái\nThông số kỹ thuật:\n- Kích thước màn hình:  ≥ 24 inch\n- Tỷ lệ hiển thị / tỷ lệ khung hình: 16:10\n- Màu sắc:  ≥ 1 triệu màu\n- Độ sáng:  ≥ 200 cd/m2\n- Tỷ lệ tương phản:  ≥ 800:1\n- Góc nhìn: ≥ 170°/170°\n- Trọng lượng:  ≤ 9kg (Không bao gồm chân màn hình)\n- Cổng tính hiệu đầu vào:\n+ DVI connector (x1),\n+ SDI connector(x1),\n+ S-Video connector(x2),\n+ RGB/ YPbPr connector(x3).\nỨng dụng: Hệ thống nội soi kỹ thuật số.\n- Loại đèn nền: LED\n- Nguồn điện sử dụng: 24V', NULL, 'Cái', 2.000, 1, '2026-09-11 09:44:43', '2026-09-11 09:44:43', 1, 1, 0),
(169, 51, 62, 2, 'HH002', 'Bộ xử lý hình ảnh camera Full HD', 'Cấu hình\n- Bộ xử lý hình ảnh: 01 cái\n- Đầu camera: 01 cái\n- Cáp nguồn: 01 cái\n- Cáp tín hiệu DVI-D: 01 cái\n- Cáp tín hiệu HDMI: 01 cái\n- Cầu chì dự phòng: 02 cái\nTính năng\n- Áp dụng cho các chuyên khoa: Phẫu thuật tiết niệu, phẫu thuật tổng quát\n- Tái tạo hình ảnh HD\n- Hỗ trợ chụp và lưu trữ hình ảnh.\nThông số kỹ thuật\n- Màn hình cảm ứng\n- Cảm biến hình ảnh: Cảm biến CMOS \n- Cổng kết nối USB:  ≥ 2.0 x 2\n- Nguồn điện: AC100- 240V 50Hz \n- Tín hiệu đầu ra: HDMI, 3G-SDI\n- Kích thước:  ≥ 360 x 300 x 85 mm\n- Công suất đầu vào:  ≥ 45VA\n- Đầu camera có 2 nút chức năng, dùng để dừng hình, ghi hình và cân bằng trắng.\n- Chiều dài dây cáp đầu camera:  ≥ 2.8m', NULL, 'Cái', 2.000, 2, '2026-09-11 09:44:43', '2026-09-11 09:44:43', 1, 1, 0),
(170, 51, 62, 3, 'HH003', 'Nguồn sáng lạnh', 'Cấu hình\n- Nguồn sáng lạnh LED: 01 cái\n- Cáp nguồn: 01 cái\n- Dây dẫn sáng: 01 cái\n- Cầu chì dự phòng: 02 cái\nTính năng\n- Màn hình: ≥ 5 inch\n- Chỉ số hoàn màu cao\nThông số kỹ thuật\n- Độ sáng: ≥ 700lm\n- Nhiệt độ màu: ≥ 5700K\n- Tuổi thọ của bóng LED: ≥ 20000 giờ\n33\n- Công suất tiêu thụ: ≥100VA\n- Kích thước sản phẩm: ≥ 360 x 300 x 85 mm', NULL, 'Cái', 2.000, 3, '2026-09-11 09:44:43', '2026-09-11 09:44:43', 1, 1, 0),
(171, 51, 62, 4, 'HH004', 'Hệ thống xe đẩy nội soi', '- Xe đẩy chuyên dụng đi kèm hệ thống nội soi, có cánh tay màn hình, bánh xe có khóa, có ổ cắm điện.', NULL, 'Cái', 2.000, 4, '2026-09-11 09:44:43', '2026-09-11 09:44:43', 1, 1, 0),
(172, 52, 63, 1, 'HH001', 'Khớp nối cố định thanh đỡ hệ thống vén não với ray bên bàn mổ', 'Chủng loại: Khớp nối cố định thanh đỡ hệ thống vén não với ray bên bàn mổ\nChất liệu: Thép không gỉ\nHình dáng: Dạng khớp cầu, xoay điều chỉnh góc được trước khi khóa cố định\nKích thước: Tương thích thanh ray bàn mổ rộng tối thiểu 10×25mm\nĐặc điểm khác: Dùng kèm thanh/trục giữ dụng cụ tương ứng trong hệ thống;', NULL, 'Cái', 2.000, 1, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(173, 52, 63, 2, 'HH002', 'Thanh đỡ hệ thống vén não', 'Chủng loại: Thanh đỡ hệ thống vén não\nChất liệu: Thép không gỉ\nHình dáng: dạng thanh/trục thẳng\nĐặc điểm khác: Tương thích với khớp nối cố định và đầu nối tay đỡ mềm của hệ thống vén não', NULL, 'Cái', 2.000, 2, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(174, 52, 63, 3, 'HH003', 'Đầu nối tay đỡ mềm hệ thống vén não', 'Chủng loại: Đầu nối tay đỡ mềm hệ thống vén não\nChất liệu: Thép không gỉ\nHình dáng: Đầu nối tự giữ vị trí khi điều chỉnh góc\nĐặc điểm khác: Kết nối đồng thời tối thiểu từ 1–5 tay giữ dụng cụ hệ thống vén não.', NULL, 'Cái', 4.000, 3, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(175, 52, 63, 4, 'HH004', 'Tay đỡ dụng cụ hệ thống vén não', 'Chủng loại: Tay đỡ dụng cụ hệ thống vén não\nChất liệu: Thép không gỉ\nHình dáng: Dạng tay đòn linh hoạt, có khả năng uốn chỉnh\nĐặc điểm khác: Tương thích với đầu nối tay đỡ mềm hệ thống vén não', NULL, 'Cái', 4.000, 4, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(176, 52, 63, 5, 'HH005', 'Giá đỡ dụng cụ vén não', 'Chủng loại: Giá đỡ dụng cụ vén não\nChất liệu: Thép không gỉ\nHình dáng: Dạng khung kẹp, khớp với trục tròn của vén não\nKích thước: Giữ được vén não có trục tròn đường kính tối đa 5.5mm\nĐặc điểm khác: Lắp vào tay đỡ dụng cụ hệ thống vén não.', NULL, 'Cái', 4.000, 5, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(177, 52, 63, 6, 'HH006', 'Vén não Heifetz uốn định hình, trục tròn, loại nhỏ', 'Chủng loại: Vén não Heifetz\nChất liệu: Thép không gỉ\nHình dáng: đầu lưới dạng dẹt, thân trục tròn\nKích thước: Chiều dài tổng thể từ 150mm đến 160mm, độ rộng lưỡi từ 7.7mm đến 8.3mm\nĐặc điểm khác: có thể uốn định hình;', NULL, 'Cái', 4.000, 6, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(178, 52, 63, 7, 'HH007', 'Vén não Heifetz uốn định hình, trục tròn, loại lớn', 'Chủng loại: Vén não Heifetz\nChất liệu: Thép không gỉ\nHình dáng: đầu lưỡi dạng dẹt, thân trục tròn\nKích thước: Chiều dài tổng thể từ 195mm đến 205mm; chiều dài hoạt động từ 95mm đến 100mm; đường kính trục từ 4.8mm đến 5.5mm; độ rộng lưỡi từ 13.5mm đến 14.5mm\nĐặc điểm khác: có thể uốn định hình;', NULL, 'Cái', 4.000, 7, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(179, 52, 63, 8, 'HH008', 'Vén não uốn định hình hai đầu, loại nhỏ', 'Vén não uốn định hình hai đầu\nChất liệu: Thép không gỉ\nHình dáng: Thiết kế bản dẹt thuôn/nón, hai đầu thao tác với kích thước khác nhau.\nKích thước: Chiều dài tổng thế từ 195mm đến 205mm; bề rộng hai đầu vén dao động trong khoảng 7.5mm -8.5mm (đầu lớn) và 3.5mm đến 4.5mm (đầu nhỏ)\nĐặc điểm khác: Có thể uốn định hình; bề mặt được xử lý bằng lớp phủ tối màu/mờ giúp tăng độ cứng và chống chói lóa.', NULL, 'Cái', 2.000, 8, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(180, 52, 63, 9, 'HH009', 'Vén não uốn định hình hai đầu, loại vừa', 'Chủng loại: Vén não uốn định hình hai đầu\nChất liệu: Thép không gỉ\nHình dáng: Thiết kế bản dẹt thuôn/nón, hai đầu thao tác với kích thước khác nhau.\nKích thước: Chiều dài tổng thể từ 195mm đến 205mm; bề rộng hai đầu vén từ 12.5mm đến 13.5mm (đầu lớn) và từ 5.5mm đến 6.5mm (đầu nhỏ).\nĐặc điểm khác: Có thể uốn định hình; bề mặt được xử lý bằng lớp phủ tối màu/mờ giúp tăng độ cứng và chống chói lóa.', NULL, 'Cái', 2.000, 9, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(181, 52, 63, 10, 'HH010', 'Vén não uốn định hình hai đầu, loại lớn', 'Chủng loại: Vén não uốn định hình hai đầu\nChất liệu: Thép không gỉ\nHình dáng: Thiết kế bản dẹt thuôn/nón, hai đầu thao tác với kích thước khác nhau.\nKích thước: Chiều dài tổng thể từ 195mm đến 205mm; bề rộng hai đầu vén từ 16.5mm đến 17.5mm (đầu lớn) và từ 6.5mm đến 9.5mm (đầu nhỏ).\nĐặc điểm khác: Có thể uốn định hình; bề mặt được xử lý bằng lớp phủ tối màu/mờ giúp tăng độ cứng và chống chói lóa.', NULL, 'Cái', 2.000, 10, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(182, 52, 63, 11, 'HH011', 'Vén não uốn định hình hai đầu, bản dẹt', 'Chủng loại: Vén não uốn định hình hai đầu\nChất liệu: Thép không gỉ\nHình dáng: Thiết kế bản dẹt\nKích thước: Chiều dài tổng thể từ 195mm đến 205mm; bề rộng lưỡi từ 7.5mm đến 9.5mm.\nĐặc điểm khác: Có thể uốn định hình', NULL, 'Cái', 4.000, 11, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(183, 52, 63, 12, 'HH012', 'Dây cưa xương phẫu thuật kiểu Gigli', 'Chủng loại: Dây cưa xương phẫu thuật kiểu Gigli\nChất liệu: Thép không gỉ\nHình dạng: Dạng dây cáp xoắn, hai đầu có khuyên tròn\nKích thước: Chiều dài tổng thể từ 390 mm đến 410mm\nĐặc điểm khác: Dòng sợi mảnh', NULL, 'Cái', 4.000, 12, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(184, 52, 63, 13, 'HH013', 'Cán kéo dây cưa Gigli có móc giữ', 'Chủng loại: Cán/tay cầm dùng cho dây cưa Gigli\nChất liệu: Thép không gỉ\nHình dáng: Thiết kế cán cầm ngang dạng chữ T, có móc giữ.\nKích thước: Chiều dài tổng thể từ 65mm đến 75mm.', NULL, 'Cái', 4.000, 13, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(185, 52, 63, 14, 'HH014', 'Kéo vi phẫu Yasargil thẳng, thân dạng lưỡi lê, loại ngắn', 'Chủng loại: Kéo vi phẫu Yasargil\nChất liệu: Thép không gỉ\nHình dáng: Thân kéo gập góc dạng lưỡi lê, lưỡi cắt thẳng\nKích thước: Chiều dài tổng thể từ 195mm đến 205mm;', NULL, 'Cái', 2.000, 14, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(186, 52, 63, 15, 'HH015', 'Kéo vi phẫu Yasargil thẳng, thân dạng lưỡi lê, loại dài', 'Chủng loại: Kéo vi phẫu Yasargil\nChất liệu: Thép không gỉ\nHình dáng: Thân kéo gập góc dạng lưỡi lê, lưỡi cắt thẳng\nKích thước: Chiều dài tổng thể từ 220mm đến 230mm;', NULL, 'Cái', 2.000, 15, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(187, 52, 63, 16, 'HH016', 'Kẹp gắp bông gạc Forster-Ballenger thẳng, ngàm có khía', 'Kẹp gắp bông gạc kiểu Forster-Ballenger\nChất liệu: Thép không gỉ\nHình dáng: Dạng kìm thẳng, có khía\nKích thước: Chiều dài tổng thể từ 240 đến 255 mm', NULL, 'Cái', 2.000, 16, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(188, 52, 63, 17, 'HH017', 'Kẹp khăn mổ Backhaus', 'Chủng loại: Kẹp khăn mổ kiểu Backhaus\nChất liệu: Thép không gỉ\nHình dáng: Dạng kìm cong, đầu nhọn\nKích thước: Chiều dài tổng thể từ 105mm đến 115mm.', NULL, 'Cái', 16.000, 17, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(189, 52, 63, 18, 'HH018', 'Cán dao phẫu thuật số 3', 'Chủng loại: Cán dao phẫu thuật số 3\nChất liệu: Thép không gỉ\nHình dáng: Dạng thân dẹt, ngàm gắn lưỡi dao chuẩn số 3\nKích thước: Chiều dài tổng thể từ 115 mm đến 130mm\nĐặc điểm khác: Loại tiêu chuẩn', NULL, 'Cái', 2.000, 18, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(190, 52, 63, 19, 'HH019', 'Cán dao phẫu thuật số 4', 'Chủng loại: Cán dao phẫu thuật số 4\nChất liệu: Thép không gỉ\nHình dáng: Dạng thân dẹt, ngàm gắn lưỡi dao chuẩn số 4\nKích thước: Chiều dài tổng thể từ 130 mm đến 140mm\nĐặc điểm khác: Loại tiêu chuẩn', NULL, 'Cái', 2.000, 19, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(191, 52, 63, 20, 'HH020', 'Cán dao phẫu thuật số 3L,', 'Chủng loại: Cán dao phẫu thuật số 3L\nChất liệu: Thép không gỉ\nHình dáng: Dạng thân thẳng, ngàm gắn lưỡi dao chuẩn số 3\nKích thước: Chiều dài tổng thể từ 205mm đến 215mm\nĐặc điểm khác: Gấp góc hình lưỡi lê', NULL, 'Cái', 2.000, 20, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(192, 52, 63, 21, 'HH021', 'Kéo phẫu tích Metzenbaum, loại ngắn', 'Chủng loại: Kéo phẫu tích kiểu Metzenbaum\nChất liệu: Thép không gỉ,\nHình dáng: Dạng cong, hai chuôi kéo được mạ vàng\nKích thước: Chiều dài tổng thể từ 175mm đến 185mm.\nĐặc điểm khác: Đầu mũi kéo thiết kế kiểu tù/tù', NULL, 'Cái', 2.000, 21, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(193, 52, 63, 22, 'HH022', 'Kéo phẫu tích Metzenbaum, loại dài', 'Chủng loại: Kéo phẫu tích kiểu Metzenbaum\nChất liệu: Thép không gỉ,\nHình dáng: Dạng cong, hai chuôi kéo được mạ vàng\nKích thước: Chiều dài tổng thể từ 195mm đến 205mm.\nĐặc điểm khác: Đầu mũi kéo thiết kế kiểu tù/tù', NULL, 'Cái', 2.000, 22, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(194, 52, 63, 23, 'HH023', 'Kéo phẫu tích Tonnis-Adson', 'Chủng loại: Kéo phẫu tích kiểu Tonnis-Adson\nChất liệu: Thép không gỉ,\nHình dáng: Dạng cong, thiết kế lưỡi kéo dạng thanh mảnh, hai chuôi kéo được mạ vàng\nKích thước: Chiều dài tổng thể từ 170mm đến 180mm.\nĐặc điểm khác: Đầu mũi kéo thiết kế kiểu tù/tù', NULL, 'Cái', 2.000, 23, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(195, 52, 63, 24, 'HH024', 'Kéo phẫu thuật Schmieden-Taylor dạng gập góc', 'Chủng loại: Kéo phẫu thuật kiểu Schmieden-Taylor.\nChất liệu: Thép không gỉ,\nHình dáng: Thân kéo uốn cong gập góc, một lưỡi cắt có thiết kế đầu dò dạng bi tròn\nKích thước: Chiều dài tổng thể từ 150mm đến 175mm.', NULL, 'Cái', 2.000, 24, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(196, 52, 63, 25, 'HH025', 'Kéo phẫu thuật thẳng, đầu tù/nhọn', 'Chủng loại: Kéo phẫu thuật thẳng, đầu tù/nhọn\nChất liệu: Thép không gỉ\nHình dáng: lưỡi cắt thẳng; đầu mũi kéo thiết kế một đầu nhọn, một đầu tù, thiết kế tiêu chuẩn\nKích thước: Chiều dài tổng thể từ 135mm đến 150mm.', NULL, 'Cái', 2.000, 25, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(197, 52, 63, 26, 'HH026', 'Kéo cắt mô Mayo cong', 'Chủng loại: Kéo cắt mô Mayo\nChất liệu: Thép không gỉ,\nHình dáng: lưỡi cắt uốn cong; đầu mũi kéo thiết kế kiểu hai đầu tù\nKích thước: Chiều dài tổng thể từ 165mm đến 175mm.', NULL, 'Cái', 2.000, 26, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(198, 52, 63, 27, 'HH027', 'Kẹp phẫu tích thẳng, không mấu', 'Chủng loại: Kẹp phẫu tích không mấu\nChất liệu: Thép không gỉ,\nHình dáng: Dạng kẹp thẳng, bản tiêu chuẩn, thân kẹp có khía nhám chống trượt, ngàm có khía\nKích thước: Chiều dài tổng thể từ 195mm đến 205mm.', NULL, 'Cái', 4.000, 27, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(199, 52, 63, 28, 'HH028', 'Kẹp mô Waugh thẳng, ngàm răng (1x2)', 'Chủng loại: Kẹp mô, thiết kế kiểu Waugh\nChất liệu: Thép không gỉ,\nHình dáng: Dạng kẹp thẳng, thiết kế thanh mảnh\nKích thước: Chiều dài tổng thể từ 175mm đến 185mm.\nĐặc điểm khác: Đầu kẹp có mấu kiểu 1x2 răng', NULL, 'Cái', 2.000, 28, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(200, 52, 63, 29, 'HH029', 'Kẹp mô Gillies thẳng, ngàm răng (1x2)', 'Chủng loại: Kẹp mô, kiểu GILLIES\nChất liệu: Thép không gỉ\nHình dáng: Dạng kẹp thẳng, thiết kế thanh mảnh, có chốt/chân định vị mặt trong\nKích thước: Chiều dài tổng thể từ 145mm đến 160mm.\nĐặc điểm khác: Đầu kẹp có mấu kiểu 1x2 răng', NULL, 'Cái', 2.000, 29, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(201, 52, 63, 30, 'HH030', 'Kẹp phẫu tích thẳng, không mấu, đầu phủ hợp kim cứng (TC)', 'Chủng loại: Kẹp phẫu tích không mấu\nChất liệu: Thép không gỉ, phần đầu/ngàm kẹp được hàn/tích hợp hợp kim cứng Tungsten Carbide (TC)\nHình dáng: Dạng kẹp thẳng, thanh mảnh; phần chuôi kẹp mạ vàng; thân kẹp có khía.\nKích thước: Chiều dài tổng thể từ 175mm đến 185mm.\nĐặc điểm khác: Mặt trong của đầu ngàm kẹp tạo nhám dạng chéo caro/ô vuông;', NULL, 'Cái', 2.000, 30, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(202, 52, 63, 31, 'HH031', 'Kẹp phẫu tích không mấu, thân dạng lưỡi lê', 'Chủng loại: Kẹp phẫu tích không mấu, kiểu Gruenwald (hoặc Grünwald-Jansen).\nChất liệu: Thép không gỉ\nHình dáng: Thân kẹp gập góc dạng lưỡi lê; đầu kẹp thẳng.\nKích thước: Chiều dài tổng thể từ 195mm đến 205mm.\nĐặc điểm khác: Ngàm có các rãnh/khía ngang', NULL, 'Cái', 2.000, 31, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(203, 52, 63, 32, 'HH032', 'Kẹp mô Gerald, không mấu,', 'Chủng loại: Kẹp phẫu tích không mấu, kiểu GERALD.\nChất liệu: Thép không gỉ\nHình dáng: Dạng kẹp thẳng, thiết kế thanh mảnh; phần cán có khía.\nKích thước: Chiều dài tổng thể từ 170mm đến 185mm.\nĐặc điểm khác: Mặt trong của mũi kẹp có các rãnh/khía ngang', NULL, 'Cái', 2.000, 32, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(204, 52, 63, 33, 'HH033', 'Kẹp cầm máu Halsted-Mosquito cong', 'Chủng loại: Kẹp cầm máu, kiểu HALSTED-MOSQUITO.\nChất liệu: Thép không gỉ.\nHình dáng: Dạng kìm cong, thiết kế thanh mảnh ; có khóa hãm ở cán cầm.\nKích thước: Chiều dài tổng thể từ 120mm đến 130mm.\nĐặc điểm khác: Ngàm kẹp không có mấu, mặt trong ngàm có các rãnh/khía;', NULL, 'Cái', 16.000, 33, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(205, 52, 63, 34, 'HH034', 'Kẹp cầm máu da đầu Dandy cong sang bên, đầu tù', 'Chủng loại: Kẹp cầm máu, kiểu DANDY.\nChất liệu: Thép không gỉ.\nHình dáng: Dạng kìm, ngàm kẹp uốn cong gập sang bên;\nKích thước: Chiều dài tổng thể từ 135mm đến 145mm.\nĐặc điểm khác: Ngàm kẹp có các rãnh/khía ngang; loại tái sử dụng nhiều lần.', NULL, 'Cái', 20.000, 34, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(206, 52, 63, 35, 'HH035', 'Kẹp cầm máu Ochsner-Kocher cong, ngàm răng 1x2', 'Chủng loại: Kẹp cầm máu, kiểu KOCHER-OCHSNER.\nChất liệu: Thép không gỉ\nHình dáng: Dạng kìm cong;\nKích thước: Chiều dài tổng thể từ 195mm đến 205mm.\nĐặc điểm khác: Đầu mũi kẹp có mấu nhọn kiểu răng (1x2); mặt trong ngàm kẹp có khía', NULL, 'Cái', 4.000, 35, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(207, 52, 63, 36, 'HH036', 'Kẹp vi phẫu/khối u Yasargil, ngàm nhỏ', 'Chủng loại: Kẹp vi phẫu/Kẹp khối u, kiểu YASARGIL.\nChất liệu: Thép không gỉ\nHình dáng: Thân kẹp gập góc dạng lưỡi lê, mũi kẹp thẳng;\nKích thước: Chiều dài tổng thể từ 215mm đến 225mm. Đường kính ngoài của ngàm kẹp từ 2.5mm đến 3.5mm.\nĐặc điểm khác: Đầu kẹp thiết kế dạng khuyên tròn/hình thìa', NULL, 'Cái', 2.000, 36, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(208, 52, 63, 37, 'HH037', 'Kẹp vi phẫu/khối u Yasargil ngàm lớn', 'Chủng loại: Kẹp vi phẫu/Kẹp khối u, kiểu YASARGIL.\nChất liệu: Thép không gỉ\nHình dáng: Thân kẹp gập góc dạng lưỡi lê, mũi kẹp thẳng;\nKích thước: Chiều dài tổng thể từ 215mm đến 225mm. Đường kính ngoài của ngàm kẹp từ 4.5mm đến 5.5mm.\nĐặc điểm khác: Đầu kẹp thiết kế dạng khuyên tròn/hình thìa.', NULL, 'Cái', 2.000, 37, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(209, 52, 63, 38, 'HH038', 'Dụng cụ tháo/lắp kẹp da đầu Raney', 'Chủng loại: Kìm lắp và tháo kẹp da đầu, kiểu RANEY.\nChất liệu: Thép không gỉ\nHình dáng: Dạng kìm thẳng; có cơ cấu khóa hãm.\nKích thước: Chiều dài tổng thể từ 155mm đến 165mm.', NULL, 'Cái', 4.000, 38, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(210, 52, 63, 39, 'HH039', 'Kẹp cầm máu da đầu Raney', 'Chủng loại: Kẹp cầm máu da đầu, kiểu RANEY.\nChất liệu: Nhựa y tế hoặc thép không gỉ\nHình dáng: Dạng kẹp chữ U uốn lượn, tương thích với dụng cụ đặt/tháo kẹp cầm máu da đầu kiểu Raney.', NULL, 'Cái', 40.000, 39, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(211, 52, 63, 40, 'HH040', 'Que thăm dò Jacobson gập góc, đầu tròn', 'Chủng loại: Que thăm dò vi phẫu kiểu JACOBSON.\nChất liệu: Thép không gỉ\nHình dáng: Dạng que thẳng, phần đầu bẻ gập góc; cán cầm dạng trụ tròn có khía nhám.\nKích thước: Chiều dài tổng thể từ 180mm đến 190mm.\nĐặc điểm khác: Đầu dạng hạt tròn.', NULL, 'Cái', 2.000, 40, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(212, 52, 63, 41, 'HH041', 'Móc vén vi phẫu Fisch gập góc, đầu nhọn', 'Chủng loại: Móc vi phẫu / Móc vén màng cứng, kiểu FISCH.\nChất liệu: Thép không gỉ\nHình dáng: Cán cầm trụ tròn có khía nhám; phần đầu gập góc 90°.\nKích thước: Chiều dài tổng thể từ 180mm đến 190mm.', NULL, 'Cái', 4.000, 41, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(213, 52, 63, 42, 'HH042', 'Dụng cụ phẫu tích Davis, hai đầu', 'Chủng loại: Dụng cụ phẫu tích kiểu DAVIS.\nChất liệu: Thép không gỉ.\nHình dáng: Thiết kế 2 đầu làm việc; phần đầu uốn cong nhẹ; cán cầm dạng trụ tròn có khía nhám.\nKích thước: Chiều dài tổng thể từ 240mm đến 250mm.', NULL, 'Cái', 2.000, 42, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(214, 52, 63, 43, 'HH043', 'Dụng cụ bóc tách Freer, hai đầu', 'Chủng loại: Cây bóc tách màng cứng, kiểu FREER.\nChất liệu: Thép không gỉ.\nHình dáng: Thiết kế 2 đầu làm việc, dạng đầu cong; một đầu nhọn và một đầu tù; cán cầm có khía nhám.\nKích thước: Chiều dài tổng thể từ 175mm đến 190mm. Bề rộng đầu từ 3.5mm đến 5.5mm.', NULL, 'Cái', 2.000, 43, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(215, 52, 63, 44, 'HH044', 'Thìa nạo xương Williger', 'Chủng loại: Thìa nạo xương, kiểu WILLIGER.\nChất liệu: Thép không gỉ\nHình dáng: Dạng thẳng; phần đầu làm việc hình thìa múc.\nKích thước: Chiều dài tổng thể từ 170mm đến 180mm. Bề rộng đầu từ 4.8mm đến 5.6mm.\nĐặc điểm khác: Loại tái sử dụng nhiều lần, chưa tiệt trùng.', NULL, 'Cái', 2.000, 44, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(216, 52, 63, 45, 'HH045', 'Dụng cụ bóc tách màng xương Adson, đầu lưỡi vuông', 'Chủng loại: Dụng cụ bóc tách màng xương kiểu ADSON.\nChất liệu: Thép không gỉ\nHình dáng: Dạng thẳng; thiết kế 1 đầu làm việc\nKích thước: Chiều dài tổng thể từ 165mm đến 175mm. Bề rộng đầu bóc tách từ 7.5mm đến 8.5mm.', NULL, 'Cái', 2.000, 45, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(217, 52, 63, 46, 'HH046', 'Dụng cụ bóc tách màng xương Adson, đầu lưỡi tròn', 'Chủng loại: Dụng cụ bóc tách màng xương kiểu ADSON.\nChất liệu: Thép không gỉ\nHình dáng: Dạng thẳng; thiết kế 1 đầu làm việc, phần đầu cong tròn\nKích thước: Chiều dài tổng thể từ 165mm đến 175mm. Bề rộng đầu bóc tách từ 6.5mm đến 7.5mm.', NULL, 'Cái', 2.000, 46, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(218, 52, 63, 47, 'HH047', 'Dụng cụ bóc tách/bẩy xương Langenbeck, đầu tù, bản hẹp', 'Chủng loại: Cây bóc tách / bẩy xương, kiểu LANGENBECK.\nChất liệu: Thép không gỉ\nHình dáng: Dạng cong, đầu tù\nKích thước: Chiều dài tổng thể từ 190mm đến 200mm. Bề rộng đầu làm việc từ 7.0mm đến 8.5mm.', NULL, 'Cái', 2.000, 47, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(219, 52, 63, 48, 'HH048', 'Dụng cụ bóc tách/ bẩy xương Langenbeck, đầu tù, bản rộng', 'Chủng loại: Cây bóc tách / bẩy xương, kiểu LANGENBECK.\nChất liệu: Thép không gỉ\nHình dáng: Cán liền khối dạng vòng khuyên, dạng cong, đầu tù\nKích thước: Chiều dài tổng thể từ 225mm đến 235mm. Bề rộng đầu làm việc từ 10.5mm đến 11.5mm.', NULL, 'Cái', 2.000, 48, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(220, 52, 63, 49, 'HH049', 'Đục xương thẳng Stille, lưỡi vát một bên', 'Chủng loại: Đục xương, kiểu STILLE.\nChất liệu: Thép không gỉ\nHình dáng: Dạng đục thẳng; phần lưỡi đục vát một bên.\nKích thước: Chiều dài tổng thể từ 200mm đến 210mm. Bề rộng lưỡi đục từ 9.5mm đến 10.5mm.', NULL, 'Cái', 2.000, 49, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(221, 52, 63, 50, 'HH050', 'Kìm gặm xương cong', 'Chủng loại: Kìm gặm xương, kiểu LUER / RUSKIN.\nChất liệu: Thép không gỉ\nHình dáng: Dạng kìm cong;\nKích thước: Chiều dài tổng thể từ 175mm đến 185mm. Bề rộng đầu ngàm gặm từ 4.0mm đến 5.5mm.', NULL, 'Cái', 4.000, 50, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(222, 52, 63, 51, 'HH051', 'Kìm gặm xương cong Olivecrona', 'Chủng loại: Kìm gặm xương, kiểu OLIVECRONA.\nChất liệu: Thép không gỉ\nHình dáng: Dạng kìm cong; đầu gặm dạng thìa múc xương.\nKích thước: Chiều dài tổng thể từ 195mm đến 210mm. Bề rộng ngàm gặm từ 5.5mm đến 6.5mm.', NULL, 'Cái', 2.000, 51, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(223, 52, 63, 52, 'HH052', 'Tay khoan sọ não Hudson', 'Chủng loại: Tay khoan kiểu HUDSON.\nChất liệu: Thép không gỉ\nHình dáng: Thiết kế tay quay hình chữ U.\nĐặc điểm khác: Phần đầu tương thích với các mũi khoan kiểu Hudson;', NULL, 'Cái', 2.000, 52, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(224, 52, 63, 53, 'HH053', 'Đầu nối Tay khoan Hudson', 'Chủng loại: Đầu nối / Cây nối dài tay quay khoan sọ, kiểu HUDSON.\nChất liệu: Thép không gỉ\nHình dáng: Thiết kế dạng trục thẳng;\nĐặc điểm khác: tương thích để lắp ghép vào cán quay khoan tay kiểu Hudson', NULL, 'Cái', 2.000, 53, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(225, 52, 63, 54, 'HH054', 'Mũi khoan Hudson, loại nhỏ', 'Chủng loại: Mũi khoan sọ nã, kiểu HUDSON.\nChất liệu: Thép không gỉ\nKích thước: Đường kính đầu mũi khoan từ 8.5mm đến 9.5mm.\nĐặc điểm khác: Tương thích với cán khoan sọ tay quay;', NULL, 'Cái', 2.000, 54, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(226, 52, 63, 55, 'HH055', 'Mũi khoan Hudson, loại vừa', 'Chủng loại: Mũi khoan sọ não, kiểu HUDSON.\nChất liệu: Thép không gỉ\nHình dáng: Dạng mũi khoan sọ kiểu chóp/nón;\nKích thước: Đường kính đầu mũi khoan từ 13.5mm đến 14.5mm.\nĐặc điểm khác: Tương thích với cán khoan sọ tay quay kiểu Hudson;', NULL, 'Cái', 2.000, 55, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(227, 52, 63, 56, 'HH056', 'Mũi khoan Hudson, loại lớn', 'Chủng loại: Mũi khoan sọ não, kiểu HUDSON.\nChất liệu: Thép không gỉ\nHình dáng: Dạng mũi khoan sọ não hình cầu;\nKích thước: Đường kính đầu mũi khoan từ 15.5mm đến 16.5mm.\nĐặc điểm khác: Tương thích với cán khoan sọ tay quay kiểu Hudson;', NULL, 'Cái', 2.000, 56, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(228, 52, 63, 57, 'HH057', 'Mũi khoan Hudson, loại cực lớn', 'Chủng loại: Mũi khoan sọ não, kiểu HUDSON.\nChất liệu: Thép không gỉ\nHình dáng: Dạng mũi khoan sọ não hình cầu;\nKích thước: Đường kính đầu mũi khoan từ 21.5mm đến 22.5mm.\nĐặc điểm khác: Tương thích với cán khoan sọ tay quay kiểu Hudson;', NULL, 'Cái', 2.000, 57, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(229, 52, 63, 58, 'HH058', 'Mũi khoan sọ tự dừng, loại nhỏ', 'Chủng loại: Mũi khoan sọ tự dừng,\nChất liệu: Thép không gỉ\nKích thước: đầu mũi 7/11 mm', NULL, 'Cái', 2.000, 58, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(230, 52, 63, 59, 'HH059', 'Mũi khoan sọ tự dừng, loại vừa', 'Chủng loại: Mũi khoan sọ tự dừng,\nChất liệu: Thép không gỉ\nKích thước: đầu mũi 11/14 mm', NULL, 'Cái', 2.000, 59, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(231, 52, 63, 60, 'HH060', 'Cây dẫn dây cưa De Martel', 'Chủng loại: Cây dẫn cưa dây kiểu DE MARTEL.\nHình dáng: Dạng thanh dẹt, mảnh;\nKích thước: Chiều dài tổng thể từ 325mm đến 355mm.', NULL, 'Cái', 2.000, 60, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(232, 52, 63, 61, 'HH061', 'Banh tự giữ Mollison gập góc, răng nhọn', 'Chủng loại: Banh tự giữ kiểu MOLLISON\nChất liệu: Thép không gỉ.\nHình dáng: Dạng banh có cơ cấu khóa tự giữ; hai nhánh vén được cấu tạo kiểu 4 x 4 răng, dạng răng nhọn.\nKích thước: Chiều dài tổng thể từ 145mm đến 160mm.', NULL, 'Cái', 2.000, 61, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(233, 52, 63, 62, 'HH062', 'Banh tự giữ Anderson-Adson, răng nhọn', 'Chủng loại: Banh vén vết thương tự giữ, kiểu ANDERSON-ADSON.\nChất liệu: Thép không gỉ\nHình dáng: Dạng banh vén có cơ cấu tự giữ; cấu tạo ngàm vén kiểu 4 x 4 răng, dạng răng sắc nhọn.\nKích thước: Chiều dài tổng thể từ 185mm đến 205mm.', NULL, 'Cái', 2.000, 62, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(234, 52, 63, 63, 'HH063', 'Dụng cụ móc màng não Frazier đầu nhọn', 'Chủng loại: Móc vén màng não, kiểu FRAZIER.\nChất liệu: Thép không gỉ.\nHình dáng: Dụng cụ cán thẳng; đầu làm việc dạng 1 móc, thiết kế mũi nhọn/sắc.\nKích thước: Chiều dài tổng thể từ 125mm đến 135mm.', NULL, 'Cái', 4.000, 63, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(235, 52, 63, 64, 'HH064', 'Móc vén mạch máu và rễ thần kinh Crile', 'Chủng loại: Móc vén / Banh vén, kiểu CRILE.\nChất liệu: Thép không gỉ\nHình dáng: Dụng cụ cán thẳng; đầu làm việc dạng 1 móc gập góc, đầu tù.\nKích thước: Chiều dài tổng thể từ 195mm đến 205mm.', NULL, 'Cái', 2.000, 64, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(236, 52, 63, 65, 'HH065', 'Dụng cụ vén não Olivecrona hai đầu, loại nhỏ', 'Chủng loại: Dụng cụ vén não, kiểu OLIVECRONA.\nHình dáng: Thiết kế dạng thanh dẹt; có tính uốn dẻo hoặc đàn hồi; hai đầu làm việc.\nKích thước: Chiều dài tổng thể từ 175mm đến 185mm.\nBề rộng hai đầu làm việc lần lượt là: 7mm và 9mm (dung sai ± 0.5mm).', NULL, 'Cái', 2.000, 65, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(237, 52, 63, 66, 'HH066', 'Vén não Olivecrona hai đầu, loại vừa', 'Chủng loại: Dụng cụ vén não, kiểu OLIVECRONA.\nHình dáng: Thiết kế dạng thanh dẹt; có tính uốn dẻo hoặc đàn hồi; hai đầu làm việc.\nKích thước: Chiều dài tổng thể từ 175mm đến 185mm.\nBề rộng hai đầu làm việc lần lượt là: 11mm và 13mm (dung sai ± 0.5mm).', NULL, 'Cái', 2.000, 66, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(238, 52, 63, 67, 'HH067', 'Vén não Olivecrona hai đầu, loại lớn', 'Chủng loại: Dụng cụ vén não, kiểu OLIVECRONA.\nHình dáng: Thiết kế dạng thanh dẹt; có tính uốn dẻo hoặc đàn hồi; hai đầu làm việc.\nKích thước: Chiều dài tổng thể từ 175mm đến 185mm.\nBề rộng hai đầu làm việc lần lượt là: 18mm và 22mm (dung sai ± 0.5mm).', NULL, 'Cái', 4.000, 67, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(239, 52, 63, 68, 'HH068', 'Ống hút phẫu thuật Fergusson/Frazier, loại nhỏ', 'Chủng loại: Ống hút phẫu thuật, kiểu FERGUSSON / FRAZIER.\nChất liệu: Thép không gỉ\nHình dáng: Hình dáng: Ống hút thân cứng, đầu uốn cong góc 45 độ, có lỗ điều chỉnh áp lực.\nKích thước: Chiều dài làm việc từ 105mm đến 115mm. Đường kính đầu ống hút tương đương khoảng 2.3mm đến 2.6mm.', NULL, 'Cái', 2.000, 68, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(240, 52, 63, 69, 'HH069', 'Ống hút phẫu thuật Fergusson/Frazier, loại vừa', 'Chủng loại: Ống hút phẫu thuật, kiểu FERGUSSON / FRAZIER.\nChất liệu: Thép không gỉ\nHình dáng: Ống hút thân cứng, đầu uốn cong góc 45 độ, có lỗ điều chỉnh áp lực.\nKích thước: Chiều dài làm việc từ 105mm đến 115mm. Đường kính đầu ống hút tương đương khoảng 2.8 mm đến 3.2 mm.', NULL, 'Cái', 2.000, 69, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(241, 52, 63, 70, 'HH070', 'Ống hút phẫu thuật Frazier gập góc, loại lớn', 'Chủng loại: Ống hút phẫu thuật, kiểu FERGUSSON / FRAZIER.\nChất liệu: Thép không gỉ\nHình dáng: Ống hút thân cứng, đầu uốn cong góc 45 độ, có lỗ điều chỉnh áp lực.\nKích thước: Chiều dài làm việc từ 105 mm đến 115mm. Đường kính đầu ống hút tương đương khoảng 3.8 mm đến 4.2 mm.', NULL, 'Cái', 2.000, 70, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(242, 52, 63, 71, 'HH071', 'Ống hút Fukushima, loại lớn', 'Chủng loại: Ống hút vi phẫu, kiểu FUKUSHIMA.\nChất liệu: Thép không gỉ.\nHình dáng: Ống hút đầu uốn cong góc khoảng 30°; đầu ống thuôn; có tính uốn dẻo;\nKích thước: Chiều dài làm việc từ 160 mm đến 170 mm. Đường kính đầu ống hút tương đương khoảng 3.8mm đến 4.2 mm (hoặc cỡ 12 Charr / 12FR).', NULL, 'Cái', 2.000, 71, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(243, 52, 63, 72, 'HH072', 'Ống hút Fukushima, loại vừa', 'Chủng loại: Ống hút vi phẫu, kiểu FUKUSHIMA.\nChất liệu: Thép không gỉ.\nHình dáng: Ống hút đầu uốn cong góc khoảng 30°;đầu ống thuôn; có tính uốn dẻo;\nKích thước: Chiều dài làm việc từ 135mm đến 145mm. Đường kính đầu ống hút tương đương khoảng 2.5mm đến 2.9 mm (hoặc cỡ 8 Charr / 8FR).', NULL, 'Cái', 2.000, 72, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(244, 52, 63, 73, 'HH073', 'Ống hút vi phẫu Fukushima, loại nhỏ', 'Chủng loại: Ống hút vi phẫu, kiểu FUKUSHIMA.\nChất liệu: Thép không gỉ.\nHình dáng: Ống hút đầu uốn cong góc khoảng 30°;đầu ống thuôn; có tính uốn dẻo;\nKích thước: Chiều dài làm việc từ 135mm đến 145mm. Đường kính đầu ống hút tương đương khoảng 2.1mm đến 2.5 mm (hoặc cỡ 7 Charr / 7FR).', NULL, 'Cái', 4.000, 73, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(245, 52, 63, 74, 'HH074', 'Kìm cắt xương sọ Dahlgren, kèm móc giữ', 'Chủng loại: Kìm cắt xương sọ / Kìm bấm sọ, kiểu DAHLGREN.\nHình dáng: Dạng kìm; có móc giữ.\nKích thước: Chiều dài tổng thể từ 195mm đến 215mm.', NULL, 'Cái', 2.000, 74, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(246, 52, 63, 75, 'HH075', 'Dụng cụ dẫn hướng khoan và bảo vệ màng cứng Adson', 'Chủng loại: Dụng cụ dẫn hướng khoan và bảo vệ màng cứng Adson\nKích thước: Chiều dài tổng thể từ 150mm đến 180mm.\nVật liệu: thép không gỉ', NULL, 'Cái', 2.000, 75, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(247, 52, 63, 76, 'HH076', 'Kẹp cầm máu vi phẫu lưỡng cực, mũi thẳng', 'Chủng loại: Kẹp phẫu thuật lưỡng cực\nĐặc tính kỹ thuật: Đầu kẹp có lớp phủ/công nghệ chống dính mô.\nHình dáng: Dạng kẹp; thân kẹp gập góc; lưỡi kẹp thẳng.\nCấu tạo kết nối: Sử dụng chuẩn kết nối dạng chân cắm đôi kiểu Mỹ.\nKích thước: Chiều dài tổng thể từ 215mm đến 235mm. Chiều dài làm việc từ 110 mm đến 130 mm. Kích thước mỏ kẹp tương đương khoảng 0.9 mm - 1.1 mm.\nĐặc điểm chung: thân kẹp có vỏ bọc cách điện.', NULL, 'Cái', 2.000, 76, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(248, 52, 63, 77, 'HH077', 'Kẹp cầm máu vi phẫu, lưỡng cực, chống dính, mũi cong gập góc', 'Chủng loại: Kẹp phẫu thuật lưỡng cực\nĐặc tính kỹ thuật: Đầu kẹp có lớp phủ/công nghệ chống dính mô.\nHình dáng: Dạng kẹp; thân kẹp gập góc; phần mũi kẹp hướng lên.\nCấu tạo kết nối: Sử dụng chuẩn kết nối dạng chân cắm đôi kiểu Mỹ.\nKích thước: Chiều dài tổng thể từ 215mm đến 225mm. Chiều dài làm việc từ 90 mm đến 120 mm. Kích thước mỏ kẹp tương đương khoảng 0.4 mm đến 0.5 mm.\nĐặc điểm chung: thân kẹp có vỏ bọc cách điện.', NULL, 'Cái', 2.000, 77, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(249, 52, 63, 78, 'HH078', 'Kẹp mang kim Mayo-Hegar thẳng', 'Chủng loại: Kìm kẹp kim phẫu thuật, kiểu MAYO-HEGAR.\nChất liệu: Thép không gỉ; ngàm chèn hợp kim cứng (TC).\nHình dạng: Mũi thẳng; tay cầm mạ màu vàng; Bước răng ngàm kẹp từ 0.45 mm đến 0.55mm.\nKích thước: Chiều dài tổng thể từ 175 mm đến 190 mm.', NULL, 'Cái', 2.000, 78, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(250, 52, 63, 79, 'HH079', 'Kẹp mang kim De Bakey thẳng', 'Chủng loại: Kìm kẹp kim kiểu De Bakey.\nChất liệu: Thép không gỉ, ngàm chèn hợp kim cứng (TC).\nHình dạng: Mũi thẳng, tay cầm mạ vàng. Bước răng ngàm kẹp khoảng từ 0.35 mm đến 0.45mm.\nKích thước: Chiều dài từ 200 mm đến 215 mm.\nĐặc điểm khác: Phù hợp kẹp chỉ cỡ 6/0-4/0.', NULL, 'Cái', 4.000, 79, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(251, 52, 63, 80, 'HH080', 'Kẹp mang kim thẳng, loại dài', 'Chủng loại: Kìm kẹp kim kiểu Mayo-Hegar hoặc tương đương.\nChất liệu: Thép không gỉ, ngàm chèn hợp kim cứng (TC).\nHình dạng: Mũi thẳng, tay cầm mạ vàng. Bước răng ngàm kẹp từ 0.4 mm đến 0.5 mm.\nKích thước: Chiều dài từ 195 mm đến 205 mm.', NULL, 'Cái', 2.000, 80, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(252, 52, 63, 81, 'HH081', 'Kẹp mang kim Crile-Wood thẳng', 'Chủng loại: Kìm kẹp kim kiểu Crile-Wood.\nChất liệu: Thép không gỉ, ngàm chèn hợp kim cứng (TC).\nHình dạng: Mũi thẳng, tay cầm mạ vàng. Bước răng ngàm kẹp khoảng từ 0.35mm đến 0.45 mm.\nKích thước: Chiều dài từ 195 mm đến 205 mm.\nĐặc điểm khác: Phù hợp kẹp chỉ cỡ 6/0 -4/0.', NULL, 'Cái', 4.000, 81, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(253, 52, 63, 82, 'HH082', 'Khay quả thận', 'Chủng loại: Khay hạt đậu / Khay quả thận\nChất liệu: Thép không gỉ.\nHình dạng: Khay hình hạt đậu/quả thận.\nKích thước: Chiều dài khoảng từ 245mm đến 255mm. Chiều rộng từ 135mm đến 165mm. Chiều cao từ 35 mm đến 45 mm.\nĐặc điểm khác: Dung tích chứa tối thiểu 500 ml.', NULL, 'Cái', 2.000, 82, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(254, 52, 63, 83, 'HH083', 'Bát đựng bệnh phẩm loại nhỏ', 'Chủng loại: Bát tròn / Cốc tròn đựng dung dịch.\nChất liệu: Thép không gỉ.\nHình dạng: Thiết kế dạng bát/cốc tròn sâu lòng.\nKích thước: Đường kính miệng bát từ 80mm đến 85mm. Chiều cao từ 40mm đến 45mm.\nĐặc điểm khác: Dung tích chứa trong khoảng từ 150ml đến 170ml.', NULL, 'Cái', 2.000, 83, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(255, 52, 63, 84, 'HH084', 'Bát đựng bệnh phẩm loại vừa', 'Chủng loại: Bát tròn / Cốc tròn đựng dung dịch.\nChất liệu: Thép không gỉ.\nHình dạng: Thiết kế dạng bát/cốc tròn sâu lòng.\nKích thước: Đường kính miệng bát từ 125mm đến 130mm. Chiều cao từ 50mm đến 60mm.\nĐặc điểm khác: Dung tích chứa trong khoảng từ 300ml đến 450ml.', NULL, 'Cái', 2.000, 84, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(256, 52, 63, 85, 'HH085', 'Bát đựng bệnh phẩm loại lớn', 'Chủng loại: Bát tròn / Cốc tròn đựng dung dịch\nChất liệu: Thép không gỉ.\nHình dạng: Thiết kế dạng bát tròn sâu lòng.\nKích thước: Đường kính miệng bát từ 150mm đến 175mm. Chiều cao từ 70 mm đến 80 mm.\nĐặc điểm khác: Dung tích chứa trong khoảng từ 950ml đến 1050ml.', NULL, 'Cái', 2.000, 85, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(257, 52, 63, 86, 'HH086', 'Bộ hộp hấp tiệt trùng và bảo quản dụng cụ phẫu thuật', 'Chủng loại: Bộ hộp đựng dụng cụ tiệt trùng đồng bộ, chuẩn kích cỡ 3/4.\nChất liệu: Thân hộp bằng hợp kim nhôm hoặc thép không gỉ; nắp đậy bằng nhựa chịu lực hoặc Hợp kim; Khay đựng bằng thép không gỉ; thảm lót bằng silicone y tế.\nHình dạng: Đáy hộp kín; Sử dụng màng/đĩa lọc cản khuẩn tái sử dụng. Khay lưới cho phép tích hợp giá/kẹp silicone\nKích thước:\nHộp ngoài: Chiều dài từ 460 mm đến 475 mm. Chiều rộng từ 270 mm đến 290 mm. Chiều cao tổng thể từ 140 mm đến 160 mm.\nRổ lưới / Thảm silicone lót đáy: Tương thích chuẩn cỡ 3/4. Chiều dài tấm silicone từ 390 mm đến 415 mm. Chiều rộng từ 240 mm đến 255 mm.\nĐặc điểm khác: Cấu hình đồng bộ trọn bộ bao gồm: 01 Thân hộp kín. 01 Nắp đậy màu xám. 01 Hệ thống lọc cản khuẩn tái sử dụng. 01 Khay lưới tương thích. 01 Thảm lót silicone. Hệ thống gá/giá đỡ cố định dụng cụ. Phụ kiện tiêu hao đi kèm.', NULL, 'Cái', 2.000, 86, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(258, 52, 64, 1, 'HH087', 'Cán dao số 3', 'Cán dao số 3\nChất liệu: thép không rỉ\nHình dạng: loại tiêu chuẩn\nKích thước: dài ≥ 120mm đến ≤ 125mm', NULL, 'Cái', 1.000, 87, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(259, 52, 64, 2, 'HH088', 'Cán dao số 4', 'Cán dao số 4\nChất liệu: thép không rỉ\nHình dạng: loại tiêu chuẩn\nKích thước: dài ≥ 130mm đến ≤ 135mm', NULL, 'Cái', 1.000, 88, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(260, 52, 64, 3, 'HH089', 'Ống hút Yankauer', 'Ống hút Yankauer\nChất liệu: thép không rỉ\nKích thước: dài ≥ 210mm đến ≤ 290mm, đường kính ống Ø 1.8 mm', NULL, 'Cái', 1.000, 89, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(261, 52, 64, 4, 'HH090', 'Ống hút De Bakey', 'Ống hút De Bakey, dùng cho ống hút đường kính Ø 6-10 mm\nChất liệu: thép không rỉ\nKích thước: dài ≥ 270mm đến ≤ 275mm, đường kính lỗ trung tâm Ø 3 mm', NULL, 'Cái', 1.000, 90, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(262, 52, 64, 5, 'HH091', 'Kéo phẫu thuật cong', 'Kéo phẫu thuật\nChất liệu: thép không rỉ\nHình dạng: loại tiêu chuẩn, cong, một đầu tù, 1 đầu nhọn\nKích thước: dài ≥ 160mm đến ≤ 165mm', NULL, 'Cái', 2.000, 91, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(263, 52, 64, 6, 'HH092', 'Kéo Mayo', 'Kéo phẫu thuật kiểu Mayo\nChất liệu: Thép không rỉ, lưỡi bằng hợp kim Tungsten Carbide\nHình dáng: cong\nKích thước: Dài ≥ 165mm đến ≤ 175mm\nChủng loại: Cán vàng, TC (lưỡi bằng hợp kim Tungsten Carbide)', NULL, 'Cái', 2.000, 92, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(264, 52, 64, 7, 'HH093', 'Kéo phẫu tích Metzenbaum', 'Kéo phẫu tích kiểu Metzenbaum\nChất liệu: Thép không rỉ, lưỡi bằng hợp kim Tungsten Carbide\nHình dáng: Cong, hai đầu tù\nKích thước: Dài ≥ 175mm đến ≤ 180mm\nChủng loại: Cán vàng, TC (Tungsten Carbide)', NULL, 'Cái', 1.000, 93, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(265, 52, 64, 8, 'HH094', 'Nhíp phẫu tích thẳng cỡ nhỏ', 'Nhíp phẫu tích\nChất liệu: thép không rỉ\nHình dạng: cỡ phổ thông, thân thẳng, hàm có khía vuông\nKích thước: dài ≥ 160mm đến ≤ 165mm', NULL, 'Cái', 2.000, 94, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(266, 52, 64, 9, 'HH095', 'Nhíp phẫu tích thẳng có răng', 'Nhíp phẫu tích\nChất liệu: thép không rỉ\nHình dạng: thẳng, đầu nhỏ, hàm có răng (1x2)\nKích thước: dài ≥ 160mm đến ≤ 165mm', NULL, 'Cái', 2.000, 95, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(267, 52, 64, 10, 'HH096', 'Nhíp phẫu tích thẳng không mấu', 'Nhíp phẫu tích\nChất liệu: thép không rỉ\nHình dạng: thân thẳng, đầu nhỏ, không mấu\nKích thước: dài ≥ 180mm đến ≤ 185mm', NULL, 'Cái', 2.000, 96, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(268, 52, 64, 11, 'HH097', 'Nhíp mô loại nhỡ', 'Nhíp mô\nChất liệu: thép không rỉ\nHình dạng: thẳng, ngàm có răng (1x2)\nKích thước: dài ≥ 160mm đến ≤ 165mm', NULL, 'Cái', 2.000, 97, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(269, 52, 64, 12, 'HH098', 'Nhíp mô Gillies', 'Nhíp mô Gillies\nChất liệu: thép không rỉ\nHình dạng: ngàm có răng (1x2)\nKích thước: dài ≥ 150mm đến ≤ 155mm', NULL, 'Cái', 2.000, 98, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(270, 52, 64, 13, 'HH099', 'Kẹp mô De Bakey', 'Kẹp mô không chấn thương De Bakey\nChất liệu: thép không rỉ\nHình dạng: thẳng, ngàm có răng De Bakey,\nKích thước: dài ≥ 145mm đến ≤ 150mm, ngàm rộng 1.0mm', NULL, 'Cái', 2.000, 99, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(271, 52, 64, 14, 'HH100', 'Kẹp mạch máu PEAN', 'Kẹp mạch máu PEAN,\nChất liệu: thép không rỉ\nHình dạng: cong, đầu tù\nKích thước: dài ≥ 180mm đến ≤ 185mm', NULL, 'Cái', 2.000, 100, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(272, 52, 64, 15, 'HH101', 'Kẹp mạch máu Crile-Rankin, thẳng', 'Kẹp mạch máu Crile-Rankin,\nChất liệu: thép không rỉ\nHình dạng: thẳng\nKích thước:dài ≥ 160mm đến ≤ 165mm', NULL, 'Cái', 2.000, 101, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(273, 52, 64, 16, 'HH102', 'Kẹp mạch máu Crile-Rankin, cong', 'Kẹp mạch máu Crile-Rankin,\nChất liệu: thép không rỉ\nHình dạng: cong\nKích thước: dài ≥ 160mm đến ≤ 165mm', NULL, 'Cái', 2.000, 102, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(274, 52, 64, 17, 'HH103', 'Kẹp cầm máu Ochsner- Kocher', 'Kẹp cầm máu Ochsner-Kocher\nChất liệu: thép không rỉ\nHình dạng: cong, ngàm có răng (1x2),\nKích thước: dài ≥ 180mm đến ≤ 185mm', NULL, 'Cái', 2.000, 103, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(275, 52, 64, 18, 'HH104', 'Kẹp gắp bông băng Forster- Ballenger', 'Kẹp gắp bông băng Forster-Ballenger,\nChất liệu: thép không rỉ\nHình dạng: cong, ngàm có khía\nKích thước: dài ≥ 245mm đến ≤ 250mm', NULL, 'Cái', 2.000, 104, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(276, 52, 64, 19, 'HH105', 'Kẹp săng Backhaus', 'Kẹp săng Backhaus,\nChất liệu: thép không rỉ\nKích thước: dài ≥ 130mm đến ≤ 135mm', NULL, 'Cái', 6.000, 105, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(277, 52, 64, 20, 'HH106', 'Banh vết thương Volkmann hẹp', 'Banh vết thương Volkmann,\nChất liệu: Thép không rỉ\nHình dáng: 1 răng , 1 răng nhọn, cong hẹp\nKích thước: dài ≥ 215mm đến ≤ 220mm', NULL, 'Cái', 2.000, 106, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(278, 52, 64, 21, 'HH107', 'Banh vết thương Volkmann rộng', 'Banh vết thương Volkmann,\nChất liệu: Thép không rỉ\nHình dáng: 1 răng , 1 răng tù, cong rộng\nKích thước: dài ≥ 215mm đến ≤ 220mm', NULL, 'Cái', 2.000, 107, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(279, 52, 64, 22, 'HH108', 'Banh vết thương Kocher- Langenbeck', 'Banh tổ chức Kocher-Langenbeck,\nChất liệu: thép không rỉ\nKích thước: dài ≥ 215mm đến ≤ 220mm, kích thước ngàm 25 x 6 mm', NULL, 'Cái', 2.000, 108, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(280, 52, 64, 23, 'HH109', 'Banh vết thương Farabeuf nhỏ', 'Banh vết thương Farabeuf\nChất liệu: thép không rỉ bộ 2 chiếc, dài 150 mm, kích thước:\n- 30 x 16 mm/27 x 16 mm\n- 26 x 16 mm/23 x 16 mm\nhoặc\n-23x16mm/28x16mm\n-20x16mm/24x16mm', NULL, 'Bộ', 1.000, 109, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(281, 52, 64, 24, 'HH110', 'Banh vết thương Farabeuf', 'Banh vết thương Farabeuf\nChất liệu: thép không rỉ bộ 2 chiếc, dài 115 mm, kích thước:\n- 34 x 13 mm/30 x 10 mm\n- 30 x 13 mm/26 x 10 mm\nhoặc\n-25x10mm/32x12mm\n-22x10mm/28x12mm', NULL, 'Bộ', 1.000, 110, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0);
INSERT INTO `bg_hang_hoa` (`id`, `goi_thau_id`, `bo_id`, `stt_chi_tiet`, `ma_hh`, `ten_hang_hoa`, `thong_so_ky_thuat`, `nhom_nuoc`, `dvt`, `so_luong`, `thu_tu`, `ngay_tao`, `ngay_cap_nhat`, `nguoi_tao`, `nguoi_cap_nhat`, `da_xoa`) VALUES
(282, 52, 64, 25, 'HH111', 'Banh vết thương Farabeuf to', 'Bộ banh vết thương 2 chiếc Parker-Langenbeck hai đầu\nChất liệu: thép không rỉ\nKích thước: dài 210 mm, bao gồm:\n- 45 x 15 mm/25 x 15 mm\n- 40 x 15 mm/21 x 15 mm', NULL, 'Cái', 1.000, 111, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(283, 52, 64, 26, 'HH112', 'Banh tự giữ Weitlaner', 'Banh tự giữ Weitlaner,\nChất liệu: thép không rỉ\nHình dạng: (3x4) răng tù\nKích thước: dài ≥ 165mm đến ≤ 170mm', NULL, 'Cái', 2.000, 112, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(284, 52, 64, 27, 'HH113', 'Banh tự giữ Jefferson', 'Banh tự giữ Jefferson\nChất liệu: thép không rỉ\nHình dạng: cong, 3x4 răng tù\nKích thước: dài ≥ 140mm đến ≤ 145mm', NULL, 'Cái', 2.000, 113, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(285, 52, 64, 28, 'HH114', 'Cây luồn chỉ Deschamps', 'Cây luồn chỉ Deschamps, dành cho người thuận tay trái\nChất liệu: thép không rỉ\nHình dạng: đầu tù\nKích thước: dài ≥ 240mm đến ≤ 245mm', NULL, 'Cái', 2.000, 114, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(286, 52, 64, 29, 'HH115', 'Kìm mang kim Mayo-Hegar cỡ trung bình', 'Kìm mang kim Mayo-Hegar, dùng cho chỉ 6/0-4/0\nChất liệu: théo không rỉ, lưỡi bằng hợp kim Tungsten Carbide\nKích thước: dài ≥ 180mm đến ≤ 185mm\nChủng loại: cán vàng, TC(lưỡi bằng hợp kim Tungsten Carbid)', NULL, 'Cái', 2.000, 115, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(287, 52, 64, 30, 'HH116', 'Kìm kẹp kim Mayo-Hegar cỡ nhỏ', 'Kìm mang kim Mayo-Hegar, dùng cho chỉ 6/0-4/0\nChất liệu: théo không rỉ, lưỡi bằng hợp kim Tungsten Carbide\nKích thước: dài ≥ 150mm đến ≤ 160mm\nChủng loại: cán vàng, TC (lưỡi bằng hợp kim Tungsten Carbid)', NULL, 'Cái', 2.000, 116, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(288, 52, 64, 31, 'HH117', 'Búa Hajek', 'Búa Hajek\nChất liệu: thép không rỉ\nKích thước: nặng ≥ 160g đến ≤ 210g, dài ≥ 200mm đến ≤ 220mm', NULL, 'Cái', 1.000, 117, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(289, 52, 64, 32, 'HH118', 'Búa Bergmann', 'Búa Bergmann\nChất liệu: thép không rỉ\nKích thước: đường kính đầu 30mm, dài ≥ 245mm đến ≤ 250mm', NULL, 'Cái', 1.000, 118, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(290, 52, 64, 33, 'HH119', 'Đục lòng máng Alexander', 'Đục lòng máng Alexander\nChất liệu: thép không gỉ\nKích thước:dài ≥ 170mm đến ≤ 180mm, lưỡi rộng 7-8 mm', NULL, 'Cái', 1.000, 119, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(291, 52, 64, 34, 'HH120', 'Đục xương Lambotte', 'Đục xương Lambotte,\nChất liệu: thép không rỉ\nHình dạng: lưỡi vát 2 bên\nKích thước: dài ≥ 125mm đến ≤ 170 mm, lưỡi rộng 15 mm', NULL, 'Cái', 1.000, 120, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(292, 52, 64, 35, 'HH121', 'Đục xương Stille cong', 'Đục xương Stille\nChất liệu: thép không rỉ\nHình dạng: cong, lưỡi vát 2 bên\nKích thước: dài ≥ 200mm đến ≤ 205mm, lưỡi rộng 15 mm', NULL, 'Cái', 1.000, 121, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(293, 52, 64, 36, 'HH122', 'Đục xương Stille thẳng lưỡi nhỏ', 'Đục xương Stille\nChất liệu: thép không rỉ\nHình dạng: thẳng, lưỡi vát 2 bên\nKích thước: dài 200-205 mm, lưỡi rộng 10 mm', NULL, 'Cái', 1.000, 122, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(294, 52, 64, 37, 'HH123', 'Đục xương Stille thẳng lưỡi rộng', 'Đục xương Stille\nChất liệu: thép không rỉ\nHình dạng: thẳng, lưỡi vát 2 bên, rất mảnh\nKích thước: dài ≥ 200mm đến ≤ 205mm, lưỡi rộng 20 mm', NULL, 'Cái', 1.000, 123, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(295, 52, 64, 38, 'HH124', 'Dụng cụ phẫu tích Watson- Cheyne', 'Dụng cụ phẫu tích Watson-Cheyne\nChất liệu: thép không rỉ hình dạng: cong, hai đầu tù, bản to\nKích thước: dài ≥ 170mm đến ≤ 195mm.', NULL, 'Cái', 1.000, 124, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(296, 52, 64, 39, 'HH125', 'Dụng cụ nậy xương Langenbeck', 'Dụng cụ nậy xương Langenbeck Chất liệu;thép không rỉ\nHình dạng: cong nhẹ, đầu tù\nKích thước: dài ≥ 190mm đến ≤ 195mm, đầu rộng 8 mm - 10mm', NULL, 'Cái', 2.000, 125, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(297, 52, 64, 40, 'HH126', 'Dụng cụ nậy xương Mini- Hohmann cỡ ngắn', 'Dụng cụ nậy xương Mini-Hohmann\nChất liệu: thép không rỉ\nHình dạng: cong,\nKích thước: : Dài ≥ 160mm đến ≤ 220mm, rộng 8 mm', NULL, 'Cái', 2.000, 126, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(298, 52, 64, 41, 'HH127', 'Dụng cụ nậy xương', 'Dụng cụ nậy xương\nChất liệu: thép không rỉ\nKích thước: : Dài ≥ 160mm đến ≤ 220mm, kích thước lưỡi rộng 14 mm', NULL, 'Cái', 2.000, 127, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(299, 52, 64, 42, 'HH128', 'Dụng cụ nậy xương Mini- Hohmann cỡ dài', 'Dụng cụ nậy xương Mini-Hohmann\nChất liệu: thép không rỉ\nKích thước: : dài ≥ 220mm đến ≤ 225mm, kích thước lưỡi rộng 8 mm', NULL, 'Cái', 2.000, 128, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(300, 52, 64, 43, 'HH129', 'Thìa nạo xương, hai đầu, nhỏ', 'Thìa nạo xương, hai đầu\nChất liệu: thép không rỉ\nKích thước: dài ≥ 210mm đến ≤ 215mm', NULL, 'Cái', 2.000, 129, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(301, 52, 64, 44, 'HH130', 'Thìa nạo xương, hai đầu, to', 'Thìa nạo xương hai đầu Volkmann\nChất liệu: thép không rỉ\nKích thước: dài ≥ 160mm đến ≤ 170mm', NULL, 'Cái', 2.000, 130, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(302, 52, 64, 45, 'HH131', 'Kẹp xương giữ xương nhỏ có khoá, cỡ ngắn', 'Kẹp giữ cố định xương\nChất liệu: thép không rỉ\nHình dạng: đầu cong, có vít khóa\nKích thước: dài ≥ 170mm đến ≤ 175mm', NULL, 'Cái', 2.000, 131, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(303, 52, 64, 46, 'HH132', 'Kẹp xương giữ xương nhỏ có khoá, cỡ dài', 'Kẹp giữ cố định xương\nChất liệu: thép không rỉ\nHình dạng: đầu cong, có vít khóa\nKích thước: dài ≥ 230mm đến ≤ 235mm', NULL, 'Cái', 2.000, 132, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(304, 52, 64, 47, 'HH133', 'Kẹp xương ngắn Kern-Lane có khoá cỡ nhỏ', 'Kẹp giữ xương Kern-Lane\nChất liệu: thép không rỉ\nHình dạng: dạng có khóa cài\nKích thước: dài ≥ 155mm đến ≤ 170mm', NULL, 'Cái', 2.000, 133, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(305, 52, 64, 48, 'HH134', 'Kẹp xương dài Kern-Lane có khoá cỡ lớn', 'Kẹp giữ xương Kern-Lane\nChất liệu: thép không rỉ\nHình dạng: dạng có khóa cài\nKích thước: dài ≥ 240mm đến ≤ 245mm', NULL, 'Cái', 2.000, 134, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(306, 52, 64, 49, 'HH135', 'Kìm gặm xương Heanley thẳng', 'Kìm gặm xương Heanley\nChất liệu: thép không rỉ Hình dạng; thẳng\nKích thước: dài ≥ 180mm đến ≤ 185mm', NULL, 'Cái', 2.000, 135, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(307, 52, 64, 50, 'HH136', 'Kìm gặm xương Luer-Fiedmann cong', 'Kìm gặm xương Luer-Fiedmann\nChất liệu: thép không rỉ Hình dạng; cong\nKích thước: dài ≥ 145mm đến ≤ 150mm', NULL, 'Cái', 2.000, 136, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(308, 52, 64, 51, 'HH137', 'Kìm gặm xương Boehler, cong', 'Kìm gặm xương Boehler\nChất liệu: thép không rỉ\nHình dạng: cong, ngàm hẹp, hoạt động kép\nKích thước: dài ≥ 155mm đến ≤ 160mm, ngàm rộng 4-5 mm', NULL, 'Cái', 2.000, 137, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(309, 52, 64, 52, 'HH138', 'Gu gặm xương Ruskin cong', 'Gu gặm xương Ruskin\nChất liệu: thép không rỉ\nHình dạng: cong, hoạt động đôi\nKích thước: dài ≥ 180mm đến ≤ 185mm, bản 4 mm + 5%', NULL, 'Cái', 2.000, 138, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(310, 52, 64, 53, 'HH139', 'Kìm cắt xương Bohler', 'Kìm cắt xương Bohler\nChất liệu: thép không rỉ\nHình dạng: thẳng\nKích thước: dài ≥ 145mm đến ≤ 150mm', NULL, 'Cái', 2.000, 139, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(311, 52, 64, 54, 'HH140', 'Kìm giữ chỉ thép ngắn', 'Kìm giữ chỉ thép\nChất liệu: thép không rỉ\nHình dạng: mũi thẳng, ngàm khía\nKích thước: dài ≥ 170mm đến ≤ 175mm', NULL, 'Cái', 2.000, 140, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(312, 52, 64, 55, 'HH141', 'Kìm giữ chỉ thép dài', 'Kìm giữ chỉ thép\nChất liệu: thép không rỉ\nKích thước: dài ≥ 190mm đến ≤ 195mm', NULL, 'Cái', 2.000, 141, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(313, 52, 64, 56, 'HH142', 'Kìm cắt chỉ thép thẳng', 'Kìm cắt chỉ thép\nChất liệu: théo không rỉ, lưỡi bằng hợp kim Tungsten Carbide\nHình dạng: dùng cắt chỉ cứng đường kính tới ≥1.5mm đến ≤1.7 mm, chỉ mềm đường kính tới 2mm\nChủng loại: cán vàng, TC(lưỡi bằng hợp kim Tungsten Carbide)', NULL, 'Cái', 2.000, 142, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(314, 52, 64, 57, 'HH143', 'Kìm cắt chỉ thép nghiêng', 'Kìm cắt chỉ thép\nChất liệu: thép không rỉ, lưỡi bằng hợp kim Tungsten Carbide\nHình dạng: đầu gập góc, dùng cắt chỉ cứng đường kính tới ≥1.5mm đến ≤2.2 mm, chỉ mềm đường kính tới ≥2mm đến ≤3mm\nChủng loại: cán vàng, TC(lưỡi bằng hợp kim Tungsten Carbide)', NULL, 'Cái', 2.000, 143, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(315, 52, 64, 58, 'HH144', 'Thước đo vít', 'Thước đo vít, dùng cho vít chiều dài tới 80 mm\nChất liệu: thép không rỉ\nKích thước: dài ≥230mm đến ≤270mm', NULL, 'Cái', 2.000, 144, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(316, 52, 64, 59, 'HH145', 'Dụng cụ vặn vít lục giác cỡ 2.5mm', 'Dụng cụ vặn vít lục giác\nChất liệu: thép không rỉ, tay cầm có thể hấp sấy ở nhiệt độ cao\nKích thước: đầu kích thước 2.5 mm', NULL, 'Cái', 2.000, 145, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(317, 52, 64, 60, 'HH146', 'Dụng cụ vặn vít lục giác cỡ 3.5m', 'Dụng cụ vặn vít lục giác\nChất liệu: thép không rỉ, tay cầm có thể hấp sấy ở nhiệt độ cao\nKích thước: đầu kích thước 3.5 mm, dài ≥ 200mm đến ≤ 250mm', NULL, 'Cái', 2.000, 146, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(318, 52, 64, 61, 'HH147', 'Bộ hộp hấp tiệt trùng và bảo', 'Chủng loại: Bộ Hộp hấp đựng và bảo quản dụng cụ phẫu thuật, loại 1/1, bao gồm: Hộp hấp, Khay lưới, Lưới silicone', NULL, 'Bộ', 1.000, 147, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(319, 52, 64, 62, 'HH148', 'Bát đựng bệnh phẩm nhỏ ( Chất liệu nhựa hấp được )', 'Bát đựng bệnh phẩm\nVật liệu: polypropylene màu xanh, có thể tái sử dụng, hấp được ở nhiệt độ cao\nThể tích: 300ml', NULL, 'Cái', 2.000, 148, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0),
(320, 52, 64, 63, 'HH149', 'Bát đựng bệnh phẩm nhỏ (Chất liệu nhựa hấp được)', 'Bát tròn đựng bệnh phẩm\nVật liệu: polypropylene màu xanh, có thể tái sử dụng, hấp được ở nhiệt độ cao\nThể tích: 900ml', NULL, 'Cái', 2.000, 149, '2026-09-11 10:57:19', '2026-09-11 10:57:19', 1, 1, 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bg_quyen_goi_thau`
--

CREATE TABLE `bg_quyen_goi_thau` (
  `id` int(11) NOT NULL,
  `goi_thau_id` int(11) NOT NULL,
  `nguoi_dung_id` int(11) NOT NULL,
  `ngay_tao` datetime DEFAULT current_timestamp(),
  `ngay_cap_nhat` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `nguoi_tao` int(11) DEFAULT NULL,
  `nguoi_cap_nhat` int(11) DEFAULT NULL,
  `da_xoa` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Ai được xem gói thầu nào — gói chưa gán thì chỉ admin + quản lý thấy';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `dm_dang_nhap_that_bai`
--

CREATE TABLE `dm_dang_nhap_that_bai` (
  `id` int(11) NOT NULL,
  `khoa` varchar(190) NOT NULL COMMENT 'Khóa đếm: <ip>|<tai_khoan>',
  `ip_address` varchar(45) NOT NULL,
  `tai_khoan` varchar(100) DEFAULT NULL COMMENT 'Tài khoản bị dò (chỉ để tra cứu)',
  `so_lan` int(11) NOT NULL DEFAULT 1,
  `lan_dau` datetime NOT NULL COMMENT 'Lần sai đầu tiên trong chuỗi',
  `lan_cuoi` datetime NOT NULL COMMENT 'Lần sai gần nhất — mốc tính khóa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `dm_danh_sach_form`
--

CREATE TABLE `dm_danh_sach_form` (
  `id` int(11) NOT NULL,
  `modules_tuong_ung` varchar(100) NOT NULL,
  `ten_form` varchar(200) NOT NULL,
  `form_cha_id` int(11) DEFAULT 0,
  `ngay_tao` datetime DEFAULT current_timestamp(),
  `ngay_cap_nhat` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `nguoi_tao` int(11) DEFAULT NULL,
  `nguoi_cap_nhat` int(11) DEFAULT NULL,
  `da_xoa` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `dm_danh_sach_form`
--

INSERT INTO `dm_danh_sach_form` (`id`, `modules_tuong_ung`, `ten_form`, `form_cha_id`, `ngay_tao`, `ngay_cap_nhat`, `nguoi_tao`, `nguoi_cap_nhat`, `da_xoa`) VALUES
(1, 'DM_NguoiDung', 'Quản lý người dùng', 0, '2026-08-16 23:01:02', '2026-08-16 23:01:02', 1, 1, 0),
(2, 'DM_NhomTaiKhoan', 'Quản lý nhóm tài khoản', 0, '2026-08-16 23:01:02', '2026-08-16 23:01:02', 1, 1, 0),
(3, 'DM_DanhSachForm', 'Danh sách form', 0, '2026-08-16 23:01:02', '2026-08-16 23:01:02', 1, 1, 0),
(4, 'DM_PhanQuyen', 'Phân quyền', 0, '2026-08-16 23:01:02', '2026-08-16 23:01:02', 1, 1, 0),
(5, 'DM_NhatKyHeThong', 'Nhật ký hệ thống', 0, '2026-08-16 23:01:02', '2026-08-16 23:01:02', 1, 1, 0),
(9, 'BG_GoiThau', 'Gói thầu / Mời chào giá', 0, '2026-08-17 20:42:19', '2026-08-17 20:42:19', 1, 1, 0),
(10, 'BG_HangHoa', 'Hàng hóa gói thầu', 0, '2026-08-17 20:42:19', '2026-08-17 20:42:19', 1, 1, 0),
(11, 'BG_BaoGia', 'Báo giá nhà thầu', 0, '2026-08-17 20:42:19', '2026-08-17 20:42:19', 1, 1, 0),
(12, 'BG_TongHop', 'Tổng hợp báo giá', 0, '2026-08-17 20:42:19', '2026-08-17 20:42:19', 1, 1, 0),
(13, 'BG_QuanLyFile', 'Quản lý file bản ký', 0, '2026-08-18 22:17:31', '2026-08-18 22:17:31', 1, 1, 0),
(14, 'BG_QuyenGoiThau', 'Phân quyền gói thầu', 0, '2026-08-26 20:49:16', '2026-08-26 20:49:16', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `dm_nguoi_dung`
--

CREATE TABLE `dm_nguoi_dung` (
  `id` int(11) NOT NULL,
  `tai_khoan` varchar(50) NOT NULL,
  `mat_khau` varchar(255) NOT NULL,
  `nhom_tai_khoan_id` int(11) NOT NULL,
  `trang_thai` int(11) DEFAULT 1,
  `lan_dang_nhap_cuoi` datetime DEFAULT NULL,
  `ngay_tao` datetime DEFAULT current_timestamp(),
  `ngay_cap_nhat` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `nguoi_tao` int(11) DEFAULT NULL,
  `nguoi_cap_nhat` int(11) DEFAULT NULL,
  `da_xoa` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `dm_nguoi_dung`
--

INSERT INTO `dm_nguoi_dung` (`id`, `tai_khoan`, `mat_khau`, `nhom_tai_khoan_id`, `trang_thai`, `lan_dang_nhap_cuoi`, `ngay_tao`, `ngay_cap_nhat`, `nguoi_tao`, `nguoi_cap_nhat`, `da_xoa`) VALUES
(1, 'admin', '$2y$10$vvWtaekhiGLYTQyROZQdgOagEbZ6z08zTwrSSvRnMiigcmxcX7q3a', 1, 1, '2026-09-13 10:38:09', '2026-08-16 23:01:02', '2026-09-13 10:38:09', 1, 1, 0),
(2, 'manager', '$2y$10$VZRoSJj9elqD6QXL/8/oNe4N6Exd.deIVW5TtP4pnA8W0o9lGE5qy', 2, 1, '2026-08-17 22:29:29', '2026-08-16 23:01:02', '2026-09-12 16:43:28', 1, 1, 1),
(3, 'staff01', '$2y$10$GbOhHEYjAtazXjQFrZqWzenBOLCfqyZ4liSD5w57OgW0qFEyBFd5K', 3, 1, '2026-08-26 21:39:54', '2026-08-16 23:01:02', '2026-09-12 16:43:24', 1, 1, 1),
(4, 'staff02', '$2y$10$vCtJf678dGRfIqDp48Jl2eLUpQJwP.l1vzT49OSoEUvCvD1hA1kbu', 3, 1, NULL, '2026-08-16 23:01:02', '2026-09-12 16:43:22', 1, 1, 1),
(5, 'viewer', '$2y$10$XggTLbHjv8La3Ktq/CtlNeAY5uoftZpv2erOHwAx0Eorxi80ErDPq', 4, 1, '2026-08-18 21:42:58', '2026-08-16 23:01:02', '2026-09-12 16:43:20', 1, 1, 1),
(6, 'locked', '$2y$10$QFiI60TPwKZ4DauLJg9Rleo5/exIdD8tJLyUsUsAaCRxhZ3ah3NSS', 3, 0, NULL, '2026-08-16 23:01:02', '2026-09-12 16:43:13', 1, 1, 1),
(10, 'guest', '$2y$10$Dqv4q0xEJqu7tNX3cTCc5eo8nRGHNWFdtL.ND5rs81ma1kvlmb28e', 9, 1, '2026-09-10 21:47:22', '2026-08-17 20:42:19', '2026-09-10 21:47:22', 1, 1, 0),
(12, 'CAMCHI', '$2y$10$9dEFhoikJNYrPCBrUp2Oo.ndixDylDiANcWmhhatTP.MZaaeyrm3W', 3, 1, '2026-09-13 08:51:46', '2026-09-13 08:51:25', '2026-09-13 08:51:46', 1, 1, 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `dm_nhat_ky_he_thong`
--

CREATE TABLE `dm_nhat_ky_he_thong` (
  `id` int(11) NOT NULL,
  `thoi_gian` datetime DEFAULT current_timestamp(),
  `nguoi_dung_id` int(11) DEFAULT NULL,
  `tai_khoan` varchar(50) DEFAULT NULL,
  `module` varchar(100) NOT NULL,
  `hanh_dong` varchar(200) NOT NULL,
  `noi_dung` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `ngay_tao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `dm_nhat_ky_he_thong`
--

INSERT INTO `dm_nhat_ky_he_thong` (`id`, `thoi_gian`, `nguoi_dung_id`, `tai_khoan`, `module`, `hanh_dong`, `noi_dung`, `ip_address`, `user_agent`, `ngay_tao`) VALUES
(1, '2026-08-16 22:56:02', 1, 'admin', 'HeThong', 'Đăng nhập', 'Đăng nhập thành công', '127.0.0.1', 'Mozilla/5.0 (Seed Data)', '2026-08-16 23:01:02'),
(2, '2026-08-16 22:19:02', 1, 'admin', 'HeThong', 'Thêm nhóm tài khoản', 'bang=dm_nhom_tai_khoan; Thêm nhóm STAFF', '127.0.0.1', 'Mozilla/5.0 (Seed Data)', '2026-08-16 23:01:02'),
(3, '2026-08-16 21:42:02', 2, 'manager', 'HeThong', 'Đăng nhập', 'Đăng nhập thành công', '192.168.1.20', 'Mozilla/5.0 (Seed Data)', '2026-08-16 23:01:02'),
(4, '2026-08-16 21:05:02', 2, 'manager', 'HeThong', 'Sửa người dùng', 'bang=dm_nguoi_dung; id=3', '192.168.1.20', 'Mozilla/5.0 (Seed Data)', '2026-08-16 23:01:02'),
(5, '2026-08-16 20:28:02', 3, 'staff01', 'HeThong', 'Đăng nhập', 'Đăng nhập thành công', '192.168.1.35', 'Mozilla/5.0 (Seed Data)', '2026-08-16 23:01:02'),
(6, '2026-08-16 19:51:02', 3, 'staff01', 'HeThong', 'Đăng nhập thất bại', 'Sai mật khẩu', '192.168.1.35', 'Mozilla/5.0 (Seed Data)', '2026-08-16 23:01:02'),
(7, '2026-08-16 19:14:02', 5, 'viewer', 'HeThong', 'Đăng nhập', 'Đăng nhập thành công', '10.0.0.8', 'Mozilla/5.0 (Seed Data)', '2026-08-16 23:01:02'),
(8, '2026-08-16 23:03:04', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'SmokeTest', '2026-08-16 23:03:04'),
(9, '2026-08-16 23:03:04', 1, NULL, 'HeThong', 'Đăng nhập thất bại: admin', 'bang=dm_nguoi_dung; id=1; Sai mật khẩu', '127.0.0.1', 'SmokeTest', '2026-08-16 23:03:04'),
(10, '2026-08-16 23:03:04', 6, NULL, 'HeThong', 'Đăng nhập thất bại: locked', 'bang=dm_nguoi_dung; id=6; Tài khoản bị khóa', '127.0.0.1', 'SmokeTest', '2026-08-16 23:03:04'),
(11, '2026-08-16 23:03:04', NULL, NULL, 'HeThong', 'Đăng nhập thất bại: nobody', 'bang=dm_nguoi_dung; Tài khoản không tồn tại', '127.0.0.1', 'SmokeTest', '2026-08-16 23:03:04'),
(12, '2026-08-16 23:03:04', 5, 'admin', 'HeThong', 'Đăng nhập: viewer', 'bang=dm_nguoi_dung; id=5', '127.0.0.1', 'SmokeTest', '2026-08-16 23:03:04'),
(13, '2026-08-16 23:03:04', 2, 'admin', 'HeThong', 'Đăng nhập: manager', 'bang=dm_nguoi_dung; id=2', '127.0.0.1', 'SmokeTest', '2026-08-16 23:03:04'),
(14, '2026-08-16 23:03:04', 1, 'admin', 'HeThong', 'Thêm người dùng: smoke_user', 'bang=dm_nguoi_dung; id=7', '127.0.0.1', 'SmokeTest', '2026-08-16 23:03:04'),
(15, '2026-08-16 23:03:05', 1, 'admin', 'HeThong', 'Sửa người dùng: smoke_user', 'bang=dm_nguoi_dung; id=7', '127.0.0.1', 'SmokeTest', '2026-08-16 23:03:05'),
(16, '2026-08-16 23:03:05', 1, 'admin', 'HeThong', 'Xóa tạm người dùng id=7', 'bang=dm_nguoi_dung; id=7', '127.0.0.1', 'SmokeTest', '2026-08-16 23:03:05'),
(17, '2026-08-16 23:03:05', 1, 'admin', 'HeThong', 'Khôi phục người dùng id=7', 'bang=dm_nguoi_dung; id=7', '127.0.0.1', 'SmokeTest', '2026-08-16 23:03:05'),
(18, '2026-08-16 23:03:05', 1, 'admin', 'HeThong', 'Xóa tạm người dùng id=7', 'bang=dm_nguoi_dung; id=7', '127.0.0.1', 'SmokeTest', '2026-08-16 23:03:05'),
(19, '2026-08-16 23:03:05', 1, 'admin', 'HeThong', 'Xóa vĩnh viễn người dùng id=7', 'bang=dm_nguoi_dung; id=7', '127.0.0.1', 'SmokeTest', '2026-08-16 23:03:05'),
(20, '2026-08-16 23:03:05', 1, 'admin', 'HeThong', 'Thêm nhóm TK: Nhom smoke', 'bang=dm_nhom_tai_khoan; id=6', '127.0.0.1', 'SmokeTest', '2026-08-16 23:03:05'),
(21, '2026-08-16 23:03:05', 1, 'admin', 'HeThong', 'Xóa tạm nhóm TK id=6', 'bang=dm_nhom_tai_khoan; id=6', '127.0.0.1', 'SmokeTest', '2026-08-16 23:03:05'),
(22, '2026-08-16 23:03:05', 1, 'admin', 'HeThong', 'Khôi phục nhóm TK id=6', 'bang=dm_nhom_tai_khoan; id=6', '127.0.0.1', 'SmokeTest', '2026-08-16 23:03:05'),
(23, '2026-08-16 23:03:05', 1, 'admin', 'HeThong', 'Xóa tạm nhóm TK id=6', 'bang=dm_nhom_tai_khoan; id=6', '127.0.0.1', 'SmokeTest', '2026-08-16 23:03:05'),
(24, '2026-08-16 23:03:05', 1, 'admin', 'HeThong', 'Xóa vĩnh viễn nhóm TK id=6', 'bang=dm_nhom_tai_khoan; id=6', '127.0.0.1', 'SmokeTest', '2026-08-16 23:03:05'),
(25, '2026-08-16 23:03:05', 1, 'admin', 'HeThong', 'Thêm form: Form smoke', 'bang=dm_danh_sach_form; id=6', '127.0.0.1', 'SmokeTest', '2026-08-16 23:03:05'),
(26, '2026-08-16 23:03:05', 1, 'admin', 'HeThong', 'Xóa tạm form id=6', 'bang=dm_danh_sach_form; id=6', '127.0.0.1', 'SmokeTest', '2026-08-16 23:03:05'),
(27, '2026-08-16 23:03:05', 1, 'admin', 'HeThong', 'Khôi phục form id=6', 'bang=dm_danh_sach_form; id=6', '127.0.0.1', 'SmokeTest', '2026-08-16 23:03:05'),
(28, '2026-08-16 23:03:05', 1, 'admin', 'HeThong', 'Xóa tạm form id=6', 'bang=dm_danh_sach_form; id=6', '127.0.0.1', 'SmokeTest', '2026-08-16 23:03:05'),
(29, '2026-08-16 23:03:05', 1, 'admin', 'HeThong', 'Xóa vĩnh viễn form id=6', 'bang=dm_danh_sach_form; id=6', '127.0.0.1', 'SmokeTest', '2026-08-16 23:03:05'),
(30, '2026-08-16 23:03:05', 1, 'admin', 'HeThong', 'Cập nhật phân quyền nhóm id=4', 'bang=dm_phan_quyen; id=4', '127.0.0.1', 'SmokeTest', '2026-08-16 23:03:05'),
(31, '2026-08-16 23:03:05', 1, 'admin', 'HeThong', 'Cập nhật phân quyền nhóm id=4', 'bang=dm_phan_quyen; id=4', '127.0.0.1', 'SmokeTest', '2026-08-16 23:03:05'),
(32, '2026-08-16 23:03:05', 1, 'admin', 'HeThong', 'Cập nhật phân quyền nhóm id=4', 'bang=dm_phan_quyen; id=4', '127.0.0.1', 'SmokeTest', '2026-08-16 23:03:05'),
(33, '2026-08-16 23:03:05', 1, 'admin', 'HeThong', 'Smoke test log', 'bang=dm_nguoi_dung; id=99; noi dung test', '127.0.0.1', 'SmokeTest', '2026-08-16 23:03:05'),
(34, '2026-08-16 23:36:33', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-16 23:36:33'),
(35, '2026-08-17 17:18:32', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-17 17:18:32'),
(36, '2026-08-17 17:18:41', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-17 17:18:41'),
(37, '2026-08-17 17:18:56', 1, NULL, 'HeThong', 'Đăng nhập thất bại: admin', 'bang=dm_nguoi_dung; id=1; Sai mật khẩu', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-17 17:18:56'),
(38, '2026-08-17 17:19:02', 1, NULL, 'HeThong', 'Đăng nhập thất bại: admin', 'bang=dm_nguoi_dung; id=1; Sai mật khẩu', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-17 17:19:02'),
(39, '2026-08-17 17:19:15', 1, NULL, 'HeThong', 'Đăng nhập thất bại: admin', 'bang=dm_nguoi_dung; id=1; Sai mật khẩu', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-17 17:19:15'),
(40, '2026-08-17 17:19:36', 1, NULL, 'HeThong', 'Đăng nhập thất bại: admin', 'bang=dm_nguoi_dung; id=1; Sai mật khẩu', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-17 17:19:36'),
(41, '2026-08-17 17:19:43', 1, NULL, 'HeThong', 'Đăng nhập thất bại: admin', 'bang=dm_nguoi_dung; id=1; Sai mật khẩu', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-17 17:19:43'),
(42, '2026-08-17 17:19:47', NULL, NULL, 'HeThong', 'Đăng nhập bị chặn (quá số lần): admin', 'bang=dm_nguoi_dung', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-17 17:19:47'),
(43, '2026-08-17 17:24:56', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-17 17:24:56'),
(44, '2026-08-17 17:24:59', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-17 17:24:59'),
(45, '2026-08-17 17:25:40', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-17 17:25:40'),
(46, '2026-08-17 17:25:51', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-17 17:25:51'),
(47, '2026-08-17 17:26:08', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'SmokeTest', '2026-08-17 17:26:08'),
(48, '2026-08-17 17:26:08', 1, NULL, 'HeThong', 'Đăng nhập thất bại: admin', 'bang=dm_nguoi_dung; id=1; Sai mật khẩu', '127.0.0.1', 'SmokeTest', '2026-08-17 17:26:08'),
(49, '2026-08-17 17:26:08', 6, NULL, 'HeThong', 'Đăng nhập thất bại: locked', 'bang=dm_nguoi_dung; id=6; Tài khoản bị khóa', '127.0.0.1', 'SmokeTest', '2026-08-17 17:26:08'),
(50, '2026-08-17 17:26:08', NULL, NULL, 'HeThong', 'Đăng nhập thất bại: nobody', 'bang=dm_nguoi_dung; Tài khoản không tồn tại', '127.0.0.1', 'SmokeTest', '2026-08-17 17:26:08'),
(51, '2026-08-17 17:26:08', 5, 'admin', 'HeThong', 'Đăng nhập: viewer', 'bang=dm_nguoi_dung; id=5', '127.0.0.1', 'SmokeTest', '2026-08-17 17:26:08'),
(52, '2026-08-17 17:26:08', 2, 'admin', 'HeThong', 'Đăng nhập: manager', 'bang=dm_nguoi_dung; id=2', '127.0.0.1', 'SmokeTest', '2026-08-17 17:26:08'),
(53, '2026-08-17 17:26:08', 1, 'admin', 'HeThong', 'Thêm người dùng: smoke_user', 'bang=dm_nguoi_dung; id=8', '127.0.0.1', 'SmokeTest', '2026-08-17 17:26:08'),
(54, '2026-08-17 17:26:08', 1, 'admin', 'HeThong', 'Sửa người dùng: smoke_user', 'bang=dm_nguoi_dung; id=8', '127.0.0.1', 'SmokeTest', '2026-08-17 17:26:08'),
(55, '2026-08-17 17:26:08', 1, 'admin', 'HeThong', 'Xóa tạm người dùng id=8', 'bang=dm_nguoi_dung; id=8', '127.0.0.1', 'SmokeTest', '2026-08-17 17:26:08'),
(56, '2026-08-17 17:26:08', 1, 'admin', 'HeThong', 'Khôi phục người dùng id=8', 'bang=dm_nguoi_dung; id=8', '127.0.0.1', 'SmokeTest', '2026-08-17 17:26:08'),
(57, '2026-08-17 17:26:08', 1, 'admin', 'HeThong', 'Xóa tạm người dùng id=8', 'bang=dm_nguoi_dung; id=8', '127.0.0.1', 'SmokeTest', '2026-08-17 17:26:08'),
(58, '2026-08-17 17:26:08', 1, 'admin', 'HeThong', 'Xóa vĩnh viễn người dùng id=8', 'bang=dm_nguoi_dung; id=8', '127.0.0.1', 'SmokeTest', '2026-08-17 17:26:08'),
(59, '2026-08-17 17:26:08', 1, 'admin', 'HeThong', 'Thêm nhóm TK: Nhom smoke', 'bang=dm_nhom_tai_khoan; id=7', '127.0.0.1', 'SmokeTest', '2026-08-17 17:26:08'),
(60, '2026-08-17 17:26:08', 1, 'admin', 'HeThong', 'Xóa tạm nhóm TK id=7', 'bang=dm_nhom_tai_khoan; id=7', '127.0.0.1', 'SmokeTest', '2026-08-17 17:26:08'),
(61, '2026-08-17 17:26:08', 1, 'admin', 'HeThong', 'Khôi phục nhóm TK id=7', 'bang=dm_nhom_tai_khoan; id=7', '127.0.0.1', 'SmokeTest', '2026-08-17 17:26:08'),
(62, '2026-08-17 17:26:08', 1, 'admin', 'HeThong', 'Xóa tạm nhóm TK id=7', 'bang=dm_nhom_tai_khoan; id=7', '127.0.0.1', 'SmokeTest', '2026-08-17 17:26:08'),
(63, '2026-08-17 17:26:08', 1, 'admin', 'HeThong', 'Xóa vĩnh viễn nhóm TK id=7', 'bang=dm_nhom_tai_khoan; id=7', '127.0.0.1', 'SmokeTest', '2026-08-17 17:26:08'),
(64, '2026-08-17 17:26:08', 1, 'admin', 'HeThong', 'Thêm form: Form smoke', 'bang=dm_danh_sach_form; id=7', '127.0.0.1', 'SmokeTest', '2026-08-17 17:26:08'),
(65, '2026-08-17 17:26:08', 1, 'admin', 'HeThong', 'Xóa tạm form id=7', 'bang=dm_danh_sach_form; id=7', '127.0.0.1', 'SmokeTest', '2026-08-17 17:26:08'),
(66, '2026-08-17 17:26:08', 1, 'admin', 'HeThong', 'Khôi phục form id=7', 'bang=dm_danh_sach_form; id=7', '127.0.0.1', 'SmokeTest', '2026-08-17 17:26:08'),
(67, '2026-08-17 17:26:08', 1, 'admin', 'HeThong', 'Xóa tạm form id=7', 'bang=dm_danh_sach_form; id=7', '127.0.0.1', 'SmokeTest', '2026-08-17 17:26:08'),
(68, '2026-08-17 17:26:08', 1, 'admin', 'HeThong', 'Xóa vĩnh viễn form id=7', 'bang=dm_danh_sach_form; id=7', '127.0.0.1', 'SmokeTest', '2026-08-17 17:26:08'),
(69, '2026-08-17 17:26:08', 1, 'admin', 'HeThong', 'Cập nhật phân quyền nhóm id=4', 'bang=dm_phan_quyen; id=4', '127.0.0.1', 'SmokeTest', '2026-08-17 17:26:08'),
(70, '2026-08-17 17:26:08', 1, 'admin', 'HeThong', 'Cập nhật phân quyền nhóm id=4', 'bang=dm_phan_quyen; id=4', '127.0.0.1', 'SmokeTest', '2026-08-17 17:26:08'),
(71, '2026-08-17 17:26:08', 1, 'admin', 'HeThong', 'Cập nhật phân quyền nhóm id=4', 'bang=dm_phan_quyen; id=4', '127.0.0.1', 'SmokeTest', '2026-08-17 17:26:08'),
(72, '2026-08-17 17:26:08', 1, 'admin', 'HeThong', 'Smoke test log', 'bang=dm_nguoi_dung; id=99; noi dung test', '127.0.0.1', 'SmokeTest', '2026-08-17 17:26:08'),
(73, '2026-08-17 17:31:00', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-17 17:31:00'),
(74, '2026-08-17 17:35:43', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-17 17:35:43'),
(75, '2026-08-17 17:35:45', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-17 17:35:45'),
(76, '2026-08-17 17:35:47', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-17 17:35:47'),
(77, '2026-08-17 17:36:15', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-17 17:36:15'),
(78, '2026-08-17 20:11:10', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-17 20:11:10'),
(79, '2026-08-17 20:11:16', 5, NULL, 'HeThong', 'Đăng nhập: viewer', 'bang=dm_nguoi_dung; id=5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-17 20:11:16'),
(80, '2026-08-17 20:12:10', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-17 20:12:10'),
(81, '2026-08-17 20:13:04', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-17 20:13:04'),
(82, '2026-08-17 20:13:07', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-17 20:13:07'),
(83, '2026-08-17 20:13:09', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-17 20:13:09'),
(84, '2026-08-17 20:13:12', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-17 20:13:12'),
(85, '2026-08-17 20:13:26', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-17 20:13:26'),
(86, '2026-08-17 20:14:03', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'SmokeTest', '2026-08-17 20:14:03'),
(87, '2026-08-17 20:14:04', 1, NULL, 'HeThong', 'Đăng nhập thất bại: admin', 'bang=dm_nguoi_dung; id=1; Sai mật khẩu', '127.0.0.1', 'SmokeTest', '2026-08-17 20:14:04'),
(88, '2026-08-17 20:14:04', 6, NULL, 'HeThong', 'Đăng nhập thất bại: locked', 'bang=dm_nguoi_dung; id=6; Tài khoản bị khóa', '127.0.0.1', 'SmokeTest', '2026-08-17 20:14:04'),
(89, '2026-08-17 20:14:04', NULL, NULL, 'HeThong', 'Đăng nhập thất bại: nobody', 'bang=dm_nguoi_dung; Tài khoản không tồn tại', '127.0.0.1', 'SmokeTest', '2026-08-17 20:14:04'),
(90, '2026-08-17 20:14:04', 5, 'admin', 'HeThong', 'Đăng nhập: viewer', 'bang=dm_nguoi_dung; id=5', '127.0.0.1', 'SmokeTest', '2026-08-17 20:14:04'),
(91, '2026-08-17 20:14:04', 2, 'admin', 'HeThong', 'Đăng nhập: manager', 'bang=dm_nguoi_dung; id=2', '127.0.0.1', 'SmokeTest', '2026-08-17 20:14:04'),
(92, '2026-08-17 20:14:04', 1, 'admin', 'HeThong', 'Thêm người dùng: smoke_user', 'bang=dm_nguoi_dung; id=9', '127.0.0.1', 'SmokeTest', '2026-08-17 20:14:04'),
(93, '2026-08-17 20:14:04', 1, 'admin', 'HeThong', 'Sửa người dùng: smoke_user', 'bang=dm_nguoi_dung; id=9', '127.0.0.1', 'SmokeTest', '2026-08-17 20:14:04'),
(94, '2026-08-17 20:14:04', 1, 'admin', 'HeThong', 'Xóa tạm người dùng id=9', 'bang=dm_nguoi_dung; id=9', '127.0.0.1', 'SmokeTest', '2026-08-17 20:14:04'),
(95, '2026-08-17 20:14:04', 1, 'admin', 'HeThong', 'Khôi phục người dùng id=9', 'bang=dm_nguoi_dung; id=9', '127.0.0.1', 'SmokeTest', '2026-08-17 20:14:04'),
(96, '2026-08-17 20:14:04', 1, 'admin', 'HeThong', 'Xóa tạm người dùng id=9', 'bang=dm_nguoi_dung; id=9', '127.0.0.1', 'SmokeTest', '2026-08-17 20:14:04'),
(97, '2026-08-17 20:14:04', 1, 'admin', 'HeThong', 'Xóa vĩnh viễn người dùng id=9', 'bang=dm_nguoi_dung; id=9', '127.0.0.1', 'SmokeTest', '2026-08-17 20:14:04'),
(98, '2026-08-17 20:14:04', 1, 'admin', 'HeThong', 'Thêm nhóm TK: Nhom smoke', 'bang=dm_nhom_tai_khoan; id=8', '127.0.0.1', 'SmokeTest', '2026-08-17 20:14:04'),
(99, '2026-08-17 20:14:04', 1, 'admin', 'HeThong', 'Xóa tạm nhóm TK id=8', 'bang=dm_nhom_tai_khoan; id=8', '127.0.0.1', 'SmokeTest', '2026-08-17 20:14:04'),
(100, '2026-08-17 20:14:04', 1, 'admin', 'HeThong', 'Khôi phục nhóm TK id=8', 'bang=dm_nhom_tai_khoan; id=8', '127.0.0.1', 'SmokeTest', '2026-08-17 20:14:04'),
(101, '2026-08-17 20:14:04', 1, 'admin', 'HeThong', 'Xóa tạm nhóm TK id=8', 'bang=dm_nhom_tai_khoan; id=8', '127.0.0.1', 'SmokeTest', '2026-08-17 20:14:04'),
(102, '2026-08-17 20:14:04', 1, 'admin', 'HeThong', 'Xóa vĩnh viễn nhóm TK id=8', 'bang=dm_nhom_tai_khoan; id=8', '127.0.0.1', 'SmokeTest', '2026-08-17 20:14:04'),
(103, '2026-08-17 20:14:04', 1, 'admin', 'HeThong', 'Thêm form: Form smoke', 'bang=dm_danh_sach_form; id=8', '127.0.0.1', 'SmokeTest', '2026-08-17 20:14:04'),
(104, '2026-08-17 20:14:04', 1, 'admin', 'HeThong', 'Xóa tạm form id=8', 'bang=dm_danh_sach_form; id=8', '127.0.0.1', 'SmokeTest', '2026-08-17 20:14:04'),
(105, '2026-08-17 20:14:04', 1, 'admin', 'HeThong', 'Khôi phục form id=8', 'bang=dm_danh_sach_form; id=8', '127.0.0.1', 'SmokeTest', '2026-08-17 20:14:04'),
(106, '2026-08-17 20:14:04', 1, 'admin', 'HeThong', 'Xóa tạm form id=8', 'bang=dm_danh_sach_form; id=8', '127.0.0.1', 'SmokeTest', '2026-08-17 20:14:04'),
(107, '2026-08-17 20:14:04', 1, 'admin', 'HeThong', 'Xóa vĩnh viễn form id=8', 'bang=dm_danh_sach_form; id=8', '127.0.0.1', 'SmokeTest', '2026-08-17 20:14:04'),
(108, '2026-08-17 20:14:04', 1, 'admin', 'HeThong', 'Cập nhật phân quyền nhóm id=4', 'bang=dm_phan_quyen; id=4', '127.0.0.1', 'SmokeTest', '2026-08-17 20:14:04'),
(109, '2026-08-17 20:14:04', 1, 'admin', 'HeThong', 'Cập nhật phân quyền nhóm id=4', 'bang=dm_phan_quyen; id=4', '127.0.0.1', 'SmokeTest', '2026-08-17 20:14:04'),
(110, '2026-08-17 20:14:04', 1, 'admin', 'HeThong', 'Cập nhật phân quyền nhóm id=4', 'bang=dm_phan_quyen; id=4', '127.0.0.1', 'SmokeTest', '2026-08-17 20:14:04'),
(111, '2026-08-17 20:14:04', 1, 'admin', 'HeThong', 'Smoke test log', 'bang=dm_nguoi_dung; id=99; noi dung test', '127.0.0.1', 'SmokeTest', '2026-08-17 20:14:04'),
(112, '2026-08-17 21:07:14', 1, NULL, 'BaoGia', 'Import 27 hàng hóa vào gói thầu 5742/2026 (thêm tiếp)', 'bang=bg_hang_hoa; id=1', '0.0.0.0', NULL, '2026-08-17 21:07:14'),
(113, '2026-08-17 21:07:44', 1, NULL, 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5742/2026 (2 nhà thầu)', 'bang=bg_goi_thau; id=1', '0.0.0.0', NULL, '2026-08-17 21:07:44'),
(114, '2026-08-17 21:07:44', 1, NULL, 'BaoGia', 'Xuất chi tiết báo giá: Công ty TNHH Thiết bị Y tế An Phát', 'bang=bg_bao_gia; id=1', '0.0.0.0', NULL, '2026-08-17 21:07:44'),
(115, '2026-08-17 21:31:25', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.19041.6328', '2026-08-17 21:31:25'),
(116, '2026-08-17 21:31:55', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.19041.6328', '2026-08-17 21:31:55'),
(117, '2026-08-17 21:31:55', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Công ty TNHH Test Portal (MST 0400112233)', 'bang=bg_bao_gia; id=4', '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.19041.6328', '2026-08-17 21:31:55'),
(118, '2026-08-17 21:32:26', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.19041.6328', '2026-08-17 21:32:26'),
(119, '2026-08-17 21:32:26', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Test Flow (MST 0555666777)', 'bang=bg_bao_gia; id=5', '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.19041.6328', '2026-08-17 21:32:26'),
(120, '2026-08-17 21:32:26', 10, 'guest', 'BaoGia', 'Nhà thầu nộp báo giá: Cty Test Flow (1 dòng)', 'bang=bg_bao_gia; id=5', '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.19041.6328', '2026-08-17 21:32:26'),
(121, '2026-08-17 21:32:43', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.19041.6328', '2026-08-17 21:32:43'),
(122, '2026-08-17 21:35:40', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.19041.6328', '2026-08-17 21:35:40'),
(123, '2026-08-17 21:38:22', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'curl/8.18.0', '2026-08-17 21:38:22'),
(124, '2026-08-17 21:38:45', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'curl/8.18.0', '2026-08-17 21:38:45'),
(125, '2026-08-17 21:38:45', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty E2E Test (MST 0999888777)', 'bang=bg_bao_gia; id=6', '127.0.0.1', 'curl/8.18.0', '2026-08-17 21:38:45'),
(126, '2026-08-17 21:38:46', 10, 'guest', 'BaoGia', 'Nhà thầu nộp báo giá: Cty E2E Test (1 dòng)', 'bang=bg_bao_gia; id=6', '127.0.0.1', 'curl/8.18.0', '2026-08-17 21:38:46'),
(127, '2026-08-17 21:39:43', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'curl/8.18.0', '2026-08-17 21:39:43'),
(128, '2026-08-17 21:39:44', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Import Test (MST 0111222333)', 'bang=bg_bao_gia; id=7', '127.0.0.1', 'curl/8.18.0', '2026-08-17 21:39:44'),
(129, '2026-08-17 21:39:44', 10, 'guest', 'BaoGia', 'Import file báo giá Cty Import Test: 27 dòng, 27 dòng có giá', 'bang=bg_bao_gia; id=7', '127.0.0.1', 'curl/8.18.0', '2026-08-17 21:39:44'),
(130, '2026-08-17 21:39:59', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-17 21:39:59'),
(131, '2026-08-17 21:40:22', 1, 'admin', 'BaoGia', 'Xác nhận bản giấy báo giá: Cty Import Test (MST 0111222333)', 'bang=bg_bao_gia; id=7', '127.0.0.1', 'curl/8.18.0', '2026-08-17 21:40:22'),
(132, '2026-08-17 21:40:22', 1, 'admin', 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5742/2026 (3 nhà thầu)', 'bang=bg_goi_thau; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-17 21:40:22'),
(133, '2026-08-17 21:42:17', 1, NULL, 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5742/2026 (3 nhà thầu)', 'bang=bg_goi_thau; id=1', '0.0.0.0', NULL, '2026-08-17 21:42:17'),
(134, '2026-08-17 21:45:06', 1, NULL, 'BaoGia', 'Import 27 hàng hóa vào gói thầu 5742/2026 (thêm tiếp)', 'bang=bg_hang_hoa; id=1', '0.0.0.0', NULL, '2026-08-17 21:45:06'),
(135, '2026-08-17 22:28:04', 1, NULL, 'HeThong', 'Xóa 0 log cũ hơn 90 ngày', 'bang=dm_nhat_ky_he_thong', '0.0.0.0', NULL, '2026-08-17 22:28:04'),
(136, '2026-08-17 22:28:22', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-17 22:28:22'),
(137, '2026-08-17 22:29:29', 2, NULL, 'HeThong', 'Đăng nhập: manager', 'bang=dm_nguoi_dung; id=2', '127.0.0.1', 'curl/8.18.0', '2026-08-17 22:29:29'),
(138, '2026-08-17 22:29:30', 2, 'manager', 'BaoGia', 'Thêm gói thầu: TEST-manager', 'bang=bg_goi_thau; id=3', '127.0.0.1', 'curl/8.18.0', '2026-08-17 22:29:30'),
(139, '2026-08-17 22:29:30', 2, 'manager', 'BaoGia', 'Xác nhận bản giấy báo giá: Công ty TNHH Dược phẩm và TBYT Hoàng Long (MST 0312345678)', 'bang=bg_bao_gia; id=3', '127.0.0.1', 'curl/8.18.0', '2026-08-17 22:29:30'),
(140, '2026-08-17 22:29:30', 3, NULL, 'HeThong', 'Đăng nhập: staff01', 'bang=dm_nguoi_dung; id=3', '127.0.0.1', 'curl/8.18.0', '2026-08-17 22:29:30'),
(141, '2026-08-17 22:29:31', 5, NULL, 'HeThong', 'Đăng nhập: viewer', 'bang=dm_nguoi_dung; id=5', '127.0.0.1', 'curl/8.18.0', '2026-08-17 22:29:31'),
(142, '2026-08-17 22:31:03', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'curl/8.18.0', '2026-08-17 22:31:03'),
(143, '2026-08-17 22:31:03', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Kiem Tra Cuoi (MST 0777666555)', 'bang=bg_bao_gia; id=4', '127.0.0.1', 'curl/8.18.0', '2026-08-17 22:31:03'),
(144, '2026-08-17 22:31:03', 10, 'guest', 'BaoGia', 'Nhà thầu nộp báo giá: Cty Kiem Tra Cuoi (1 dòng)', 'bang=bg_bao_gia; id=4', '127.0.0.1', 'curl/8.18.0', '2026-08-17 22:31:03'),
(145, '2026-08-17 22:31:03', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-17 22:31:03'),
(146, '2026-08-17 22:31:03', 1, 'admin', 'BaoGia', 'Xác nhận bản giấy báo giá: Cty Kiem Tra Cuoi (MST 0777666555)', 'bang=bg_bao_gia; id=4', '127.0.0.1', 'curl/8.18.0', '2026-08-17 22:31:03'),
(147, '2026-08-17 22:31:03', 1, 'admin', 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5742/2026 (3 nhà thầu)', 'bang=bg_goi_thau; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-17 22:31:03'),
(148, '2026-08-17 22:38:28', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-17 22:38:28'),
(149, '2026-08-17 22:39:56', 1, 'admin', 'BaoGia', 'Sửa hàng hóa: Nẹp tạo hình bản sống cổ lối sau', 'bang=bg_hang_hoa; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-17 22:39:56'),
(150, '2026-08-17 22:40:54', 1, 'admin', 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5742/2026 (2 nhà thầu)', 'bang=bg_goi_thau; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-17 22:40:54'),
(151, '2026-08-17 22:41:23', 1, 'admin', 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5742/2026 (2 nhà thầu)', 'bang=bg_goi_thau; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-17 22:41:23'),
(152, '2026-08-17 22:42:12', 1, 'admin', 'BaoGia', 'Xuất chi tiết báo giá: Công ty TNHH Thiết bị Y tế An Phát', 'bang=bg_bao_gia; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-17 22:42:12'),
(153, '2026-08-17 22:43:09', 1, 'admin', 'BaoGia', 'Xác nhận bản giấy báo giá: Công ty TNHH Dược phẩm và TBYT Hoàng Long (MST 0312345678)', 'bang=bg_bao_gia; id=3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-17 22:43:09'),
(154, '2026-08-17 22:44:45', 1, 'admin', 'BaoGia', 'Nhà thầu tạo báo giá gói 5800/2026: Nhà thầu test (MST 1233212331)', 'bang=bg_bao_gia; id=5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-17 22:44:45'),
(155, '2026-08-17 22:46:05', 1, 'admin', 'BaoGia', 'Import file báo giá Nhà thầu test: 4 dòng, 0 dòng có giá', 'bang=bg_bao_gia; id=5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-17 22:46:05'),
(156, '2026-08-17 22:48:27', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 22:48:27'),
(157, '2026-08-17 22:48:38', 10, 'guest', 'HeThong', 'Đăng xuất', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 22:48:38'),
(158, '2026-08-17 23:17:54', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'curl/8.18.0', '2026-08-17 23:17:54'),
(159, '2026-08-17 23:18:14', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'curl/8.18.0', '2026-08-17 23:18:14'),
(160, '2026-08-17 23:18:14', 10, 'guest', 'BaoGia', 'Xuất chi tiết báo giá: Công ty TNHH Thiết bị Y tế An Phát', 'bang=bg_bao_gia; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-17 23:18:14'),
(161, '2026-08-17 23:19:19', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-17 23:19:19'),
(162, '2026-08-17 23:20:20', 1, 'admin', 'BaoGia', 'Thêm gói thầu: TG-001/2026', 'bang=bg_goi_thau; id=4', '127.0.0.1', 'curl/8.18.0', '2026-08-17 23:20:20'),
(163, '2026-08-17 23:20:21', 1, 'admin', 'BaoGia', 'Xóa tạm gói thầu: TG-001/2026', 'bang=bg_goi_thau; id=4', '127.0.0.1', 'curl/8.18.0', '2026-08-17 23:20:21'),
(164, '2026-08-17 23:22:02', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-17 23:22:02'),
(165, '2026-08-17 23:22:05', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'curl/8.18.0', '2026-08-17 23:22:05'),
(166, '2026-08-17 23:22:48', 1, NULL, 'BaoGia', 'Import 27 hàng hóa vào gói thầu 5742/2026 (thêm tiếp)', 'bang=bg_hang_hoa; id=1', '0.0.0.0', NULL, '2026-08-17 23:22:48'),
(167, '2026-08-17 23:23:17', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'curl/8.18.0', '2026-08-17 23:23:17'),
(168, '2026-08-17 23:23:17', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Kiem Tra 5 Muc (MST 0888777666)', 'bang=bg_bao_gia; id=4', '127.0.0.1', 'curl/8.18.0', '2026-08-17 23:23:17'),
(169, '2026-08-17 23:23:17', 10, 'guest', 'BaoGia', 'Nhà thầu nộp báo giá: Cty Kiem Tra 5 Muc (1 dòng)', 'bang=bg_bao_gia; id=4', '127.0.0.1', 'curl/8.18.0', '2026-08-17 23:23:17'),
(170, '2026-08-17 23:59:46', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-17 23:59:46'),
(171, '2026-08-18 00:01:35', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-18 00:01:35'),
(172, '2026-08-18 00:22:17', 1, 'admin', 'HeThong', 'Đăng xuất', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-18 00:22:17'),
(173, '2026-08-18 10:49:46', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-18 10:49:46'),
(174, '2026-08-18 11:05:05', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-18 11:05:05'),
(175, '2026-08-18 11:07:30', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-18 11:07:30'),
(176, '2026-08-18 16:59:19', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 16:59:19'),
(177, '2026-08-18 17:00:11', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-18 17:00:11'),
(178, '2026-08-18 17:09:47', 1, NULL, 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5742/2026 (2 nhà thầu)', 'bang=bg_goi_thau; id=1', '0.0.0.0', NULL, '2026-08-18 17:09:47'),
(179, '2026-08-18 17:16:48', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'curl/8.18.0', '2026-08-18 17:16:48'),
(180, '2026-08-18 17:17:49', 10, 'guest', 'BaoGia', 'Nhà thầu tải bản ký + tự xác nhận báo giá: Công ty TNHH Dược phẩm và TBYT Hoàng Long (MST 0312345678)', 'bang=bg_bao_gia; id=3', '127.0.0.1', 'curl/8.18.0', '2026-08-18 17:17:49'),
(181, '2026-08-18 17:18:30', 10, 'guest', 'BaoGia', 'Nhà thầu tải bản ký + tự xác nhận báo giá: Công ty TNHH Dược phẩm và TBYT Hoàng Long (MST 0312345678)', 'bang=bg_bao_gia; id=3', '127.0.0.1', 'curl/8.18.0', '2026-08-18 17:18:30'),
(182, '2026-08-18 17:18:51', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-18 17:18:51'),
(183, '2026-08-18 17:19:04', 1, NULL, 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5742/2026 (3 nhà thầu)', 'bang=bg_goi_thau; id=1', '0.0.0.0', NULL, '2026-08-18 17:19:04'),
(184, '2026-08-18 17:20:26', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-18 17:20:26'),
(185, '2026-08-18 17:20:28', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'curl/8.18.0', '2026-08-18 17:20:28'),
(186, '2026-08-18 20:18:02', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-18 20:18:02'),
(187, '2026-08-18 20:19:36', 1, 'admin', 'BaoGia', 'Xuất chi tiết báo giá: Công ty CP Vật tư Y tế Bình Minh', 'bang=bg_bao_gia; id=2', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-18 20:19:36'),
(188, '2026-08-18 20:23:41', 1, 'admin', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Nhà thầu mới (MST 1234567891)', 'bang=bg_bao_gia; id=5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-18 20:23:41'),
(189, '2026-08-18 20:24:31', 1, 'admin', 'BaoGia', 'Nhà thầu nộp báo giá: Nhà thầu mới (1 dòng)', 'bang=bg_bao_gia; id=5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-18 20:24:31'),
(190, '2026-08-18 20:26:45', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'curl/8.18.0', '2026-08-18 20:26:45'),
(191, '2026-08-18 20:27:11', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-18 20:27:11'),
(192, '2026-08-18 20:27:37', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'curl/8.18.0', '2026-08-18 20:27:37'),
(193, '2026-08-18 20:28:01', 1, 'admin', 'BaoGia', 'Xuất chi tiết báo giá: Nhà thầu mới', 'bang=bg_bao_gia; id=5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-18 20:28:01'),
(194, '2026-08-18 20:28:18', 10, 'guest', 'BaoGia', 'Nhà thầu tải bản ký + tự xác nhận báo giá: Cty Test Tra Cuu (MST 0999111222)', 'bang=bg_bao_gia; id=6', '127.0.0.1', 'curl/8.18.0', '2026-08-18 20:28:18'),
(195, '2026-08-18 20:29:28', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-18 20:29:28'),
(196, '2026-08-18 20:29:29', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'curl/8.18.0', '2026-08-18 20:29:29'),
(197, '2026-08-18 20:34:05', 1, 'admin', 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5742/2026 (2 nhà thầu)', 'bang=bg_goi_thau; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-18 20:34:05'),
(198, '2026-08-18 20:36:01', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'curl/8.18.0', '2026-08-18 20:36:01'),
(199, '2026-08-18 20:36:24', 10, 'guest', 'BaoGia', 'Nhà thầu tải bản ký + tự xác nhận báo giá: Công ty TNHH Thiết bị Y tế An Phát (MST 0101234567)', 'bang=bg_bao_gia; id=7', '127.0.0.1', 'curl/8.18.0', '2026-08-18 20:36:24'),
(200, '2026-08-18 20:37:36', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-18 20:37:36'),
(201, '2026-08-18 20:38:30', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-18 20:38:30'),
(202, '2026-08-18 20:38:31', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Test Luong Nop (MST 0555000111)', 'bang=bg_bao_gia; id=8', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-18 20:38:31'),
(203, '2026-08-18 20:38:35', 10, 'guest', 'BaoGia', 'Nhà thầu nộp báo giá: Cty Test Luong Nop (1 dòng)', 'bang=bg_bao_gia; id=8', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-18 20:38:35'),
(204, '2026-08-18 20:39:13', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-18 20:39:13'),
(205, '2026-08-18 20:39:33', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-18 20:39:33'),
(206, '2026-08-18 20:40:39', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148', '2026-08-18 20:40:39'),
(207, '2026-08-18 20:41:41', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-18 20:41:41'),
(208, '2026-08-18 20:54:24', 1, 'admin', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: 123 (MST 0101234517)', 'bang=bg_bao_gia; id=9', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-18 20:54:24'),
(209, '2026-08-18 20:54:50', 1, 'admin', 'BaoGia', 'Nhà thầu nộp báo giá: 123 (1 dòng)', 'bang=bg_bao_gia; id=9', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-18 20:54:50'),
(210, '2026-08-18 20:57:45', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-18 20:57:45'),
(211, '2026-08-18 20:58:11', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-18 20:58:11'),
(212, '2026-08-18 20:59:13', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-18 20:59:13'),
(213, '2026-08-18 21:01:55', 1, NULL, 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5742/2026 (2 nhà thầu)', 'bang=bg_goi_thau; id=1', '0.0.0.0', NULL, '2026-08-18 21:01:55'),
(214, '2026-08-18 21:04:44', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-18 21:04:44'),
(215, '2026-08-18 21:04:46', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Kiem Tra Giao Dien (MST 0777111222)', 'bang=bg_bao_gia; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-18 21:04:46'),
(216, '2026-08-18 21:05:44', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-18 21:05:44'),
(217, '2026-08-18 21:05:45', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Kiem Tra Giao Dien (MST 0777111222)', 'bang=bg_bao_gia; id=11', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-18 21:05:45'),
(218, '2026-08-18 21:06:29', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-18 21:06:29'),
(219, '2026-08-18 21:06:30', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Kiem Tra Giao Dien (MST 0777111222)', 'bang=bg_bao_gia; id=12', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-18 21:06:30'),
(220, '2026-08-18 21:07:13', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-18 21:07:13'),
(221, '2026-08-18 21:07:18', 10, 'guest', 'BaoGia', 'Nhà thầu tải bản ký + tự xác nhận báo giá: Công ty TNHH Thiết bị Y tế An Phát (MST 0101234567)', 'bang=bg_bao_gia; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-18 21:07:18'),
(222, '2026-08-18 21:08:42', 1, NULL, 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5742/2026 (2 nhà thầu)', 'bang=bg_goi_thau; id=1', '0.0.0.0', NULL, '2026-08-18 21:08:42'),
(223, '2026-08-18 21:08:42', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-18 21:08:42'),
(224, '2026-08-18 21:11:10', 1, 'admin', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Nhà thầu test 123 (MST 0101234527)', 'bang=bg_bao_gia; id=13', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-18 21:11:10'),
(225, '2026-08-18 21:11:36', 1, 'admin', 'BaoGia', 'Nhà thầu nộp báo giá: Nhà thầu test 123 (1 dòng)', 'bang=bg_bao_gia; id=13', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-18 21:11:36'),
(226, '2026-08-18 21:11:55', 1, 'admin', 'BaoGia', 'Nhà thầu tải bản ký + tự xác nhận báo giá: Nhà thầu test 123 (MST 0101234527)', 'bang=bg_bao_gia; id=13', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-18 21:11:55'),
(227, '2026-08-18 21:12:45', 1, 'admin', 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5742/2026 (3 nhà thầu)', 'bang=bg_goi_thau; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-18 21:12:45'),
(228, '2026-08-18 21:39:18', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-18 21:39:18'),
(229, '2026-08-18 21:40:04', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-18 21:40:04'),
(230, '2026-08-18 21:41:21', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-18 21:41:21'),
(231, '2026-08-18 21:42:09', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-18 21:42:09'),
(232, '2026-08-18 21:42:58', 5, NULL, 'HeThong', 'Đăng nhập: viewer', 'bang=dm_nguoi_dung; id=5', '127.0.0.1', 'curl/8.18.0', '2026-08-18 21:42:58'),
(233, '2026-08-18 21:43:24', 11, NULL, 'HeThong', 'Đăng nhập: noperm', 'bang=dm_nguoi_dung; id=11', '127.0.0.1', 'curl/8.18.0', '2026-08-18 21:43:24'),
(234, '2026-08-18 21:44:17', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-18 21:44:17'),
(235, '2026-08-18 22:24:38', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-18 22:24:38'),
(236, '2026-08-18 22:24:45', 1, 'admin', 'QuanLyFile', 'Xóa file mồ côi: 0000000000_file-mo-coi-test.pdf', 'bang=file', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-18 22:24:45'),
(237, '2026-08-18 22:25:31', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-18 22:25:31'),
(238, '2026-08-18 22:25:31', 3, NULL, 'HeThong', 'Đăng nhập: staff01', 'bang=dm_nguoi_dung; id=3', '127.0.0.1', 'curl/8.18.0', '2026-08-18 22:25:31'),
(239, '2026-08-18 22:25:53', 1, 'admin', 'QuanLyFile', 'Xóa file bản ký của Công ty CP Vật tư Y tế Bình Minh (MST 0209876543), file: BanKyGoc_2.png — báo giá trở lại Chờ xác nhận', 'bang=bg_bao_gia; id=2', '127.0.0.1', 'curl/8.18.0', '2026-08-18 22:25:53'),
(240, '2026-08-19 07:02:13', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-19 07:02:13'),
(241, '2026-08-19 07:03:06', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-19 07:03:06'),
(242, '2026-08-19 07:03:53', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-19 07:03:53'),
(243, '2026-08-19 07:04:31', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'curl/8.18.0', '2026-08-19 07:04:31'),
(244, '2026-08-19 07:04:31', 10, 'guest', 'BaoGia', 'Nhà thầu tải bản ký + tự xác nhận báo giá: Công ty TNHH Thiết bị Y tế An Phát (MST 0101234567)', 'bang=bg_bao_gia; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-19 07:04:31'),
(245, '2026-08-19 07:04:59', 10, 'guest', 'BaoGia', 'Nhà thầu tải bản ký + tự xác nhận báo giá: Công ty TNHH Thiết bị Y tế An Phát (MST 0101234567)', 'bang=bg_bao_gia; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-19 07:04:59'),
(246, '2026-08-19 07:04:59', 1, 'admin', 'QuanLyFile', 'Xóa file bản ký của Công ty TNHH Thiết bị Y tế An Phát (MST 0101234567), file: BanKy_ThayThe.png — báo giá trở lại Chờ xác nhận', 'bang=bg_file; id=3', '127.0.0.1', 'curl/8.18.0', '2026-08-19 07:04:59'),
(247, '2026-08-19 07:05:33', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-19 07:05:33'),
(248, '2026-08-19 07:07:23', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-19 07:07:23'),
(249, '2026-08-19 07:07:37', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-19 07:07:37'),
(250, '2026-08-19 07:15:06', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 07:15:06'),
(251, '2026-08-19 07:23:22', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-19 07:23:22');
INSERT INTO `dm_nhat_ky_he_thong` (`id`, `thoi_gian`, `nguoi_dung_id`, `tai_khoan`, `module`, `hanh_dong`, `noi_dung`, `ip_address`, `user_agent`, `ngay_tao`) VALUES
(252, '2026-08-19 07:30:39', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'curl/8.18.0', '2026-08-19 07:30:39'),
(253, '2026-08-19 07:31:24', 1, NULL, 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5742/2026 (3 nhà thầu)', 'bang=bg_goi_thau; id=1', '0.0.0.0', NULL, '2026-08-19 07:31:24'),
(254, '2026-08-19 07:31:54', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-19 07:31:54'),
(255, '2026-08-19 07:32:07', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-19 07:32:07'),
(256, '2026-08-19 07:36:51', 1, 'admin', 'BaoGia', 'Nhà thầu tải bản ký + tự xác nhận báo giá: Công ty CP Vật tư Y tế Bình Minh (MST 0209876543)', 'bang=bg_bao_gia; id=2', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-19 07:36:51'),
(257, '2026-08-19 07:37:27', 1, 'admin', 'BaoGia', 'Xác nhận bản giấy báo giá: Công ty TNHH Dược phẩm và TBYT Hoàng Long (MST 0312345678)', 'bang=bg_bao_gia; id=3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-19 07:37:27'),
(258, '2026-08-19 07:38:17', 1, 'admin', 'BaoGia', 'Bỏ xác nhận báo giá: Công ty TNHH Thiết bị Y tế An Phát', 'bang=bg_bao_gia; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-19 07:38:17'),
(259, '2026-08-19 07:38:20', 1, 'admin', 'BaoGia', 'Bỏ xác nhận báo giá: Công ty TNHH Dược phẩm và TBYT Hoàng Long', 'bang=bg_bao_gia; id=3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-19 07:38:20'),
(260, '2026-08-19 09:52:18', 1, NULL, 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5742/2026 (2 nhà thầu)', 'bang=bg_goi_thau; id=1', '0.0.0.0', NULL, '2026-08-19 09:52:18'),
(261, '2026-08-19 09:54:05', 1, NULL, 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5742/2026 (2 nhà thầu)', 'bang=bg_goi_thau; id=1', '0.0.0.0', NULL, '2026-08-19 09:54:05'),
(262, '2026-08-19 09:54:47', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-19 09:54:47'),
(263, '2026-08-19 09:54:47', 1, 'admin', 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5742/2026 (2 nhà thầu)', 'bang=bg_goi_thau; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-19 09:54:47'),
(264, '2026-08-19 16:46:05', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-19 16:46:05'),
(265, '2026-08-19 20:52:22', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-19 20:52:22'),
(266, '2026-08-19 20:53:39', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-19 20:53:39'),
(267, '2026-08-19 20:54:15', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-19 20:54:15'),
(268, '2026-08-19 20:55:04', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-19 20:55:04'),
(269, '2026-08-19 20:56:17', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-19 20:56:17'),
(270, '2026-08-19 20:57:24', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-19 20:57:24'),
(271, '2026-08-19 20:58:03', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-19 20:58:03'),
(272, '2026-08-19 20:59:45', 1, NULL, 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5742/2026 (2 nhà thầu)', 'bang=bg_goi_thau; id=1', '0.0.0.0', NULL, '2026-08-19 20:59:45'),
(273, '2026-08-19 21:21:27', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-19 21:21:27'),
(274, '2026-08-19 21:21:55', 1, 'admin', 'BaoGia', 'Xác nhận bản giấy báo giá: Công ty TNHH Dược phẩm và TBYT Hoàng Long (MST 0312345678)', 'bang=bg_bao_gia; id=3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-19 21:21:55'),
(275, '2026-08-19 21:21:57', 1, 'admin', 'BaoGia', 'Xác nhận bản giấy báo giá: Công ty TNHH Thiết bị Y tế An Phát (MST 0101234567)', 'bang=bg_bao_gia; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-19 21:21:57'),
(276, '2026-08-19 21:29:30', 1, NULL, 'BaoGia', 'Xóa tạm hàng hóa: Nẹp tạo hình bản sống cổ lối sau', 'bang=bg_hang_hoa; id=1', '0.0.0.0', NULL, '2026-08-19 21:29:30'),
(277, '2026-08-19 21:29:30', 1, NULL, 'BaoGia', 'Đổi trạng thái gói thầu 5742/2026 → Nháp', 'bang=bg_goi_thau; id=1', '0.0.0.0', NULL, '2026-08-19 21:29:30'),
(278, '2026-08-19 21:29:30', 1, NULL, 'BaoGia', 'Đổi trạng thái gói thầu 5742/2026 → Đang mở', 'bang=bg_goi_thau; id=1', '0.0.0.0', NULL, '2026-08-19 21:29:30'),
(279, '2026-08-19 21:30:01', 1, NULL, 'BaoGia', 'Xóa tạm hàng hóa: Nẹp tạo hình bản sống cổ lối sau', 'bang=bg_hang_hoa; id=1', '0.0.0.0', NULL, '2026-08-19 21:30:01'),
(280, '2026-08-19 21:30:01', 1, NULL, 'BaoGia', 'Khôi phục hàng hóa: Nẹp tạo hình bản sống cổ lối sau', 'bang=bg_hang_hoa; id=1', '0.0.0.0', NULL, '2026-08-19 21:30:01'),
(281, '2026-08-19 21:30:55', 1, NULL, 'HeThong', 'Đăng nhập thất bại: admin', 'bang=dm_nguoi_dung; id=1; Sai mật khẩu', '127.0.0.1', 'curl/8.18.0', '2026-08-19 21:30:55'),
(282, '2026-08-19 21:30:55', 1, NULL, 'HeThong', 'Đăng nhập thất bại: admin', 'bang=dm_nguoi_dung; id=1; Sai mật khẩu', '127.0.0.1', 'curl/8.18.0', '2026-08-19 21:30:55'),
(283, '2026-08-19 21:30:55', 1, NULL, 'HeThong', 'Đăng nhập thất bại: admin', 'bang=dm_nguoi_dung; id=1; Sai mật khẩu', '127.0.0.1', 'curl/8.18.0', '2026-08-19 21:30:55'),
(284, '2026-08-19 21:30:56', 1, NULL, 'HeThong', 'Đăng nhập thất bại: admin', 'bang=dm_nguoi_dung; id=1; Sai mật khẩu', '127.0.0.1', 'curl/8.18.0', '2026-08-19 21:30:56'),
(285, '2026-08-19 21:30:56', 1, NULL, 'HeThong', 'Đăng nhập thất bại: admin', 'bang=dm_nguoi_dung; id=1; Sai mật khẩu', '127.0.0.1', 'curl/8.18.0', '2026-08-19 21:30:56'),
(286, '2026-08-19 21:30:56', 1, NULL, 'HeThong', 'Đăng nhập thất bại: admin', 'bang=dm_nguoi_dung; id=1; Sai mật khẩu', '127.0.0.1', 'curl/8.18.0', '2026-08-19 21:30:56'),
(287, '2026-08-19 21:30:56', 1, NULL, 'HeThong', 'Đăng nhập thất bại: admin', 'bang=dm_nguoi_dung; id=1; Sai mật khẩu', '127.0.0.1', 'curl/8.18.0', '2026-08-19 21:30:56'),
(288, '2026-08-19 22:13:28', 1, NULL, 'BaoGia', 'Xóa tạm hàng hóa: Hộp đựng mũi khoan', 'bang=bg_hang_hoa; id=28', '0.0.0.0', NULL, '2026-08-19 22:13:28'),
(289, '2026-08-19 22:13:28', 1, NULL, 'BaoGia', 'Khôi phục hàng hóa: Hộp đựng mũi khoan', 'bang=bg_hang_hoa; id=28', '0.0.0.0', NULL, '2026-08-19 22:13:28'),
(290, '2026-08-19 22:16:08', 1, NULL, 'HeThong', 'Đăng nhập thất bại: admin', 'bang=dm_nguoi_dung; id=1; Sai mật khẩu', '127.0.0.1', 'curl/8.18.0', '2026-08-19 22:16:08'),
(291, '2026-08-19 22:16:09', 1, NULL, 'HeThong', 'Đăng nhập thất bại: admin', 'bang=dm_nguoi_dung; id=1; Sai mật khẩu', '127.0.0.1', 'curl/8.18.0', '2026-08-19 22:16:09'),
(292, '2026-08-19 22:16:09', 1, NULL, 'HeThong', 'Đăng nhập thất bại: admin', 'bang=dm_nguoi_dung; id=1; Sai mật khẩu', '127.0.0.1', 'curl/8.18.0', '2026-08-19 22:16:09'),
(293, '2026-08-19 22:16:09', 1, NULL, 'HeThong', 'Đăng nhập thất bại: admin', 'bang=dm_nguoi_dung; id=1; Sai mật khẩu', '127.0.0.1', 'curl/8.18.0', '2026-08-19 22:16:09'),
(294, '2026-08-19 22:16:09', 1, NULL, 'HeThong', 'Đăng nhập thất bại: admin', 'bang=dm_nguoi_dung; id=1; Sai mật khẩu', '127.0.0.1', 'curl/8.18.0', '2026-08-19 22:16:09'),
(295, '2026-08-19 22:16:10', NULL, NULL, 'HeThong', 'Đăng nhập bị chặn (quá số lần): admin', 'bang=dm_nguoi_dung', '127.0.0.1', 'curl/8.18.0', '2026-08-19 22:16:10'),
(296, '2026-08-19 22:16:10', NULL, NULL, 'HeThong', 'Đăng nhập bị chặn (quá số lần): admin', 'bang=dm_nguoi_dung', '127.0.0.1', 'curl/8.18.0', '2026-08-19 22:16:10'),
(297, '2026-08-19 22:16:10', NULL, NULL, 'HeThong', 'Đăng nhập bị chặn (quá số lần): admin', 'bang=dm_nguoi_dung', '127.0.0.1', 'curl/8.18.0', '2026-08-19 22:16:10'),
(298, '2026-08-19 22:16:35', 1, NULL, 'HeThong', 'Xóa 0 log cũ hơn 90 ngày', 'bang=dm_nhat_ky_he_thong', '0.0.0.0', NULL, '2026-08-19 22:16:35'),
(299, '2026-08-19 22:16:56', 1, NULL, 'HeThong', 'Xóa 0 log cũ hơn 90 ngày', 'bang=dm_nhat_ky_he_thong', '0.0.0.0', NULL, '2026-08-19 22:16:56'),
(300, '2026-08-19 22:17:43', 1, NULL, 'BaoGia', 'Đổi trạng thái gói thầu 5742/2026 → Đã đóng', 'bang=bg_goi_thau; id=1', '0.0.0.0', NULL, '2026-08-19 22:17:43'),
(301, '2026-08-19 22:17:43', 1, NULL, 'BaoGia', 'Đổi trạng thái gói thầu 5742/2026 → Đang mở', 'bang=bg_goi_thau; id=1', '0.0.0.0', NULL, '2026-08-19 22:17:43'),
(302, '2026-08-19 22:22:17', 1, NULL, 'BaoGia', 'Bỏ xác nhận báo giá: Công ty TNHH Thiết bị Y tế An Phát', 'bang=bg_bao_gia; id=1', '0.0.0.0', NULL, '2026-08-19 22:22:17'),
(303, '2026-08-19 22:22:17', 1, NULL, 'BaoGia', 'Xác nhận bản giấy báo giá: Công ty TNHH Thiết bị Y tế An Phát (MST 0101234567)', 'bang=bg_bao_gia; id=1', '0.0.0.0', NULL, '2026-08-19 22:22:17'),
(305, '2026-08-19 22:25:03', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-19 22:25:03'),
(306, '2026-08-19 22:25:05', 1, 'admin', 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5742/2026 (4 nhà thầu)', 'bang=bg_goi_thau; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-19 22:25:05'),
(307, '2026-08-20 22:16:46', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-20 22:16:46'),
(308, '2026-08-20 22:26:30', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-20 22:26:30'),
(309, '2026-08-20 22:27:35', 1, 'admin', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Nhà thầu test (MST 0101231267)', 'bang=bg_bao_gia; id=16', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-20 22:27:35'),
(310, '2026-08-20 23:13:16', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'curl/8.18.0', '2026-08-20 23:13:16'),
(311, '2026-08-20 23:13:17', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'curl/8.18.0', '2026-08-20 23:13:17'),
(312, '2026-08-20 23:16:13', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-20 23:16:13'),
(313, '2026-08-20 23:16:15', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Test 4 Buoc (MST 0555444333)', 'bang=bg_bao_gia; id=4', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-20 23:16:15'),
(314, '2026-08-20 23:16:22', 10, 'guest', 'BaoGia', 'Nhà thầu nộp báo giá: Cty Test 4 Buoc (1 dòng)', 'bang=bg_bao_gia; id=4', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-20 23:16:22'),
(315, '2026-08-20 23:17:28', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-20 23:17:28'),
(316, '2026-08-20 23:17:30', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Test 4 Buoc (MST 0555444333)', 'bang=bg_bao_gia; id=5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-20 23:17:30'),
(317, '2026-08-20 23:17:37', 10, 'guest', 'BaoGia', 'Nhà thầu nộp báo giá: Cty Test 4 Buoc (1 dòng)', 'bang=bg_bao_gia; id=5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-20 23:17:37'),
(318, '2026-08-20 23:18:25', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-20 23:18:25'),
(319, '2026-08-20 23:19:02', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-20 23:19:02'),
(320, '2026-08-20 23:19:04', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Test 4 Buoc (MST 0555444333)', 'bang=bg_bao_gia; id=6', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-20 23:19:04'),
(321, '2026-08-20 23:19:09', 10, 'guest', 'BaoGia', 'Nhà thầu nộp báo giá: Cty Test 4 Buoc (1 dòng)', 'bang=bg_bao_gia; id=6', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-20 23:19:09'),
(322, '2026-08-20 23:20:20', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-20 23:20:20'),
(323, '2026-08-20 23:21:09', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-20 23:21:09'),
(324, '2026-08-20 23:23:58', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-20 23:23:58'),
(325, '2026-08-20 23:24:00', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Test 4 Buoc (MST 0555444333)', 'bang=bg_bao_gia; id=7', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-20 23:24:00'),
(326, '2026-08-20 23:24:07', 10, 'guest', 'BaoGia', 'Nhà thầu nộp báo giá: Cty Test 4 Buoc (1 dòng)', 'bang=bg_bao_gia; id=7', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-20 23:24:07'),
(327, '2026-08-20 23:24:32', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-20 23:24:32'),
(328, '2026-08-20 23:29:05', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-20 23:29:05'),
(329, '2026-08-20 23:29:08', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Test 4 Buoc (MST 0555444333)', 'bang=bg_bao_gia; id=8', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-20 23:29:08'),
(330, '2026-08-20 23:29:15', 10, 'guest', 'BaoGia', 'Nhà thầu nộp báo giá: Cty Test 4 Buoc (1 dòng)', 'bang=bg_bao_gia; id=8', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-20 23:29:15'),
(331, '2026-08-20 23:31:12', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-20 23:31:12'),
(332, '2026-08-20 23:31:15', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Test 4 Buoc (MST 0555444333)', 'bang=bg_bao_gia; id=9', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-20 23:31:15'),
(333, '2026-08-20 23:31:22', 10, 'guest', 'BaoGia', 'Nhà thầu nộp báo giá: Cty Test 4 Buoc (1 dòng)', 'bang=bg_bao_gia; id=9', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-20 23:31:22'),
(334, '2026-08-20 23:31:45', 1, NULL, 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5742/2026 (2 nhà thầu)', 'bang=bg_goi_thau; id=1', '0.0.0.0', NULL, '2026-08-20 23:31:45'),
(335, '2026-08-20 23:31:45', 1, NULL, 'BaoGia', 'Xuất chi tiết báo giá: Công ty TNHH Thiết bị Y tế An Phát', 'bang=bg_bao_gia; id=1', '0.0.0.0', NULL, '2026-08-20 23:31:45'),
(336, '2026-08-21 07:02:19', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-21 07:02:19'),
(337, '2026-08-21 07:11:36', 1, 'admin', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Nhà thầu mới (MST 1234267891)', 'bang=bg_bao_gia; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-21 07:11:36'),
(338, '2026-08-21 07:22:43', 1, 'admin', 'BaoGia', 'Nhà thầu nộp báo giá: Nhà thầu mới (1 dòng)', 'bang=bg_bao_gia; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-21 07:22:43'),
(339, '2026-08-21 07:23:17', 1, 'admin', 'BaoGia', 'Xuất chi tiết báo giá: Nhà thầu mới', 'bang=bg_bao_gia; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-21 07:23:17'),
(340, '2026-08-21 07:37:16', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 07:37:16'),
(341, '2026-08-21 07:37:19', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Test 5 Yeu Cau (MST 0555444333)', 'bang=bg_bao_gia; id=11', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 07:37:19'),
(342, '2026-08-21 07:38:16', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 07:38:16'),
(343, '2026-08-21 07:38:40', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 07:38:40'),
(344, '2026-08-21 07:39:05', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 07:39:05'),
(345, '2026-08-21 07:39:35', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 07:39:35'),
(346, '2026-08-21 07:39:38', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Test 5 Yeu Cau (MST 0555444333)', 'bang=bg_bao_gia; id=12', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 07:39:38'),
(347, '2026-08-21 07:39:58', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 07:39:58'),
(348, '2026-08-21 07:40:01', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Test 5 Yeu Cau (MST 0555444333)', 'bang=bg_bao_gia; id=13', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 07:40:01'),
(349, '2026-08-21 07:40:44', NULL, NULL, 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Import (MST 0999888777)', 'bang=bg_bao_gia; id=14', '0.0.0.0', NULL, '2026-08-21 07:40:44'),
(350, '2026-08-21 07:40:54', NULL, NULL, 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Import (MST 0999888777)', 'bang=bg_bao_gia; id=15', '0.0.0.0', NULL, '2026-08-21 07:40:54'),
(351, '2026-08-21 07:40:54', NULL, NULL, 'BaoGia', 'Nhà thầu import file báo giá: Cty Import — 5 dòng', 'bang=bg_bao_gia; id=15', '0.0.0.0', NULL, '2026-08-21 07:40:54'),
(352, '2026-08-21 07:40:54', NULL, NULL, 'BaoGia', 'Nhà thầu import file báo giá: Cty Import — 5 dòng', 'bang=bg_bao_gia; id=15', '0.0.0.0', NULL, '2026-08-21 07:40:54'),
(353, '2026-08-21 08:08:10', 1, 'admin', 'BaoGia', 'Cập nhật thông tin báo giá: Nhà thầu mới (không có gì đổi)', 'bang=bg_bao_gia; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-21 08:08:10'),
(354, '2026-08-21 08:12:56', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 08:12:56'),
(355, '2026-08-21 08:12:59', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Test Toolbar (MST 0555444333)', 'bang=bg_bao_gia; id=16', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 08:12:59'),
(356, '2026-08-21 08:13:18', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 08:13:18'),
(357, '2026-08-21 08:13:21', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Test 5 Yeu Cau (MST 0555444333)', 'bang=bg_bao_gia; id=17', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 08:13:21'),
(358, '2026-08-21 08:37:25', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 08:37:25'),
(359, '2026-08-21 08:37:28', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Test Ghi Chu (MST 0555444333)', 'bang=bg_bao_gia; id=18', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 08:37:28'),
(360, '2026-08-21 08:37:47', 1, 'admin', 'BaoGia', 'Xuất chi tiết báo giá: Nhà thầu mới', 'bang=bg_bao_gia; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-21 08:37:47'),
(361, '2026-08-21 15:56:42', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-21 15:56:42'),
(362, '2026-08-21 15:57:12', 1, 'admin', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Nhà thầu test 123 (MST 1234568911)', 'bang=bg_bao_gia; id=19', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-21 15:57:12'),
(363, '2026-08-21 15:58:10', 1, 'admin', 'BaoGia', 'Nhà thầu nộp báo giá: Nhà thầu test 123 (1 dòng)', 'bang=bg_bao_gia; id=19', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-21 15:58:10'),
(364, '2026-08-21 16:00:29', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 16:00:29'),
(365, '2026-08-21 16:00:32', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Test Ghi Chu (MST 0555444333)', 'bang=bg_bao_gia; id=20', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 16:00:32'),
(366, '2026-08-21 16:00:50', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 16:00:50'),
(367, '2026-08-21 16:00:53', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Test Ghi Chu (MST 0555444333)', 'bang=bg_bao_gia; id=21', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 16:00:53'),
(368, '2026-08-21 16:01:02', 1, 'admin', 'BaoGia', 'Nhà thầu nộp báo giá: Nhà thầu test 123 (1 dòng)', 'bang=bg_bao_gia; id=19', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-21 16:01:02'),
(369, '2026-08-21 16:22:23', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 16:22:23'),
(370, '2026-08-21 16:23:16', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 16:23:16'),
(371, '2026-08-21 16:23:59', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 16:23:59'),
(372, '2026-08-21 17:11:04', 1, NULL, 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5742/2026 (2 nhà thầu)', 'bang=bg_goi_thau; id=1', '0.0.0.0', NULL, '2026-08-21 17:11:04'),
(373, '2026-08-21 17:11:04', 1, NULL, 'BaoGia', 'Xuất chi tiết báo giá: Công ty TNHH Thiết bị Y tế An Phát', 'bang=bg_bao_gia; id=1', '0.0.0.0', NULL, '2026-08-21 17:11:04'),
(374, '2026-08-21 17:12:07', 1, NULL, 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5742/2026 (2 nhà thầu)', 'bang=bg_goi_thau; id=1', '0.0.0.0', NULL, '2026-08-21 17:12:07'),
(375, '2026-08-21 17:12:07', 1, NULL, 'BaoGia', 'Xuất chi tiết báo giá: Công ty TNHH Thiết bị Y tế An Phát', 'bang=bg_bao_gia; id=1', '0.0.0.0', NULL, '2026-08-21 17:12:07'),
(376, '2026-08-21 17:12:42', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 17:12:42'),
(377, '2026-08-21 17:12:45', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Test Cot 12 (MST 0555444333)', 'bang=bg_bao_gia; id=22', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 17:12:45'),
(378, '2026-08-21 17:13:40', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 17:13:40'),
(379, '2026-08-21 17:13:44', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Test Cot 12 (MST 0555444333)', 'bang=bg_bao_gia; id=23', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 17:13:44'),
(380, '2026-08-21 17:14:02', NULL, NULL, 'BaoGia', 'Nhà thầu import file báo giá: Cty Test Cot 12 — 5 dòng', 'bang=bg_bao_gia; id=23', '0.0.0.0', NULL, '2026-08-21 17:14:02'),
(381, '2026-08-21 17:14:02', NULL, NULL, 'BaoGia', 'Nhà thầu import file báo giá: Cty Test Cot 12 — 5 dòng', 'bang=bg_bao_gia; id=23', '0.0.0.0', NULL, '2026-08-21 17:14:02'),
(382, '2026-08-21 17:32:05', 1, 'admin', 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5742/2026 (2 nhà thầu)', 'bang=bg_goi_thau; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-21 17:32:05'),
(383, '2026-08-21 17:32:14', 1, NULL, 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5742/2026 (2 nhà thầu)', 'bang=bg_goi_thau; id=1', '0.0.0.0', NULL, '2026-08-21 17:32:14'),
(384, '2026-08-21 17:40:09', 1, NULL, 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5742/2026 (2 nhà thầu)', 'bang=bg_goi_thau; id=1', '0.0.0.0', NULL, '2026-08-21 17:40:09'),
(385, '2026-08-21 17:40:18', 1, NULL, 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5742/2026 (2 nhà thầu)', 'bang=bg_goi_thau; id=1', '0.0.0.0', NULL, '2026-08-21 17:40:18'),
(386, '2026-08-21 17:40:49', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 17:40:49'),
(387, '2026-08-21 19:29:11', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-21 19:29:11'),
(388, '2026-08-21 19:29:25', 1, 'admin', 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5742/2026 (2 nhà thầu)', 'bang=bg_goi_thau; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-21 19:29:25'),
(389, '2026-08-21 20:13:05', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 20:13:05'),
(390, '2026-08-21 20:13:08', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Test 5 Buoc (MST 0555444333)', 'bang=bg_bao_gia; id=24', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 20:13:08'),
(391, '2026-08-21 20:14:09', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 20:14:09'),
(392, '2026-08-21 20:14:12', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Test 5 Buoc (MST 0555444333)', 'bang=bg_bao_gia; id=25', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 20:14:12'),
(393, '2026-08-21 20:15:14', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 20:15:14'),
(394, '2026-08-21 20:16:18', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 20:16:18'),
(395, '2026-08-21 20:16:21', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Test 5 Buoc (MST 0555444333)', 'bang=bg_bao_gia; id=26', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 20:16:21'),
(396, '2026-08-21 20:16:27', 10, 'guest', 'BaoGia', 'Nhà thầu tải catalog: Cty Test 5 Buoc (MST 0555444333)', 'bang=bg_bao_gia; id=26', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 20:16:27'),
(397, '2026-08-21 22:32:49', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-21 22:32:49'),
(398, '2026-08-21 22:32:59', 1, 'admin', 'BaoGia', 'Sửa gói thầu: 5800/2026', 'bang=bg_goi_thau; id=2', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-21 22:32:59'),
(399, '2026-08-21 22:33:17', 1, 'admin', 'BaoGia', 'Nhà thầu tạo báo giá gói 5800/2026: Nhà thầu test4444 (MST 1234567891)', 'bang=bg_bao_gia; id=27', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-21 22:33:17'),
(400, '2026-08-21 22:33:40', 1, 'admin', 'BaoGia', 'Nhà thầu nộp báo giá: Nhà thầu test4444 (1 dòng)', 'bang=bg_bao_gia; id=27', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-21 22:33:40'),
(401, '2026-08-21 22:33:49', 1, 'admin', 'BaoGia', 'Xuất chi tiết báo giá: Nhà thầu test4444', 'bang=bg_bao_gia; id=27', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-21 22:33:49'),
(402, '2026-08-21 22:35:05', 1, 'admin', 'BaoGia', 'Nhà thầu tải bản ký + tự xác nhận báo giá: Nhà thầu test4444 (MST 1234567891)', 'bang=bg_bao_gia; id=27', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-21 22:35:05'),
(403, '2026-08-21 22:40:14', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 22:40:14'),
(404, '2026-08-21 22:40:17', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Test B4B5 (MST 0555444333)', 'bang=bg_bao_gia; id=28', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 22:40:17'),
(405, '2026-08-21 22:40:23', 10, 'guest', 'BaoGia', 'Nhà thầu nộp báo giá: Cty Test B4B5 (1 dòng)', 'bang=bg_bao_gia; id=28', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 22:40:23'),
(406, '2026-08-21 22:40:29', 10, 'guest', 'BaoGia', 'Nhà thầu tải bản ký + tự xác nhận báo giá: Cty Test B4B5 (MST 0555444333)', 'bang=bg_bao_gia; id=28', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 22:40:29'),
(407, '2026-08-21 22:40:58', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 22:40:58'),
(408, '2026-08-21 22:41:07', 10, 'guest', 'BaoGia', 'Nhà thầu tải bản ký + tự xác nhận báo giá: Công ty TNHH Dược phẩm và TBYT Hoàng Long (MST 0312345678)', 'bang=bg_bao_gia; id=3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 22:41:07'),
(409, '2026-08-21 22:56:26', 1, NULL, 'HeThong', 'Đăng nhập thất bại: admin', 'bang=dm_nguoi_dung; id=1; Sai mật khẩu', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 22:56:26'),
(410, '2026-08-21 22:57:17', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 22:57:17'),
(411, '2026-08-21 22:57:23', 1, 'admin', 'HeThong', 'Đăng xuất', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 22:57:23'),
(412, '2026-08-21 22:57:25', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 22:57:25'),
(413, '2026-08-21 22:59:19', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 22:59:19'),
(414, '2026-08-21 22:59:25', 1, 'admin', 'HeThong', 'Đăng xuất', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 22:59:25'),
(415, '2026-08-21 22:59:26', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 22:59:26'),
(416, '2026-08-21 23:08:59', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 23:08:59'),
(417, '2026-08-21 23:09:02', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Test Khoa (MST 0555444333)', 'bang=bg_bao_gia; id=29', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 23:09:02'),
(418, '2026-08-21 23:09:08', 10, 'guest', 'BaoGia', 'Nhà thầu nộp báo giá: Cty Test Khoa (1 dòng)', 'bang=bg_bao_gia; id=29', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 23:09:08'),
(419, '2026-08-21 23:09:13', 10, 'guest', 'BaoGia', 'Nhà thầu tải bản ký + tự xác nhận báo giá: Cty Test Khoa (MST 0555444333)', 'bang=bg_bao_gia; id=29', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 23:09:13'),
(420, '2026-08-21 23:09:20', 10, 'guest', 'BaoGia', 'Nhà thầu tải catalog: Cty Test Khoa (MST 0555444333)', 'bang=bg_bao_gia; id=29', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 23:09:20'),
(421, '2026-08-21 23:09:24', 10, 'guest', 'BaoGia', 'Nhà thầu hoàn thành báo giá: Cty Test Khoa (MST 0555444333)', 'bang=bg_bao_gia; id=29', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 23:09:24'),
(422, '2026-08-21 23:10:18', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 23:10:18'),
(423, '2026-08-21 23:10:21', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Test Khoa (MST 0555444333)', 'bang=bg_bao_gia; id=30', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 23:10:21'),
(424, '2026-08-21 23:10:27', 10, 'guest', 'BaoGia', 'Nhà thầu nộp báo giá: Cty Test Khoa (1 dòng)', 'bang=bg_bao_gia; id=30', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 23:10:27'),
(425, '2026-08-21 23:10:32', 10, 'guest', 'BaoGia', 'Nhà thầu tải bản ký + tự xác nhận báo giá: Cty Test Khoa (MST 0555444333)', 'bang=bg_bao_gia; id=30', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 23:10:32'),
(426, '2026-08-21 23:10:39', 10, 'guest', 'BaoGia', 'Nhà thầu tải catalog: Cty Test Khoa (MST 0555444333)', 'bang=bg_bao_gia; id=30', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 23:10:39'),
(427, '2026-08-21 23:10:43', 10, 'guest', 'BaoGia', 'Nhà thầu hoàn thành báo giá: Cty Test Khoa (MST 0555444333)', 'bang=bg_bao_gia; id=30', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 23:10:43'),
(428, '2026-08-21 23:11:16', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 23:11:16'),
(429, '2026-08-21 23:13:05', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 23:13:05'),
(430, '2026-08-21 23:21:34', 1, 'admin', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Nhà thầu mới (MST 1234567891)', 'bang=bg_bao_gia; id=31', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-21 23:21:34'),
(431, '2026-08-21 23:21:54', 1, 'admin', 'BaoGia', 'Nhà thầu nộp báo giá: Nhà thầu mới (1 dòng)', 'bang=bg_bao_gia; id=31', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-21 23:21:54'),
(432, '2026-08-21 23:22:21', 1, 'admin', 'BaoGia', 'Nhà thầu tải bản ký + tự xác nhận báo giá: Nhà thầu mới (MST 1234567891)', 'bang=bg_bao_gia; id=31', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-21 23:22:21'),
(433, '2026-08-21 23:24:03', 1, 'admin', 'BaoGia', 'Nhà thầu tải catalog: Nhà thầu mới (MST 1234567891)', 'bang=bg_bao_gia; id=31', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-21 23:24:03'),
(434, '2026-08-21 23:27:22', 1, 'admin', 'BaoGia', 'Nhà thầu hoàn thành báo giá: Nhà thầu mới (MST 1234567891)', 'bang=bg_bao_gia; id=31', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-21 23:27:22'),
(435, '2026-08-21 23:32:17', 1, 'admin', 'BaoGia', 'Nhà thầu tải bản ký + tự xác nhận báo giá: Nhà thầu test4444 (MST 1234567891)', 'bang=bg_bao_gia; id=27', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-21 23:32:17'),
(436, '2026-08-21 23:34:02', 1, 'admin', 'HeThong', 'Đăng xuất', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-21 23:34:02'),
(437, '2026-08-21 23:34:09', 10, NULL, 'HeThong', 'Đăng nhập thất bại: guest', 'bang=dm_nguoi_dung; id=10; Sai mật khẩu', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-21 23:34:09'),
(438, '2026-08-21 23:34:14', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-21 23:34:14'),
(439, '2026-08-21 23:34:48', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-21 23:34:48'),
(440, '2026-08-21 23:40:21', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 23:40:21'),
(441, '2026-08-21 23:40:25', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Test Excel (MST 0555444333)', 'bang=bg_bao_gia; id=32', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 23:40:25'),
(442, '2026-08-21 23:40:31', 10, 'guest', 'BaoGia', 'Nhà thầu nộp báo giá: Cty Test Excel (1 dòng)', 'bang=bg_bao_gia; id=32', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 23:40:31'),
(443, '2026-08-21 23:40:51', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 23:40:51'),
(444, '2026-08-21 23:40:54', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Test Excel (MST 0555444333)', 'bang=bg_bao_gia; id=33', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 23:40:54'),
(445, '2026-08-21 23:41:00', 10, 'guest', 'BaoGia', 'Nhà thầu nộp báo giá: Cty Test Excel (1 dòng)', 'bang=bg_bao_gia; id=33', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 23:41:00'),
(446, '2026-08-21 23:41:07', 10, 'guest', 'BaoGia', 'Nhà thầu tải Excel chỉ dẫn: Cty Test Excel (MST 0555444333)', 'bang=bg_bao_gia; id=33', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 23:41:07'),
(447, '2026-08-21 23:41:42', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-21 23:41:42'),
(448, '2026-08-22 07:06:42', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-22 07:06:42'),
(449, '2026-08-22 07:08:43', 1, 'admin', 'BaoGia', 'Nhà thầu tạo báo giá gói 5800/2026: Nhà thầu test 123 (MST 1234567441)', 'bang=bg_bao_gia; id=34', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-22 07:08:43'),
(450, '2026-08-22 07:09:09', 1, 'admin', 'BaoGia', 'Nhà thầu nộp báo giá: Nhà thầu test 123 (1 dòng)', 'bang=bg_bao_gia; id=34', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-22 07:09:09'),
(451, '2026-08-22 07:09:32', 1, 'admin', 'BaoGia', 'Nhà thầu tải bản ký + tự xác nhận báo giá: Nhà thầu test 123 (MST 1234567441)', 'bang=bg_bao_gia; id=34', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-22 07:09:32'),
(452, '2026-08-22 07:10:05', 1, 'admin', 'BaoGia', 'Nhà thầu tải catalog: Nhà thầu test 123 (MST 1234567441)', 'bang=bg_bao_gia; id=34', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-22 07:10:05');
INSERT INTO `dm_nhat_ky_he_thong` (`id`, `thoi_gian`, `nguoi_dung_id`, `tai_khoan`, `module`, `hanh_dong`, `noi_dung`, `ip_address`, `user_agent`, `ngay_tao`) VALUES
(453, '2026-08-22 07:10:13', 1, 'admin', 'BaoGia', 'Nhà thầu hoàn thành báo giá: Nhà thầu test 123 (MST 1234567441)', 'bang=bg_bao_gia; id=34', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-22 07:10:13'),
(454, '2026-08-22 07:13:38', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-22 07:13:38'),
(455, '2026-08-22 07:14:21', 10, 'guest', 'HeThong', 'Đăng xuất', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-22 07:14:21'),
(456, '2026-08-22 07:14:24', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-22 07:14:24'),
(457, '2026-08-22 07:23:29', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 07:23:29'),
(458, '2026-08-22 07:25:04', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 07:25:04'),
(459, '2026-08-22 07:25:30', 1, 'admin', 'BaoGia', 'Xóa tạm báo giá: Nhà thầu test 123', 'bang=bg_bao_gia; id=34', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-22 07:25:30'),
(460, '2026-08-22 07:25:51', 1, 'admin', 'BaoGia', 'Từ chối báo giá: Nhà thầu mới — thiếu catolog', 'bang=bg_bao_gia; id=31', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-22 07:25:51'),
(461, '2026-08-22 07:25:59', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 07:25:59'),
(462, '2026-08-22 07:26:26', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 07:26:26'),
(463, '2026-08-22 07:26:55', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 07:26:55'),
(464, '2026-08-22 07:27:51', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 07:27:51'),
(465, '2026-08-22 07:28:21', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 07:28:21'),
(466, '2026-08-22 07:29:32', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 07:29:32'),
(467, '2026-08-22 07:30:18', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 07:30:18'),
(468, '2026-08-22 07:31:46', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 07:31:46'),
(469, '2026-08-22 07:32:15', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 07:32:15'),
(470, '2026-08-22 07:33:37', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 07:33:37'),
(471, '2026-08-22 07:34:36', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 07:34:36'),
(472, '2026-08-22 07:35:38', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 07:35:38'),
(473, '2026-08-22 07:36:05', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 07:36:05'),
(474, '2026-08-22 07:36:33', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 07:36:33'),
(475, '2026-08-22 07:37:30', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 07:37:30'),
(476, '2026-08-22 07:37:59', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 07:37:59'),
(477, '2026-08-22 07:38:47', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 07:38:47'),
(478, '2026-08-22 09:39:50', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-22 09:39:50'),
(479, '2026-08-22 09:44:16', 1, 'admin', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Nhà thầu test4444 (MST 1234517891)', 'bang=bg_bao_gia; id=35', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-22 09:44:16'),
(480, '2026-08-22 09:44:28', 1, 'admin', 'BaoGia', 'Nhà thầu nộp báo giá: Nhà thầu test4444 (1 dòng)', 'bang=bg_bao_gia; id=35', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-22 09:44:28'),
(481, '2026-08-22 09:44:45', 1, 'admin', 'BaoGia', 'Nhà thầu tải bản ký + tự xác nhận báo giá: Nhà thầu test4444 (MST 1234517891)', 'bang=bg_bao_gia; id=35', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-22 09:44:45'),
(482, '2026-08-22 09:45:01', 1, 'admin', 'BaoGia', 'Nhà thầu tải catalog: Nhà thầu test4444 (MST 1234517891)', 'bang=bg_bao_gia; id=35', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-22 09:45:01'),
(483, '2026-08-22 10:01:17', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 10:01:17'),
(484, '2026-08-22 10:01:20', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Test Duyet (MST 0555444333)', 'bang=bg_bao_gia; id=36', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 10:01:20'),
(485, '2026-08-22 10:01:26', 10, 'guest', 'BaoGia', 'Nhà thầu nộp báo giá: Cty Test Duyet (1 dòng)', 'bang=bg_bao_gia; id=36', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 10:01:26'),
(486, '2026-08-22 10:01:31', 10, 'guest', 'BaoGia', 'Nhà thầu tải bản ký: Cty Test Duyet (MST 0555444333)', 'bang=bg_bao_gia; id=36', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 10:01:31'),
(487, '2026-08-22 10:01:36', 10, 'guest', 'BaoGia', 'Nhà thầu tải catalog: Cty Test Duyet (MST 0555444333)', 'bang=bg_bao_gia; id=36', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 10:01:36'),
(488, '2026-08-22 10:01:41', 10, 'guest', 'BaoGia', 'Nhà thầu tải bảng chỉ dẫn: Cty Test Duyet (MST 0555444333)', 'bang=bg_bao_gia; id=36', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 10:01:41'),
(489, '2026-08-22 10:01:45', 10, 'guest', 'BaoGia', 'Nhà thầu hoàn thành 5 bước → báo giá chuyển ĐÃ XÁC NHẬN: Cty Test Duyet (MST 0555444333)', 'bang=bg_bao_gia; id=36', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 10:01:45'),
(490, '2026-08-22 10:02:29', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 10:02:29'),
(491, '2026-08-22 10:23:16', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 10:23:16'),
(492, '2026-08-22 13:31:33', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-22 13:31:33'),
(493, '2026-08-22 13:31:58', 1, 'admin', 'BaoGia', 'Thêm gói thầu: qr123', 'bang=bg_goi_thau; id=3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-22 13:31:58'),
(494, '2026-08-22 13:38:41', NULL, NULL, 'BaoGia', 'Import 2 hàng hóa vào gói thầu TEST-RT/2026 (thêm tiếp)', 'bang=bg_hang_hoa; id=11', '0.0.0.0', NULL, '2026-08-22 13:38:41'),
(495, '2026-08-22 13:39:42', NULL, NULL, 'BaoGia', 'Import 2 hàng hóa vào gói thầu RT/2026 (thêm tiếp)', 'bang=bg_hang_hoa; id=13', '0.0.0.0', NULL, '2026-08-22 13:39:42'),
(496, '2026-08-22 13:41:18', NULL, NULL, 'BaoGia', 'Import 2 hàng hóa vào gói thầu RT2/2026 (thêm tiếp)', 'bang=bg_hang_hoa; id=15', '0.0.0.0', NULL, '2026-08-22 13:41:18'),
(497, '2026-08-22 13:41:54', NULL, NULL, 'BaoGia', 'Import 2 hàng hóa vào gói thầu RT3/2026 (thêm tiếp)', 'bang=bg_hang_hoa; id=16', '0.0.0.0', NULL, '2026-08-22 13:41:54'),
(498, '2026-08-22 13:42:49', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 13:42:49'),
(499, '2026-08-22 14:01:40', NULL, NULL, 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Test Quyen (MST 0555444333)', 'bang=bg_bao_gia; id=37', '0.0.0.0', NULL, '2026-08-22 14:01:40'),
(500, '2026-08-22 14:01:40', NULL, NULL, 'BaoGia', 'Cập nhật thông tin báo giá: Cty SUA | Tên công ty: Cty Test Quyen → Cty SUA', 'bang=bg_bao_gia; id=37', '0.0.0.0', NULL, '2026-08-22 14:01:40'),
(501, '2026-08-22 14:01:40', NULL, NULL, 'BaoGia', 'Nhà thầu nộp báo giá: Cty SUA (1 dòng)', 'bang=bg_bao_gia; id=37', '0.0.0.0', NULL, '2026-08-22 14:01:40'),
(502, '2026-08-22 14:02:20', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 14:02:20'),
(503, '2026-08-22 14:02:23', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Trong Han (MST 0555444333)', 'bang=bg_bao_gia; id=38', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 14:02:23'),
(504, '2026-08-22 14:02:54', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 14:02:54'),
(505, '2026-08-22 14:02:57', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty KT (MST 0555444333)', 'bang=bg_bao_gia; id=39', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 14:02:57'),
(506, '2026-08-22 14:03:29', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 14:03:29'),
(507, '2026-08-22 14:03:32', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty HT (MST 0555444333)', 'bang=bg_bao_gia; id=40', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 14:03:32'),
(508, '2026-08-22 14:05:32', 1, 'admin', 'BaoGia', 'Import 2 hàng hóa vào gói thầu qr123 (thêm tiếp)', 'bang=bg_hang_hoa; id=3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-22 14:05:32'),
(509, '2026-08-22 14:06:27', 1, 'admin', 'BaoGia', 'Nhà thầu tạo báo giá gói 5800/2026: 4eeeeeee (MST 0101234567)', 'bang=bg_bao_gia; id=41', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-22 14:06:27'),
(510, '2026-08-22 14:06:47', 1, 'admin', 'BaoGia', 'Nhà thầu nộp báo giá: 4eeeeeee (1 dòng)', 'bang=bg_bao_gia; id=41', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-22 14:06:47'),
(511, '2026-08-22 14:10:12', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 14:10:12'),
(512, '2026-08-22 14:10:15', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Repro (MST 0555444333)', 'bang=bg_bao_gia; id=42', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 14:10:15'),
(513, '2026-08-22 14:10:21', 10, 'guest', 'BaoGia', 'Nhà thầu nộp báo giá: Cty Repro (1 dòng)', 'bang=bg_bao_gia; id=42', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 14:10:21'),
(514, '2026-08-22 14:10:39', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 14:10:39'),
(515, '2026-08-22 14:10:42', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Repro (MST 0555444333)', 'bang=bg_bao_gia; id=43', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 14:10:42'),
(516, '2026-08-22 14:10:49', 10, 'guest', 'BaoGia', 'Nhà thầu nộp báo giá: Cty Repro (1 dòng)', 'bang=bg_bao_gia; id=43', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 14:10:49'),
(517, '2026-08-22 14:11:09', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 14:11:09'),
(518, '2026-08-22 14:11:12', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Repro (MST 0555444333)', 'bang=bg_bao_gia; id=44', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 14:11:12'),
(519, '2026-08-22 14:11:18', 10, 'guest', 'BaoGia', 'Nhà thầu nộp báo giá: Cty Repro (1 dòng)', 'bang=bg_bao_gia; id=44', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 14:11:18'),
(520, '2026-08-22 14:11:44', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 14:11:44'),
(521, '2026-08-22 14:12:08', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 14:12:08'),
(522, '2026-08-22 14:12:14', 10, 'guest', 'BaoGia', 'Xuất chi tiết báo giá: Công ty TNHH Thiết bị Y tế An Phát', 'bang=bg_bao_gia; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-22 14:12:14'),
(523, '2026-08-24 14:27:40', NULL, NULL, 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Loc (MST 0555444333)', 'bang=bg_bao_gia; id=45', '0.0.0.0', NULL, '2026-08-24 14:27:40'),
(524, '2026-08-24 17:20:43', NULL, NULL, 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Loc (MST 0555444333)', 'bang=bg_bao_gia; id=46', '0.0.0.0', NULL, '2026-08-24 17:20:43'),
(525, '2026-08-24 17:21:27', NULL, NULL, 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Rong (MST 0555444333)', 'bang=bg_bao_gia; id=47', '0.0.0.0', NULL, '2026-08-24 17:21:27'),
(526, '2026-08-24 19:23:37', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-24 19:23:37'),
(527, '2026-08-24 19:24:18', 1, 'admin', 'BaoGia', 'Nhà thầu tạo báo giá gói 5800/2026: Nhà thầu mới (MST 1234867891)', 'bang=bg_bao_gia; id=48', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-24 19:24:18'),
(528, '2026-08-24 19:24:32', 1, 'admin', 'BaoGia', 'Nhà thầu nộp báo giá: Nhà thầu mới (1 dòng)', 'bang=bg_bao_gia; id=48', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-24 19:24:32'),
(529, '2026-08-25 17:21:17', NULL, NULL, 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty Catalog (MST 0555444333)', 'bang=bg_bao_gia; id=49', '0.0.0.0', NULL, '2026-08-25 17:21:17'),
(530, '2026-08-26 21:00:04', 3, NULL, 'HeThong', 'Đăng nhập: staff01', 'bang=dm_nguoi_dung; id=3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-26 21:00:04'),
(531, '2026-08-26 21:00:09', 3, 'staff01', 'HeThong', 'Đăng xuất', 'bang=dm_nguoi_dung; id=3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-26 21:00:09'),
(532, '2026-08-26 21:00:09', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-26 21:00:09'),
(533, '2026-08-26 21:01:12', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-26 21:01:12'),
(534, '2026-08-26 21:02:07', 3, NULL, 'HeThong', 'Đăng nhập: staff01', 'bang=dm_nguoi_dung; id=3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-26 21:02:07'),
(535, '2026-08-26 21:02:11', 3, 'staff01', 'HeThong', 'Đăng xuất', 'bang=dm_nguoi_dung; id=3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-26 21:02:11'),
(536, '2026-08-26 21:02:12', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-26 21:02:12'),
(537, '2026-08-26 21:03:27', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-26 21:03:27'),
(538, '2026-08-26 21:04:03', 3, NULL, 'HeThong', 'Đăng nhập: staff01', 'bang=dm_nguoi_dung; id=3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-26 21:04:03'),
(539, '2026-08-26 21:04:08', 3, 'staff01', 'HeThong', 'Đăng xuất', 'bang=dm_nguoi_dung; id=3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-26 21:04:08'),
(540, '2026-08-26 21:04:08', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-26 21:04:08'),
(541, '2026-08-26 21:04:14', 1, 'admin', 'QuyenGoiThau', 'Đổi phân quyền xem gói thầu 5742/2026: 1 người dùng', 'bang=bg_goi_thau; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-26 21:04:14'),
(542, '2026-08-26 21:04:16', 1, 'admin', 'HeThong', 'Đăng xuất', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-26 21:04:16'),
(543, '2026-08-26 21:04:17', 3, NULL, 'HeThong', 'Đăng nhập: staff01', 'bang=dm_nguoi_dung; id=3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-26 21:04:17'),
(544, '2026-08-26 21:04:23', 3, 'staff01', 'HeThong', 'Đăng xuất', 'bang=dm_nguoi_dung; id=3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-26 21:04:23'),
(545, '2026-08-26 21:04:23', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-26 21:04:23'),
(546, '2026-08-26 21:09:32', 3, NULL, 'HeThong', 'Đăng nhập: staff01', 'bang=dm_nguoi_dung; id=3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-26 21:09:32'),
(547, '2026-08-26 21:15:11', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-26 21:15:11'),
(548, '2026-08-26 21:17:41', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-26 21:17:41'),
(549, '2026-08-26 21:25:41', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-26 21:25:41'),
(550, '2026-08-26 21:25:46', 1, 'admin', 'QuyenGoiThau', 'Đổi phân quyền xem gói thầu qr123: 1 người dùng', 'bang=bg_goi_thau; id=3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-26 21:25:46'),
(551, '2026-08-26 21:26:21', 3, NULL, 'HeThong', 'Đăng nhập: staff01', 'bang=dm_nguoi_dung; id=3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-26 21:26:21'),
(552, '2026-08-26 21:30:52', 1, 'admin', 'QuyenGoiThau', 'Đổi phân quyền xem gói thầu qr123: 1 người dùng', 'bang=bg_goi_thau; id=3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-26 21:30:52'),
(553, '2026-08-26 21:31:00', 1, 'admin', 'QuyenGoiThau', 'Đổi phân quyền xem gói thầu qr123: 2 người dùng', 'bang=bg_goi_thau; id=3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-26 21:31:00'),
(554, '2026-08-26 21:33:34', 3, NULL, 'HeThong', 'Đăng nhập: staff01', 'bang=dm_nguoi_dung; id=3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-26 21:33:34'),
(555, '2026-08-26 21:36:40', 3, NULL, 'HeThong', 'Đăng nhập: staff01', 'bang=dm_nguoi_dung; id=3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-26 21:36:40'),
(556, '2026-08-26 21:37:24', 3, NULL, 'HeThong', 'Đăng nhập: staff01', 'bang=dm_nguoi_dung; id=3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-26 21:37:24'),
(557, '2026-08-26 21:37:42', 3, NULL, 'HeThong', 'Đăng nhập: staff01', 'bang=dm_nguoi_dung; id=3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-26 21:37:42'),
(558, '2026-08-26 21:39:07', 3, NULL, 'HeThong', 'Đăng nhập: staff01', 'bang=dm_nguoi_dung; id=3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-26 21:39:07'),
(559, '2026-08-26 21:39:54', 3, NULL, 'HeThong', 'Đăng nhập: staff01', 'bang=dm_nguoi_dung; id=3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-26 21:39:54'),
(560, '2026-08-26 21:39:56', 3, 'staff01', 'HeThong', 'Đăng xuất', 'bang=dm_nguoi_dung; id=3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-26 21:39:56'),
(561, '2026-08-26 21:39:57', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-08-26 21:39:57'),
(562, '2026-09-06 08:03:19', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 08:03:19'),
(563, '2026-09-06 08:04:10', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 08:04:10'),
(564, '2026-09-06 08:04:32', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 08:04:32'),
(565, '2026-09-06 08:04:35', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cong ty TEST luong moi (MST 9988776655)', 'bang=bg_bao_gia; id=50', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 08:04:35'),
(566, '2026-09-06 08:04:37', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 08:04:37'),
(567, '2026-09-06 08:05:36', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 08:05:36'),
(568, '2026-09-06 08:05:39', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cong ty TEST luong moi (MST 9988776655)', 'bang=bg_bao_gia; id=51', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 08:05:39'),
(569, '2026-09-06 08:06:24', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 08:06:24'),
(570, '2026-09-06 08:06:27', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cong ty TEST luong moi (MST 9988776655)', 'bang=bg_bao_gia; id=52', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 08:06:27'),
(571, '2026-09-06 08:06:34', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 08:06:34'),
(572, '2026-09-06 08:07:08', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 08:07:08'),
(573, '2026-09-06 08:07:11', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cong ty TEST luong moi (MST 9988776655)', 'bang=bg_bao_gia; id=53', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 08:07:11'),
(574, '2026-09-06 08:07:15', 10, 'guest', 'BaoGia', 'Nhà thầu nộp báo giá: Cong ty TEST luong moi (5 dòng)', 'bang=bg_bao_gia; id=53', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 08:07:15'),
(575, '2026-09-06 08:07:16', 10, 'guest', 'BaoGia', 'Nhà thầu hoàn thành 5 bước → báo giá CHỜ DUYỆT: Cong ty TEST luong moi (MST 9988776655)', 'bang=bg_bao_gia; id=53', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 08:07:16'),
(576, '2026-09-06 08:07:16', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 08:07:16'),
(577, '2026-09-06 08:07:19', 1, 'admin', 'BaoGia', 'Xác nhận bản giấy báo giá: Cong ty TEST luong moi (MST 9988776655)', 'bang=bg_bao_gia; id=53', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 08:07:19'),
(578, '2026-09-06 08:09:25', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 08:09:25'),
(579, '2026-09-06 08:09:28', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cong ty xem banner (MST 7766554433)', 'bang=bg_bao_gia; id=55', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 08:09:28'),
(580, '2026-09-06 08:10:06', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 08:10:06'),
(581, '2026-09-06 08:10:24', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 08:10:24'),
(582, '2026-09-06 08:12:22', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 08:12:22'),
(583, '2026-09-06 08:12:37', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 08:12:37'),
(584, '2026-09-06 08:13:16', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 08:13:16'),
(585, '2026-09-06 09:33:55', 1, NULL, 'HeThong', 'Xóa 0 log cũ hơn 90 ngày', 'bang=dm_nhat_ky_he_thong', '0.0.0.0', NULL, '2026-09-06 09:33:55'),
(586, '2026-09-06 09:34:18', 1, NULL, 'HeThong', 'Xóa 0 log cũ hơn 90 ngày', 'bang=dm_nhat_ky_he_thong', '0.0.0.0', NULL, '2026-09-06 09:34:18'),
(587, '2026-09-06 09:34:39', 1, NULL, 'HeThong', 'Xóa 0 log cũ hơn 90 ngày', 'bang=dm_nhat_ky_he_thong', '0.0.0.0', NULL, '2026-09-06 09:34:39'),
(588, '2026-09-06 09:40:55', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 09:40:55'),
(589, '2026-09-06 09:41:42', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 09:41:42'),
(590, '2026-09-06 09:41:45', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty tai file trong phien (MST 5544332211)', 'bang=bg_bao_gia; id=56', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 09:41:45'),
(591, '2026-09-06 09:41:48', 10, 'guest', 'BaoGia', 'Nhà thầu nộp báo giá: Cty tai file trong phien (5 dòng)', 'bang=bg_bao_gia; id=56', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 09:41:48'),
(592, '2026-09-06 09:41:48', 10, 'guest', 'BaoGia', 'Xuất chi tiết báo giá: Cty tai file trong phien', 'bang=bg_bao_gia; id=56', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 09:41:48'),
(593, '2026-09-06 09:41:59', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 09:41:59'),
(594, '2026-09-06 09:42:02', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty tai file trong phien (MST 5544332211)', 'bang=bg_bao_gia; id=57', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 09:42:02'),
(595, '2026-09-06 09:42:05', 10, 'guest', 'BaoGia', 'Nhà thầu nộp báo giá: Cty tai file trong phien (5 dòng)', 'bang=bg_bao_gia; id=57', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 09:42:05'),
(596, '2026-09-06 09:42:05', 10, 'guest', 'BaoGia', 'Xuất chi tiết báo giá: Cty tai file trong phien', 'bang=bg_bao_gia; id=57', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 09:42:05'),
(597, '2026-09-06 20:59:41', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-06 20:59:41'),
(598, '2026-09-09 20:32:49', 1, NULL, 'BaoGia', 'Import 2 bộ / 3 hàng hóa vào gói thầu TEST-BO_DUN-203249 (thêm tiếp)', 'bang=bg_hang_hoa; id=19', '0.0.0.0', NULL, '2026-09-09 20:32:49'),
(599, '2026-09-09 20:32:49', 1, NULL, 'BaoGia', 'Import 1 bộ / 4 hàng hóa vào gói thầu TEST-HE_THO-203249 (thêm tiếp)', 'bang=bg_hang_hoa; id=20', '0.0.0.0', NULL, '2026-09-09 20:32:49'),
(600, '2026-09-09 20:32:49', 1, NULL, 'BaoGia', 'Import 2 bộ / 4 hàng hóa vào gói thầu TEST-VAT_TU-203249 (thêm tiếp)', 'bang=bg_hang_hoa; id=21', '0.0.0.0', NULL, '2026-09-09 20:32:49'),
(601, '2026-09-09 20:34:48', 1, NULL, 'BaoGia', 'Import 2 bộ / 3 hàng hóa vào gói thầu T12-BO_DU-203448 (thêm tiếp)', 'bang=bg_hang_hoa; id=22', '0.0.0.0', NULL, '2026-09-09 20:34:48'),
(602, '2026-09-09 20:34:48', 1, NULL, 'BaoGia', 'Import 1 bộ / 4 hàng hóa vào gói thầu T12-HE_TH-203448 (thêm tiếp)', 'bang=bg_hang_hoa; id=23', '0.0.0.0', NULL, '2026-09-09 20:34:48'),
(603, '2026-09-09 20:34:48', 1, NULL, 'BaoGia', 'Import 2 bộ / 4 hàng hóa vào gói thầu T12-VAT_T-203448 (thêm tiếp)', 'bang=bg_hang_hoa; id=24', '0.0.0.0', NULL, '2026-09-09 20:34:48'),
(604, '2026-09-10 06:00:34', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-10 06:00:34'),
(605, '2026-09-10 06:00:50', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-10 06:00:50'),
(606, '2026-09-10 06:04:37', 1, NULL, 'BaoGia', 'Import 2 bộ / 3 hàng hóa vào gói thầu TM12-BO_DU-060436 (thêm tiếp)', 'bang=bg_hang_hoa; id=25', '0.0.0.0', NULL, '2026-09-10 06:04:37'),
(607, '2026-09-10 06:04:37', 1, NULL, 'BaoGia', 'Nhà thầu import file báo giá: Cty test bo_dung_cu — 3 dòng', 'bang=bg_bao_gia; id=58', '0.0.0.0', NULL, '2026-09-10 06:04:37'),
(608, '2026-09-10 06:04:37', 1, NULL, 'BaoGia', 'Nhà thầu import file báo giá: Cty test bo_dung_cu — 3 dòng', 'bang=bg_bao_gia; id=58', '0.0.0.0', NULL, '2026-09-10 06:04:37'),
(609, '2026-09-10 06:04:37', 1, NULL, 'BaoGia', 'Import 1 bộ / 4 hàng hóa vào gói thầu TM12-HE_TH-060437 (thêm tiếp)', 'bang=bg_hang_hoa; id=26', '0.0.0.0', NULL, '2026-09-10 06:04:37'),
(610, '2026-09-10 06:04:37', 1, NULL, 'BaoGia', 'Nhà thầu import file báo giá: Cty test he_thong_tbyt — 4 dòng', 'bang=bg_bao_gia; id=59', '0.0.0.0', NULL, '2026-09-10 06:04:37'),
(611, '2026-09-10 06:04:37', 1, NULL, 'BaoGia', 'Nhà thầu import file báo giá: Cty test he_thong_tbyt — 4 dòng', 'bang=bg_bao_gia; id=59', '0.0.0.0', NULL, '2026-09-10 06:04:37'),
(612, '2026-09-10 06:04:37', 1, NULL, 'BaoGia', 'Import 2 bộ / 4 hàng hóa vào gói thầu TM12-VAT_T-060437 (thêm tiếp)', 'bang=bg_hang_hoa; id=27', '0.0.0.0', NULL, '2026-09-10 06:04:37'),
(613, '2026-09-10 06:04:37', 1, NULL, 'BaoGia', 'Nhà thầu import file báo giá: Cty test vat_tu_duoc — 4 dòng', 'bang=bg_bao_gia; id=60', '0.0.0.0', NULL, '2026-09-10 06:04:37'),
(614, '2026-09-10 06:04:37', 1, NULL, 'BaoGia', 'Nhà thầu import file báo giá: Cty test vat_tu_duoc — 4 dòng', 'bang=bg_bao_gia; id=60', '0.0.0.0', NULL, '2026-09-10 06:04:37'),
(615, '2026-09-10 06:05:10', 1, NULL, 'BaoGia', 'Import 2 bộ / 3 hàng hóa vào gói thầu TM12-BO_DU-060510 (thêm tiếp)', 'bang=bg_hang_hoa; id=28', '0.0.0.0', NULL, '2026-09-10 06:05:10'),
(616, '2026-09-10 06:05:10', 1, NULL, 'BaoGia', 'Nhà thầu import file báo giá: Cty test bo_dung_cu — 3 dòng', 'bang=bg_bao_gia; id=61', '0.0.0.0', NULL, '2026-09-10 06:05:10'),
(617, '2026-09-10 06:05:10', 1, NULL, 'BaoGia', 'Nhà thầu import file báo giá: Cty test bo_dung_cu — 3 dòng', 'bang=bg_bao_gia; id=61', '0.0.0.0', NULL, '2026-09-10 06:05:10'),
(618, '2026-09-10 06:05:10', 1, NULL, 'BaoGia', 'Import 1 bộ / 4 hàng hóa vào gói thầu TM12-HE_TH-060510 (thêm tiếp)', 'bang=bg_hang_hoa; id=29', '0.0.0.0', NULL, '2026-09-10 06:05:10'),
(619, '2026-09-10 06:05:10', 1, NULL, 'BaoGia', 'Nhà thầu import file báo giá: Cty test he_thong_tbyt — 4 dòng', 'bang=bg_bao_gia; id=62', '0.0.0.0', NULL, '2026-09-10 06:05:10'),
(620, '2026-09-10 06:05:10', 1, NULL, 'BaoGia', 'Nhà thầu import file báo giá: Cty test he_thong_tbyt — 4 dòng', 'bang=bg_bao_gia; id=62', '0.0.0.0', NULL, '2026-09-10 06:05:10'),
(621, '2026-09-10 06:05:10', 1, NULL, 'BaoGia', 'Import 2 bộ / 4 hàng hóa vào gói thầu TM12-VAT_T-060510 (thêm tiếp)', 'bang=bg_hang_hoa; id=30', '0.0.0.0', NULL, '2026-09-10 06:05:10'),
(622, '2026-09-10 06:05:10', 1, NULL, 'BaoGia', 'Nhà thầu import file báo giá: Cty test vat_tu_duoc — 4 dòng', 'bang=bg_bao_gia; id=63', '0.0.0.0', NULL, '2026-09-10 06:05:10'),
(623, '2026-09-10 06:05:10', 1, NULL, 'BaoGia', 'Nhà thầu import file báo giá: Cty test vat_tu_duoc — 4 dòng', 'bang=bg_bao_gia; id=63', '0.0.0.0', NULL, '2026-09-10 06:05:10'),
(624, '2026-09-10 06:09:55', 1, NULL, 'BaoGia', 'Import 1 bộ / 4 hàng hóa vào gói thầu PORTAL-TEST-060955 (thêm tiếp)', 'bang=bg_hang_hoa; id=31', '0.0.0.0', NULL, '2026-09-10 06:09:55'),
(625, '2026-09-10 06:10:03', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-10 06:10:03'),
(626, '2026-09-10 06:10:06', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói PORTAL-TEST-060955: Cty test portal moi (MST 3344556677)', 'bang=bg_bao_gia; id=64', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-10 06:10:06'),
(627, '2026-09-10 06:10:38', 1, NULL, 'BaoGia', 'Import 1 bộ / 4 hàng hóa vào gói thầu PORTAL-TEST-061038 (thêm tiếp)', 'bang=bg_hang_hoa; id=32', '0.0.0.0', NULL, '2026-09-10 06:10:38'),
(628, '2026-09-10 06:10:39', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-10 06:10:39'),
(629, '2026-09-10 06:10:42', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói PORTAL-TEST-061038: Cty test portal moi (MST 3344556677)', 'bang=bg_bao_gia; id=65', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-10 06:10:42'),
(630, '2026-09-10 06:11:45', 1, NULL, 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5742/2026 (3 nhà thầu)', 'bang=bg_goi_thau; id=1', '0.0.0.0', NULL, '2026-09-10 06:11:45'),
(631, '2026-09-10 06:11:45', 1, NULL, 'BaoGia', 'Xuất chi tiết báo giá: Công ty TNHH Thiết bị Y tế An Phát', 'bang=bg_bao_gia; id=1', '0.0.0.0', NULL, '2026-09-10 06:11:45'),
(632, '2026-09-10 06:12:08', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-10 06:12:08'),
(633, '2026-09-10 06:12:23', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-10 06:12:23'),
(634, '2026-09-10 06:40:32', 1, NULL, 'BaoGia', 'Xuất chi tiết báo giá: Công ty TNHH Thiết bị Y tế An Phát', 'bang=bg_bao_gia; id=1', '0.0.0.0', NULL, '2026-09-10 06:40:32'),
(635, '2026-09-10 06:44:11', 1, NULL, 'BaoGia', 'Import 1 bộ / 4 hàng hóa vào gói thầu GIAODIEN-064411 (thêm tiếp)', 'bang=bg_hang_hoa; id=33', '0.0.0.0', NULL, '2026-09-10 06:44:11'),
(636, '2026-09-10 06:44:19', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-10 06:44:19'),
(637, '2026-09-10 06:46:02', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-10 06:46:02'),
(638, '2026-09-10 17:28:58', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-09-10 17:28:58'),
(639, '2026-09-10 17:33:06', 1, 'admin', 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5800/2026 (1 nhà thầu)', 'bang=bg_goi_thau; id=2', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-09-10 17:33:06'),
(640, '2026-09-10 17:36:13', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-10 17:36:13'),
(641, '2026-09-10 20:16:37', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-10 20:16:37'),
(642, '2026-09-10 20:17:06', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-10 20:17:06'),
(643, '2026-09-10 20:27:05', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-09-10 20:27:05'),
(644, '2026-09-10 20:36:37', 1, 'admin', 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5902/2026 (2 nhà thầu)', 'bang=bg_goi_thau; id=37', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-09-10 20:36:37'),
(645, '2026-09-10 20:40:15', 1, 'admin', 'BaoGia', 'Nhà thầu tạo báo giá gói 5902/2026: Nhà thầu test 123 (MST 1234567891)', 'bang=bg_bao_gia; id=78', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-09-10 20:40:15'),
(646, '2026-09-10 20:40:52', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-10 20:40:52'),
(647, '2026-09-10 20:41:29', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-10 20:41:29'),
(648, '2026-09-10 20:42:49', 1, 'admin', 'BaoGia', 'Nhà thầu import file báo giá: Nhà thầu test 123 — 11 dòng', 'bang=bg_bao_gia; id=78', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-09-10 20:42:49'),
(649, '2026-09-10 20:52:12', 1, NULL, 'BaoGia', 'Import 1 bộ / 4 hàng hóa vào gói thầu SLTEST-205212 (ghi đè)', 'bang=bg_hang_hoa; id=38', '0.0.0.0', NULL, '2026-09-10 20:52:12'),
(650, '2026-09-10 21:13:12', 1, NULL, 'BaoGia', 'Import 1 bộ / 4 hàng hóa vào gói thầu SLB-HE_T-21131256 (ghi đè)', 'bang=bg_hang_hoa; id=39', '0.0.0.0', NULL, '2026-09-10 21:13:12'),
(651, '2026-09-10 21:13:12', 1, NULL, 'BaoGia', 'Import 2 bộ / 3 hàng hóa vào gói thầu SLB-BO_D-21131228 (ghi đè)', 'bang=bg_hang_hoa; id=40', '0.0.0.0', NULL, '2026-09-10 21:13:12');
INSERT INTO `dm_nhat_ky_he_thong` (`id`, `thoi_gian`, `nguoi_dung_id`, `tai_khoan`, `module`, `hanh_dong`, `noi_dung`, `ip_address`, `user_agent`, `ngay_tao`) VALUES
(652, '2026-09-10 21:13:12', 1, NULL, 'BaoGia', 'Import 2 bộ / 4 hàng hóa vào gói thầu SLB-VAT_-21131298 (thêm tiếp)', 'bang=bg_hang_hoa; id=41', '0.0.0.0', NULL, '2026-09-10 21:13:12'),
(653, '2026-09-10 21:13:12', 1, NULL, 'BaoGia', 'Import 2 bộ / 4 hàng hóa vào gói thầu SLB-VAT_-21131298 (ghi đè)', 'bang=bg_hang_hoa; id=41', '0.0.0.0', NULL, '2026-09-10 21:13:12'),
(654, '2026-09-10 21:14:02', 1, NULL, 'BaoGia', 'Import 2 bộ / 3 hàng hóa vào gói thầu 6M-BO_D-21140221 (thêm tiếp)', 'bang=bg_hang_hoa; id=42', '0.0.0.0', NULL, '2026-09-10 21:14:02'),
(655, '2026-09-10 21:14:02', 1, NULL, 'BaoGia', 'Import 1 bộ / 4 hàng hóa vào gói thầu 6M-HE_T-21140293 (thêm tiếp)', 'bang=bg_hang_hoa; id=43', '0.0.0.0', NULL, '2026-09-10 21:14:02'),
(656, '2026-09-10 21:14:02', 1, NULL, 'BaoGia', 'Import 2 bộ / 4 hàng hóa vào gói thầu 6M-VAT_-21140296 (thêm tiếp)', 'bang=bg_hang_hoa; id=44', '0.0.0.0', NULL, '2026-09-10 21:14:02'),
(657, '2026-09-10 21:18:38', 1, 'admin', 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5902/2026 (2 nhà thầu)', 'bang=bg_goi_thau; id=37', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-09-10 21:18:38'),
(658, '2026-09-10 21:21:42', 1, NULL, 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5901/2026 (2 nhà thầu)', 'bang=bg_goi_thau; id=36', '0.0.0.0', NULL, '2026-09-10 21:21:42'),
(659, '2026-09-10 21:21:42', 1, NULL, 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5902/2026 (2 nhà thầu)', 'bang=bg_goi_thau; id=37', '0.0.0.0', NULL, '2026-09-10 21:21:42'),
(660, '2026-09-10 21:23:32', 1, NULL, 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5742/2026 (3 nhà thầu)', 'bang=bg_goi_thau; id=1', '0.0.0.0', NULL, '2026-09-10 21:23:32'),
(661, '2026-09-10 21:23:32', 1, NULL, 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5800/2026 (1 nhà thầu)', 'bang=bg_goi_thau; id=2', '0.0.0.0', NULL, '2026-09-10 21:23:32'),
(662, '2026-09-10 21:23:32', 1, NULL, 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5901/2026 (2 nhà thầu)', 'bang=bg_goi_thau; id=36', '0.0.0.0', NULL, '2026-09-10 21:23:32'),
(663, '2026-09-10 21:23:32', 1, NULL, 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5902/2026 (2 nhà thầu)', 'bang=bg_goi_thau; id=37', '0.0.0.0', NULL, '2026-09-10 21:23:32'),
(664, '2026-09-10 21:26:50', 1, NULL, 'BaoGia', 'Thêm hàng hóa vào gói 5902/2026: HH test A', 'bang=bg_hang_hoa; id=146', '0.0.0.0', NULL, '2026-09-10 21:26:50'),
(665, '2026-09-10 21:26:50', 1, NULL, 'BaoGia', 'Thêm hàng hóa vào gói 5742/2026: HH test B', 'bang=bg_hang_hoa; id=147', '0.0.0.0', NULL, '2026-09-10 21:26:50'),
(666, '2026-09-10 21:26:50', 1, NULL, 'BaoGia', 'Thêm hàng hóa vào gói 5902/2026: HH DU TRUONG', 'bang=bg_hang_hoa; id=148', '0.0.0.0', NULL, '2026-09-10 21:26:50'),
(667, '2026-09-10 21:26:50', 1, NULL, 'BaoGia', 'Sửa hàng hóa: HH DU TRUONG', 'bang=bg_hang_hoa; id=148', '0.0.0.0', NULL, '2026-09-10 21:26:50'),
(668, '2026-09-10 21:27:41', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-10 21:27:41'),
(669, '2026-09-10 21:34:01', 1, 'admin', 'BaoGia', 'Sửa hàng hóa: Hộp đựng mũi khoan', 'bang=bg_hang_hoa; id=6', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-09-10 21:34:01'),
(670, '2026-09-10 21:34:11', 1, 'admin', 'BaoGia', 'Sửa hàng hóa: Bộ que hàn Composite (Cây trám)', 'bang=bg_hang_hoa; id=7', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-09-10 21:34:11'),
(671, '2026-09-10 21:34:18', 1, 'admin', 'BaoGia', 'Sửa hàng hóa: Cây nạo nha chu GRACEY số 11-12', 'bang=bg_hang_hoa; id=8', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-09-10 21:34:18'),
(672, '2026-09-10 21:34:23', 1, 'admin', 'BaoGia', 'Sửa hàng hóa: Thước đo túi lợi', 'bang=bg_hang_hoa; id=9', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-09-10 21:34:23'),
(673, '2026-09-10 21:38:20', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-10 21:38:20'),
(674, '2026-09-10 21:38:26', 1, 'admin', 'BaoGia', 'Sửa bộ: HỆ THỐNG SIÊU ÂM (ĐÃ SỬA)', 'bang=bg_bo; id=41', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-10 21:38:26'),
(675, '2026-09-10 21:38:45', 1, 'admin', 'BaoGia', 'Sửa gói thầu: 5800/2026', 'bang=bg_goi_thau; id=2', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-09-10 21:38:45'),
(676, '2026-09-10 21:38:58', 1, 'admin', 'BaoGia', 'Nhà thầu tạo báo giá gói 5800/2026: Nhà thầu test (MST 1234267891)', 'bang=bg_bao_gia; id=80', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-09-10 21:38:58'),
(677, '2026-09-10 21:39:10', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-10 21:39:10'),
(678, '2026-09-10 21:39:16', 1, 'admin', 'BaoGia', 'Sửa bộ: HỆ THỐNG SIÊU ÂM (ĐÃ SỬA)', 'bang=bg_bo; id=41', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-10 21:39:16'),
(679, '2026-09-10 21:39:43', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-10 21:39:43'),
(680, '2026-09-10 21:41:39', 1, 'admin', 'BaoGia', 'Nhà thầu import file báo giá: Nhà thầu test — 4 dòng', 'bang=bg_bao_gia; id=80', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-09-10 21:41:39'),
(681, '2026-09-10 21:43:07', 1, 'admin', 'BaoGia', 'Nhà thầu import file báo giá: Nhà thầu test — 4 dòng', 'bang=bg_bao_gia; id=80', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-09-10 21:43:07'),
(682, '2026-09-10 21:43:50', 1, 'admin', 'BaoGia', 'Nhà thầu import file báo giá: Nhà thầu test — 4 dòng', 'bang=bg_bao_gia; id=80', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-09-10 21:43:50'),
(683, '2026-09-10 21:44:17', 1, NULL, 'BaoGia', 'Import 2 bộ / 3 hàng hóa vào gói thầu 6M-BO_D-21441782 (thêm tiếp)', 'bang=bg_hang_hoa; id=45', '0.0.0.0', NULL, '2026-09-10 21:44:17'),
(684, '2026-09-10 21:44:17', 1, NULL, 'BaoGia', 'Import 1 bộ / 4 hàng hóa vào gói thầu 6M-HE_T-21441784 (thêm tiếp)', 'bang=bg_hang_hoa; id=46', '0.0.0.0', NULL, '2026-09-10 21:44:17'),
(685, '2026-09-10 21:44:17', 1, NULL, 'BaoGia', 'Import 1 bộ / 4 hàng hóa vào gói thầu 6M-VAT_-21441732 (thêm tiếp)', 'bang=bg_hang_hoa; id=47', '0.0.0.0', NULL, '2026-09-10 21:44:17'),
(686, '2026-09-10 21:44:52', 1, NULL, 'BaoGia', 'Import 1 bộ / 4 hàng hóa vào gói thầu HANGLE-214452 (thêm tiếp)', 'bang=bg_hang_hoa; id=48', '0.0.0.0', NULL, '2026-09-10 21:44:52'),
(687, '2026-09-10 21:46:02', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-10 21:46:02'),
(688, '2026-09-10 21:46:06', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-10 21:46:06'),
(689, '2026-09-10 21:46:09', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty xem hang le (MST 1122334455)', 'bang=bg_bao_gia; id=82', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-10 21:46:09'),
(690, '2026-09-10 21:47:22', 10, NULL, 'HeThong', 'Đăng nhập: guest', 'bang=dm_nguoi_dung; id=10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-10 21:47:22'),
(691, '2026-09-10 21:47:25', 10, 'guest', 'BaoGia', 'Nhà thầu tạo báo giá gói 5742/2026: Cty xem hang le (MST 1122334455)', 'bang=bg_bao_gia; id=83', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', '2026-09-10 21:47:25'),
(692, '2026-09-10 22:48:21', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-09-10 22:48:21'),
(693, '2026-09-10 23:09:22', 1, 'admin', 'BaoGia', 'Nhà thầu tạo báo giá gói 5902/2026: Nhà thầu test (MST 1232267891)', 'bang=bg_bao_gia; id=84', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-09-10 23:09:22'),
(694, '2026-09-11 08:10:46', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '192.168.103.154', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 08:10:46'),
(695, '2026-09-11 08:15:46', 1, 'admin', 'BaoGia', 'Thêm gói thầu: 1192026', 'bang=bg_goi_thau; id=49', '192.168.103.154', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 08:15:46'),
(696, '2026-09-11 08:43:00', 1, 'admin', 'BaoGia', 'Import 1 bộ / 4 hàng hóa vào gói thầu 1192026 (thêm tiếp)', 'bang=bg_hang_hoa; id=49', '192.168.103.154', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 08:43:00'),
(697, '2026-09-11 08:52:13', 1, 'admin', 'BaoGia', 'Thêm gói thầu: 11.192026', 'bang=bg_goi_thau; id=50', '192.168.103.154', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 08:52:13'),
(698, '2026-09-11 09:27:10', 1, 'admin', 'BaoGia', 'Xóa tạm gói thầu: 11.192026', 'bang=bg_goi_thau; id=50', '192.168.103.154', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 09:27:10'),
(699, '2026-09-11 09:27:45', 1, 'admin', 'BaoGia', 'Thêm gói thầu: 11.192026', 'bang=bg_goi_thau; id=51', '192.168.103.154', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 09:27:45'),
(700, '2026-09-11 09:44:43', 1, 'admin', 'BaoGia', 'Import 1 bộ / 4 hàng hóa vào gói thầu 11.192026 (thêm tiếp)', 'bang=bg_hang_hoa; id=51', '192.168.103.154', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 09:44:43'),
(701, '2026-09-11 09:45:45', 1, 'admin', 'BaoGia', 'Sửa gói thầu: 11.192026', 'bang=bg_goi_thau; id=51', '192.168.103.154', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 09:45:45'),
(702, '2026-09-11 09:47:11', 1, 'admin', 'BaoGia', 'Nhà thầu tạo báo giá gói 11.192026: công ty TNHH A (MST 2901788301)', 'bang=bg_bao_gia; id=85', '192.168.103.154', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 09:47:11'),
(703, '2026-09-11 09:53:16', 1, 'admin', 'BaoGia', 'Nhà thầu import file báo giá: công ty TNHH A — 4 dòng', 'bang=bg_bao_gia; id=85', '192.168.103.154', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 09:53:16'),
(704, '2026-09-11 09:55:52', 1, 'admin', 'BaoGia', 'Nhà thầu import file báo giá: công ty TNHH A — 4 dòng', 'bang=bg_bao_gia; id=85', '192.168.103.154', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 09:55:52'),
(705, '2026-09-11 10:10:31', 1, 'admin', 'BaoGia', 'Xuất Excel tổng hợp báo giá gói 5902/2026 (2 nhà thầu)', 'bang=bg_goi_thau; id=37', '192.168.103.154', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 10:10:31'),
(706, '2026-09-11 10:33:48', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '192.168.103.154', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 10:33:48'),
(707, '2026-09-11 10:34:06', 1, 'admin', 'BaoGia', 'Sửa gói thầu: 11.192026', 'bang=bg_goi_thau; id=51', '192.168.103.154', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 10:34:06'),
(708, '2026-09-11 10:38:03', 1, 'admin', 'BaoGia', 'Sửa gói thầu: 1192026', 'bang=bg_goi_thau; id=49', '192.168.103.154', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 10:38:03'),
(709, '2026-09-11 10:38:24', 1, 'admin', 'BaoGia', 'Nhà thầu tạo báo giá gói 1192026: công ty TNHH A (MST 2900621000)', 'bang=bg_bao_gia; id=86', '192.168.103.154', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 10:38:24'),
(710, '2026-09-11 10:46:26', 1, 'admin', 'BaoGia', 'Thêm gói thầu: 11.292026', 'bang=bg_goi_thau; id=52', '192.168.103.154', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 10:46:26'),
(711, '2026-09-11 10:57:19', 1, 'admin', 'BaoGia', 'Import 2 bộ / 149 hàng hóa vào gói thầu 11.292026 (thêm tiếp)', 'bang=bg_hang_hoa; id=52', '192.168.103.154', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 10:57:19'),
(712, '2026-09-11 10:58:41', 1, 'admin', 'BaoGia', 'Nhà thầu tạo báo giá gói 11.292026: công ty TNHH A (MST 2900621000)', 'bang=bg_bao_gia; id=87', '192.168.103.154', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 10:58:41'),
(713, '2026-09-11 11:07:44', 1, 'admin', 'BaoGia', 'Nhà thầu import file báo giá: công ty TNHH A — 86 dòng', 'bang=bg_bao_gia; id=87', '192.168.103.154', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 11:07:44'),
(714, '2026-09-11 11:14:32', 1, 'admin', 'BaoGia', 'Nhà thầu import file báo giá: công ty TNHH A — 86 dòng', 'bang=bg_bao_gia; id=87', '192.168.103.154', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 11:14:32'),
(715, '2026-09-11 11:26:34', 1, 'admin', 'BaoGia', 'Nhà thầu tạo báo giá gói 1192026: công ty TNHH B (MST 2900621006)', 'bang=bg_bao_gia; id=88', '192.168.103.154', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 11:26:34'),
(716, '2026-09-11 11:29:49', 1, 'admin', 'BaoGia', 'Nhà thầu import file báo giá: công ty TNHH B — 4 dòng', 'bang=bg_bao_gia; id=88', '192.168.103.154', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 11:29:49'),
(717, '2026-09-11 11:33:30', 1, 'admin', 'BaoGia', 'Nhà thầu import file báo giá: công ty TNHH B — 4 dòng', 'bang=bg_bao_gia; id=88', '192.168.103.154', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 11:33:30'),
(718, '2026-09-12 16:41:24', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '171.253.51.216', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-09-12 16:41:24'),
(719, '2026-09-12 16:43:13', 1, 'admin', 'HeThong', 'Xóa tạm người dùng id=6', 'bang=dm_nguoi_dung; id=6', '171.253.51.216', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-09-12 16:43:13'),
(720, '2026-09-12 16:43:20', 1, 'admin', 'HeThong', 'Xóa tạm người dùng id=5', 'bang=dm_nguoi_dung; id=5', '171.253.51.216', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-09-12 16:43:20'),
(721, '2026-09-12 16:43:22', 1, 'admin', 'HeThong', 'Xóa tạm người dùng id=4', 'bang=dm_nguoi_dung; id=4', '171.253.51.216', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-09-12 16:43:22'),
(722, '2026-09-12 16:43:24', 1, 'admin', 'HeThong', 'Xóa tạm người dùng id=3', 'bang=dm_nguoi_dung; id=3', '171.253.51.216', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-09-12 16:43:24'),
(723, '2026-09-12 16:43:28', 1, 'admin', 'HeThong', 'Xóa tạm người dùng id=2', 'bang=dm_nguoi_dung; id=2', '171.253.51.216', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-09-12 16:43:28'),
(724, '2026-09-12 16:50:48', 1, 'admin', 'BaoGia', 'Nhà thầu tạo báo giá gói 5800/2026: Nhà thầu mới (MST 0111234567)', 'bang=bg_bao_gia; id=89', '171.253.51.216', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-09-12 16:50:48'),
(725, '2026-09-13 08:45:03', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '14.182.246.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-13 08:45:03'),
(726, '2026-09-13 08:51:25', 1, 'admin', 'HeThong', 'Thêm người dùng: CAMCHI', 'bang=dm_nguoi_dung; id=12', '14.182.246.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-13 08:51:25'),
(727, '2026-09-13 08:51:32', 1, 'admin', 'HeThong', 'Đăng xuất', 'bang=dm_nguoi_dung; id=1', '14.182.246.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-13 08:51:32'),
(728, '2026-09-13 08:51:46', 12, NULL, 'HeThong', 'Đăng nhập: CAMCHI', 'bang=dm_nguoi_dung; id=12', '14.182.246.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-13 08:51:46'),
(729, '2026-09-13 08:52:52', 12, 'CAMCHI', 'HeThong', 'Đăng xuất', 'bang=dm_nguoi_dung; id=12', '14.182.246.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-13 08:52:52'),
(730, '2026-09-13 08:53:01', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '14.182.246.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-13 08:53:01'),
(731, '2026-09-13 08:55:26', 1, 'admin', 'BaoGia', 'Nhà thầu tạo báo giá gói 11.192026: Công Ty BCV (MST 2900621156)', 'bang=bg_bao_gia; id=90', '14.182.246.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-13 08:55:26'),
(732, '2026-09-13 08:58:14', 1, 'admin', 'BaoGia', 'Nhà thầu import file báo giá: Công Ty BCV — 4 dòng', 'bang=bg_bao_gia; id=90', '14.182.246.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-13 08:58:14'),
(733, '2026-09-13 09:04:50', 1, 'admin', 'BaoGia', 'Nhà thầu import file báo giá: Công Ty BCV — 4 dòng', 'bang=bg_bao_gia; id=90', '14.182.246.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-13 09:04:50'),
(734, '2026-09-13 09:45:15', 1, 'admin', 'BaoGia', 'Nhà thầu import file báo giá: Công Ty BCV — 4 dòng', 'bang=bg_bao_gia; id=90', '14.182.246.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-13 09:45:15'),
(735, '2026-09-13 09:45:25', 1, 'admin', 'BaoGia', 'Nhà thầu nộp báo giá: Công Ty BCV (4 dòng)', 'bang=bg_bao_gia; id=90', '14.182.246.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-13 09:45:25'),
(736, '2026-09-13 10:38:09', 1, NULL, 'HeThong', 'Đăng nhập: admin', 'bang=dm_nguoi_dung; id=1', '171.253.51.216', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-09-13 10:38:09'),
(737, '2026-09-13 10:40:28', 1, 'admin', 'BaoGia', 'Nhà thầu tạo báo giá gói 11.192026: Nhà thầu Đức (MST 1234564991)', 'bang=bg_bao_gia; id=91', '171.253.51.216', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-09-13 10:40:28'),
(738, '2026-09-13 10:45:16', 1, 'admin', 'BaoGia', 'Nhà thầu import file báo giá: Nhà thầu Đức — 4 dòng', 'bang=bg_bao_gia; id=91', '171.253.51.216', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-09-13 10:45:16'),
(739, '2026-09-13 10:47:03', 1, 'admin', 'BaoGia', 'Nhà thầu import file báo giá: Nhà thầu Đức — 4 dòng', 'bang=bg_bao_gia; id=91', '171.253.51.216', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-09-13 10:47:03'),
(740, '2026-09-13 10:48:02', 1, 'admin', 'BaoGia', 'Nhà thầu import file báo giá: Nhà thầu Đức — 4 dòng', 'bang=bg_bao_gia; id=91', '171.253.51.216', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-09-13 10:48:02'),
(741, '2026-09-13 10:48:04', 1, 'admin', 'BaoGia', 'Nhà thầu nộp báo giá: Nhà thầu Đức (4 dòng)', 'bang=bg_bao_gia; id=91', '171.253.51.216', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-09-13 10:48:04');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `dm_nhom_tai_khoan`
--

CREATE TABLE `dm_nhom_tai_khoan` (
  `id` int(11) NOT NULL,
  `ma_nhom` varchar(20) NOT NULL,
  `ten_nhom` varchar(100) NOT NULL,
  `mo_ta` text DEFAULT NULL,
  `trang_thai` int(11) DEFAULT 1,
  `la_admin` int(11) DEFAULT 0,
  `ngay_tao` datetime DEFAULT current_timestamp(),
  `ngay_cap_nhat` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `nguoi_tao` int(11) DEFAULT NULL,
  `nguoi_cap_nhat` int(11) DEFAULT NULL,
  `da_xoa` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `dm_nhom_tai_khoan`
--

INSERT INTO `dm_nhom_tai_khoan` (`id`, `ma_nhom`, `ten_nhom`, `mo_ta`, `trang_thai`, `la_admin`, `ngay_tao`, `ngay_cap_nhat`, `nguoi_tao`, `nguoi_cap_nhat`, `da_xoa`) VALUES
(1, 'ADMIN', 'Quản trị viên', 'Toàn quyền trên hệ thống', 1, 1, '2026-08-16 23:01:02', '2026-08-16 23:01:02', 1, 1, 0),
(2, 'MANAGER', 'Quản lý', 'Xem, thêm, sửa dữ liệu; không được xóa', 1, 0, '2026-08-16 23:01:02', '2026-08-17 17:32:30', 1, 1, 0),
(3, 'STAFF', 'Nhân viên', 'Chủ yếu xem và nhập liệu cơ bản', 1, 0, '2026-08-16 23:01:02', '2026-08-16 23:01:02', 1, 1, 0),
(4, 'VIEWER', 'Chỉ xem', 'Chỉ được xem, không thay đổi dữ liệu', 1, 0, '2026-08-16 23:01:02', '2026-08-16 23:01:02', 1, 1, 0),
(5, 'TEMP', 'Tài khoản tạm', 'Nhóm đã ngừng hoạt động - dùng để test trạng thái', 0, 0, '2026-08-16 23:01:02', '2026-08-16 23:01:02', 1, 1, 0),
(9, 'NHATHAU', 'Nhà thầu', 'Tài khoản dùng chung cho nhà thầu quét QR vào chào giá', 1, 0, '2026-08-17 20:42:19', '2026-08-17 20:42:19', 1, 1, 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `dm_phan_quyen`
--

CREATE TABLE `dm_phan_quyen` (
  `id` int(11) NOT NULL,
  `nhom_tai_khoan_id` int(11) NOT NULL,
  `form_id` int(11) NOT NULL,
  `quyen_xem` int(11) DEFAULT 0,
  `quyen_them` int(11) DEFAULT 0,
  `quyen_sua` int(11) DEFAULT 0,
  `quyen_xoa` int(11) DEFAULT 0,
  `ngay_tao` datetime DEFAULT current_timestamp(),
  `ngay_cap_nhat` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `nguoi_tao` int(11) DEFAULT NULL,
  `nguoi_cap_nhat` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `dm_phan_quyen`
--

INSERT INTO `dm_phan_quyen` (`id`, `nhom_tai_khoan_id`, `form_id`, `quyen_xem`, `quyen_them`, `quyen_sua`, `quyen_xoa`, `ngay_tao`, `ngay_cap_nhat`, `nguoi_tao`, `nguoi_cap_nhat`) VALUES
(1, 2, 1, 1, 1, 1, 0, '2026-08-16 23:01:02', '2026-08-16 23:01:02', 1, 1),
(2, 2, 2, 1, 1, 1, 0, '2026-08-16 23:01:02', '2026-08-16 23:01:02', 1, 1),
(3, 2, 3, 1, 0, 0, 0, '2026-08-16 23:01:02', '2026-08-16 23:01:02', 1, 1),
(4, 2, 4, 1, 0, 1, 0, '2026-08-16 23:01:02', '2026-08-16 23:01:02', 1, 1),
(5, 2, 5, 1, 0, 0, 0, '2026-08-16 23:01:02', '2026-08-16 23:01:02', 1, 1),
(6, 3, 1, 1, 1, 1, 0, '2026-08-16 23:01:02', '2026-08-16 23:01:02', 1, 1),
(7, 3, 2, 1, 0, 0, 0, '2026-08-16 23:01:02', '2026-08-16 23:01:02', 1, 1),
(8, 3, 5, 1, 0, 0, 0, '2026-08-16 23:01:02', '2026-08-16 23:01:02', 1, 1),
(9, 4, 1, 1, 0, 0, 0, '2026-08-16 23:01:02', '2026-08-17 20:14:04', 1, 1),
(10, 4, 2, 1, 0, 0, 0, '2026-08-16 23:01:02', '2026-08-17 20:14:04', 1, 1),
(11, 4, 3, 1, 0, 0, 0, '2026-08-16 23:01:02', '2026-08-17 20:14:04', 1, 1),
(12, 4, 5, 1, 0, 0, 0, '2026-08-16 23:01:02', '2026-08-17 20:14:04', 1, 1),
(13, 1, 1, 1, 1, 1, 1, '2026-08-16 23:01:02', '2026-08-16 23:01:02', 1, 1),
(14, 1, 2, 1, 1, 1, 1, '2026-08-16 23:01:02', '2026-08-16 23:01:02', 1, 1),
(15, 1, 3, 1, 1, 1, 1, '2026-08-16 23:01:02', '2026-08-16 23:01:02', 1, 1),
(16, 1, 4, 1, 1, 1, 1, '2026-08-16 23:01:02', '2026-08-16 23:01:02', 1, 1),
(17, 1, 5, 1, 1, 1, 1, '2026-08-16 23:01:02', '2026-08-16 23:01:02', 1, 1),
(24, 4, 4, 0, 0, 0, 0, '2026-08-16 23:03:05', '2026-08-17 20:14:04', 1, 1),
(42, 1, 9, 1, 1, 1, 1, '2026-08-17 20:42:19', '2026-08-17 22:29:12', 1, 1),
(43, 1, 10, 1, 1, 1, 1, '2026-08-17 20:42:19', '2026-08-17 22:29:12', 1, 1),
(44, 1, 11, 1, 1, 1, 1, '2026-08-17 20:42:19', '2026-08-17 22:29:12', 1, 1),
(45, 1, 12, 1, 1, 1, 1, '2026-08-17 20:42:19', '2026-08-17 22:29:12', 1, 1),
(46, 9, 9, 0, 0, 0, 0, '2026-08-17 20:42:19', '2026-08-17 22:29:12', 1, 1),
(47, 9, 10, 0, 0, 0, 0, '2026-08-17 20:42:19', '2026-08-17 22:29:12', 1, 1),
(48, 9, 11, 0, 0, 0, 0, '2026-08-17 20:42:19', '2026-08-17 22:29:12', 1, 1),
(49, 9, 12, 0, 0, 0, 0, '2026-08-17 20:42:19', '2026-08-17 22:29:12', 1, 1),
(54, 2, 9, 1, 1, 1, 0, '2026-08-17 22:29:12', '2026-08-17 22:29:12', 1, 1),
(55, 2, 10, 1, 1, 1, 0, '2026-08-17 22:29:12', '2026-08-17 22:29:12', 1, 1),
(56, 2, 11, 1, 0, 1, 0, '2026-08-17 22:29:12', '2026-08-17 22:29:12', 1, 1),
(57, 2, 12, 1, 0, 0, 0, '2026-08-17 22:29:12', '2026-08-17 22:29:12', 1, 1),
(58, 3, 9, 1, 0, 0, 0, '2026-08-17 22:29:12', '2026-08-17 22:29:12', 1, 1),
(59, 3, 10, 1, 1, 1, 0, '2026-08-17 22:29:12', '2026-08-17 22:29:12', 1, 1),
(60, 3, 11, 1, 0, 0, 0, '2026-08-17 22:29:12', '2026-08-17 22:29:12', 1, 1),
(61, 3, 12, 1, 0, 0, 0, '2026-08-17 22:29:12', '2026-08-17 22:29:12', 1, 1),
(62, 4, 9, 1, 0, 0, 0, '2026-08-17 22:29:12', '2026-08-17 22:29:12', 1, 1),
(63, 4, 10, 1, 0, 0, 0, '2026-08-17 22:29:12', '2026-08-17 22:29:12', 1, 1),
(64, 4, 11, 1, 0, 0, 0, '2026-08-17 22:29:12', '2026-08-17 22:29:12', 1, 1),
(65, 4, 12, 1, 0, 0, 0, '2026-08-17 22:29:12', '2026-08-17 22:29:12', 1, 1),
(78, 1, 13, 1, 1, 1, 1, '2026-08-18 22:17:31', '2026-08-19 07:26:13', 1, 1),
(79, 2, 13, 1, 0, 1, 0, '2026-08-18 22:17:31', '2026-08-18 22:17:31', 1, 1),
(80, 3, 13, 1, 0, 0, 0, '2026-08-18 22:17:31', '2026-08-18 22:17:31', 1, 1),
(81, 4, 13, 1, 0, 0, 0, '2026-08-18 22:17:31', '2026-08-18 22:17:31', 1, 1),
(82, 9, 13, 0, 0, 0, 0, '2026-08-18 22:17:31', '2026-08-18 22:17:31', 1, 1),
(84, 1, 14, 1, 1, 1, 1, '2026-08-26 20:49:16', '2026-08-26 20:49:16', NULL, NULL);

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `bg_bao_gia`
--
ALTER TABLE `bg_bao_gia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_goi_thau` (`goi_thau_id`,`da_xoa`),
  ADD KEY `idx_trang_thai` (`trang_thai`,`da_xoa`),
  ADD KEY `idx_mst` (`ma_so_thue`),
  ADD KEY `idx_file_ban_ky` (`file_ban_ky_id`);

--
-- Chỉ mục cho bảng `bg_bao_gia_chi_tiet`
--
ALTER TABLE `bg_bao_gia_chi_tiet`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_bao_gia_hang_hoa` (`bao_gia_id`,`hang_hoa_id`),
  ADD KEY `idx_hang_hoa` (`hang_hoa_id`);

--
-- Chỉ mục cho bảng `bg_bo`
--
ALTER TABLE `bg_bo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_goi_thau` (`goi_thau_id`,`da_xoa`),
  ADD KEY `idx_thu_tu` (`goi_thau_id`,`thu_tu`);

--
-- Chỉ mục cho bảng `bg_file`
--
ALTER TABLE `bg_file`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_nhom` (`nhom_file`,`da_xoa`),
  ADD KEY `idx_ten_file` (`ten_file`);

--
-- Chỉ mục cho bảng `bg_goi_thau`
--
ALTER TABLE `bg_goi_thau`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_token` (`token`),
  ADD UNIQUE KEY `uk_so_thong_bao` (`so_thong_bao`,`da_xoa`),
  ADD KEY `idx_trang_thai` (`trang_thai`,`da_xoa`),
  ADD KEY `idx_han_cuoi` (`han_cuoi`),
  ADD KEY `idx_thoi_gian_bao_gia` (`thoi_gian_mo_bao_gia`,`thoi_gian_dong_bao_gia`);

--
-- Chỉ mục cho bảng `bg_hang_hoa`
--
ALTER TABLE `bg_hang_hoa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_goi_thau` (`goi_thau_id`,`da_xoa`,`thu_tu`),
  ADD KEY `idx_ma_hh` (`goi_thau_id`,`ma_hh`);

--
-- Chỉ mục cho bảng `bg_quyen_goi_thau`
--
ALTER TABLE `bg_quyen_goi_thau`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_goi_nguoi` (`goi_thau_id`,`nguoi_dung_id`,`da_xoa`),
  ADD KEY `idx_nguoi_dung` (`nguoi_dung_id`),
  ADD KEY `idx_goi_thau` (`goi_thau_id`);

--
-- Chỉ mục cho bảng `dm_dang_nhap_that_bai`
--
ALTER TABLE `dm_dang_nhap_that_bai`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_khoa` (`khoa`),
  ADD KEY `idx_lan_cuoi` (`lan_cuoi`),
  ADD KEY `idx_ip` (`ip_address`);

--
-- Chỉ mục cho bảng `dm_danh_sach_form`
--
ALTER TABLE `dm_danh_sach_form`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_modules` (`modules_tuong_ung`,`da_xoa`);

--
-- Chỉ mục cho bảng `dm_nguoi_dung`
--
ALTER TABLE `dm_nguoi_dung`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_tai_khoan` (`tai_khoan`,`da_xoa`);

--
-- Chỉ mục cho bảng `dm_nhat_ky_he_thong`
--
ALTER TABLE `dm_nhat_ky_he_thong`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `dm_nhom_tai_khoan`
--
ALTER TABLE `dm_nhom_tai_khoan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_ma_nhom` (`ma_nhom`,`da_xoa`);

--
-- Chỉ mục cho bảng `dm_phan_quyen`
--
ALTER TABLE `dm_phan_quyen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_nhom_form` (`nhom_tai_khoan_id`,`form_id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `bg_bao_gia`
--
ALTER TABLE `bg_bao_gia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT cho bảng `bg_bao_gia_chi_tiet`
--
ALTER TABLE `bg_bao_gia_chi_tiet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=651;

--
-- AUTO_INCREMENT cho bảng `bg_bo`
--
ALTER TABLE `bg_bo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT cho bảng `bg_file`
--
ALTER TABLE `bg_file`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT cho bảng `bg_goi_thau`
--
ALTER TABLE `bg_goi_thau`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT cho bảng `bg_hang_hoa`
--
ALTER TABLE `bg_hang_hoa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=321;

--
-- AUTO_INCREMENT cho bảng `bg_quyen_goi_thau`
--
ALTER TABLE `bg_quyen_goi_thau`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `dm_dang_nhap_that_bai`
--
ALTER TABLE `dm_dang_nhap_that_bai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `dm_danh_sach_form`
--
ALTER TABLE `dm_danh_sach_form`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT cho bảng `dm_nguoi_dung`
--
ALTER TABLE `dm_nguoi_dung`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `dm_nhat_ky_he_thong`
--
ALTER TABLE `dm_nhat_ky_he_thong`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=742;

--
-- AUTO_INCREMENT cho bảng `dm_nhom_tai_khoan`
--
ALTER TABLE `dm_nhom_tai_khoan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT cho bảng `dm_phan_quyen`
--
ALTER TABLE `dm_phan_quyen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
