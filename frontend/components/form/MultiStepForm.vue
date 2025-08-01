<template>
  <div class="multi-step-form">
    <!-- Progress Steps -->
    <el-steps 
      :active="currentStep" 
      finish-status="success" 
      align-center
      class="step-progress"
    >
      <el-step 
        v-for="(step, index) in steps" 
        :key="index"
        :title="step.title"
        :description="step.description"
        :icon="step.icon"
      />
    </el-steps>

    <!-- Form Content -->
    <div class="form-content">
      <el-form
        ref="formRef"
        :model="formData"
        :rules="currentStepRules"
        :label-width="labelWidth"
        size="large"
        @submit.prevent="handleNext"
      >
        <transition 
          :name="transitionName" 
          mode="out-in"
          @before-leave="onBeforeLeave"
          @after-enter="onAfterEnter"
        >
          <div :key="currentStep" class="step-content">
            <slot 
              :name="`step-${currentStep}`" 
              :form-data="formData"
              :validate="validateCurrentStep"
              :loading="loading"
            />
          </div>
        </transition>

        <!-- Form Actions -->
        <div class="form-actions">
          <el-button 
            v-if="currentStep > 0"
            @click="handlePrevious"
            :disabled="loading"
            size="large"
          >
            <el-icon><ArrowLeft /></el-icon>
            上一步
          </el-button>

          <div class="spacer" />

          <el-button 
            v-if="currentStep < steps.length - 1"
            type="primary"
            @click="handleNext"
            :loading="loading"
            :disabled="!canProceed"
            size="large"
          >
            下一步
            <el-icon><ArrowRight /></el-icon>
          </el-button>

          <el-button 
            v-else
            type="primary"
            @click="handleSubmit"
            :loading="loading"
            :disabled="!canSubmit"
            size="large"
          >
            <el-icon><Check /></el-icon>
            {{ submitText }}
          </el-button>
        </div>
      </el-form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ArrowLeft, ArrowRight, Check } from '@element-plus/icons-vue'
import type { FormInstance, FormRules } from 'element-plus'

interface StepConfig {
  title: string
  description?: string
  icon?: string
  rules?: FormRules
  validate?: () => Promise<boolean> | boolean
}

interface Props {
  steps: StepConfig[]
  modelValue: Record<string, any>
  labelWidth?: string
  submitText?: string
  allowSkip?: boolean
  loading?: boolean
}

interface Emits {
  (e: 'update:modelValue', value: Record<string, any>): void
  (e: 'step-change', step: number): void
  (e: 'submit', data: Record<string, any>): void
  (e: 'validate', step: number, isValid: boolean): void
}

const props = withDefaults(defineProps<Props>(), {
  labelWidth: '120px',
  submitText: '提交',
  allowSkip: false,
  loading: false
})

const emit = defineEmits<Emits>()

// Form reference
const formRef = ref<FormInstance>()

// Current step state
const currentStep = ref(0)

// Transition direction
const transitionName = ref('slide-left')

// Form data computed property
const formData = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

// Current step rules
const currentStepRules = computed(() => {
  return props.steps[currentStep.value]?.rules || {}
})

// Can proceed to next step
const canProceed = computed(() => {
  if (props.loading) return false
  
  const step = props.steps[currentStep.value]
  if (step?.validate) {
    // Will be validated on next click
    return true
  }
  
  return true
})

// Can submit form
const canSubmit = computed(() => {
  return !props.loading && currentStep.value === props.steps.length - 1
})

// Step validation states
const stepValidation = ref<boolean[]>(new Array(props.steps.length).fill(false))

// Methods
const validateCurrentStep = async (): Promise<boolean> => {
  if (!formRef.value) return false

  try {
    // Validate form rules
    await formRef.value.validate()
    
    // Run custom validation if provided
    const step = props.steps[currentStep.value]
    if (step?.validate) {
      const isValid = await step.validate()
      if (!isValid) {
        return false
      }
    }

    // Mark step as valid
    stepValidation.value[currentStep.value] = true
    emit('validate', currentStep.value, true)
    
    return true
  } catch (error) {
    stepValidation.value[currentStep.value] = false
    emit('validate', currentStep.value, false)
    return false
  }
}

const handleNext = async () => {
  const isValid = await validateCurrentStep()
  
  if (isValid) {
    transitionName.value = 'slide-left'
    currentStep.value++
    emit('step-change', currentStep.value)
  }
}

const handlePrevious = () => {
  if (currentStep.value > 0) {
    transitionName.value = 'slide-right'
    currentStep.value--
    emit('step-change', currentStep.value)
  }
}

const handleSubmit = async () => {
  const isValid = await validateCurrentStep()
  
  if (isValid) {
    emit('submit', formData.value)
  }
}

const goToStep = async (step: number) => {
  if (step < 0 || step >= props.steps.length) return
  
  // Validate all previous steps if moving forward
  if (step > currentStep.value) {
    for (let i = currentStep.value; i < step; i++) {
      currentStep.value = i
      const isValid = await validateCurrentStep()
      if (!isValid && !props.allowSkip) {
        return false
      }
    }
  }
  
  transitionName.value = step > currentStep.value ? 'slide-left' : 'slide-right'
  currentStep.value = step
  emit('step-change', currentStep.value)
  
  return true
}

const resetForm = () => {
  currentStep.value = 0
  stepValidation.value = new Array(props.steps.length).fill(false)
  formRef.value?.resetFields()
}

// Transition handlers
const onBeforeLeave = () => {
  // Optional: Add any cleanup before step change
}

const onAfterEnter = () => {
  // Focus first input in new step
  nextTick(() => {
    const firstInput = formRef.value?.$el.querySelector('.step-content input, .step-content textarea, .step-content .el-select input')
    if (firstInput) {
      firstInput.focus()
    }
  })
}

// Expose methods for parent component
defineExpose({
  validateCurrentStep,
  goToStep,
  resetForm,
  currentStep: readonly(currentStep)
})

// Watch for step changes
watch(currentStep, (newStep) => {
  emit('step-change', newStep)
})
</script>

<style scoped>
.multi-step-form {
  max-width: 800px;
  margin: 0 auto;
}

.step-progress {
  margin-bottom: 40px;
  padding: 0 20px;
}

.form-content {
  min-height: 400px;
  position: relative;
}

.step-content {
  padding: 20px;
  background: var(--el-bg-color);
  border-radius: 8px;
  border: 1px solid var(--el-border-color-lighter);
}

.form-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 30px;
  padding: 20px 0;
  border-top: 1px solid var(--el-border-color-lighter);
}

.spacer {
  flex: 1;
}

/* Transition animations */
.slide-left-enter-active,
.slide-left-leave-active,
.slide-right-enter-active,
.slide-right-leave-active {
  transition: all 0.3s ease;
}

.slide-left-enter-from {
  transform: translateX(100%);
  opacity: 0;
}

.slide-left-leave-to {
  transform: translateX(-100%);
  opacity: 0;
}

.slide-right-enter-from {
  transform: translateX(-100%);
  opacity: 0;
}

.slide-right-leave-to {
  transform: translateX(100%);
  opacity: 0;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .multi-step-form {
    padding: 0 16px;
  }
  
  .step-progress {
    margin-bottom: 30px;
    padding: 0 10px;
  }
  
  .step-content {
    padding: 16px;
  }
  
  .form-actions {
    flex-direction: column;
    gap: 12px;
  }
  
  .form-actions .el-button {
    width: 100%;
  }
  
  .spacer {
    display: none;
  }
}

/* Step progress styling */
:deep(.el-steps) {
  --el-step-font-size: 14px;
}

:deep(.el-step__title) {
  font-weight: 600;
  font-size: 14px;
}

:deep(.el-step__description) {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

:deep(.el-step__icon) {
  border-width: 2px;
}

/* Form styling */
:deep(.el-form-item) {
  margin-bottom: 24px;
}

:deep(.el-form-item__label) {
  font-weight: 500;
  color: var(--el-text-color-primary);
}

:deep(.el-input__wrapper),
:deep(.el-textarea__inner) {
  border-radius: 8px;
}
</style>