import { useAsync } from './useAsync.js';
import { productService } from '../services/productService.js';

// 1 sản phẩm theo id (trang Sửa). error là ApiError (có .status, vd 404)
export function useProduct(id) {
  const { data, loading, error } = useAsync(() => productService.get(id), [id]);
  return { product: data, loading, error };
}
