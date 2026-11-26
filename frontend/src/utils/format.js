// 1500000000.00 -> "1,500,000,000" (giống number_format của PHP)
export function formatPrice(price) {
  return Number(price).toLocaleString('en-US', { maximumFractionDigits: 0 });
}

// "2026-11-25 04:57:40" (DATETIME của MySQL) -> "25/11/2026 04:57"
export function formatDateTime(value) {
  const m = /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/.exec(value ?? '');
  return m ? `${m[3]}/${m[2]}/${m[1]} ${m[4]}:${m[5]}` : (value ?? '');
}

// 45 -> "45 giây", 120 -> "2 phút", 150 -> "2 phút 30 giây"
export function formatWait(seconds) {
  if (seconds < 60) return `${seconds} giây`;
  const minutes = Math.floor(seconds / 60);
  const rest = seconds % 60;
  return rest ? `${minutes} phút ${rest} giây` : `${minutes} phút`;
}

// Hàm đặt tên danh mục cho ô chọn. Mỗi quản trị có bộ danh mục riêng nên có thể trùng tên
// (admin tối cao thấy "Tàu thủy" của nhiều người) → khi danh sách có nhiều chủ thì thêm tên chủ.
//   const label = categoryLabeler(categories);  label(c) → "Tàu thủy (user2)"
export function categoryLabeler(categories) {
  const multiOwner = new Set(categories.map((c) => c.owner_id ?? null)).size > 1;
  return (c) => (multiOwner ? `${c.name} (${c.owner_name ?? 'admin tối cao'})` : c.name);
}
