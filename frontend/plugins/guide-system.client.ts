/**
 * User guide system plugin
 * Automatically manages user guides based on authentication and routes
 */
export default defineNuxtPlugin(() => {
  const authStore = useAuthStore()
  
  // Initialize guide system when user is authenticated
  const initializeGuides = async () => {
    if (authStore.isAuthenticated) {
      // Start auto guide checking with a delay to ensure page is loaded
      setTimeout(async () => {
        const { useAutoGuide } = await import('~/composables/useUserGuide')
        useAutoGuide()
      }, 1500)
    }
  }

  // Watch for authentication changes
  watch(() => authStore.isAuthenticated, (isAuthenticated) => {
    if (isAuthenticated) {
      initializeGuides()
    }
  }, { immediate: true })

  // Add global guide utilities
  return {
    provide: {
      guide: {
        // Quick access to guide functions for components
        startGuide: (guide: any) => {
          const { startGuide } = useUserGuide()
          startGuide(guide)
        },
        resetGuides: () => {
          const { resetAllGuides } = useUserGuide()
          resetAllGuides()
        }
      }
    }
  }
})