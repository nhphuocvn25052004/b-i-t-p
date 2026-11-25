import { Navigate, Route, Routes } from 'react-router-dom';
import ProtectedRoute from './components/ProtectedRoute.jsx';
import LoginPage from './pages/LoginPage.jsx';
import RegisterPage from './pages/RegisterPage.jsx';
import ChangePasswordPage from './pages/ChangePasswordPage.jsx';
import ProductListPage from './pages/ProductListPage.jsx';
import ProductCreatePage from './pages/ProductCreatePage.jsx';
import ProductEditPage from './pages/ProductEditPage.jsx';
import UserListPage from './pages/UserListPage.jsx';

export default function App() {
  return (
    <Routes>
      {/* Chưa đăng nhập */}
      <Route path="/login" element={<LoginPage />} />
      <Route path="/register" element={<RegisterPage />} />

      {/* Đã đăng nhập: xem, tìm kiếm, lọc, phân trang, đổi mật khẩu */}
      <Route path="/" element={<ProtectedRoute><ProductListPage /></ProtectedRoute>} />
      <Route path="/account/password" element={<ProtectedRoute><ChangePasswordPage /></ProtectedRoute>} />

      {/* Chỉ admin: thêm / sửa sản phẩm, quản lý người dùng */}
      <Route path="/create" element={<ProtectedRoute role="admin"><ProductCreatePage /></ProtectedRoute>} />
      <Route path="/edit/:id" element={<ProtectedRoute role="admin"><ProductEditPage /></ProtectedRoute>} />
      <Route path="/users" element={<ProtectedRoute role="admin"><UserListPage /></ProtectedRoute>} />

      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}
