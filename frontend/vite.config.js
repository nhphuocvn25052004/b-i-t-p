import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  // Đường dẫn tương đối để chạy được cả khi đặt trong thư mục con
  base: './',
  server: {
    // Khi chạy "npm run dev", chuyển các request api/*.php sang PHP (php -S 127.0.0.1:8000)
    proxy: {
      '/api': 'http://127.0.0.1:8000',
    },
  },
});
