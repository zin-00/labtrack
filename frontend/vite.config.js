import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'
import tailwindcss from '@tailwindcss/vite'
import vueDevTools from 'vite-plugin-vue-devtools'
import { createHtmlPlugin } from 'vite-plugin-html'

export default defineConfig({
  plugins: [
    vue(),
    tailwindcss(),
    vueDevTools(),
    createHtmlPlugin({})
  ],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  }
  // server: {
  //   host: '127.0.0.1',
  //   port: 5173,
  //   strictPort: true,
  //   hmr: {
  //     protocol: 'ws',
  //     host: '127.0.0.1',
  //     port: 5173
  //   }
  // }
})
