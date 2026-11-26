import { Navigate, useLocation } from 'react-router-dom';
import Loading from './Loading.jsx';
import { useAuth } from '../hooks/useAuth.js';

// Bảo vệ route phía giao diện:
//   - chưa đăng nhập  → chuyển tới /login (Authentication)
//   - sai vai trò / thiếu quyền → lặng lẽ về trang chủ, không báo lỗi (Authorization)
//     <ProtectedRoute role="admin">            chỉ admin
//     <ProtectedRoute permission="products.create">  admin, hoặc editor được cấp quyền
// Lưu ý: đây chỉ để ẩn giao diện. Kiểm tra quyền THẬT nằm ở server (app/Auth/Auth.php).
export default function ProtectedRoute({ role, permission, children }) {
  const { user, loading, can } = useAuth();
  const location = useLocation();

  if (loading) return <Loading />;

  if (!user) {
    return <Navigate to="/login" replace state={{ from: location }} />;
  }

  if ((role && user.role !== role) || (permission && !can(permission))) {
    return <Navigate to="/" replace />;
  }

  return children;
}
