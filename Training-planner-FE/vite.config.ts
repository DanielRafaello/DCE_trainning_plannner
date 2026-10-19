import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'


const BACKEND_URL = process.env.BACKEND_URL ?? 'http://localhost:8080';

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    vueDevTools(),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    host: true, // listen on 0.0.0.0 so the dev server is reachable from outside the docker container
    proxy: {
      // VITE_API_BASE_URL=/api → /api/auth/login is forwarded to BACKEND_URL/auth/login
      '/api': {
        target: BACKEND_URL,
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/api/, ''),
        // The backend scopes the refresh_token cookie to Path=/auth; in the browser that's /api/auth
        cookiePathRewrite: { '/auth': '/api/auth' },
      }
    }
  }
})
