import { Navigate, useLocation } from 'react-router-dom';
import Loading from './Loading.jsx';
import { useAuth } from '../hooks/useAuth.js';

// Bảo vệ route phía giao diện:
//   - chưa đăng nhập  → chuyển tới /login (Authentication)
//   - sai vai trò     → về trang chủ kèm thông báo (Authorization)
// Lưu ý: đây chỉ để ẩn giao diện. Kiểm tra quyền THẬT nằm ở server (app/Auth/Auth.php).
export default function ProtectedRoute({ role, children }) {
  const { user, loading } = useAuth();
  const location = useLocation();

  if (loading) return <Loading />;

  if (!user) {
    return <Navigate to="/login" replace state={{ from: location }} />;
  }

  if (role && user.role !== role) {
    return <Navigate to="/" replace state={{ error: 'Bạn không có quyền truy cập trang này.' }} />;
  }

  return children;
}
