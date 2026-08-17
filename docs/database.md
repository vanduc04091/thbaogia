# DATABASE.md — Schema hệ thống quản lý người dùng

Tài liệu cơ sở dữ liệu cho hệ thống quản lý người dùng đơn giản.

- **DBMS**: MariaDB / MySQL 5.7+
- **Charset**: `utf8mb4` / `utf8mb4_unicode_ci`
- **Engine**: InnoDB
- **Database name**: `ql_user_management`

## Quy ước chung

### Tên bảng / cột
- Tên bảng: `snake_case`, prefix `dm_` (danh mục).
- Cột tiếng Việt không dấu.

### Cột chuẩn audit
Mọi bảng có:
- `id` INT AUTO_INCREMENT PK
- `ngay_tao` DATETIME DEFAULT CURRENT_TIMESTAMP
- `ngay_cap_nhat` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
- `nguoi_tao` INT NULL
- `nguoi_cap_nhat` INT NULL
- `da_xoa` INT DEFAULT 0

### Soft delete
- Không DELETE thật, chỉ UPDATE da_xoa = 1
- UNIQUE KEY bao gồm da_xoa
- SELECT luôn WHERE da_xoa = 0

## Các bảng

### dm_nguoi_dung (Người dùng)
```sql
CREATE TABLE dm_nguoi_dung (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tai_khoan VARCHAR(50) NOT NULL,
    mat_khau VARCHAR(255) NOT NULL,
    nhom_tai_khoan_id INT NOT NULL,
    trang_thai INT DEFAULT 1,
    lan_dang_nhap_cuoi DATETIME NULL,
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    ngay_cap_nhat DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    nguoi_tao INT NULL,
    nguoi_cap_nhat INT NULL,
    da_xoa INT DEFAULT 0,
    UNIQUE KEY uk_tai_khoan(tai_khoan, da_xoa)
);
```

### dm_nhom_tai_khoan (Nhóm tài khoản)
```sql
CREATE TABLE dm_nhom_tai_khoan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_nhom VARCHAR(20) NOT NULL,
    ten_nhom VARCHAR(100) NOT NULL,
    mo_ta TEXT NULL,
    trang_thai INT DEFAULT 1,
    la_admin INT DEFAULT 0,
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    ngay_cap_nhat DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    nguoi_tao INT NULL,
    nguoi_cap_nhat INT NULL,
    da_xoa INT DEFAULT 0,
    UNIQUE KEY uk_ma_nhom(ma_nhom, da_xoa)
);
```

### dm_danh_sach_form (Danh sách form)
```sql
CREATE TABLE dm_danh_sach_form (
    id INT AUTO_INCREMENT PRIMARY KEY,
    modules_tuong_ung VARCHAR(100) NOT NULL,
    ten_form VARCHAR(200) NOT NULL,
    form_cha_id INT DEFAULT 0,
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    ngay_cap_nhat DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    nguoi_tao INT NULL,
    nguoi_cap_nhat INT NULL,
    da_xoa INT DEFAULT 0,
    UNIQUE KEY uk_modules(modules_tuong_ung, da_xoa)
);
```

### dm_phan_quyen (Phân quyền)
```sql
CREATE TABLE dm_phan_quyen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nhom_tai_khoan_id INT NOT NULL,
    form_id INT NOT NULL,
    quyen_xem INT DEFAULT 0,
    quyen_them INT DEFAULT 0,
    quyen_sua INT DEFAULT 0,
    quyen_xoa INT DEFAULT 0,
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    ngay_cap_nhat DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    nguoi_tao INT NULL,
    nguoi_cap_nhat INT NULL,
    UNIQUE KEY uk_nhom_form(nhom_tai_khoan_id, form_id)
);
```

### dm_nhat_ky_he_thong (Nhật ký hệ thống)
```sql
CREATE TABLE dm_nhat_ky_he_thong (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thoi_gian DATETIME DEFAULT CURRENT_TIMESTAMP,
    nguoi_dung_id INT NULL,
    tai_khoan VARCHAR(50) NULL,      -- snapshot tài khoản lúc ghi log
    module VARCHAR(100) NOT NULL,
    hanh_dong VARCHAR(200) NOT NULL,
    noi_dung TEXT NULL,              -- gồm cả "bang=...; id=..." nếu có
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

> **Lưu ý**: bảng này KHÔNG có `bang_lien_quan`, `id_lien_quan`, `noi_dung_thay_doi`, `dia_chi_ip`.
> `DM_NhatKyHeThong_DAL::log()` gộp thông tin bảng/bản ghi liên quan vào cột `noi_dung`.

## Quan hệ
- dm_nguoi_dung.nhom_tai_khoan_id → dm_nhom_tai_khoan.id
- dm_phan_quyen.nhom_tai_khoan_id → dm_nhom_tai_khoan.id
- **dm_phan_quyen.form_id → dm_danh_sach_form.id**  ← tên cột là `form_id`
- dm_nhat_ky_he_thong.nguoi_dung_id → dm_nguoi_dung.id

## Quyền

Trong DB, quyền lưu thành **4 cột riêng biệt** (0/1), KHÔNG phải bitwise:
`quyen_xem`, `quyen_them`, `quyen_sua`, `quyen_xoa`.

Bitmask **chỉ dùng ở tầng frontend** để gọn dữ liệu truyền tải:

| Quyền | Bit | Cột DB |
|---|---|---|
| Xem  | 1 | `quyen_xem` |
| Thêm | 2 | `quyen_them` |
| Sửa  | 4 | `quyen_sua` |
| Xóa  | 8 | `quyen_xoa` |

Chuyển đổi 2 chiều nằm ở `DM_PhanQuyen_BUS`:
- `getBitmaskByNhom()` — 4 cột DB → bitmask cho JS
- `saveMatrix()` — nhận bitmask **hoặc** mảng `['xem'=>1,...]`, ghi ra 4 cột

Quy tắc nghiệp vụ: có `them`/`sua`/`xoa` thì tự động bật `xem`.

## Dữ liệu test

`php seed.php --reset` tạo 5 nhóm, 5 form, 6 người dùng, ma trận phân quyền và log mẫu.
Tài khoản: `admin/Admin@123`, `manager/Manager@123`, `staff01,staff02/Staff@123`,
`viewer/Viewer@123`, `locked/Locked@123` (bị khóa).
---

# PHẦN 2 — SCHEMA NGHIỆP VỤ TỔNG HỢP BÁO GIÁ

> Tạo bởi `php database/migrate_bao_gia.php` (idempotent, chạy nhiều lần vô hại).
> Dữ liệu test: `php database/seed_bao_gia.php --reset`.
> **Database name**: `th_bao_gia`

## Luồng nghiệp vụ

```
1. Bên mời tạo GÓI THẦU (bg_goi_thau)          → sinh token QR
2. Import/nhập HÀNG HÓA yêu cầu (bg_hang_hoa)  → cột A-K của file mẫu
3. Chuyển gói thầu sang trạng thái "Đang mở"   → phát mã QR cho nhà thầu
4. Nhà thầu quét QR → đăng nhập tài khoản chung → khai thông tin công ty
                                                → tạo bg_bao_gia (Chờ xác nhận)
5. Nhà thầu tải file mẫu, điền cột L-AD, import lên
   HOẶC điền trực tiếp vào bảng web            → ghi bg_bao_gia_chi_tiet
6. Nhà thầu nộp (ngay_nop) → gửi BẢN GIẤY
7. Bên mời tích XÁC NHẬN bản giấy               → trang_thai = 1 (Đã xác nhận)
8. Tổng hợp Excel: CHỈ gộp báo giá trang_thai = 1
```

## bg_goi_thau (Gói thầu / Thông báo mời chào giá)

| Cột | Kiểu | Ghi chú |
|---|---|---|
| `so_thong_bao` | VARCHAR(100) | VD "5742/2026". UNIQUE cùng `da_xoa` |
| `ten_goi_thau` | VARCHAR(500) | |
| `noi_dung` | TEXT | Danh mục tóm tắt, hiện cho nhà thầu |
| `ngay_phat_hanh` / `han_cuoi` | DATE | Quá `han_cuoi` → cổng chào giá tự khóa |
| `thoi_gian_hop_dong` | INT | Số tháng |
| `hieu_luc_bao_gia` | INT | Số ngày tối thiểu nhà thầu phải cam kết |
| `token` | VARCHAR(64) | UNIQUE. Hex 32 ký tự, nhúng vào link QR |
| `trang_thai` | INT | 0=Nháp, 1=Đang mở, 2=Đã đóng, 3=Đã tổng hợp |

**Chỉ `trang_thai = 1` (Đang mở) mới nhận được báo giá** — xem `BG_GoiThau_BUS::kiemTraConNhan()`.

## bg_hang_hoa (Hàng hóa yêu cầu — cột A-K file mẫu)

| Cột DB | Cột Excel | Ghi chú |
|---|---|---|
| `ten_phan` | A | VD "Phần 1" |
| `stt_theo_phan` | B | VD "P1.1" |
| `stt_thong_bao` | C | |
| `ten_hang_hoa` | D | **Bắt buộc** — dòng thiếu cột này bị bỏ qua khi import |
| `thong_so_ky_thuat` | E | |
| `chung_nhan` | F | |
| `yeu_cau_xuat_xu` | G | |
| `dvt` | H | |
| `so_luong` | I | DECIMAL(18,3) — dùng tính thành tiền |
| `yeu_cau_tro_cu` | J | |
| `thu_tu` | — | Thứ tự hiển thị & khớp dòng khi import báo giá |

Cột K (Số thông báo) lấy từ `bg_goi_thau.so_thong_bao`, không lưu lặp.

## bg_bao_gia (1 lần nộp của 1 nhà thầu)

| Cột | Ghi chú |
|---|---|
| `ten_cong_ty`, `ma_so_thue`, `email`, `dien_thoai`, `dia_chi` | Nhà thầu tự khai |
| `hieu_luc_bao_gia` | Không được nhỏ hơn `bg_goi_thau.hieu_luc_bao_gia` |
| `trang_thai` | 0=Chờ xác nhận, 1=Đã xác nhận bản giấy, 2=Từ chối |
| `ngay_nop` | Nhà thầu bấm nộp online |
| `ngay_xac_nhan`, `nguoi_xac_nhan` | Bên mời tích xác nhận đã nhận bản giấy |
| `ly_do_tu_choi` | Bắt buộc khi `trang_thai = 2` |
| `tong_tien` | Cache `SUM(chi_tiet.thanh_tien)`, cập nhật bởi `updateTongTien()` |

1 MST chỉ nộp được 1 báo giá cho 1 gói thầu (trừ khi bản cũ đã bị từ chối).

## bg_bao_gia_chi_tiet (Dòng chào giá — cột L-AD file mẫu)

| Cột DB | Cột Excel | Ghi chú |
|---|---|---|
| `ten_thuong_mai` | L | |
| `model` | M | |
| `ma_hs` | N | |
| `hang_san_xuat` | O | |
| `xuat_xu` | P | |
| `quy_cach` | R | |
| `chi_phi_dich_vu` | T | |
| `thue_vat` | U | Lưu dạng **%**: 10 = 10%. Nhận cả "10%", "0.1", "0,1" |
| `don_gia` | V | Đã gồm thuế, phí |
| `thanh_tien` | W | **Server tự tính** = `don_gia × hang_hoa.so_luong` |
| `chung_nhan_chao` | X | |
| `don_gia_trung_thau` | Y | |
| `tai_lieu_tham_chieu` | Z | |
| `ma_qr_hang_hoa` | AA | |
| `thong_so_chao_gia` | AC | |
| `diem_khong_dat` | AD | |

Cột Q, S (số lượng/ĐVT nhà thầu) không lưu — lấy từ `bg_hang_hoa` để 2 bên không lệch.
UNIQUE `(bao_gia_id, hang_hoa_id)` → import lại là upsert, không nhân bản dòng.

## Quan hệ

- `bg_hang_hoa.goi_thau_id` → `bg_goi_thau.id`
- `bg_bao_gia.goi_thau_id` → `bg_goi_thau.id`
- `bg_bao_gia_chi_tiet.bao_gia_id` → `bg_bao_gia.id`
- `bg_bao_gia_chi_tiet.hang_hoa_id` → `bg_hang_hoa.id`

## Form & phân quyền

| Module key | Tên form |
|---|---|
| `BG_GoiThau` | Gói thầu / Mời chào giá |
| `BG_HangHoa` | Hàng hóa gói thầu |
| `BG_BaoGia` | Báo giá nhà thầu |
| `BG_TongHop` | Tổng hợp báo giá |

Nhóm `NHATHAU` + tài khoản `guest / 123456`: dùng chung cho nhà thầu quét QR.
Nhóm này **không có quyền nào** trong `dm_phan_quyen` — cổng nhà thầu
(`GUI/portal/`) kiểm soát bằng token + danh sách báo giá trong session,
không qua ma trận phân quyền. Xem chú thích đầu `GUI/portal/ajax_handler.php`.

---

## CẬP NHẬT: Thời gian nhận báo giá

> Tạo bởi `php database/migrate_thoi_gian_bao_gia.php` (idempotent).

Thêm vào `bg_goi_thau`:

| Cột | Kiểu | Ghi chú |
|---|---|---|
| `thoi_gian_mo_bao_gia` | DATETIME NULL | Bắt đầu nhận báo giá. NULL = nhận ngay khi mở |
| `thoi_gian_dong_bao_gia` | DATETIME NULL | Kết thúc nhận báo giá. NULL = không giới hạn giờ |

`han_cuoi` (DATE) được **giữ lại** nhưng chỉ còn để hiển thị/in trên thông báo.
Việc khóa cổng chào giá căn theo `thoi_gian_dong_bao_gia`.

### Trạng thái báo giá (suy ra, không lưu DB)

Khác `trang_thai` (người dùng đặt tay), trạng thái này **tính từ** `trang_thai`
+ 2 mốc thời gian, bởi `BG_GoiThau_PUBLIC::tinhTrangThaiBaoGia()`:

| Mã | Nhãn | Điều kiện | Nhà thầu quét QR làm được gì |
|---|---|---|---|
| `chua_mo` | Chưa mở báo giá | Đang mở, `now < thoi_gian_mo` | **Chỉ tra cứu** |
| `dang_mo` | Đang mở báo giá | Đang mở, trong khoảng | Điền + nộp báo giá |
| `het_han` | Hết thời gian báo giá | Đang mở, `now > thoi_gian_dong` | **Chỉ tra cứu** |
| `khong_nhan` | Không nhận báo giá | `trang_thai <> Đang mở` | Chỉ tra cứu |

**LƯU Ý khi sửa logic này:** điều kiện tồn tại ở **2 nơi phải khớp nhau**:
1. `BG_GoiThau_PUBLIC::tinhTrangThaiBaoGia()` — tính cho 1 bản ghi
2. `BG_GoiThau_DAL::getPaged()` — mệnh đề WHERE để lọc theo trạng thái

Sửa 1 bên thì phải sửa cả bên còn lại (đã có test so khớp 2 nguồn khi phát triển).

### Tra cứu báo giá theo MST

`BG_BaoGia_DAL::getByMstTrongGoiThau()` — so sánh MST **chính xác** (`=`, không LIKE)
trong đúng 1 gói thầu, nên nhà thầu không xem được của nhau.

Dùng ở 2 nơi:
- **Cổng nhà thầu** (`GUI/portal/`): khi ngoài thời gian chào giá, hiện form tra cứu.
  Tải Excel yêu cầu MST đó **đã tra cứu thành công trong phiên**
  (`portal_mst_tra_cuu` trong session) → chặn dò id báo giá của đối thủ.
- **Modal QR phía quản trị**: ô tra cứu theo MST + nút xuất Excel từng báo giá.
  Cần quyền `BG_BaoGia` / `quyen_xem` (khác quyền xem gói thầu).
