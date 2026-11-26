import { Link } from 'react-router-dom';
import { formatPrice } from '../../utils/format.js';

// Bảng sản phẩm — chỉ hiển thị, mọi thao tác báo ngược lên trang cha qua props callback.
// Props:
//   products, loading, emptyText
//   Cột ID hiện p.seq — số thứ tự 1..n trong danh sách của người đang xem (server tính);
//   id thật (p.id) chỉ dùng cho link Sửa và chọn để xóa.
//   canUpdate    true = hiện nút Sửa (quyền products.update)
//   canDelete    true = hiện cột chọn + nút Xóa (quyền products.delete)
//   selected     Set các id đang tick, allChecked
//   onToggle(id), onToggleAll(checked), onDelete(product)
export default function ProductTable({
  products, loading, emptyText, canUpdate, canDelete,
  selected, allChecked, onToggle, onToggleAll, onDelete,
}) {
  const hasActions = canUpdate || canDelete;
  const columns = 5 + (canDelete ? 1 : 0) + (hasActions ? 1 : 0);

  return (
    <table className={loading ? 'is-loading' : undefined}>
      <thead>
        <tr>
          {canDelete && (
            <th className="check">
              <input type="checkbox" title="Chọn tất cả" aria-label="Chọn tất cả"
                     checked={allChecked} onChange={(e) => onToggleAll(e.target.checked)} />
            </th>
          )}
          <th>ID</th>
          <th>Tên sản phẩm</th>
          <th>Danh mục</th>
          <th>Giá (VND)</th>
          <th>Mô tả</th>
          {hasActions && <th>Thao tác</th>}
        </tr>
      </thead>
      <tbody>
        {products.map((p) => {
          const id = Number(p.id);
          return (
            <tr key={id}>
              {canDelete && (
                <td className="check">
                  <input type="checkbox" aria-label={`Chọn ${p.name}`}
                         checked={selected.has(id)} onChange={() => onToggle(id)} />
                </td>
              )}
              <td>{p.seq}</td>
              <td>{p.name}</td>
              <td>{p.category_name ?? <span className="muted">Chưa phân loại</span>}</td>
              <td className="price">{formatPrice(p.price)}</td>
              <td>{p.description}</td>
              {hasActions && (
                <td className="actions">
                  {canUpdate && <><Link to={`/edit/${id}`} className="btn btn-edit">Sửa</Link>{' '}</>}
                  {canDelete && <button type="button" className="btn btn-delete" onClick={() => onDelete(p)}>Xóa</button>}
                </td>
              )}
            </tr>
          );
        })}
        {products.length === 0 && (
          <tr><td colSpan={columns}>{loading ? 'Đang tải...' : emptyText}</td></tr>
        )}
      </tbody>
    </table>
  );
}
