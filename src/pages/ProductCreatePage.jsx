import { useNavigate } from 'react-router-dom';
import Layout from '../components/Layout.jsx';
import Loading from '../components/Loading.jsx';
import ProductForm from '../components/products/ProductForm.jsx';
import { useCategories } from '../hooks/useCategories.js';
import { useSubmit } from '../hooks/useSubmit.js';
import { productService } from '../services/productService.js';

const EMPTY_PRODUCT = {};

// CREATE — Thêm sản phẩm (chỉ admin)
export default function ProductCreatePage() {
  const navigate = useNavigate();
  const { categories, loading } = useCategories();
  const { submit, submitting, error } = useSubmit(async (product) => {
    await productService.create(product);
    navigate('/', { state: { msg: 'Đã thêm sản phẩm.' } });
  });

  return (
    <Layout title="Thêm sản phẩm" error={error}>
      {loading
        ? <Loading />
        : <ProductForm initial={EMPTY_PRODUCT} categories={categories} onSubmit={submit} submitting={submitting} />}
    </Layout>
  );
}
