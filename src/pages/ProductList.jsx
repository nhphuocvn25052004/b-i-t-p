import { useCallback, useEffect, useState } from 'react';
import { Link, useLocation, useNavigate, useSearchParams } from 'react-router-dom';
import Layout from '../components/Layout.jsx';
import { formatPrice, productApi } from '../api.js';

// READ — Danh sách sản phẩm, tìm kiếm (?q=), xóa 1 / xóa nhiều
export default function ProductList() {
  const [searchParams, setSearchParams] = useSearchParams();
  const q = (searchParams.get('q') ?? '').trim();

  const location = useLocation();
  const navigate = useNavigate();

  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [keyword, setKeyword] = useState(q);
  const [selected, setSelected] = useState(new Set());
  const [msg, setMsg] = useState(location.state?.msg ?? '');
  const [error, setError] = useState('');

  // Thông báo truyền từ trang Thêm / Sửa: hiển thị 1 lần rồi xóa khỏi history
  useEffect(() => {
    if (location.state?.msg) {
      navigate(location.pathname + location.search, { replace: true, state: null });
    }
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const list = await productApi.list(q);
      setProducts(list);
      setError('');
      if (q) setMsg(`Tìm thấy ${list.length} sản phẩm.`);
    } catch (err) {
      setProducts([]);
      setError(err.message);
    } finally {
      setSelected(new Set());
      setLoading(false);
    }
  }, [q]);

  useEffect(() => {
    setKeyword(q);
    load();
  }, [q, load]);

  function search(e) {
    e.preventDefault();
    const k = keyword.trim();
    setMsg('');
    setSearchParams(k ? { q: k } : {});
  }

  function clearFilter() {
    setMsg('');
    setSearchParams({});
  }

  async function remove(ids, confirmText) {
    if (!window.confirm(confirmText)) return;
    try {
      const { deleted } = await productApi.remove(ids);
      await load();
      setMsg(ids.length === 1 ? 'Đã xóa sản phẩm.' : `Đã xóa ${deleted} sản phẩm.`);
    } catch (err) {
      setError(err.message);
    }
  }

  function removeSelected() {
    if (selected.size === 0) {
      window.alert('Bạn chưa chọn sản phẩm nào.');
      return;
    }
    remove([...selected], `Bạn có chắc muốn xóa ${selected.size} sản phẩm đã chọn?`);
  }

  function toggle(id) {
    const next = new Set(selected);
    next.has(id) ? next.delete(id) : next.add(id);
    setSelected(next);
  }

  const allChecked = products.length > 0 && selected.size === products.length;
  function toggleAll(e) {
    setSelected(e.target.checked ? new Set(products.map((p) => Number(p.id))) : new Set());
  }

  return (
    <Layout title={q ? `Tìm kiếm: ${q}` : 'Danh sách sản phẩm'} msg={msg} error={error}>
      <div className="toolbar">
        <Link to="/create" className="btn btn-add">+ Thêm</Link>

        <form onSubmit={search} className="search">
          <input type="search" value={keyword} onChange={(e) => setKeyword(e.target.value)}
                 placeholder="Tìm theo tên hoặc mô tả..." />
          <button type="submit" className="btn btn-search">Tìm</button>
          {q && <button type="button" className="btn btn-cancel" onClick={clearFilter}>Bỏ lọc</button>}
        </form>

        <button type="button" className="btn btn-delete" onClick={removeSelected}>Xóa đã chọn</button>
      </div>

      <table>
        <thead>
          <tr>
            <th className="check">
              <input type="checkbox" title="Chọn tất cả" checked={allChecked} onChange={toggleAll} />
            </th>
            <th>ID</th>
            <th>Tên sản phẩm</th>
            <th>Giá (VND)</th>
            <th>Mô tả</th>
            <th>Thao tác</th>
          </tr>
        </thead>
        <tbody>
          {products.map((row) => {
            const id = Number(row.id);
            return (
              <tr key={id}>
                <td className="check">
                  <input type="checkbox" checked={selected.has(id)} onChange={() => toggle(id)} />
                </td>
                <td>{id}</td>
                <td>{row.name}</td>
                <td className="price">{formatPrice(row.price)}</td>
                <td>{row.description}</td>
                <td className="actions">
                  <Link to={`/edit/${id}`} className="btn btn-edit">Sửa</Link>{' '}
                  <button type="button" className="btn btn-delete"
                          onClick={() => remove([id], 'Bạn có chắc muốn xóa sản phẩm này?')}>
                    Xóa
                  </button>
                </td>
              </tr>
            );
          })}
          {!loading && products.length === 0 && (
            <tr>
              <td colSpan={6}>{q ? 'Không tìm thấy sản phẩm phù hợp' : 'Không có dữ liệu'}</td>
            </tr>
          )}
          {loading && products.length === 0 && (
            <tr><td colSpan={6}>Đang tải...</td></tr>
          )}
        </tbody>
      </table>
    </Layout>
  );
}
