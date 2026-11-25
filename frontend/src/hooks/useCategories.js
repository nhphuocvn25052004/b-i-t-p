import { useAsync } from './useAsync.js';
import { categoryService } from '../services/categoryService.js';

const NONE = [];

// Danh sách danh mục (cho bộ lọc và form sản phẩm)
export function useCategories() {
  const { data, loading, error } = useAsync(categoryService.list, [], NONE);
  return { categories: data, loading, error: error?.message ?? '' };
}
