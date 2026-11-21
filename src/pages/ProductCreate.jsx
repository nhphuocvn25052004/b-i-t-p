import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import Layout from '../components/Layout.jsx';
import ProductForm from '../components/ProductForm.jsx';
import { productApi } from '../api.js';

// CREATE — Thêm sản phẩm
export default function ProductCreate() {
  const navigate = useNavigate();
  const [error, setError] = useState('');

  async function save(product) {
    try {
      await productApi.create(product);
      navigate('/', { state: { msg: 'Đã thêm sản phẩm.' } });
    } catch (err) {
      setError(err.message);
    }
  }

  return (
    <Layout title="Thêm sản phẩm" error={error}>
      <ProductForm initial={{ name: '', price: '', description: '' }} onSubmit={save} />
    </Layout>
  );
}
