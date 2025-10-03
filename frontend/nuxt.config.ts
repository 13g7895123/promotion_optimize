// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  devtools: { enabled: true },
  devServer: {
    port: 3307,
    host: '0.0.0.0'
  },
  modules: [
    '@pinia/nuxt',
    '@vueuse/nuxt'
  ],
  css: [
    // CSS 已改為在各 layout 中按需載入，避免前後台樣式混用
    '~/assets/css/responsive.css'  // 通用響應式樣式
  ],
  runtimeConfig: {
    public: {
      apiBase: process.env.API_BASE_URL || (
        process.env.NODE_ENV === 'development'
          ? 'http://localhost:9217/api'
          : 'https://promotion.mercylife.cc/api'
      )
    }
  },
  // TypeScript configuration
  typescript: {
    strict: true,
    typeCheck: false
  }
})