import { useEffect, useState } from 'react';

// Ô tìm kiếm.
// Props: value (từ khóa đang áp dụng), onSearch(từ_khóa)
// State riêng `text` = nội dung đang gõ, chỉ báo lên cha khi bấm Tìm.
export default function SearchBar({ value, onSearch }) {
  const [text, setText] = useState(value);

  // Từ khóa đổi từ bên ngoài (Back/Forward, Bỏ lọc) → cập nhật ô nhập
  useEffect(() => {
    setText(value);
  }, [value]);

  function submit(e) {
    e.preventDefault();
    onSearch(text);
  }

  return (
    <form onSubmit={submit} className="search">
      <input type="search" value={text} onChange={(e) => setText(e.target.value)}
             placeholder="Tìm theo tên hoặc mô tả..." aria-label="Từ khóa tìm kiếm" />
      <button type="submit" className="btn btn-search">Tìm</button>
    </form>
  );
}
