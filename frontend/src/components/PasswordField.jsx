import { useState } from 'react';
import FilteredInput from './FilteredInput.jsx';
import { PASSWORD_CHAR } from '../utils/validation.js';

// Ô nhập mật khẩu (label + input + nút Hiện/Ẩn).
// Luôn gõ như bàn phím tiếng Anh dù bật bộ gõ tiếng Việt (gõ "as" ra "as", không thành "á");
// khoảng trắng bị bỏ qua. Nút "Hiện" để xem lại mật khẩu đã nhập.
// Props: id, name, label, value, onChange, autoComplete, autoFocus
export default function PasswordField({ id, name, label, value, onChange, autoComplete, autoFocus }) {
  const [visible, setVisible] = useState(false);

  return (
    <>
      <label htmlFor={id}>{label}</label>
      <div className="password-row">
        <FilteredInput id={id} name={name} type={visible ? 'text' : 'password'} required
                       autoComplete={autoComplete} autoFocus={autoFocus}
                       allowedChar={PASSWORD_CHAR} value={value} onChange={onChange} />
        <button type="button" className="btn btn-cancel toggle" onClick={() => setVisible((v) => !v)}
                aria-controls={id} aria-pressed={visible}>
          {visible ? 'Ẩn' : 'Hiện'}
        </button>
      </div>
    </>
  );
}
