import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
  },
  // base: process.env.VITE_BASE_URL ?? '/',
  base: '/ue/site/fe',
  // base: '/',
  build: {
    outDir: '../../ue/site/fe',
    assetsInlineLimit: 10240,
    rollupOptions: {
      input: {
        login: resolve(__dirname, './login/index.html'),
        channel: resolve(__dirname, './channel/index.html'),
      },
      // output: {
      //   entryFileNames: '[name]-[hash].js',
      // },
    },
  },
  server: {
    port: 9000,
    proxy: {
      '^/rest/.*': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
      '^/kcfinder/.*': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
    },
  },
})
