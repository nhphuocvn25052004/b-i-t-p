// Vai trò gán được cho tài khoản trong bảng users (ô chọn vai trò)
//   admin  — quản trị: chỉ quản lý cây tài khoản do mình tạo / nâng lên
//   editor — (user1) chỉ làm những việc được cấp
//   user   — (user2) chỉ xem
export const ROLE_LABEL = { admin: 'Quản trị', editor: 'Biên tập viên', user: 'Người xem' };

// Tên hiển thị của mọi vai trò, kể cả admin tối cao (bảng riêng super_admins, không gán được)
export const ALL_ROLE_LABEL = { superadmin: 'Admin tối cao', ...ROLE_LABEL };

// Admin tối cao hoặc quản trị — được chọn vai trò, cấp quyền khi quản lý tài khoản
export function isManager(user) {
  return user?.role === 'superadmin' || user?.role === 'admin';
}

// Các quyền admin cấp cho editor — giống Auth::PERMISSIONS trong app/Auth/Auth.php
export const PERMISSION_LABEL = {
  'products.create': 'Thêm sản phẩm',
  'products.update': 'Sửa sản phẩm',
  'products.delete': 'Xóa sản phẩm',
  'users.create': 'Tạo / xóa tài khoản xem',
};

// Cùng luật với Auth::can() ở server: superadmin, admin → mọi quyền; editor → quyền được cấp; user → không có
export function can(user, permission) {
  if (!user) return false;
  if (isManager(user)) return true;
  return user.role === 'editor' && (user.permissions ?? []).includes(permission);
}
