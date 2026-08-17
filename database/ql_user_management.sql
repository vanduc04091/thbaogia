-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: ql_user_management
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `ql_user_management`
--

/*!40000 DROP DATABASE IF EXISTS `ql_user_management`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `ql_user_management` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `ql_user_management`;

--
-- Table structure for table `dm_danh_sach_form`
--

DROP TABLE IF EXISTS `dm_danh_sach_form`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dm_danh_sach_form` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `modules_tuong_ung` varchar(100) NOT NULL,
  `ten_form` varchar(200) NOT NULL,
  `form_cha_id` int(11) DEFAULT 0,
  `ngay_tao` datetime DEFAULT current_timestamp(),
  `ngay_cap_nhat` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `nguoi_tao` int(11) DEFAULT NULL,
  `nguoi_cap_nhat` int(11) DEFAULT NULL,
  `da_xoa` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_modules` (`modules_tuong_ung`,`da_xoa`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dm_danh_sach_form`
--

LOCK TABLES `dm_danh_sach_form` WRITE;
/*!40000 ALTER TABLE `dm_danh_sach_form` DISABLE KEYS */;
INSERT INTO `dm_danh_sach_form` VALUES (1,'DM_NguoiDung','Quản lý người dùng',0,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1,0),(2,'DM_NhomTaiKhoan','Quản lý nhóm tài khoản',0,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1,0),(3,'DM_DanhSachForm','Danh sách form',0,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1,0),(4,'DM_PhanQuyen','Phân quyền',0,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1,0),(5,'DM_NhatKyHeThong','Nhật ký hệ thống',0,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1,0);
/*!40000 ALTER TABLE `dm_danh_sach_form` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dm_nguoi_dung`
--

DROP TABLE IF EXISTS `dm_nguoi_dung`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dm_nguoi_dung` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tai_khoan` varchar(50) NOT NULL,
  `mat_khau` varchar(255) NOT NULL,
  `nhom_tai_khoan_id` int(11) NOT NULL,
  `trang_thai` int(11) DEFAULT 1,
  `lan_dang_nhap_cuoi` datetime DEFAULT NULL,
  `ngay_tao` datetime DEFAULT current_timestamp(),
  `ngay_cap_nhat` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `nguoi_tao` int(11) DEFAULT NULL,
  `nguoi_cap_nhat` int(11) DEFAULT NULL,
  `da_xoa` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tai_khoan` (`tai_khoan`,`da_xoa`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dm_nguoi_dung`
--

LOCK TABLES `dm_nguoi_dung` WRITE;
/*!40000 ALTER TABLE `dm_nguoi_dung` DISABLE KEYS */;
INSERT INTO `dm_nguoi_dung` VALUES (1,'admin','$2y$10$vvWtaekhiGLYTQyROZQdgOagEbZ6z08zTwrSSvRnMiigcmxcX7q3a',1,1,NULL,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1,0),(2,'manager','$2y$10$VZRoSJj9elqD6QXL/8/oNe4N6Exd.deIVW5TtP4pnA8W0o9lGE5qy',2,1,NULL,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1,0),(3,'staff01','$2y$10$cKPzk62iaX2xPuMUmd4cRubfZ586701/trJV09qBpOvrSEqDEgcPm',3,1,NULL,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1,0),(4,'staff02','$2y$10$vCtJf678dGRfIqDp48Jl2eLUpQJwP.l1vzT49OSoEUvCvD1hA1kbu',3,1,NULL,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1,0),(5,'viewer','$2y$10$XggTLbHjv8La3Ktq/CtlNeAY5uoftZpv2erOHwAx0Eorxi80ErDPq',4,1,NULL,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1,0),(6,'locked','$2y$10$QFiI60TPwKZ4DauLJg9Rleo5/exIdD8tJLyUsUsAaCRxhZ3ah3NSS',3,0,NULL,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1,0);
/*!40000 ALTER TABLE `dm_nguoi_dung` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dm_nhat_ky_he_thong`
--

DROP TABLE IF EXISTS `dm_nhat_ky_he_thong`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dm_nhat_ky_he_thong` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `thoi_gian` datetime DEFAULT current_timestamp(),
  `nguoi_dung_id` int(11) DEFAULT NULL,
  `tai_khoan` varchar(50) DEFAULT NULL,
  `module` varchar(100) NOT NULL,
  `hanh_dong` varchar(200) NOT NULL,
  `noi_dung` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `ngay_tao` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dm_nhat_ky_he_thong`
--

LOCK TABLES `dm_nhat_ky_he_thong` WRITE;
/*!40000 ALTER TABLE `dm_nhat_ky_he_thong` DISABLE KEYS */;
INSERT INTO `dm_nhat_ky_he_thong` VALUES (1,'2026-08-16 22:56:02',1,'admin','HeThong','Đăng nhập','Đăng nhập thành công','127.0.0.1','Mozilla/5.0 (Seed Data)','2026-08-16 23:01:02'),(2,'2026-08-16 22:19:02',1,'admin','HeThong','Thêm nhóm tài khoản','bang=dm_nhom_tai_khoan; Thêm nhóm STAFF','127.0.0.1','Mozilla/5.0 (Seed Data)','2026-08-16 23:01:02'),(3,'2026-08-16 21:42:02',2,'manager','HeThong','Đăng nhập','Đăng nhập thành công','192.168.1.20','Mozilla/5.0 (Seed Data)','2026-08-16 23:01:02'),(4,'2026-08-16 21:05:02',2,'manager','HeThong','Sửa người dùng','bang=dm_nguoi_dung; id=3','192.168.1.20','Mozilla/5.0 (Seed Data)','2026-08-16 23:01:02'),(5,'2026-08-16 20:28:02',3,'staff01','HeThong','Đăng nhập','Đăng nhập thành công','192.168.1.35','Mozilla/5.0 (Seed Data)','2026-08-16 23:01:02'),(6,'2026-08-16 19:51:02',3,'staff01','HeThong','Đăng nhập thất bại','Sai mật khẩu','192.168.1.35','Mozilla/5.0 (Seed Data)','2026-08-16 23:01:02'),(7,'2026-08-16 19:14:02',5,'viewer','HeThong','Đăng nhập','Đăng nhập thành công','10.0.0.8','Mozilla/5.0 (Seed Data)','2026-08-16 23:01:02');
/*!40000 ALTER TABLE `dm_nhat_ky_he_thong` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dm_nhom_tai_khoan`
--

DROP TABLE IF EXISTS `dm_nhom_tai_khoan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dm_nhom_tai_khoan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ma_nhom` varchar(20) NOT NULL,
  `ten_nhom` varchar(100) NOT NULL,
  `mo_ta` text DEFAULT NULL,
  `trang_thai` int(11) DEFAULT 1,
  `la_admin` int(11) DEFAULT 0,
  `ngay_tao` datetime DEFAULT current_timestamp(),
  `ngay_cap_nhat` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `nguoi_tao` int(11) DEFAULT NULL,
  `nguoi_cap_nhat` int(11) DEFAULT NULL,
  `da_xoa` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ma_nhom` (`ma_nhom`,`da_xoa`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dm_nhom_tai_khoan`
--

LOCK TABLES `dm_nhom_tai_khoan` WRITE;
/*!40000 ALTER TABLE `dm_nhom_tai_khoan` DISABLE KEYS */;
INSERT INTO `dm_nhom_tai_khoan` VALUES (1,'ADMIN','Quản trị viên','Toàn quyền trên hệ thống',1,1,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1,0),(2,'MANAGER','Quản lý','Xem, thêm, sửa dữ liệu; không được xóa',1,0,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1,0),(3,'STAFF','Nhân viên','Chủ yếu xem và nhập liệu cơ bản',1,0,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1,0),(4,'VIEWER','Chỉ xem','Chỉ được xem, không thay đổi dữ liệu',1,0,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1,0),(5,'TEMP','Tài khoản tạm','Nhóm đã ngừng hoạt động - dùng để test trạng thái',0,0,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1,0);
/*!40000 ALTER TABLE `dm_nhom_tai_khoan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dm_phan_quyen`
--

DROP TABLE IF EXISTS `dm_phan_quyen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dm_phan_quyen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nhom_tai_khoan_id` int(11) NOT NULL,
  `form_id` int(11) NOT NULL,
  `quyen_xem` int(11) DEFAULT 0,
  `quyen_them` int(11) DEFAULT 0,
  `quyen_sua` int(11) DEFAULT 0,
  `quyen_xoa` int(11) DEFAULT 0,
  `ngay_tao` datetime DEFAULT current_timestamp(),
  `ngay_cap_nhat` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `nguoi_tao` int(11) DEFAULT NULL,
  `nguoi_cap_nhat` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nhom_form` (`nhom_tai_khoan_id`,`form_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dm_phan_quyen`
--

LOCK TABLES `dm_phan_quyen` WRITE;
/*!40000 ALTER TABLE `dm_phan_quyen` DISABLE KEYS */;
INSERT INTO `dm_phan_quyen` VALUES (1,2,1,1,1,1,0,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1),(2,2,2,1,1,1,0,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1),(3,2,3,1,0,0,0,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1),(4,2,4,1,0,1,0,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1),(5,2,5,1,0,0,0,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1),(6,3,1,1,1,1,0,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1),(7,3,2,1,0,0,0,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1),(8,3,5,1,0,0,0,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1),(9,4,1,1,0,0,0,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1),(10,4,2,1,0,0,0,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1),(11,4,3,1,0,0,0,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1),(12,4,5,1,0,0,0,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1),(13,1,1,1,1,1,1,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1),(14,1,2,1,1,1,1,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1),(15,1,3,1,1,1,1,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1),(16,1,4,1,1,1,1,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1),(17,1,5,1,1,1,1,'2026-08-16 23:01:02','2026-08-16 23:01:02',1,1);
/*!40000 ALTER TABLE `dm_phan_quyen` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'ql_user_management'
--

--
-- Dumping routines for database 'ql_user_management'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-16 23:01:02
