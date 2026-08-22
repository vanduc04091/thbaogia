THU MUC MPS — MAU FILE WORD
===========================

Chua cac file .docx mau dung de sinh van ban cho nha thau.
He thong chi THAY cac {{KEY}} bang du lieu that, GIU NGUYEN moi dinh dang
ban da dat trong Word (font, co chu, can le, mau sac, logo, header/footer).

FILE HIEN CO
------------
  bao_gia.docx   Ban BAO GIA nha thau tai ve o Buoc 4 de in ra ky + dong dau.
                 Gom: BAO GIA (bang chao gia Mau 2) + BANG DAP UNG KY THUAT (Mau 1).

CACH SUA
--------
1. Mo file bang Microsoft Word.
2. Sua thoai mai: font, co chu, mau, can le, them logo/header/footer...
3. GIU NGUYEN cac {{KEY}} — xoa key nao thi cho do se trong.
4. Luu lai (Ctrl+S). Khong can lam gi them, lan tai tiep theo se dung ban moi.

DANH SACH KEY
-------------
Key thuong (thay 1 gia tri):
  {{GIOI_THIEU}}    Ten + MST + dia chi + DT + email cua cong ty
  {{TEN_CONG_TY}}   Ten cong ty
  {{MST}}           Ma so thue
  {{DIA_CHI}}       Dia chi
  {{DIEN_THOAI}}    So dien thoai
  {{EMAIL}}         Email
  {{SO_THONG_BAO}}  So thu moi cua goi thau
  {{TEN_GOI_THAU}}  Ten goi thau
  {{HIEU_LUC}}      So ngay hieu luc bao gia
  {{NGAY_NOP}}      Ngay nop bao gia (dd/mm/yyyy)
  {{TONG_TIEN}}     Tong tien da dinh dang 1.234.567
  {{NGAY_IN}}       Ngay in file

Nhom dong lap (dat trong 1 DONG cua BANG — dong do se duoc nhan ban
cho tung hang hoa):

  {{#CHAO_GIA}} — Bang chao gia (Mau 2). Key con dung trong cung dong:
      {{STT}} {{MA_HH}} {{TEN_HANG_HOA}} {{TEN_THUONG_MAI}} {{MODEL}}
      {{HANG_SAN_XUAT}} {{XUAT_XU}} {{SO_LUONG}} {{QUY_CACH}} {{DVT}}
      {{DON_GIA}} {{THANH_TIEN}} {{DON_GIA_TRUNG_THAU}} {{TAI_LIEU_THAM_CHIEU}}

  {{#DAP_UNG}} — Bang dap ung ky thuat (Mau 1). Key con:
      {{STT}} {{MA_HH}} {{TEN_HANG_HOA}} {{YEU_CAU_KY_THUAT}}
      {{THONG_SO_CHAO_GIA}} {{DIEM_KHONG_DAT}}

LUU Y
-----
- Dong chua {{#...}} la DONG MAU. Muon doi dinh dang MOI dong du lieu thi
  sua dinh dang cua chinh dong mau do.
- Muon them cot: them o vao ca dong tieu de va dong mau, roi dat key vao.
- Muon bo cot: xoa o do o ca 2 dong.
- KHONG doi ten file bao_gia.docx (code goi theo ten nay).
- Muon tao lai file mau goc (mat het chinh sua):
      php database/tao_mau_word.php --ghi-de
