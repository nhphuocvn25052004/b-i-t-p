import { http } from './http.js';

const URL = 'api/auth.php';

export const authService = {
  // → { user } (user = null nếu chưa đăng nhập)
  me: () => http(URL),

  // → { user }; sai mật khẩu → ApiError 401; sai quá 3 lần → ApiError 429 (err.data.retry_after)
  login: (username, password) =>
    http(URL, { method: 'POST', params: { action: 'login' }, body: { username, password } }),

  logout: () => http(URL, { method: 'POST', params: { action: 'logout' }, body: {} }),

  // → { user } (đã đăng nhập luôn); trùng tên → ApiError 409
  register: (username, password) =>
    http(URL, { method: 'POST', params: { action: 'register' }, body: { username, password } }),

  // → { ok: true }; sai mật khẩu hiện tại → ApiError 422; sai quá 3 lần → 429
  changePassword: (currentPassword, newPassword) =>
    http(URL, {
      method: 'POST',
      params: { action: 'change_password' },
      body: { current_password: currentPassword, new_password: newPassword },
    }),
};
