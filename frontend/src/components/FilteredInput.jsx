import { useEffect, useRef, useState } from 'react';
import { untelex } from '../utils/telex.js';

const NOTICE_MS = 5000;

const TELEX_NOTICE =
  'Bộ gõ tiếng Việt đang bật — đã giữ nguyên phím bạn gõ (vd á → as). '
  + 'Nên tắt bộ gõ (Ctrl+Shift hoặc Alt+Z) để chắc chắn đúng.';

// Ô nhập KHÔNG BAO GIỜ chứa chữ có dấu:
//   1. Chữ có dấu do bộ gõ tiếng Việt tạo ra được đổi lại thành phím Telex đã gõ: á → as, đ → dd
//   2. Ký tự không hợp lệ còn lại (khoảng trắng, emoji...) bị chặn
// Áp dụng cho cả gõ, dán, và bộ gõ dạng ghép chữ (IME trên điện thoại / macOS).
// Mỗi lần đổi / chặn thì hiện nhắc nhở vài giây.
//
//   <FilteredInput allowedChar={/^[A-Za-z0-9_]$/} name="username" value={...} onChange={handleChange} />
//
// Props: allowedChar (RegExp khớp đúng 1 ký tự được phép), warning (câu nhắc khi chặn),
//        onChange nhận { target: { name, value } } như ô input thường (dùng được với useForm),
//        các props còn lại truyền thẳng cho <input>.
export default function FilteredInput({
  allowedChar,
  warning = 'Không gõ được khoảng trắng hay ký tự đặc biệt này.',
  onChange,
  id,
  name,
  ...inputProps
}) {
  const composing = useRef(false);
  const [notice, setNotice] = useState(null); // { text, at }

  // Ẩn nhắc nhở sau vài giây (mỗi lần nhắc mới thì đếm lại)
  useEffect(() => {
    if (!notice) return undefined;
    const timer = setTimeout(() => setNotice(null), NOTICE_MS);
    return () => clearTimeout(timer);
  }, [notice]);

  function emit(raw) {
    const { text, converted } = untelex(raw);
    const clean = [...text].filter((ch) => allowedChar.test(ch)).join('');
    if (clean !== text) setNotice({ text: warning, at: Date.now() });
    else if (converted) setNotice({ text: TELEX_NOTICE, at: Date.now() });
    onChange({ target: { name, value: clean } });
  }

  function handleChange(e) {
    // Đang ghép chữ bằng bộ gõ (IME): để yên cho bộ gõ làm xong, xử lý ở compositionend
    if (composing.current) {
      onChange({ target: { name, value: e.target.value } });
      return;
    }
    emit(e.target.value);
  }

  const hintId = `${id}-notice`;

  return (
    <>
      <input
        {...inputProps}
        id={id}
        name={name}
        onChange={handleChange}
        onCompositionStart={() => { composing.current = true; }}
        onCompositionEnd={(e) => {
          composing.current = false;
          emit(e.currentTarget.value);
        }}
        autoCapitalize="off"
        autoCorrect="off"
        spellCheck={false}
        aria-describedby={notice ? hintId : undefined}
      />
      {notice && <p id={hintId} className="field-error" role="status">{notice.text}</p>}
    </>
  );
}
