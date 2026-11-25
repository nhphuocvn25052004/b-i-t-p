import { PAGE_SIZES } from '../../hooks/useProductFilters.js';

// Danh sách số trang hiển thị: 1 … 4 5 [6] 7 8 … 20
function pageNumbers(page, totalPages) {
  const pages = new Set([1, totalPages]);
  for (let p = page - 2; p <= page + 2; p++) {
    if (p >= 1 && p <= totalPages) pages.add(p);
  }
  const sorted = [...pages].sort((a, b) => a - b);
  const result = [];
  sorted.forEach((p, i) => {
    if (i > 0 && p - sorted[i - 1] > 1) result.push('…' + p);
    result.push(p);
  });
  return result;
}

// Thanh phân trang.
// Props: page, totalPages, total, perPage, onPageChange(trang), onPerPageChange(số_dòng)
export default function Pagination({ page, totalPages, total, perPage, onPageChange, onPerPageChange }) {
  const from = total === 0 ? 0 : (page - 1) * perPage + 1;
  const to = Math.min(page * perPage, total);

  return (
    <nav className="pagination" aria-label="Phân trang">
      <span className="summary">Hiển thị {from}–{to} / {total} sản phẩm</span>

      <div className="pages">
        <button type="button" className="page" disabled={page <= 1} onClick={() => onPageChange(page - 1)}>
          ‹ Trước
        </button>
        {pageNumbers(page, totalPages).map((p) =>
          typeof p === 'string' ? (
            <span key={p} className="gap">…</span>
          ) : (
            <button type="button" key={p} className={`page${p === page ? ' current' : ''}`}
                    aria-current={p === page ? 'page' : undefined} onClick={() => onPageChange(p)}>
              {p}
            </button>
          )
        )}
        <button type="button" className="page" disabled={page >= totalPages} onClick={() => onPageChange(page + 1)}>
          Sau ›
        </button>
      </div>

      <label className="size">
        Số dòng:{' '}
        <select value={perPage} onChange={(e) => onPerPageChange(Number(e.target.value))}>
          {PAGE_SIZES.map((n) => <option key={n} value={n}>{n}</option>)}
        </select>
      </label>
    </nav>
  );
}
