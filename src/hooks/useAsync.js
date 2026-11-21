import { useCallback, useEffect, useRef, useState } from 'react';

// Hook dùng chung để tải dữ liệu bất đồng bộ (gọi service).
//   const { data, loading, error, reload } = useAsync(() => service.get(id), [id], giáTrịBanĐầu);
// - Tự chạy lại khi deps đổi; gọi reload() để tải lại thủ công
// - Bỏ qua kết quả của request cũ nếu deps đã đổi trong lúc chờ
// - error là ApiError (có .message, .status) hoặc null
export function useAsync(asyncFn, deps, initialData = null) {
  const [data, setData] = useState(initialData);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const requestId = useRef(0);
  const initial = useRef(initialData);

  // eslint-disable-next-line react-hooks/exhaustive-deps
  const run = useCallback(asyncFn, deps);

  const reload = useCallback(async () => {
    const id = ++requestId.current;
    setLoading(true);
    try {
      const result = await run();
      if (id === requestId.current) {
        setData(result);
        setError(null);
      }
    } catch (err) {
      if (id === requestId.current) {
        setData(initial.current);
        setError(err);
      }
    } finally {
      if (id === requestId.current) setLoading(false);
    }
  }, [run]);

  useEffect(() => {
    reload();
    // Component bị gỡ → bỏ qua mọi kết quả đang chờ
    return () => { requestId.current++; };
  }, [reload]);

  return { data, loading, error, reload };
}
