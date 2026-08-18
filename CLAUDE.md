# CLAUDE.md — Hướng dẫn cho AI Assistant maintain hệ thống Tổng Hợp Báo Giá

> File này được Claude Code đọc tự động khi mở project. Mục tiêu: cung cấp đủ context để Claude hoặc dev mới bắt nhịp ngay.

---

## 1. Tổng quan

| Mục | Giá trị |
|---|---|
| **Tên** | Hệ thống tổng hợp báo giá (nền tảng: quản lý người dùng + phân quyền) |
| **Mục đích** | Bên mời phát mã QR → nhà thầu chào giá online/import Excel → xác nhận bản giấy → xuất Excel tổng hợp so sánh |
| **Stack** | PHP 8.0, MariaDB 10.4+, jQuery 3.7 (không framework, không Composer) |
| **Vị trí dev** | `d:\wwweb\thbaogia` (Windows + XAMPP) |
| **Domain dev** | `http://thbg.bv` |
| **Database** | `th_bao_gia` |

## 2. Kiến trúc

### 2.1. Mô hình 3-tier
```
GUI/<Module>/         Presentation: index.php (HTML+JS) + ajax_handler.php
   │
BUS/<Module>_BUS.php  Business logic, validate, transaction
   │
DAL/<Module>_DAL.php  Data access (PDO + named placeholder)
   │
PUBLIC/Entities/      DTO (typed properties)
PUBLIC/Common/        Helpers (Session, PhanQuyen, Mail, Icon...)
```

### 2.2. Quy ước tên
- **Bảng DB**: `snake_case` — `dm_nguoi_dung`, `dm_nhom_tai_khoan`.
- **Class PHP**: PascalCase + suffix layer — `DM_NguoiDung_BUS`, `DM_NhomTaiKhoan_DAL`.
- **File**: theo class — `BUS/DM_NguoiDung_BUS.php`.
- **Module key** (`dm_danh_sach_form.modules_tuong_ung`): `DM_NguoiDung`, `DM_NhomTaiKhoan`.

### 2.3. Entry point
- `index.php` (root) → redirect login hoặc dashboard.
- `bootstrap.php` → load AppConfig, helpers, DB, start session. **Mọi GUI/ajax_handler phải `require_once __DIR__ . '/../../bootstrap.php';` ở dòng đầu.**

---

## 3. Convention bắt buộc

### 3.1. Soft delete (KHÔNG DELETE thật)
- Luôn `UPDATE ... SET da_xoa = 1`.
- Mọi `SELECT` phải có `WHERE X.da_xoa = 0`.
- UNIQUE KEY luôn bao gồm `da_xoa` cuối: `UNIQUE (ma_x, da_xoa)` → cho phép tái dùng mã sau xoá.

### 3.2. Audit fields
Mọi bảng phải có:
`ngay_tao`, `ngay_cap_nhat`, `nguoi_tao`, `nguoi_cap_nhat`, `da_xoa`.
Set chúng từ `SessionHelper::userId()` ở BUS hoặc DAL.

### 3.3. PDO — KHÔNG reuse named placeholder
- `PDO::ATTR_EMULATE_PREPARES = false` (xem `DAL/database.php`) ⇒ một query không được dùng cùng `:name` 2 lần.
- Cần lặp giá trị (vd 5 LIKE search) → đặt `:s1, :s2, :s3, :s4, :s5` rồi bind từng giá trị.
- Đã từng gặp `HY093 Invalid parameter number` — luôn nhớ.
- **Batch insert: placeholder đánh số PHẢI có dấu ngăn `_`.**
  `:nt{$i}` + `:nt2{$i}` sẽ sinh ra `:nt20` từ cả (`nt2`,i=0) và (`nt`,i=20) → trùng tên,
  HY093 chỉ xuất hiện khi lô ≥ 21 dòng (test 5 dòng không phát hiện được).
  Đúng: `:ntao_{$i}`, `:ncn_{$i}`. Xem `BG_HangHoa_DAL::insertBatch()`.

### 3.4. LIMIT / OFFSET
- An toàn để nội suy trực tiếp **chỉ khi** đã ép int qua `PaginationHelper::normalize(int, int)` hoặc cast `(int)` rõ ràng:
  ```php
  [$page, $pageSize, $offset] = PaginationHelper::normalize($page, $pageSize);
  $sql .= " LIMIT {$pageSize} OFFSET {$offset}"; // OK — đã type-safe
  ```

### 3.5. AJAX response
Mọi ajax_handler trả qua `ResponseHelper`:
- `ResponseHelper::success($message, $data)`
- `ResponseHelper::error($message, $code)`
- `ResponseHelper::paged($data, $page, $size, $total)`

**Không** dùng `die()`, `exit`, `echo json_encode(...)` trực tiếp.

**Chỉ dùng mã HTTP chuẩn.** Apache từ chối mã phi chuẩn (vd `419`):
biến response thành `500` và **làm mất body JSON** → client không đọc được message.
Hết hạn CSRF dùng `403` + cờ `csrf_expired` (xem `Helper::requireAjaxCsrf()` và
nhánh xử lý trong `APP.ajax`). Đặt `ErrorDocument 419 default` còn tệ hơn:
Apache báo "Unsupported HTTP response code 419" và **sập toàn bộ site**.

File trả nhị phân (Excel) thì KHÔNG dùng ResponseHelper — viết `download.php`
riêng, gọi `ExcelHelper::download()`, và vẫn phải `requireLogin()` + check quyền.

### 3.6. Auth + Permission + CSRF ở mọi ajax_handler
Pattern chuẩn:
```php
require_once __DIR__ . '/../../bootstrap.php';
Helper::requireAjaxCsrf();                                  // 1. Login + CSRF
$action = Helper::post('action', '');
switch ($action) {
    case 'getPaged':
        PhanQuyenHelper::requireQuyen('DM_NguoiDung',
            PhanQuyenHelper::QUYEN_XEM);                    // 2. Permission
        // ... business logic
}
```

### 3.7. Transaction cho multi-table writes
Khi 1 action ghi vào ≥ 2 bảng, **bắt buộc** bọc transaction:
```php
try {
    Database::beginTransaction();
    // ... write A, B, C
    Database::commit();
} catch (Throwable $ex) {
    Database::rollBack();
    return ['success' => false, 'message' => 'Lỗi: ' . $ex->getMessage()];
}
```

### 3.8. Frontend
- jQuery 3.7 **local** (`assets/js/jquery-3.7.1.min.js`) — không dùng CDN (tránh phụ thuộc mạng + rủi ro supply chain).
- 1 file global `assets/js/app.js` chứa `APP.ajax/toast/confirm/escape/debounce/showLoading/formatDate/formatDateTime/renderPagination/bindPagination/emptyRow/icon`.
- Mỗi module có 1 `index.php` chứa cả HTML + `<script>` inline.
- Modal: `.modal > .modal-content`, thêm class `.open` vào `.modal` để hiện.
  (`APP.confirm` dựng `.modal-backdrop > .modal-content` — CSS hỗ trợ cả 2.)
- Phân trang: server trả `pagination.currentPage`; render bằng `APP.renderPagination(res.pagination)`
  rồi gắn sự kiện 1 lần bằng `APP.bindPagination('#paginationWrap', fn)`.

### 3.9. Icon
- Mọi icon qua `IconHelper::svg('name', size, class, color)`. Không hardcode SVG, **không dùng emoji**.
- **Icon theo module** (sidebar, ma trận phân quyền): dùng `IconHelper::moduleIcon($moduleKey, size)`.
  Bảng map nằm ở `IconHelper::MODULE_ICONS` — thêm module mới chỉ khai báo 1 chỗ đó,
  KHÔNG hardcode icon trong `sidebar.php` như trước.
- Phía JS dùng `APP.icon('name', size)`. Thêm icon mới phải thêm ở **cả 2**:
  `PUBLIC/Common/IconHelper.php` và mảng `ICONS` trong `assets/js/app.js`.
- ⚠️ **`APP.icon('ten-sai')` trả về chuỗi RỖNG, không báo lỗi JS** → nút hiện trống trơn
  mà không ai biết. Đã gặp thật: nút "Phân quyền" ở `DM_NhomTaiKhoan` gọi `shield-check`
  nhưng icon đó chỉ có ở IconHelper, chưa có trong app.js.
- **Sau khi thêm/sửa icon, chạy `php database/kiem_tra_icon.php`** — kiểm 3 việc:
  tên gọi qua `APP.icon()` có tồn tại, icon module đủ ở cả 2 nơi, và path trùng tên
  phải giống nhau. Exit code 1 nếu có vấn đề.
- Đổi file CSS/JS thì **tăng `AppConfig::APP_VERSION`** — nó là cache-buster `?v=`,
  không tăng thì browser vẫn dùng bản cũ đã cache.
- Bộ icon theo phong cách Lucide: outline, `viewBox="0 0 24 24"`, `stroke-width="2"`, `stroke="currentColor"`.
- Xem danh sách icon hiện có: `IconHelper::names()`.

### 3.10. Output XSS
- Echo biến vào HTML qua `Helper::h($val)`.
- JS hiện text qua `APP.escape(val)`.

### 3.11. Color / UI tokens
- `--primary: #16a34a` (xanh lá).
- Sidebar nền xanh đen `#1e293b → #0f172a`, active border-left `#ec4899` (hồng).

---

## 3B. BẢO MẬT — quy tắc bắt buộc

> Đây là phần **không được bỏ qua** khi thêm bất kỳ chức năng nào.
> Mỗi mục có lý do và cách áp dụng cụ thể.

### 3B.1. Xác thực & phân quyền — kiểm tra ở SERVER, luôn luôn

Ẩn nút trên giao diện **không phải** là bảo mật — chỉ là trải nghiệm. Kẻ tấn công gọi thẳng `ajax_handler.php`.

Mọi `ajax_handler.php` phải theo đúng thứ tự:

```php
require_once __DIR__ . '/../../bootstrap.php';
Helper::requireAjaxCsrf();                    // 1. Đăng nhập + CSRF
$action = Helper::post('action', '');
switch ($action) {
    case 'insert':
        PhanQuyenHelper::requireQuyen($MODULE, PhanQuyenHelper::QUYEN_THEM);  // 2. Quyền cho TỪNG action
        // 3. Nghiệp vụ
}
```

- **Mỗi `case` phải tự check quyền riêng** — không check 1 lần ở đầu file rồi dùng chung.
- Ánh xạ đúng quyền: đọc → `QUYEN_XEM`, tạo → `QUYEN_THEM`, sửa/khôi phục → `QUYEN_SUA`, xóa → `QUYEN_XOA`.
- Trang GUI dùng `PhanQuyenHelper::requireQuyenView($moduleKey)` (trả trang 403), **gọi trước khi require header**.
- Cờ `la_admin = 1` bỏ qua ma trận quyền → chỉ cấp cho nhóm thật sự cần.

### 3B.2. KHÔNG BAO GIỜ trả mật khẩu (hash) xuống client

- `DM_NguoiDung_DAL::selectSafeSql()` (dùng cho `getPaged`, `getById`) **không chứa** cột `mat_khau`.
- Chỉ `getByIdWithPassword()` / `getByTaiKhoan()` mới lấy hash — **chỉ dùng để xác thực**, không trả ra response.
- Khi thêm bảng có dữ liệu nhạy cảm (token, secret, số CMND...), làm tương tự: tạo `selectSafeSql()` riêng.

### 3B.3. Mật khẩu

- Luôn `password_hash()` với `AppConfig::PASSWORD_ALGO` + `PASSWORD_COST`. **Không** md5/sha1, không tự chế.
- So sánh bằng `password_verify()` — không dùng `==`.
- Chính sách tối thiểu qua `DM_NguoiDung_BUS::validatePassword()`: ≥ 8 ký tự, có cả chữ và số.
  Muốn siết thêm thì sửa **1 chỗ** này.
- Login tự `password_needs_rehash()` → nâng cấp hash khi đổi cost.

### 3B.4. Chống brute force đăng nhập

- `DM_NguoiDung_BUS::login()` đếm số lần sai theo `tài khoản + IP`:
  `MAX_LOGIN_ATTEMPTS = 5`, khóa `LOGIN_LOCKOUT_SECONDS = 900` (15 phút).
- Sai tài khoản / sai mật khẩu đều trả **cùng một thông báo** → không lộ tài khoản nào tồn tại.
- Mọi lần đăng nhập thất bại đều ghi `dm_nhat_ky_he_thong`.
- Bộ đếm hiện lưu trong session (đủ cho nội bộ). Nếu mở ra Internet → chuyển sang lưu DB/Redis theo IP.

### 3B.5. SQL Injection

- **Luôn** dùng prepared statement với named placeholder. **Không** nối chuỗi biến vào SQL.
- `PDO::ATTR_EMULATE_PREPARES = false` ⇒ **không reuse** cùng `:name` 2 lần → đặt `:s1, :s2, :s3...`.
- Chỉ được nội suy trực tiếp `LIMIT/OFFSET` sau khi qua `PaginationHelper::normalize()` (đã ép int).
- Tên bảng/cột **không bao giờ** lấy từ input người dùng. Cần động → whitelist:
  ```php
  $cotSapXep = in_array($input, ['id','ten_form','ngay_tao'], true) ? $input : 'id';
  ```

### 3B.6. XSS

- PHP: mọi biến in ra HTML phải qua `Helper::h($val)`.
- JS: mọi text từ server phải qua `APP.escape(val)` trước khi nối vào chuỗi HTML.
- **Không** dùng `$el.html(dataTuServer)` khi chưa escape. Ưu tiên `.text()` khi chỉ hiện chữ.
- Nhúng dữ liệu PHP vào JS bằng `json_encode()`, không nhúng thẳng vào dấu nháy.

### 3B.7. CSRF

- `Helper::requireAjaxCsrf()` ở **mọi** ajax_handler — không có ngoại lệ.
- Form POST thường (login...) phải có `<input type="hidden" name="_csrf">` + `SessionHelper::verifyCsrf()`.
- `APP.ajax()` tự gắn header `X-CSRF-Token` — dùng nó thay vì gọi `$.ajax` trực tiếp.
- Thao tác thay đổi dữ liệu phải là **POST**, không dùng GET.

### 3B.8. Session

- Cookie: `httponly` (JS không đọc được), `samesite=Lax`, `secure` tự bật khi chạy HTTPS.
- `session.use_strict_mode` + `use_only_cookies` → chống session fixation.
- `session_regenerate_id(true)` ngay sau khi đăng nhập thành công.
- Tự hết hạn sau `SESSION_LIFETIME` không hoạt động.

### 3B.9. Upload file (khi làm tính năng upload)

- Whitelist đuôi file qua `AppConfig::UPLOAD_ALLOWED_EXT`, kiểm tra cả MIME thật (`finfo_file`).
- **Đổi tên file** khi lưu (`Helper::randomString()`), không giữ tên gốc từ user.
- Không tin `$_FILES['x']['type']` (client gửi lên, giả được).
- `assets/uploads/.htaccess` đã chặn thực thi PHP — không xóa file này.
- Kiểm tra `UPLOAD_MAX_SIZE` ở cả PHP lẫn cấu hình server.

### 3B.10. Rò rỉ thông tin

- `AppConfig::APP_DEBUG = false` khi lên production ⇒ ẩn thông báo lỗi chi tiết.
- Handler đã bọc `try/catch` trả message chung khi tắt debug — giữ nguyên pattern này.
- **Không** commit mật khẩu DB thật; production nên đọc từ biến môi trường.
- Header bảo mật đã set ở `bootstrap.php` (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`).

### 3B.11. Ghi nhật ký

- Ghi log mọi hành động nhạy cảm: đăng nhập (thành công/thất bại), đổi/reset mật khẩu, đổi phân quyền, xóa dữ liệu.
- Dùng `DM_NhatKyHeThong_DAL::log($userId, $module, $hanhDong, $bang, $id, $noiDung)`.
- **Không** ghi mật khẩu, token hay dữ liệu nhạy cảm vào nội dung log.

### 3B.12. Checklist trước khi đưa tính năng lên production

- [ ] Mọi ajax_handler có `requireAjaxCsrf()` và check quyền theo từng action
- [ ] Không có query nối chuỗi; không reuse named placeholder
- [ ] Mọi output HTML đã `Helper::h()` / `APP.escape()`
- [ ] Response không chứa `mat_khau` hay dữ liệu nhạy cảm
- [ ] Ghi log cho hành động nhạy cảm
- [ ] `APP_DEBUG = false`, đổi mật khẩu mặc định của seed
- [ ] Ghi ≥ 2 bảng trong 1 action → đã bọc transaction

---

## 4. Tasks thường gặp

### 4.1. Thêm module CRUD mới
1. Thiết kế bảng DB (id + nghiệp vụ + audit + da_xoa, UNIQUE bao gồm da_xoa).
2. Tạo Entity DTO ở `PUBLIC/Entities/<Tên>_PUBLIC.php` (typed properties).
3. Tạo DAL: `selectSql()`, `getById()`, `getPaged($filter, $page, $size)`, `insert()`, `update()`, `softDelete()`.
4. Tạo BUS validate + gọi DAL, trả `['success'=>bool, 'message'=>str, 'data'=>...]`. Bọc transaction nếu multi-table.
5. Tạo `GUI/<Tên>/index.php` + `ajax_handler.php` (mở đầu bằng `Helper::requireAjaxCsrf()`).
6. Khai báo form trong `dm_danh_sach_form` (key + tên), gán quyền tại `dm_phan_quyen`.

### 4.2. Thêm cột vào bảng có sẵn
1. ALTER TABLE qua phpMyAdmin hoặc qua PHP script.
2. Thêm property vào DTO.
3. Cập nhật `selectSql()`, `insert()`, `update()` ở DAL.
4. Cập nhật form ở GUI (input + JS load/save).

### 4.3. Sửa quyền cho nhóm
- UI: `GUI/DM_PhanQuyen/index.php` (ma trận).
- Hoặc UPDATE `dm_phan_quyen` rồi `MemcachedHelper::deleteByPrefix('phan_quyen:')` để clear cache.

---

## 5. Helpers

| Helper | Mô tả |
|---|---|
| `Helper::h($val)` | Escape HTML |
| `Helper::post('key', $default)` / `postInt` / `postStr` | Lấy POST |
| `Helper::requireLogin()` | Redirect login nếu chưa đăng nhập |
| `Helper::requireAjaxLogin()` | Trả 401 JSON nếu chưa đăng nhập |
| **`Helper::requireAjaxCsrf()`** | Login + verify CSRF — dùng cho **mọi ajax_handler** |
| `SessionHelper::userId() / nhomTaiKhoanId() / taiKhoan() / hoTen()` | Đọc info user |
| `SessionHelper::csrfToken() / verifyCsrf($t)` | CSRF token |
| `PhanQuyenHelper::hasQuyen($key, $quyen) / requireQuyen()` | Check quyền (ajax → JSON 403) |
| `PhanQuyenHelper::requireQuyenView($key)` | Check quyền xem cho trang GUI (→ trang 403) |
| **`IconHelper::svg($name, $size, $class, $color)`** | Render icon SVG — nguồn icon duy nhất |
| `IconHelper::names()` | Liệt kê icon hiện có |
| `ResponseHelper::success / error / paged / json` | JSON response |
| `Database::beginTransaction / commit / rollBack` | Transaction |
| `Database::hydrate($row, $class)` | Map row → DTO |
| `MemcachedHelper::get / set / delete / deleteByPrefix` | Cache |
| `PaginationHelper::normalize($page, $size)` | Trả `[page, size, offset]` đã clamp |

### Frontend (`APP` namespace)
| Method | Mô tả |
|---|---|
| `APP.ajax(url, data, opts)` | $.ajax wrapper, **tự gắn CSRF**, handle 401 redirect |
| `APP.toast(msg, type)` | Notification (success/error/warning/info) |
| `APP.confirm(msg, onYes, opts)` | Modal xác nhận |
| `APP.escape(str)` | Escape HTML |
| `APP.debounce(fn, ms)` | Debounce |
| `APP.formatDate / formatDateTime` | Hiện dd/mm/Y [H:i] |
| `APP.renderPagination(info)` | Render UI phân trang (nhận `res.pagination`) |
| `APP.bindPagination(sel, fn)` | Gắn sự kiện click phân trang (gọi 1 lần) |
| `APP.showLoading / hideLoading` | Overlay loading |
| `APP.emptyRow(colspan, msg)` | Dòng "chưa có dữ liệu" cho bảng |
| `APP.icon(name, size)` | Render icon SVG phía JS |

---

## 6. Run / Test

- Web server: XAMPP Apache + MariaDB ở `C:\xampp`.
- DB credentials dev: user `root`, pass rỗng.
- Không có unit test. Test thủ công qua browser.

---

## 7. Files / Folders quan trọng

| Path | Vai trò |
|---|---|
| `bootstrap.php` | Entry bootstrap |
| `index.php` | Root redirect |
| `PUBLIC/Common/` | Helpers chung |
| `PUBLIC/Entities/` | DTOs |
| `assets/css/style.css` | CSS chính |
| `assets/js/app.js` | JS chung (APP namespace, tự gắn CSRF) |
| `GUI/layouts/header.php` | Layout (sidebar/topbar/CSRF token JS) |
| `GUI/layouts/sidebar.php` | Sidebar menu |
| `GUI/layouts/footer.php` | Layout footer + script toggle sidebar |
| `docs/database.md` | Schema documentation |
| `docs/README.md` | Hướng dẫn sử dụng |

---

## 8. Kim chỉ nam khi sửa code

1. **Soft delete > delete** — không xoá thật.
2. **Named placeholder không reuse** — `:s1, :s2…` khi cần lặp.
3. **`requireAjaxCsrf()` ở MỌI ajax_handler** — không skip.
4. **Check quyền ở TỪNG action** — ẩn nút ở UI không phải bảo mật (xem §3B.1).
5. **Không trả `mat_khau` xuống client** — dùng `selectSafeSql()` (xem §3B.2).
6. **`Helper::h()` mọi output** — không echo biến trực tiếp.
7. **`IconHelper::svg()` mọi icon** — không hardcode SVG, không emoji.
8. **Transaction cho ≥ 2 table writes** trong cùng action.
9. **Đọc 1 module hiện có** (ví dụ `DM_NguoiDung`) trước khi viết module mới — convention nhất quán.
10. **Đừng tự ý refactor lớn** — prefer pragmatic.
11. **Đừng tạo file thừa** (docs/note/plan riêng) trừ khi user yêu cầu.
12. **Chạy `php seed.php --reset`** để có lại dữ liệu test sạch khi cần.

---

## 9. Lưu ý khi làm việc với AI Assistant

- User dùng tiếng Việt; code/identifier không dấu.
- User prefer terse: làm đúng, không narrate, không comment dư.
- Nhiều bước → dùng TodoWrite, mark completed ngay khi xong từng bước.
- Tìm code: dùng Grep/Glob, không `find`/`grep` qua Bash.
- Trên Windows + Git Bash: dùng forward slash trong path.
- ALTER TABLE Unicode default: PHP script, không CLI mysql.

---

## 10. Nghiệp vụ tổng hợp báo giá

### 10.1. Luồng chính
```
Gói thầu (bg_goi_thau) → sinh token QR
   ↓ import Excel cột A-K
Hàng hóa (bg_hang_hoa)
   ↓ mở nhận báo giá + phát QR
Nhà thầu quét QR → login tài khoản chung (guest/123456)
   → khai thông tin công ty → bg_bao_gia (Chờ xác nhận)
   → tải file mẫu, điền cột L-AD, import  HOẶC  điền trực tiếp bảng web
   → nộp online (ngay_nop) → gửi BẢN GIẤY
   ↓
Bên mời tích XÁC NHẬN bản giấy → trang_thai = 1
   ↓
Xuất Excel tổng hợp — CHỈ gộp báo giá đã xác nhận
```

### 10.2. Quy tắc nghiệp vụ không được phá
- **Chỉ báo giá `trang_thai = 1` (Đã xác nhận bản giấy) vào bảng tổng hợp.** Đây là chốt kiểm soát chính.
- **Ngoài khoảng `thoi_gian_mo_bao_gia` → `thoi_gian_dong_bao_gia`: nhà thầu CHỈ tra cứu, không điền được.**
  Trạng thái tính bởi `BG_GoiThau_PUBLIC::tinhTrangThaiBaoGia()` — **nguồn duy nhất**.
  Điều kiện này lặp ở `BG_GoiThau_DAL::getPaged()` (mệnh đề WHERE để lọc) → **sửa 1 bên phải sửa cả 2**.
- `han_cuoi` chỉ để hiển thị/in. Khóa cổng chào giá căn theo `thoi_gian_dong_bao_gia`.
- Tra cứu theo MST so sánh **chính xác** (`=`, không LIKE) và giới hạn trong 1 gói thầu
  → nhà thầu không xem được báo giá của nhau.
- **Nhà thầu upload bản ký (PDF/ảnh có dấu + chữ ký) → TỰ chuyển sang "Đã xác nhận".**
  Đây là đường xác nhận thứ 2 bên cạnh việc bên mời tích tay khi nhận bản giấy.
  `nguoi_xac_nhan = NULL` để phân biệt: NULL = nhà thầu tự ký, có giá trị = nhân viên tích.
  Điều kiện: báo giá phải ĐÃ NỘP (`ngay_nop`) và có ≥ 1 dòng giá — chặn lách bằng cách
  upload file để thành "đã xác nhận" mà chưa hề chào giá.
- File bản ký lưu ở `assets/uploads/ban_ky/` (có `.htaccess` chặn truy cập thẳng),
  chỉ xem được qua `GUI/BG_BaoGia/xem_ban_ky.php` (quản trị, check quyền) hoặc
  `GUI/portal/download.php?loai=ban_ky` (nhà thầu, phải tra cứu đúng MST trước).
- **Tra cứu theo MST chỉ có ở cổng nhà thầu** (`GUI/portal/`) — không đặt ở modal QR
  phía quản trị. Bên mời xem báo giá thì vào module `BG_BaoGia` (đủ bộ lọc + tìm kiếm).
- Giao diện tra cứu: **nút nổi** `.fab-tracuu` góc dưới phải (mọi trạng thái của cổng)
  → mở **lớp phủ toàn trang** `#traCuuOverlay`. Nộp báo giá xong tự mở, điền sẵn MST.
- **Tra cứu là LIÊN GÓI**: `traCuuTatCaTheoMst()` trả TẤT CẢ báo giá của MST đó ở
  mọi gói thầu, nhóm theo từng gói. Nhà thầu thường chào nhiều gói nên cần xem 1 chỗ.
  Kéo theo: quyền tải file / upload bản ký cũng theo MST, KHÔNG giới hạn gói thầu
  (dùng `baoGiaCuaMst()`, không dùng `baoGiaThuocMst()` vốn bó trong 1 gói).
- `thanh_tien` **luôn** tính ở server = `don_gia × bg_hang_hoa.so_luong`. Không tin giá trị client gửi lên.
- 1 MST chỉ 1 báo giá / gói thầu (trừ bản đã bị từ chối).
- Gói thầu đã có báo giá → **không cho ghi đè** danh mục hàng hóa (lệch dữ liệu đã chào).
- Báo giá đã xác nhận → khóa, nhà thầu không sửa được nữa.
- `thue_vat` lưu dạng **%** (10 = 10%), nhận cả `"10%"`, `"0.1"`, `"0,1"`.

### 10.3. Cổng nhà thầu (`GUI/portal/`) — mô hình quyền RIÊNG
Nhà thầu dùng **tài khoản chung** `guest` không có quyền nào trong `dm_phan_quyen`,
nên portal **không** dùng `PhanQuyenHelper::requireQuyen()`. Thay vào đó mỗi action check:
1. `Helper::requireAjaxCsrf()` — login + CSRF
2. Token gói thầu lấy từ **session** (`portal_token`), không tin POST
3. `kiemTraBaoGiaThuocPhien()` — id báo giá phải nằm trong danh sách của phiên **và** thuộc đúng gói thầu
   → chặn IDOR giữa các nhà thầu dùng chung tài khoản
4. `BG_GoiThau_BUS::kiemTraConNhan()` — trạng thái + hạn cuối

Khi thêm action mới vào portal, **bắt buộc** giữ đủ 4 lớp này.

### 10.4. Excel — `PUBLIC/Common/ExcelHelper.php`
Reader/writer .xlsx thuần PHP (ZipArchive + XMLReader), **không cần Composer**.
- Đọc: `ExcelHelper::readSheet($path)` → `[dòng(1-based) => [cột(0-based) => giá trị]]`
- Ghi: `ExcelHelper::write($path, $sheets)` — hỗ trợ nhiều sheet, merge, freeze, style
- Style dùng hằng `S_HEADER`, `S_MONEY`, `S_BEST`, `S_TOTAL`... — thêm style mới phải
  **tăng `count` của `<cellXfs>`** cho khớp số phần tử, nếu không Excel báo file lỗi.
- `ExcelHelper::toNumber()` xử lý `"10.000,00"`, `"10,000.00"`, `"1000"`, `"5%"` → dùng cho MỌI ô số từ user.

### 10.5. QR — `PUBLIC/Common/QrHelper.php`
Sinh QR thuần PHP xuất SVG (không CDN, không thư viện ngoài). `QrHelper::svg($url, $size)`.
Hỗ trợ version 1-10 mức sửa lỗi M (đủ cho URL ~200 ký tự).

⚠️ **QR sai vẫn "trông như QR thật"** — vẫn đủ 3 ô định vị, vẫn vuông vắn, và bộ
giải mã tự viết vẫn đọc lại được (vì sai giống nhau ở cả 2 chiều mã hóa/giải mã).
Chỉ MÁY QUÉT THẬT mới phát hiện. Đã gặp 2 lỗi thuộc loại này ở phần format info:
1. Ghi **LSB-first** thay vì **MSB-first** (spec ghi bit cao trước)
2. Copy 2 lệch 1 ô: ranh giới đúng là `$i < 7`, không phải `$i < 8`
   (đặt sai làm bit 7 rơi vào ô dark module)

Cả 2 đều khiến máy quét đọc sai mask → không giải mã nổi, **dù dữ liệu và
Reed-Solomon hoàn toàn chính xác**.

**Sau khi đụng vào QrHelper, BẮT BUỘC chạy `php database/kiem_tra_qr.php`** —
script giải mã ngược độc lập, kiểm format info có trong bảng chuẩn ISO/IEC 18004,
2 bản copy khớp nhau, syndrome Reed-Solomon = 0, và nội dung giải ra đúng URL gốc.
Đã kiểm chứng script bắt được cả 2 lỗi trên.

### 10.6. Vị trí file nghiệp vụ
| Path | Vai trò |
|---|---|
| `database/migrate_bao_gia.php` | Tạo 4 bảng + form + nhóm/tài khoản nhà thầu |
| `database/seed_bao_gia.php` | Dữ liệu test (`--reset` để làm sạch) |
| `GUI/BG_GoiThau/` | CRUD gói thầu + modal QR |
| `GUI/BG_HangHoa/` | CRUD + import Excel danh mục hàng hóa |
| `GUI/BG_BaoGia/` | Xem báo giá, **xác nhận bản giấy**, từ chối, **xem/tải bản ký** |
| `GUI/BG_BaoGia/xem_ban_ky.php` | Gửi file bản ký (inline hoặc `?tai_ve=1` để tải) |
| `GUI/BG_TongHop/` | Bảng so sánh + xuất Excel (mỗi nhà thầu 1 dòng) |
| `GUI/portal/` | Cổng nhà thầu (token QR, layout riêng không sidebar) |
| `*/download.php` | Xuất file nhị phân (không qua ResponseHelper) |

---

## 11. Roadmap

- ✅ **Đăng nhập/đăng xuất**
- ✅ **CRUD người dùng**
- ✅ **CRUD nhóm tài khoản**
- ✅ **CRUD danh sách form**
- ✅ **Phân quyền ma trận**
- ✅ **Nhật ký hệ thống**
- ✅ **Gói thầu + mã QR chào giá**
- ✅ **Import hàng hóa từ Excel mẫu**
- ✅ **Cổng nhà thầu: khai thông tin, điền giá, import file**
- ✅ **Xác nhận bản giấy**
- ✅ **Tổng hợp báo giá + xuất Excel so sánh ngang**