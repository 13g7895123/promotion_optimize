<template>
  <div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
          伺服器管理
        </h2>
        <div class="flex items-center space-x-4">
          <!-- Search -->
          <div class="relative">
            <input
              v-model="searchQuery"
              @input="onSearch"
              type="text"
              placeholder="搜尋伺服器..."
              class="w-64 px-4 py-2 pl-10 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            />
            <MagnifyingGlassIcon class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" />
          </div>

          <!-- Status Filter -->
          <select
            v-model="selectedStatus"
            @change="onFilterChange"
            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          >
            <option value="">所有狀態</option>
            <option value="pending">待審核</option>
            <option value="approved">已核准</option>
            <option value="rejected">已拒絕</option>
            <option value="suspended">已暫停</option>
            <option value="inactive">未啟用</option>
          </select>

          <!-- Game Type Filter -->
          <select
            v-model="selectedGameType"
            @change="onFilterChange"
            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          >
            <option value="">所有遊戲</option>
            <option value="lineage">天堂</option>
            <option value="maplestory">楓之谷</option>
            <option value="ragnarok">仙境傳說</option>
            <option value="dragon-nest">龍之谷</option>
            <option value="minecraft">我的世界</option>
            <option value="other">其他</option>
          </select>

          <!-- Add Server Button -->
          <button
            @click="openCreateModal"
            class="flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors"
          >
            <PlusIcon class="w-4 h-4 mr-2" />
            新增伺服器
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto mb-4"></div>
        <p class="text-gray-600 dark:text-gray-300">載入伺服器資料中...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6">
        <div class="flex items-center">
          <ExclamationTriangleIcon class="w-5 h-5 text-red-600 dark:text-red-400 mr-2" />
          <span class="text-red-600 dark:text-red-400">{{ error }}</span>
          <button @click="fetchServers()" class="ml-4 text-sm text-red-600 hover:text-red-800 underline">
            重試
          </button>
        </div>
      </div>

      <!-- Stats Row -->
      <div v-else class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
          <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
            <div class="flex items-center">
              <ServerIcon class="w-8 h-8 text-blue-600 dark:text-blue-400" />
              <div class="ml-3">
                <p class="text-sm text-blue-600 dark:text-blue-400">總伺服器</p>
                <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ totalServers }}</p>
              </div>
            </div>
          </div>

          <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
            <div class="flex items-center">
              <CheckCircleIcon class="w-8 h-8 text-green-600 dark:text-green-400" />
              <div class="ml-3">
                <p class="text-sm text-green-600 dark:text-green-400">已核准</p>
                <p class="text-2xl font-bold text-green-900 dark:text-green-100">{{ approvedServersCount }}</p>
              </div>
            </div>
          </div>

          <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
            <div class="flex items-center">
              <ClockIcon class="w-8 h-8 text-yellow-600 dark:text-yellow-400" />
              <div class="ml-3">
                <p class="text-sm text-yellow-600 dark:text-yellow-400">待審核</p>
                <p class="text-2xl font-bold text-yellow-900 dark:text-yellow-100">{{ pendingServersCount }}</p>
              </div>
            </div>
          </div>

          <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
            <div class="flex items-center">
              <StarIcon class="w-8 h-8 text-purple-600 dark:text-purple-400" />
              <div class="ml-3">
                <p class="text-sm text-purple-600 dark:text-purple-400">精選</p>
                <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">{{ featuredServersCount }}</p>
              </div>
            </div>
          </div>

          <div class="bg-cyan-50 dark:bg-cyan-900/20 p-4 rounded-lg">
            <div class="flex items-center">
              <SignalIcon class="w-8 h-8 text-cyan-600 dark:text-cyan-400" />
              <div class="ml-3">
                <p class="text-sm text-cyan-600 dark:text-cyan-400">在線</p>
                <p class="text-2xl font-bold text-cyan-900 dark:text-cyan-100">{{ onlineServersCount }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Servers Table -->
        <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  伺服器
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  遊戲類型
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  擁有者
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  狀態
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  在線狀態
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  人數
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  創建時間
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  操作
                </th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="server in servers" :key="server.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <!-- Server Info -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="flex-shrink-0 h-12 w-12">
                      <div v-if="server.logo_image" class="h-12 w-12 rounded-lg overflow-hidden">
                        <img :src="server.logo_image" :alt="server.server_name" class="h-full w-full object-cover" />
                      </div>
                      <div v-else class="h-12 w-12 rounded-lg bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                        <ServerIcon class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                      </div>
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ server.server_name }}
                        <StarIcon v-if="server.is_featured" class="w-4 h-4 inline ml-1 text-yellow-500" />
                      </div>
                      <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ server.server_code }}
                      </div>
                    </div>
                  </div>
                </td>

                <!-- Game Type -->
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ formatGameType(server.game_type) }}
                  <div v-if="server.version" class="text-xs text-gray-500 dark:text-gray-400">
                    v{{ server.version }}
                  </div>
                </td>

                <!-- Owner -->
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ server.owner_name || server.owner_username }}
                </td>

                <!-- Status -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                    :class="getStatusColor(server.status)"
                  >
                    {{ formatStatus(server.status) }}
                  </span>
                </td>

                <!-- Ping Status -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                    :class="getPingStatusColor(server.ping_status)"
                  >
                    {{ formatPingStatus(server.ping_status) }}
                  </span>
                  <div v-if="server.last_ping_at" class="text-xs text-gray-500 dark:text-gray-400">
                    {{ formatLastPing(server.last_ping_at) }}
                  </div>
                </td>

                <!-- Player Count -->
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  <div class="flex items-center">
                    <UsersIcon class="w-4 h-4 mr-1 text-gray-400" />
                    {{ server.online_players || 0 }}
                    <span v-if="server.max_players" class="text-gray-500 dark:text-gray-400">
                      / {{ server.max_players }}
                    </span>
                  </div>
                </td>

                <!-- Created At -->
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ formatLastPing(server.created_at) }}
                </td>

                <!-- Actions -->
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <div class="flex items-center space-x-2">
                    <!-- View Details -->
                    <button
                      @click="openDetailModal(server)"
                      class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300 transition-colors duration-200"
                      title="查看詳情"
                    >
                      <EyeIcon class="w-4 h-4" />
                    </button>

                    <!-- Edit -->
                    <button
                      @click="openEditModal(server)"
                      class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 transition-colors duration-200"
                      title="編輯伺服器"
                    >
                      <PencilIcon class="w-4 h-4" />
                    </button>

                    <!-- Test Connection -->
                    <button
                      @click="handleTestConnection(server)"
                      class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300 transition-colors duration-200"
                      title="測試連線"
                    >
                      <SignalIcon class="w-4 h-4" />
                    </button>

                    <!-- Approval Actions (for pending servers) -->
                    <template v-if="server.status === 'pending'">
                      <button
                        @click="handleApproveServer(server)"
                        class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300 transition-colors duration-200"
                        title="核准伺服器"
                      >
                        <CheckIcon class="w-4 h-4" />
                      </button>
                      <button
                        @click="openApprovalModal(server, 'reject')"
                        class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition-colors duration-200"
                        title="拒絕伺服器"
                      >
                        <XMarkIcon class="w-4 h-4" />
                      </button>
                    </template>

                    <!-- Feature Toggle -->
                    <button
                      v-if="server.status === 'approved'"
                      @click="handleToggleFeature(server)"
                      :class="[
                        'transition-colors duration-200',
                        server.is_featured
                          ? 'text-yellow-600 dark:text-yellow-400 hover:text-yellow-900 dark:hover:text-yellow-300'
                          : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300'
                      ]"
                      :title="server.is_featured ? '取消精選' : '設為精選'"
                    >
                      <StarIcon class="w-4 h-4" />
                    </button>

                    <!-- More Actions -->
                    <div class="relative group">
                      <button class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300 transition-colors duration-200">
                        <EllipsisHorizontalIcon class="w-4 h-4" />
                      </button>

                      <!-- Dropdown Menu -->
                      <div class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-10">
                        <div class="py-1">
                          <button
                            v-if="server.status === 'approved'"
                            @click="openApprovalModal(server, 'suspend')"
                            class="block px-4 py-2 text-sm text-yellow-700 dark:text-yellow-400 hover:bg-gray-100 dark:hover:bg-gray-700 w-full text-left"
                          >
                            暫停伺服器
                          </button>
                          <button
                            @click="handleDeleteServer(server)"
                            class="block px-4 py-2 text-sm text-red-700 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700 w-full text-left"
                          >
                            刪除伺服器
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- No Servers Found -->
          <div v-if="servers.length === 0 && !loading" class="text-center py-12">
            <ServerIcon class="w-12 h-12 text-gray-400 mx-auto mb-4" />
            <p class="text-gray-500 dark:text-gray-400">找不到伺服器資料</p>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="flex items-center justify-between mt-6">
          <div class="text-sm text-gray-700 dark:text-gray-300">
            顯示第 {{ (currentPage - 1) * perPage + 1 }} 到 {{ Math.min(currentPage * perPage, totalServers) }} 項，共 {{ totalServers }} 項
          </div>

          <div class="flex items-center space-x-2">
            <button
              @click="prevPage()"
              :disabled="currentPage === 1"
              class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              <ChevronLeftIcon class="w-4 h-4" />
            </button>

            <span class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300">
              第 {{ currentPage }} / {{ totalPages }} 頁
            </span>

            <button
              @click="nextPage()"
              :disabled="currentPage === totalPages"
              class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              <ChevronRightIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  MagnifyingGlassIcon,
  ServerIcon,
  PlusIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  ClockIcon,
  StarIcon,
  SignalIcon,
  UsersIcon,
  EyeIcon,
  PencilIcon,
  CheckIcon,
  XMarkIcon,
  EllipsisHorizontalIcon,
  ChevronLeftIcon,
  ChevronRightIcon
} from '@heroicons/vue/24/outline'

// Use the server management composable
const {
  servers,
  totalServers,
  currentPage,
  perPage,
  loading,
  error,
  fetchServers,
  approveServer,
  testServerConnection,
  toggleServerFeature,
  deleteServer,
  openCreateModal,
  openEditModal,
  openDetailModal,
  openApprovalModal,
  formatStatus,
  formatPingStatus,
  formatLastPing,
  getStatusColor,
  getPingStatusColor,
  totalPages,
  nextPage,
  prevPage
} = useServerManagement()

// Search and filter states
const searchQuery = ref('')
const selectedStatus = ref('')
const selectedGameType = ref('')

// Debounced search
let searchTimeout = null
const onSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchServers({
      search: searchQuery.value,
      status: selectedStatus.value,
      game_type: selectedGameType.value
    })
  }, 300)
}

// Filter change handler
const onFilterChange = () => {
  fetchServers({
    search: searchQuery.value,
    status: selectedStatus.value,
    game_type: selectedGameType.value
  })
}

// Server action handlers
const handleApproveServer = async (server) => {
  if (confirm(`確定要核准伺服器 "${server.server_name}" 嗎？`)) {
    const result = await approveServer(server.id)
    if (result.success) {
      console.log('Server approved successfully')
    } else {
      console.error('Failed to approve server:', result.error)
    }
  }
}

const handleTestConnection = async (server) => {
  const result = await testServerConnection(server.id)
  if (result.success) {
    console.log('Connection test completed:', result.data)
  } else {
    console.error('Connection test failed:', result.error)
  }
}

const handleToggleFeature = async (server) => {
  const result = await toggleServerFeature(server.id)
  if (result.success) {
    console.log('Server feature status updated')
  } else {
    console.error('Failed to toggle feature:', result.error)
  }
}

const handleDeleteServer = async (server) => {
  if (confirm(`確定要刪除伺服器 "${server.server_name}" 嗎？此操作無法撤銷。`)) {
    const result = await deleteServer(server.id)
    if (result.success) {
      console.log('Server deleted successfully')
    } else {
      console.error('Failed to delete server:', result.error)
    }
  }
}

// Format game type display
const formatGameType = (gameType) => {
  const gameTypeMap = {
    'lineage': '天堂',
    'maplestory': '楓之谷',
    'ragnarok': '仙境傳說',
    'dragon-nest': '龍之谷',
    'minecraft': '我的世界',
    'other': '其他'
  }
  return gameTypeMap[gameType] || gameType
}

// Computed stats
const approvedServersCount = computed(() => {
  return servers.value.filter(server => server.status === 'approved').length
})

const pendingServersCount = computed(() => {
  return servers.value.filter(server => server.status === 'pending').length
})

const featuredServersCount = computed(() => {
  return servers.value.filter(server => server.is_featured).length
})

const onlineServersCount = computed(() => {
  return servers.value.filter(server => server.ping_status === 'online').length
})

// Initialize data
onMounted(() => {
  fetchServers()
})
</script>