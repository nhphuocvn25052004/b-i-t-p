import { Navigate, Route, Routes } from 'react-router-dom';
import ProductList from './pages/ProductList.jsx';
import ProductCreate from './pages/ProductCreate.jsx';
import ProductEdit from './pages/ProductEdit.jsx';

export default function App() {
  return (
    <Routes>
      <Route path="/" element={<ProductList />} />           {/* READ + tìm kiếm (?q=) + xóa */}
      <Route path="/create" element={<ProductCreate />} />   {/* CREATE */}
      <Route path="/edit/:id" element={<ProductEdit />} />   {/* UPDATE */}
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}
