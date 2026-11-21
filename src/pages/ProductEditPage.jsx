import { Navigate, useNavigate, useParams } from 'react-router-dom';
import Layout from '../components/Layout.jsx';
import Loading from '../components/Loading.jsx';
import ProductForm from '../components/products/ProductForm.jsx';
import { useCategories } from '../hooks/useCategories.js';
import { useProduct } from '../hooks/useProduct.js';
import { useSubmit } from '../hooks/useSubmit.js';
import { productService } from '../services/productService.js';

// UPDATE — Sửa sản phẩm (chỉ admin)
export default function ProductEditPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { product, loading, error: loadError } = useProduct(id);
  const { categories, loading: loadingCategories } = useCategories();
  const { submit, submitting, error } = useSubmit(async (data) => {
    await productService.update(id, data);
    navigate('/', { state: { msg: 'Đã cập nhật sản phẩm.' } });
  });

  // Không có sản phẩm này thì quay về danh sách
  if (loadError?.status === 404) {
    return <Navigate to="/" replace state={{ error: `Không tìm thấy sản phẩm #${id}.` }} />;
  }

  return (
    <Layout title={`Sửa sản phẩm #${id}`} error={error || loadError?.message}>
      {(loading || loadingCategories) && <Loading />}
      {/* Chờ có cả danh mục để ô chọn danh mục hiện đúng ngay từ đầu.
          key: đổi sản phẩm thì tạo form mới với dữ liệu mới */}
      {product && !loading && !loadingCategories && (
        <ProductForm key={product.id} initial={product} categories={categories}
                     onSubmit={submit} submitting={submitting} />
      )}
    </Layout>
  );
}
