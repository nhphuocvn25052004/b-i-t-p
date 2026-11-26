# Ghi chú kiến thức — React State/Props và Xác thực

Tài liệu đi kèm mã nguồn của dự án. Mỗi phần đều chỉ ra file minh họa tương ứng.

---

## 1. React State và Props

| | **State** | **Props** |
|---|---|---|
| Là gì | Dữ liệu **của chính** component, thay đổi theo thời gian | Dữ liệu component **cha truyền xuống** con |
| Ai được sửa | Chỉ component sở hữu (qua hàm `setX`) | Con **chỉ đọc**, không được sửa |
| Khi thay đổi | Component vẽ lại | Con vẽ lại với giá trị mới |
| Khai báo | `const [x, setX] = useState(giáTrịĐầu)` | `function Con({ x, onChange }) { ... }` |

### useState

```jsx
const [text, setText] = useState(value);       // frontend/src/components/products/SearchBar.jsx
<input value={text} onChange={(e) => setText(e.target.value)} />
```

- `setText(...)` **không** đổi biến ngay, mà yêu cầu React vẽ lại với giá trị mới.
- Với object / mảng / Set: luôn tạo **bản mới**, không sửa trực tiếp:
  ```js
  setProduct({ ...product, name: 'Mới' });     // đúng
  product.name = 'Mới';                         // SAI — React không biết để vẽ lại
  ```
  Xem `useSelection.js`: tạo `new Set(prev)` rồi mới thêm / bớt.

### Truyền dữ liệu xuống (props) và báo ngược lên (callback)

Dữ liệu chảy **một chiều**: cha → con qua props. Con muốn thay đổi thì **gọi hàm** cha truyền xuống.

```
ProductListPage  (giữ state: bộ lọc, danh sách, các dòng đã chọn)
 ├── <SearchBar      value={filters.q}        onSearch={setSearch} />
 ├── <CategoryFilter value={filters.category} onChange={setCategory} />
 ├── <ProductTable   products={items} selected={...} onToggle={...} onDelete={...} />
 └── <Pagination     page={page} totalPages={...} onPageChange={setPage} />
```

`ProductTable` không tự xóa sản phẩm — nó chỉ gọi `onDelete(p)`; trang cha quyết định hỏi xác nhận,
gọi API và tải lại. Nhờ vậy component con đơn giản, dễ dùng lại.

### Chia sẻ state giữa các component

| Cách | Khi nào dùng | Ví dụ trong dự án |
|---|---|---|
| **Nâng state lên cha chung** (lifting state up) | 2 component anh em cần cùng dữ liệu | `ProductListPage` giữ bộ lọc cho `SearchBar`, `CategoryFilter`, `Pagination` |
| **Context** | Rất nhiều component ở nhiều tầng cần (user đăng nhập, ngôn ngữ, theme) | `AuthProvider` + `useAuth()`: `Layout`, `ProtectedRoute`, `LoginPage`, `ProductListPage` |
| **URL** (search params) | State cần giữ khi F5, Back, gửi link | `useProductFilters`: `#/?q=tau&category=1&page=2` |
| **Custom hook** | Dùng lại *logic* có state (không phải dùng chung *giá trị*) | `useProducts`, `useSelection`, `useCategories` |

Lưu ý: mỗi lần gọi custom hook tạo ra state **riêng**. Hai component cùng gọi `useCategories()`
sẽ có 2 bản danh sách riêng. Muốn dùng chung **một** giá trị thì dùng Context (như `useAuth`).

---

## 2. Cấu trúc thư mục React

```
frontend/src/
├── services/    gọi API, không chứa state, không biết gì về React
├── hooks/       custom hook: state + effect + gọi service, trả dữ liệu cho component
├── context/     state dùng chung toàn ứng dụng (đăng nhập)
├── components/  hiển thị, nhận props, báo sự kiện bằng callback
├── pages/       ghép hook + component thành 1 trang, gắn với 1 route
└── utils/       hàm thuần (định dạng giá...)
```

Luồng: **Page** → gọi **Hook** → gọi **Service** → `http.js` → PHP API.
Muốn đổi cách gọi API (vd thêm header) chỉ sửa `services/`; muốn đổi giao diện chỉ sửa `components/`.

---

## 3. Authentication và Authorization

| | **Authentication** (xác thực) | **Authorization** (phân quyền) |
|---|---|---|
| Câu hỏi | **Bạn là ai?** | **Bạn được làm gì?** |
| Cách làm | Đăng nhập bằng tên + mật khẩu | Kiểm tra vai trò (role) và quyền (permission) của người đã xác thực |
| Lỗi HTTP | **401** Unauthorized — chưa đăng nhập / hết phiên | **403** Forbidden — đã biết bạn là ai nhưng không đủ quyền |
| Trong dự án | `$auth->requireLogin()` trong `app/Auth/Auth.php` | `$auth->requirePermission(...)`, `$auth->requireRole(...)` trong `app/Auth/Auth.php` |

Phân quyền trong dự án:

- **Admin tối cao** (`superadmin`, bảng riêng `super_admins`): thấy và làm mọi thứ với mọi tài khoản,
  kể cả cưỡng chế đổi mật khẩu và xóa; quản lý cả bảng `super_admins` (thêm, nâng / hạ cấp, xóa —
  trừ chính mình, nên luôn còn ít nhất 1 admin tối cao).
- **`admin`** (quản trị): mọi quyền với sản phẩm, nhưng chỉ quản lý được **cây tài khoản nằm dưới mình**
  (người mình tạo / nâng lên, và người do họ tạo...). Các quản trị không nhìn thấy nhau.
- **`editor`** (user1): chỉ làm những việc admin **tick cấp** cho riêng mình (bảng `user_permissions`).
- **`user`** (user2): chỉ xem. Do admin / editor tạo, hoặc tự đăng ký.

| Thao tác | Khách | `user` | `editor` | `admin` |
|---|---|---|---|---|
| Đăng ký tài khoản (ra `user`) | ✓ | — | — | — |
| Xem, tìm kiếm, lọc, phân trang, đổi mật khẩu | ✗ (401) | ✓ | ✓ | ✓ |
| Thêm / sửa / xóa sản phẩm | ✗ (401) | ✗ (403) | nếu được cấp `products.create` / `.update` / `.delete` | ✓ |
| Tạo / đặt lại mật khẩu / xóa tài khoản xem | ✗ (401) | ✗ (403) | nếu được cấp `users.create` — chỉ tài khoản **nằm dưới mình** | ✓ trong cây của mình (admin tối cao: mọi tài khoản) |
| Tạo / nâng lên quản trị, editor; cấp quyền | ✗ (401) | ✗ (403) | ✗ (403) | ✓ trong cây của mình (admin tối cao: mọi tài khoản) |

Cột `admin` ở trên áp dụng cho cả admin tối cao, chỉ khác phạm vi tài khoản.

Ngoài "được làm gì" còn có **"được thấy dữ liệu nào"** (`app/Auth/DataScope.php`): mỗi quản trị có sản phẩm
và danh mục riêng; quản trị thấy dữ liệu của cả nhánh mình, editor chỉ thấy sản phẩm mình thêm, người xem
thấy sản phẩm của người đã tạo ra mình. Người xem tự đăng ký thuộc admin tối cao: xem được mọi sản phẩm
nhưng không làm được gì khác, và chỉ admin tối cao quản lý họ. Kiểm tra quyền (403) và lọc phạm vi (404) đều làm ở server.

Luật nằm ở một chỗ, `Auth::can($user, $permission)`: admin → luôn đúng; editor → có trong danh sách
được cấp; user → luôn sai. `requirePermission()` = `requireLogin()` (401) + `can()` (403).

**Quan trọng:** ẩn nút "Thêm/Sửa/Xóa" và `ProtectedRoute` ở React **chỉ để giao diện gọn**.
Ai cũng có thể mở DevTools hoặc dùng `curl` gọi thẳng API, nên **mọi kiểm tra quyền phải làm ở server**.

---

## 4. Cookie, Session, Token và JWT

### Cookie
Một mẩu dữ liệu nhỏ server gửi về (`Set-Cookie`), trình duyệt **tự động** gửi kèm mọi request sau
tới cùng tên miền. Các cờ quan trọng:

| Cờ | Tác dụng |
|---|---|
| `HttpOnly` | JavaScript không đọc được → nếu bị XSS cũng không lấy trộm được cookie |
| `Secure` | Chỉ gửi qua HTTPS |
| `SameSite=Lax` | Không gửi kèm request POST/PUT/DELETE từ trang web khác → chống CSRF |

### Session (phiên) — cách dự án này dùng
- Thông tin đăng nhập nằm **trên server** (`$_SESSION['user']`).
- Trình duyệt chỉ giữ **session id** ngẫu nhiên trong cookie `BITP_SESSION`.
- Đăng xuất = server xóa session → id cũ vô dụng ngay lập tức.

### Token
Chuỗi chứng minh danh tính mà client tự gửi kèm, thường trong header
`Authorization: Bearer <token>`. Hay dùng cho app di động, API cho bên thứ ba,
hoặc khi frontend và API khác tên miền.

### JWT (JSON Web Token)
Một loại token **tự chứa thông tin**, gồm 3 phần ngăn bởi dấu chấm: `header.payload.signature`

```
eyJhbGciOiJIUzI1NiJ9 . eyJzdWIiOjEsInJvbGUiOiJhZG1pbiIsImV4cCI6MTc2NDAwMDAwMH0 . chữ_ký
   {"alg":"HS256"}          {"sub":1,"role":"admin","exp":1764000000}         HMAC(bí_mật)
```

- Payload chỉ là base64 — **ai cũng đọc được**, không để mật khẩu hay dữ liệu nhạy cảm vào.
- Chữ ký giúp server biết payload **không bị sửa**, không cần tra CSDL hay lưu session.
- Nhược điểm: không thu hồi được trước hạn `exp` (muốn "đăng xuất ngay" phải có thêm danh sách đen),
  và nếu lưu trong `localStorage` thì bị XSS lấy được.

### So sánh

| | Session + Cookie | JWT |
|---|---|---|
| Lưu trạng thái | Server (session) | Trong chính token (client giữ) |
| Đăng xuất / khóa tài khoản | Có hiệu lực ngay | Phải chờ hết hạn hoặc làm danh sách đen |
| Mở rộng nhiều server | Cần chia sẻ session (Redis, DB) | Dễ — server nào có khóa bí mật cũng kiểm tra được |
| Hợp với | Web cùng tên miền (như dự án này) | Mobile app, nhiều dịch vụ, API công khai |

**Vì sao dự án chọn Session:** React và PHP chạy cùng tên miền, PHP có sẵn session,
đăng xuất có hiệu lực ngay, và cookie `HttpOnly` an toàn hơn lưu JWT trong `localStorage`.

---

## 5. Luồng đăng nhập trong dự án

```
React (trình duyệt)                                PHP (server)
───────────────────                                ────────────
1. Mở trang → AuthProvider gọi
   GET api/auth.php  ───────────────────────────►  auth_user(): đọc $_SESSION
                     ◄───────────────────────────  {"user": null}
   → ProtectedRoute chuyển tới #/login

2. Nhập admin / admin123
   POST api/auth.php?action=login  ─────────────►  user_verify(): password_verify(mật khẩu, hash)
                                                   auth_login(): session_regenerate_id()
                                                                 $_SESSION['user'] = {...}
                     ◄───────────────────────────  Set-Cookie: BITP_SESSION=abc; HttpOnly; SameSite=Lax
                                                   {"user": {"id":1,"username":"admin","role":"admin"}}
   → setUser(...) trong AuthProvider → mọi component dùng useAuth() vẽ lại

3. Gọi API (trình duyệt tự gửi cookie)
   DELETE api/products.php  ────────────────────►  requireRole('admin') → OK → xóa
   Cookie: BITP_SESSION=abc

4. Hết phiên / bị đăng xuất ở nơi khác
   GET api/products.php  ───────────────────────►  requireLogin() → 401
                     ◄───────────────────────────
   → http.js gọi unauthorizedHandler → setUser(null) → quay về #/login

5. Đăng xuất
   POST api/auth.php?action=logout  ────────────►  session_destroy(), xóa cookie
```

File liên quan:
- PHP: `api/auth.php`, `app/Controllers/AuthController.php`, `app/Auth/Auth.php`, `app/Repositories/UserRepository.php`
- React: `frontend/src/services/authService.js`, `frontend/src/services/http.js`, `frontend/src/context/AuthProvider.jsx`,
  `frontend/src/hooks/useAuth.js`, `frontend/src/components/ProtectedRoute.jsx`, `frontend/src/pages/LoginPage.jsx`

### Các lớp bảo vệ đã có
- Mật khẩu lưu bằng `password_hash` (bcrypt), so sánh bằng `password_verify`.
- `session_regenerate_id(true)` sau khi đăng nhập → chống session fixation.
- Cookie `HttpOnly` + `SameSite=Lax`; API ghi dữ liệu bắt buộc `Content-Type: application/json`
  (form HTML của trang khác không gửi được) → chống CSRF.
- Báo lỗi chung "Sai tên đăng nhập hoặc mật khẩu" → không lộ tên đăng nhập nào tồn tại.
- Giới hạn số lần nhập sai (`app/Auth/LoginThrottle.php`): sai 3 lần → chờ 30 giây, mỗi lần sai
  tiếp theo thời gian chờ nhân đôi (tối đa 1 giờ). Server trả **429 Too Many Requests**.
  Đếm theo cặp (tên đăng nhập, IP) để người dò từ máy khác không khóa được tài khoản của bạn.
- Session chỉ giữ id user + "dấu" của mật khẩu; mỗi request đọc lại tài khoản từ CSDL
  → bị xóa / đổi vai trò có hiệu lực ngay, đổi mật khẩu thì các phiên khác bị đăng xuất.

### Nên làm thêm khi dùng thật
- Chạy HTTPS (cookie tự bật cờ `Secure`).
- Đổi mật khẩu 2 tài khoản demo, hoặc xóa đi.
- Giới hạn số tài khoản đăng ký từ 1 IP (chống tạo tài khoản rác), hoặc thêm CAPTCHA.
