// Lớp gọi HTTP dùng chung cho mọi service.
// - Tự gửi cookie phiên đăng nhập (credentials: 'same-origin')
// - Lỗi từ server ({"error": "..."}) được ném ra dưới dạng ApiError kèm mã HTTP
// - Gặp 401 (hết phiên / chưa đăng nhập) thì báo cho AuthProvider để đưa về trang đăng nhập

export class ApiError extends Error {
  constructor(message, status) {
    super(message);
    this.status = status;
  }
}

let unauthorizedHandler = null;

export function setUnauthorizedHandler(handler) {
  unauthorizedHandler = handler;
}

// Bỏ các tham số rỗng: { q: '', page: 2 } -> "?page=2"
function toQuery(params = {}) {
  const search = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') search.set(key, value);
  });
  const s = search.toString();
  return s ? `?${s}` : '';
}

export async function http(url, { method = 'GET', params, body } = {}) {
  const headers = { Accept: 'application/json' };
  if (body !== undefined) headers['Content-Type'] = 'application/json';

  let res;
  try {
    res = await fetch(url + toQuery(params), {
      method,
      headers,
      credentials: 'same-origin',
      body: body !== undefined ? JSON.stringify(body) : undefined,
    });
  } catch {
    throw new ApiError('Không kết nối được máy chủ.', 0);
  }

  let data = null;
  try {
    data = await res.json();
  } catch {
    // body không phải JSON
  }

  if (!res.ok) {
    if (res.status === 401 && unauthorizedHandler) unauthorizedHandler();
    throw new ApiError(data?.error || `Lỗi máy chủ (${res.status}).`, res.status);
  }
  return data;
}
