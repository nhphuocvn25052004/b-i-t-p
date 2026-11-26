import { useEffect, useLayoutEffect, useRef } from 'react';
import { pressedKey, untelex } from '../utils/telex.js';

// Bộ gõ (Unikey...) sửa chữ bằng cách gửi liền một loạt: vài phím xóa + chữ mới.
// Các thao tác cách nhau dưới khoảng này được coi là cùng 1 lần sửa của bộ gõ.
const BURST_MS = 20;

// Ô nhập luôn ở "chế độ tiếng Anh": dù đang bật bộ gõ tiếng Việt, gõ "as" vẫn ra "as"
// (không thành "á"), gõ "dd" ra "dd". Không hiện cảnh báo gì.
//
// Cách làm: trang web không nhận được phím gốc khi bộ gõ bật — bộ gõ chặn phím "s" rồi tự
// sửa chữ trong ô. Ô này tự xử lý mọi thao tác gõ (chặn beforeinput), nhận ra 2 kiểu bộ gõ:
//   1. "Xóa + ghi lại" (Unikey...): giữ bản sao `shadow` = chữ bộ gõ tưởng đang có trong ô;
//      so shadow trước / sau để biết người dùng vừa bấm phím nào (pressedKey).
//   2. "Bôi đen + ghi đè" (bộ gõ đọc chữ trong ô, vd macOS): ô có "use", gõ r → bộ gõ thay
//      "use" bằng "ủe"; so "use" với "ủe" → phím r → ô thành "user".
// Rồi chỉ thêm đúng phím đó. Ký tự không thuộc allowedChar (khoảng trắng...) bị bỏ qua.
//
//   <FilteredInput allowedChar={/^[A-Za-z0-9_]$/} name="username" value={...} onChange={handleChange} />
//
// Props: allowedChar (RegExp khớp đúng 1 ký tự được phép),
//        onChange nhận { target: { name, value } } như ô input thường (dùng được với useForm),
//        các props còn lại truyền thẳng cho <input>.
export default function FilteredInput({ allowedChar, onChange, id, name, value, ...inputProps }) {
  const inputRef = useRef(null);
  const latest = useRef(null);
  latest.current = { allowedChar, onChange, name };

  // display: chữ hiển thị (chỉ ký tự hợp lệ); shadow: chữ bộ gõ tưởng đang có;
  // burst: lần sửa của bộ gõ đang gom; caret: vị trí con trỏ cần đặt sau khi vẽ lại
  const st = useRef({ display: value, shadow: value, burst: null, timer: 0, composing: false, caret: null });

  // Ký tự gõ / dán vào → chỉ giữ ký tự hợp lệ (chữ có dấu đổi về phím Telex: á → as)
  function clean(text) {
    return [...untelex(text).text].filter((ch) => latest.current.allowedChar.test(ch)).join('');
  }

  function emit(display, caret = null) {
    const s = st.current;
    s.display = display;
    s.caret = caret;
    latest.current.onChange({ target: { name: latest.current.name, value: display } });
  }

  // Hết 1 loạt thao tác:
  //   - bộ gõ bôi đen chữ cũ rồi ghi đè chữ có dấu → giữ chữ cũ, thêm đúng phím vừa bấm
  //   - có xóa + ghi lại → bộ gõ vừa sửa chữ (người dùng bấm đúng 1 phím)
  //   - chỉ có xóa → người dùng bấm Backspace
  function flushBurst() {
    const s = st.current;
    const burst = s.burst;
    if (!burst) return;
    clearTimeout(s.timer);
    s.burst = null;
    if (burst.kind === 'replace') {
      const byIme = [...burst.inserted].some((ch) => !latest.current.allowedChar.test(ch));
      const head = byIme
        ? burst.prefix + burst.replaced + clean(pressedKey(burst.replaced, burst.inserted)) // bộ gõ ghi đè
        : burst.prefix + clean(burst.inserted);                                              // người dùng gõ đè
      s.shadow = '';
      emit(head + burst.suffix, head.length);
    } else if (!burst.inserted) {
      emit(s.display.slice(0, Math.max(0, s.display.length - burst.deletes)));
    } else {
      emit(s.display + clean(pressedKey(burst.before, s.shadow)));
    }
  }

  function scheduleFlush() {
    const s = st.current;
    clearTimeout(s.timer);
    s.timer = setTimeout(flushBurst, BURST_MS);
  }

  // Sửa ở giữa chuỗi, có bôi đen, dán, kéo thả: sửa thẳng trên chữ hiển thị
  function editRange(type, data, start, end) {
    const s = st.current;
    const text = s.display;
    if (start === end && type.startsWith('delete')) {
      if (type === 'deleteContentBackward') start = Math.max(0, start - 1);
      else if (type === 'deleteContentForward') end = Math.min(text.length, end + 1);
      else if (type.includes('Backward')) start = 0;   // Ctrl+Backspace...
      else end = text.length;                         // Ctrl+Delete...
    }
    const inserted = type.startsWith('insert') ? clean(data) : '';
    s.shadow = ''; // bộ gõ không còn khớp vị trí → bắt đầu lại
    emit(text.slice(0, start) + inserted + text.slice(end), start + inserted.length);
  }

  // Chặn mọi thao tác gõ của trình duyệt, tự áp dụng lên display / shadow
  useEffect(() => {
    const el = inputRef.current;
    const s = st.current;

    function onBeforeInput(e) {
      const type = e.inputType || '';
      // Bộ gõ dạng ghép chữ (điện thoại, macOS): để nó làm xong, xử lý ở compositionend
      if (e.isComposing || type.includes('Composition')) return;
      if (type.startsWith('history')) { e.preventDefault(); return; } // Ctrl+Z
      const isInsert = type.startsWith('insert');
      if (!isInsert && !type.startsWith('delete')) return;
      if (type === 'insertLineBreak' || type === 'insertParagraph') return; // Enter → gửi form
      e.preventDefault();

      const data = isInsert ? (e.data ?? e.dataTransfer?.getData('text/plain') ?? '') : '';
      const length = el.value.length;
      const start = el.selectionStart ?? length;
      const end = el.selectionEnd ?? length;
      const atEnd = start === end && end === length;
      const typed = type === 'insertText' || type === 'insertReplacementText';

      // Đang gom loạt ghi đè của bộ gõ: các chữ tiếp theo thuộc cùng loạt
      if (s.burst?.kind === 'replace' && typed) {
        s.burst.inserted += data;
        scheduleFlush();
        return;
      }

      // Gõ đè lên phần bôi đen: có thể là bộ gõ đọc chữ trong ô rồi ghi đè bằng chữ có dấu
      // (vd ô có "use", gõ r → bộ gõ thay "use" bằng "ủe"), hoặc người dùng tự bôi đen rồi gõ.
      // Gom cả loạt lại, flushBurst quyết định.
      if (typed && start !== end) {
        flushBurst();
        const text = s.display;
        s.burst = {
          kind: 'replace',
          prefix: text.slice(0, start),
          replaced: text.slice(start, end),
          suffix: text.slice(end),
          inserted: data,
        };
        scheduleFlush();
        return;
      }

      if (!atEnd || (type !== 'insertText' && type !== 'deleteContentBackward')) {
        flushBurst();
        editRange(type, data, start, end);
        return;
      }

      if (type === 'deleteContentBackward') {
        if (s.burst?.kind === 'replace') flushBurst();
        if (!s.burst) s.burst = { kind: 'retype', before: s.shadow, deletes: 0, inserted: false };
        s.burst.deletes += 1;
        s.shadow = [...s.shadow].slice(0, -1).join('');
        scheduleFlush();
        return;
      }

      // Gõ 1 chữ ở cuối
      s.shadow += data;
      if (s.burst) {
        s.burst.inserted = true; // chữ bộ gõ ghi lại sau khi xóa
        scheduleFlush();
        return;
      }
      emit(s.display + clean(data));
    }

    el.addEventListener('beforeinput', onBeforeInput);
    return () => {
      el.removeEventListener('beforeinput', onBeforeInput);
      clearTimeout(s.timer);
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // Giá trị bị đổi từ bên ngoài (vd form xóa trắng sau khi lưu) → bắt đầu lại
  useEffect(() => {
    const s = st.current;
    if (!s.composing && value !== s.display) {
      clearTimeout(s.timer);
      s.burst = null;
      s.display = value;
      s.shadow = value;
    }
  }, [value]);

  // Đặt lại con trỏ sau khi sửa ở giữa chuỗi
  useLayoutEffect(() => {
    const s = st.current;
    if (s.caret !== null && inputRef.current) {
      inputRef.current.setSelectionRange(s.caret, s.caret);
      s.caret = null;
    }
  });

  // Chỉ chạy khi trình duyệt đổi chữ KHÔNG qua beforeinput (tự điền mật khẩu...) hoặc đang ghép chữ
  function handleChange(e) {
    const s = st.current;
    const raw = e.target.value;
    if (s.composing) {
      latest.current.onChange({ target: { name, value: raw } });
      return;
    }
    clearTimeout(s.timer);
    s.burst = null;
    s.shadow = raw;
    emit(clean(raw));
  }

  return (
    <input
      {...inputProps}
      ref={inputRef}
      id={id}
      name={name}
      value={value}
      onChange={handleChange}
      onCompositionStart={() => {
        flushBurst();
        st.current.composing = true;
      }}
      onCompositionEnd={(e) => {
        const s = st.current;
        s.composing = false;
        s.shadow = e.currentTarget.value;
        emit(clean(e.currentTarget.value));
      }}
      autoCapitalize="off"
      autoCorrect="off"
      spellCheck={false}
    />
  );
}
