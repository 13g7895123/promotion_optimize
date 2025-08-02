<template>
  <span class="typewriter-text">{{ displayText }}</span>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

interface Props {
  texts: string[]
  speed?: number
  pause?: number
  loop?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  speed: 100,
  pause: 2000,
  loop: true
})

const displayText = ref('')
let currentTextIndex = 0
let currentCharIndex = 0
let isDeleting = false
let timeoutId: ReturnType<typeof setTimeout> | null = null

const typeEffect = () => {
  const currentText = props.texts[currentTextIndex]
  
  if (!isDeleting) {
    // 打字效果
    displayText.value = currentText.substring(0, currentCharIndex + 1)
    currentCharIndex++
    
    if (currentCharIndex === currentText.length) {
      // 完成打字，等待一段時間後開始刪除
      timeoutId = setTimeout(() => {
        if (props.loop && props.texts.length > 1) {
          isDeleting = true
          typeEffect()
        }
      }, props.pause)
      return
    }
  } else {
    // 刪除效果
    displayText.value = currentText.substring(0, currentCharIndex - 1)
    currentCharIndex--
    
    if (currentCharIndex === 0) {
      // 完成刪除，移動到下一個文本
      isDeleting = false
      currentTextIndex = (currentTextIndex + 1) % props.texts.length
    }
  }
  
  timeoutId = setTimeout(typeEffect, isDeleting ? props.speed / 2 : props.speed)
}

onMounted(() => {
  typeEffect()
})

onUnmounted(() => {
  if (timeoutId) {
    clearTimeout(timeoutId)
  }
})
</script>

<style scoped>
.typewriter-text {
  display: inline-block;
  position: relative;
}

.typewriter-text::after {
  content: '|';
  animation: blink 1s infinite;
  margin-left: 2px;
}

@keyframes blink {
  0%, 50% {
    opacity: 1;
  }
  51%, 100% {
    opacity: 0;
  }
}
</style>