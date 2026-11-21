import { useEffect, useState } from 'react';
import { Navigate, useNavigate, useParams } from 'react-router-dom';
import Layout from '../components/Layout.jsx';
import ProductForm from '../components/ProductForm.jsx';
import { productApi } from '../api.js';

// UPDATE — Sửa sản phẩm
export default function ProductEdit() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [product, setProduct] = useState(null);
  const [notFound, setNotFound] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    let active = true;
    setProduct(null);
    productApi.get(id)
      .then((p) => active && setProduct(p))
      .catch(() => active && setNotFound(true));
    return () => { active = false; };
  }, [id]);

  async function save(data) {
    try {
      await productApi.update(id, data);
      navigate('/', { state: { msg: 'Đã cập nhật sản phẩm.' } });
    } catch (err) {
      setError(err.message);
    }
  }

  // Không có sản phẩm này thì quay về danh sách
  if (notFound) return <Navigate to="/" replace />;

  return (
    <Layout title={`Sửa sản phẩm #${id}`} error={error}>
      {product ? <ProductForm initial={product} onSubmit={save} /> : <p>Đang tải...</p>}
    </Layout>
  );
}
