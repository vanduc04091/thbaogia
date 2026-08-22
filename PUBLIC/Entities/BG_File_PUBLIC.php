<?php
/**
 * BG_File_PUBLIC — DTO cho bảng `bg_file`.
 *
 * Mỗi bản ghi = 1 file người dùng tải lên (hiện dùng cho bản báo giá có dấu
 * và chữ ký). Tách khỏi bg_bao_gia để sau này thêm loại file khác chỉ cần
 * đổi `nhom_file`, không phải thêm cột vào bảng nghiệp vụ.
 */
class BG_File_PUBLIC
{
    public ?int $id = null;

    /** Tên file trên đĩa: <mst>_<slug-goi-thau>.<ext> */
    public string $ten_file = '';

    /** Tên gốc người dùng đặt — chỉ để hiển thị, KHÔNG dùng làm đường dẫn */
    public ?string $ten_file_goc = null;

    /** Thư mục con trong assets/uploads/ */
    public string $duong_dan = 'ban_ky';

    public ?string $loai_file = null;   // pdf, jpg, png
    public ?string $mime_type = null;   // MIME thật đọc bằng finfo
    public int $kich_thuoc = 0;         // byte

    /** Phân loại nghiệp vụ — hiện chỉ có 'ban_ky' */
    public string $nhom_file = self::NHOM_BAN_KY;

    public ?string $ngay_tao = null;
    public ?string $ngay_cap_nhat = null;
    public ?int $nguoi_tao = null;
    public ?int $nguoi_cap_nhat = null;
    public int $da_xoa = 0;

    const NHOM_BAN_KY = 'ban_ky';
    const NHOM_CATALOG = 'catalog';   // Bước 5 — chỉ dẫn vị trí tài liệu

    /** Đuôi file được phép tải lên */
    const EXT_CHO_PHEP = ['pdf', 'jpg', 'jpeg', 'png'];

    /** MIME thật tương ứng — KHÔNG tin $_FILES['type'] do client gửi */
    const MIME_CHO_PHEP = ['application/pdf', 'image/jpeg', 'image/png'];

    /** Đuôi file phải khớp nội dung thật (chặn đổi tên .php thành .pdf) */
    const EXT_MIME = [
        'pdf'  => ['application/pdf'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
    ];

    /**
     * Bảng chỉ dẫn vị trí tài liệu (Bước 5) — nhà thầu điền vào file Word mẫu.
     * Nhận thêm PDF vì nhiều đơn vị in ký rồi scan lại thành PDF.
     * KHÔNG gộp vào EXT_CHO_PHEP vì bản ký chỉ nhận PDF/ảnh.
     */
    const EXT_CHI_DAN = ['docx', 'doc', 'pdf'];

    const EXT_MIME_CHI_DAN = [
        'docx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',            // .docx là zip — finfo hay nhận diện thế này
            'application/octet-stream',
        ],
        'doc'  => [
            'application/msword',
            'application/octet-stream',
        ],
        'pdf'  => ['application/pdf'],
    ];

    const NHOM_CATALOG_EXCEL = 'catalog_excel';

    public function laAnh(): bool
    {
        return in_array(strtolower((string)$this->loai_file), ['jpg', 'jpeg', 'png'], true);
    }

    /** Dung lượng dạng dễ đọc */
    public function kichThuocDep(): string
    {
        return self::dinhDangDungLuong($this->kich_thuoc);
    }

    public static function dinhDangDungLuong(int $byte): string
    {
        if ($byte <= 0) return '—';
        if ($byte < 1024) return $byte . ' B';
        if ($byte < 1048576) return number_format($byte / 1024, 0) . ' KB';
        return number_format($byte / 1048576, 1) . ' MB';
    }
}
