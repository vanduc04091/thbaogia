# Backup database — hướng dẫn khôi phục

File: `ql_user_management.sql` — dump đầy đủ (cấu trúc + dữ liệu test).

- **DBMS**: MariaDB 10.4+ / MySQL 5.7+
- **Charset**: `utf8mb4` / `utf8mb4_unicode_ci`
- **Database**: `ql_user_management`
- File dump đã có sẵn `DROP DATABASE` + `CREATE DATABASE` ⇒ **không cần tạo DB trước**.

---

## Cách 1: Dòng lệnh (khuyến nghị)

Mở terminal tại thư mục gốc dự án:

```bash
# Windows + XAMPP
C:\xampp\mysql\bin\mysql.exe -u root < database/ql_user_management.sql

# Linux/macOS
mysql -u root -p < database/ql_user_management.sql
```

> Dump chứa `DROP DATABASE IF EXISTS ql_user_management` — sẽ **xóa sạch** DB cùng tên đang có.
> Nếu đang có dữ liệu thật, backup trước khi chạy.

## Cách 2: phpMyAdmin

1. Vào `http://localhost/phpmyadmin`
2. Tab **Import** → **Choose File** → chọn `database/ql_user_management.sql`
3. Charset để `utf8mb4` → bấm **Go**

## Cách 3: Tạo lại từ seed (không cần file dump)

Nếu DB đã có sẵn cấu trúc bảng:

```bash
php seed.php --reset
```

---

## Kiểm tra sau khi khôi phục

```sql
USE ql_user_management;
SELECT COUNT(*) FROM dm_nguoi_dung;      -- 6
SELECT COUNT(*) FROM dm_nhom_tai_khoan;  -- 5
SELECT COUNT(*) FROM dm_danh_sach_form;  -- 5
SELECT COUNT(*) FROM dm_phan_quyen;      -- 18
SELECT ten_nhom FROM dm_nhom_tai_khoan;  -- phải hiện tiếng Việt có dấu
```

Nếu tiếng Việt bị lỗi font (`Qu?n tr? viên`) → thêm `--default-character-set=utf8mb4` khi import.

---

## Tài khoản đăng nhập

| Tài khoản | Mật khẩu | Vai trò |
|---|---|---|
| `admin` | `Admin@123` | Toàn quyền |
| `manager` | `Manager@123` | Xem / thêm / sửa (không xóa) |
| `staff01` | `Staff@123` | Người dùng + nhật ký |
| `staff02` | `Staff@123` | Người dùng + nhật ký |
| `viewer` | `Viewer@123` | Chỉ xem |
| `locked` | `Locked@123` | **Bị khóa** (để test đăng nhập lỗi) |

> Đây là mật khẩu môi trường dev. **Đổi hết trước khi đưa lên production.**

---

## Tạo backup mới

```bash
C:\xampp\mysql\bin\mysqldump.exe -u root --databases ql_user_management \
  --add-drop-database --default-character-set=utf8mb4 \
  --routines --events --single-transaction > database/ql_user_management.sql
```

## Cấu hình kết nối

Sửa tại `PUBLIC/Common/AppConfig.php`:

```php
const DB_HOST = 'localhost';
const DB_NAME = 'ql_user_management';
const DB_USER = 'root';
const DB_PASS = '';
const APP_URL = 'http://codekhoitao.bv';   // đổi theo domain/máy của bạn
```
