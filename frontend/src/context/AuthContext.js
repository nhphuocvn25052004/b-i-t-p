import { createContext } from 'react';

// State đăng nhập dùng chung cho cả ứng dụng: { user, loading, isAdmin, login, logout, register }
// Giá trị do <AuthProvider> cung cấp, đọc bằng hook useAuth()
export const AuthContext = createContext(null);
