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
          // Don't rewrite paths with subdirectories or special routes
          // These are handled by the backend router
          if (path.includes('/attendance-') || 
              path.includes('/enroll') || 
              path.includes('/unenroll') || 
              path.includes('/instructors') || 
              path.includes('/students') ||
              path.includes('/auth/')) {
            return path
          }
          // For simple endpoints without subdirectories, add .php for backward compatibility
          // Example: /api/courses -> /api/courses.php
          // But only if it doesn't already have .php and has no subdirectories
          const parts = path.split('/').filter(p => p)
          if (parts.length === 2 && !path.endsWith('.php')) {
            // Simple endpoint like /api/courses or /api/users
            return path + '.php'
          }
          return path
        }
      }
    }
  }
})

