import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import basicSsl from '@vitejs/plugin-basic-ssl'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const proxyTarget = env.VITE_API_PROXY_TARGET || 'http://localhost:2345'

  return {
    plugins: [vue(), basicSsl()],
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url))
      }
    },
    server: {
      host: true,
      https: true,
      port: 5173,
      proxy: {
        '/api': {
          target: proxyTarget,
          changeOrigin: true
        }
      }
    }
  }
})
