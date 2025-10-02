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
    '~/assets/css/main.css',
    '~/assets/css/theme-integration.css',
    '~/assets/css/responsive.css'
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