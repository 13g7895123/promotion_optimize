<template>
  <div class="promotion-card" :class="cardClasses">
    <!-- 卡片頭部 -->
    <div class="card-header">
      <div class="header-main">
        <h3 class="promotion-title">{{ promotion.title }}</h3>
        <div class="promotion-meta">
          <el-tag 
            :type="getStatusColor(promotion.status)" 
            size="small"
          >
            {{ getStatusText(promotion.status) }}
          </el-tag>
          <el-tag 
            :type="getRewardTypeColor(promotion.reward_type)" 
            size="small"
          >
            {{ getRewardTypeText(promotion.reward_type) }}
          </el-tag>
        </div>
      </div>
      
      <!-- 操作按鈕 -->
      <div class="header-actions">
        <el-dropdown 
          trigger="click" 
          @command="handleCommand"
          :disabled="loading"
        >
          <el-button 
            :icon="MoreFilled" 
            circle 
            size="small"
            :loading="loading"
          />
          <template #dropdown>
            <el-dropdown-menu>
              <el-dropdown-item command="view" :icon="View">
                查看詳情
              </el-dropdown-item>
              <el-dropdown-item command="edit" :icon="Edit">
                編輯
              </el-dropdown-item>
              <el-dropdown-item command="duplicate" :icon="CopyDocument">
                複製
              </el-dropdown-item>
              <el-dropdown-item 
                :command="promotion.status === 'active' ? 'pause' : 'activate'"
                :icon="promotion.status === 'active' ? VideoPause : VideoPlay"
              >
                {{ promotion.status === 'active' ? '暫停' : '啟用' }}
              </el-dropdown-item>
              <el-dropdown-item 
                command="delete" 
                :icon="Delete"
                divided
                class="danger-item"
              >
                刪除
              </el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
      </div>
    </div>

    <!-- 卡片內容 -->
    <div class="card-content">
      <!-- 推廣描述 -->
      <p class="promotion-description">{{ promotion.description }}</p>
      
      <!-- 獎勵資訊 -->
      <div class="reward-info">
        <div class="reward-icon">
          <el-icon :size="20"><component :is="getRewardIcon(promotion.reward_type)" /></el-icon>
        </div>
        <div class="reward-details">
          <div class="reward-value">{{ formatRewardValue(promotion.reward_value, promotion.reward_type) }}</div>
          <div v-if="promotion.reward_description" class="reward-description">
            {{ promotion.reward_description }}
          </div>
        </div>
      </div>

      <!-- 統計資訊 -->
      <div class="statistics-grid">
        <div class="stat-item">
          <div class="stat-value">{{ formatNumber(statistics?.total_clicks || 0) }}</div>
          <div class="stat-label">總點擊數</div>
        </div>
        <div class="stat-item">
          <div class="stat-value">{{ formatNumber(statistics?.conversions || 0) }}</div>
          <div class="stat-label">轉換數</div>
        </div>
        <div class="stat-item">
          <div class="stat-value">{{ formatPercentage(statistics?.conversion_rate || 0) }}</div>
          <div class="stat-label">轉換率</div>
        </div>
        <div class="stat-item">
          <div class="stat-value">{{ `${promotion.current_uses}/${promotion.max_uses}` }}</div>
          <div class="stat-label">使用次數</div>
        </div>
      </div>

      <!-- 進度條 -->
      <div class="usage-progress">
        <el-progress 
          :percentage="usagePercentage" 
          :color="getProgressColor(usagePercentage)"
          :show-text="false"
          :stroke-width="6"
        />
        <div class="progress-text">
          已使用 {{ promotion.current_uses }} / {{ promotion.max_uses }} 次
        </div>
      </div>

      <!-- 時間資訊 -->
      <div class="time-info">
        <div class="time-item">
          <el-icon><Calendar /></el-icon>
          <span>開始：{{ formatDate(promotion.start_date) }}</span>
        </div>
        <div class="time-item">
          <el-icon><Calendar /></el-icon>
          <span>結束：{{ formatDate(promotion.end_date) }}</span>
        </div>
      </div>

      <!-- 伺服器資訊 -->
      <div v-if="promotion.server" class="server-info">
        <div class="server-badge">
          <el-icon><Monitor /></el-icon>
          <span>{{ promotion.server.name }}</span>
        </div>
      </div>
    </div>

    <!-- 卡片底部 -->
    <div class="card-footer">
      <div class="footer-left">
        <el-button 
          size="small" 
          @click="copyPromotionLink"
          :loading="copyLoading"
          :icon="Link"
        >
          複製連結
        </el-button>
        <el-button 
          size="small" 
          @click="viewAnalytics"
          :icon="TrendCharts"
        >
          查看分析
        </el-button>
      </div>
      <div class="footer-right">
        <span class="last-update">
          更新於 {{ formatRelativeTime(promotion.updated_at) }}
        </span>
      </div>
    </div>

    <!-- 過期警告 -->
    <div v-if="isExpired" class="expired-overlay">
      <div class="expired-content">
        <el-icon :size="24"><Warning /></el-icon>
        <span>已過期</span>
      </div>
    </div>

    <!-- 即將過期警告 -->
    <div v-else-if="isExpiringSoon" class="expiring-badge">
      <el-icon><Warning /></el-icon>
      <span>即將過期</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  MoreFilled,
  View,
  Edit,
  CopyDocument,
  VideoPause,
  VideoPlay,
  Delete,
  Calendar,
  Monitor,
  Link,
  TrendCharts,
  Warning,
  Trophy,
  Coin,
  Star,
  Present,
} from '@element-plus/icons-vue'
import type { Promotion, PromotionStatistics } from '~/types'

interface Props {
  promotion: Promotion
  statistics?: PromotionStatistics | null
  loading?: boolean
  showServer?: boolean
}

interface Emits {
  (e: 'view', promotion: Promotion): void
  (e: 'edit', promotion: Promotion): void
  (e: 'duplicate', promotion: Promotion): void
  (e: 'toggle-status', promotion: Promotion): void
  (e: 'delete', promotion: Promotion): void
  (e: 'view-analytics', promotion: Promotion): void
}

const props = withDefaults(defineProps<Props>(), {
  statistics: null,
  loading: false,
  showServer: true,
})

const emit = defineEmits<Emits>()

// 響應式數據
const copyLoading = ref(false)

// 計算屬性
const cardClasses = computed(() => ({
  'is-expired': isExpired.value,
  'is-expiring': isExpiringSoon.value,
  'is-inactive': props.promotion.status !== 'active',
}))

const usagePercentage = computed(() => {
  const { current_uses, max_uses } = props.promotion
  return max_uses > 0 ? Math.round((current_uses / max_uses) * 100) : 0
})

const isExpired = computed(() => {
  return new Date(props.promotion.end_date) < new Date()
})

const isExpiringSoon = computed(() => {
  if (isExpired.value) return false
  const now = new Date()
  const endDate = new Date(props.promotion.end_date)
  const sevenDaysLater = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000)
  return endDate <= sevenDaysLater
})

// 方法
const handleCommand = (command: string) => {
  switch (command) {
    case 'view':
      emit('view', props.promotion)
      break
    case 'edit':
      emit('edit', props.promotion)
      break
    case 'duplicate':
      emit('duplicate', props.promotion)
      break
    case 'pause':
    case 'activate':
      emit('toggle-status', props.promotion)
      break
    case 'delete':
      emit('delete', props.promotion)
      break
  }
}

const copyPromotionLink = async () => {
  if (!props.promotion.promotion_link) {
    ElMessage.warning('推廣連結尚未生成')
    return
  }

  copyLoading.value = true
  try {
    await navigator.clipboard.writeText(props.promotion.promotion_link)
    ElMessage.success('推廣連結已複製到剪貼簿')
  } catch (error) {
    ElMessage.error('複製失敗，請手動複製')
  } finally {
    copyLoading.value = false
  }
}

const viewAnalytics = () => {
  emit('view-analytics', props.promotion)
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

const getRewardIcon = (type: string) => {
  const iconMap: Record<string, any> = {
    'points': Star,
    'items': Present,
    'experience': Trophy,
    'currency': Coin,
  }
  return iconMap[type] || Present
}

const formatRewardValue = (value: number, type: string) => {
  switch (type) {
    case 'points':
      return `${value} 積分`
    case 'experience':
      return `${value} 經驗`
    case 'currency':
      return `$${value}`
    case 'items':
      return `${value} 個道具`
    default:
      return String(value)
  }
}

const getProgressColor = (percentage: number) => {
  if (percentage >= 90) return '#f56c6c'
  if (percentage >= 70) return '#e6a23c'
  return '#409eff'
}

const formatNumber = (num: number) => {
  return num.toLocaleString()
}

const formatPercentage = (num: number) => {
  return `${(num * 100).toFixed(1)}%`
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('zh-TW')
}

const formatRelativeTime = (dateString: string) => {
  const date = new Date(dateString)
  const now = new Date()
  const diff = now.getTime() - date.getTime()
  
  const minutes = Math.floor(diff / (1000 * 60))
  const hours = Math.floor(diff / (1000 * 60 * 60))
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))
  
  if (minutes < 60) return `${minutes} 分鐘前`
  if (hours < 24) return `${hours} 小時前`
  return `${days} 天前`
}
</script>

<style scoped lang="scss">
.promotion-card {
  position: relative;
  border: 1px solid var(--el-border-color-light);
  border-radius: 8px;
  background: var(--el-bg-color);
  transition: all 0.3s ease;
  overflow: hidden;

  &:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
  }

  &.is-expired {
    opacity: 0.7;
    border-color: var(--el-color-danger);
  }

  &.is-expiring {
    border-color: var(--el-color-warning);
  }

  &.is-inactive {
    opacity: 0.8;
  }

  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 16px 16px 0;

    .header-main {
      flex: 1;
      min-width: 0;

      .promotion-title {
        font-size: 18px;
        font-weight: 600;
        margin: 0 0 8px 0;
        color: var(--el-text-color-primary);
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
      }

      .promotion-meta {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
      }
    }

    .header-actions {
      margin-left: 12px;
    }
  }

  .card-content {
    padding: 16px;

    .promotion-description {
      color: var(--el-text-color-regular);
      line-height: 1.5;
      margin: 0 0 16px 0;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .reward-info {
      display: flex;
      align-items: center;
      padding: 12px;
      background: var(--el-fill-color-lighter);
      border-radius: 6px;
      margin-bottom: 16px;

      .reward-icon {
        margin-right: 12px;
        color: var(--el-color-primary);
      }

      .reward-details {
        flex: 1;

        .reward-value {
          font-size: 16px;
          font-weight: 600;
          color: var(--el-text-color-primary);
        }

        .reward-description {
          font-size: 12px;
          color: var(--el-text-color-secondary);
          margin-top: 2px;
        }
      }
    }

    .statistics-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 12px;
      margin-bottom: 16px;

      @media (max-width: 768px) {
        grid-template-columns: repeat(2, 1fr);
      }

      .stat-item {
        text-align: center;

        .stat-value {
          font-size: 18px;
          font-weight: 600;
          color: var(--el-color-primary);
        }

        .stat-label {
          font-size: 12px;
          color: var(--el-text-color-secondary);
          margin-top: 2px;
        }
      }
    }

    .usage-progress {
      margin-bottom: 16px;

      .progress-text {
        font-size: 12px;
        color: var(--el-text-color-secondary);
        text-align: center;
        margin-top: 4px;
      }
    }

    .time-info {
      display: flex;
      flex-direction: column;
      gap: 4px;
      margin-bottom: 12px;

      .time-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: var(--el-text-color-secondary);

        .el-icon {
          font-size: 14px;
        }
      }
    }

    .server-info {
      .server-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 8px;
        background: var(--el-color-primary-light-9);
        color: var(--el-color-primary);
        border-radius: 4px;
        font-size: 12px;
      }
    }
  }

  .card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    border-top: 1px solid var(--el-border-color-lighter);
    background: var(--el-fill-color-blank);

    .footer-left {
      display: flex;
      gap: 8px;
    }

    .footer-right {
      .last-update {
        font-size: 12px;
        color: var(--el-text-color-secondary);
      }
    }
  }

  .expired-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;

    .expired-content {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 8px;
      font-size: 18px;
      font-weight: 600;
    }
  }

  .expiring-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: var(--el-color-warning);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 4px;
  }
}

// 下拉選單危險項目樣式
:deep(.danger-item) {
  color: var(--el-color-danger);
}

// 響應式設計
@media (max-width: 480px) {
  .promotion-card {
    .card-header {
      padding: 12px 12px 0;

      .promotion-title {
        font-size: 16px;
      }
    }

    .card-content {
      padding: 12px;

      .statistics-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;

        .stat-item .stat-value {
          font-size: 16px;
        }
      }
    }

    .card-footer {
      padding: 8px 12px;
      flex-direction: column;
      gap: 8px;
      align-items: flex-start;

      .footer-left {
        width: 100%;
      }
    }
  }
}
</style>