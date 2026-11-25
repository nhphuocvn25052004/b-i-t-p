import { useAsync } from './useAsync.js';
import { userService } from '../services/userService.js';

const NONE = [];

// Danh sách tài khoản (trang Quản lý người dùng). Gọi reload() sau khi thêm / sửa / xóa.
export function useUsers() {
  const { data, loading, error, reload } = useAsync(userService.list, [], NONE);
  return { users: data, loading, error: error?.message ?? '', reload };
}
