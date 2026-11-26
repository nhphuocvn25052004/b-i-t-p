import { useCallback, useEffect, useMemo, useState } from 'react';
import { AuthContext } from './AuthContext.js';
import { authService } from '../services/authService.js';
import { setUnauthorizedHandler } from '../services/http.js';
import { can as userCan, isManager } from '../components/users/roles.js';

// Giữ state "ai đang đăng nhập" ở 1 chỗ duy nhất và chia sẻ cho mọi component bên trong
// qua Context — không phải truyền props user qua từng tầng component.
export default function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true); // đang hỏi server xem đã đăng nhập chưa

  useEffect(() => {
    // Mở trang / F5: hỏi server, cookie phiên vẫn còn thì user vẫn đăng nhập
    authService.me()
      .then((data) => setUser(data.user))
      .catch(() => setUser(null))
      .finally(() => setLoading(false));

    // API trả về 401 (hết phiên) → coi như đã đăng xuất
    setUnauthorizedHandler(() => setUser(null));
    return () => setUnauthorizedHandler(null);
  }, []);

  const login = useCallback(async (username, password) => {
    const data = await authService.login(username, password);
    setUser(data.user);
    return data.user;
  }, []);

  const logout = useCallback(async () => {
    try {
      await authService.logout();
    } finally {
      setUser(null);
    }
  }, []);

  // Đăng ký xong server đăng nhập luôn → cập nhật user như khi đăng nhập
  const register = useCallback(async (username, password) => {
    const data = await authService.register(username, password);
    setUser(data.user);
    return data.user;
  }, []);

  // can('products.create') → true/false — chỉ để ẩn/hiện nút, server vẫn kiểm tra lại
  const can = useCallback((permission) => userCan(user, permission), [user]);

  const value = useMemo(
    // isAdmin: admin tối cao hoặc quản trị (được chọn vai trò / cấp quyền khi quản lý tài khoản)
    () => ({ user, loading, isAdmin: isManager(user), can, login, logout, register }),
    [user, loading, can, login, logout, register]
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}
