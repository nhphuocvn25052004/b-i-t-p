// Quy tắc tài khoản — giống hệt app/Validators/UserValidator.php.
// Kiểm tra ở React để báo lỗi ngay; server vẫn kiểm tra lại (ai cũng gọi thẳng API được).

export const USERNAME_MIN = 3;
export const USERNAME_MAX = 50;
export const PASSWORD_MIN = 6;
export const PASSWORD_MAX = 72;

const USERNAME_CHARS = /^[A-Za-z0-9_]*$/;

// 1 ký tự được phép — dùng để lọc ngay khi gõ (FilteredInput)
export const USERNAME_CHAR = /^[A-Za-z0-9_]$/;
export const PASSWORD_CHAR = /^[\x21-\x7E]$/;

// Ký tự in được trong bảng ASCII (mã 33–126): chữ KHÔNG DẤU, số, ký hiệu trên bàn phím.
// Không cho chữ có dấu (á, ư, ô...) và khoảng trắng.
const PASSWORD_CHARS = /^[\x21-\x7E]*$/;

export const PASSWORD_CHAR_MESSAGE =
  'Mật khẩu chỉ gồm chữ không dấu, số và ký hiệu (!@#...), không có chữ có dấu hay khoảng trắng.';

// Chỉ kiểm tra ký tự — để báo ngay khi đang gõ (vd bộ gõ Telex biến "as" thành "á")
export function passwordCharError(password) {
  return PASSWORD_CHARS.test(password) ? '' : PASSWORD_CHAR_MESSAGE;
}

// '' = hợp lệ
export function passwordError(password) {
  const charError = passwordCharError(password);
  if (charError) return charError;
  if (password.length < PASSWORD_MIN || password.length > PASSWORD_MAX) {
    return `Mật khẩu dài từ ${PASSWORD_MIN} đến ${PASSWORD_MAX} ký tự.`;
  }
  return '';
}

export function usernameError(username) {
  if (!USERNAME_CHARS.test(username)) {
    return 'Tên đăng nhập chỉ gồm chữ không dấu, số và dấu gạch dưới (_).';
  }
  if (username.length < USERNAME_MIN || username.length > USERNAME_MAX) {
    return `Tên đăng nhập dài từ ${USERNAME_MIN} đến ${USERNAME_MAX} ký tự.`;
  }
  return '';
}
