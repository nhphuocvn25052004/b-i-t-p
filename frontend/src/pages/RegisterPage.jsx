import { Link, Navigate, useNavigate } from 'react-router-dom';
import Layout from '../components/Layout.jsx';
import FilteredInput from '../components/FilteredInput.jsx';
import Loading from '../components/Loading.jsx';
import PasswordField from '../components/PasswordField.jsx';
import { useAuth } from '../hooks/useAuth.js';
import { useForm } from '../hooks/useForm.js';
import { useSubmit } from '../hooks/useSubmit.js';
import {
  PASSWORD_MIN, passwordError, USERNAME_CHAR, USERNAME_MIN, usernameError,
} from '../utils/validation.js';

// Trang đăng ký. Tài khoản mới có vai trò "Người dùng" (chỉ xem) và được đăng nhập luôn.
export default function RegisterPage() {
  const { user, loading, register } = useAuth();
  const navigate = useNavigate();

  const { values, handleChange, handleSubmit } = useForm({ username: '', password: '', confirm: '' });
  const { submit, submitting, error } = useSubmit(async ({ username, password, confirm }) => {
    const invalid = usernameError(username.trim()) || passwordError(password)
      || (password !== confirm ? 'Mật khẩu nhập lại không khớp.' : '');
    if (invalid) throw new Error(invalid);

    await register(username.trim(), password);
    navigate('/', { replace: true, state: { msg: 'Đăng ký thành công. Chào mừng bạn!' } });
  });

  if (loading) return <Loading />;
  if (user) return <Navigate to="/" replace />;

  return (
    <Layout title="Đăng ký tài khoản" error={error}>
      <div className="box">
        <form onSubmit={handleSubmit(submit)}>
          <label htmlFor="username">Tên đăng nhập</label>
          <FilteredInput id="username" name="username" autoComplete="username" required autoFocus
                         allowedChar={USERNAME_CHAR} value={values.username} onChange={handleChange}
                         warning="Tên đăng nhập chỉ gồm chữ không dấu, số, dấu gạch dưới (_)." />
          <p className="field-hint">Từ {USERNAME_MIN} ký tự: chữ không dấu, số, dấu gạch dưới (_).</p>

          <PasswordField id="password" name="password" label="Mật khẩu" autoComplete="new-password"
                         value={values.password} onChange={handleChange} />
          <p className="field-hint">Từ {PASSWORD_MIN} ký tự: chữ không dấu, số, ký hiệu. Không dùng chữ có dấu.</p>

          <PasswordField id="confirm" name="confirm" label="Nhập lại mật khẩu" autoComplete="new-password"
                         value={values.confirm} onChange={handleChange} />

          <div className="buttons">
            <button type="submit" className="btn btn-add" disabled={submitting}>
              {submitting ? 'Đang đăng ký...' : 'Đăng ký'}
            </button>
          </div>
        </form>
        <p className="hint">
          Đã có tài khoản? <Link to="/login">Đăng nhập</Link>
        </p>
      </div>
    </Layout>
  );
}
