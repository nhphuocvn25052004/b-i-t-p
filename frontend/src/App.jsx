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

      {/* Theo quyền: admin luôn được, editor chỉ khi admin đã cấp quyền đó */}
      <Route path="/create" element={<ProtectedRoute permission="products.create"><ProductCreatePage /></ProtectedRoute>} />
      <Route path="/edit/:id" element={<ProtectedRoute permission="products.update"><ProductEditPage /></ProtectedRoute>} />
      <Route path="/users" element={<ProtectedRoute permission="users.create"><UserListPage /></ProtectedRoute>} />

      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}
