<template>
  <el-dialog
    v-model="visible"
    :title="promotion?.title || '推廣詳情'"
    width="800px"
    :before-close="handleClose"
    class="promotion-detail-dialog"
  >
    <div v-if="promotion" class="detail-content">
      <!-- 基本資訊 -->
      <div class="info-section">
        <h3 class="section-title">基本資訊</h3>
        <div class="info-grid">
          <div class="info-item">
            <span class="info-label">推廣標題：</span>
            <span class="info-value">{{ promotion.title }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">推廣狀態：</span>
            <el-tag :type="getStatusColor(promotion.status)">
              {{ getStatusText(promotion.status) }}
            </el-tag>
          </div>
          <div class="info-item">
            <span class="info-label">所屬伺服器：</span>
            <span class="info-value">{{ promotion.server?.name || '未知' }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">獎勵類型：</span>
            <el-tag :type="getRewardTypeColor(promotion.reward_type)">
              {{ getRewardTypeText(promotion.reward_type) }}
            </el-tag>
          </div>
          <div class="info-item">
            <span class="info-label">獎勵數量：</span>
            <span class="info-value">{{ formatRewardValue(promotion.reward_value, promotion.reward_type) }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">使用限額：</span>
            <span class="info-value">{{ promotion.max_uses }} 次</span>
          </div>
          <div class="info-item">
            <span class="info-label">開始時間：</span>
            <span class="info-value">{{ formatDateTime(promotion.start_date) }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">結束時間：</span>
            <span class="info-value">{{ formatDateTime(promotion.end_date) }}</span>
          </div>
        </div>
      </div>

      <!-- 推廣描述 -->
      <div class="info-section">
        <h3 class="section-title">推廣描述</h3>
        <div class="description-content">
          {{ promotion.description }}
        </div>
      </div>

      <!-- 獎勵說明 -->
      <div v-if="promotion.reward_description" class="info-section">
        <h3 class="section-title">獎勵說明</h3>
        <div class="description-content">
          {{ promotion.reward_description }}
        </div>
      </div>

      <!-- 推廣連結 -->
      <div v-if="promotion.promotion_link" class="info-section">
        <h3 class="section-title">推廣連結</h3>
        <div class="link-container">
          <el-input 
            :value="promotion.promotion_link" 
            readonly
            class="link-input"
          >
            <template #append>
              <el-button @click="copyLink" :loading="copyLoading">
                複製
              </el-button>
            </template>
          </el-input>
        </div>
      </div>

      <!-- 統計數據 -->
      <div v-if="statistics" class="info-section">
        <h3 class="section-title">統計數據</h3>
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon clicks">
              <el-icon><View /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ formatNumber(statistics.total_clicks) }}</div>
              <div class="stat-label">總點擊數</div>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon unique">
              <el-icon><User /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ formatNumber(statistics.unique_clicks) }}</div>
              <div class="stat-label">獨立訪客</div>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon conversions">
              <el-icon><Trophy /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ formatNumber(statistics.conversions) }}</div>
              <div class="stat-label">轉換數量</div>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon rate">
              <el-icon><TrendCharts /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ formatPercentage(statistics.conversion_rate) }}</div>
              <div class="stat-label">轉換率</div>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon rewards">
              <el-icon><Present /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ formatNumber(statistics.total_rewards_given) }}</div>
              <div class="stat-label">已發放獎勵</div>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon usage">
              <el-icon><DataAnalysis /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ `${promotion.current_uses}/${promotion.max_uses}` }}</div>
              <div class="stat-label">使用情況</div>
            </div>
          </div>
        </div>

        <!-- 使用進度 -->
        <div class="usage-progress">
          <div class="progress-header">
            <span class="progress-label">使用進度</span>
            <span class="progress-percentage">{{ Math.round((promotion.current_uses / promotion.max_uses) * 100) }}%</span>
          </div>
          <el-progress 
            :percentage="Math.round((promotion.current_uses / promotion.max_uses) * 100)"
            :color="getProgressColor(promotion.current_uses / promotion.max_uses)"
            :stroke-width="8"
          />
        </div>

        <!-- 最後活動時間 -->
        <div v-if="statistics.last_click_at" class="last-activity">
          <el-icon><Clock /></el-icon>
          <span>最後點擊時間：{{ formatDateTime(statistics.last_click_at) }}</span>
        </div>
      </div>

      <!-- QR Code -->
      <div v-if="promotion.qr_code" class="info-section">
        <h3 class="section-title">QR Code</h3>
        <div class="qr-container">
          <img :src="promotion.qr_code" alt="QR Code" class="qr-image" />
          <div class="qr-actions">
            <el-button size="small" @click="downloadQR">
              <el-icon><Download /></el-icon>
              下載 QR Code
            </el-button>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="handleClose">關閉</el-button>
        <el-button type="primary" @click="handleEdit">
          <el-icon><Edit /></el-icon>
          編輯
        </el-button>
        <el-button type="info" @click="handleViewAnalytics">
          <el-icon><TrendCharts /></el-icon>
          查看分析
        </el-button>
        <el-button type="danger" @click="handleDelete">
          <el-icon><Delete /></el-icon>
          刪除
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import {
  View,
  User,
  Trophy,
  TrendCharts,
  Present,
  DataAnalysis,
  Clock,
  Download,
  Edit,
  Delete,
} from '@element-plus/icons-vue'
import type { Promotion, PromotionStatistics } from '~/types'

interface Props {
  modelValue: boolean
  promotion: Promotion | null
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'edit', promotion: Promotion): void
  (e: 'delete', promotion: Promotion): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// 響應式數據
const copyLoading = ref(false)
const statistics = ref<PromotionStatistics | null>(null)

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

const handleEdit = () => {
  if (props.promotion) {
    emit('edit', props.promotion)
    handleClose()
  }
}

const handleDelete = () => {
  if (props.promotion) {
    emit('delete', props.promotion)
    handleClose()
  }
}

const handleViewAnalytics = () => {
  // 發出事件或直接導航
  if (props.promotion) {
    navigateTo(`/promotion/analytics/${props.promotion.id}`)
  }
}

const copyLink = async () => {
  if (!props.promotion?.promotion_link) return
  
  copyLoading.value = true
  try {
    await navigator.clipboard.writeText(props.promotion.promotion_link)
    ElMessage.success('推廣連結已複製')
  } catch (error) {
    ElMessage.error('複製失敗')
  } finally {
    copyLoading.value = false
  }
}

const downloadQR = () => {
  if (!props.promotion?.qr_code) return
  
  const link = document.createElement('a')
  link.href = props.promotion.qr_code
  link.download = `qr-${props.promotion.title}.png`
  link.click()
}

// 工具方法
const getStatusColor = (status: string) => {
  const colorMap: Record<string, string> = {
    'active': 'success',
    'inactive': 'info',
    'expired': 'danger',
    'paused': 'warning',
  }
  return colorMap[status] || 'info'
}

const getStatusText = (status: string) => {
  const textMap: Record<string, string> = {
    'active': '進行中',
    'inactive': '未啟用',
    'expired': '已過期',
    'paused': '已暫停',
  }
  return textMap[status] || status
}

const getRewardTypeColor = (type: string) => {
  const colorMap: Record<string, string> = {
    'points': 'primary',
    'items': 'success',
    'experience': 'warning',
    'currency': 'danger',
  }
  return colorMap[type] || 'default'
}

const getRewardTypeText = (type: string) => {
  const textMap: Record<string, string> = {
    'points': '積分',
    'items': '道具',
    'experience': '經驗',
    'currency': '貨幣',
  }
  return textMap[type] || type
}

const formatRewardValue = (value: number, type: string) => {
  switch (type) {
    case 'points':
      return `${value} 積分`
    case 'experience':
      return `${value} 經驗值`
    case 'currency':
      return `$${value}`
    case 'items':
      return `${value} 個道具`
    default:
      return String(value)
  }
}

const formatNumber = (num: number) => {
  return num.toLocaleString()
}

const formatPercentage = (num: number) => {
  return `${(num * 100).toFixed(1)}%`
}

const formatDateTime = (dateString: string) => {
  return new Date(dateString).toLocaleString('zh-TW')
}

const getProgressColor = (ratio: number) => {
  if (ratio >= 0.9) return '#f56c6c'
  if (ratio >= 0.7) return '#e6a23c'
  return '#409eff'
}

// 監聽推廣變化，載入統計數據
watch(() => props.promotion, async (newPromotion) => {
  if (newPromotion && props.modelValue) {
    try {
      const stats = await promotionStore.fetchPromotionStatistics(newPromotion.id)
      statistics.value = stats
    } catch (error) {
      console.error('Failed to load promotion statistics:', error)
    }
  }
}, { immediate: true })
</script>

<style scoped lang="scss">
.promotion-detail-dialog {
  .detail-content {
    .info-section {
      margin-bottom: 24px;

      .section-title {
        font-size: 16px;
        font-weight: 600;
        color: var(--el-text-color-primary);
        margin: 0 0 16px 0;
        padding-bottom: 8px;
        border-bottom: 1px solid var(--el-border-color-lighter);
      }

      .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;

        @media (max-width: 600px) {
          grid-template-columns: 1fr;
        }

        .info-item {
          display: flex;
          align-items: center;
          
          .info-label {
            flex: 0 0 100px;
            font-size: 14px;
            color: var(--el-text-color-secondary);
          }

          .info-value {
            flex: 1;
            font-size: 14px;
            color: var(--el-text-color-primary);
            font-weight: 500;
          }
        }
      }

      .description-content {
        background: var(--el-fill-color-light);
        border-radius: 6px;
        padding: 16px;
        font-size: 14px;
        line-height: 1.6;
        color: var(--el-text-color-regular);
      }

      .link-container {
        .link-input {
          :deep(.el-input__inner) {
            font-family: monospace;
            font-size: 12px;
          }
        }
      }

      .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 20px;

        @media (max-width: 768px) {
          grid-template-columns: repeat(2, 1fr);
        }

        @media (max-width: 480px) {
          grid-template-columns: 1fr;
        }

        .stat-card {
          background: var(--el-fill-color-blank);
          border: 1px solid var(--el-border-color-lighter);
          border-radius: 8px;
          padding: 16px;
          display: flex;
          align-items: center;
          gap: 12px;

          .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;

            &.clicks {
              background: var(--el-color-primary);
            }

            &.unique {
              background: var(--el-color-success);
            }

            &.conversions {
              background: var(--el-color-warning);
            }

            &.rate {
              background: var(--el-color-danger);
            }

            &.rewards {
              background: var(--el-color-info);
            }

            &.usage {
              background: #909399;
            }
          }

          .stat-info {
            flex: 1;

            .stat-value {
              font-size: 18px;
              font-weight: 700;
              color: var(--el-text-color-primary);
              line-height: 1;
            }

            .stat-label {
              font-size: 12px;
              color: var(--el-text-color-secondary);
              margin-top: 4px;
            }
          }
        }
      }

      .usage-progress {
        background: var(--el-fill-color-light);
        border-radius: 6px;
        padding: 16px;
        margin-bottom: 16px;

        .progress-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 8px;

          .progress-label {
            font-size: 14px;
            color: var(--el-text-color-regular);
          }

          .progress-percentage {
            font-size: 14px;
            font-weight: 600;
            color: var(--el-text-color-primary);
          }
        }
      }

      .last-activity {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: var(--el-text-color-secondary);
        background: var(--el-fill-color-light);
        border-radius: 6px;
        padding: 12px;
      }

      .qr-container {
        text-align: center;

        .qr-image {
          max-width: 200px;
          border-radius: 8px;
          box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
          margin-bottom: 16px;
        }

        .qr-actions {
          display: flex;
          justify-content: center;
        }
      }
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
  max-height: 60vh;
  overflow-y: auto;
}
</style>