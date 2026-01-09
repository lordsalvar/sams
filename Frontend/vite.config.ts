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
        target: 'http://localhost/sams/Backend/gateway',
        changeOrigin: true,
        rewrite: (path) => {
          // Route all API requests through gateway
          // Gateway handles /api prefix routing
          return path
        }
      }
    }
  }
})

