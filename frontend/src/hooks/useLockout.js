import { useCallback, useEffect, useState } from 'react';
import { formatWait } from '../utils/format.js';

// Đếm ngược khi server khóa vì nhập sai mật khẩu quá nhiều lần (HTTP 429, body { retry_after }).
//   const lockout = useLockout();
//   try { await authService.login(...) } catch (err) { if (!lockout.catchLock(err)) throw err; }
//   <button disabled={lockout.locked}>   <Layout error={lockout.message || error}>
export function useLockout() {
  const [until, setUntil] = useState(0);      // thời điểm hết khóa (ms)
  const [now, setNow] = useState(() => Date.now());

  useEffect(() => {
    if (until <= Date.now()) return undefined;
    const timer = setInterval(() => {
      setNow(Date.now());
      if (Date.now() >= until) clearInterval(timer);
    }, 1000);
    return () => clearInterval(timer);
  }, [until]);

  // Lỗi 429 → bắt đầu đếm ngược và trả về true (đã xử lý); lỗi khác → false
  const catchLock = useCallback((err) => {
    if (err?.status !== 429) return false;
    const seconds = Number(err.data?.retry_after) || 30;
    setNow(Date.now());
    setUntil(Date.now() + seconds * 1000);
    return true;
  }, []);

  const secondsLeft = Math.max(0, Math.ceil((until - now) / 1000));
  const locked = secondsLeft > 0;
  const message = locked
    ? `Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau ${formatWait(secondsLeft)}.`
    : '';

  return { locked, secondsLeft, message, catchLock };
}
