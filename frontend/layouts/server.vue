<template>
  <div class="server-layout">
    <!-- 粒子背景效果 -->
    <div class="particle-background">
      <canvas ref="particleCanvas" class="particle-canvas"></canvas>
    </div>
    
    <!-- 漸變背景層 -->
    <div class="gradient-background"></div>
    
    <!-- 主要內容區域 -->
    <div class="content-area">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick } from 'vue'

// 響應式數據
const particleCanvas = ref<HTMLCanvasElement>()
let animationId: number | null = null
let resizeHandler: () => void

// 粒子動畫
const initParticleEffect = () => {
  if (!particleCanvas.value) return

  const canvas = particleCanvas.value
  const ctx = canvas.getContext('2d')
  if (!ctx) return

  // 設置 canvas 尺寸
  const resizeCanvas = () => {
    canvas.width = window.innerWidth
    canvas.height = window.innerHeight
  }
  
  resizeCanvas()
  resizeHandler = resizeCanvas
  window.addEventListener('resize', resizeHandler)

  // 粒子類
  class Particle {
    x: number
    y: number
    size: number
    speedX: number
    speedY: number
    opacity: number

    constructor() {
      this.x = Math.random() * canvas.width
      this.y = Math.random() * canvas.height
      this.size = Math.random() * 2 + 0.5
      this.speedX = Math.random() * 0.5 - 0.25
      this.speedY = Math.random() * 0.5 - 0.25
      this.opacity = Math.random() * 0.5 + 0.2
    }

    update() {
      this.x += this.speedX
      this.y += this.speedY

      if (this.x > canvas.width) this.x = 0
      if (this.x < 0) this.x = canvas.width
      if (this.y > canvas.height) this.y = 0
      if (this.y < 0) this.y = canvas.height
    }

    draw() {
      ctx!.save()
      ctx!.globalAlpha = this.opacity
      ctx!.fillStyle = '#00d4ff'
      ctx!.shadowBlur = 10
      ctx!.shadowColor = '#00d4ff'
      ctx!.beginPath()
      ctx!.arc(this.x, this.y, this.size, 0, Math.PI * 2)
      ctx!.fill()
      ctx!.restore()
    }
  }

  // 創建粒子
  const particles: Particle[] = []
  for (let i = 0; i < 50; i++) {
    particles.push(new Particle())
  }

  // 動畫循環
  const animate = () => {
    ctx.clearRect(0, 0, canvas.width, canvas.height)
    
    particles.forEach(particle => {
      particle.update()
      particle.draw()
    })

    animationId = requestAnimationFrame(animate)
  }

  animate()
}

// 生命週期
onMounted(() => {
  nextTick(() => {
    initParticleEffect()
  })
})

onUnmounted(() => {
  if (animationId) {
    cancelAnimationFrame(animationId)
  }
  if (resizeHandler) {
    window.removeEventListener('resize', resizeHandler)
  }
})
</script>

<style scoped>
.server-layout {
  min-height: 100vh;
  position: relative;
  background: linear-gradient(135deg, #0a0e1a 0%, #1a1f35 50%, #2d1b69 100%);
  overflow: hidden;
}

.particle-background {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1;
  pointer-events: none;
}

.particle-canvas {
  width: 100%;
  height: 100%;
}

.gradient-background {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: 
    radial-gradient(ellipse at 20% 80%, rgba(0, 212, 255, 0.15) 0%, transparent 50%),
    radial-gradient(ellipse at 80% 20%, rgba(45, 27, 105, 0.3) 0%, transparent 50%),
    radial-gradient(ellipse at 40% 40%, rgba(0, 128, 255, 0.1) 0%, transparent 50%);
  animation: gradient-shift 30s ease-in-out infinite;
  z-index: 2;
}

@keyframes gradient-shift {
  0%, 100% {
    opacity: 0.6;
    transform: scale(1);
  }
  50% {
    opacity: 0.8;
    transform: scale(1.02);
  }
}

.content-area {
  position: relative;
  z-index: 3;
  min-height: 100vh;
}

/* 響應式設計 */
@media (max-width: 768px) {
  .server-layout {
    overflow-x: hidden;
  }
}

/* 效能優化 */
@media (prefers-reduced-motion: reduce) {
  .gradient-background {
    animation: none !important;
  }
  
  .particle-background {
    display: none;
  }
}

/* GPU 加速優化 */
.server-layout,
.gradient-background,
.particle-background {
  transform: translateZ(0);
  backface-visibility: hidden;
  -webkit-transform: translateZ(0);
  -webkit-backface-visibility: hidden;
  -moz-backface-visibility: hidden;
}

/* Safari 支援 */
@supports (-webkit-touch-callout: none) {
  .server-layout {
    height: -webkit-fill-available;
  }
  
  .content-area {
    min-height: -webkit-fill-available;
  }
}
</style>