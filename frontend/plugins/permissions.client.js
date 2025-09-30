export default defineNuxtPlugin(async () => {
  const { initializePermissions, vPermission, vPermissionRemove } = usePermissions()

  // Initialize user permissions on app start
  try {
    await initializePermissions()
  } catch (error) {
    console.warn('Failed to initialize permissions:', error)
  }

  // Register permission directives globally
  return {
    provide: {
      permissions: {
        vPermission,
        vPermissionRemove
      }
    }
  }
})