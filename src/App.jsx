import { Navigate, Route, Routes } from 'react-router-dom';
import ProtectedRoute from './components/ProtectedRoute.jsx';
import LoginPage from './pages/LoginPage.jsx';
import ProductListPage from './pages/ProductListPage.jsx';
import ProductCreatePage from './pages/ProductCreatePage.jsx';
import ProductEditPage from './pages/ProductEditPage.jsx';

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />

      {/* Đã đăng nhập: xem, tìm kiếm, lọc, phân trang */}
      <Route path="/" element={<ProtectedRoute><ProductListPage /></ProtectedRoute>} />

      {/* Chỉ admin: thêm / sửa */}
      <Route path="/create" element={<ProtectedRoute role="admin"><ProductCreatePage /></ProtectedRoute>} />
      <Route path="/edit/:id" element={<ProtectedRoute role="admin"><ProductEditPage /></ProtectedRoute>} />

      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}
