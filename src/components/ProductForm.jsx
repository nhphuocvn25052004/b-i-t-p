import { useState } from 'react';
import { Link } from 'react-router-dom';
import { MAX_PRICE } from '../api.js';

// Form dùng chung cho Thêm và Sửa
export default function ProductForm({ initial, onSubmit }) {
  const [product, setProduct] = useState(initial);
  const [saving, setSaving] = useState(false);

  const change = (e) => setProduct({ ...product, [e.target.name]: e.target.value });

  async function handleSubmit(e) {
    e.preventDefault();
    setSaving(true);
    try {
      await onSubmit(product);
    } finally {
      setSaving(false);
    }
  }

  return (
    <div className="box">
      <form onSubmit={handleSubmit}>
        <label htmlFor="name">Tên sản phẩm</label>
        <input type="text" id="name" name="name" required maxLength={255}
               value={product.name} onChange={change} />

        <label htmlFor="price">Giá (VND)</label>
        <input type="number" id="price" name="price" required min="0" max={MAX_PRICE} step="0.01"
               value={product.price} onChange={change} />

        <label htmlFor="description">Mô tả</label>
        <textarea id="description" name="description" rows={3}
                  value={product.description ?? ''} onChange={change} />

        <div className="buttons">
          <button type="submit" className="btn btn-add" disabled={saving}>
            {saving ? 'Đang lưu...' : 'Lưu'}
          </button>{' '}
          <Link to="/" className="btn btn-cancel">Hủy</Link>
        </div>
      </form>
    </div>
  );
}
