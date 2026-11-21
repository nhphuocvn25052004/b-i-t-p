import { useCallback, useMemo } from 'react';
import { useSearchParams } from 'react-router-dom';

export const PAGE_SIZES = [5, 10, 20, 50];
const DEFAULT_SIZE = 10;

// Bộ lọc của trang danh sách lưu trên URL (#/?q=tau&category=1&page=2&size=10)
// → F5, Back/Forward, gửi link cho người khác vẫn giữ đúng trang đang xem.
export function useProductFilters() {
  const [params, setParams] = useSearchParams();

  const filters = useMemo(() => {
    const size = Number(params.get('size'));
    return {
      q: (params.get('q') ?? '').trim(),
      category: params.get('category') ?? '',
      page: Math.max(1, Number(params.get('page')) || 1),
      perPage: PAGE_SIZES.includes(size) ? size : DEFAULT_SIZE,
    };
  }, [params]);

  // Ghi đè 1 phần bộ lọc; bỏ các giá trị rỗng / mặc định khỏi URL cho gọn
  const update = useCallback((changes, options) => {
    setParams((prev) => {
      const next = new URLSearchParams(prev);
      Object.entries(changes).forEach(([key, value]) => {
        const isDefault = value === '' || value == null
          || (key === 'page' && Number(value) === 1)
          || (key === 'size' && Number(value) === DEFAULT_SIZE);
        if (isDefault) next.delete(key);
        else next.set(key, value);
      });
      return next;
    }, options);
  }, [setParams]);

  // Đổi từ khóa / danh mục / số dòng thì quay về trang 1
  const setSearch   = useCallback((q) => update({ q: q.trim(), page: 1 }), [update]);
  const setCategory = useCallback((category) => update({ category, page: 1 }), [update]);
  const setPerPage  = useCallback((size) => update({ size, page: 1 }), [update]);
  const setPage     = useCallback((page, options) => update({ page }, options), [update]);
  const clear       = useCallback(() => setParams({}), [setParams]);

  const isFiltered = filters.q !== '' || filters.category !== '';

  return { filters, isFiltered, setSearch, setCategory, setPerPage, setPage, clear };
}
