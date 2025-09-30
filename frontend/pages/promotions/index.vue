<template>
  <div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
          推廣管理系統
        </h2>
        <div class="flex items-center space-x-4">
          <!-- Search -->
          <div class="relative">
            <input
              v-model="searchQuery"
              @input="onSearch"
              type="text"
              placeholder="搜尋推廣活動..."
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
            <option value="active">啟用中</option>
            <option value="inactive">已停用</option>
            <option value="expired">已過期</option>
            <option value="draft">草稿</option>
            <option value="pending">待審核</option>
          </select>

          <!-- Type Filter -->
          <select
            v-model="selectedType"
            @change="onFilterChange"
            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          >
            <option value="">所有類型</option>
            <option value="referral">推薦獎勵</option>
            <option value="click">點擊獎勵</option>
            <option value="signup">註冊獎勵</option>
            <option value="activity">活動獎勵</option>
            <option value="milestone">里程碑獎勵</option>
          </select>

          <!-- Action Buttons -->
          <button
            v-if="canAccess('promotions.create')"
            @click="openLinkGeneratorModal"
            class="flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors"
          >
            <LinkIcon class="w-4 h-4 mr-2" />
            生成連結
          </button>

          <button
            v-if="canAccess('promotions.create')"
            @click="openCreateModal"
            class="flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors"
          >
            <PlusIcon class="w-4 h-4 mr-2" />
            新增推廣
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto mb-4"></div>
        <p class="text-gray-600 dark:text-gray-300">載入推廣資料中...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6">
        <div class="flex items-center">
          <ExclamationTriangleIcon class="w-5 h-5 text-red-600 dark:text-red-400 mr-2" />
          <span class="text-red-600 dark:text-red-400">{{ error }}</span>
          <button @click="fetchPromotions()" class="ml-4 text-sm text-red-600 hover:text-red-800 underline">
            重試
          </button>
        </div>
      </div>

      <!-- Stats Row -->
      <div v-else class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
          <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
            <div class="flex items-center">
              <MegaphoneIcon class="w-8 h-8 text-blue-600 dark:text-blue-400" />
              <div class="ml-3">
                <p class="text-sm text-blue-600 dark:text-blue-400">總推廣數</p>
                <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ totalPromotions }}</p>
              </div>
            </div>
          </div>

          <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
            <div class="flex items-center">
              <CheckCircleIcon class="w-8 h-8 text-green-600 dark:text-green-400" />
              <div class="ml-3">
                <p class="text-sm text-green-600 dark:text-green-400">啟用中</p>
                <p class="text-2xl font-bold text-green-900 dark:text-green-100">{{ activePromotionsCount }}</p>
              </div>
            </div>
          </div>

          <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
            <div class="flex items-center">
              <CursorArrowRaysIcon class="w-8 h-8 text-purple-600 dark:text-purple-400" />
              <div class="ml-3">
                <p class="text-sm text-purple-600 dark:text-purple-400">總點擊數</p>
                <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">{{ formatNumber(totalClicks) }}</p>
              </div>
            </div>
          </div>

          <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
            <div class="flex items-center">
              <UserPlusIcon class="w-8 h-8 text-yellow-600 dark:text-yellow-400" />
              <div class="ml-3">
                <p class="text-sm text-yellow-600 dark:text-yellow-400">轉換數</p>
                <p class="text-2xl font-bold text-yellow-900 dark:text-yellow-100">{{ formatNumber(totalConversions) }}</p>
              </div>
            </div>
          </div>

          <div class="bg-cyan-50 dark:bg-cyan-900/20 p-4 rounded-lg">
            <div class="flex items-center">
              <ChartBarIcon class="w-8 h-8 text-cyan-600 dark:text-cyan-400" />
              <div class="ml-3">
                <p class="text-sm text-cyan-600 dark:text-cyan-400">轉換率</p>
                <p class="text-2xl font-bold text-cyan-900 dark:text-cyan-100">{{ conversionRate }}%</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Promotions Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
          <div
            v-for="promotion in promotions"
            :key="promotion.id"
            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 hover:shadow-md transition-all duration-200"
          >
            <!-- Promotion Header -->
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center">
                <div
                  class="w-12 h-12 rounded-lg flex items-center justify-center"
                  :class="getPromotionTypeColor(promotion.promotion_type)"
                >
                  <component :is="getPromotionTypeIcon(promotion.promotion_type)" class="w-6 h-6" />
                </div>
                <div class="ml-3">
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ promotion.title }}
                  </h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ formatPromotionType(promotion.promotion_type) }}
                  </p>
                </div>
              </div>

              <!-- Status Badge -->
              <div class="flex items-center space-x-2">
                <span
                  class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                  :class="getStatusColor(promotion.status)"
                >
                  {{ formatStatus(promotion.status) }}
                </span>
                <div v-if="isPromotionExpired(promotion)" class="text-red-500" title="已過期">
                  <ClockIcon class="w-4 h-4" />
                </div>
              </div>
            </div>

            <!-- Promotion Description -->
            <p v-if="promotion.description" class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
              {{ promotion.description }}
            </p>

            <!-- Reward Info -->
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 mb-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-xs text-gray-500 dark:text-gray-400">獎勵類型</p>
                  <p class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ formatRewardType(promotion.reward_type) }}
                  </p>
                </div>
                <div class="text-right">
                  <p class="text-xs text-gray-500 dark:text-gray-400">獎勵數量</p>
                  <p class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ promotion.reward_amount }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-3 gap-4 mb-4 text-center">
              <div>
                <div class="text-lg font-bold text-blue-600 dark:text-blue-400">
                  {{ formatNumber(promotion.click_count || 0) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">點擊數</div>
              </div>
              <div>
                <div class="text-lg font-bold text-green-600 dark:text-green-400">
                  {{ formatNumber(promotion.conversion_count || 0) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">轉換數</div>
              </div>
              <div>
                <div class="text-lg font-bold text-purple-600 dark:text-purple-400">
                  {{ formatNumber(promotion.reward_distributed || 0) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">已發放</div>
              </div>
            </div>

            <!-- Date Info -->
            <div v-if="promotion.start_date || promotion.end_date" class="text-xs text-gray-500 dark:text-gray-400 mb-4">
              <div v-if="promotion.start_date" class="flex items-center">
                <CalendarIcon class="w-3 h-3 mr-1" />
                開始：{{ formatDate(promotion.start_date) }}
              </div>
              <div v-if="promotion.end_date" class="flex items-center">
                <CalendarIcon class="w-3 h-3 mr-1" />
                結束：{{ formatDate(promotion.end_date) }}
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between pt-4 border-t dark:border-gray-700">
              <div class="flex items-center space-x-2">
                <!-- View Details -->
                <button
                  @click="openDetailModal(promotion)"
                  class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300 transition-colors"
                  title="查看詳情"
                >
                  <EyeIcon class="w-4 h-4" />
                </button>

                <!-- View Stats -->
                <button
                  @click="openStatsModal(promotion)"
                  class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 transition-colors"
                  title="查看統計"
                >
                  <ChartBarIcon class="w-4 h-4" />
                </button>

                <!-- Edit -->
                <button
                  v-if="canAccess('promotions.update')"
                  @click="openEditModal(promotion)"
                  class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300 transition-colors"
                  title="編輯推廣"
                >
                  <PencilIcon class="w-4 h-4" />
                </button>

                <!-- QR Code -->
                <button
                  @click="handleGenerateQR(promotion)"
                  class="text-purple-600 dark:text-purple-400 hover:text-purple-900 dark:hover:text-purple-300 transition-colors"
                  title="生成QR碼"
                >
                  <QrCodeIcon class="w-4 h-4" />
                </button>
              </div>

              <div class="flex items-center space-x-2">
                <!-- Toggle Status -->
                <button
                  v-if="canAccess('promotions.update')"
                  @click="handleToggleStatus(promotion)"
                  :class="[
                    'transition-colors',
                    promotion.status === 'active'
                      ? 'text-yellow-600 dark:text-yellow-400 hover:text-yellow-900 dark:hover:text-yellow-300'
                      : 'text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300'
                  ]"
                  :title="promotion.status === 'active' ? '停用推廣' : '啟用推廣'"
                >
                  <component :is="promotion.status === 'active' ? 'StopIcon' : 'PlayIcon'" class="w-4 h-4" />
                </button>

                <!-- Delete -->
                <button
                  v-if="canAccess('promotions.delete')"
                  @click="handleDeletePromotion(promotion)"
                  class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition-colors"
                  title="刪除推廣"
                >
                  <TrashIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- No Promotions Found -->
        <div v-if="promotions.length === 0 && !loading" class="text-center py-12">
          <MegaphoneIcon class="w-12 h-12 text-gray-400 mx-auto mb-4" />
          <p class="text-gray-500 dark:text-gray-400">尚未建立任何推廣活動</p>
          <button
            v-if="canAccess('promotions.create')"
            @click="openCreateModal"
            class="mt-4 inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors"
          >
            <PlusIcon class="w-4 h-4 mr-2" />
            建立第一個推廣
          </button>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="flex items-center justify-between mt-6">
          <div class="text-sm text-gray-700 dark:text-gray-300">
            顯示第 {{ (currentPage - 1) * perPage + 1 }} 到 {{ Math.min(currentPage * perPage, totalPromotions) }} 項，共 {{ totalPromotions }} 項
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

    <!-- Modal Components -->
    <CreatePromotionModal
      :show-create-modal="showCreateModal"
      @close="showCreateModal = false"
      @created="onPromotionCreated"
    />

    <EditPromotionModal
      :show-edit-modal="showEditModal"
      @close="showEditModal = false"
      @updated="onPromotionUpdated"
    />

    <PromotionDetailModal
      :show-detail-modal="showDetailModal"
      @close="showDetailModal = false"
      @open-stats="onOpenStatsFromDetail"
      @open-edit="onOpenEditFromDetail"
    />

    <PromotionStatsModal
      :show-stats-modal="showStatsModal"
      @close="showStatsModal = false"
    />

    <LinkGeneratorModal
      :show-link-generator-modal="showLinkGeneratorModal"
      @close="showLinkGeneratorModal = false"
      @generated="onLinkGenerated"
    />
  </div>
</template>

<script setup>
import {
  MagnifyingGlassIcon,
  MegaphoneIcon,
  LinkIcon,
  PlusIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  CursorArrowRaysIcon,
  UserPlusIcon,
  ChartBarIcon,
  ClockIcon,
  CalendarIcon,
  EyeIcon,
  PencilIcon,
  QrCodeIcon,
  StopIcon,
  PlayIcon,
  TrashIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  GiftIcon,
  UserIcon,
  ActivityIcon
} from '@heroicons/vue/24/outline'

// Import modal components
import CreatePromotionModal from '~/components/promotions/CreatePromotionModal.vue'
import EditPromotionModal from '~/components/promotions/EditPromotionModal.vue'
import PromotionDetailModal from '~/components/promotions/PromotionDetailModal.vue'
import PromotionStatsModal from '~/components/promotions/PromotionStatsModal.vue'
import LinkGeneratorModal from '~/components/promotions/LinkGeneratorModal.vue'

// Use the promotion management composable
const {
  promotions,
  totalPromotions,
  currentPage,
  perPage,
  loading,
  error,
  showCreateModal,
  showEditModal,
  showDetailModal,
  showStatsModal,
  showLinkGeneratorModal,
  fetchPromotions,
  togglePromotionStatus,
  deletePromotion,
  generateQRCode,
  openCreateModal,
  openEditModal,
  openDetailModal,
  openStatsModal,
  openLinkGeneratorModal,
  formatPromotionType,
  formatRewardType,
  formatStatus,
  getStatusColor,
  isPromotionExpired,
  isPromotionActive,
  totalPages,
  nextPage,
  prevPage
} = usePromotionManagement()

// Use permissions
const { canAccess } = usePermissions()

// Search and filter states
const searchQuery = ref('')
const selectedStatus = ref('')
const selectedType = ref('')

// Mock stats (in real app, these would come from API)
const totalClicks = ref(125420)
const totalConversions = ref(8932)
const conversionRate = computed(() => {
  if (totalClicks.value === 0) return 0
  return ((totalConversions.value / totalClicks.value) * 100).toFixed(1)
})

// Debounced search
let searchTimeout = null
const onSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchPromotions({
      search: searchQuery.value,
      status: selectedStatus.value,
      promotion_type: selectedType.value
    })
  }, 300)
}

// Filter change handler
const onFilterChange = () => {
  fetchPromotions({
    search: searchQuery.value,
    status: selectedStatus.value,
    promotion_type: selectedType.value
  })
}

// Promotion action handlers
const handleToggleStatus = async (promotion) => {
  const result = await togglePromotionStatus(promotion.id)
  if (result.success) {
    console.log('Promotion status updated successfully')
  } else {
    console.error('Failed to toggle promotion status:', result.error)
  }
}

const handleDeletePromotion = async (promotion) => {
  if (confirm(`確定要刪除推廣活動 "${promotion.title}" 嗎？此操作無法撤銷。`)) {
    const result = await deletePromotion(promotion.id)
    if (result.success) {
      console.log('Promotion deleted successfully')
    } else {
      console.error('Failed to delete promotion:', result.error)
    }
  }
}

const handleGenerateQR = async (promotion) => {
  const result = await generateQRCode(promotion.id)
  if (result.success) {
    // Show QR code in modal or download
    console.log('QR code generated:', result.qrCode)
  } else {
    console.error('Failed to generate QR code:', result.error)
  }
}

// Utility functions
const formatNumber = (num) => {
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + 'M'
  }
  if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K'
  }
  return num.toString()
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('zh-TW')
}

const getPromotionTypeIcon = (type) => {
  switch (type) {
    case 'referral': return UserPlusIcon
    case 'click': return CursorArrowRaysIcon
    case 'signup': return UserIcon
    case 'activity': return ActivityIcon
    case 'milestone': return GiftIcon
    default: return MegaphoneIcon
  }
}

const getPromotionTypeColor = (type) => {
  switch (type) {
    case 'referral': return 'bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400'
    case 'click': return 'bg-green-100 text-green-600 dark:bg-green-900/20 dark:text-green-400'
    case 'signup': return 'bg-purple-100 text-purple-600 dark:bg-purple-900/20 dark:text-purple-400'
    case 'activity': return 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/20 dark:text-yellow-400'
    case 'milestone': return 'bg-pink-100 text-pink-600 dark:bg-pink-900/20 dark:text-pink-400'
    default: return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
  }
}

// Computed stats
const activePromotionsCount = computed(() => {
  return promotions.value.filter(p => isPromotionActive(p)).length
})

// Modal event handlers
const onPromotionCreated = (promotion) => {
  console.log('Promotion created:', promotion)
  fetchPromotions() // Refresh the list
}

const onPromotionUpdated = (promotion) => {
  console.log('Promotion updated:', promotion)
  fetchPromotions() // Refresh the list
}

const onOpenStatsFromDetail = (promotion) => {
  showDetailModal.value = false
  openStatsModal(promotion)
}

const onOpenEditFromDetail = (promotion) => {
  showDetailModal.value = false
  openEditModal(promotion)
}

const onLinkGenerated = (linkData) => {
  console.log('Link generated:', linkData)
}

// Initialize data
onMounted(() => {
  fetchPromotions()
})
</script>