import { useContext } from 'react';
import { AuthContext } from '../context/AuthContext.js';

// const { user, isAdmin, can, login, logout } = useAuth();
// can('products.create') → user hiện tại có quyền đó không
export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth phải được dùng bên trong <AuthProvider>');
  return ctx;
}
