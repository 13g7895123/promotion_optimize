<template>
  <div
    v-if="showModal"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    @click.self="closeModal"
  >
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-6xl max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          推廣連結分析
        </h3>
        <button
          @click="closeModal"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <div v-if="promotion" class="space-y-6">
        <!-- Promotion Header -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-6">
          <div class="flex items-center justify-between">
            <div>
              <h4 class="text-xl font-bold text-gray-900 dark:text-white">{{ promotion.title }}</h4>
              <p class="text-gray-600 dark:text-gray-400">{{ formatPromotionType(promotion.promotion_type) }}</p>
            </div>
            <div class="flex items-center space-x-4">
              <!-- Date Range Selector -->
              <select
                v-model="selectedDateRange"
                @change="refreshAnalytics"
                class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              >
                <option value="7days">過去 7 天</option>
                <option value="30days">過去 30 天</option>
                <option value="90days">過去 90 天</option>
                <option value="all">全部時間</option>
              </select>
              <button
                @click="refreshAnalytics"
                class="flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
              >
                <ArrowPathIcon class="w-4 h-4 mr-1" />
                刷新
              </button>
            </div>
          </div>
        </div>

        <!-- Key Metrics -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
          <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
            <div class="flex items-center">
              <CursorArrowRaysIcon class="w-8 h-8 text-blue-600 dark:text-blue-400" />
              <div class="ml-3">
                <p class="text-sm text-blue-600 dark:text-blue-400">總點擊數</p>
                <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                  {{ formatNumber(analytics.totalClicks || 0) }}
                </p>
              </div>
            </div>
          </div>

          <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
            <div class="flex items-center">
              <UsersIcon class="w-8 h-8 text-green-600 dark:text-green-400" />
              <div class="ml-3">
                <p class="text-sm text-green-600 dark:text-green-400">唯一點擊</p>
                <p class="text-2xl font-bold text-green-900 dark:text-green-100">
                  {{ formatNumber(analytics.uniqueClicks || 0) }}
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
                  {{ analytics.conversionRate || 0 }}%
                </p>
              </div>
            </div>
          </div>

          <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
            <div class="flex items-center">
              <DevicePhoneMobileIcon class="w-8 h-8 text-yellow-600 dark:text-yellow-400" />
              <div class="ml-3">
                <p class="text-sm text-yellow-600 dark:text-yellow-400">行動裝置</p>
                <p class="text-2xl font-bold text-yellow-900 dark:text-yellow-100">
                  {{ analytics.deviceBreakdown?.mobile || 0 }}%
                </p>
              </div>
            </div>
          </div>

          <div class="bg-cyan-50 dark:bg-cyan-900/20 p-4 rounded-lg">
            <div class="flex items-center">
              <GlobeAltIcon class="w-8 h-8 text-cyan-600 dark:text-cyan-400" />
              <div class="ml-3">
                <p class="text-sm text-cyan-600 dark:text-cyan-400">來源數量</p>
                <p class="text-2xl font-bold text-cyan-900 dark:text-cyan-100">
                  {{ analytics.topSources?.length || 0 }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Click Trends Chart -->
          <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <h5 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">點擊趨勢</h5>
            <div class="h-64">
              <Line :data="clickTrendData" :options="chartOptions" />
            </div>
          </div>

          <!-- Device Breakdown Chart -->
          <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <h5 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">裝置分布</h5>
            <div class="h-64">
              <Doughnut :data="deviceBreakdownData" :options="doughnutOptions" />
            </div>
          </div>
        </div>

        <!-- Top Sources Table -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
          <div class="flex items-center justify-between mb-4">
            <h5 class="text-lg font-semibold text-gray-900 dark:text-white">熱門來源</h5>
            <button
              @click="exportSourcesData"
              class="flex items-center px-3 py-1 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm rounded-lg transition-colors"
            >
              <ArrowDownTrayIcon class="w-4 h-4 mr-1" />
              匯出
            </button>
          </div>

          <div v-if="analytics.topSources && analytics.topSources.length > 0" class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b dark:border-gray-700">
                  <th class="text-left py-2 text-gray-600 dark:text-gray-400">來源</th>
                  <th class="text-left py-2 text-gray-600 dark:text-gray-400">UTM Source</th>
                  <th class="text-left py-2 text-gray-600 dark:text-gray-400">UTM Medium</th>
                  <th class="text-right py-2 text-gray-600 dark:text-gray-400">點擊數</th>
                  <th class="text-right py-2 text-gray-600 dark:text-gray-400">轉換數</th>
                  <th class="text-right py-2 text-gray-600 dark:text-gray-400">轉換率</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="source in analytics.topSources"
                  :key="source.id"
                  class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50"
                >
                  <td class="py-2 text-gray-900 dark:text-white font-medium">
                    {{ source.source || '直接訪問' }}
                  </td>
                  <td class="py-2 text-gray-600 dark:text-gray-400">
                    {{ source.utm_source || '-' }}
                  </td>
                  <td class="py-2 text-gray-600 dark:text-gray-400">
                    {{ source.utm_medium || '-' }}
                  </td>
                  <td class="py-2 text-right text-gray-900 dark:text-white">
                    {{ formatNumber(source.clicks || 0) }}
                  </td>
                  <td class="py-2 text-right text-green-600 dark:text-green-400">
                    {{ formatNumber(source.conversions || 0) }}
                  </td>
                  <td class="py-2 text-right">
                    <span
                      class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                      :class="getConversionRateColor(source.conversion_rate || 0)"
                    >
                      {{ (source.conversion_rate || 0).toFixed(1) }}%
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
            尚無來源數據
          </div>
        </div>

        <!-- Link Management Section -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
          <div class="flex items-center justify-between mb-4">
            <h5 class="text-lg font-semibold text-gray-900 dark:text-white">推廣連結管理</h5>
            <div class="flex items-center space-x-2">
              <button
                @click="generateNewLink"
                class="flex items-center px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg transition-colors"
              >
                <PlusIcon class="w-4 h-4 mr-1" />
                新增連結
              </button>
              <button
                @click="showBulkLinkGenerator = true"
                class="flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors"
              >
                <LinkIcon class="w-4 h-4 mr-1" />
                批次生成
              </button>
            </div>
          </div>

          <!-- Generated Links List -->
          <div class="space-y-3">
            <div
              v-for="link in generatedLinks"
              :key="link.id"
              class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
            >
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                  {{ link.name || '未命名連結' }}
                </p>
                <p class="text-xs text-gray-600 dark:text-gray-400 font-mono truncate">
                  {{ link.url }}
                </p>
                <div class="flex items-center space-x-4 mt-1">
                  <span class="text-xs text-gray-500">點擊: {{ link.clicks || 0 }}</span>
                  <span class="text-xs text-gray-500">轉換: {{ link.conversions || 0 }}</span>
                  <span class="text-xs text-gray-500">建立: {{ formatDate(link.created_at) }}</span>
                </div>
              </div>

              <div class="flex items-center space-x-2 ml-4">
                <button
                  @click="copyLink(link.url)"
                  class="p-1 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300"
                  title="複製連結"
                >
                  <DocumentDuplicateIcon class="w-4 h-4" />
                </button>
                <button
                  @click="generateQRForLink(link)"
                  class="p-1 text-purple-600 dark:text-purple-400 hover:text-purple-900 dark:hover:text-purple-300"
                  title="生成 QR 碼"
                >
                  <QrCodeIcon class="w-4 h-4" />
                </button>
                <button
                  @click="deleteLink(link.id)"
                  class="p-1 text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                  title="刪除連結"
                >
                  <TrashIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-else class="text-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto mb-4"></div>
        <p class="text-gray-600 dark:text-gray-300">載入分析資料中...</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  XMarkIcon,
  CursorArrowRaysIcon,
  UsersIcon,
  ChartBarIcon,
  DevicePhoneMobileIcon,
  GlobeAltIcon,
  ArrowPathIcon,
  ArrowDownTrayIcon,
  PlusIcon,
  LinkIcon,
  DocumentDuplicateIcon,
  QrCodeIcon,
  TrashIcon
} from '@heroicons/vue/24/outline'
import { Line, Doughnut } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  ArcElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, ArcElement, Title, Tooltip, Legend)

const props = defineProps({
  showModal: Boolean,
  promotion: Object
})

const emit = defineEmits(['close', 'generateLink', 'generateQR'])

const {
  getPromotionLinkAnalytics,
  generateCustomPromotionLink,
  formatPromotionType
} = usePromotionManagement()

// Reactive data
const analytics = ref({})
const selectedDateRange = ref('30days')
const generatedLinks = ref([])
const showBulkLinkGenerator = ref(false)

// Chart data
const clickTrendData = computed(() => ({
  labels: analytics.value.clicksByDay?.map(d => d.date) || [],
  datasets: [
    {
      label: '點擊數',
      data: analytics.value.clicksByDay?.map(d => d.clicks) || [],
      borderColor: 'rgb(59, 130, 246)',
      backgroundColor: 'rgba(59, 130, 246, 0.1)',
      tension: 0.4
    }
  ]
}))

const deviceBreakdownData = computed(() => ({
  labels: ['桌面', '行動裝置', '平板'],
  datasets: [
    {
      data: [
        analytics.value.deviceBreakdown?.desktop || 0,
        analytics.value.deviceBreakdown?.mobile || 0,
        analytics.value.deviceBreakdown?.tablet || 0
      ],
      backgroundColor: [
        'rgba(59, 130, 246, 0.8)',
        'rgba(34, 197, 94, 0.8)',
        'rgba(168, 85, 247, 0.8)'
      ],
      borderColor: [
        'rgb(59, 130, 246)',
        'rgb(34, 197, 94)',
        'rgb(168, 85, 247)'
      ],
      borderWidth: 1
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
  }
}

const doughnutOptions = {
  ...chartOptions,
  plugins: {
    ...chartOptions.plugins,
    legend: {
      position: 'bottom'
    }
  }
}

// Methods
const closeModal = () => {
  emit('close')
}

const refreshAnalytics = async () => {
  if (!props.promotion) return

  try {
    const result = await getPromotionLinkAnalytics(props.promotion.id, selectedDateRange.value)
    if (result.success) {
      analytics.value = result.analytics
    }
  } catch (error) {
    console.error('Failed to refresh analytics:', error)
  }
}

const generateNewLink = () => {
  emit('generateLink', props.promotion)
}

const copyLink = async (url) => {
  try {
    await navigator.clipboard.writeText(url)
    console.log('Link copied to clipboard')
  } catch (err) {
    console.error('Failed to copy link:', err)
  }
}

const generateQRForLink = (link) => {
  emit('generateQR', { promotion: props.promotion, link })
}

const deleteLink = async (linkId) => {
  if (confirm('確定要刪除這個連結嗎？')) {
    generatedLinks.value = generatedLinks.value.filter(link => link.id !== linkId)
  }
}

const exportSourcesData = () => {
  if (!analytics.value.topSources) return

  const csvContent = analytics.value.topSources.map(source =>
    `${source.source || '直接訪問'},${source.utm_source || ''},${source.utm_medium || ''},${source.clicks || 0},${source.conversions || 0},${(source.conversion_rate || 0).toFixed(1)}%`
  ).join('\n')

  const blob = new Blob([`來源,UTM Source,UTM Medium,點擊數,轉換數,轉換率\n${csvContent}`], { type: 'text/csv' })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `promotion_${props.promotion.id}_sources.csv`
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

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('zh-TW')
}

const getConversionRateColor = (rate) => {
  if (rate >= 10) return 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
  if (rate >= 5) return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400'
  return 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
}

// Initialize analytics when modal opens
watch(() => props.showModal, (newVal) => {
  if (newVal && props.promotion) {
    refreshAnalytics()
    // Mock generated links data
    generatedLinks.value = [
      {
        id: 1,
        name: '臉書分享連結',
        url: `https://promotion.mercylife.cc/p/${props.promotion.id}?utm_source=facebook&ref=FB123`,
        clicks: 245,
        conversions: 18,
        created_at: '2024-01-15'
      },
      {
        id: 2,
        name: 'Instagram 連結',
        url: `https://promotion.mercylife.cc/p/${props.promotion.id}?utm_source=instagram&ref=IG456`,
        clicks: 189,
        conversions: 12,
        created_at: '2024-01-14'
      }
    ]
  }
})
</script>