import { Link, useNavigate } from 'react-router-dom';
import Layout from '../components/Layout.jsx';
import PasswordField from '../components/PasswordField.jsx';
import { useForm } from '../hooks/useForm.js';
import { useLockout } from '../hooks/useLockout.js';
import { useSubmit } from '../hooks/useSubmit.js';
import { authService } from '../services/authService.js';
import { PASSWORD_MIN, passwordError } from '../utils/validation.js';

// Đổi mật khẩu (đã đăng nhập). Phải nhập đúng mật khẩu hiện tại;
// nhập sai cũng bị tính vào giới hạn 3 lần như khi đăng nhập.
// Đổi xong, các phiên đăng nhập ở máy / trình duyệt khác bị đăng xuất.
export default function ChangePasswordPage() {
  const navigate = useNavigate();
  const lockout = useLockout();

  const { values, handleChange, handleSubmit } = useForm({ current: '', next: '', confirm: '' });
  const { submit, submitting, error } = useSubmit(async ({ current, next, confirm }) => {
    const invalid = passwordError(next)
      || (next !== confirm ? 'Mật khẩu mới nhập lại không khớp.' : '')
      || (next === current ? 'Mật khẩu mới phải khác mật khẩu hiện tại.' : '');
    if (invalid) throw new Error(invalid);

    try {
      await authService.changePassword(current, next);
      navigate('/', { state: { msg: 'Đã đổi mật khẩu.' } });
    } catch (err) {
      if (!lockout.catchLock(err)) throw err;
    }
  });

  return (
    <Layout title="Đổi mật khẩu" error={lockout.message || error}>
      <div className="box">
        <form onSubmit={handleSubmit(submit)}>
          <PasswordField id="current" name="current" label="Mật khẩu hiện tại" autoComplete="current-password"
                         autoFocus value={values.current} onChange={handleChange} />

          <PasswordField id="next" name="next" label="Mật khẩu mới" autoComplete="new-password"
                         value={values.next} onChange={handleChange} />
          <p className="field-hint">Từ {PASSWORD_MIN} ký tự: chữ không dấu, số, ký hiệu. Không dùng chữ có dấu.</p>

          <PasswordField id="confirm" name="confirm" label="Nhập lại mật khẩu mới" autoComplete="new-password"
                         value={values.confirm} onChange={handleChange} />

          <div className="buttons">
            <button type="submit" className="btn btn-add" disabled={submitting || lockout.locked}>
              {submitting ? 'Đang lưu...' : lockout.locked ? `Chờ ${lockout.secondsLeft}s` : 'Đổi mật khẩu'}
            </button>{' '}
            <Link to="/" className="btn btn-cancel">Hủy</Link>
          </div>
        </form>
      </div>
    </Layout>
  );
}
