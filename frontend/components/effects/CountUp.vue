<template>
  <span class="count-up">{{ prefix }}{{ displayValue }}{{ suffix }}</span>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'

interface Props {
  start?: number
  end: number
  duration?: number
  prefix?: string
  suffix?: string
  separator?: string
  decimal?: number
}

const props = withDefaults(defineProps<Props>(), {
  start: 0,
  duration: 2000,
  prefix: '',
  suffix: '',
  separator: ',',
  decimal: 0
})

const displayValue = ref(props.start)

const formatNumber = (num: number): string => {
  const rounded = Math.round(num * Math.pow(10, props.decimal)) / Math.pow(10, props.decimal)
  const parts = rounded.toFixed(props.decimal).split('.')
  
  // 添加千位分隔符
  if (props.separator) {
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, props.separator)
  }
  
  return parts.join('.')
}

const animateCount = () => {
  const startTime = Date.now()
  const startValue = props.start
  const endValue = props.end
  const duration = props.duration
  
  const animate = () => {
    const now = Date.now()
    const elapsed = now - startTime
    const progress = Math.min(elapsed / duration, 1)
    
    // 使用緩動函數 (easeOutCubic)
    const easeProgress = 1 - Math.pow(1 - progress, 3)
    
    const currentValue = startValue + (endValue - startValue) * easeProgress
    displayValue.value = parseFloat(formatNumber(currentValue))
    
    if (progress < 1) {
      requestAnimationFrame(animate)
    }
  }
  
  requestAnimationFrame(animate)
}

// 監聽 end 值變化
watch(() => props.end, () => {
  animateCount()
})

onMounted(() => {
  animateCount()
})
</script>

<style scoped>
.count-up {
  font-variant-numeric: tabular-nums;
  display: inline-block;
}
</style>