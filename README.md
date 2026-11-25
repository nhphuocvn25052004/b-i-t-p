# Quản lý sản phẩm (React + PHP + MySQL)

Ứng dụng web quản lý sản phẩm: đăng nhập / đăng ký / đổi mật khẩu, xem, tìm kiếm, lọc theo
danh mục, phân trang, thêm / sửa / xóa (CRUD), quản lý người dùng.

- **Giao diện:** React 18 (Vite, react-router-dom) — thư mục `frontend/`, phục vụ qua `index.php`
- **Backend:** PHP thuần (mysqli) cung cấp API JSON — thư mục `api/`
- **Đăng nhập:** PHP Session + cookie HttpOnly; `admin` toàn quyền, `user` chỉ xem.
  Tự đăng ký (vai trò `user`), đổi mật khẩu, khóa tạm khi nhập sai mật khẩu quá 3 lần

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
   - **CSDL cũ đã có dữ liệu**, giữ nguyên dữ liệu — chạy lần lượt các migration chưa chạy:
     - bản chưa có danh mục / đăng nhập: `mysql -u root -p < migrations/001_categories_users.sql`
     - bản chưa có chống dò mật khẩu / ngày tạo tài khoản:
       `mysql -u root -p < migrations/002_login_attempts_user_created_at.sql`

2. Tạo user MySQL cho web và cấp quyền trên `website_db`:

   ```sql
   CREATE USER 'webuser'@'localhost' IDENTIFIED BY 'mat_khau_cua_ban';
   GRANT SELECT, INSERT, UPDATE, DELETE ON website_db.* TO 'webuser'@'localhost';
   ```

3. Sao chép `config.example.php` thành `config.php` và điền thông tin kết nối.

4. Cài thư viện JavaScript: `cd frontend && npm install`

**Tài khoản demo** (tạo sẵn trong `database.sql` / migration):

| Tên đăng nhập | Mật khẩu   | Quyền                    |
|---------------|------------|--------------------------|
| `admin`       | `admin123` | xem, thêm, sửa, xóa      |
| `user`        | `user123`  | chỉ xem / tìm / lọc      |

Đổi mật khẩu (menu **Đổi mật khẩu**) hoặc xóa các tài khoản này (menu **Người dùng**)
trước khi đưa lên internet.

**Quy tắc tài khoản** (kiểm tra ở cả React và PHP):

- Tên đăng nhập: 3–50 ký tự, chỉ chữ không dấu, số, `_`.
- Mật khẩu: 6–72 ký tự, chỉ chữ **không dấu**, số và ký hiệu bàn phím (`!@#$...`);
  không có chữ có dấu (`á`, `ư`, `ô`...) hay khoảng trắng.

## Chạy khi phát triển

```bash
php -S 127.0.0.1:8000              # cửa sổ 1 (thư mục gốc): API PHP
cd frontend && npm run dev         # cửa sổ 2: giao diện React
```

Mở **http://localhost:5173** (không mở cổng 8000 hay file `index.html` trực tiếp).
Vite chuyển các request `api/...` sang PHP ở cổng 8000 (xem `frontend/vite.config.js`).

## Đưa lên server

```bash
cd frontend && npm run build       # tạo frontend/dist/ (index.html + assets/)
```

`index.php` ở thư mục gốc đọc `frontend/dist/index.html`, đổi đường dẫn `./assets/...`
thành `/frontend/dist/assets/...` rồi trả về trình duyệt (không cache HTML, nên build mới
có hiệu lực ngay).

Nginx (`root /var/www/html`) chỉ mở các đường dẫn sau, còn lại trả 404:

| Đường dẫn                   | Xử lý                         |
|-----------------------------|-------------------------------|
| `/`, `/index.php`           | PHP — trang React              |
| `/frontend/dist/assets/*`   | file tĩnh (JS, CSS)           |
| `/api/*.php`                | PHP — API JSON                |
| `/adminer.php`              | PHP — quản trị CSDL (nên giới hạn IP) |

Ứng dụng dùng đường dẫn dạng `#/create`, `#/edit/3` nên không cần cấu hình rewrite.

## Cấu trúc thư mục

```
├── api/                      điểm vào API — mỗi file chỉ tạo controller rồi gọi handle()
│   ├── auth.php              → AuthController
│   ├── users.php             → UserController (chỉ admin)
│   ├── products.php          → ProductController
│   └── categories.php        → CategoryController
├── app/                      backend PHP hướng đối tượng, namespace App\ (PSR-4)
│   ├── autoload.php          tự nạp class: App\Core\Database → app/Core/Database.php
│   ├── bootstrap.php         nạp autoload, kết nối CSDL, bắt lỗi chung (JSON 500), tạo $app
│   ├── Core/
│   │   ├── App.php           tạo + nối các đối tượng dùng chung (dependency injection)
│   │   ├── Database.php      bọc mysqli: query() prepared statement, transaction()
│   │   ├── Request.php       method, query string, body JSON (bắt buộc Content-Type JSON)
│   │   └── JsonResponse.php  trả JSON / lỗi {"error": "..."}
│   ├── Auth/
│   │   ├── Auth.php          đăng nhập, session, đổi mật khẩu, requireLogin() (401), requireRole() (403)
│   │   └── LoginThrottle.php chống dò mật khẩu: sai 3 lần → khóa tạm, sai càng nhiều chờ càng lâu (429)
│   ├── Controllers/          nhận request, kiểm tra quyền, gọi repository, trả JSON
│   │   ├── AuthController.php      đăng nhập / đăng xuất / đăng ký / đổi mật khẩu
│   │   ├── UserController.php      quản lý người dùng (admin)
│   │   ├── ProductController.php
│   │   └── CategoryController.php
│   ├── Repositories/         mọi câu SQL
│   │   ├── ProductRepository.php
│   │   ├── CategoryRepository.php
│   │   ├── UserRepository.php
│   │   └── LoginAttemptRepository.php   bảng login_attempts
│   ├── Validators/
│   │   ├── ProductValidator.php    kiểm tra dữ liệu sản phẩm (lỗi → 422)
│   │   └── UserValidator.php       tên đăng nhập, mật khẩu (không dấu), vai trò
│   └── Services/DatabaseExporter.php     tạo nội dung database.sql (dùng bởi scripts/export_db.php)
├── migrations/               nâng cấp CSDL cũ, không mất dữ liệu
├── scripts/export_db.php     xuất dữ liệu ra database.sql (dòng lệnh)
├── database.sql              tạo CSDL + dữ liệu mẫu
├── docs/KIEN_THUC.md         ghi chú kiến thức
├── index.php                 trả về trang React đã build (frontend/dist/index.html)
└── frontend/                 toàn bộ giao diện React
    ├── index.html, vite.config.js, package.json
    ├── dist/                 bản build (npm run build) — không commit
    └── src/
        ├── main.jsx, App.jsx     khởi động, khai báo route
        ├── services/             gọi API (không có state)
        │   ├── http.js             fetch dùng chung, ApiError, xử lý 401
        │   ├── productService.js
        │   ├── categoryService.js
        │   ├── authService.js      đăng nhập, đăng ký, đổi mật khẩu
        │   └── userService.js      quản lý người dùng (admin)
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
        │   ├── useFlashMessage.js  thông báo giữa các trang
        │   ├── useLockout.js       đếm ngược khi bị khóa vì nhập sai mật khẩu (429)
        │   └── useUsers.js         danh sách tài khoản (dựa trên useAsync)
        ├── context/              AuthContext + AuthProvider (state đăng nhập dùng chung)
        ├── components/           hiển thị, nhận props
        │   ├── Layout.jsx, Alert.jsx, Loading.jsx, ProtectedRoute.jsx
        │   ├── PasswordField.jsx   ô mật khẩu, báo ngay khi gõ chữ có dấu
        │   ├── products/  SearchBar, CategoryFilter, ProductTable, Pagination, ProductForm
        │   └── users/     UserForm, UserTable, roles.js
        ├── pages/                LoginPage, RegisterPage, ChangePasswordPage, UserListPage,
        │                         ProductListPage, ProductCreatePage, ProductEditPage
        └── utils/                format.js, validation.js (quy tắc tên đăng nhập / mật khẩu)
```

Luồng React: **Page** → **Hook** → **Service** → `http.js` → PHP `api/` → **Controller** → **Repository** → MySQL.

## API

Mọi API trả JSON; lỗi có dạng `{"error": "..."}`. Request có body phải gửi
`Content-Type: application/json`.

| Phương thức | URL                                                      | Quyền    | Kết quả |
|-------------|----------------------------------------------------------|----------|---------|
| GET         | `api/auth.php`                                           | —        | `{"user": {...} \| null}` |
| POST        | `api/auth.php?action=login` `{username, password}`       | —        | `{"user": {...}}`, sai → 401, sai quá 3 lần → 429 |
| POST        | `api/auth.php?action=logout`                             | —        | `{"ok": true}` |
| POST        | `api/auth.php?action=register` `{username, password}`    | —        | `{"user": {...}}` (201, đăng nhập luôn), trùng tên → 409 |
| POST        | `api/auth.php?action=change_password` `{current_password, new_password}` | đăng nhập | `{"ok": true}`, sai mật khẩu hiện tại → 422 / 429 |
| GET         | `api/users.php`                                          | admin    | `[{id, username, role, created_at}]` |
| POST        | `api/users.php` `{username, password, role}`             | admin    | tạo tài khoản (201), trùng tên → 409 |
| PUT         | `api/users.php?id=3` `{role}`                            | admin    | đổi vai trò (không đổi được chính mình) |
| DELETE      | `api/users.php?id=3`                                     | admin    | xóa tài khoản (không xóa được chính mình) |
| GET         | `api/categories.php`                                     | đăng nhập | `[{id, name, product_count}]` |
| GET         | `api/products.php?q=&category=&page=1&per_page=10`       | đăng nhập | `{items, total, page, per_page, total_pages}` |
| GET         | `api/products.php?id=3`                                  | đăng nhập | 1 sản phẩm, không có → 404 |
| POST        | `api/products.php` `{name, price, description, category_id}` | admin | thêm (201) |
| PUT         | `api/products.php?id=3` `{...}`                          | admin    | cập nhật |
| DELETE      | `api/products.php` `{"ids": [1, 2]}`                     | admin    | `{"deleted": 2}` |

`category`: bỏ trống = tất cả, `none` = chưa phân loại, số = id danh mục.
`page` vượt quá số trang thì trả về trang cuối. `per_page` tối đa 100.
Mã lỗi: 401 chưa đăng nhập, 403 không đủ quyền, 404 không tìm thấy, 409 trùng tên đăng nhập,
422 dữ liệu không hợp lệ, 429 nhập sai mật khẩu quá nhiều lần (`{"error", "retry_after": số_giây}`
và header `Retry-After`).

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

**`users`**: `id`, `username` (duy nhất), `password_hash` (bcrypt), `role` (`admin` / `user`), `created_at`

**`login_attempts`**: số lần nhập sai mật khẩu liên tiếp theo cặp (`username`, `ip`) — `failures`,
`locked_until`, `last_failed_at`. Đăng nhập đúng thì xóa dòng tương ứng.

## Chống dò mật khẩu

Sai mật khẩu (khi đăng nhập hoặc ở ô "mật khẩu hiện tại" khi đổi mật khẩu) được đếm theo cặp
(tên đăng nhập, IP). Được sai 3 lần, sau đó phải chờ; sai càng nhiều chờ càng lâu:

| Lần sai thứ | 1–2 | 3      | 4      | 5      | 6      | 7      | 8       | 9       | 10+     |
|-------------|-----|--------|--------|--------|--------|--------|---------|---------|---------|
| Phải chờ    | —   | 30 giây | 1 phút | 2 phút | 4 phút | 8 phút | 16 phút | 32 phút | 60 phút |

Đang bị khóa thì nhập đúng mật khẩu cũng phải chờ. Đăng nhập đúng → đếm lại từ đầu;
24 giờ không sai lần nào → cũng đếm lại. Giao diện hiện đồng hồ đếm ngược và khóa nút Đăng nhập.
Thông số nằm ở đầu `app/Auth/LoginThrottle.php`.

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
- Nhập sai mật khẩu quá 3 lần → khóa tạm, thời gian chờ tăng dần (xem "Chống dò mật khẩu").
- Session chỉ lưu id user; mỗi request đọc lại tài khoản từ CSDL → admin đổi vai trò / xóa tài khoản
  có hiệu lực ngay; đổi mật khẩu thì các phiên đăng nhập khác bị đăng xuất.
- Tự đăng ký chỉ tạo được vai trò `user`; admin không đổi vai trò / xóa được chính mình
  (luôn còn ít nhất 1 admin).
- API ghi dữ liệu bắt buộc JSON → form của trang khác không giả mạo được (CSRF).
- Quyền được kiểm tra ở **server** (`app/Auth/Auth.php`); ẩn nút trên giao diện chỉ để gọn.
- Không commit `config.php` (đã có trong `.gitignore`). Chỉ cần đưa `index.php`, `frontend/dist/`, `api/`, `app/`,
  `config.php` lên server — không cần `frontend/src/`, `frontend/node_modules/`, `migrations/`, `*.sql`.
