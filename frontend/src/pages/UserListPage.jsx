import { useState } from 'react';
import Layout from '../components/Layout.jsx';
import UserForm from '../components/users/UserForm.jsx';
import UserTable from '../components/users/UserTable.jsx';
import { ROLE_LABEL } from '../components/users/roles.js';
import { useAuth } from '../hooks/useAuth.js';
import { useFlashMessage } from '../hooks/useFlashMessage.js';
import { useUsers } from '../hooks/useUsers.js';
import { userService } from '../services/userService.js';
import { passwordError, usernameError } from '../utils/validation.js';

// Quản lý người dùng (chỉ admin): xem danh sách, thêm, đổi vai trò, xóa.
export default function UserListPage() {
  const { user: me } = useAuth();
  const { users, loading, error: loadError, reload } = useUsers();
  const { msg, setMsg, error, setError } = useFlashMessage();
  const [creating, setCreating] = useState(false);
  const [busyId, setBusyId] = useState(null);

  // Chạy 1 thao tác: thành công → báo msg + tải lại danh sách; lỗi → báo error
  async function run(action, successMsg) {
    try {
      await action();
      setError('');
      setMsg(successMsg);
      await reload();
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
      return await run(() => userService.create({ ...values, username }), `Đã thêm tài khoản “${username}”.`);
    } finally {
      setCreating(false);
    }
  }

  async function changeRole(user, role) {
    if (!window.confirm(`Đổi vai trò của “${user.username}” thành “${ROLE_LABEL[role]}”?`)) return;
    setBusyId(user.id);
    await run(() => userService.updateRole(user.id, role),
      `Đã đổi vai trò của “${user.username}” thành “${ROLE_LABEL[role]}”.`);
    setBusyId(null);
  }

  async function remove(user) {
    if (!window.confirm(`Bạn có chắc muốn xóa tài khoản “${user.username}”?`)) return;
    setBusyId(user.id);
    await run(() => userService.remove(user.id), `Đã xóa tài khoản “${user.username}”.`);
    setBusyId(null);
  }

  return (
    <Layout title="Quản lý người dùng" msg={msg} error={error || loadError}>
      <UserForm onSubmit={create} submitting={creating} />

      <UserTable
        users={users}
        loading={loading}
        currentUserId={me?.id}
        busyId={busyId}
        onRoleChange={changeRole}
        onDelete={remove}
      />
    </Layout>
  );
}
