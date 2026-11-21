import { useCallback, useEffect, useMemo, useState } from 'react';

// Quản lý các dòng được tick chọn (để xóa nhiều).
// Danh sách hiển thị đổi (sang trang khác, lọc, tải lại) thì bỏ chọn hết.
export function useSelection(items) {
  const [selected, setSelected] = useState(() => new Set());

  useEffect(() => {
    setSelected(new Set());
  }, [items]);

  const toggle = useCallback((id) => {
    setSelected((prev) => {
      const next = new Set(prev);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });
  }, []);

  const allChecked = items.length > 0 && items.every((item) => selected.has(Number(item.id)));

  const toggleAll = useCallback((checked) => {
    setSelected(checked ? new Set(items.map((item) => Number(item.id))) : new Set());
  }, [items]);

  const ids = useMemo(() => [...selected], [selected]);

  return { selected, ids, toggle, toggleAll, allChecked };
}
