import { http } from './http.js';

const URL = 'api/products.php';

export const MAX_PRICE = 9999999999.99; // giới hạn của DECIMAL(12,2)

// Chỉ gửi các trường server cần
function toPayload(product) {
  return {
    name: product.name,
    price: product.price,
    description: product.description ?? '',
    category_id: product.category_id || null,
  };
}

export const productService = {
  // → { items, total, page, per_page, total_pages }
  list: ({ q, category, page, perPage } = {}) =>
    http(URL, { params: { q, category, page, per_page: perPage } }),

  get: (id) => http(URL, { params: { id } }),

  create: (product) => http(URL, { method: 'POST', body: toPayload(product) }),

  update: (id, product) => http(URL, { method: 'PUT', params: { id }, body: toPayload(product) }),

  // Xóa 1 hoặc nhiều sản phẩm → { deleted: n }
  remove: (ids) => http(URL, { method: 'DELETE', body: { ids } }),
};
