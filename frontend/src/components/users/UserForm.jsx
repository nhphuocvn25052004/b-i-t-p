import PasswordField from '../PasswordField.jsx';
import { useForm } from '../../hooks/useForm.js';
import { ROLE_LABEL } from './roles.js';

const EMPTY = { username: '', password: '', role: 'user' };

// Form thêm tài khoản (admin).
// Props: onSubmit(values) — trả về Promise, xong mà không lỗi thì xóa trắng form; submitting
export default function UserForm({ onSubmit, submitting }) {
  const { values, setValues, handleChange, handleSubmit } = useForm(EMPTY);

  async function submit(data) {
    if (await onSubmit(data)) setValues(EMPTY);
  }

  return (
    <div className="box">
      <form onSubmit={handleSubmit(submit)}>
        <label htmlFor="new-username">Tên đăng nhập</label>
        <input id="new-username" name="username" required autoComplete="off"
               value={values.username} onChange={handleChange} />

        <PasswordField id="new-password" name="password" label="Mật khẩu" autoComplete="new-password"
                       value={values.password} onChange={handleChange} />

        <label htmlFor="new-role">Vai trò</label>
        <select id="new-role" name="role" value={values.role} onChange={handleChange}>
          {Object.entries(ROLE_LABEL).map(([role, label]) => <option key={role} value={role}>{label}</option>)}
        </select>

        <div className="buttons">
          <button type="submit" className="btn btn-add" disabled={submitting}>
            {submitting ? 'Đang thêm...' : 'Thêm tài khoản'}
          </button>
        </div>
      </form>
    </div>
  );
}
