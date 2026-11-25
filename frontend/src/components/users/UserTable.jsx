import { formatDateTime } from '../../utils/format.js';
import { ROLE_LABEL } from './roles.js';

// Bảng tài khoản — chỉ hiển thị, thao tác báo lên trang cha.
// Props: users, loading, currentUserId, busyId (id đang lưu), onRoleChange(user, role), onDelete(user)
// Không cho đổi vai trò / xóa chính mình (server cũng chặn).
export default function UserTable({ users, loading, currentUserId, busyId, onRoleChange, onDelete }) {
  return (
    <table className={loading ? 'is-loading' : undefined}>
      <thead>
        <tr>
          <th>ID</th>
          <th>Tên đăng nhập</th>
          <th>Vai trò</th>
          <th>Ngày tạo</th>
          <th>Thao tác</th>
        </tr>
      </thead>
      <tbody>
        {users.map((u) => {
          const isSelf = u.id === currentUserId;
          const busy = busyId === u.id;
          return (
            <tr key={u.id}>
              <td>{u.id}</td>
              <td>{u.username}{isSelf && <span className="muted"> (bạn)</span>}</td>
              <td>
                <select className="filter" value={u.role} disabled={isSelf || busy}
                        aria-label={`Vai trò của ${u.username}`}
                        onChange={(e) => onRoleChange(u, e.target.value)}>
                  {Object.entries(ROLE_LABEL).map(([role, label]) => <option key={role} value={role}>{label}</option>)}
                </select>
              </td>
              <td>{formatDateTime(u.created_at)}</td>
              <td className="actions">
                <button type="button" className="btn btn-delete" disabled={isSelf || busy}
                        title={isSelf ? 'Không thể xóa tài khoản đang đăng nhập' : undefined}
                        onClick={() => onDelete(u)}>Xóa</button>
              </td>
            </tr>
          );
        })}
        {users.length === 0 && (
          <tr><td colSpan={5}>{loading ? 'Đang tải...' : 'Không có tài khoản'}</td></tr>
        )}
      </tbody>
    </table>
  );
}
