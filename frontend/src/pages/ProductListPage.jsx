import { useEffect } from 'react';
import { Link } from 'react-router-dom';
import Layout from '../components/Layout.jsx';
import SearchBar from '../components/products/SearchBar.jsx';
import CategoryFilter from '../components/products/CategoryFilter.jsx';
import ProductTable from '../components/products/ProductTable.jsx';
import Pagination from '../components/products/Pagination.jsx';
import { useAuth } from '../hooks/useAuth.js';
import { useCategories } from '../hooks/useCategories.js';
import { useFlashMessage } from '../hooks/useFlashMessage.js';
import { useProductFilters } from '../hooks/useProductFilters.js';
import { useProducts } from '../hooks/useProducts.js';
import { useSelection } from '../hooks/useSelection.js';
import { productService } from '../services/productService.js';

// READ — Danh sách: tìm kiếm, lọc theo danh mục, phân trang, xóa 1 / xóa nhiều.
// Trang này giữ state và truyền xuống các component con bằng props;
// các component con báo ngược lên bằng callback (onSearch, onChange, onDelete...).
export default function ProductListPage() {
  const { can } = useAuth();
  const canCreate = can('products.create');
  const canUpdate = can('products.update');
  const canDelete = can('products.delete');
  const { filters, isFiltered, setSearch, setCategory, setPage, setPerPage, clear } = useProductFilters();
  const {
    items, total, page, total_pages: totalPages, requestedPage, loading, error: loadError, reload,
  } = useProducts(filters);
  const { categories, error: categoryError } = useCategories();
  const selection = useSelection(items);
  const { msg, setMsg, error, setError } = useFlashMessage();

  // Server trả về trang cuối nếu trang yêu cầu không còn (vd vừa xóa hết trang cuối)
  // → sửa lại số trang trên URL cho khớp
  useEffect(() => {
    if (requestedPage === filters.page && page !== filters.page) setPage(page, { replace: true });
  }, [requestedPage, page, filters.page, setPage]);

  // Đổi bộ lọc thì bỏ thông báo cũ
  function withReset(fn) {
    return (value) => {
      setMsg('');
      setError('');
      fn(value);
    };
  }

  async function remove(ids, confirmText) {
    if (!window.confirm(confirmText)) return;
    try {
      const { deleted } = await productService.remove(ids);
      setError('');
      setMsg(ids.length === 1 ? 'Đã xóa sản phẩm.' : `Đã xóa ${deleted} sản phẩm.`);
      await reload();
    } catch (err) {
      setMsg('');
      setError(err.message);
    }
  }

  function removeSelected() {
    if (selection.ids.length === 0) {
      window.alert('Bạn chưa chọn sản phẩm nào.');
      return;
    }
    remove(selection.ids, `Bạn có chắc muốn xóa ${selection.ids.length} sản phẩm đã chọn?`);
  }

  const categoryName = filters.category === 'none'
    ? 'Chưa phân loại'
    : categories.find((c) => String(c.id) === filters.category)?.name;

  const title = filters.q ? `Tìm kiếm: ${filters.q}` : 'Danh sách sản phẩm';

  return (
    <Layout title={title} msg={msg} error={error || loadError || categoryError}>
      <div className="toolbar">
        {canCreate && <Link to="/create" className="btn btn-add">+ Thêm</Link>}

        <SearchBar value={filters.q} onSearch={withReset(setSearch)} />

        <CategoryFilter categories={categories} value={filters.category}
                        onChange={withReset(setCategory)} />

        {isFiltered && (
          <button type="button" className="btn btn-cancel" onClick={withReset(clear)}>Bỏ lọc</button>
        )}

        {canDelete && (
          <button type="button" className="btn btn-delete" onClick={removeSelected}>
            Xóa đã chọn{selection.ids.length > 0 && ` (${selection.ids.length})`}
          </button>
        )}
      </div>

      {isFiltered && !loading && !loadError && (
        <p className="result-info">
          Tìm thấy {total} sản phẩm
          {filters.q && <> khớp “{filters.q}”</>}
          {categoryName && <> trong danh mục “{categoryName}”</>}.
        </p>
      )}

      <ProductTable
        products={items}
        loading={loading}
        emptyText={isFiltered ? 'Không tìm thấy sản phẩm phù hợp' : 'Không có dữ liệu'}
        canUpdate={canUpdate}
        canDelete={canDelete}
        selected={selection.selected}
        allChecked={selection.allChecked}
        onToggle={selection.toggle}
        onToggleAll={selection.toggleAll}
        onDelete={(p) => remove([Number(p.id)], `Bạn có chắc muốn xóa sản phẩm “${p.name}”?`)}
      />

      <Pagination
        page={page}
        totalPages={totalPages}
        total={total}
        perPage={filters.perPage}
        onPageChange={setPage}
        onPerPageChange={setPerPage}
      />
    </Layout>
  );
}
