import { http } from './http.js';

export const categoryService = {
  // → [{ id, name, product_count }]
  list: () => http('api/categories.php'),
};
