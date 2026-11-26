import { http } from './http.js';

const URL = 'api/super_admins.php';

// Quản lý bảng super_admins — server chỉ cho admin tối cao (người khác → 403)
export const superAdminService = {
  // → [{ id, username, created_at }]
  list: () => http(URL),

  // → { user }; trùng tên (ở bảng này hoặc bảng users) → ApiError 409
  create: ({ username, password }) => http(URL, { method: 'POST', body: { username, password } }),

  resetPassword: (id, password) =>
    http(URL, { method: 'POST', params: { id, action: 'reset_password' }, body: { password } }),

  // Hạ xuống bảng users với vai trò role (admin / editor / user)
  demote: (id, role) => http(URL, { method: 'POST', params: { id, action: 'demote' }, body: { role } }),

  remove: (id) => http(URL, { method: 'DELETE', params: { id }, body: {} }),
};
