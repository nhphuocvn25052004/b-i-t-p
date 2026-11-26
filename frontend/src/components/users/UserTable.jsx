import { Fragment, useState } from 'react';
import { formatDateTime } from '../../utils/format.js';
import PermissionChecks from './PermissionChecks.jsx';
import ResetPasswordForm from './ResetPasswordForm.jsx';
import { ROLE_LABEL } from './roles.js';

// Bảng tài khoản — chỉ hiển thị, thao tác báo lên trang cha.
// Props: users, loading, currentUserId (null với admin tối cao — họ không nằm trong bảng users),
//        busyId (id đang lưu), isAdmin, roleOptions ({role: nhãn} cho ô vai trò; admin tối cao có thêm 'superadmin'),
//        onUpdate(user, { role, permissions }), onResetPassword(user, password) → Promise<bool>, onDelete(user)
//   admin tối cao / quản trị → đổi vai trò, tick quyền cho editor, đặt lại mật khẩu, xóa
//   editor                   → đặt lại mật khẩu, xóa (danh sách server trả về chỉ gồm tài khoản xem của mình)
// Không cho thao tác trên chính mình (server cũng chặn).
export default function UserTable({
  users, loading, currentUserId, busyId, isAdmin, roleOptions = ROLE_LABEL, onUpdate, onResetPassword, onDelete,
}) {
  const [resetId, setResetId] = useState(null);  // dòng đang mở form đặt lại mật khẩu
  const columns = isAdmin ? 7 : 5;

  return (
    <table className={loading ? 'is-loading' : undefined}>
      <thead>
        <tr>
          <th>ID</th>
          <th>Tên đăng nhập</th>
          <th>Vai trò</th>
          {isAdmin && <th>Quyền</th>}
          {isAdmin && <th>Người tạo</th>}
          <th>Ngày tạo</th>
          <th>Thao tác</th>
        </tr>
      </thead>
      <tbody>
        {users.map((u) => {
          const isSelf = u.id === currentUserId;
          const busy = busyId === u.id;
          return (
            <Fragment key={u.id}>
              <tr>
                <td>{u.id}</td>
                <td>{u.username}{isSelf && <span className="muted"> (bạn)</span>}</td>
                <td>
                  {isAdmin ? (
                    <select className="filter" value={u.role} disabled={isSelf || busy}
                            aria-label={`Vai trò của ${u.username}`}
                            onChange={(e) => onUpdate(u, { role: e.target.value, permissions: u.permissions })}>
                      {Object.entries(roleOptions).map(([role, label]) => <option key={role} value={role}>{label}</option>)}
                    </select>
                  ) : (
                    <span className={`role role-${u.role}`}>{ROLE_LABEL[u.role] ?? u.role}</span>
                  )}
                </td>
                {isAdmin && (
                  <td>
                    {u.role === 'editor' ? (
                      <PermissionChecks idPrefix={`perm-${u.id}`} value={u.permissions} disabled={busy}
                                        onChange={(permissions) => onUpdate(u, { role: u.role, permissions })} />
                    ) : (
                      <span className="muted">{u.role === 'admin' ? 'Toàn quyền (trong nhánh của mình)' : 'Chỉ xem'}</span>
                    )}
                  </td>
                )}
                {isAdmin && <td>{u.created_by_name ?? <span className="muted">Admin tối cao / tự đăng ký</span>}</td>}
                <td>{formatDateTime(u.created_at)}</td>
                <td className="actions">
                  <button type="button" className="btn btn-edit" disabled={isSelf || busy}
                          aria-expanded={resetId === u.id}
                          onClick={() => setResetId(resetId === u.id ? null : u.id)}>Đặt lại MK</button>{' '}
                  <button type="button" className="btn btn-delete" disabled={isSelf || busy}
                          title={isSelf ? 'Không thể xóa tài khoản đang đăng nhập' : undefined}
                          onClick={() => onDelete(u)}>Xóa</button>
                </td>
              </tr>
              {resetId === u.id && (
                <tr className="reset-row">
                  <td colSpan={columns}>
                    <ResetPasswordForm id={`reset-${u.id}`} username={u.username} busy={busy}
                                       onSubmit={(password) => onResetPassword(u, password)}
                                       onCancel={() => setResetId(null)} />
                  </td>
                </tr>
              )}
            </Fragment>
          );
        })}
        {users.length === 0 && (
          <tr><td colSpan={columns}>{loading ? 'Đang tải...' : 'Không có tài khoản'}</td></tr>
        )}
      </tbody>
    </table>
  );
}
