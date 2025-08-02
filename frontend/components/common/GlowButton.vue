<template>
  <button 
    :class="buttonClasses"
    :disabled="loading || disabled"
    @click="handleClick"
  >
    <span v-if="loading" class="loading-spinner"></span>
    <slot />
  </button>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  variant?: 'primary' | 'secondary' | 'success' | 'danger' | 'warning'
  size?: 'small' | 'medium' | 'large'
  loading?: boolean
  disabled?: boolean
  outline?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'medium',
  loading: false,
  disabled: false,
  outline: false
})

const emit = defineEmits<{
  click: [event: MouseEvent]
}>()

const buttonClasses = computed(() => [
  'glow-button',
  `glow-button--${props.variant}`,
  `glow-button--${props.size}`,
  {
    'glow-button--loading': props.loading,
    'glow-button--disabled': props.disabled,
    'glow-button--outline': props.outline
  }
])

const handleClick = (event: MouseEvent) => {
  if (!props.loading && !props.disabled) {
    emit('click', event)
  }
}
</script>

<style scoped>
.glow-button {
  position: relative;
  border: none;
  border-radius: 25px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 1px;
  overflow: hidden;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.glow-button::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
  transition: left 0.5s;
}

.glow-button:hover::before {
  left: 100%;
}

/* 尺寸變體 */
.glow-button--small {
  padding: 0.5rem 1rem;
  font-size: 0.8rem;
}

.glow-button--medium {
  padding: 0.75rem 1.5rem;
  font-size: 1rem;
}

.glow-button--large {
  padding: 1rem 2rem;
  font-size: 1.1rem;
}

/* 顏色變體 */
.glow-button--primary {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.glow-button--primary:hover {
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
  transform: translateY(-2px);
}

.glow-button--secondary {
  background: linear-gradient(135deg, #6c757d, #495057);
  color: white;
  box-shadow: 0 4px 15px rgba(108, 117, 125, 0.4);
}

.glow-button--secondary:hover {
  box-shadow: 0 8px 25px rgba(108, 117, 125, 0.6);
  transform: translateY(-2px);
}

.glow-button--success {
  background: linear-gradient(135deg, #28a745, #20c997);
  color: white;
  box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
}

.glow-button--success:hover {
  box-shadow: 0 8px 25px rgba(40, 167, 69, 0.6);
  transform: translateY(-2px);
}

.glow-button--danger {
  background: linear-gradient(135deg, #dc3545, #e83e8c);
  color: white;
  box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
}

.glow-button--danger:hover {
  box-shadow: 0 8px 25px rgba(220, 53, 69, 0.6);
  transform: translateY(-2px);
}

.glow-button--warning {
  background: linear-gradient(135deg, #ffc107, #fd7e14);
  color: #212529;
  box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);
}

.glow-button--warning:hover {
  box-shadow: 0 8px 25px rgba(255, 193, 7, 0.6);
  transform: translateY(-2px);
}

/* 輪廓變體 */
.glow-button--outline {
  background: transparent;
  border: 2px solid;
}

.glow-button--outline.glow-button--primary {
  border-color: #667eea;
  color: #667eea;
  box-shadow: 0 0 10px rgba(102, 126, 234, 0.3);
}

.glow-button--outline.glow-button--primary:hover {
  background: rgba(102, 126, 234, 0.1);
  box-shadow: 0 0 20px rgba(102, 126, 234, 0.5);
}

/* 狀態變體 */
.glow-button--loading {
  pointer-events: none;
  opacity: 0.7;
}

.glow-button--disabled {
  pointer-events: none;
  opacity: 0.5;
  transform: none !important;
  box-shadow: none !important;
}

.loading-spinner {
  width: 1em;
  height: 1em;
  border: 2px solid transparent;
  border-top: 2px solid currentColor;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

/* 漣漪效果 */
.glow-button {
  position: relative;
  overflow: hidden;
}

.glow-button::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0;
  height: 0;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.3);
  transform: translate(-50%, -50%);
  transition: width 0.3s, height 0.3s;
}

.glow-button:active::after {
  width: 200%;
  height: 200%;
}
</style>