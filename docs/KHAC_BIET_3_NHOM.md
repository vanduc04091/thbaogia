# Khác biệt giữa 3 nhóm gói thầu

> Tài liệu soát lại nghiệp vụ. Mọi con số dưới đây được lấy từ code đang chạy
> (`BG_Nhom_PUBLIC`, `BG_HangHoa_BUS::cotDanhMuc()`, `xuatMau1/xuatMau2`,
> `xuatWordBanKy`), không phải chép tay.
>
> **Nguồn duy nhất của quy tắc:** `PUBLIC/Entities/BG_Nhom_PUBLIC.php`.
> Cần biết "nhóm này có cột gì / tính tiền kiểu gì" thì hỏi class đó, KHÔNG
> viết lại điều kiện theo tên nhóm ở nơi khác.

---

## 1. Bảng tóm tắt

| | Bộ y dụng cụ | Hệ thống máy, TBYT | Vật tư, dược |
|---|---|---|---|
| Mã nhóm | `bo_dung_cu` | `he_thong_tbyt` | `vat_tu_duoc` |
| Yêu cầu chung | có | có | — |
| Yêu cầu khác | có | có | — |
| Yêu cầu cấu hình | — | **có** | — |
| Yêu cầu kỹ thuật | có (ở chi tiết) | có (ở chi tiết) | có |
| Nhóm nước / vùng lãnh thổ | có | có | có |
| Mua theo BỘ | có | có | không |
| **Tiền của BỘ** | **cộng dồn từ chi tiết** | **nhà thầu nhập tay** | không dùng |

Nhóm mặc định khi tạo gói mới và cho dữ liệu cũ: `vat_tu_duoc`.

---

## 2. Số cột từng loại file

| File | Bộ y dụng cụ | Hệ thống TBYT | Vật tư, dược |
|---|---|---|---|
| **Phụ lục III** (bên mời import danh mục) | 11 | 12 | **9** |
| **Mẫu 1** — bảng đáp ứng (nhà thầu điền) | 18 | 21 | **12** |
| **Mẫu 2** — bảng chào giá (nhà thầu điền) | 14 | 14 | 14 |
| **Word Bước 4** — bảng chào giá | 14 | 14 | 14 |
| **Word Bước 4** — bảng đáp ứng | 18 | 21 | **12** |

Mẫu 2 giữ **14 cột cho cả 3 nhóm** (đúng Phụ lục II Thư mời) — khác biệt nằm
ở chỗ *ô nào được phép điền*, không phải số cột.

### Cột bị bỏ ở Phụ lục III (nhóm vật tư dược)
Bỏ hẳn 3 cột, không còn giữ chỗ rồi ghi "(không áp dụng)":
`Yêu cầu chung`, `Yêu cầu khác`, `Yêu cầu cấu hình`.

### Cột bị bỏ ở bảng đáp ứng Word (`WordTemplate::boCot()`)
Mẫu Word là **tĩnh 21 cột**; để cột rỗng thì bản in bị ép ngang không đọc nổi,
nên cắt hẳn cột khỏi file:

| Nhóm | Chỉ số cột bỏ (0-based) | Cột |
|---|---|---|
| Vật tư dược | 5,6,7,10,11,12,13,14,15 | 3 cột yêu cầu + 6 cột đáp ứng/không đáp ứng (chung, khác, cấu hình) |
| Bộ y dụng cụ | 7,14,15 | Yêu cầu cấu hình + Đáp ứng/Không đáp ứng cấu hình |
| Hệ thống TBYT | — | giữ đủ 21 |

Cắt đồng thời 3 chỗ, thiếu một là Word báo file hỏng: `<w:gridCol>` trong
`<w:tblGrid>`, `<w:tc>` trên **mọi** `<w:tr>`, và trừ lại `w:w` của `<w:tblW>`.

---

## 3. Nhà thầu điền giá ở đâu (Mẫu 2)

| | Dòng BỘ | Dòng hàng hóa chi tiết |
|---|---|---|
| **Bộ y dụng cụ** | khóa (nền trắng) | **mở** — điền đơn giá từng dụng cụ |
| **Hệ thống TBYT** | **mở** — điền đơn giá + thành tiền trọn bộ | khóa (nền trắng) |
| **Vật tư, dược** | (thường không có bộ) | **mở** |

Lý do khác nhau: bộ dụng cụ là tập hợp dụng cụ rời, mỗi cái một đơn giá →
tiền của bộ là tổng các chi tiết. Hệ thống TBYT chào giá trọn gói, giá không
chia đều được theo từng thành phần → nhà thầu tự ghi.

## 4. Nhà thầu điền đáp ứng ở đâu (Mẫu 1)

| Cặp đáp ứng | Điền ở |
|---|---|
| Yêu cầu chung / khác / cấu hình | **DÒNG BỘ** (vì các yêu cầu này gắn với bộ) |
| Yêu cầu kỹ thuật | dòng hàng hóa chi tiết |
| Nhóm nước, vùng lãnh thổ | dòng hàng hóa chi tiết |
| Tài liệu chứng minh | cả hai |

Ô cần điền tô **nền vàng**, ô của bên mời để nền trắng. Điền vào ô nền trắng
sẽ KHÔNG được ghi nhận.

Đáp ứng cấp bộ lưu ở bảng riêng `bg_bao_gia_bo` (11 cột đáp ứng), vì
`bg_bao_gia_chi_tiet` bắt buộc gắn với một hàng hóa (`hang_hoa_id NOT NULL`).

---

## 5. Công thức tính tiền

`BG_BaoGia_DAL::updateTongTien()` phân nhánh theo
`BG_Nhom_PUBLIC::giaBoNhapTay()`:

**Hệ thống TBYT** (nhập tay):
```
tổng = SUM(thành tiền các BỘ)  +  SUM(thành tiền hàng LẺ)
```
Chi tiết trong bộ KHÔNG cộng — chúng không có giá. Cộng vào sẽ nhân đôi.

**Bộ y dụng cụ** (cộng dồn) và **Vật tư dược**:
```
tổng = SUM(thành tiền TẤT CẢ hàng hóa chi tiết)
```
Dòng `bg_bao_gia_bo` của bộ y dụng cụ KHÔNG cộng — nhóm này không nhập giá bộ,
dòng đó chỉ mang phần đáp ứng.

⚠️ Chọn sai nhánh là **nhân đôi** hoặc **mất trắng** tiền của cả gói. Công thức
này còn được lặp lại ở `getBangChaoGia()` và `xuatWordBanKy()` — sửa một chỗ
phải sửa cả ba, nếu không số trên màn hình sẽ lệch số trong DB.

### Thành tiền hiển thị ở dòng BỘ

| Nhóm | Lấy từ |
|---|---|
| Bộ y dụng cụ | tổng thành tiền các chi tiết thuộc bộ |
| Hệ thống TBYT | số nhà thầu nhập ở `bg_bao_gia_bo.thanh_tien` |

Với hệ thống TBYT, thành tiền bộ **cố ý khác** tổng chi tiết (chi tiết thường
không có giá) — đây không phải lỗi.

> **Ngoại lệ có chủ ý với §10.2:** thành tiền của bộ ở nhóm hệ thống TBYT do
> nhà thầu NHẬP TAY, không tính ở server. Giá trọn gói không phải lúc nào cũng
> chia đều theo số lượng bộ. Đừng "sửa lại cho đúng chuẩn".

---

## 6. Nhận dạng dòng BỘ khi import

`bg_bo.ma_bo` cho phép NULL → dòng bộ trong file mẫu có thể có ô Mã **trống**.
`BG_BaoGia_BUS::nhanDangDongBo()` thử theo thứ tự:

1. Theo **Mã bộ** (chắc chắn nhất)
2. Không có mã → theo **Tên bộ**, trùng tên thì lọc tiếp bằng **STT bộ**

Trước đây chỉ nhận theo mã, nên bộ không đặt mã thì mọi dữ liệu nhà thầu điền
ở dòng bộ bị bỏ qua **im lặng**. Đây là lỗi đã gặp thật.

---

## 7. Nơi phân nhánh theo nhóm trong code

| File | Phân nhánh gì |
|---|---|
| `PUBLIC/Entities/BG_Nhom_PUBLIC.php` | **nguồn duy nhất** của mọi quy tắc |
| `DAL/BG_BaoGia_DAL.php` | công thức `updateTongTien()` |
| `BUS/BG_BaoGia_BUS.php` | cộng tổng ở `getBangChaoGia()`, `xuatWordBanKy()`; bỏ cột Word |
| `BUS/BG_HangHoa_BUS.php` | số cột Phụ lục III, ô mở/khóa ở Mẫu 1 và Mẫu 2 |
| `BUS/BG_TongHop_BUS.php` | cột bảng tổng hợp, giá thấp nhất theo bộ |
| `BUS/BG_Bo_BUS.php` | cột nào của bộ được lưu (xóa cột nhóm không dùng) |
| `PUBLIC/Common/WordTemplate.php` | `boCot()` — cắt cột khỏi file Word |
| `GUI/portal/index.php` | `GIA_THEO_BO` — dòng bộ hiện cột hay gộp ô |
| `GUI/BG_BaoGia/index.php` | `CT_GIA_THEO_BO` — như trên, ở màn quản trị |

---

## 8. Điểm giống nhau (cả 3 nhóm)

- Mẫu 2 luôn 14 cột, đúng Phụ lục II.
- Cặp **kỹ thuật** và **nhóm nước** luôn có.
- Cột **Tài liệu chứng minh** luôn có.
- Quy trình cổng nhà thầu: 4 bước, phải đi liên tục, chưa hoàn thành thì
  cron xóa sau 24h.
- Thành tiền của **hàng hóa chi tiết** luôn tính ở server
  (`đơn giá × số lượng`), không tin số client gửi lên.
- Chỉ báo giá `trang_thai = 1` (Đã duyệt) mới vào bảng tổng hợp.
