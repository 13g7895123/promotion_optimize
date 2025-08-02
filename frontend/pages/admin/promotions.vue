<template>
  <div class="promotion-records-page">
    <!-- 頁面標題 -->
    <div class="page-header">
      <div class="header-left">
        <h1 class="page-title">推廣記錄</h1>
        <p class="page-description">
          管理您的推廣活動，查看推廣效果和統計數據
        </p>
      </div>
      
      <div class="header-right">
        <el-button 
          type="primary" 
          :icon="Plus"
          @click="createPromotion"
        >
          建立推廣
        </el-button>
      </div>
    </div>

    <!-- 篩選工具列 -->
    <div class="filter-toolbar">
      <el-card class="filter-card">
        <el-form 
          :model="filters" 
          :inline="true" 
          class="filter-form"
          @submit.prevent="handleSearch"
        >
          <el-form-item label="伺服器">
            <ServerSelector
              v-model="filters.server_id"
              mode="select"
              placeholder="所有伺服器"
              clearable
              style="width: 200px"
              @change="handleFilterChange"
            />
          </el-form-item>
          
          <el-form-item label="狀態">
            <el-select 
              v-model="filters.status" 
              placeholder="所有狀態"
              clearable
              style="width: 120px"
              @change="handleFilterChange"
            >
              <el-option label="進行中" value="active" />
              <el-option label="已暫停" value="paused" />
              <el-option label="未啟用" value="inactive" />
              <el-option label="已過期" value="expired" />
            </el-select>
          </el-form-item>

          <el-form-item label="時間範圍">
            <el-date-picker
              v-model="dateRange"
              type="daterange"
              range-separator="至"
              start-placeholder="開始日期"
              end-placeholder="結束日期"
              format="YYYY-MM-DD"
              value-format="YYYY-MM-DD"
              style="width: 240px"
              @change="handleDateRangeChange"
            />
          </el-form-item>

          <el-form-item label="搜索">
            <el-input
              v-model="filters.search"
              placeholder="推廣標題、描述"
              :prefix-icon="Search"
              style="width: 200px"
              @keyup.enter="handleSearch"
              @clear="handleSearch"
              clearable
            />
          </el-form-item>

          <el-form-item>
            <el-button 
              type="primary" 
              :icon="Search"
              @click="handleSearch"
              :loading="loading"
            >
              搜索
            </el-button>
            <el-button 
              :icon="Refresh"
              @click="handleReset"
            >
              重置
            </el-button>
          </el-form-item>
        </el-form>
      </el-card>
    </div>

    <!-- 統計面板 -->
    <div class="stats-panels">
      <el-row :gutter="16">
        <el-col :span="6">
          <el-card class="stats-card">
            <div class="stat-content">
              <div class="stat-icon active">
                <el-icon><PromotionIcon /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ totalStats.active_promotions }}</div>
                <div class="stat-label">進行中</div>
              </div>
            </div>
          </el-card>
        </el-col>
        
        <el-col :span="6">
          <el-card class="stats-card">
            <div class="stat-content">
              <div class="stat-icon clicks">
                <el-icon><View /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ formatNumber(totalStats.total_clicks) }}</div>
                <div class="stat-label">總點擊數</div>
              </div>
            </div>
          </el-card>
        </el-col>
        
        <el-col :span="6">
          <el-card class="stats-card">
            <div class="stat-content">
              <div class="stat-icon conversions">
                <el-icon><Trophy /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ formatNumber(totalStats.total_conversions) }}</div>
                <div class="stat-label">總轉換數</div>
              </div>
            </div>
          </el-card>
        </el-col>
        
        <el-col :span="6">
          <el-card class="stats-card">
            <div class="stat-content">
              <div class="stat-icon rate">
                <el-icon><TrendCharts /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ formatPercentage(totalStats.avg_conversion_rate) }}</div>
                <div class="stat-label">平均轉換率</div>
              </div>
            </div>
          </el-card>
        </el-col>
      </el-row>
    </div>

    <!-- 批量操作工具列 -->
    <div v-if="selectedPromotions.length > 0" class="batch-toolbar">
      <div class="selected-info">
        已選擇 {{ selectedPromotions.length }} 個推廣活動
      </div>
      <div class="batch-actions">
        <el-button 
          size="small" 
          @click="batchActivate"
          :loading="batchLoading"
        >
          批量啟用
        </el-button>
        <el-button 
          size="small" 
          @click="batchPause"
          :loading="batchLoading"
        >
          批量暫停
        </el-button>
        <el-button 
          size="small" 
          type="danger"
          @click="batchDelete"
          :loading="batchLoading"
        >
          批量刪除
        </el-button>
      </div>
    </div>

    <!-- 推廣列表 -->
    <div class="promotions-list">
      <!-- 列表視圖切換 -->
      <div class="view-controls">
        <el-radio-group v-model="viewMode" size="small">
          <el-radio-button label="card">卡片視圖</el-radio-button>
          <el-radio-button label="table">表格視圖</el-radio-button>
        </el-radio-group>
        
        <div class="list-actions">
          <el-dropdown @command="handleExport">
            <el-button size="small" :icon="Download">
              匯出資料
              <el-icon class="el-icon--right"><ArrowDown /></el-icon>
            </el-button>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item command="csv">匯出 CSV</el-dropdown-item>
                <el-dropdown-item command="excel">匯出 Excel</el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
          
          <el-button 
            size="small" 
            :icon="Refresh"
            @click="refreshList"
            :loading="loading"
          >
            重新整理
          </el-button>
        </div>
      </div>

      <!-- 卡片視圖 -->
      <div v-if="viewMode === 'card'" class="cards-container">
        <!-- 載入骨架屏 -->
        <div v-if="loading" class="skeleton-container">
          <div v-for="i in 6" :key="i" class="skeleton-card">
            <el-skeleton animated>
              <template #template>
                <div class="skeleton-header">
                  <el-skeleton-item variant="text" style="width: 60%" />
                  <el-skeleton-item variant="text" style="width: 30%" />
                </div>
                <el-skeleton-item variant="text" style="width: 100%; margin: 12px 0" />
                <el-skeleton-item variant="text" style="width: 80%; margin: 8px 0" />
                <div class="skeleton-stats">
                  <el-skeleton-item variant="text" style="width: 20%" />
                  <el-skeleton-item variant="text" style="width: 20%" />
                  <el-skeleton-item variant="text" style="width: 20%" />
                </div>
              </template>
            </el-skeleton>
          </div>
        </div>

        <!-- 推廣卡片 -->
        <div v-else-if="promotions.length > 0" class="promotions-grid">
          <div 
            v-for="promotion in promotions" 
            :key="promotion.id"
            class="promotion-item"
          >
            <!-- 選擇框 -->
            <div class="selection-checkbox">
              <el-checkbox 
                :model-value="selectedPromotions.includes(promotion.id)"
                @change="(checked) => handlePromotionSelect(promotion.id, checked)"
              />
            </div>
            
            <PromotionCard
              :promotion="promotion"
              :statistics="getPromotionStatistics(promotion.id)"
              @view="handleViewPromotion"
              @edit="handleEditPromotion"
              @duplicate="handleDuplicatePromotion"
              @toggle-status="handleToggleStatus"
              @delete="handleDeletePromotion"
              @view-analytics="handleViewAnalytics"
            />
          </div>
        </div>

        <!-- 空狀態 -->
        <div v-else class="empty-state">
          <el-empty 
            :image-size="120"
            description="暫無推廣記錄"
          >
            <el-button type="primary" @click="createPromotion">
              建立第一個推廣活動
            </el-button>
          </el-empty>
        </div>
      </div>

      <!-- 表格視圖 -->
      <div v-else class="table-container">
        <el-table
          v-loading="loading"
          :data="promotions"
          style="width: 100%"
          @selection-change="handleSelectionChange"
          @sort-change="handleSortChange"
        >
          <el-table-column type="selection" width="55" />
          
          <el-table-column prop="title" label="推廣標題" min-width="200" sortable>
            <template #default="{ row }">
              <div class="table-title">
                <span class="title-text">{{ row.title }}</span>
                <div class="title-tags">
                  <el-tag size="small" :type="getStatusColor(row.status)">
                    {{ getStatusText(row.status) }}
                  </el-tag>
                </div>
              </div>
            </template>
          </el-table-column>

          <el-table-column prop="server" label="伺服器" width="140">
            <template #default="{ row }">
              <div v-if="row.server" class="server-info">
                <span>{{ row.server.name }}</span>
              </div>
            </template>
          </el-table-column>

          <el-table-column prop="reward_type" label="獎勵" width="120">
            <template #default="{ row }">
              <div class="reward-info">
                <el-icon><component :is="getRewardIcon(row.reward_type)" /></el-icon>
                <span>{{ formatRewardValue(row.reward_value, row.reward_type) }}</span>
              </div>
            </template>
          </el-table-column>

          <el-table-column label="統計數據" width="200">
            <template #default="{ row }">
              <div class="stats-info">
                <div class="stat-item">
                  <span class="stat-label">點擊:</span>
                  <span class="stat-value">{{ getPromotionStatistics(row.id)?.total_clicks || 0 }}</span>
                </div>
                <div class="stat-item">
                  <span class="stat-label">轉換:</span>
                  <span class="stat-value">{{ getPromotionStatistics(row.id)?.conversions || 0 }}</span>
                </div>
                <div class="stat-item">
                  <span class="stat-label">轉換率:</span>
                  <span class="stat-value">{{ formatPercentage(getPromotionStatistics(row.id)?.conversion_rate || 0) }}</span>
                </div>
              </div>
            </template>
          </el-table-column>

          <el-table-column prop="usage" label="使用情況" width="120">
            <template #default="{ row }">
              <div class="usage-info">
                <div class="usage-text">{{ row.current_uses }}/{{ row.max_uses }}</div>
                <el-progress 
                  :percentage="Math.round((row.current_uses / row.max_uses) * 100)"
                  :show-text="false"
                  :stroke-width="4"
                />
              </div>
            </template>
          </el-table-column>

          <el-table-column prop="end_date" label="結束時間" width="120" sortable>
            <template #default="{ row }">
              <div class="date-info">
                {{ formatDate(row.end_date) }}
              </div>
            </template>
          </el-table-column>

          <el-table-column label="操作" width="140" fixed="right">
            <template #default="{ row }">
              <div class="action-buttons">
                <el-button 
                  size="small" 
                  type="primary" 
                  link
                  @click="handleViewPromotion(row)"
                >
                  查看
                </el-button>
                <el-button 
                  size="small" 
                  type="primary" 
                  link
                  @click="handleEditPromotion(row)"
                >
                  編輯
                </el-button>
                <el-dropdown @command="(command) => handleTableCommand(command, row)">
                  <el-button size="small" type="primary" link>
                    更多<el-icon class="el-icon--right"><ArrowDown /></el-icon>
                  </el-button>
                  <template #dropdown>
                    <el-dropdown-menu>
                      <el-dropdown-item :command="`analytics-${row.id}`">查看分析</el-dropdown-item>
                      <el-dropdown-item :command="`duplicate-${row.id}`">複製</el-dropdown-item>
                      <el-dropdown-item 
                        :command="`toggle-${row.id}`"
                      >
                        {{ row.status === 'active' ? '暫停' : '啟用' }}
                      </el-dropdown-item>
                      <el-dropdown-item 
                        :command="`delete-${row.id}`"
                        divided
                      >
                        刪除
                      </el-dropdown-item>
                    </el-dropdown-menu>
                  </template>
                </el-dropdown>
              </div>
            </template>
          </el-table-column>
        </el-table>
      </div>

      <!-- 分頁 -->
      <div v-if="totalPromotions > 0" class="pagination-container">
        <el-pagination
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :page-sizes="[10, 20, 50, 100]"
          layout="total, sizes, prev, pager, next, jumper"
          :total="totalPromotions"
          @size-change="handlePageSizeChange"
          @current-change="handlePageChange"
        />
      </div>
    </div>

    <!-- 詳情對話框 -->
    <PromotionDetailDialog
      v-model="detailDialogVisible"
      :promotion="selectedPromotion"
      @edit="handleEditPromotion"
      @delete="handleDeletePromotion"
    />

    <!-- 分析對話框 -->
    <PromotionAnalyticsDialog
      v-model="analyticsDialogVisible"
      :promotion="selectedPromotion"
    />
  </div>
</template>

<script setup lang="ts">
import {
  Plus,
  Search,
  Refresh,
  Download,
  ArrowDown,
  Promotion as PromotionIcon,
  View,
  Trophy,
  TrendCharts,
  Star,
  Gift,
  Coin,
} from '@element-plus/icons-vue'
import type { Promotion, PromotionFilter, PromotionStatistics } from '~/types'

// 頁面元數據
definePageMeta({
  layout: 'admin',
  middleware: 'auth'
})

// 使用 stores
const promotionStore = usePromotionStore()
const serverStore = useServerStore()

// 響應式數據
const loading = ref(false)
const batchLoading = ref(false)
const viewMode = ref<'card' | 'table'>('card')

// 篩選和搜索
const filters = ref<PromotionFilter>({})
const dateRange = ref<[string, string] | null>(null)

// 分頁
const currentPage = ref(1)
const pageSize = ref(20)

// 選擇狀態
const selectedPromotions = ref<number[]>([])

// 對話框狀態
const detailDialogVisible = ref(false)
const analyticsDialogVisible = ref(false)
const selectedPromotion = ref<Promotion | null>(null)

// 計算屬性
const promotions = computed(() => promotionStore.promotions)
const totalPromotions = computed(() => promotionStore.totalPromotions)

const totalStats = computed(() => {
  const activePromotions = promotions.value.filter(p => p.status === 'active').length
  const totalClicks = promotions.value.reduce((sum, p) => {
    const stats = getPromotionStatistics(p.id)
    return sum + (stats?.total_clicks || 0)
  }, 0)
  const totalConversions = promotions.value.reduce((sum, p) => {
    const stats = getPromotionStatistics(p.id)
    return sum + (stats?.conversions || 0)
  }, 0)
  const avgConversionRate = totalClicks > 0 ? totalConversions / totalClicks : 0

  return {
    active_promotions: activePromotions,
    total_clicks: totalClicks,
    total_conversions: totalConversions,
    avg_conversion_rate: avgConversionRate,
  }
})

// 方法
const fetchPromotions = async () => {
  loading.value = true
  try {
    await promotionStore.fetchPromotions(currentPage.value, filters.value, pageSize.value)
  } catch (error: any) {
    ElMessage.error('載入推廣記錄失敗：' + error.message)
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  currentPage.value = 1
  fetchPromotions()
}

const handleReset = () => {
  filters.value = {}
  dateRange.value = null
  currentPage.value = 1
  fetchPromotions()
}

const handleFilterChange = () => {
  currentPage.value = 1
  fetchPromotions()
}

const handleDateRangeChange = (dates: [string, string] | null) => {
  if (dates) {
    filters.value.start_date = dates[0]
    filters.value.end_date = dates[1]
  } else {
    delete filters.value.start_date
    delete filters.value.end_date
  }
  handleFilterChange()
}

const handlePageChange = (page: number) => {
  currentPage.value = page
  fetchPromotions()
}

const handlePageSizeChange = (size: number) => {
  pageSize.value = size
  currentPage.value = 1
  fetchPromotions()
}

const refreshList = () => {
  fetchPromotions()
}

const createPromotion = () => {
  navigateTo('/promotion/tools')
}

// 推廣操作
const handleViewPromotion = (promotion: Promotion) => {
  selectedPromotion.value = promotion
  detailDialogVisible.value = true
}

const handleEditPromotion = (promotion: Promotion) => {
  // 導航到編輯頁面
  navigateTo(`/promotion/edit/${promotion.id}`)
}

const handleDuplicatePromotion = async (promotion: Promotion) => {
  try {
    await promotionStore.duplicatePromotion(promotion.id)
    ElMessage.success('推廣活動已複製')
    refreshList()
  } catch (error: any) {
    ElMessage.error('複製失敗：' + error.message)
  }
}

const handleToggleStatus = async (promotion: Promotion) => {
  try {
    const newStatus = promotion.status === 'active' ? 'paused' : 'active'
    await promotionStore.togglePromotionStatus(promotion.id, newStatus)
    ElMessage.success(`推廣活動已${newStatus === 'active' ? '啟用' : '暫停'}`)
  } catch (error: any) {
    ElMessage.error('操作失敗：' + error.message)
  }
}

const handleDeletePromotion = (promotion: Promotion) => {
  ElMessageBox.confirm(
    `確定要刪除推廣活動「${promotion.title}」嗎？`,
    '確認刪除',
    {
      confirmButtonText: '確定',
      cancelButtonText: '取消',
      type: 'warning',
    }
  ).then(async () => {
    try {
      await promotionStore.deletePromotion(promotion.id)
      ElMessage.success('推廣活動已刪除')
    } catch (error: any) {
      ElMessage.error('刪除失敗：' + error.message)
    }
  })
}

const handleViewAnalytics = (promotion: Promotion) => {
  selectedPromotion.value = promotion
  analyticsDialogVisible.value = true
}

// 批量操作
const handlePromotionSelect = (id: number, checked: boolean) => {
  if (checked) {
    selectedPromotions.value.push(id)
  } else {
    const index = selectedPromotions.value.indexOf(id)
    if (index > -1) {
      selectedPromotions.value.splice(index, 1)
    }
  }
}

const handleSelectionChange = (selection: Promotion[]) => {
  selectedPromotions.value = selection.map(p => p.id)
}

const batchActivate = async () => {
  batchLoading.value = true
  try {
    await promotionStore.batchOperation(selectedPromotions.value, 'activate')
    ElMessage.success('批量啟用成功')
    selectedPromotions.value = []
  } catch (error: any) {
    ElMessage.error('批量操作失敗：' + error.message)
  } finally {
    batchLoading.value = false
  }
}

const batchPause = async () => {
  batchLoading.value = true
  try {
    await promotionStore.batchOperation(selectedPromotions.value, 'pause')
    ElMessage.success('批量暫停成功')
    selectedPromotions.value = []
  } catch (error: any) {
    ElMessage.error('批量操作失敗：' + error.message)
  } finally {
    batchLoading.value = false
  }
}

const batchDelete = () => {
  ElMessageBox.confirm(
    `確定要刪除選中的 ${selectedPromotions.value.length} 個推廣活動嗎？`,
    '確認批量刪除',
    {
      confirmButtonText: '確定',
      cancelButtonText: '取消',
      type: 'warning',
    }
  ).then(async () => {
    batchLoading.value = true
    try {
      await promotionStore.batchOperation(selectedPromotions.value, 'delete')
      ElMessage.success('批量刪除成功')
      selectedPromotions.value = []
    } catch (error: any) {
      ElMessage.error('批量操作失敗：' + error.message)
    } finally {
      batchLoading.value = false
    }
  })
}

// 表格操作
const handleSortChange = ({ prop, order }: { prop: string; order: string }) => {
  // 實現排序邏輯
  console.log('Sort change:', prop, order)
}

const handleTableCommand = (command: string, promotion: Promotion) => {
  const [action, id] = command.split('-')
  const promotionId = parseInt(id)
  
  switch (action) {
    case 'analytics':
      handleViewAnalytics(promotion)
      break
    case 'duplicate':
      handleDuplicatePromotion(promotion)
      break
    case 'toggle':
      handleToggleStatus(promotion)
      break
    case 'delete':
      handleDeletePromotion(promotion)
      break
  }
}

// 匯出功能
const handleExport = async (format: 'csv' | 'excel') => {
  try {
    const promotionService = usePromotionService()
    const blob = await promotionService.exportPromotions(filters.value, format)
    
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `promotions_${new Date().toISOString().split('T')[0]}.${format === 'csv' ? 'csv' : 'xlsx'}`
    link.click()
    
    window.URL.revokeObjectURL(url)
    ElMessage.success('資料匯出成功')
  } catch (error: any) {
    ElMessage.error('匯出失敗：' + error.message)
  }
}

// 工具方法
const getPromotionStatistics = (promotionId: number): PromotionStatistics | null => {
  return promotionStore.statistics[promotionId] || null
}

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

const getRewardIcon = (type: string) => {
  const iconMap: Record<string, any> = {
    'points': Star,
    'items': Gift,
    'experience': Trophy,
    'currency': Coin,
  }
  return iconMap[type] || Gift
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

const formatNumber = (num: number) => {
  return num.toLocaleString()
}

const formatPercentage = (num: number) => {
  return `${(num * 100).toFixed(1)}%`
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('zh-TW')
}

// 生命週期
onMounted(async () => {
  // 載入伺服器列表
  if (serverStore.myServers.length === 0) {
    await serverStore.fetchMyServers()
  }
  
  // 載入推廣列表
  await fetchPromotions()
  
  // 載入統計數據
  for (const promotion of promotions.value) {
    try {
      await promotionStore.fetchPromotionStatistics(promotion.id)
    } catch (error) {
      console.error(`Failed to load statistics for promotion ${promotion.id}:`, error)
    }
  }
})
</script>

<style scoped lang="scss">
.promotion-records-page {
  max-width: 1400px;
  margin: 0 auto;
  padding: 24px;

  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 24px;

    @media (max-width: 768px) {
      flex-direction: column;
      gap: 16px;
      align-items: stretch;
    }

    .header-left {
      flex: 1;

      .page-title {
        font-size: 28px;
        font-weight: 700;
        color: var(--el-text-color-primary);
        margin: 0 0 8px 0;
      }

      .page-description {
        font-size: 14px;
        color: var(--el-text-color-secondary);
        margin: 0;
      }
    }

    .header-right {
      @media (max-width: 768px) {
        width: 100%;
        
        .el-button {
          width: 100%;
        }
      }
    }
  }

  .filter-toolbar {
    margin-bottom: 24px;

    .filter-card {
      .filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;

        @media (max-width: 768px) {
          .el-form-item {
            margin-bottom: 16px;
          }
        }
      }
    }
  }

  .stats-panels {
    margin-bottom: 24px;

    .stats-card {
      .stat-content {
        display: flex;
        align-items: center;
        gap: 16px;

        .stat-icon {
          width: 48px;
          height: 48px;
          border-radius: 8px;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 24px;
          color: white;

          &.active {
            background: var(--el-color-primary);
          }

          &.clicks {
            background: var(--el-color-success);
          }

          &.conversions {
            background: var(--el-color-warning);
          }

          &.rate {
            background: var(--el-color-danger);
          }
        }

        .stat-info {
          flex: 1;

          .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--el-text-color-primary);
            line-height: 1;
          }

          .stat-label {
            font-size: 14px;
            color: var(--el-text-color-secondary);
            margin-top: 4px;
          }
        }
      }
    }
  }

  .batch-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--el-color-primary-light-9);
    border: 1px solid var(--el-color-primary-light-5);
    border-radius: 6px;
    padding: 12px 16px;
    margin-bottom: 16px;

    .selected-info {
      color: var(--el-color-primary);
      font-weight: 500;
    }

    .batch-actions {
      display: flex;
      gap: 8px;
    }
  }

  .promotions-list {
    .view-controls {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;

      @media (max-width: 768px) {
        flex-direction: column;
        gap: 12px;
        align-items: stretch;
      }

      .list-actions {
        display: flex;
        gap: 8px;

        @media (max-width: 768px) {
          width: 100%;
          
          .el-button,
          .el-dropdown {
            flex: 1;
          }
        }
      }
    }

    .cards-container {
      .skeleton-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
        gap: 16px;

        @media (max-width: 768px) {
          grid-template-columns: 1fr;
        }

        .skeleton-card {
          background: var(--el-bg-color);
          border-radius: 8px;
          padding: 16px;
          border: 1px solid var(--el-border-color-lighter);

          .skeleton-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
          }

          .skeleton-stats {
            display: flex;
            justify-content: space-between;
            margin-top: 16px;
          }
        }
      }

      .promotions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
        gap: 16px;

        @media (max-width: 768px) {
          grid-template-columns: 1fr;
        }

        .promotion-item {
          position: relative;

          .selection-checkbox {
            position: absolute;
            top: 12px;
            left: 12px;
            z-index: 1;
          }
        }
      }

      .empty-state {
        text-align: center;
        padding: 60px 20px;
      }
    }

    .table-container {
      background: var(--el-bg-color);
      border-radius: 8px;
      overflow: hidden;

      .table-title {
        .title-text {
          font-weight: 500;
          color: var(--el-text-color-primary);
          display: block;
          margin-bottom: 4px;
        }

        .title-tags {
          display: flex;
          gap: 4px;
        }
      }

      .server-info {
        font-size: 14px;
        color: var(--el-text-color-regular);
      }

      .reward-info {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 14px;
      }

      .stats-info {
        font-size: 12px;

        .stat-item {
          display: flex;
          justify-content: space-between;
          margin-bottom: 2px;

          .stat-label {
            color: var(--el-text-color-secondary);
          }

          .stat-value {
            color: var(--el-text-color-primary);
            font-weight: 500;
          }
        }
      }

      .usage-info {
        .usage-text {
          font-size: 12px;
          color: var(--el-text-color-regular);
          margin-bottom: 4px;
          text-align: center;
        }
      }

      .date-info {
        font-size: 12px;
        color: var(--el-text-color-secondary);
      }

      .action-buttons {
        display: flex;
        align-items: center;
        gap: 8px;
      }
    }

    .pagination-container {
      display: flex;
      justify-content: center;
      margin-top: 24px;
    }
  }
}

// 響應式設計優化
@media (max-width: 480px) {
  .promotion-records-page {
    padding: 16px;

    .stats-panels {
      :deep(.el-col) {
        margin-bottom: 12px;
      }
    }

    .filter-toolbar {
      .filter-form {
        :deep(.el-form-item) {
          width: 100%;
          margin-right: 0;
        }
      }
    }
  }
}
</style>