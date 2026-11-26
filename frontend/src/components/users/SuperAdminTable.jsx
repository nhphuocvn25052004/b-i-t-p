import { Fragment, useState } from 'react';
import { formatDateTime } from '../../utils/format.js';
import ResetPasswordForm from './ResetPasswordForm.jsx';
import { ROLE_LABEL } from './roles.js';

// Bảng admin tối cao (bảng super_admins) — chỉ admin tối cao thấy.
// Props: admins, loading, currentId (id của mình trong super_admins), busyId,
//        onResetPassword(admin, password) → Promise<bool>, onDemote(admin, role), onDelete(admin)
// Không thao tác được trên chính mình (server cũng chặn) → luôn còn ít nhất 1 admin tối cao.
export default function SuperAdminTable({ admins, loading, currentId, busyId, onResetPassword, onDemote, onDelete }) {
  const [resetId, setResetId] = useState(null);

  return (
    <table className={loading ? 'is-loading' : undefined}>
      <thead>
        <tr>
          <th>ID</th>
          <th>Admin tối cao</th>
          <th>Ngày tạo</th>
          <th>Hạ cấp xuống</th>
          <th>Thao tác</th>
        </tr>
      </thead>
      <tbody>
        {admins.map((a) => {
          const isSelf = a.id === currentId;
          const busy = busyId === a.id;
          return (
            <Fragment key={a.id}>
              <tr>
                <td>{a.id}</td>
                <td>{a.username}{isSelf && <span className="muted"> (bạn)</span>}</td>
                <td>{formatDateTime(a.created_at)}</td>
                <td>
                  <select className="filter" value="" disabled={isSelf || busy}
                          aria-label={`Hạ cấp ${a.username}`}
                          onChange={(e) => e.target.value && onDemote(a, e.target.value)}>
                    <option value="">— Giữ nguyên —</option>
                    {Object.entries(ROLE_LABEL).map(([role, label]) => <option key={role} value={role}>{label}</option>)}
                  </select>
                </td>
                <td className="actions">
                  <button type="button" className="btn btn-edit" disabled={isSelf || busy}
                          aria-expanded={resetId === a.id}
                          onClick={() => setResetId(resetId === a.id ? null : a.id)}>Đặt lại MK</button>{' '}
                  <button type="button" className="btn btn-delete" disabled={isSelf || busy}
                          onClick={() => onDelete(a)}>Xóa</button>
                </td>
              </tr>
              {resetId === a.id && (
                <tr className="reset-row">
                  <td colSpan={5}>
                    <ResetPasswordForm id={`reset-super-${a.id}`} username={a.username} busy={busy}
                                       onSubmit={(password) => onResetPassword(a, password)}
                                       onCancel={() => setResetId(null)} />
                  </td>
                </tr>
              )}
            </Fragment>
          );
        })}
        {admins.length === 0 && (
          <tr><td colSpan={5}>{loading ? 'Đang tải...' : 'Không có tài khoản'}</td></tr>
        )}
      </tbody>
    </table>
  );
}
