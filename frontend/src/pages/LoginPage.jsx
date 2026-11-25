import { Navigate, useLocation, useNavigate } from 'react-router-dom';
import Layout from '../components/Layout.jsx';
import Loading from '../components/Loading.jsx';
import { useAuth } from '../hooks/useAuth.js';
import { useForm } from '../hooks/useForm.js';
import { useSubmit } from '../hooks/useSubmit.js';

// Trang đăng nhập.
// Thành công → quay lại trang người dùng định vào trước đó (location.state.from)
export default function LoginPage() {
  const { user, loading, login } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const from = location.state?.from;
  const target = from ? from.pathname + (from.search ?? '') : '/';

  const { values, handleChange, handleSubmit } = useForm({ username: '', password: '' });
  const { submit, submitting, error } = useSubmit(async ({ username, password }) => {
    await login(username, password);
    navigate(target, { replace: true });
  });

  if (loading) return <Loading />;
  if (user) return <Navigate to={target} replace />;

  return (
    <Layout title="Đăng nhập" error={error}>
      <div className="box">
        <form onSubmit={handleSubmit(submit)}>
          <label htmlFor="username">Tên đăng nhập</label>
          <input id="username" name="username" autoComplete="username" required autoFocus
                 value={values.username} onChange={handleChange} />

          <label htmlFor="password">Mật khẩu</label>
          <input id="password" name="password" type="password" autoComplete="current-password" required
                 value={values.password} onChange={handleChange} />

          <div className="buttons">
            <button type="submit" className="btn btn-add" disabled={submitting}>
              {submitting ? 'Đang đăng nhập...' : 'Đăng nhập'}
            </button>
          </div>
        </form>
        <p className="hint">
          Tài khoản demo: <code>admin / admin123</code> (toàn quyền),{' '}
          <code>user / user123</code> (chỉ xem).
        </p>
      </div>
    </Layout>
  );
}
