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
        target: 'http://localhost',
        changeOrigin: true,
        secure: false,
        ws: true,
        rewrite: (path) => {
          // Rewrite /api/* to /sams/Backend/gateway/api/*
          // Gateway will strip /sams/Backend/gateway and /api prefixes
          return `/sams/Backend/gateway${path}`
        },
        configure: (proxy, _options) => {
          proxy.on('error', (err, _req, _res) => {
            // Only log if it's not a connection refused error (these can be transient)
            if (!err.message.includes('ECONNREFUSED')) {
              console.log('Proxy error:', err.message);
            }
          });
        },
      }
    }
  }
})

