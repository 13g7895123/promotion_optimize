<template>
  <div class="statistics-chart">
    <!-- 圖表標題和控制項 -->
    <div class="chart-header">
      <div class="header-left">
        <h3 class="chart-title">{{ title }}</h3>
        <p v-if="description" class="chart-description">{{ description }}</p>
      </div>
      
      <div class="header-right">
        <!-- 圖表類型切換 -->
        <el-radio-group 
          v-if="showTypeSwitch" 
          v-model="currentChartType" 
          size="small"
          @change="handleTypeChange"
        >
          <el-radio-button label="line">線圖</el-radio-button>
          <el-radio-button label="bar">柱圖</el-radio-button>
          <el-radio-button label="area">面積圖</el-radio-button>
        </el-radio-group>

        <!-- 時間範圍選擇 -->
        <el-select 
          v-if="showTimeRange"
          v-model="selectedTimeRange"
          size="small"
          style="width: 120px"
          @change="handleTimeRangeChange"
        >
          <el-option label="7天" value="7d" />
          <el-option label="30天" value="30d" />
          <el-option label="90天" value="90d" />
          <el-option label="自訂" value="custom" />
        </el-select>

        <!-- 自訂時間範圍 -->
        <el-date-picker
          v-if="selectedTimeRange === 'custom'"
          v-model="customDateRange"
          type="daterange"
          size="small"
          range-separator="至"
          start-placeholder="開始日期"
          end-placeholder="結束日期"
          format="YYYY-MM-DD"
          value-format="YYYY-MM-DD"
          @change="handleCustomDateChange"
        />

        <!-- 重新整理按鈕 -->
        <el-button 
          :icon="Refresh" 
          size="small" 
          circle
          @click="refreshData"
          :loading="loading"
        />
      </div>
    </div>

    <!-- 圖表內容 -->
    <div class="chart-content">
      <!-- 載入狀態 -->
      <div v-if="loading" class="loading-container">
        <el-skeleton animated>
          <template #template>
            <div style="height: 300px; display: flex; align-items: center; justify-content: center;">
              <el-skeleton-item variant="image" style="width: 100%; height: 200px;" />
            </div>
          </template>
        </el-skeleton>
      </div>

      <!-- 錯誤狀態 -->
      <div v-else-if="error" class="error-container">
        <el-empty :image-size="100" description="載入失敗">
          <template #description>
            <p>{{ error }}</p>
          </template>
          <el-button type="primary" @click="refreshData">重新載入</el-button>
        </el-empty>
      </div>

      <!-- 無資料狀態 -->
      <div v-else-if="!hasData" class="empty-container">
        <el-empty :image-size="100" :description="emptyMessage" />
      </div>

      <!-- 圖表容器 -->
      <div v-else ref="chartContainer" class="chart-container" :style="{ height: `${height}px` }"></div>
    </div>

    <!-- 圖表說明 -->
    <div v-if="showLegend && hasData" class="chart-legend">
      <div class="legend-items">
        <div 
          v-for="(dataset, index) in chartData.datasets"
          :key="index"
          class="legend-item"
        >
          <div 
            class="legend-color" 
            :style="{ backgroundColor: dataset.backgroundColor || dataset.borderColor }"
          ></div>
          <span class="legend-label">{{ dataset.label }}</span>
        </div>
      </div>
      
      <!-- 統計摘要 -->
      <div v-if="showSummary" class="chart-summary">
        <div class="summary-item">
          <span class="summary-label">總計：</span>
          <span class="summary-value">{{ formatNumber(totalValue) }}</span>
        </div>
        <div class="summary-item">
          <span class="summary-label">平均：</span>
          <span class="summary-value">{{ formatNumber(averageValue) }}</span>
        </div>
        <div v-if="maxValue > 0" class="summary-item">
          <span class="summary-label">最高：</span>
          <span class="summary-value">{{ formatNumber(maxValue) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Refresh } from '@element-plus/icons-vue'
import type { ChartData, DateRange } from '~/types'

interface Props {
  title: string
  description?: string
  chartData: ChartData
  chartType?: 'line' | 'bar' | 'area' | 'pie' | 'doughnut'
  height?: number
  loading?: boolean
  error?: string
  emptyMessage?: string
  showTypeSwitch?: boolean
  showTimeRange?: boolean
  showLegend?: boolean
  showSummary?: boolean
  colors?: string[]
}

interface Emits {
  (e: 'refresh'): void
  (e: 'type-change', type: string): void
  (e: 'time-range-change', range: string | DateRange): void
}

const props = withDefaults(defineProps<Props>(), {
  chartType: 'line',
  height: 300,
  loading: false,
  error: '',
  emptyMessage: '暫無資料',
  showTypeSwitch: false,
  showTimeRange: false,
  showLegend: true,
  showSummary: false,
  colors: () => [
    '#409EFF', '#67C23A', '#E6A23C', '#F56C6C', '#909399',
    '#c45656', '#8e44ad', '#3498db', '#e67e22', '#f39c12'
  ],
})

const emit = defineEmits<Emits>()

// 響應式數據
const chartContainer = ref<HTMLElement>()
const chartInstance = ref<any>(null)
const currentChartType = ref(props.chartType)
const selectedTimeRange = ref('30d')
const customDateRange = ref<[string, string] | null>(null)

// 計算屬性
const hasData = computed(() => {
  return props.chartData?.datasets?.length > 0 && 
         props.chartData.datasets.some(dataset => dataset.data?.length > 0)
})

const totalValue = computed(() => {
  if (!hasData.value) return 0
  return props.chartData.datasets.reduce((total, dataset) => {
    return total + dataset.data.reduce((sum: number, value: number) => sum + value, 0)
  }, 0)
})

const averageValue = computed(() => {
  if (!hasData.value) return 0
  const totalPoints = props.chartData.datasets.reduce((total, dataset) => {
    return total + dataset.data.length
  }, 0)
  return totalPoints > 0 ? totalValue.value / totalPoints : 0
})

const maxValue = computed(() => {
  if (!hasData.value) return 0
  return props.chartData.datasets.reduce((max, dataset) => {
    const datasetMax = Math.max(...dataset.data)
    return Math.max(max, datasetMax)
  }, 0)
})

// 方法
const initChart = async () => {
  if (!chartContainer.value || !hasData.value) return

  try {
    // 動態導入 Chart.js
    const { Chart, registerables } = await import('chart.js')
    Chart.register(...registerables)

    // 銷毀舊圖表
    if (chartInstance.value) {
      chartInstance.value.destroy()
    }

    // 準備圖表配置
    const config = {
      type: currentChartType.value,
      data: {
        labels: props.chartData.labels,
        datasets: props.chartData.datasets.map((dataset, index) => ({
          ...dataset,
          backgroundColor: dataset.backgroundColor || props.colors[index % props.colors.length] + '20',
          borderColor: dataset.borderColor || props.colors[index % props.colors.length],
          borderWidth: dataset.borderWidth || 2,
          fill: currentChartType.value === 'area',
          tension: 0.4,
        })),
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          intersect: false,
          mode: 'index' as const,
        },
        plugins: {
          legend: {
            display: false, // 使用自訂圖例
          },
          tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            borderColor: '#409EFF',
            borderWidth: 1,
            cornerRadius: 6,
            displayColors: true,
            callbacks: {
              title: (context: any) => {
                return context[0]?.label || ''
              },
              label: (context: any) => {
                const label = context.dataset.label || ''
                const value = formatNumber(context.parsed.y || context.parsed)
                return `${label}: ${value}`
              },
            },
          },
        },
        scales: currentChartType.value !== 'pie' && currentChartType.value !== 'doughnut' ? {
          x: {
            display: true,
            grid: {
              color: 'rgba(0, 0, 0, 0.05)',
            },
            ticks: {
              color: '#666',
            },
          },
          y: {
            display: true,
            beginAtZero: true,
            grid: {
              color: 'rgba(0, 0, 0, 0.05)',
            },
            ticks: {
              color: '#666',
              callback: (value: any) => formatNumber(value),
            },
          },
        } : {},
        elements: {
          point: {
            radius: 3,
            hoverRadius: 6,
          },
        },
        animation: {
          duration: 750,
          easing: 'easeInOutQuart',
        },
      },
    }

    // 創建圖表實例
    chartInstance.value = new Chart(chartContainer.value, config)
  } catch (error) {
    console.error('圖表初始化失敗:', error)
  }
}

const updateChart = () => {
  if (!chartInstance.value || !hasData.value) return

  chartInstance.value.data.labels = props.chartData.labels
  chartInstance.value.data.datasets = props.chartData.datasets.map((dataset, index) => ({
    ...dataset,
    backgroundColor: dataset.backgroundColor || props.colors[index % props.colors.length] + '20',
    borderColor: dataset.borderColor || props.colors[index % props.colors.length],
    borderWidth: dataset.borderWidth || 2,
    fill: currentChartType.value === 'area',
  }))
  
  chartInstance.value.update()
}

const refreshData = () => {
  emit('refresh')
}

const handleTypeChange = (type: string) => {
  currentChartType.value = type as any
  emit('type-change', type)
  
  // 重新初始化圖表
  nextTick(() => {
    initChart()
  })
}

const handleTimeRangeChange = (range: string) => {
  if (range !== 'custom') {
    customDateRange.value = null
    emit('time-range-change', range)
  }
}

const handleCustomDateChange = (dates: [string, string] | null) => {
  if (dates) {
    emit('time-range-change', { start: dates[0], end: dates[1] })
  }
}

const formatNumber = (num: number): string => {
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + 'M'
  }
  if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K'
  }
  return num.toLocaleString()
}

// 生命週期
onMounted(() => {
  if (hasData.value) {
    initChart()
  }
})

onUnmounted(() => {
  if (chartInstance.value) {
    chartInstance.value.destroy()
  }
})

// 監聽數據變化
watch(() => props.chartData, () => {
  if (chartInstance.value && hasData.value) {
    updateChart()
  } else if (hasData.value) {
    nextTick(() => {
      initChart()
    })
  }
}, { deep: true })

watch(() => props.loading, (loading) => {
  if (!loading && hasData.value) {
    nextTick(() => {
      initChart()
    })
  }
})
</script>

<style scoped lang="scss">
.statistics-chart {
  background: var(--el-bg-color);
  border-radius: 8px;
  padding: 16px;
  border: 1px solid var(--el-border-color-lighter);

  .chart-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;

    @media (max-width: 768px) {
      flex-direction: column;
      gap: 12px;
    }

    .header-left {
      flex: 1;
      min-width: 0;

      .chart-title {
        font-size: 18px;
        font-weight: 600;
        margin: 0 0 4px 0;
        color: var(--el-text-color-primary);
      }

      .chart-description {
        font-size: 14px;
        color: var(--el-text-color-secondary);
        margin: 0;
        line-height: 1.4;
      }
    }

    .header-right {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;

      @media (max-width: 768px) {
        width: 100%;
        justify-content: flex-end;
      }

      @media (max-width: 480px) {
        justify-content: stretch;
        
        .el-select,
        .el-date-editor {
          flex: 1;
        }
      }
    }
  }

  .chart-content {
    position: relative;
    min-height: 200px;

    .loading-container,
    .error-container,
    .empty-container {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 300px;
    }

    .chart-container {
      position: relative;
      width: 100%;
    }
  }

  .chart-legend {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--el-border-color-lighter);

    .legend-items {
      display: flex;
      flex-wrap: wrap;
      gap: 16px;
      margin-bottom: 12px;

      .legend-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 14px;

        .legend-color {
          width: 12px;
          height: 12px;
          border-radius: 2px;
          flex-shrink: 0;
        }

        .legend-label {
          color: var(--el-text-color-regular);
        }
      }
    }

    .chart-summary {
      display: flex;
      gap: 24px;
      font-size: 14px;

      @media (max-width: 480px) {
        flex-direction: column;
        gap: 8px;
      }

      .summary-item {
        display: flex;
        align-items: center;
        gap: 4px;

        .summary-label {
          color: var(--el-text-color-secondary);
        }

        .summary-value {
          color: var(--el-text-color-primary);
          font-weight: 600;
        }
      }
    }
  }
}

// Element Plus 樣式覆蓋
:deep(.el-radio-group) {
  .el-radio-button__inner {
    padding: 6px 12px;
    font-size: 12px;
  }
}

:deep(.el-date-editor) {
  --el-date-editor-width: 240px;
  
  @media (max-width: 480px) {
    --el-date-editor-width: 100%;
  }
}
</style>