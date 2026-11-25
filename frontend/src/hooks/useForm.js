import { useCallback, useState } from 'react';

// State cho form nhiều ô nhập. Mỗi ô cần có thuộc tính name trùng với key trong values.
//   const { values, handleChange, handleSubmit } = useForm({ username: '', password: '' });
//   <form onSubmit={handleSubmit(onSubmit)}>
//     <input name="username" value={values.username} onChange={handleChange} />
export function useForm(initialValues) {
  const [values, setValues] = useState(initialValues);

  const handleChange = useCallback((e) => {
    const { name, value } = e.target;
    setValues((prev) => ({ ...prev, [name]: value }));
  }, []);

  // Chặn trình duyệt tải lại trang, rồi gọi onSubmit với dữ liệu form
  const handleSubmit = useCallback((onSubmit) => (e) => {
    e.preventDefault();
    onSubmit(values);
  }, [values]);

  return { values, setValues, handleChange, handleSubmit };
}
