<template>
  <button
    :class="[
      'glow-button',
      variant,
      size,
      {
        'loading': loading,
        'disabled': disabled,
        'pulse': pulse
      }
    ]"
    :disabled="disabled || loading"
    @click="handleClick"
    @mouseenter="onHover"
    @mouseleave="onLeave"
  >
    <span class="button-content">
      <el-icon v-if="loading" class="loading-icon">
        <div class="loading-spinner"></div>
      </el-icon>
      <el-icon v-else-if="icon" class="button-icon">
        <component :is="icon" />
      </el-icon>
      <span v-if="$slots.default" class="button-text">
        <slot></slot>
      </span>
    </span>
    <div class="glow-effect"></div>
    <div class="ripple-container" ref="rippleContainer"></div>
  </button>
</template>

<script setup lang="ts">
interface Props {
  variant?: 'primary' | 'secondary' | 'success' | 'warning' | 'danger'
  size?: 'small' | 'medium' | 'large'
  loading?: boolean
  disabled?: boolean
  pulse?: boolean
  icon?: any
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'medium',
  loading: false,
  disabled: false,
  pulse: false
})

const emit = defineEmits<{
  click: [event: MouseEvent]
}>()

const rippleContainer = ref<HTMLDivElement>()

const handleClick = (event: MouseEvent) => {
  if (props.disabled || props.loading) return
  
  createRipple(event)
  emit('click', event)
}

const createRipple = (event: MouseEvent) => {
  if (!rippleContainer.value) return
  
  const button = event.currentTarget as HTMLButtonElement
  const rect = button.getBoundingClientRect()
  const size = Math.max(rect.width, rect.height)
  const x = event.clientX - rect.left - size / 2
  const y = event.clientY - rect.top - size / 2
  
  const ripple = document.createElement('div')
  ripple.className = 'ripple'
  ripple.style.width = ripple.style.height = size + 'px'
  ripple.style.left = x + 'px'
  ripple.style.top = y + 'px'
  
  rippleContainer.value.appendChild(ripple)
  
  setTimeout(() => {
    ripple.remove()
  }, 600)
}

const onHover = () => {
  // 可以添加額外的懸停效果
}

const onLeave = () => {
  // 可以添加離開效果
}
</script>

<style scoped>
.glow-button {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 12px 24px;
  border: 2px solid transparent;
  border-radius: 8px;
  background: linear-gradient(45deg, rgba(0, 255, 255, 0.1), rgba(0, 128, 255, 0.1));
  color: #00ffff;
  font-family: 'Segoe UI', sans-serif;
  font-weight: 600;
  font-size: 14px;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  cursor: pointer;
  overflow: hidden;
  transition: all 0.3s ease;
  backdrop-filter: blur(10px);
  box-shadow: 
    0 0 10px rgba(0, 255, 255, 0.3),
    inset 0 0 10px rgba(0, 255, 255, 0.1);
}

.glow-button:before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  transition: left 0.5s;
}

.glow-button:hover:before {
  left: 100%;
}

.glow-button:hover {
  border-color: #00ffff;
  box-shadow: 
    0 0 20px rgba(0, 255, 255, 0.6),
    0 0 30px rgba(0, 255, 255, 0.4),
    inset 0 0 15px rgba(0, 255, 255, 0.2);
  transform: translateY(-2px);
}

.glow-button:active {
  transform: translateY(0);
  box-shadow: 
    0 0 15px rgba(0, 255, 255, 0.8),
    inset 0 0 10px rgba(0, 255, 255, 0.3);
}

/* 變體樣式 */
.glow-button.primary {
  border-color: rgba(0, 255, 255, 0.5);
  color: #00ffff;
}

.glow-button.secondary {
  border-color: rgba(128, 255, 0, 0.5);
  color: #80ff00;
  background: linear-gradient(45deg, rgba(128, 255, 0, 0.1), rgba(0, 255, 128, 0.1));
  box-shadow: 
    0 0 10px rgba(128, 255, 0, 0.3),
    inset 0 0 10px rgba(128, 255, 0, 0.1);
}

.glow-button.secondary:hover {
  border-color: #80ff00;
  box-shadow: 
    0 0 20px rgba(128, 255, 0, 0.6),
    0 0 30px rgba(128, 255, 0, 0.4);
}

.glow-button.success {
  border-color: rgba(0, 255, 128, 0.5);
  color: #00ff80;
  background: linear-gradient(45deg, rgba(0, 255, 128, 0.1), rgba(0, 255, 0, 0.1));
}

.glow-button.warning {
  border-color: rgba(255, 128, 0, 0.5);
  color: #ff8000;
  background: linear-gradient(45deg, rgba(255, 128, 0, 0.1), rgba(255, 255, 0, 0.1));
}

.glow-button.danger {
  border-color: rgba(255, 0, 128, 0.5);
  color: #ff0080;
  background: linear-gradient(45deg, rgba(255, 0, 128, 0.1), rgba(255, 0, 0, 0.1));
}

/* 尺寸變體 */
.glow-button.small {
  padding: 8px 16px;
  font-size: 12px;
  border-radius: 6px;
}

.glow-button.medium {
  padding: 12px 24px;
  font-size: 14px;
  border-radius: 8px;
}

.glow-button.large {
  padding: 16px 32px;
  font-size: 16px;
  border-radius: 10px;
}

/* 狀態樣式 */
.glow-button.loading,
.glow-button.disabled {
  opacity: 0.6;
  cursor: not-allowed;
  pointer-events: none;
}

.glow-button.pulse {
  animation: button-pulse 2s infinite;
}

.button-content {
  display: flex;
  align-items: center;
  gap: 8px;
  position: relative;
  z-index: 2;
}

.button-icon {
  font-size: 16px;
}

.loading-icon {
  display: flex;
  align-items: center;
}

.loading-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid transparent;
  border-top: 2px solid currentColor;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.glow-effect {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: radial-gradient(circle, rgba(0, 255, 255, 0.1) 0%, transparent 70%);
  opacity: 0;
  transition: opacity 0.3s ease;
  pointer-events: none;
}

.glow-button:hover .glow-effect {
  opacity: 1;
}

.ripple-container {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  overflow: hidden;
  border-radius: inherit;
  pointer-events: none;
}

.ripple {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.3);
  transform: scale(0);
  animation: ripple-animation 0.6s linear;
  pointer-events: none;
}

@keyframes button-pulse {
  0%, 100% {
    box-shadow: 
      0 0 10px rgba(0, 255, 255, 0.3),
      inset 0 0 10px rgba(0, 255, 255, 0.1);
  }
  50% {
    box-shadow: 
      0 0 25px rgba(0, 255, 255, 0.6),
      0 0 35px rgba(0, 255, 255, 0.4),
      inset 0 0 15px rgba(0, 255, 255, 0.2);
  }
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@keyframes ripple-animation {
  to {
    transform: scale(4);
    opacity: 0;
  }
}

/* 響應式設計 */
@media (max-width: 768px) {
  .glow-button.large {
    padding: 12px 24px;
    font-size: 14px;
  }
  
  .glow-button.medium {
    padding: 10px 20px;
    font-size: 13px;
  }
  
  .glow-button.small {
    padding: 8px 16px;
    font-size: 12px;
  }
}
</style>