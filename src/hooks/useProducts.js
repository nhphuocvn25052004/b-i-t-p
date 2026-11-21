import { useAsync } from './useAsync.js';
import { productService } from '../services/productService.js';

const EMPTY = { items: [], total: 0, page: 1, per_page: 10, total_pages: 1 };

// Danh sách sản phẩm theo bộ lọc { q, category, page, perPage }.
// Tự tải lại khi bộ lọc đổi; gọi reload() sau khi xóa.
export function useProducts({ q, category, page, perPage }) {
  const { data, loading, error, reload } = useAsync(
    // requestedPage: trang đã yêu cầu — server có thể trả về trang khác (trang cuối)
    () => productService.list({ q, category, page, perPage }).then((r) => ({ ...r, requestedPage: page })),
    [q, category, page, perPage],
    EMPTY
  );
  return { ...data, loading, error: error?.message ?? '', reload };
}
