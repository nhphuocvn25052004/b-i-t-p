# Quản lý sản phẩm (React + PHP + MySQL)

Ứng dụng web quản lý sản phẩm: đăng nhập, xem, tìm kiếm, lọc theo danh mục, phân trang,
thêm / sửa / xóa (CRUD).

- **Giao diện:** React 18 (Vite, react-router-dom) — thư mục `src/`
- **Backend:** PHP thuần (mysqli) cung cấp API JSON — thư mục `api/`
- **Đăng nhập:** PHP Session + cookie HttpOnly; `admin` toàn quyền, `user` chỉ xem

Kiến thức nền (State/Props, Authentication/Authorization, Cookie/Session/JWT, luồng đăng nhập):
xem [docs/KIEN_THUC.md](docs/KIEN_THUC.md).

## Yêu cầu

- Node.js 18 trở lên (chỉ cần để build giao diện)
- PHP 7.4 trở lên, có extension `mysqli`
- MySQL 8 / MariaDB 11.4+
- Web server: Nginx hoặc Apache (có PHP-FPM)

## Cài đặt

1. Tạo CSDL:

   - **CSDL mới:** `mysql -u root -p < database.sql`
   - **CSDL cũ đã có dữ liệu** (bản trước chưa có danh mục / đăng nhập), giữ nguyên dữ liệu:
     `mysql -u root -p < migrations/001_categories_users.sql`

2. Tạo user MySQL cho web và cấp quyền trên `website_db`:

   ```sql
   CREATE USER 'webuser'@'localhost' IDENTIFIED BY 'mat_khau_cua_ban';
   GRANT SELECT, INSERT, UPDATE, DELETE ON website_db.* TO 'webuser'@'localhost';
   ```

3. Sao chép `config.example.php` thành `config.php` và điền thông tin kết nối.

4. Cài thư viện JavaScript: `npm install`

**Tài khoản demo** (tạo sẵn trong `database.sql` / migration):

| Tên đăng nhập | Mật khẩu   | Quyền                    |
|---------------|------------|--------------------------|
| `admin`       | `admin123` | xem, thêm, sửa, xóa      |
| `user`        | `user123`  | chỉ xem / tìm / lọc      |

Đổi mật khẩu hoặc xóa các tài khoản này trước khi đưa lên internet. Tạo hash mật khẩu mới:
`php -r "echo password_hash('mat_khau_moi', PASSWORD_DEFAULT);"` rồi `UPDATE users SET password_hash = '...'`.

## Chạy khi phát triển

```bash
php -S 127.0.0.1:8000    # cửa sổ 1: API PHP
npm run dev              # cửa sổ 2: giao diện React
```

Mở **http://localhost:5173** (không mở cổng 8000 hay file `index.html` trực tiếp).
Vite chuyển các request `api/...` sang PHP ở cổng 8000 (xem `vite.config.js`).

## Đưa lên server

```bash
npm run build            # tạo dist/ (index.html + assets/)
```

Chép nội dung `dist/` **cùng thư mục** với `api/` trên server:

```
/var/www/html/
├── index.html      ← từ dist/
├── assets/         ← từ dist/
├── api/
├── lib/
└── config.php
```

Ứng dụng dùng đường dẫn dạng `#/create`, `#/edit/3` nên không cần cấu hình rewrite.

## Cấu trúc thư mục

```
├── api/                      API JSON — mỗi file 1 nhóm chức năng
│   ├── auth.php              đăng nhập / đăng xuất / user hiện tại
│   ├── products.php          CRUD + tìm kiếm + lọc + phân trang
│   └── categories.php        danh sách danh mục
├── lib/
│   ├── bootstrap.php         nạp mọi file dưới đây + bắt lỗi chung (JSON 500) cho mọi API
│   ├── import.php            kết nối CSDL, tạo $conn
│   ├── functions.php         json_response(), read_json_body(), string_field(), db_query()...
│   ├── auth.php              session, require_login() (401), require_role() (403)
│   └── models/               mọi câu SQL
│       ├── product.php
│       ├── category.php
│       └── user.php
├── migrations/               nâng cấp CSDL cũ, không mất dữ liệu
├── scripts/export_db.php     xuất dữ liệu ra database.sql (dòng lệnh)
├── database.sql              tạo CSDL + dữ liệu mẫu
├── docs/KIEN_THUC.md         ghi chú kiến thức
└── src/                      React
    ├── main.jsx, App.jsx     khởi động, khai báo route
    ├── services/             gọi API (không có state)
    │   ├── http.js             fetch dùng chung, ApiError, xử lý 401
    │   ├── productService.js
    │   ├── categoryService.js
    │   └── authService.js
    ├── hooks/                custom hook (state + gọi service)
    │   ├── useAsync.js         [dùng chung] tải dữ liệu: data / loading / error / reload
    │   ├── useForm.js          [dùng chung] state form + handleChange + handleSubmit
    │   ├── useSubmit.js        [dùng chung] gửi form: submitting + error
    │   ├── useAuth.js          đọc AuthContext
    │   ├── useProducts.js      danh sách theo bộ lọc + phân trang (dựa trên useAsync)
    │   ├── useProduct.js       1 sản phẩm — trang Sửa (dựa trên useAsync)
    │   ├── useCategories.js    danh mục (dựa trên useAsync)
    │   ├── useProductFilters.js bộ lọc lưu trên URL (q, category, page, size)
    │   ├── useSelection.js     tick chọn để xóa nhiều
    │   └── useFlashMessage.js  thông báo giữa các trang
    ├── context/              AuthContext + AuthProvider (state đăng nhập dùng chung)
    ├── components/           hiển thị, nhận props
    │   ├── Layout.jsx, Alert.jsx, Loading.jsx, ProtectedRoute.jsx
    │   └── products/  SearchBar, CategoryFilter, ProductTable, Pagination, ProductForm
    ├── pages/                LoginPage, ProductListPage, ProductCreatePage, ProductEditPage
    └── utils/format.js
```

Luồng React: **Page** → **Hook** → **Service** → `http.js` → PHP `api/` → `lib/models/` → MySQL.

## API

Mọi API trả JSON; lỗi có dạng `{"error": "..."}`. Request có body phải gửi
`Content-Type: application/json`.

| Phương thức | URL                                                      | Quyền    | Kết quả |
|-------------|----------------------------------------------------------|----------|---------|
| GET         | `api/auth.php`                                           | —        | `{"user": {...} \| null}` |
| POST        | `api/auth.php?action=login` `{username, password}`       | —        | `{"user": {...}}`, sai → 401 |
| POST        | `api/auth.php?action=logout`                             | —        | `{"ok": true}` |
| GET         | `api/categories.php`                                     | đăng nhập | `[{id, name, product_count}]` |
| GET         | `api/products.php?q=&category=&page=1&per_page=10`       | đăng nhập | `{items, total, page, per_page, total_pages}` |
| GET         | `api/products.php?id=3`                                  | đăng nhập | 1 sản phẩm, không có → 404 |
| POST        | `api/products.php` `{name, price, description, category_id}` | admin | thêm (201) |
| PUT         | `api/products.php?id=3` `{...}`                          | admin    | cập nhật |
| DELETE      | `api/products.php` `{"ids": [1, 2]}`                     | admin    | `{"deleted": 2}` |

`category`: bỏ trống = tất cả, `none` = chưa phân loại, số = id danh mục.
`page` vượt quá số trang thì trả về trang cuối. `per_page` tối đa 100.
Mã lỗi: 401 chưa đăng nhập, 403 không đủ quyền, 404 không tìm thấy, 422 dữ liệu không hợp lệ.

## CSDL

**`categories`**: `id`, `name` (duy nhất)

**`products`**

| Cột           | Kiểu          | Ghi chú                                       |
|---------------|---------------|-----------------------------------------------|
| `id`          | INT           | Khóa chính, luôn liên tục                     |
| `name`        | VARCHAR(255)  | Bắt buộc                                      |
| `price`       | DECIMAL(12,2) | Bắt buộc, ≥ 0 (VND)                           |
| `description` | TEXT          | Không bắt buộc                                |
| `category_id` | INT NULL      | Khóa ngoại → `categories.id`; xóa danh mục thì về NULL |

**`users`**: `id`, `username` (duy nhất), `password_hash` (bcrypt), `role` (`admin` / `user`)

### Cách đánh số `id` sản phẩm

- **Thêm mới:** `id` = id lớn nhất hiện có + 1.
- **Xóa:** các sản phẩm có `id` lớn hơn lùi xuống 1, để id luôn là 1, 2, 3... (chạy trong transaction).

Vì id thay đổi sau khi xóa, **không** dùng `products.id` làm khóa ngoại ở bảng khác.

## Tìm kiếm, lọc, phân trang

- **Tìm kiếm:** trong tên và mô tả, không phân biệt hoa thường và dấu (`tau` tìm được `Tàu`).
  Ký tự `%` và `_` được tìm nguyên văn.
- **Lọc theo danh mục:** kết hợp được với tìm kiếm; có lựa chọn "Chưa phân loại".
- **Phân trang:** chọn 5 / 10 / 20 / 50 dòng mỗi trang. Bộ lọc và số trang nằm trên URL
  (`#/?q=tau&category=1&page=2`) nên F5 / Back / gửi link vẫn giữ nguyên.
- Đổi từ khóa / danh mục / số dòng thì quay về trang 1. Xóa hết trang cuối thì tự về trang trước.

## Sao lưu dữ liệu

```bash
php scripts/export_db.php   # MySQL → database.sql (danh mục + sản phẩm; không xuất users)
sudo mysql < database.sql   # database.sql → MySQL (XÓA bảng products, categories cũ; giữ users)
```

## Bảo mật

- SQL dùng prepared statement → chống SQL injection.
- React tự escape khi hiển thị → chống XSS.
- Mật khẩu băm bằng `password_hash`; cookie phiên `HttpOnly`, `SameSite=Lax`;
  đổi session id sau khi đăng nhập.
- API ghi dữ liệu bắt buộc JSON → form của trang khác không giả mạo được (CSRF).
- Quyền được kiểm tra ở **server** (`lib/auth.php`); ẩn nút trên giao diện chỉ để gọn.
- Không commit `config.php` (đã có trong `.gitignore`). Chỉ cần đưa `dist/`, `api/`, `lib/`,
  `config.php` lên server — không cần `src/`, `node_modules/`, `migrations/`, `*.sql`.
