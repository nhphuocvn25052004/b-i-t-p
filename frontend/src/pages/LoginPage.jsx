import { Link, Navigate, useLocation, useNavigate } from 'react-router-dom';
import Layout from '../components/Layout.jsx';
import FilteredInput from '../components/FilteredInput.jsx';
import Loading from '../components/Loading.jsx';
import PasswordField from '../components/PasswordField.jsx';
import { useAuth } from '../hooks/useAuth.js';
import { useForm } from '../hooks/useForm.js';
import { useLockout } from '../hooks/useLockout.js';
import { useSubmit } from '../hooks/useSubmit.js';
import { USERNAME_CHAR } from '../utils/validation.js';

// Trang đăng nhập.
// Thành công → quay lại trang người dùng định vào trước đó (location.state.from)
// Sai quá 3 lần → server trả 429, nút Đăng nhập bị khóa và đếm ngược
export default function LoginPage() {
  const { user, loading, login } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const from = location.state?.from;
  const target = from ? from.pathname + (from.search ?? '') : '/';

  const lockout = useLockout();
  const { values, handleChange, handleSubmit } = useForm({ username: '', password: '' });
  const { submit, submitting, error } = useSubmit(async ({ username, password }) => {
    try {
      await login(username, password);
      navigate(target, { replace: true });
    } catch (err) {
      if (!lockout.catchLock(err)) throw err;
    }
  });

  if (loading) return <Loading />;
  if (user) return <Navigate to={target} replace />;

  return (
    <Layout title="Đăng nhập" error={lockout.message || error}>
      <div className="box">
        <form onSubmit={handleSubmit(submit)}>
          <label htmlFor="username">Tên đăng nhập</label>
          <FilteredInput id="username" name="username" autoComplete="username" required autoFocus
                         allowedChar={USERNAME_CHAR} value={values.username} onChange={handleChange} />

          <PasswordField id="password" name="password" label="Mật khẩu" autoComplete="current-password"
                         value={values.password} onChange={handleChange} />

          <div className="buttons">
            <button type="submit" className="btn btn-add" disabled={submitting || lockout.locked}>
              {submitting ? 'Đang đăng nhập...' : lockout.locked ? `Chờ ${lockout.secondsLeft}s` : 'Đăng nhập'}
            </button>
          </div>
        </form>
        <p className="hint">
          Chưa có tài khoản? <Link to="/register">Đăng ký</Link>
        </p>
        <p className="hint">
          Tài khoản demo: <code>admin / admin123</code> (toàn quyền),{' '}
          <code>user / user123</code> (chỉ xem).
        </p>
      </div>
    </Layout>
  );
}
