import { http } from './http.js';

const URL = 'api/auth.php';

export const authService = {
  // → { user } (user = null nếu chưa đăng nhập)
  me: () => http(URL),

  // → { user }; sai mật khẩu → ApiError 401
  login: (username, password) =>
    http(URL, { method: 'POST', params: { action: 'login' }, body: { username, password } }),

  logout: () => http(URL, { method: 'POST', params: { action: 'logout' }, body: {} }),
};
