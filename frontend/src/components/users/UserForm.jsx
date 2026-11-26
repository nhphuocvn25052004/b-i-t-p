import PasswordField from '../PasswordField.jsx';
import { useForm } from '../../hooks/useForm.js';
import PermissionChecks from './PermissionChecks.jsx';
import { ROLE_LABEL } from './roles.js';

const EMPTY = { username: '', password: '', role: 'user', permissions: [] };

// Form thêm tài khoản.
//   admin  → chọn vai trò (roleOptions); chọn "Biên tập viên" thì tick quyền cấp cho tài khoản đó
//   editor → không có ô vai trò, luôn tạo tài khoản xem (server cũng ép như vậy)
// Props: onSubmit(values) — trả về Promise, xong mà không lỗi thì xóa trắng form; submitting; isAdmin;
//        roleOptions ({role: nhãn}; admin tối cao có thêm 'superadmin')
export default function UserForm({ onSubmit, submitting, isAdmin, roleOptions = ROLE_LABEL }) {
  const { values, setValues, handleChange, handleSubmit } = useForm(EMPTY);

  async function submit(data) {
    const body = isAdmin
      ? { ...data, permissions: data.role === 'editor' ? data.permissions : [] }
      : { username: data.username, password: data.password };
    if (await onSubmit(body)) setValues(EMPTY);
  }

  return (
    <div className="box">
      <form onSubmit={handleSubmit(submit)}>
        <label htmlFor="new-username">Tên đăng nhập</label>
        <input id="new-username" name="username" required autoComplete="off"
               value={values.username} onChange={handleChange} />

        <PasswordField id="new-password" name="password" label="Mật khẩu" autoComplete="new-password"
                       value={values.password} onChange={handleChange} />

        {isAdmin ? (
          <>
            <label htmlFor="new-role">Vai trò</label>
            <select id="new-role" name="role" value={values.role} onChange={handleChange}>
              {Object.entries(roleOptions).map(([role, label]) => <option key={role} value={role}>{label}</option>)}
            </select>

            {values.role === 'editor' && (
              <fieldset className="perm-box">
                <legend>Quyền được cấp</legend>
                <PermissionChecks idPrefix="new-perm" value={values.permissions}
                                  onChange={(permissions) => setValues((v) => ({ ...v, permissions }))} />
              </fieldset>
            )}
          </>
        ) : (
          <p className="muted">Tài khoản tạo ra có vai trò “{ROLE_LABEL.user}” (chỉ xem).</p>
        )}

        <div className="buttons">
          <button type="submit" className="btn btn-add" disabled={submitting}>
            {submitting ? 'Đang thêm...' : 'Thêm tài khoản'}
          </button>
        </div>
      </form>
    </div>
  );
}
