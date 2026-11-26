import { Link } from 'react-router-dom';
import { useForm } from '../../hooks/useForm.js';
import { MAX_PRICE } from '../../services/productService.js';
import { categoryLabeler } from '../../utils/format.js';

// Form dùng chung cho Thêm và Sửa.
// Props: initial (giá trị ban đầu), categories, onSubmit(product), submitting (đang lưu)
export default function ProductForm({ initial, categories, onSubmit, submitting }) {
  const { values, handleChange, handleSubmit } = useForm({
    name: initial.name ?? '',
    price: initial.price ?? '',
    description: initial.description ?? '',
    category_id: initial.category_id ? String(initial.category_id) : '',
  });
  const label = categoryLabeler(categories);

  return (
    <div className="box">
      <form onSubmit={handleSubmit(onSubmit)}>
        <label htmlFor="name">Tên sản phẩm</label>
        <input type="text" id="name" name="name" required maxLength={255}
               value={values.name} onChange={handleChange} />

        <label htmlFor="category_id">Danh mục</label>
        <select id="category_id" name="category_id" value={values.category_id} onChange={handleChange}>
          <option value="">— Chưa phân loại —</option>
          {categories.map((c) => <option key={c.id} value={String(c.id)}>{label(c)}</option>)}
        </select>

        <label htmlFor="price">Giá (VND)</label>
        <input type="number" id="price" name="price" required min="0" max={MAX_PRICE} step="0.01"
               value={values.price} onChange={handleChange} />

        <label htmlFor="description">Mô tả</label>
        <textarea id="description" name="description" rows={3}
                  value={values.description} onChange={handleChange} />

        <div className="buttons">
          <button type="submit" className="btn btn-add" disabled={submitting}>
            {submitting ? 'Đang lưu...' : 'Lưu'}
          </button>{' '}
          <Link to="/" className="btn btn-cancel">Hủy</Link>
        </div>
      </form>
    </div>
  );
}
