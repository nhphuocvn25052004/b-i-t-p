import { http } from './http.js';

const URL = 'api/users.php';

// Quản lý người dùng — server chỉ cho admin (người khác → 403)
export const userService = {
  // → [{ id, username, role, created_at }]
  list: () => http(URL),

  // → { user }; trùng tên → ApiError 409
  create: ({ username, password, role }) =>
    http(URL, { method: 'POST', body: { username, password, role } }),

  updateRole: (id, role) => http(URL, { method: 'PUT', params: { id }, body: { role } }),

  remove: (id) => http(URL, { method: 'DELETE', params: { id }, body: {} }),
};
