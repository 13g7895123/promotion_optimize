<template>
  <div
    v-if="showDetailModal"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    @click.self="closeModal"
  >
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          推廣活動詳情
        </h3>
        <button
          @click="closeModal"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <div v-if="selectedPromotion" class="space-y-6">
        <!-- Basic Information -->
        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-6">
          <div class="flex items-center justify-between mb-4">
            <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
              基本資訊
            </h4>
            <div class="flex items-center space-x-2">
              <span
                class="inline-flex px-3 py-1 text-sm font-semibold rounded-full"
                :class="getStatusColor(selectedPromotion.status)"
              >
                {{ formatStatus(selectedPromotion.status) }}
              </span>
              <div v-if="isPromotionExpired(selectedPromotion)" class="text-red-500" title="已過期">
                <ClockIcon class="w-5 h-5" />
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">推廣標題</label>
                <p class="text-gray-900 dark:text-white font-medium">{{ selectedPromotion.title }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">推廣類型</label>
                <div class="flex items-center mt-1">
                  <div
                    class="w-8 h-8 rounded-lg flex items-center justify-center mr-2"
                    :class="getPromotionTypeColor(selectedPromotion.promotion_type)"
                  >
                    <component :is="getPromotionTypeIcon(selectedPromotion.promotion_type)" class="w-4 h-4" />
                  </div>
                  <span class="text-gray-900 dark:text-white">{{ formatPromotionType(selectedPromotion.promotion_type) }}</span>
                </div>
              </div>

              <div v-if="selectedPromotion.description">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">推廣描述</label>
                <p class="text-gray-900 dark:text-white">{{ selectedPromotion.description }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">建立時間</label>
                <p class="text-gray-900 dark:text-white">{{ formatDateTime(selectedPromotion.created_at) }}</p>
              </div>
            </div>

            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">獎勵類型</label>
                <p class="text-gray-900 dark:text-white">{{ formatRewardType(selectedPromotion.reward_type) }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">獎勵數量</label>
                <p class="text-gray-900 dark:text-white font-medium">{{ selectedPromotion.reward_amount }}</p>
              </div>

              <div v-if="selectedPromotion.reward_description">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">獎勵說明</label>
                <p class="text-gray-900 dark:text-white">{{ selectedPromotion.reward_description }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">最後更新</label>
                <p class="text-gray-900 dark:text-white">{{ formatDateTime(selectedPromotion.updated_at) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Usage Limits -->
        <div v-if="selectedPromotion.max_uses || selectedPromotion.max_uses_per_user" class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-6">
          <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">使用限制</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-if="selectedPromotion.max_uses">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">最大使用次數</label>
              <p class="text-gray-900 dark:text-white">{{ selectedPromotion.max_uses }}</p>
            </div>
            <div v-if="selectedPromotion.max_uses_per_user">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">每用戶最大使用次數</label>
              <p class="text-gray-900 dark:text-white">{{ selectedPromotion.max_uses_per_user }}</p>
            </div>
          </div>
        </div>

        <!-- Time Period -->
        <div v-if="selectedPromotion.start_date || selectedPromotion.end_date" class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
          <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">活動時間</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-if="selectedPromotion.start_date">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">開始時間</label>
              <div class="flex items-center">
                <CalendarIcon class="w-4 h-4 mr-2 text-gray-500 dark:text-gray-400" />
                <p class="text-gray-900 dark:text-white">{{ formatDateTime(selectedPromotion.start_date) }}</p>
              </div>
            </div>
            <div v-if="selectedPromotion.end_date">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">結束時間</label>
              <div class="flex items-center">
                <CalendarIcon class="w-4 h-4 mr-2 text-gray-500 dark:text-gray-400" />
                <p class="text-gray-900 dark:text-white">{{ formatDateTime(selectedPromotion.end_date) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Statistics -->
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-6">
          <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">統計數據</h4>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="text-center">
              <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                {{ formatNumber(selectedPromotion.click_count || 0) }}
              </div>
              <div class="text-sm text-gray-500 dark:text-gray-400">總點擊數</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                {{ formatNumber(selectedPromotion.conversion_count || 0) }}
              </div>
              <div class="text-sm text-gray-500 dark:text-gray-400">轉換數</div>
            </div>
            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400 text-center">
              {{ conversionRate }}%
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400 text-center">轉換率</div>
            <div class="text-center">
              <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                {{ formatNumber(selectedPromotion.reward_distributed || 0) }}
              </div>
              <div class="text-sm text-gray-500 dark:text-gray-400">已發放獎勵</div>
            </div>
          </div>
        </div>

        <!-- Promotion Links -->
        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-6">
          <div class="flex items-center justify-between mb-4">
            <h4 class="text-lg font-semibold text-gray-900 dark:text-white">推廣連結</h4>
            <button
              @click="copyPromotionLink"
              class="flex items-center px-3 py-1 bg-purple-600 hover:bg-purple-700 text-white text-sm rounded-lg transition-colors"
            >
              <DocumentDuplicateIcon class="w-4 h-4 mr-1" />
              複製連結
            </button>
          </div>
          <div class="bg-white dark:bg-gray-800 rounded-lg p-3 font-mono text-sm break-all">
            https://promotion.mercylife.cc/p/{{ selectedPromotion.id }}?ref={{ selectedPromotion.reference_code || 'ABC123' }}
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end space-x-4 pt-6 border-t dark:border-gray-700">
          <button
            @click="openStatsModal(selectedPromotion)"
            class="flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
          >
            <ChartBarIcon class="w-4 h-4 mr-2" />
            查看詳細統計
          </button>

          <button
            v-if="canAccess('promotions.update')"
            @click="openEditModal(selectedPromotion)"
            class="flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors"
          >
            <PencilIcon class="w-4 h-4 mr-2" />
            編輯推廣
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-else class="text-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto mb-4"></div>
        <p class="text-gray-600 dark:text-gray-300">載入推廣詳情中...</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  XMarkIcon,
  ClockIcon,
  CalendarIcon,
  ChartBarIcon,
  PencilIcon,
  DocumentDuplicateIcon,
  UserPlusIcon,
  CursorArrowRaysIcon,
  UserIcon,
  GiftIcon,
  MegaphoneIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  showDetailModal: Boolean
})

const emit = defineEmits(['close', 'openStats', 'openEdit'])

const {
  selectedPromotion,
  formatPromotionType,
  formatRewardType,
  formatStatus,
  getStatusColor,
  isPromotionExpired
} = usePromotionManagement()

const { canAccess } = usePermissions()

const closeModal = () => {
  emit('close')
}

const openStatsModal = (promotion) => {
  emit('openStats', promotion)
}

const openEditModal = (promotion) => {
  emit('openEdit', promotion)
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

const formatDateTime = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleString('zh-TW')
}

const getPromotionTypeIcon = (type) => {
  switch (type) {
    case 'referral': return UserPlusIcon
    case 'click': return CursorArrowRaysIcon
    case 'signup': return UserIcon
    case 'activity': return GiftIcon
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

const conversionRate = computed(() => {
  if (!selectedPromotion.value || !selectedPromotion.value.click_count) return 0
  return ((selectedPromotion.value.conversion_count || 0) / selectedPromotion.value.click_count * 100).toFixed(1)
})

const copyPromotionLink = () => {
  const link = `https://promotion.mercylife.cc/p/${selectedPromotion.value.id}?ref=${selectedPromotion.value.reference_code || 'ABC123'}`
  navigator.clipboard.writeText(link).then(() => {
    console.log('Promotion link copied to clipboard')
  }).catch(err => {
    console.error('Failed to copy promotion link:', err)
  })
}
</script>