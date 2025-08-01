interface LoadingState {
  [key: string]: boolean
}

/**
 * Global loading state management composable
 */
export const useLoadingState = () => {
  const loadingStates = ref<LoadingState>({})

  /**
   * Set loading state for a specific key
   */
  const setLoading = (key: string, loading: boolean) => {
    loadingStates.value[key] = loading
  }

  /**
   * Check if a specific key is loading
   */
  const isLoading = (key: string): boolean => {
    return loadingStates.value[key] || false
  }

  /**
   * Check if any loading state is active
   */
  const isAnyLoading = computed(() => {
    return Object.values(loadingStates.value).some(loading => loading)
  })

  /**
   * Get all active loading keys
   */
  const activeLoadingKeys = computed(() => {
    return Object.keys(loadingStates.value).filter(key => loadingStates.value[key])
  })

  /**
   * Clear all loading states
   */
  const clearAllLoading = () => {
    loadingStates.value = {}
  }

  /**
   * Clear specific loading state
   */
  const clearLoading = (key: string) => {
    delete loadingStates.value[key]
  }

  /**
   * Async wrapper that automatically manages loading state
   */
  const withLoading = async <T>(
    key: string, 
    asyncFn: () => Promise<T>,
    options?: {
      onStart?: () => void
      onSuccess?: (result: T) => void
      onError?: (error: Error) => void
      onFinally?: () => void
    }
  ): Promise<T> => {
    try {
      setLoading(key, true)
      options?.onStart?.()
      
      const result = await asyncFn()
      options?.onSuccess?.(result)
      
      return result
    } catch (error) {
      const err = error instanceof Error ? error : new Error(String(error))
      options?.onError?.(err)
      throw err
    } finally {
      setLoading(key, false)
      options?.onFinally?.()
    }
  }

  return {
    loadingStates: readonly(loadingStates),
    setLoading,
    isLoading,
    isAnyLoading,
    activeLoadingKeys,
    clearAllLoading,
    clearLoading,
    withLoading
  }
}

/**
 * Page-level loading composable with common loading patterns
 */
export const usePageLoading = () => {
  const { setLoading, isLoading, withLoading } = useLoadingState()

  // Common loading keys
  const LOADING_KEYS = {
    PAGE: 'page',
    FORM: 'form',
    DATA: 'data',
    SUBMIT: 'submit',
    DELETE: 'delete',
    UPLOAD: 'upload'
  } as const

  // Convenient methods for common loading states
  const setPageLoading = (loading: boolean) => setLoading(LOADING_KEYS.PAGE, loading)
  const setFormLoading = (loading: boolean) => setLoading(LOADING_KEYS.FORM, loading)
  const setDataLoading = (loading: boolean) => setLoading(LOADING_KEYS.DATA, loading)
  const setSubmitLoading = (loading: boolean) => setLoading(LOADING_KEYS.SUBMIT, loading)
  const setDeleteLoading = (loading: boolean) => setLoading(LOADING_KEYS.DELETE, loading)
  const setUploadLoading = (loading: boolean) => setLoading(LOADING_KEYS.UPLOAD, loading)

  const isPageLoading = () => isLoading(LOADING_KEYS.PAGE)
  const isFormLoading = () => isLoading(LOADING_KEYS.FORM)
  const isDataLoading = () => isLoading(LOADING_KEYS.DATA)
  const isSubmitLoading = () => isLoading(LOADING_KEYS.SUBMIT)
  const isDeleteLoading = () => isLoading(LOADING_KEYS.DELETE)
  const isUploadLoading = () => isLoading(LOADING_KEYS.UPLOAD)

  // Async wrappers for common operations
  const withPageLoading = <T>(asyncFn: () => Promise<T>) => 
    withLoading(LOADING_KEYS.PAGE, asyncFn)
    
  const withFormLoading = <T>(asyncFn: () => Promise<T>) => 
    withLoading(LOADING_KEYS.FORM, asyncFn)
    
  const withDataLoading = <T>(asyncFn: () => Promise<T>) => 
    withLoading(LOADING_KEYS.DATA, asyncFn)
    
  const withSubmitLoading = <T>(asyncFn: () => Promise<T>) => 
    withLoading(LOADING_KEYS.SUBMIT, asyncFn)

  return {
    // Loading state setters
    setPageLoading,
    setFormLoading,
    setDataLoading,
    setSubmitLoading,
    setDeleteLoading,
    setUploadLoading,

    // Loading state getters
    isPageLoading,
    isFormLoading,
    isDataLoading,
    isSubmitLoading,
    isDeleteLoading,
    isUploadLoading,

    // Async wrappers
    withPageLoading,
    withFormLoading,
    withDataLoading,
    withSubmitLoading,

    // Constants
    LOADING_KEYS
  }
}

/**
 * Component-level loading composable for granular control
 */
export const useComponentLoading = (componentId?: string) => {
  const { setLoading, isLoading, withLoading } = useLoadingState()
  const id = componentId || `component-${Math.random().toString(36).substr(2, 9)}`

  const setComponentLoading = (loading: boolean) => setLoading(id, loading)
  const isComponentLoading = () => isLoading(id)
  const withComponentLoading = <T>(asyncFn: () => Promise<T>) => withLoading(id, asyncFn)

  // Auto cleanup on unmount
  onUnmounted(() => {
    setLoading(id, false)
  })

  return {
    setComponentLoading,
    isComponentLoading,
    withComponentLoading,
    componentId: id
  }
}