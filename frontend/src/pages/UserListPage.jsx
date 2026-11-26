import { useState } from 'react';
import Layout from '../components/Layout.jsx';
import SuperAdminTable from '../components/users/SuperAdminTable.jsx';
import UserForm from '../components/users/UserForm.jsx';
import UserTable from '../components/users/UserTable.jsx';
import { ALL_ROLE_LABEL, ROLE_LABEL } from '../components/users/roles.js';
import { useAsync } from '../hooks/useAsync.js';
import { useAuth } from '../hooks/useAuth.js';
import { useFlashMessage } from '../hooks/useFlashMessage.js';
import { useUsers } from '../hooks/useUsers.js';
import { superAdminService } from '../services/superAdminService.js';
import { userService } from '../services/userService.js';
import { passwordError, usernameError } from '../utils/validation.js';

// Quản lý người dùng.
//   admin tối cao → mọi tài khoản: thêm, đổi vai trò, cấp quyền, cưỡng chế đổi mật khẩu, xóa;
//                   quản lý cả bảng admin tối cao (thêm, nâng / hạ cấp, đặt lại mật khẩu, xóa)
//   quản trị      → như trên nhưng chỉ với cây tài khoản do mình tạo / nâng lên
//   editor        → (có quyền users.create) thêm / đặt lại mật khẩu / xóa tài khoản xem của mình
const NONE = [];
const noSuperAdmins = async () => NONE;

export default function UserListPage() {
  const { user: me, isAdmin } = useAuth();
  const isSuper = me?.role === 'superadmin';
  const { users, loading, error: loadError, reload } = useUsers();
  // Bảng admin tối cao — chỉ admin tối cao mới tải (người khác server trả 403)
  const supers = useAsync(isSuper ? superAdminService.list : noSuperAdmins, [isSuper], NONE);
  const { msg, setMsg, error, setError } = useFlashMessage();
  const [creating, setCreating] = useState(false);
  const [busyId, setBusyId] = useState(null);
  const [busySuperId, setBusySuperId] = useState(null);
  const roleOptions = isSuper ? ALL_ROLE_LABEL : ROLE_LABEL;

  // Chạy 1 thao tác: thành công → báo msg + tải lại danh sách; lỗi → báo error
  async function run(action, successMsg) {
    try {
      await action();
      setError('');
      setMsg(successMsg);
      // Nâng / hạ cấp chuyển tài khoản giữa 2 bảng → tải lại cả hai
      await Promise.all([reload(), supers.reload()]);
      return true;
    } catch (err) {
      setMsg('');
      setError(err.message);
      return false;
    }
  }

  // Trả về true nếu thêm được (để UserForm xóa trắng form)
  async function create(values) {
    const username = values.username.trim();
    const invalid = usernameError(username) || passwordError(values.password);
    if (invalid) {
      setMsg('');
      setError(invalid);
      return false;
    }
    setCreating(true);
    try {
      const save = values.role === 'superadmin'
        ? () => superAdminService.create({ username, password: values.password })
        : () => userService.create({ ...values, username });
      return await run(save, `Đã thêm tài khoản “${username}”.`);
    } finally {
      setCreating(false);
    }
  }

  // Đổi vai trò (hỏi lại trước) hoặc tick / bỏ tick quyền của biên tập viên (lưu ngay)
  async function update(user, { role, permissions }) {
    const roleChanged = role !== user.role;
    if (roleChanged && !window.confirm(`Đổi vai trò của “${user.username}” thành “${ALL_ROLE_LABEL[role]}”?`)) return;
    setBusyId(user.id);
    await run(() => userService.update(user.id, { role, permissions }),
      roleChanged
        ? `Đã đổi vai trò của “${user.username}” thành “${ALL_ROLE_LABEL[role]}”.`
        : `Đã cập nhật quyền của “${user.username}”.`);
    setBusyId(null);
  }

  // Trả về true nếu đặt được (để UserTable đóng form)
  async function resetPassword(user, password) {
    const invalid = passwordError(password);
    if (invalid) {
      setMsg('');
      setError(invalid);
      return false;
    }
    if (!window.confirm(`Đặt mật khẩu mới cho “${user.username}”? Người này sẽ bị đăng xuất.`)) return false;
    setBusyId(user.id);
    const ok = await run(() => userService.resetPassword(user.id, password),
      `Đã đặt mật khẩu mới cho “${user.username}”.`);
    setBusyId(null);
    return ok;
  }

  async function remove(user) {
    if (!window.confirm(`Bạn có chắc muốn xóa tài khoản “${user.username}”?\n`
      + 'Các tài khoản do người này tạo sẽ chuyển về cho người đã tạo ra họ.')) return;
    setBusyId(user.id);
    await run(() => userService.remove(user.id), `Đã xóa tài khoản “${user.username}”.`);
    setBusyId(null);
  }

  // ---- Bảng admin tối cao ----
  async function resetSuperPassword(admin, password) {
    const invalid = passwordError(password);
    if (invalid) {
      setMsg('');
      setError(invalid);
      return false;
    }
    if (!window.confirm(`Đặt mật khẩu mới cho admin tối cao “${admin.username}”?`)) return false;
    setBusySuperId(admin.id);
    const ok = await run(() => superAdminService.resetPassword(admin.id, password),
      `Đã đặt mật khẩu mới cho “${admin.username}”.`);
    setBusySuperId(null);
    return ok;
  }

  async function demoteSuper(admin, role) {
    if (!window.confirm(`Hạ “${admin.username}” từ admin tối cao xuống “${ROLE_LABEL[role]}”?`)) return;
    setBusySuperId(admin.id);
    await run(() => superAdminService.demote(admin.id, role), `Đã hạ “${admin.username}” xuống “${ROLE_LABEL[role]}”.`);
    setBusySuperId(null);
  }

  async function removeSuper(admin) {
    if (!window.confirm(`Bạn có chắc muốn xóa admin tối cao “${admin.username}”?`)) return;
    setBusySuperId(admin.id);
    await run(() => superAdminService.remove(admin.id), `Đã xóa tài khoản “${admin.username}”.`);
    setBusySuperId(null);
  }

  return (
    <Layout title="Quản lý người dùng" msg={msg} error={error || loadError || supers.error?.message}>
      <UserForm onSubmit={create} submitting={creating} isAdmin={isAdmin} roleOptions={roleOptions} />

      {isSuper && (
        <>
          <h3>Admin tối cao</h3>
          <SuperAdminTable
            admins={supers.data}
            loading={supers.loading}
            currentId={me.id}
            busyId={busySuperId}
            onResetPassword={resetSuperPassword}
            onDemote={demoteSuper}
            onDelete={removeSuper}
          />
          <h3>Tài khoản khác</h3>
        </>
      )}

      <UserTable
        users={users}
        loading={loading}
        currentUserId={me?.role === 'superadmin' ? null : me?.id}
        busyId={busyId}
        isAdmin={isAdmin}
        roleOptions={roleOptions}
        onUpdate={update}
        onResetPassword={resetPassword}
        onDelete={remove}
      />
    </Layout>
  );
}
