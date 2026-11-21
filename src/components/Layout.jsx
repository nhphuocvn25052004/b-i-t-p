import { useEffect } from 'react';
import { Link } from 'react-router-dom';
import Alert from './Alert.jsx';
import { useAuth } from '../hooks/useAuth.js';

const ROLE_LABEL = { admin: 'Quản trị', user: 'Người dùng' };

// Khung trang: thanh trên cùng (user + đăng xuất), tiêu đề, thông báo
export default function Layout({ title, msg, error, children }) {
  const { user, logout } = useAuth();

  useEffect(() => {
    document.title = title;
  }, [title]);

  return (
    <>
      {user && (
        <header className="topbar">
          <Link to="/" className="brand">Quản lý sản phẩm</Link>
          <span className="who">
            {user.username} <span className={`role role-${user.role}`}>{ROLE_LABEL[user.role] ?? user.role}</span>
          </span>
          <button type="button" className="btn btn-cancel" onClick={logout}>Đăng xuất</button>
        </header>
      )}
      <h2>{title}</h2>
      <Alert type="ok">{msg}</Alert>
      <Alert type="err">{error}</Alert>
      {children}
    </>
  );
}
