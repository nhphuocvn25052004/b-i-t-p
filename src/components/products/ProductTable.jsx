import { Link } from 'react-router-dom';
import { formatPrice } from '../../utils/format.js';

// Bảng sản phẩm — chỉ hiển thị, mọi thao tác báo ngược lên trang cha qua props callback.
// Props:
//   products, loading, emptyText
//   canEdit      true = hiện cột chọn + nút Sửa / Xóa (chỉ admin)
//   selected     Set các id đang tick, allChecked
//   onToggle(id), onToggleAll(checked), onDelete(product)
export default function ProductTable({
  products, loading, emptyText, canEdit,
  selected, allChecked, onToggle, onToggleAll, onDelete,
}) {
  const columns = canEdit ? 7 : 5;

  return (
    <table className={loading ? 'is-loading' : undefined}>
      <thead>
        <tr>
          {canEdit && (
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
          {canEdit && <th>Thao tác</th>}
        </tr>
      </thead>
      <tbody>
        {products.map((p) => {
          const id = Number(p.id);
          return (
            <tr key={id}>
              {canEdit && (
                <td className="check">
                  <input type="checkbox" aria-label={`Chọn ${p.name}`}
                         checked={selected.has(id)} onChange={() => onToggle(id)} />
                </td>
              )}
              <td>{id}</td>
              <td>{p.name}</td>
              <td>{p.category_name ?? <span className="muted">Chưa phân loại</span>}</td>
              <td className="price">{formatPrice(p.price)}</td>
              <td>{p.description}</td>
              {canEdit && (
                <td className="actions">
                  <Link to={`/edit/${id}`} className="btn btn-edit">Sửa</Link>{' '}
                  <button type="button" className="btn btn-delete" onClick={() => onDelete(p)}>Xóa</button>
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
