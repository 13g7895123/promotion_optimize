<template>
  <div class="server-selector">
    <!-- 卡片模式 -->
    <template v-if="mode === 'card'">
      <div class="server-grid">
        <div
          v-for="server in availableServers"
          :key="server.id"
          class="server-card"
          :class="{
            'is-selected': selectedServerId === server.id,
            'is-disabled': server.status !== 'active'
          }"
          @click="selectServer(server)"
        >
          <!-- 伺服器圖片 -->
          <div class="server-image">
            <el-image
              :src="server.image_url || '/images/default-server.png'"
              :alt="server.name"
              fit="cover"
              :lazy="true"
            >
              <template #error>
                <div class="image-error">
                  <el-icon><Picture /></el-icon>
                </div>
              </template>
            </el-image>
            
            <!-- 狀態指示器 -->
            <div class="status-indicator" :class="`status-${server.status}`">
              <el-icon v-if="server.status === 'active'"><Check /></el-icon>
              <el-icon v-else-if="server.status === 'maintenance'"><Tools /></el-icon>
              <el-icon v-else><Close /></el-icon>
            </div>
          </div>

          <!-- 伺服器資訊 -->
          <div class="server-info">
            <h4 class="server-name">{{ server.name }}</h4>
            <p class="server-code">{{ server.code }}</p>
            <div class="server-meta">
              <el-tag size="small" :type="getGameTypeColor(server.game_type)">
                {{ server.game_type }}
              </el-tag>
              <el-tag 
                size="small" 
                :type="getStatusColor(server.status)"
              >
                {{ getStatusText(server.status) }}
              </el-tag>
            </div>
            <p v-if="server.description" class="server-description">
              {{ server.description }}
            </p>
          </div>

          <!-- 選中指示器 -->
          <div v-if="selectedServerId === server.id" class="selected-indicator">
            <el-icon><Check /></el-icon>
          </div>
        </div>

        <!-- 空狀態 -->
        <div v-if="availableServers.length === 0" class="empty-state">
          <el-empty description="暫無可用伺服器">
            <el-button type="primary" @click="$emit('create-server')">
              建立伺服器
            </el-button>
          </el-empty>
        </div>
      </div>
    </template>

    <!-- 下拉選單模式 -->
    <template v-else>
      <el-select
        v-model="selectedServerId"
        :placeholder="placeholder"
        :loading="loading"
        :disabled="disabled"
        :clearable="clearable"
        :filterable="filterable"
        :size="size"
        class="server-select"
        @change="handleSelectChange"
      >
        <el-option
          v-for="server in availableServers"
          :key="server.id"
          :label="server.name"
          :value="server.id"
          :disabled="server.status !== 'active'"
        >
          <div class="select-option">
            <div class="option-main">
              <span class="option-name">{{ server.name }}</span>
              <span class="option-code">{{ server.code }}</span>
            </div>
            <div class="option-tags">
              <el-tag size="small" :type="getGameTypeColor(server.game_type)">
                {{ server.game_type }}
              </el-tag>
              <el-tag 
                size="small" 
                :type="getStatusColor(server.status)"
              >
                {{ getStatusText(server.status) }}
              </el-tag>
            </div>
          </div>
        </el-option>
      </el-select>
    </template>
  </div>
</template>

<script setup lang="ts">
import { Picture, Check, Close, Tools } from '@element-plus/icons-vue'
import type { Server } from '~/types'

interface Props {
  modelValue?: number | null
  mode?: 'card' | 'select'
  placeholder?: string
  loading?: boolean
  disabled?: boolean
  clearable?: boolean
  filterable?: boolean
  size?: 'large' | 'default' | 'small'
  showInactive?: boolean
  gameTypeFilter?: string[]
}

interface Emits {
  (e: 'update:modelValue', value: number | null): void
  (e: 'change', server: Server | null): void
  (e: 'create-server'): void
}

const props = withDefaults(defineProps<Props>(), {
  mode: 'card',
  placeholder: '請選擇伺服器',
  loading: false,
  disabled: false,
  clearable: false,
  filterable: true,
  size: 'default',
  showInactive: false,
  gameTypeFilter: undefined,
})

const emit = defineEmits<Emits>()

// 使用 stores
const serverStore = useServerStore()

// 響應式數據
const selectedServerId = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

// 獲取可用伺服器列表
const availableServers = computed(() => {
  let servers = serverStore.myServers

  // 過濾非活躍伺服器
  if (!props.showInactive) {
    servers = servers.filter(s => s.status === 'active')
  }

  // 過濾遊戲類型
  if (props.gameTypeFilter && props.gameTypeFilter.length > 0) {
    servers = servers.filter(s => props.gameTypeFilter!.includes(s.game_type))
  }

  return servers
})

// 獲取選中的伺服器
const selectedServer = computed(() => {
  if (!selectedServerId.value) return null
  return availableServers.value.find(s => s.id === selectedServerId.value) || null
})

// 方法
const selectServer = (server: Server) => {
  if (server.status !== 'active' && !props.showInactive) {
    return
  }
  
  if (props.disabled) {
    return
  }

  selectedServerId.value = server.id
  emit('change', server)
}

const handleSelectChange = (value: number | null) => {
  const server = value ? availableServers.value.find(s => s.id === value) || null : null
  emit('change', server)
}

// 工具方法
const getGameTypeColor = (gameType: string) => {
  const colorMap: Record<string, string> = {
    'minecraft': 'success',
    'terraria': 'warning',
    'csgo': 'danger',
    'gmod': 'info',
    'other': 'default',
  }
  return colorMap[gameType.toLowerCase()] || 'default'
}

const getStatusColor = (status: string) => {
  const colorMap: Record<string, string> = {
    'active': 'success',
    'inactive': 'info',
    'maintenance': 'warning',
  }
  return colorMap[status] || 'info'
}

const getStatusText = (status: string) => {
  const textMap: Record<string, string> = {
    'active': '運行中',
    'inactive': '未運行',
    'maintenance': '維護中',
  }
  return textMap[status] || status
}

// 初始化
onMounted(async () => {
  if (serverStore.myServers.length === 0) {
    await serverStore.fetchMyServers()
  }
})
</script>

<style scoped lang="scss">
.server-selector {
  .server-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;

    @media (max-width: 768px) {
      grid-template-columns: 1fr;
    }
  }

  .server-card {
    position: relative;
    border: 2px solid var(--el-border-color-light);
    border-radius: 8px;
    padding: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: var(--el-bg-color);

    &:hover {
      border-color: var(--el-color-primary);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    &.is-selected {
      border-color: var(--el-color-primary);
      background: var(--el-color-primary-light-9);
    }

    &.is-disabled {
      opacity: 0.6;
      cursor: not-allowed;
      
      &:hover {
        transform: none;
        box-shadow: none;
        border-color: var(--el-border-color-light);
      }
    }
  }

  .server-image {
    position: relative;
    width: 100%;
    height: 120px;
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 12px;

    :deep(.el-image) {
      width: 100%;
      height: 100%;
    }

    .image-error {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      height: 100%;
      background: var(--el-fill-color-light);
      color: var(--el-text-color-placeholder);
      font-size: 24px;
    }

    .status-indicator {
      position: absolute;
      top: 8px;
      right: 8px;
      width: 24px;
      height: 24px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      color: white;

      &.status-active {
        background: var(--el-color-success);
      }

      &.status-inactive {
        background: var(--el-color-info);
      }

      &.status-maintenance {
        background: var(--el-color-warning);
      }
    }
  }

  .server-info {
    .server-name {
      font-size: 16px;
      font-weight: 600;
      margin: 0 0 4px 0;
      color: var(--el-text-color-primary);
    }

    .server-code {
      font-size: 12px;
      color: var(--el-text-color-regular);
      margin: 0 0 8px 0;
      font-family: monospace;
    }

    .server-meta {
      display: flex;
      gap: 4px;
      margin-bottom: 8px;
      flex-wrap: wrap;
    }

    .server-description {
      font-size: 12px;
      color: var(--el-text-color-secondary);
      line-height: 1.4;
      margin: 0;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
  }

  .selected-indicator {
    position: absolute;
    top: -1px;
    right: -1px;
    width: 20px;
    height: 20px;
    background: var(--el-color-primary);
    border-radius: 0 8px 0 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
  }

  .empty-state {
    grid-column: 1 / -1;
    padding: 40px;
    text-align: center;
  }

  .server-select {
    width: 100%;

    .select-option {
      display: flex;
      justify-content: space-between;
      align-items: center;
      width: 100%;

      .option-main {
        flex: 1;
        
        .option-name {
          font-weight: 500;
        }

        .option-code {
          font-size: 12px;
          color: var(--el-text-color-secondary);
          margin-left: 8px;
          font-family: monospace;
        }
      }

      .option-tags {
        display: flex;
        gap: 4px;
      }
    }
  }
}

// 響應式設計
@media (max-width: 480px) {
  .server-selector {
    .server-card {
      padding: 12px;
    }

    .server-image {
      height: 100px;
    }
  }
}
</style>