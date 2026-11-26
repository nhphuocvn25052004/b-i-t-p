import { useState } from 'react';
import PasswordField from '../PasswordField.jsx';

// Form nhỏ đặt mật khẩu mới cho 1 tài khoản (mở ngay trong bảng).
// Props: id (để tạo id ô nhập), username, busy, onSubmit(password) → Promise<bool>, onCancel
export default function ResetPasswordForm({ id, username, busy, onSubmit, onCancel }) {
  const [password, setPassword] = useState('');

  async function submit(e) {
    e.preventDefault();
    if (await onSubmit(password)) onCancel();
  }

  return (
    <form className="reset-form" onSubmit={submit}>
      <div>
        <PasswordField id={id} name="password" autoFocus autoComplete="new-password"
                       label={`Mật khẩu mới cho “${username}”`}
                       value={password} onChange={(e) => setPassword(e.target.value)} />
      </div>
      <button type="submit" className="btn btn-add" disabled={busy}>Lưu</button>
      <button type="button" className="btn btn-cancel" onClick={onCancel}>Hủy</button>
    </form>
  );
}
