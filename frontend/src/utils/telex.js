// Đổi chữ có dấu về các phím Telex đã gõ ra nó: á → as, â → aa, ư → uw, đ → dd, ấ → aas.
//
// Vì sao cần: bộ gõ tiếng Việt (Unikey, EVKey...) chặn phím ở cấp hệ điều hành rồi gửi vào
// trình duyệt chữ đã ghép ("á"), trang web không nhận được phím gốc ("a", "s").
// Nên chỉ có thể SUY NGƯỢC theo quy tắc Telex — đúng với cách gõ thông thường, nhưng không
// chắc chắn 100% (vd gõ VNI "a1" cũng ra "á" → bị đổi thành "as").
//
// Dấu thanh (s f r x j) đặt ngay sau cụm nguyên âm chứa nó: hóa → hoas, tiếng → tieesng.
//
//   untelex('mật khẩu')  → { text: 'maajt khaaur', converted: true }

const TONE_KEYS = {
  '́': 's', // sắc  á
  '̀': 'f', // huyền à
  '̉': 'r', // hỏi  ả
  '̃': 'x', // ngã  ã
  '̣': 'j', // nặng ạ
};
const CIRCUMFLEX = '̂';       // â ê ô  → gõ lặp chữ: aa ee oo
const BREVE_HORN = ['̆', '̛']; // ă ơ ư → gõ thêm w

const VOWEL = /^[aeiouy]/i;
const ASCII_LETTER = /^[A-Za-z]$/;

// 1 ký tự → { keys: các phím (chưa có dấu thanh), tone: phím dấu thanh hoặc '' }
// Ký tự không phải chữ tiếng Việt thì trả nguyên (để bộ lọc phía sau chặn nếu cần)
function decompose(ch) {
  if (ch === 'đ') return { keys: 'dd', tone: '' };
  if (ch === 'Đ') return { keys: 'Dd', tone: '' };

  const [base, ...marks] = [...ch.normalize('NFD')];
  if (marks.length === 0 || !ASCII_LETTER.test(base)) return { keys: ch, tone: '' };

  let keys = base;
  let tone = '';
  for (const mark of marks) {
    if (mark === CIRCUMFLEX) keys += base.toLowerCase();
    else if (BREVE_HORN.includes(mark)) keys += 'w';
    else if (TONE_KEYS[mark]) tone = TONE_KEYS[mark];
    else return { keys: ch, tone: '' }; // dấu lạ, không thuộc tiếng Việt
  }
  return { keys, tone };
}

export function untelex(text) {
  const parts = [...text].map(decompose);
  let out = '';
  let pendingTone = '';
  let converted = false;

  parts.forEach(({ keys, tone }, i) => {
    out += keys;
    if (tone) pendingTone = tone;
    if (keys.length > 1 || tone) converted = true;

    // Hết cụm nguyên âm (ký tự sau không phải nguyên âm) → đặt phím dấu thanh vào đây
    const next = parts[i + 1];
    if (pendingTone && VOWEL.test(keys) && !(next && VOWEL.test(next.keys))) {
      out += pendingTone;
      pendingTone = '';
    }
  });

  return { text: out + pendingTone, converted };
}
