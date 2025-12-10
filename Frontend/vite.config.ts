import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import path from 'path'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  server: {
    port: 3000,
    proxy: {
      '/api': {
        target: 'http://localhost/sams/Backend',
        changeOrigin: true,
        rewrite: (path) => {
          // Convert /api/courses to /api/courses.php
          // Convert /api/courses/enroll to /api/courses.php/enroll
          return path.replace(/^\/api\/([^\/\?]+)/, '/api/$1.php')
        }
      }
    }
  }
})

