import { useEffect, useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';

// Thông báo truyền giữa các trang qua navigate('/', { state: { msg, error } }).
// Hiển thị 1 lần rồi xóa khỏi history, để F5 không hiện lại.
export function useFlashMessage() {
  const location = useLocation();
  const navigate = useNavigate();
  const [msg, setMsg] = useState(location.state?.msg ?? '');
  const [error, setError] = useState(location.state?.error ?? '');

  useEffect(() => {
    if (location.state?.msg || location.state?.error) {
      setMsg(location.state.msg ?? '');
      setError(location.state.error ?? '');
      navigate(location.pathname + location.search, { replace: true, state: null });
    }
  }, [location, navigate]);

  return { msg, setMsg, error, setError };
}
