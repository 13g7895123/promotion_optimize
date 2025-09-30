export const useServerManagement = () => {
  const api = useApi()
  const loading = ref(false)
  const error = ref(null)

  // Server data
  const servers = ref([])
  const totalServers = ref(0)
  const currentPage = ref(1)
  const perPage = ref(20)

  // Selected server for operations
  const selectedServer = ref(null)

  // Modal states
  const showCreateModal = ref(false)
  const showEditModal = ref(false)
  const showDetailModal = ref(false)
  const showApprovalModal = ref(false)

  // Form data
  const serverForm = ref({
    id: null,
    server_code: '',
    server_name: '',
    game_type: '',
    version: '',
    description: '',
    website_url: '',
    discord_url: '',
    server_ip: '',
    server_port: '',
    max_players: '',
    online_players: '',
    tags: [],
    features: [],
    social_links: {},
    stats: {},
    metadata: {}
  })

  // Approval form
  const approvalForm = ref({
    action: '', // approve, reject, suspend
    reason: '',
    note: ''
  })

  /**
   * Fetch servers with pagination and filters
   */
  const fetchServers = async (params = {}) => {
    try {
      loading.value = true
      error.value = null

      const queryParams = {
        page: currentPage.value,
        per_page: perPage.value,
        search: params.search || '',
        status: params.status || '',
        game_type: params.game_type || '',
        owner_id: params.owner_id || '',
        is_featured: params.is_featured || '',
        ping_status: params.ping_status || '',
        sort_by: params.sort_by || 'created_at',
        sort_order: params.sort_order || 'desc',
        ...params
      }

      const response = await api.getServers(queryParams)

      if (response.status === 'success') {
        servers.value = response.data.servers || []
        totalServers.value = response.data.total || 0
        currentPage.value = response.data.current_page || 1
      } else {
        throw new Error(response.message || 'Failed to fetch servers')
      }
    } catch (err) {
      console.error('Failed to fetch servers:', err)
      error.value = err.message || 'Failed to load servers'
      servers.value = []
    } finally {
      loading.value = false
    }
  }

  /**
   * Get server details
   */
  const getServerDetails = async (serverId) => {
    try {
      loading.value = true
      const response = await api.getServerDetails(serverId)

      if (response.status === 'success') {
        selectedServer.value = response.data.server
        return response.data.server
      } else {
        throw new Error(response.message || 'Failed to fetch server details')
      }
    } catch (err) {
      console.error('Failed to get server details:', err)
      error.value = err.message || 'Failed to load server details'
      return null
    } finally {
      loading.value = false
    }
  }

  /**
   * Create new server
   */
  const createServer = async (serverData) => {
    try {
      loading.value = true
      error.value = null

      const response = await api.createServer(serverData)

      if (response.status === 'success') {
        await fetchServers()
        resetForm()
        showCreateModal.value = false
        return { success: true, server: response.data.server }
      } else {
        throw new Error(response.message || 'Failed to create server')
      }
    } catch (err) {
      console.error('Failed to create server:', err)
      error.value = err.message || 'Failed to create server'
      return { success: false, error: err.message }
    } finally {
      loading.value = false
    }
  }

  /**
   * Update server
   */
  const updateServer = async (serverId, serverData) => {
    try {
      loading.value = true
      error.value = null

      const response = await api.updateServer(serverId, serverData)

      if (response.status === 'success') {
        // Update local server data
        const serverIndex = servers.value.findIndex(s => s.id === serverId)
        if (serverIndex !== -1) {
          servers.value[serverIndex] = { ...servers.value[serverIndex], ...response.data.server }
        }

        resetForm()
        showEditModal.value = false
        return { success: true, server: response.data.server }
      } else {
        throw new Error(response.message || 'Failed to update server')
      }
    } catch (err) {
      console.error('Failed to update server:', err)
      error.value = err.message || 'Failed to update server'
      return { success: false, error: err.message }
    } finally {
      loading.value = false
    }
  }

  /**
   * Delete server
   */
  const deleteServer = async (serverId) => {
    try {
      loading.value = true
      error.value = null

      const response = await api.deleteServer(serverId)

      if (response.status === 'success') {
        servers.value = servers.value.filter(s => s.id !== serverId)
        totalServers.value -= 1
        return { success: true }
      } else {
        throw new Error(response.message || 'Failed to delete server')
      }
    } catch (err) {
      console.error('Failed to delete server:', err)
      error.value = err.message || 'Failed to delete server'
      return { success: false, error: err.message }
    } finally {
      loading.value = false
    }
  }

  /**
   * Approve server
   */
  const approveServer = async (serverId, note = '') => {
    try {
      const result = await api.updateServerStatus(serverId, {
        status: 'approved',
        note
      })

      if (result.success) {
        await fetchServers()
      }

      return result
    } catch (err) {
      console.error('Failed to approve server:', err)
      return { success: false, error: err.message }
    }
  }

  /**
   * Reject server
   */
  const rejectServer = async (serverId, reason, note = '') => {
    try {
      const result = await api.updateServerStatus(serverId, {
        status: 'rejected',
        rejection_reason: reason,
        note
      })

      if (result.success) {
        await fetchServers()
      }

      return result
    } catch (err) {
      console.error('Failed to reject server:', err)
      return { success: false, error: err.message }
    }
  }

  /**
   * Suspend server
   */
  const suspendServer = async (serverId, reason, note = '') => {
    try {
      const result = await api.updateServerStatus(serverId, {
        status: 'suspended',
        suspension_reason: reason,
        note
      })

      if (result.success) {
        await fetchServers()
      }

      return result
    } catch (err) {
      console.error('Failed to suspend server:', err)
      return { success: false, error: err.message }
    }
  }

  /**
   * Feature/unfeature server
   */
  const toggleServerFeature = async (serverId) => {
    try {
      const server = servers.value.find(s => s.id === serverId)
      if (!server) return { success: false, error: 'Server not found' }

      const newFeaturedStatus = !server.is_featured
      const result = await updateServer(serverId, {
        is_featured: newFeaturedStatus,
        featured_until: newFeaturedStatus ? new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString() : null
      })

      return result
    } catch (err) {
      console.error('Failed to toggle server feature:', err)
      return { success: false, error: err.message }
    }
  }

  /**
   * Test server connection
   */
  const testServerConnection = async (serverId) => {
    try {
      loading.value = true
      const response = await api.testServerConnection(serverId)

      if (response.status === 'success') {
        // Update server ping status
        const serverIndex = servers.value.findIndex(s => s.id === serverId)
        if (serverIndex !== -1) {
          servers.value[serverIndex].ping_status = response.data.ping_status
          servers.value[serverIndex].last_ping_at = new Date().toISOString()
          servers.value[serverIndex].online_players = response.data.online_players || 0
        }

        return { success: true, data: response.data }
      } else {
        throw new Error(response.message || 'Failed to test server connection')
      }
    } catch (err) {
      console.error('Failed to test server connection:', err)
      return { success: false, error: err.message }
    } finally {
      loading.value = false
    }
  }

  /**
   * Reset form data
   */
  const resetForm = () => {
    serverForm.value = {
      id: null,
      server_code: '',
      server_name: '',
      game_type: '',
      version: '',
      description: '',
      website_url: '',
      discord_url: '',
      server_ip: '',
      server_port: '',
      max_players: '',
      online_players: '',
      tags: [],
      features: [],
      social_links: {},
      stats: {},
      metadata: {}
    }
  }

  /**
   * Open create modal
   */
  const openCreateModal = () => {
    resetForm()
    showCreateModal.value = true
  }

  /**
   * Open edit modal
   */
  const openEditModal = (server) => {
    serverForm.value = {
      id: server.id,
      server_code: server.server_code || '',
      server_name: server.server_name || '',
      game_type: server.game_type || '',
      version: server.version || '',
      description: server.description || '',
      website_url: server.website_url || '',
      discord_url: server.discord_url || '',
      server_ip: server.server_ip || '',
      server_port: server.server_port || '',
      max_players: server.max_players || '',
      online_players: server.online_players || '',
      tags: server.tags || [],
      features: server.features || [],
      social_links: server.social_links || {},
      stats: server.stats || {},
      metadata: server.metadata || {}
    }
    showEditModal.value = true
  }

  /**
   * Open server detail modal
   */
  const openDetailModal = async (server) => {
    selectedServer.value = server
    await getServerDetails(server.id)
    showDetailModal.value = true
  }

  /**
   * Open approval modal
   */
  const openApprovalModal = (server, action) => {
    selectedServer.value = server
    approvalForm.value = {
      action,
      reason: '',
      note: ''
    }
    showApprovalModal.value = true
  }

  /**
   * Format server status display
   */
  const formatStatus = (status) => {
    const statusMap = {
      'pending': '待審核',
      'approved': '已核准',
      'rejected': '已拒絕',
      'suspended': '已暫停',
      'inactive': '未啟用'
    }
    return statusMap[status] || status
  }

  /**
   * Get status color class
   */
  const getStatusColor = (status) => {
    switch (status) {
      case 'approved':
        return 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
      case 'pending':
        return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400'
      case 'rejected':
        return 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
      case 'suspended':
        return 'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400'
      case 'inactive':
        return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
      default:
        return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
    }
  }

  /**
   * Get ping status color
   */
  const getPingStatusColor = (status) => {
    switch (status) {
      case 'online':
        return 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
      case 'offline':
        return 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
      case 'timeout':
        return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400'
      default:
        return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
    }
  }

  /**
   * Format ping status display
   */
  const formatPingStatus = (status) => {
    const statusMap = {
      'online': '在線',
      'offline': '離線',
      'timeout': '超時',
      'unknown': '未知'
    }
    return statusMap[status] || '未知'
  }

  /**
   * Format last ping time
   */
  const formatLastPing = (timestamp) => {
    if (!timestamp) return '從未檢測'

    const date = new Date(timestamp)
    const now = new Date()
    const diff = now - date

    const minutes = Math.floor(diff / (1000 * 60))
    const hours = Math.floor(diff / (1000 * 60 * 60))
    const days = Math.floor(diff / (1000 * 60 * 60 * 24))

    if (minutes < 60) {
      return `${minutes}分鐘前`
    } else if (hours < 24) {
      return `${hours}小時前`
    } else if (days < 7) {
      return `${days}天前`
    } else {
      return date.toLocaleDateString('zh-TW', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }
  }

  /**
   * Pagination helpers
   */
  const totalPages = computed(() => Math.ceil(totalServers.value / perPage.value))

  const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
      currentPage.value = page
      fetchServers()
    }
  }

  const nextPage = () => {
    if (currentPage.value < totalPages.value) {
      currentPage.value += 1
      fetchServers()
    }
  }

  const prevPage = () => {
    if (currentPage.value > 1) {
      currentPage.value -= 1
      fetchServers()
    }
  }

  return {
    // Data
    servers,
    totalServers,
    currentPage,
    perPage,
    selectedServer,
    loading,
    error,

    // Modal states
    showCreateModal,
    showEditModal,
    showDetailModal,
    showApprovalModal,

    // Forms
    serverForm,
    approvalForm,

    // Methods
    fetchServers,
    getServerDetails,
    createServer,
    updateServer,
    deleteServer,
    approveServer,
    rejectServer,
    suspendServer,
    toggleServerFeature,
    testServerConnection,
    resetForm,
    openCreateModal,
    openEditModal,
    openDetailModal,
    openApprovalModal,

    // Formatters
    formatStatus,
    formatPingStatus,
    formatLastPing,
    getStatusColor,
    getPingStatusColor,

    // Pagination
    totalPages,
    goToPage,
    nextPage,
    prevPage
  }
}