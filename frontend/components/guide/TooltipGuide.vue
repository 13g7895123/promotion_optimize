<template>
  <el-popover
    :visible="visible"
    :placement="placement"
    :width="width"
    :trigger="trigger"
    :show-arrow="showArrow"
    :popper-class="`tooltip-guide ${popperClass}`"
    :popper-style="{ zIndex }"
    @show="handleShow"
    @hide="handleHide"
  >
    <template #reference>
      <slot name="reference" />
    </template>

    <div class="tooltip-guide-content">
      <!-- Header -->
      <div v-if="title || showClose" class="tooltip-header">
        <h4 v-if="title" class="tooltip-title">{{ title }}</h4>
        <el-button 
          v-if="showClose"
          @click="handleClose"
          :icon="Close"
          text
          size="small"
          class="close-btn"
        />
      </div>

      <!-- Content -->
      <div class="tooltip-body">
        <p v-if="description" class="tooltip-description">
          {{ description }}
        </p>
        
        <div v-if="$slots.default" class="tooltip-custom-content">
          <slot />
        </div>
      </div>

      <!-- Actions -->
      <div v-if="showActions" class="tooltip-actions">
        <el-button 
          v-if="showSkip"
          @click="handleSkip"
          text
          size="small"
        >
          {{ skipText }}
        </el-button>
        
        <el-button 
          v-if="showConfirm"
          @click="handleConfirm"
          type="primary"
          size="small"
        >
          {{ confirmText }}
        </el-button>
      </div>
    </div>
  </el-popover>
</template>

<script setup lang="ts">
import { Close } from '@element-plus/icons-vue'

interface Props {
  visible?: boolean
  title?: string
  description?: string
  placement?: 'top' | 'bottom' | 'left' | 'right' | 'top-start' | 'top-end' | 'bottom-start' | 'bottom-end' | 'left-start' | 'left-end' | 'right-start' | 'right-end'
  width?: string | number
  trigger?: 'hover' | 'click' | 'focus' | 'manual'
  showArrow?: boolean
  showClose?: boolean
  showActions?: boolean
  showSkip?: boolean
  showConfirm?: boolean
  skipText?: string
  confirmText?: string
  popperClass?: string
  zIndex?: number
  autoHide?: boolean
  autoHideDelay?: number
}

interface Emits {
  (e: 'update:visible', value: boolean): void
  (e: 'show'): void
  (e: 'hide'): void
  (e: 'close'): void
  (e: 'skip'): void
  (e: 'confirm'): void
}

const props = withDefaults(defineProps<Props>(), {
  visible: false,
  placement: 'top',
  width: 250,
  trigger: 'manual',
  showArrow: true,
  showClose: false,
  showActions: false,
  showSkip: false,
  showConfirm: true,
  skipText: '跳過',
  confirmText: '知道了',
  popperClass: '',
  zIndex: 2000,
  autoHide: false,
  autoHideDelay: 3000
})

const emit = defineEmits<Emits>()

// Auto hide timer
let autoHideTimer: NodeJS.Timeout | null = null

const handleShow = () => {
  emit('show')
  
  // Set auto hide timer
  if (props.autoHide) {
    autoHideTimer = setTimeout(() => {
      handleClose()
    }, props.autoHideDelay)
  }
}

const handleHide = () => {
  emit('hide')
  
  // Clear auto hide timer
  if (autoHideTimer) {
    clearTimeout(autoHideTimer)
    autoHideTimer = null
  }
}

const handleClose = () => {
  emit('update:visible', false)
  emit('close')
}

const handleSkip = () => {
  emit('update:visible', false)
  emit('skip')
}

const handleConfirm = () => {
  emit('update:visible', false)
  emit('confirm')
}

// Clear timer on unmount
onUnmounted(() => {
  if (autoHideTimer) {
    clearTimeout(autoHideTimer)
  }
})
</script>

<style>
.tooltip-guide {
  max-width: 350px;
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  border: 1px solid var(--el-border-color-light);
}

.tooltip-guide .el-popover {
  padding: 0;
}

.tooltip-guide-content {
  padding: 16px;
}

.tooltip-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 12px;
}

.tooltip-title {
  margin: 0;
  font-size: 14px;
  font-weight: 600;
  color: var(--el-text-color-primary);
  line-height: 1.4;
}

.close-btn {
  margin-left: 8px;
  padding: 0;
  min-height: auto;
  color: var(--el-text-color-secondary);
}

.close-btn:hover {
  color: var(--el-text-color-primary);
}

.tooltip-body {
  margin-bottom: 12px;
}

.tooltip-description {
  margin: 0;
  font-size: 13px;
  line-height: 1.5;
  color: var(--el-text-color-regular);
}

.tooltip-custom-content {
  margin-top: 8px;
}

.tooltip-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  padding-top: 12px;
  border-top: 1px solid var(--el-border-color-lighter);
}

/* Variants */
.tooltip-guide.info {
  border-left: 4px solid var(--el-color-primary);
}

.tooltip-guide.success {
  border-left: 4px solid var(--el-color-success);
}

.tooltip-guide.warning {
  border-left: 4px solid var(--el-color-warning);
}

.tooltip-guide.error {
  border-left: 4px solid var(--el-color-danger);
}

/* Animation */
.tooltip-guide .el-popover {
  animation: tooltipFadeIn 0.3s ease;
}

@keyframes tooltipFadeIn {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .tooltip-guide {
    max-width: 280px;
  }
  
  .tooltip-guide-content {
    padding: 12px;
  }
  
  .tooltip-title {
    font-size: 13px;
  }
  
  .tooltip-description {
    font-size: 12px;
  }
  
  .tooltip-actions {
    flex-direction: column;
    gap: 6px;
  }
  
  .tooltip-actions .el-button {
    width: 100%;
  }
}
</style>