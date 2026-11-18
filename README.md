# Quản lý sản phẩm (PHP + MySQL)

Ứng dụng web nhỏ để xem, thêm, sửa, xóa sản phẩm (CRUD) trong bảng `products`.
Viết bằng PHP thuần (mysqli), không dùng framework.

## Yêu cầu

- PHP 7.4 trở lên, có extension `mysqli`
- MySQL / MariaDB
- Web server: Nginx hoặc Apache (có PHP-FPM)

## Cài đặt

1. Tạo CSDL và dữ liệu mẫu:

   ```bash
   mysql -u root -p < database.sql
   ```

2. Tạo user MySQL cho web và cấp quyền trên `website_db`:

   ```sql
   CREATE USER 'webuser'@'localhost' IDENTIFIED BY 'mat_khau_cua_ban';
   GRANT SELECT, INSERT, UPDATE, DELETE ON website_db.* TO 'webuser'@'localhost';
   ```

3. Khai báo thông tin kết nối trong [lib/import.php](lib/import.php)
   (host, user, mật khẩu, tên CSDL).

4. Đặt thư mục vào web root (ví dụ `/var/www/html`) và mở `http://<địa-chỉ-server>/index.php`.

   Chạy thử nhanh không cần web server:

   ```bash
   php -S 127.0.0.1:8000
   ```

## Cấu trúc thư mục

```
├── index.php             READ   – danh sách sản phẩm
├── create.php            CREATE – thêm sản phẩm
├── edit.php              UPDATE – sửa sản phẩm (?id=...)
├── delete.php            DELETE – xóa 1 sản phẩm (chỉ nhận POST)
├── bulk_delete.php       DELETE – xóa nhiều sản phẩm đã chọn (chỉ nhận POST, ids[])
├── search.php            tìm kiếm theo tên / mô tả (?q=từ khóa)
├── product.php           model: mọi câu SQL của bảng products
├── database.sql          script tạo CSDL + dữ liệu (tạo bởi scripts/export_db.php)
├── scripts/
│   └── export_db.php     xuất dữ liệu hiện tại ra database.sql (chỉ chạy bằng dòng lệnh)
├── lib/
│   ├── bootstrap.php     nạp import.php, functions.php, product.php — mỗi trang chỉ cần require file này
│   ├── import.php        kết nối CSDL, tạo biến $conn
│   └── functions.php     hàm tiện ích: e(), redirect(), flash_message()
├── views/
│   ├── header.php        phần đầu trang, tiêu đề, thông báo
│   ├── footer.php        phần cuối trang
│   ├── product_form.php  form dùng chung cho Thêm và Sửa
│   └── product_table.php bảng sản phẩm + ô tìm kiếm + nút xóa nhiều (dùng cho index và search)
└── assets/
    └── style.css         giao diện
```

Luồng xử lý của mỗi trang: `require lib/bootstrap.php` → gọi hàm trong `product.php`
→ hiển thị bằng các file trong `views/`.

## Bảng `products`

| Cột           | Kiểu          | Ghi chú                    |
|---------------|---------------|----------------------------|
| `id`          | INT           | Khóa chính, luôn liên tục  |
| `name`        | VARCHAR(255)  | Bắt buộc                   |
| `price`       | DECIMAL(12,2) | Bắt buộc, ≥ 0 (VND)        |
| `description` | TEXT          | Không bắt buộc             |

## Cách đánh số `id`

- **Thêm mới:** `id` = id lớn nhất hiện có + 1 (không dùng AUTO_INCREMENT nên không bị nhảy số).
- **Xóa:** sau khi xóa, mọi sản phẩm có `id` lớn hơn được lùi xuống 1, để id luôn là 1, 2, 3...
  Việc xóa và dời id chạy trong một transaction.

Lưu ý: vì id thay đổi sau khi xóa, **không** dùng `id` để liên kết sang bảng khác
(khóa ngoại) hay lưu làm đường dẫn cố định.

## Tìm kiếm và xóa nhiều

- **Tìm kiếm:** gõ từ khóa vào ô tìm kiếm → tìm trong tên và mô tả, không phân biệt
  hoa thường và không phân biệt dấu (gõ `tau` tìm được `Tàu`). Ký tự `%` và `_` được tìm nguyên văn.
- **Xóa nhiều:** tick chọn các dòng (hoặc ô ở tiêu đề để chọn tất cả) → bấm **Xóa đã chọn**.
  Toàn bộ thao tác chạy trong 1 transaction và id được đánh số lại liên tục như khi xóa 1 sản phẩm.

## Sao lưu dữ liệu ra `database.sql`

Ghi đè `database.sql` bằng dữ liệu hiện tại trong CSDL (giữ nguyên id):

```bash
php scripts/export_db.php
```

Khôi phục lại từ file (sẽ **xóa** bảng `products` hiện có rồi tạo lại):

```bash
sudo mysql < database.sql
```

Tóm tắt:

```bash
cd /var/www/html
php scripts/export_db.php   # tải dữ liệu từ MySQL về file database.sql
sudo mysql < database.sql   # nạp dữ liệu từ file database.sql vào MySQL
```

## Bảo mật

- Mọi câu SQL dùng prepared statement → chống SQL injection.
- Mọi dữ liệu in ra HTML đi qua hàm `e()` → chống XSS.
- Xóa chỉ thực hiện qua POST, mở link `delete.php?id=...` không xóa được.
- **Chưa có đăng nhập:** ai truy cập được trang đều thêm / sửa / xóa được.
  Nên thêm đăng nhập hoặc giới hạn IP trước khi đưa lên internet.
- Không commit mật khẩu CSDL trong `lib/import.php` lên kho mã công khai.
- Không để file chứa mật khẩu (ví dụ file `.sql` tạo user MySQL) trong `/var/www/html`:
  Nginx cho phép tải trực tiếp mọi file không phải `.php` trong thư mục này.

## Thêm một bảng mới

1. Sao chép `product.php` thành `<ten>.php`, đổi tên bảng / cột / tên hàm.
2. Thêm `require_once` file đó vào `lib/bootstrap.php`.
3. Sao chép `index.php`, `create.php`, `edit.php`, `delete.php`, `views/product_form.php`
   và sửa cho phù hợp.
