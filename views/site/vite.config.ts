import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { viteCommonjs } from '@originjs/vite-plugin-commonjs'
import styleImport, { VantResolve } from 'vite-plugin-style-import'
// import { config } from 'dotenv'
import { resolve } from 'path'

// config({ path: `.env.${process.env.NODE_ENV}` })

export default defineConfig({
  plugins: [
    vue(),
    viteCommonjs(),
    styleImport({
      resolves: [VantResolve()],
    }),
  ],
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
      },
      // output: {
      //   entryFileNames: '[name]-[hash].js',
      // },
    },
  },
  server: {
    port: 9000,
  },
})
