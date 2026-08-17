# Hệ thống quản lý người dùng

Hệ thống quản lý người dùng đơn giản với chức năng đăng nhập, đăng xuất, CRUD người dùng, nhóm tài khoản, phân quyền, nhật ký hệ thống.

## Cài đặt

1. Tạo database `ql_user_management` với charset utf8mb4
2. Import schema từ `docs/database.md`
3. Cấu hình XAMPP với PHP 7.4+, MariaDB 10.4+
4. Thay đổi cấu hình trong `PUBLIC/Common/AppConfig.php`:
   - DB_HOST, DB_NAME, DB_USER, DB_PASS
   - APP_URL
5. Truy cập `index.php` để bắt đầu

## Cấu trúc

- `bootstrap.php`: Khởi tạo hệ thống
- `index.php`: Entry point
- `GUI/auth/`: Đăng nhập/đăng xuất
- `GUI/dashboard/`: Trang chủ
- `GUI/DM_*/`: Các module quản lý
- `BUS/`: Business logic
- `DAL/`: Data access
- `PUBLIC/Entities/`: DTO
- `PUBLIC/Common/`: Helpers
- `assets/`: CSS, JS
- `docs/`: Tài liệu

## Tính năng

- Đăng nhập/đăng xuất
- Quản lý người dùng (CRUD)
- Quản lý nhóm tài khoản (CRUD)
- Quản lý danh sách form
- Phân quyền theo ma trận
- Nhật ký hệ thống
- Soft delete
- CSRF protection
- Audit trail

## Bảo mật

- Bcrypt password hashing
- CSRF token
- Session management
- XSS escaping
- Soft delete
- Permission-based access

## Phát triển

- PHP 7.4+ với typed properties
- PDO với named placeholders
- jQuery 3.7
- 3-tier architecture
- Transaction support