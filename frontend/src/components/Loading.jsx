// Dòng "Đang tải..." dùng chung
export default function Loading({ text = 'Đang tải...' }) {
  return <p className="loading">{text}</p>;
}
