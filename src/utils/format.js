// 1500000000.00 -> "1,500,000,000" (giống number_format của PHP)
export function formatPrice(price) {
  return Number(price).toLocaleString('en-US', { maximumFractionDigits: 0 });
}
