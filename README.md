# Quản lý sản phẩm (React + PHP + MySQL)

Ứng dụng web nhỏ để xem, thêm, sửa, xóa sản phẩm (CRUD) trong bảng `products`.

- **Giao diện:** React 18 (Vite, react-router-dom) — thư mục `src/`
- **Backend:** PHP thuần (mysqli) cung cấp API JSON — file `api.php`

## Yêu cầu

- Node.js 18 trở lên (chỉ cần để build giao diện)
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

3. Sao chép `config.example.php` thành `config.php` và điền thông tin kết nối.

4. Cài thư viện JavaScript:

   ```bash
   npm install
   ```

## Chạy khi phát triển

Mở 2 cửa sổ dòng lệnh:

```bash
php -S 127.0.0.1:8000    # API PHP
npm run dev              # giao diện React tại http://localhost:5173
```

Vite tự chuyển các request `api.php` sang PHP ở cổng 8000 (xem `vite.config.js`).

## Đưa lên server

```bash
npm run build            # tạo thư mục dist/ (index.html + assets/)
```

Đặt `dist/index.html`, `dist/assets/` **cùng thư mục** với `api.php` trên server, ví dụ:

```
/var/www/html/
├── index.html      ← từ dist/
├── assets/         ← từ dist/
├── api.php
├── product.php
├── config.php
└── lib/
```

Mở `http://<địa-chỉ-server>/`. Ứng dụng dùng đường dẫn dạng `#/create`, `#/edit/3`
nên không cần cấu hình rewrite cho web server.

## Cấu trúc thư mục

```
├── api.php               API JSON (GET / POST / PUT / DELETE) cho giao diện React
├── product.php           model: mọi câu SQL của bảng products
├── config.example.php    mẫu file cấu hình CSDL (copy thành config.php)
├── database.sql          script tạo CSDL + dữ liệu (tạo bởi scripts/export_db.php)
├── scripts/
│   └── export_db.php     xuất dữ liệu hiện tại ra database.sql (chỉ chạy bằng dòng lệnh)
├── lib/
│   ├── bootstrap.php     nạp import.php, functions.php, product.php
│   ├── import.php        kết nối CSDL, tạo biến $conn
│   └── functions.php     json_response(), json_error(), read_json_body()
├── index.html            trang gốc của React (Vite)
├── vite.config.js
├── package.json
└── src/
    ├── main.jsx          điểm khởi động React
    ├── App.jsx           khai báo các route
    ├── api.js            hàm gọi api.php, định dạng giá
    ├── style.css         giao diện
    ├── components/
    │   ├── Layout.jsx      tiêu đề trang + thông báo
    │   └── ProductForm.jsx form dùng chung cho Thêm và Sửa
    └── pages/
        ├── ProductList.jsx   #/            danh sách, tìm kiếm (?q=), xóa 1 / xóa nhiều
        ├── ProductCreate.jsx #/create      thêm sản phẩm
        └── ProductEdit.jsx   #/edit/:id    sửa sản phẩm
```

## API (`api.php`)

| Phương thức | URL                 | Body JSON                          | Kết quả                 |
|-------------|---------------------|------------------------------------|-------------------------|
| GET         | `api.php`           |                                    | danh sách sản phẩm      |
| GET         | `api.php?q=tau`     |                                    | kết quả tìm kiếm        |
| GET         | `api.php?id=3`      |                                    | 1 sản phẩm (404 nếu không có) |
| POST        | `api.php`           | `{name, price, description}`       | thêm mới (201)          |
| PUT         | `api.php?id=3`      | `{name, price, description}`       | cập nhật                |
| DELETE      | `api.php`           | `{"ids": [1, 2]}`                  | `{"deleted": 2}`        |

Lỗi trả về dạng `{"error": "..."}` (422 khi dữ liệu không hợp lệ).

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

```bash
php scripts/export_db.php   # tải dữ liệu từ MySQL về file database.sql
sudo mysql < database.sql   # nạp dữ liệu từ file database.sql vào MySQL (XÓA bảng products cũ)
```

## Bảo mật

- Mọi câu SQL dùng prepared statement → chống SQL injection.
- React tự escape dữ liệu khi hiển thị → chống XSS.
- Xóa chỉ thực hiện qua method DELETE của API.
- **Chưa có đăng nhập:** ai truy cập được trang (hoặc gọi thẳng `api.php`) đều thêm / sửa / xóa được.
  Nên thêm đăng nhập hoặc giới hạn IP trước khi đưa lên internet.
- Không commit `config.php` (đã có trong `.gitignore`).
- Không để file chứa mật khẩu (ví dụ file `.sql` tạo user MySQL) trong `/var/www/html`:
  Nginx cho phép tải trực tiếp mọi file không phải `.php` trong thư mục này.
- Không cần đưa `src/`, `node_modules/`, `package.json` lên server — chỉ cần kết quả trong `dist/`.
