// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  devtools: { enabled: true },
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
      apiBase: process.env.API_BASE_URL || 'http://localhost:9217/api'
    }
  },
  // TypeScript configuration
  typescript: {
    strict: true,
    typeCheck: false
  }
})