// Ô chọn danh mục để lọc.
// Props: categories, value ('' | 'none' | id), onChange(giá_trị_mới)
export default function CategoryFilter({ categories, value, onChange }) {
  return (
    <select className="filter" value={value} onChange={(e) => onChange(e.target.value)}
            aria-label="Lọc theo danh mục">
      <option value="">Tất cả danh mục</option>
      {categories.map((c) => (
        <option key={c.id} value={String(c.id)}>
          {c.name} ({c.product_count})
        </option>
      ))}
      <option value="none">Chưa phân loại</option>
    </select>
  );
}
