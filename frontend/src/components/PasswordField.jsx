import { useState } from 'react';
import FilteredInput from './FilteredInput.jsx';
import { PASSWORD_CHAR } from '../utils/validation.js';

// Ô nhập mật khẩu (label + input + nút Hiện/Ẩn).
// Không bao giờ chứa chữ có dấu: chữ do bộ gõ tiếng Việt tạo ra được đổi lại thành phím đã gõ
// (á → as), khoảng trắng bị chặn. Nút "Hiện" để kiểm tra lại đúng mật khẩu đã nhập.
// Props: id, name, label, value, onChange, autoComplete, autoFocus
export default function PasswordField({ id, name, label, value, onChange, autoComplete, autoFocus }) {
  const [visible, setVisible] = useState(false);

  return (
    <>
      <label htmlFor={id}>{label}</label>
      <div className="password-row">
        <FilteredInput id={id} name={name} type={visible ? 'text' : 'password'} required
                       autoComplete={autoComplete} autoFocus={autoFocus}
                       allowedChar={PASSWORD_CHAR} value={value} onChange={onChange}
                       warning="Mật khẩu không nhận khoảng trắng hay ký tự này." />
        <button type="button" className="btn btn-cancel toggle" onClick={() => setVisible((v) => !v)}
                aria-controls={id} aria-pressed={visible}>
          {visible ? 'Ẩn' : 'Hiện'}
        </button>
      </div>
    </>
  );
}
