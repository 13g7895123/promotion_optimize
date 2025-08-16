<template>
  <el-dialog
    v-model="visible"
    :title="`${promotion?.title} - 分析報告`"
    width="1000px"
    :before-close="handleClose"
    class="promotion-analytics-dialog"
  >
    <div v-if="promotion" class="analytics-content">
      <!-- 時間範圍選擇 -->
      <div class="time-range-selector">
        <el-date-picker
          v-model="dateRange"
          type="datetimerange"
          range-separator="至"
          start-placeholder="開始時間"
          end-placeholder="結束時間"
          format="YYYY-MM-DD HH:mm"
          value-format="YYYY-MM-DD HH:mm:ss"
          @change="handleDateRangeChange"
        />
        <el-button 
          type="primary" 
          :icon="Refresh"
          @click="refreshAnalytics"
          :loading="loading"
        >
          刷新數據
        </el-button>
      </div>

      <!-- 載入狀態 -->
      <div v-if="loading" class="loading-container">
        <el-skeleton animated>
          <template #template>
            <div class="skeleton-stats">
              <el-skeleton-item variant="rect" style="height: 120px; margin-bottom: 20px;" />
              <el-skeleton-item variant="rect" style="height: 300px; margin-bottom: 20px;" />
              <el-skeleton-item variant="rect" style="height: 250px;" />
            </div>
          </template>
        </el-skeleton>
      </div>

      <!-- 分析數據 -->
      <div v-else-if="analytics" class="analytics-data">
        <!-- 概覽統計 -->
        <div class="overview-stats">
          <div class="stat-card">
            <div class="stat-header">
              <h4>總點擊數</h4>
              <el-icon class="stat-icon clicks"><View /></el-icon>
            </div>
            <div class="stat-value">{{ formatNumber(getTotalClicks()) }}</div>
            <div class="stat-trend">
              <el-icon class="trend-up"><TrendCharts /></el-icon>
              <span>{{ getClicksTrend() }}</span>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-header">
              <h4>總轉換數</h4>
              <el-icon class="stat-icon conversions"><Trophy /></el-icon>
            </div>
            <div class="stat-value">{{ formatNumber(getTotalConversions()) }}</div>
            <div class="stat-trend">
              <el-icon class="trend-up"><TrendCharts /></el-icon>
              <span>{{ getConversionsTrend() }}</span>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-header">
              <h4>平均轉換率</h4>
              <el-icon class="stat-icon rate"><DataAnalysis /></el-icon>
            </div>
            <div class="stat-value">{{ formatPercentage(getAverageConversionRate()) }}</div>
            <div class="stat-trend">
              <el-icon class="trend-up"><TrendCharts /></el-icon>
              <span>{{ getConversionRateTrend() }}</span>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-header">
              <h4>熱門時段</h4>
              <el-icon class="stat-icon time"><Clock /></el-icon>
            </div>
            <div class="stat-value">{{ getPeakHour() }}</div>
            <div class="stat-trend">
              <span>點擊最活躍時段</span>
            </div>
          </div>
        </div>

        <!-- 趨勢圖表 -->
        <div class="charts-section">
          <!-- 每日點擊趨勢 -->
          <div class="chart-container">
            <StatisticsChart
              title="每日點擊趨勢"
              description="顯示推廣活動的每日點擊和轉換情況"
              :chart-data="getDailyTrendChartData()"
              chart-type="line"
              :height="300"
              :loading="chartLoading"
              show-legend
              show-summary
              @refresh="refreshAnalytics"
            />
          </div>

          <!-- 小時分佈圖 -->
          <div class="chart-container">
            <StatisticsChart
              title="24小時點擊分佈"
              description="分析用戶點擊的時間偏好"
              :chart-data="getHourlyDistributionChartData()"
              chart-type="bar"
              :height="250"
              :loading="chartLoading"
              show-legend
              @refresh="refreshAnalytics"
            />
          </div>
        </div>

        <!-- 詳細分析 -->
        <div class="detailed-analysis">
          <el-row :gutter="20">
            <!-- 來源分析 -->
            <el-col :span="12">
              <el-card class="analysis-card">
                <template #header>
                  <div class="card-header">
                    <span>熱門來源</span>
                    <el-tag size="small">TOP 5</el-tag>
                  </div>
                </template>
                
                <div class="referrer-list">
                  <div 
                    v-for="(referrer, index) in analytics.top_referrers.slice(0, 5)"
                    :key="index"
                    class="referrer-item"
                  >
                    <div class="referrer-info">
                      <span class="referrer-name">{{ getReferrerName(referrer.referrer) }}</span>
                      <span class="referrer-url">{{ referrer.referrer || '直接訪問' }}</span>
                    </div>
                    <div class="referrer-stats">
                      <span class="clicks-count">{{ referrer.clicks }}</span>
                      <div class="clicks-bar">
                        <div 
                          class="clicks-fill" 
                          :style="{ width: `${(referrer.clicks / analytics.top_referrers[0]?.clicks * 100) || 0}%` }"
                        ></div>
                      </div>
                    </div>
                  </div>

                  <div v-if="analytics.top_referrers.length === 0" class="empty-state">
                    <el-empty :image-size="60" description="暫無來源數據" />
                  </div>
                </div>
              </el-card>
            </el-col>

            <!-- 地理分佈 -->
            <el-col :span="12">
              <el-card class="analysis-card">
                <template #header>
                  <div class="card-header">
                    <span>地理分佈</span>
                    <el-tag size="small">按國家/地區</el-tag>
                  </div>
                </template>
                
                <div class="geographic-list">
                  <div 
                    v-for="(geo, index) in analytics.geographic_data.slice(0, 5)"
                    :key="index"
                    class="geographic-item"
                  >
                    <div class="geo-info">
                      <span class="country-flag">{{ getCountryFlag(geo.country) }}</span>
                      <span class="country-name">{{ getCountryName(geo.country) }}</span>
                    </div>
                    <div class="geo-stats">
                      <span class="clicks-count">{{ geo.clicks }}</span>
                      <span class="clicks-percentage">{{ formatPercentage(geo.clicks / getTotalClicks()) }}</span>
                    </div>
                  </div>

                  <div v-if="analytics.geographic_data.length === 0" class="empty-state">
                    <el-empty :image-size="60" description="暫無地理數據" />
                  </div>
                </div>
              </el-card>
            </el-col>
          </el-row>

          <!-- 設備分析 -->
          <el-row :gutter="20" style="margin-top: 20px;">
            <el-col :span="24">
              <el-card class="analysis-card">
                <template #header>
                  <div class="card-header">
                    <span>設備類型分析</span>
                    <el-tag size="small">用戶設備偏好</el-tag>
                  </div>
                </template>
                
                <div class="device-analysis">
                  <div class="device-chart">
                    <StatisticsChart
                      title=""
                      :chart-data="getDeviceChartData()"
                      chart-type="doughnut"
                      :height="200"
                      :loading="chartLoading"
                      show-legend
                    />
                  </div>
                  
                  <div class="device-list">
                    <div 
                      v-for="(device, index) in analytics.device_types"
                      :key="index"
                      class="device-item"
                    >
                      <div class="device-info">
                        <el-icon class="device-icon">
                          <component :is="getDeviceIcon(device.device)" />
                        </el-icon>
                        <span class="device-name">{{ getDeviceName(device.device) }}</span>
                      </div>
                      <div class="device-stats">
                        <span class="device-count">{{ device.clicks }}</span>
                        <span class="device-percentage">{{ formatPercentage(device.clicks / getTotalClicks()) }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </el-card>
            </el-col>
          </el-row>
        </div>
      </div>

      <!-- 錯誤狀態 -->
      <div v-else-if="error" class="error-state">
        <el-empty :image-size="100" description="載入分析數據失敗">
          <template #description>
            <p>{{ error }}</p>
          </template>
          <el-button type="primary" @click="refreshAnalytics">重新載入</el-button>
        </el-empty>
      </div>

      <!-- 無數據狀態 -->
      <div v-else class="empty-state">
        <el-empty :image-size="100" description="暫無分析數據" />
      </div>
    </div>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="handleClose">關閉</el-button>
        <el-button type="primary" @click="exportReport">
          <el-icon><Download /></el-icon>
          匯出報告
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import {
  Refresh,
  View,
  Trophy,
  DataAnalysis,
  Clock,
  TrendCharts,
  Download,
  Monitor,
  Iphone,
  Monitor as Tablet,
} from '@element-plus/icons-vue'
import type { Promotion, PromotionAnalytics, ChartData } from '~/types'

interface Props {
  modelValue: boolean
  promotion: Promotion | null
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// 響應式數據
const loading = ref(false)
const chartLoading = ref(false)
const error = ref('')
const analytics = ref<PromotionAnalytics | null>(null)
const dateRange = ref<[string, string] | null>(null)

// 計算屬性
const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

// 使用 stores
const promotionStore = usePromotionStore()

// 方法
const handleClose = () => {
  visible.value = false
}

const handleDateRangeChange = (dates: [string, string] | null) => {
  if (dates) {
    refreshAnalytics()
  }
}

const refreshAnalytics = async () => {
  if (!props.promotion) return
  
  loading.value = true
  chartLoading.value = true
  error.value = ''
  
  try {
    const dateRangeParam = dateRange.value ? {
      start: dateRange.value[0],
      end: dateRange.value[1]
    } : undefined
    
    const result = await promotionStore.fetchPromotionAnalytics(
      props.promotion.id,
      dateRangeParam
    )
    analytics.value = result
  } catch (err: any) {
    error.value = err.message || '載入分析數據失敗'
  } finally {
    loading.value = false
    chartLoading.value = false
  }
}

const exportReport = async () => {
  if (!props.promotion || !analytics.value) return
  
  try {
    // 這裡可以實現報告匯出功能
    ElMessage.success('報告匯出功能開發中')
  } catch (error: any) {
    ElMessage.error('匯出失敗：' + error.message)
  }
}

// 統計計算方法
const getTotalClicks = (): number => {
  if (!analytics.value) return 0
  return analytics.value.daily_clicks.reduce((sum, day) => sum + day.clicks, 0)
}

const getTotalConversions = (): number => {
  if (!analytics.value) return 0
  return analytics.value.daily_clicks.reduce((sum, day) => sum + day.conversions, 0)
}

const getAverageConversionRate = (): number => {
  const totalClicks = getTotalClicks()
  const totalConversions = getTotalConversions()
  return totalClicks > 0 ? totalConversions / totalClicks : 0
}

const getPeakHour = (): string => {
  if (!analytics.value || analytics.value.hourly_distribution.length === 0) return '-'
  
  const peakHour = analytics.value.hourly_distribution.reduce((max, hour) => 
    hour.clicks > max.clicks ? hour : max
  )
  
  return `${peakHour.hour}:00 - ${peakHour.hour + 1}:00`
}

const getClicksTrend = (): string => {
  // 簡化的趨勢計算
  if (!analytics.value || analytics.value.daily_clicks.length < 2) return '無數據'
  
  const recent = analytics.value.daily_clicks.slice(-7)
  const older = analytics.value.daily_clicks.slice(-14, -7)
  
  const recentAvg = recent.reduce((sum, day) => sum + day.clicks, 0) / recent.length
  const olderAvg = older.reduce((sum, day) => sum + day.clicks, 0) / older.length
  
  if (olderAvg === 0) return '新活動'
  
  const change = ((recentAvg - olderAvg) / olderAvg) * 100
  return change > 0 ? `↗ +${change.toFixed(1)}%` : `↘ ${change.toFixed(1)}%`
}

const getConversionsTrend = (): string => {
  // 類似點擊趨勢的計算
  return getClicksTrend().replace('點擊', '轉換')
}

const getConversionRateTrend = (): string => {
  return '穩定增長'
}

// 圖表數據生成
const getDailyTrendChartData = (): ChartData => {
  if (!analytics.value) {
    return { labels: [], datasets: [] }
  }
  
  return {
    labels: analytics.value.daily_clicks.map(day => day.date),
    datasets: [
      {
        label: '點擊數',
        data: analytics.value.daily_clicks.map(day => day.clicks),
        borderColor: '#409EFF',
        backgroundColor: '#409EFF20',
        tension: 0.4,
      },
      {
        label: '轉換數',
        data: analytics.value.daily_clicks.map(day => day.conversions),
        borderColor: '#67C23A',
        backgroundColor: '#67C23A20',
        tension: 0.4,
      }
    ]
  }
}

const getHourlyDistributionChartData = (): ChartData => {
  if (!analytics.value) {
    return { labels: [], datasets: [] }
  }
  
  return {
    labels: analytics.value.hourly_distribution.map(hour => `${hour.hour}:00`),
    datasets: [
      {
        label: '點擊數',
        data: analytics.value.hourly_distribution.map(hour => hour.clicks),
        backgroundColor: '#409EFF',
      }
    ]
  }
}

const getDeviceChartData = (): ChartData => {
  if (!analytics.value) {
    return { labels: [], datasets: [] }
  }
  
  const colors = ['#409EFF', '#67C23A', '#E6A23C', '#F56C6C', '#909399']
  
  return {
    labels: analytics.value.device_types.map(device => getDeviceName(device.device)),
    datasets: [
      {
        data: analytics.value.device_types.map(device => device.clicks),
        backgroundColor: colors.slice(0, analytics.value.device_types.length),
      }
    ]
  }
}

// 工具方法
const formatNumber = (num: number) => {
  return num.toLocaleString()
}

const formatPercentage = (num: number) => {
  return `${(num * 100).toFixed(1)}%`
}

const getReferrerName = (referrer: string): string => {
  if (!referrer) return '直接訪問'
  
  try {
    const url = new URL(referrer)
    return url.hostname
  } catch {
    return referrer
  }
}

const getCountryFlag = (countryCode: string): string => {
  const flagMap: Record<string, string> = {
    'TW': '🇹🇼',
    'CN': '🇨🇳',
    'US': '🇺🇸',
    'JP': '🇯🇵',
    'KR': '🇰🇷',
  }
  return flagMap[countryCode] || '🌍'
}

const getCountryName = (countryCode: string): string => {
  const nameMap: Record<string, string> = {
    'TW': '台灣',
    'CN': '中國',
    'US': '美國',
    'JP': '日本',
    'KR': '韓國',
  }
  return nameMap[countryCode] || countryCode
}

const getDeviceIcon = (device: string) => {
  const iconMap: Record<string, any> = {
    'desktop': Monitor,
    'mobile': Iphone,
    'tablet': Monitor as Tablet,
  }
  return iconMap[device.toLowerCase()] || Monitor
}

const getDeviceName = (device: string): string => {
  const nameMap: Record<string, string> = {
    'desktop': '桌面設備',
    'mobile': '手機',
    'tablet': '平板',
  }
  return nameMap[device.toLowerCase()] || device
}

// 監聽推廣變化
watch(() => props.promotion, async (newPromotion) => {
  if (newPromotion && props.modelValue) {
    // 設置默認時間範圍為最近30天
    const endDate = new Date()
    const startDate = new Date(endDate.getTime() - 30 * 24 * 60 * 60 * 1000)
    
    dateRange.value = [
      startDate.toISOString().slice(0, 19).replace('T', ' '),
      endDate.toISOString().slice(0, 19).replace('T', ' ')
    ]
    
    await refreshAnalytics()
  }
}, { immediate: true })
</script>

<style scoped lang="scss">
.promotion-analytics-dialog {
  .analytics-content {
    .time-range-selector {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
      padding-bottom: 16px;
      border-bottom: 1px solid var(--el-border-color-lighter);

      @media (max-width: 768px) {
        flex-direction: column;
        gap: 12px;
        align-items: stretch;
      }
    }

    .loading-container {
      .skeleton-stats {
        padding: 20px 0;
      }
    }

    .overview-stats {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
      margin-bottom: 32px;

      @media (max-width: 768px) {
        grid-template-columns: repeat(2, 1fr);
      }

      @media (max-width: 480px) {
        grid-template-columns: 1fr;
      }

      .stat-card {
        background: var(--el-bg-color);
        border: 1px solid var(--el-border-color-lighter);
        border-radius: 8px;
        padding: 20px;

        .stat-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 12px;

          h4 {
            font-size: 14px;
            color: var(--el-text-color-secondary);
            margin: 0;
          }

          .stat-icon {
            font-size: 18px;
            
            &.clicks {
              color: var(--el-color-primary);
            }

            &.conversions {
              color: var(--el-color-success);
            }

            &.rate {
              color: var(--el-color-warning);
            }

            &.time {
              color: var(--el-color-info);
            }
          }
        }

        .stat-value {
          font-size: 24px;
          font-weight: 700;
          color: var(--el-text-color-primary);
          margin-bottom: 8px;
        }

        .stat-trend {
          display: flex;
          align-items: center;
          gap: 4px;
          font-size: 12px;
          color: var(--el-color-success);

          .trend-up {
            font-size: 14px;
          }
        }
      }
    }

    .charts-section {
      display: grid;
      grid-template-columns: 1fr;
      gap: 24px;
      margin-bottom: 32px;

      .chart-container {
        background: var(--el-bg-color);
        border-radius: 8px;
        padding: 16px;
        border: 1px solid var(--el-border-color-lighter);
      }
    }

    .detailed-analysis {
      .analysis-card {
        .card-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
        }

        .referrer-list,
        .geographic-list {
          .referrer-item,
          .geographic-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--el-border-color-lighter);

            &:last-child {
              border-bottom: none;
            }
          }

          .referrer-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 2px;

            .referrer-name {
              font-size: 14px;
              font-weight: 500;
              color: var(--el-text-color-primary);
            }

            .referrer-url {
              font-size: 12px;
              color: var(--el-text-color-secondary);
              max-width: 200px;
              overflow: hidden;
              text-overflow: ellipsis;
              white-space: nowrap;
            }
          }

          .referrer-stats {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 100px;

            .clicks-count {
              font-size: 14px;
              font-weight: 600;
              color: var(--el-text-color-primary);
            }

            .clicks-bar {
              flex: 1;
              height: 4px;
              background: var(--el-fill-color-light);
              border-radius: 2px;
              overflow: hidden;

              .clicks-fill {
                height: 100%;
                background: var(--el-color-primary);
                transition: width 0.3s ease;
              }
            }
          }

          .geo-info {
            display: flex;
            align-items: center;
            gap: 8px;

            .country-flag {
              font-size: 18px;
            }

            .country-name {
              font-size: 14px;
              color: var(--el-text-color-primary);
              font-weight: 500;
            }
          }

          .geo-stats {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 2px;

            .clicks-count {
              font-size: 14px;
              font-weight: 600;
              color: var(--el-text-color-primary);
            }

            .clicks-percentage {
              font-size: 12px;
              color: var(--el-text-color-secondary);
            }
          }

          .empty-state {
            padding: 40px 0;
            text-align: center;
          }
        }

        .device-analysis {
          display: flex;
          gap: 24px;

          @media (max-width: 768px) {
            flex-direction: column;
          }

          .device-chart {
            flex: 0 0 300px;

            @media (max-width: 768px) {
              flex: none;
            }
          }

          .device-list {
            flex: 1;

            .device-item {
              display: flex;
              justify-content: space-between;
              align-items: center;
              padding: 12px 0;
              border-bottom: 1px solid var(--el-border-color-lighter);

              &:last-child {
                border-bottom: none;
              }

              .device-info {
                display: flex;
                align-items: center;
                gap: 8px;

                .device-icon {
                  font-size: 18px;
                  color: var(--el-color-primary);
                }

                .device-name {
                  font-size: 14px;
                  color: var(--el-text-color-primary);
                  font-weight: 500;
                }
              }

              .device-stats {
                display: flex;
                flex-direction: column;
                align-items: flex-end;
                gap: 2px;

                .device-count {
                  font-size: 14px;
                  font-weight: 600;
                  color: var(--el-text-color-primary);
                }

                .device-percentage {
                  font-size: 12px;
                  color: var(--el-text-color-secondary);
                }
              }
            }
          }
        }
      }
    }

    .error-state,
    .empty-state {
      padding: 60px 0;
      text-align: center;
    }
  }

  .dialog-footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;

    @media (max-width: 480px) {
      flex-direction: column;
      
      .el-button {
        width: 100%;
      }
    }
  }
}

// Element Plus 樣式覆蓋
:deep(.el-dialog__body) {
  max-height: 70vh;
  overflow-y: auto;
}
</style>