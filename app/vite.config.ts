import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
    extensions: ['.mts', '.ts', '.tsx', '.mjs', '.js', '.jsx', '.json', '.vue']
  },
  css: {
    preprocessorOptions: {
      scss: {
        api: 'modern'
      },
      sass: {
        api: 'modern'
      }
    }
  },
  build: {
    sourcemap: false,
    // Skip TypeScript type checking during build
    rollupOptions: {
      // This allows the build to proceed despite TS errors
    }
  },
  server: {
    proxy: {
      // Uploaded files: /api/storage/* in nginx, /storage/* (public/storage symlink) on `php artisan serve`
      '/api/storage': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false,
        rewrite: (path) => path.replace(/^\/api\/storage/, '/storage')
      },
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false
      }
    }
  }
})
