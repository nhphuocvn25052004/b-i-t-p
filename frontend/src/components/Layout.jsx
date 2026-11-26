import { useEffect } from 'react';
import { Link, NavLink } from 'react-router-dom';
import Alert from './Alert.jsx';
import { ALL_ROLE_LABEL } from './users/roles.js';
import { useAuth } from '../hooks/useAuth.js';

// Khung trang: thanh trên cùng (menu, user, đăng xuất), tiêu đề, thông báo
export default function Layout({ title, msg, error, children }) {
  const { user, can, logout } = useAuth();

  useEffect(() => {
    document.title = title;
  }, [title]);

  return (
    <>
      {user && (
        <header className="topbar">
          <Link to="/" className="brand">Quản lý sản phẩm</Link>
          <nav className="menu">
            {can('users.create') && <NavLink to="/users">Người dùng</NavLink>}
            <NavLink to="/account/password">Đổi mật khẩu</NavLink>
          </nav>
          <span className="who">
            {user.username} <span className={`role role-${user.role}`}>{ALL_ROLE_LABEL[user.role] ?? user.role}</span>
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
