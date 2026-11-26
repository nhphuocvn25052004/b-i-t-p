import { PERMISSION_LABEL } from './roles.js';

// Danh sách checkbox quyền của editor.
// Props: value (mảng quyền đang có), onChange(mảng quyền mới), disabled, idPrefix
export default function PermissionChecks({ value, onChange, disabled, idPrefix }) {
  function toggle(permission, checked) {
    onChange(checked ? [...value, permission] : value.filter((p) => p !== permission));
  }

  return (
    <div className="perm-checks">
      {Object.entries(PERMISSION_LABEL).map(([permission, label]) => (
        <label key={permission} htmlFor={`${idPrefix}-${permission}`}>
          <input type="checkbox" id={`${idPrefix}-${permission}`} disabled={disabled}
                 checked={value.includes(permission)}
                 onChange={(e) => toggle(permission, e.target.checked)} />
          {' '}{label}
        </label>
      ))}
    </div>
  );
}
