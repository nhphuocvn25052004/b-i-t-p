import { http } from './http.js';

const URL = 'api/users.php';

// Quản lý người dùng — server chỉ cho admin tối cao, quản trị, hoặc editor có quyền users.create.
// Admin tối cao thấy mọi tài khoản; quản trị / editor chỉ thấy cây tài khoản nằm dưới mình.
export const userService = {
  // → [{ id, username, role, created_at, created_by, created_by_name, permissions }]
  list: () => http(URL),

  // → { user }; trùng tên → ApiError 409. role / permissions chỉ có tác dụng khi admin gửi.
  create: ({ username, password, role, permissions }) =>
    http(URL, { method: 'POST', body: { username, password, role, permissions } }),

  // Chỉ admin: đổi vai trò + quyền (quyền chỉ lưu khi role là editor)
  update: (id, { role, permissions }) =>
    http(URL, { method: 'PUT', params: { id }, body: { role, permissions } }),

  // Cưỡng chế đặt mật khẩu mới (không cần mật khẩu cũ); người đó bị đăng xuất khỏi mọi phiên
  resetPassword: (id, password) =>
    http(URL, { method: 'POST', params: { id, action: 'reset_password' }, body: { password } }),

  remove: (id) => http(URL, { method: 'DELETE', params: { id }, body: {} }),
};
