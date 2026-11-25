import { useCallback, useState } from 'react';

// Gửi dữ liệu lên server: quản lý trạng thái "đang gửi" và thông báo lỗi.
//   const { submit, submitting, error } = useSubmit(async (data) => {
//     await productService.create(data);
//     navigate('/');
//   });
export function useSubmit(action) {
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');

  const submit = useCallback(async (...args) => {
    setSubmitting(true);
    setError('');
    try {
      await action(...args);
    } catch (err) {
      setError(err.message);
    } finally {
      setSubmitting(false);
    }
  }, [action]);

  return { submit, submitting, error, setError };
}
