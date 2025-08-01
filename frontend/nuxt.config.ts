// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  devtools: { enabled: true },
  modules: [
    '@element-plus/nuxt',
    '@pinia/nuxt',
    '@vueuse/nuxt'
  ],
  css: ['~/assets/css/main.css'],
  runtimeConfig: {
    public: {
      apiBase: process.env.API_BASE_URL || 'http://localhost:8080/api'
    }
  },
  elementPlus: {
    /** Options */
  },

  // TypeScript configuration
  typescript: {
    strict: true,
    typeCheck: true
  },

  // Build optimization
  build: {
    transpile: ['element-plus']
  },

  // Vite configuration
  vite: {
    optimizeDeps: {
      include: ['element-plus/es', '@element-plus/icons-vue']
    }
  }
})