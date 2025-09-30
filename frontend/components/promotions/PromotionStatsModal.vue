<template>
  <div
    v-if="showStatsModal"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    @click.self="closeModal"
  >
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-6xl max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          推廣統計分析
        </h3>
        <button
          @click="closeModal"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <div v-if="selectedPromotion" class="space-y-6">
        <!-- Promotion Header -->
        <div class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-lg p-6">
          <div class="flex items-center justify-between">
            <div>
              <h4 class="text-xl font-bold text-gray-900 dark:text-white">{{ selectedPromotion.title }}</h4>
              <p class="text-gray-600 dark:text-gray-400">{{ formatPromotionType(selectedPromotion.promotion_type) }}</p>
            </div>
            <span
              class="inline-flex px-3 py-1 text-sm font-semibold rounded-full"
              :class="getStatusColor(selectedPromotion.status)"
            >
              {{ formatStatus(selectedPromotion.status) }}
            </span>
          </div>
        </div>

        <!-- Period Selector -->
        <div class="flex items-center space-x-4">
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300">統計期間：</label>
          <select
            v-model="selectedPeriod"
            @change="onPeriodChange"
            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          >
            <option value="7days">過去 7 天</option>
            <option value="30days">過去 30 天</option>
            <option value="90days">過去 90 天</option>
            <option value="all">全部時間</option>
          </select>
          <button
            @click="refreshStats"
            class="flex items-center px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors"
          >
            <ArrowPathIcon class="w-4 h-4 mr-1" />
            刷新
          </button>
        </div>

        <!-- Key Metrics -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
          <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
            <div class="flex items-center">
              <CursorArrowRaysIcon class="w-8 h-8 text-blue-600 dark:text-blue-400" />
              <div class="ml-3">
                <p class="text-sm text-blue-600 dark:text-blue-400">總點擊數</p>
                <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                  {{ formatNumber(promotionStats.total_clicks || 0) }}
                </p>
              </div>
            </div>
          </div>

          <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
            <div class="flex items-center">
              <UserPlusIcon class="w-8 h-8 text-green-600 dark:text-green-400" />
              <div class="ml-3">
                <p class="text-sm text-green-600 dark:text-green-400">轉換數</p>
                <p class="text-2xl font-bold text-green-900 dark:text-green-100">
                  {{ formatNumber(promotionStats.total_conversions || 0) }}
                </p>
              </div>
            </div>
          </div>

          <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
            <div class="flex items-center">
              <ChartBarIcon class="w-8 h-8 text-purple-600 dark:text-purple-400" />
              <div class="ml-3">
                <p class="text-sm text-purple-600 dark:text-purple-400">轉換率</p>
                <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">
                  {{ conversionRate }}%
                </p>
              </div>
            </div>
          </div>

          <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
            <div class="flex items-center">
              <GiftIcon class="w-8 h-8 text-yellow-600 dark:text-yellow-400" />
              <div class="ml-3">
                <p class="text-sm text-yellow-600 dark:text-yellow-400">已發放獎勵</p>
                <p class="text-2xl font-bold text-yellow-900 dark:text-yellow-100">
                  {{ formatNumber(promotionStats.total_rewards || 0) }}
                </p>
              </div>
            </div>
          </div>

          <div class="bg-cyan-50 dark:bg-cyan-900/20 p-4 rounded-lg">
            <div class="flex items-center">
              <UsersIcon class="w-8 h-8 text-cyan-600 dark:text-cyan-400" />
              <div class="ml-3">
                <p class="text-sm text-cyan-600 dark:text-cyan-400">參與用戶</p>
                <p class="text-2xl font-bold text-cyan-900 dark:text-cyan-100">
                  {{ formatNumber(promotionStats.unique_users || 0) }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Click Trend Chart -->
          <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <h5 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">點擊趨勢</h5>
            <div class="h-64">
              <Line :data="clickTrendData" :options="chartOptions" />
            </div>
          </div>

          <!-- Conversion Funnel -->
          <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <h5 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">轉換漏斗</h5>
            <div class="space-y-3">
              <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <span class="text-blue-800 dark:text-blue-200">點擊訪問</span>
                <span class="font-bold text-blue-900 dark:text-blue-100">{{ formatNumber(promotionStats.total_clicks || 0) }}</span>
              </div>
              <div class="flex items-center justify-between p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                <span class="text-yellow-800 dark:text-yellow-200">進入註冊頁</span>
                <span class="font-bold text-yellow-900 dark:text-yellow-100">{{ formatNumber(Math.floor((promotionStats.total_clicks || 0) * 0.7)) }}</span>
              </div>
              <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <span class="text-green-800 dark:text-green-200">完成註冊</span>
                <span class="font-bold text-green-900 dark:text-green-100">{{ formatNumber(promotionStats.total_conversions || 0) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
          <div class="flex items-center justify-between mb-4">
            <h5 class="text-lg font-semibold text-gray-900 dark:text-white">最近活動</h5>
            <button
              @click="exportRecentClicks"
              class="flex items-center px-3 py-1 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm rounded-lg transition-colors"
            >
              <ArrowDownTrayIcon class="w-4 h-4 mr-1" />
              匯出資料
            </button>
          </div>

          <div v-if="recentClicks.length > 0" class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b dark:border-gray-700">
                  <th class="text-left py-2 text-gray-600 dark:text-gray-400">時間</th>
                  <th class="text-left py-2 text-gray-600 dark:text-gray-400">用戶 IP</th>
                  <th class="text-left py-2 text-gray-600 dark:text-gray-400">來源</th>
                  <th class="text-left py-2 text-gray-600 dark:text-gray-400">狀態</th>
                  <th class="text-left py-2 text-gray-600 dark:text-gray-400">轉換</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="click in recentClicks.slice(0, 10)"
                  :key="click.id"
                  class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50"
                >
                  <td class="py-2 text-gray-900 dark:text-white">
                    {{ formatDateTime(click.created_at) }}
                  </td>
                  <td class="py-2 text-gray-600 dark:text-gray-400">
                    {{ click.ip_address || '未知' }}
                  </td>
                  <td class="py-2 text-gray-600 dark:text-gray-400">
                    {{ click.referrer || '直接訪問' }}
                  </td>
                  <td class="py-2">
                    <span
                      class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                      :class="click.status === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'"
                    >
                      {{ click.status === 'success' ? '成功' : '失敗' }}
                    </span>
                  </td>
                  <td class="py-2">
                    <span
                      v-if="click.converted"
                      class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400"
                    >
                      已轉換
                    </span>
                    <span v-else class="text-gray-400 dark:text-gray-500">-</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
            尚無活動記錄
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-else class="text-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto mb-4"></div>
        <p class="text-gray-600 dark:text-gray-300">載入統計資料中...</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  XMarkIcon,
  CursorArrowRaysIcon,
  UserPlusIcon,
  ChartBarIcon,
  GiftIcon,
  UsersIcon,
  ArrowPathIcon,
  ArrowDownTrayIcon
} from '@heroicons/vue/24/outline'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend)

const props = defineProps({
  showStatsModal: Boolean
})

const emit = defineEmits(['close'])

const {
  selectedPromotion,
  promotionStats,
  promotionClicks,
  getPromotionStats,
  getPromotionClicks,
  formatPromotionType,
  formatStatus,
  getStatusColor
} = usePromotionManagement()

// Reactive data
const selectedPeriod = ref('30days')
const recentClicks = ref([])

// Chart data
const clickTrendData = computed(() => ({
  labels: ['過去7天', '過去6天', '過去5天', '過去4天', '過去3天', '過去2天', '昨天', '今天'],
  datasets: [
    {
      label: '點擊數',
      data: [45, 67, 89, 123, 98, 156, 134, 89],
      borderColor: 'rgb(59, 130, 246)',
      backgroundColor: 'rgba(59, 130, 246, 0.1)',
      tension: 0.4
    },
    {
      label: '轉換數',
      data: [5, 8, 12, 15, 11, 18, 16, 12],
      borderColor: 'rgb(34, 197, 94)',
      backgroundColor: 'rgba(34, 197, 94, 0.1)',
      tension: 0.4
    }
  ]
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top'
    }
  },
  scales: {
    y: {
      beginAtZero: true
    }
  }
}

// Computed values
const conversionRate = computed(() => {
  const clicks = promotionStats.value?.total_clicks || 0
  const conversions = promotionStats.value?.total_conversions || 0
  if (clicks === 0) return 0
  return ((conversions / clicks) * 100).toFixed(1)
})

// Methods
const closeModal = () => {
  emit('close')
}

const onPeriodChange = () => {
  refreshStats()
}

const refreshStats = async () => {
  if (!selectedPromotion.value) return

  try {
    await getPromotionStats(selectedPromotion.value.id, selectedPeriod.value)
    const clicks = await getPromotionClicks(selectedPromotion.value.id, {
      period: selectedPeriod.value,
      limit: 50
    })
    recentClicks.value = clicks || []
  } catch (error) {
    console.error('Failed to refresh stats:', error)
  }
}

const exportRecentClicks = () => {
  // Mock export functionality
  console.log('Exporting recent clicks data...')
  const csvContent = recentClicks.value.map(click =>
    `${click.created_at},${click.ip_address},${click.referrer},${click.status},${click.converted}`
  ).join('\n')

  const blob = new Blob([`時間,IP地址,來源,狀態,轉換\n${csvContent}`], { type: 'text/csv' })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `promotion_${selectedPromotion.value.id}_clicks.csv`
  a.click()
  window.URL.revokeObjectURL(url)
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

// Initialize stats when modal opens
watch(() => props.showStatsModal, (newVal) => {
  if (newVal && selectedPromotion.value) {
    refreshStats()
  }
})
</script>