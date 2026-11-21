// Gọi API PHP (api.php). Lỗi từ server ({"error": "..."}) được ném ra dưới dạng Error.
const API = 'api.php';

async function request(url, options = {}) {
  const res = await fetch(url, {
    ...options,
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
  });

  let data = null;
  try {
    data = await res.json();
  } catch {
    // body không phải JSON
  }

  if (!res.ok) {
    throw new Error(data?.error || `Lỗi máy chủ (${res.status}).`);
  }
  return data;
}

export const MAX_PRICE = 9999999999.99; // giới hạn của DECIMAL(12,2)

export const productApi = {
  list: (q = '') => request(q ? `${API}?q=${encodeURIComponent(q)}` : API),
  get: (id) => request(`${API}?id=${encodeURIComponent(id)}`),
  create: (product) => request(API, { method: 'POST', body: JSON.stringify(product) }),
  update: (id, product) =>
    request(`${API}?id=${encodeURIComponent(id)}`, { method: 'PUT', body: JSON.stringify(product) }),
  // Xóa 1 hoặc nhiều sản phẩm, trả về { deleted: n }
  remove: (ids) => request(API, { method: 'DELETE', body: JSON.stringify({ ids }) }),
};

// 1500000000.00 -> "1,500,000,000" (giống number_format của PHP)
export function formatPrice(price) {
  return Number(price).toLocaleString('en-US', { maximumFractionDigits: 0 });
}
