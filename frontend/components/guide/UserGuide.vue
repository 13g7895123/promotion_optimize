<template>
  <div v-if="showGuide" class="user-guide-overlay">
    <!-- Backdrop -->
    <div class="guide-backdrop" @click="handleBackdropClick" />
    
    <!-- Spotlight -->
    <div 
      v-if="currentStep && targetElement"
      class="guide-spotlight"
      :style="spotlightStyle"
    />
    
    <!-- Guide Popover -->
    <div 
      v-if="currentStep && targetElement"
      class="guide-popover"
      :style="popoverStyle"
      :class="popoverClass"
    >
      <!-- Step Content -->
      <div class="guide-content">
        <div class="guide-header">
          <div class="step-indicator">
            {{ currentStepIndex + 1 }} / {{ steps.length }}
          </div>
          <el-button @click="skipGuide" text size="small">
            跳過引導
          </el-button>
        </div>
        
        <div class="guide-body">
          <h4 v-if="currentStep.title" class="guide-title">
            {{ currentStep.title }}
          </h4>
          <p class="guide-description">
            {{ currentStep.description }}
          </p>
          
          <!-- Custom content slot -->
          <div v-if="currentStep.component" class="guide-custom-content">
            <component :is="currentStep.component" v-bind="currentStep.props" />
          </div>
        </div>
        
        <div class="guide-footer">
          <div class="step-dots">
            <span 
              v-for="(step, index) in steps" 
              :key="index"
              class="step-dot"
              :class="{ 'active': index === currentStepIndex }"
              @click="goToStep(index)"
            />
          </div>
          
          <div class="guide-actions">
            <el-button 
              v-if="currentStepIndex > 0"
              @click="previousStep"
              size="small"
            >
              上一步
            </el-button>
            
            <el-button 
              v-if="currentStepIndex < steps.length - 1"
              @click="nextStep"
              type="primary"
              size="small"
            >
              下一步
            </el-button>
            
            <el-button 
              v-else
              @click="finishGuide"
              type="primary"
              size="small"
            >
              完成
            </el-button>
          </div>
        </div>
      </div>
      
      <!-- Arrow -->
      <div class="guide-arrow" :class="arrowDirection" />
    </div>
  </div>
</template>

<script setup lang="ts">
interface GuideStep {
  target: string | HTMLElement
  title?: string
  description: string
  position?: 'top' | 'bottom' | 'left' | 'right' | 'auto'
  component?: any
  props?: Record<string, any>
  beforeShow?: () => Promise<void> | void
  afterShow?: () => Promise<void> | void
  allowClickTarget?: boolean
  showOverlay?: boolean
}

interface Props {
  steps: GuideStep[]
  modelValue: boolean
  allowBackdropClose?: boolean
  showProgress?: boolean
  zIndex?: number
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'step-change', step: number): void
  (e: 'finish'): void
  (e: 'skip'): void
}

const props = withDefaults(defineProps<Props>(), {
  allowBackdropClose: true,
  showProgress: true,
  zIndex: 9999
})

const emit = defineEmits<Emits>()

// Reactive state
const currentStepIndex = ref(0)
const targetElement = ref<HTMLElement | null>(null)
const showGuide = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

// Current step
const currentStep = computed(() => props.steps[currentStepIndex.value])

// Element positioning
const elementRect = ref<DOMRect | null>(null)
const popoverPosition = ref<'top' | 'bottom' | 'left' | 'right'>('bottom')

// Computed styles
const spotlightStyle = computed(() => {
  if (!elementRect.value) return {}
  
  const rect = elementRect.value
  const padding = 8
  
  return {
    position: 'fixed',
    top: `${rect.top - padding}px`,
    left: `${rect.left - padding}px`,
    width: `${rect.width + padding * 2}px`,
    height: `${rect.height + padding * 2}px`,
    borderRadius: '8px',
    boxShadow: '0 0 0 4px rgba(255, 255, 255, 0.2)',
    border: '2px solid var(--el-color-primary)',
    backgroundColor: 'transparent',
    pointerEvents: props.currentStep?.allowClickTarget ? 'none' : 'auto',
    zIndex: props.zIndex + 1
  }
})

const popoverStyle = computed(() => {
  if (!elementRect.value) return {}
  
  const rect = elementRect.value
  const popoverWidth = 300
  const popoverHeight = 200
  const spacing = 20
  
  let top = 0
  let left = 0
  
  switch (popoverPosition.value) {
    case 'top':
      top = rect.top - popoverHeight - spacing
      left = rect.left + rect.width / 2 - popoverWidth / 2
      break
    case 'bottom':
      top = rect.bottom + spacing
      left = rect.left + rect.width / 2 - popoverWidth / 2
      break
    case 'left':
      top = rect.top + rect.height / 2 - popoverHeight / 2
      left = rect.left - popoverWidth - spacing
      break
    case 'right':
      top = rect.top + rect.height / 2 - popoverHeight / 2
      left = rect.right + spacing
      break
  }
  
  // Ensure popover stays within viewport
  const viewport = {
    width: window.innerWidth,
    height: window.innerHeight
  }
  
  if (left < 10) left = 10
  if (left + popoverWidth > viewport.width - 10) left = viewport.width - popoverWidth - 10
  if (top < 10) top = 10
  if (top + popoverHeight > viewport.height - 10) top = viewport.height - popoverHeight - 10
  
  return {
    position: 'fixed',
    top: `${top}px`,
    left: `${left}px`,
    width: `${popoverWidth}px`,
    minHeight: `${popoverHeight}px`,
    zIndex: props.zIndex + 2
  }
})

const popoverClass = computed(() => {
  return `popover-${popoverPosition.value}`
})

const arrowDirection = computed(() => {
  switch (popoverPosition.value) {
    case 'top': return 'arrow-bottom'
    case 'bottom': return 'arrow-top'
    case 'left': return 'arrow-right'
    case 'right': return 'arrow-left'
    default: return 'arrow-top'
  }
})

// Methods
const findTargetElement = (target: string | HTMLElement): HTMLElement | null => {
  if (typeof target === 'string') {
    return document.querySelector(target) as HTMLElement
  }
  return target
}

const updateTargetElement = async () => {
  if (!currentStep.value) return
  
  const element = findTargetElement(currentStep.value.target)
  if (!element) {
    console.warn(`Guide target not found: ${currentStep.value.target}`)
    return
  }
  
  targetElement.value = element
  
  // Scroll element into view
  element.scrollIntoView({
    behavior: 'smooth',
    block: 'center',
    inline: 'center'
  })
  
  // Wait for scroll to complete
  await new Promise(resolve => setTimeout(resolve, 500))
  
  // Update element rect
  elementRect.value = element.getBoundingClientRect()
  
  // Determine optimal popover position
  popoverPosition.value = determineOptimalPosition(elementRect.value)
}

const determineOptimalPosition = (rect: DOMRect): 'top' | 'bottom' | 'left' | 'right' => {
  if (currentStep.value?.position && currentStep.value.position !== 'auto') {
    return currentStep.value.position
  }
  
  const viewport = {
    width: window.innerWidth,
    height: window.innerHeight
  }
  
  const spaceTop = rect.top
  const spaceBottom = viewport.height - rect.bottom
  const spaceLeft = rect.left
  const spaceRight = viewport.width - rect.right
  
  // Choose position with most space
  const maxSpace = Math.max(spaceTop, spaceBottom, spaceLeft, spaceRight)
  
  if (maxSpace === spaceBottom && spaceBottom > 250) return 'bottom'
  if (maxSpace === spaceTop && spaceTop > 250) return 'top'
  if (maxSpace === spaceRight && spaceRight > 320) return 'right'
  if (maxSpace === spaceLeft && spaceLeft > 320) return 'left'
  
  // Fallback to bottom
  return 'bottom'
}

const nextStep = async () => {
  if (currentStepIndex.value < props.steps.length - 1) {
    currentStepIndex.value++
    await showCurrentStep()
    emit('step-change', currentStepIndex.value)
  }
}

const previousStep = async () => {
  if (currentStepIndex.value > 0) {
    currentStepIndex.value--
    await showCurrentStep()
    emit('step-change', currentStepIndex.value)
  }
}

const goToStep = async (index: number) => {
  if (index >= 0 && index < props.steps.length) {
    currentStepIndex.value = index
    await showCurrentStep()
    emit('step-change', currentStepIndex.value)
  }
}

const showCurrentStep = async () => {
  const step = currentStep.value
  if (!step) return
  
  // Execute beforeShow hook
  if (step.beforeShow) {
    await step.beforeShow()
  }
  
  // Update target element
  await updateTargetElement()
  
  // Execute afterShow hook
  if (step.afterShow) {
    await step.afterShow()
  }
}

const finishGuide = () => {
  showGuide.value = false
  emit('finish')
}

const skipGuide = () => {
  showGuide.value = false
  emit('skip')
}

const handleBackdropClick = () => {
  if (props.allowBackdropClose) {
    skipGuide()
  }
}

// Watch for guide visibility changes
watch(showGuide, async (visible) => {
  if (visible && props.steps.length > 0) {
    currentStepIndex.value = 0
    await nextTick()
    await showCurrentStep()
  }
}, { immediate: true })

// Handle window resize
const handleResize = () => {
  if (showGuide.value && targetElement.value) {
    elementRect.value = targetElement.value.getBoundingClientRect()
  }
}

onMounted(() => {
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})
</script>

<style scoped>
.user-guide-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  pointer-events: none;
}

.guide-backdrop {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  pointer-events: auto;
}

.guide-spotlight {
  transition: all 0.3s ease;
  pointer-events: auto;
}

.guide-popover {
  background: var(--el-bg-color);
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
  border: 1px solid var(--el-border-color);
  pointer-events: auto;
  animation: popoverFadeIn 0.3s ease;
}

@keyframes popoverFadeIn {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.guide-content {
  padding: 20px;
}

.guide-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.step-indicator {
  font-size: 12px;
  color: var(--el-text-color-secondary);
  background: var(--el-bg-color-page);
  padding: 4px 8px;
  border-radius: 12px;
  font-weight: 500;
}

.guide-title {
  margin: 0 0 8px;
  font-size: 16px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.guide-description {
  margin: 0 0 16px;
  font-size: 14px;
  line-height: 1.5;
  color: var(--el-text-color-regular);
}

.guide-custom-content {
  margin-bottom: 16px;
}

.guide-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid var(--el-border-color-lighter);
}

.step-dots {
  display: flex;
  gap: 6px;
}

.step-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--el-border-color);
  cursor: pointer;
  transition: all 0.2s ease;
}

.step-dot.active {
  background: var(--el-color-primary);
  transform: scale(1.2);
}

.step-dot:hover {
  background: var(--el-color-primary-light-3);
}

.guide-actions {
  display: flex;
  gap: 8px;
}

/* Arrow styles */
.guide-arrow {
  position: absolute;
  width: 0;
  height: 0;
}

.arrow-top {
  top: -8px;
  left: 50%;
  transform: translateX(-50%);
  border-left: 8px solid transparent;
  border-right: 8px solid transparent;
  border-bottom: 8px solid var(--el-bg-color);
}

.arrow-bottom {
  bottom: -8px;
  left: 50%;
  transform: translateX(-50%);
  border-left: 8px solid transparent;
  border-right: 8px solid transparent;
  border-top: 8px solid var(--el-bg-color);
}

.arrow-left {
  left: -8px;
  top: 50%;
  transform: translateY(-50%);
  border-top: 8px solid transparent;
  border-bottom: 8px solid transparent;
  border-right: 8px solid var(--el-bg-color);
}

.arrow-right {
  right: -8px;
  top: 50%;
  transform: translateY(-50%);
  border-top: 8px solid transparent;
  border-bottom: 8px solid transparent;
  border-left: 8px solid var(--el-bg-color);
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .guide-popover {
    position: fixed !important;
    bottom: 20px !important;
    left: 20px !important;
    right: 20px !important;
    top: auto !important;
    width: auto !important;
    min-height: auto !important;
  }
  
  .guide-content {
    padding: 16px;
  }
  
  .guide-footer {
    flex-direction: column;
    gap: 12px;
    align-items: stretch;
  }
  
  .guide-actions {
    justify-content: center;
  }
  
  .guide-arrow {
    display: none;
  }
}
</style>