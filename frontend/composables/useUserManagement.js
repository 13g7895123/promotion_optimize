export const useUserManagement = () => {
  const api = useApi()
  const loading = ref(false)
  const error = ref(null)

  // User data
  const users = ref([])
  const totalUsers = ref(0)
  const currentPage = ref(1)
  const perPage = ref(20)

  // Selected user for operations
  const selectedUser = ref(null)

  // Modal states
  const showCreateModal = ref(false)
  const showEditModal = ref(false)
  const showDetailModal = ref(false)

  // Form data
  const userForm = ref({
    id: null,
    username: '',
    email: '',
    name: '',
    role: 'user',
    status: 'active',
    password: '',
    password_confirmation: ''
  })

  /**
   * Fetch users with pagination and filters
   */
  const fetchUsers = async (params = {}) => {
    try {
      loading.value = true
      error.value = null

      const queryParams = {
        page: currentPage.value,
        per_page: perPage.value,
        search: params.search || '',
        role: params.role || '',
        status: params.status || '',
        sort_by: params.sort_by || 'created_at',
        sort_order: params.sort_order || 'desc',
        ...params
      }

      const response = await api.getUsers(queryParams)

      if (response.status === 'success') {
        users.value = response.data.users || []
        totalUsers.value = response.data.total || 0
        currentPage.value = response.data.current_page || 1
      } else {
        throw new Error(response.message || 'Failed to fetch users')
      }
    } catch (err) {
      console.error('Failed to fetch users:', err)
      error.value = err.message || 'Failed to load users'
      users.value = []
    } finally {
      loading.value = false
    }
  }

  /**
   * Get user details
   */
  const getUserDetails = async (userId) => {
    try {
      loading.value = true
      const response = await api.getUserDetails(userId)

      if (response.status === 'success') {
        selectedUser.value = response.data.user
        return response.data.user
      } else {
        throw new Error(response.message || 'Failed to fetch user details')
      }
    } catch (err) {
      console.error('Failed to get user details:', err)
      error.value = err.message || 'Failed to load user details'
      return null
    } finally {
      loading.value = false
    }
  }

  /**
   * Create new user
   */
  const createUser = async (userData) => {
    try {
      loading.value = true
      error.value = null

      const response = await api.createUser(userData)

      if (response.status === 'success') {
        // Refresh users list
        await fetchUsers()
        resetForm()
        showCreateModal.value = false
        return { success: true, user: response.data.user }
      } else {
        throw new Error(response.message || 'Failed to create user')
      }
    } catch (err) {
      console.error('Failed to create user:', err)
      error.value = err.message || 'Failed to create user'
      return { success: false, error: err.message }
    } finally {
      loading.value = false
    }
  }

  /**
   * Update user
   */
  const updateUser = async (userId, userData) => {
    try {
      loading.value = true
      error.value = null

      const response = await api.updateUser(userId, userData)

      if (response.status === 'success') {
        // Update local user data
        const userIndex = users.value.findIndex(u => u.id === userId)
        if (userIndex !== -1) {
          users.value[userIndex] = { ...users.value[userIndex], ...response.data.user }
        }

        resetForm()
        showEditModal.value = false
        return { success: true, user: response.data.user }
      } else {
        throw new Error(response.message || 'Failed to update user')
      }
    } catch (err) {
      console.error('Failed to update user:', err)
      error.value = err.message || 'Failed to update user'
      return { success: false, error: err.message }
    } finally {
      loading.value = false
    }
  }

  /**
   * Delete user
   */
  const deleteUser = async (userId) => {
    try {
      loading.value = true
      error.value = null

      const response = await api.deleteUser(userId)

      if (response.status === 'success') {
        // Remove user from local data
        users.value = users.value.filter(u => u.id !== userId)
        totalUsers.value -= 1
        return { success: true }
      } else {
        throw new Error(response.message || 'Failed to delete user')
      }
    } catch (err) {
      console.error('Failed to delete user:', err)
      error.value = err.message || 'Failed to delete user'
      return { success: false, error: err.message }
    } finally {
      loading.value = false
    }
  }

  /**
   * Toggle user status (active/inactive)
   */
  const toggleUserStatus = async (userId) => {
    try {
      const user = users.value.find(u => u.id === userId)
      if (!user) return { success: false, error: 'User not found' }

      const newStatus = user.status === 'active' ? 'inactive' : 'active'
      const result = await updateUser(userId, { status: newStatus })

      return result
    } catch (err) {
      console.error('Failed to toggle user status:', err)
      return { success: false, error: err.message }
    }
  }

  /**
   * Reset form data
   */
  const resetForm = () => {
    userForm.value = {
      id: null,
      username: '',
      email: '',
      name: '',
      role: 'user',
      status: 'active',
      password: '',
      password_confirmation: ''
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
  const openEditModal = (user) => {
    userForm.value = {
      id: user.id,
      username: user.username || '',
      email: user.email || '',
      name: user.name || '',
      role: user.role || 'user',
      status: user.status || 'active',
      password: '',
      password_confirmation: ''
    }
    showEditModal.value = true
  }

  /**
   * Open user detail modal
   */
  const openDetailModal = async (user) => {
    selectedUser.value = user
    await getUserDetails(user.id)
    showDetailModal.value = true
  }

  /**
   * Format user role display
   */
  const formatRole = (role) => {
    const roleMap = {
      'super_admin': '超級管理員',
      'admin': '管理員',
      'server_owner': '服主',
      'moderator': '審核員',
      'user': '一般用戶'
    }
    return roleMap[role] || role
  }

  /**
   * Format user status display
   */
  const formatStatus = (status) => {
    const statusMap = {
      'active': '啟用',
      'inactive': '停用',
      'suspended': '暫停',
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
        return 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
      case 'suspended':
        return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400'
      case 'pending':
        return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
      default:
        return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
    }
  }

  /**
   * Get role color class
   */
  const getRoleColor = (role) => {
    switch (role) {
      case 'super_admin':
        return 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400'
      case 'admin':
        return 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400'
      case 'server_owner':
        return 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/20 dark:text-indigo-400'
      case 'moderator':
        return 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
      case 'user':
        return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
      default:
        return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
    }
  }

  /**
   * Format last login time
   */
  const formatLastLogin = (timestamp) => {
    if (!timestamp) return '從未登入'

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
  const totalPages = computed(() => Math.ceil(totalUsers.value / perPage.value))

  const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
      currentPage.value = page
      fetchUsers()
    }
  }

  const nextPage = () => {
    if (currentPage.value < totalPages.value) {
      currentPage.value += 1
      fetchUsers()
    }
  }

  const prevPage = () => {
    if (currentPage.value > 1) {
      currentPage.value -= 1
      fetchUsers()
    }
  }

  return {
    // Data
    users,
    totalUsers,
    currentPage,
    perPage,
    selectedUser,
    loading,
    error,

    // Modal states
    showCreateModal,
    showEditModal,
    showDetailModal,

    // Form
    userForm,

    // Methods
    fetchUsers,
    getUserDetails,
    createUser,
    updateUser,
    deleteUser,
    toggleUserStatus,
    resetForm,
    openCreateModal,
    openEditModal,
    openDetailModal,

    // Formatters
    formatRole,
    formatStatus,
    formatLastLogin,
    getStatusColor,
    getRoleColor,

    // Pagination
    totalPages,
    goToPage,
    nextPage,
    prevPage
  }
}