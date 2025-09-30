export const usePromotionManagement = () => {
  const api = useApi()
  const loading = ref(false)
  const error = ref(null)

  // Promotion data
  const promotions = ref([])
  const totalPromotions = ref(0)
  const currentPage = ref(1)
  const perPage = ref(20)

  // Promotion clicks and stats
  const promotionClicks = ref([])
  const promotionStats = ref({})

  // Selected promotion
  const selectedPromotion = ref(null)

  // Modal states
  const showCreateModal = ref(false)
  const showEditModal = ref(false)
  const showDetailModal = ref(false)
  const showStatsModal = ref(false)
  const showLinkGeneratorModal = ref(false)

  // Form data
  const promotionForm = ref({
    id: null,
    server_id: '',
    promotion_type: 'referral', // referral, click, signup, etc.
    title: '',
    description: '',
    reward_type: 'game_currency', // game_currency, items, points, etc.
    reward_amount: '',
    reward_description: '',
    max_uses: '',
    max_uses_per_user: '',
    start_date: '',
    end_date: '',
    status: 'active',
    requirements: {},
    metadata: {}
  })

  // Link generator form
  const linkForm = ref({
    server_id: '',
    campaign_name: '',
    utm_source: '',
    utm_medium: '',
    utm_campaign: '',
    custom_params: {}
  })

  /**
   * Fetch promotions with pagination and filters
   */
  const fetchPromotions = async (params = {}) => {
    try {
      loading.value = true
      error.value = null

      const queryParams = {
        page: currentPage.value,
        per_page: perPage.value,
        server_id: params.server_id || '',
        status: params.status || '',
        promotion_type: params.promotion_type || '',
        date_from: params.date_from || '',
        date_to: params.date_to || '',
        sort_by: params.sort_by || 'created_at',
        sort_order: params.sort_order || 'desc',
        ...params
      }

      const response = await api.getPromotions(queryParams)

      if (response.status === 'success') {
        promotions.value = response.data.promotions || []
        totalPromotions.value = response.data.total || 0
        currentPage.value = response.data.current_page || 1
      } else {
        throw new Error(response.message || 'Failed to fetch promotions')
      }
    } catch (err) {
      console.error('Failed to fetch promotions:', err)
      error.value = err.message || 'Failed to load promotions'
      promotions.value = []
    } finally {
      loading.value = false
    }
  }

  /**
   * Get promotion details
   */
  const getPromotionDetails = async (promotionId) => {
    try {
      loading.value = true
      const response = await api.getPromotionDetails(promotionId)

      if (response.status === 'success') {
        selectedPromotion.value = response.data.promotion
        return response.data.promotion
      } else {
        throw new Error(response.message || 'Failed to fetch promotion details')
      }
    } catch (err) {
      console.error('Failed to get promotion details:', err)
      error.value = err.message || 'Failed to load promotion details'
      return null
    } finally {
      loading.value = false
    }
  }

  /**
   * Get promotion statistics
   */
  const getPromotionStats = async (promotionId, period = '30 days') => {
    try {
      const response = await api.getPromotionStatistics(promotionId, { period })

      if (response.status === 'success') {
        promotionStats.value = response.data.stats
        return response.data.stats
      } else {
        throw new Error(response.message || 'Failed to fetch promotion stats')
      }
    } catch (err) {
      console.error('Failed to get promotion stats:', err)
      return {}
    }
  }

  /**
   * Get promotion clicks
   */
  const getPromotionClicks = async (promotionId, params = {}) => {
    try {
      const response = await api.getPromotionClicks(promotionId, params)

      if (response.status === 'success') {
        promotionClicks.value = response.data.clicks || []
        return response.data.clicks
      } else {
        throw new Error(response.message || 'Failed to fetch promotion clicks')
      }
    } catch (err) {
      console.error('Failed to get promotion clicks:', err)
      return []
    }
  }

  /**
   * Create new promotion
   */
  const createPromotion = async (promotionData) => {
    try {
      loading.value = true
      error.value = null

      const response = await api.createPromotion(promotionData)

      if (response.status === 'success') {
        await fetchPromotions()
        resetPromotionForm()
        showCreateModal.value = false
        return { success: true, promotion: response.data.promotion }
      } else {
        throw new Error(response.message || 'Failed to create promotion')
      }
    } catch (err) {
      console.error('Failed to create promotion:', err)
      error.value = err.message || 'Failed to create promotion'
      return { success: false, error: err.message }
    } finally {
      loading.value = false
    }
  }

  /**
   * Update promotion
   */
  const updatePromotion = async (promotionId, promotionData) => {
    try {
      loading.value = true
      error.value = null

      const response = await api.updatePromotion(promotionId, promotionData)

      if (response.status === 'success') {
        // Update local promotion data
        const promotionIndex = promotions.value.findIndex(p => p.id === promotionId)
        if (promotionIndex !== -1) {
          promotions.value[promotionIndex] = { ...promotions.value[promotionIndex], ...response.data.promotion }
        }

        resetPromotionForm()
        showEditModal.value = false
        return { success: true, promotion: response.data.promotion }
      } else {
        throw new Error(response.message || 'Failed to update promotion')
      }
    } catch (err) {
      console.error('Failed to update promotion:', err)
      error.value = err.message || 'Failed to update promotion'
      return { success: false, error: err.message }
    } finally {
      loading.value = false
    }
  }

  /**
   * Delete promotion
   */
  const deletePromotion = async (promotionId) => {
    try {
      loading.value = true
      error.value = null

      const response = await api.deletePromotion(promotionId)

      if (response.status === 'success') {
        promotions.value = promotions.value.filter(p => p.id !== promotionId)
        totalPromotions.value -= 1
        return { success: true }
      } else {
        throw new Error(response.message || 'Failed to delete promotion')
      }
    } catch (err) {
      console.error('Failed to delete promotion:', err)
      error.value = err.message || 'Failed to delete promotion'
      return { success: false, error: err.message }
    } finally {
      loading.value = false
    }
  }

  /**
   * Toggle promotion status
   */
  const togglePromotionStatus = async (promotionId) => {
    try {
      const promotion = promotions.value.find(p => p.id === promotionId)
      if (!promotion) return { success: false, error: 'Promotion not found' }

      const newStatus = promotion.status === 'active' ? 'inactive' : 'active'
      const result = await updatePromotion(promotionId, { status: newStatus })

      return result
    } catch (err) {
      console.error('Failed to toggle promotion status:', err)
      return { success: false, error: err.message }
    }
  }

  /**
   * Generate promotion link
   */
  const generatePromotionLink = async (linkData) => {
    try {
      loading.value = true
      error.value = null

      const response = await api.generatePromotionLink(linkData)

      if (response.status === 'success') {
        return { success: true, data: response.data }
      } else {
        throw new Error(response.message || 'Failed to generate promotion link')
      }
    } catch (err) {
      console.error('Failed to generate promotion link:', err)
      error.value = err.message || 'Failed to generate promotion link'
      return { success: false, error: err.message }
    } finally {
      loading.value = false
    }
  }

  /**
   * Generate promotion QR code
   */
  const generateQRCode = async (promotionId, options = {}) => {
    try {
      const response = await api.generatePromotionQR(promotionId, options)

      if (response.status === 'success') {
        return { success: true, qrCode: response.data.qr_code }
      } else {
        throw new Error(response.message || 'Failed to generate QR code')
      }
    } catch (err) {
      console.error('Failed to generate QR code:', err)
      return { success: false, error: err.message }
    }
  }

  /**
   * Generate custom promotion link with UTM parameters
   */
  const generateCustomPromotionLink = (promotionId, options = {}) => {
    const baseUrl = 'https://promotion.mercylife.cc/p'
    const params = new URLSearchParams()

    // Basic promotion parameters
    params.append('id', promotionId)
    params.append('ref', options.referenceCode || generateReferenceCode())

    // UTM parameters
    if (options.utm_source) params.append('utm_source', options.utm_source)
    if (options.utm_medium) params.append('utm_medium', options.utm_medium)
    if (options.utm_campaign) params.append('utm_campaign', options.utm_campaign)
    if (options.utm_term) params.append('utm_term', options.utm_term)
    if (options.utm_content) params.append('utm_content', options.utm_content)

    // Custom parameters
    if (options.custom) {
      Object.entries(options.custom).forEach(([key, value]) => {
        params.append(key, value)
      })
    }

    // Tracking parameters
    params.append('t', Date.now()) // Timestamp for unique tracking

    if (options.source) params.append('src', options.source)
    if (options.campaign) params.append('campaign', options.campaign)

    return `${baseUrl}?${params.toString()}`
  }

  /**
   * Generate reference code
   */
  const generateReferenceCode = (length = 8) => {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
    let result = ''
    for (let i = 0; i < length; i++) {
      result += chars.charAt(Math.floor(Math.random() * chars.length))
    }
    return result
  }

  /**
   * Validate promotion link
   */
  const validatePromotionLink = (url) => {
    try {
      const urlObj = new URL(url)
      const params = new URLSearchParams(urlObj.search)

      return {
        isValid: true,
        promotionId: params.get('id'),
        referenceCode: params.get('ref'),
        utmParams: {
          source: params.get('utm_source'),
          medium: params.get('utm_medium'),
          campaign: params.get('utm_campaign'),
          term: params.get('utm_term'),
          content: params.get('utm_content')
        },
        customParams: Object.fromEntries(
          Array.from(params.entries()).filter(([key]) =>
            !['id', 'ref', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 't', 'src', 'campaign'].includes(key)
          )
        )
      }
    } catch (error) {
      return {
        isValid: false,
        error: 'Invalid URL format'
      }
    }
  }

  /**
   * Get promotion link analytics
   */
  const getPromotionLinkAnalytics = async (promotionId, dateRange = '30days') => {
    try {
      const response = await api.getPromotionClicks(promotionId, {
        period: dateRange,
        analytics: true
      })

      if (response.status === 'success') {
        return {
          success: true,
          analytics: {
            totalClicks: response.data.total_clicks || 0,
            uniqueClicks: response.data.unique_clicks || 0,
            conversionRate: response.data.conversion_rate || 0,
            topSources: response.data.top_sources || [],
            clicksByDay: response.data.clicks_by_day || [],
            deviceBreakdown: response.data.device_breakdown || {},
            locationBreakdown: response.data.location_breakdown || {}
          }
        }
      } else {
        throw new Error(response.message || 'Failed to get analytics')
      }
    } catch (error) {
      console.error('Failed to get promotion analytics:', error)
      return { success: false, error: error.message }
    }
  }

  /**
   * Batch generate promotion links
   */
  const batchGenerateLinks = async (promotionId, configurations = []) => {
    try {
      const links = configurations.map(config => ({
        ...config,
        url: generateCustomPromotionLink(promotionId, config),
        generated_at: new Date().toISOString()
      }))

      // Save batch to backend if needed
      const response = await generatePromotionLink({
        promotion_id: promotionId,
        batch_links: links
      })

      if (response.success) {
        return { success: true, links: links }
      } else {
        return { success: false, error: response.error }
      }
    } catch (error) {
      console.error('Failed to batch generate links:', error)
      return { success: false, error: error.message }
    }
  }

  /**
   * Track promotion click
   */
  const trackPromotionClick = async (promotionId, clickData) => {
    try {
      await api.trackPromotionClick(promotionId, clickData)
      return { success: true }
    } catch (err) {
      console.error('Failed to track promotion click:', err)
      return { success: false, error: err.message }
    }
  }

  /**
   * Reset promotion form
   */
  const resetPromotionForm = () => {
    promotionForm.value = {
      id: null,
      server_id: '',
      promotion_type: 'referral',
      title: '',
      description: '',
      reward_type: 'game_currency',
      reward_amount: '',
      reward_description: '',
      max_uses: '',
      max_uses_per_user: '',
      start_date: '',
      end_date: '',
      status: 'active',
      requirements: {},
      metadata: {}
    }
  }

  /**
   * Reset link form
   */
  const resetLinkForm = () => {
    linkForm.value = {
      server_id: '',
      campaign_name: '',
      utm_source: '',
      utm_medium: '',
      utm_campaign: '',
      custom_params: {}
    }
  }

  /**
   * Open create modal
   */
  const openCreateModal = () => {
    resetPromotionForm()
    showCreateModal.value = true
  }

  /**
   * Open edit modal
   */
  const openEditModal = (promotion) => {
    promotionForm.value = {
      id: promotion.id,
      server_id: promotion.server_id || '',
      promotion_type: promotion.promotion_type || 'referral',
      title: promotion.title || '',
      description: promotion.description || '',
      reward_type: promotion.reward_type || 'game_currency',
      reward_amount: promotion.reward_amount || '',
      reward_description: promotion.reward_description || '',
      max_uses: promotion.max_uses || '',
      max_uses_per_user: promotion.max_uses_per_user || '',
      start_date: promotion.start_date || '',
      end_date: promotion.end_date || '',
      status: promotion.status || 'active',
      requirements: promotion.requirements || {},
      metadata: promotion.metadata || {}
    }
    showEditModal.value = true
  }

  /**
   * Open detail modal
   */
  const openDetailModal = async (promotion) => {
    selectedPromotion.value = promotion
    await getPromotionDetails(promotion.id)
    await getPromotionStats(promotion.id)
    showDetailModal.value = true
  }

  /**
   * Open stats modal
   */
  const openStatsModal = async (promotion) => {
    selectedPromotion.value = promotion
    await getPromotionStats(promotion.id)
    await getPromotionClicks(promotion.id)
    showStatsModal.value = true
  }

  /**
   * Open link generator modal
   */
  const openLinkGeneratorModal = () => {
    resetLinkForm()
    showLinkGeneratorModal.value = true
  }

  /**
   * Format promotion type display
   */
  const formatPromotionType = (type) => {
    const typeMap = {
      'referral': '推薦獎勵',
      'click': '點擊獎勵',
      'signup': '註冊獎勵',
      'activity': '活動獎勵',
      'milestone': '里程碑獎勵'
    }
    return typeMap[type] || type
  }

  /**
   * Format reward type display
   */
  const formatRewardType = (type) => {
    const typeMap = {
      'game_currency': '遊戲幣',
      'items': '道具獎勵',
      'points': '積分獎勵',
      'experience': '經驗獎勵',
      'other': '其他獎勵'
    }
    return typeMap[type] || type
  }

  /**
   * Format promotion status display
   */
  const formatStatus = (status) => {
    const statusMap = {
      'active': '啟用中',
      'inactive': '已停用',
      'expired': '已過期',
      'draft': '草稿',
      'pending': '待審核'
    }
    return statusMap[status] || status
  }

  /**
   * Get status color class
   */
  const getStatusColor = (status) => {
    switch (status) {
      case 'active':
        return 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
      case 'inactive':
        return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
      case 'expired':
        return 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
      case 'draft':
        return 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400'
      case 'pending':
        return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400'
      default:
        return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
    }
  }

  /**
   * Check if promotion is expired
   */
  const isPromotionExpired = (promotion) => {
    if (!promotion.end_date) return false
    return new Date(promotion.end_date) < new Date()
  }

  /**
   * Check if promotion is active
   */
  const isPromotionActive = (promotion) => {
    if (promotion.status !== 'active') return false
    if (isPromotionExpired(promotion)) return false

    const now = new Date()
    if (promotion.start_date && new Date(promotion.start_date) > now) return false

    return true
  }

  /**
   * Pagination helpers
   */
  const totalPages = computed(() => Math.ceil(totalPromotions.value / perPage.value))

  const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
      currentPage.value = page
      fetchPromotions()
    }
  }

  const nextPage = () => {
    if (currentPage.value < totalPages.value) {
      currentPage.value += 1
      fetchPromotions()
    }
  }

  const prevPage = () => {
    if (currentPage.value > 1) {
      currentPage.value -= 1
      fetchPromotions()
    }
  }

  return {
    // Data
    promotions,
    totalPromotions,
    currentPage,
    perPage,
    promotionClicks,
    promotionStats,
    selectedPromotion,
    loading,
    error,

    // Modal states
    showCreateModal,
    showEditModal,
    showDetailModal,
    showStatsModal,
    showLinkGeneratorModal,

    // Forms
    promotionForm,
    linkForm,

    // Methods
    fetchPromotions,
    getPromotionDetails,
    getPromotionStats,
    getPromotionClicks,
    createPromotion,
    updatePromotion,
    deletePromotion,
    togglePromotionStatus,
    generatePromotionLink,
    generateQRCode,
    generateCustomPromotionLink,
    generateReferenceCode,
    validatePromotionLink,
    getPromotionLinkAnalytics,
    batchGenerateLinks,
    trackPromotionClick,
    openCreateModal,
    openEditModal,
    openDetailModal,
    openStatsModal,
    openLinkGeneratorModal,

    // Utilities
    formatPromotionType,
    formatRewardType,
    formatStatus,
    getStatusColor,
    isPromotionExpired,
    isPromotionActive,

    // Pagination
    totalPages,
    goToPage,
    nextPage,
    prevPage
  }
}