import { useEffect } from 'react';

// Khung trang: tiêu đề + thông báo thành công / lỗi
export default function Layout({ title, msg, error, children }) {
  useEffect(() => {
    document.title = title;
  }, [title]);

  return (
    <>
      <h2>{title}</h2>
      {msg && <div className="alert alert-ok">{msg}</div>}
      {error && <div className="alert alert-err">{error}</div>}
      {children}
    </>
  );
}
