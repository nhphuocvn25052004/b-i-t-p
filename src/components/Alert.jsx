// Hộp thông báo. type: 'ok' (xanh) | 'err' (đỏ)
export default function Alert({ type = 'ok', children }) {
  if (!children) return null;
  return <div className={`alert alert-${type}`} role={type === 'err' ? 'alert' : 'status'}>{children}</div>;
}
