<template>
  <div ref="canvasContainer" class="particle-background">
    <canvas 
      ref="canvas" 
      :width="canvasWidth" 
      :height="canvasHeight"
      class="particle-canvas"
    ></canvas>
  </div>
</template>

<script setup lang="ts">
interface Particle {
  x: number
  y: number
  vx: number
  vy: number
  size: number
  opacity: number
  color: string
  pulse: number
}

const canvasContainer = ref<HTMLDivElement>()
const canvas = ref<HTMLCanvasElement>()
const canvasWidth = ref(0)
const canvasHeight = ref(0)

let ctx: CanvasRenderingContext2D | null = null
let particles: Particle[] = []
let animationId: number = 0

// 遊戲科技風色彩配置
const particleColors = [
  '#00ffff',  // 青色霓虹
  '#0080ff',  // 藍色發光
  '#ff0080',  // 紅紫色霓虹
  '#80ff00',  // 綠色發光
  '#ff8000',  // 橘色發光
]

onMounted(() => {
  initCanvas()
  createParticles()
  animate()
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  cancelAnimationFrame(animationId)
  window.removeEventListener('resize', handleResize)
})

const initCanvas = () => {
  if (!canvas.value || !canvasContainer.value) return
  
  ctx = canvas.value.getContext('2d')
  updateCanvasSize()
}

const updateCanvasSize = () => {
  if (!canvasContainer.value || !canvas.value) return
  
  canvasWidth.value = canvasContainer.value.clientWidth
  canvasHeight.value = canvasContainer.value.clientHeight
  
  canvas.value.width = canvasWidth.value
  canvas.value.height = canvasHeight.value
}

const createParticles = () => {
  particles = []
  const particleCount = Math.floor((canvasWidth.value * canvasHeight.value) / 15000)
  
  for (let i = 0; i < particleCount; i++) {
    particles.push({
      x: Math.random() * canvasWidth.value,
      y: Math.random() * canvasHeight.value,
      vx: (Math.random() - 0.5) * 0.5,
      vy: (Math.random() - 0.5) * 0.5,
      size: Math.random() * 2 + 1,
      opacity: Math.random() * 0.8 + 0.2,
      color: particleColors[Math.floor(Math.random() * particleColors.length)],
      pulse: Math.random() * Math.PI * 2
    })
  }
}

const animate = () => {
  if (!ctx) return
  
  // 清除畫布，保持透明背景
  ctx.clearRect(0, 0, canvasWidth.value, canvasHeight.value)
  
  // 更新和繪製粒子
  particles.forEach((particle) => {
    // 更新位置
    particle.x += particle.vx
    particle.y += particle.vy
    
    // 邊界檢測
    if (particle.x < 0 || particle.x > canvasWidth.value) particle.vx *= -1
    if (particle.y < 0 || particle.y > canvasHeight.value) particle.vy *= -1
    
    // 脈衝效果
    particle.pulse += 0.02
    const pulseOpacity = particle.opacity * (0.5 + 0.5 * Math.sin(particle.pulse))
    
    // 繪製粒子
    ctx.save()
    ctx.globalAlpha = pulseOpacity
    ctx.beginPath()
    ctx.arc(particle.x, particle.y, particle.size, 0, Math.PI * 2)
    ctx.fillStyle = particle.color
    ctx.fill()
    
    // 添加發光效果
    ctx.shadowColor = particle.color
    ctx.shadowBlur = 10
    ctx.fill()
    ctx.restore()
  })
  
  // 繪製連接線
  drawConnections()
  
  animationId = requestAnimationFrame(animate)
}

const drawConnections = () => {
  if (!ctx) return
  
  const maxDistance = 100
  
  for (let i = 0; i < particles.length; i++) {
    for (let j = i + 1; j < particles.length; j++) {
      const dx = particles[i].x - particles[j].x
      const dy = particles[i].y - particles[j].y
      const distance = Math.sqrt(dx * dx + dy * dy)
      
      if (distance < maxDistance) {
        const opacity = (1 - distance / maxDistance) * 0.3
        
        ctx.save()
        ctx.globalAlpha = opacity
        ctx.beginPath()
        ctx.moveTo(particles[i].x, particles[i].y)
        ctx.lineTo(particles[j].x, particles[j].y)
        ctx.strokeStyle = '#00ffff'
        ctx.lineWidth = 0.5
        ctx.stroke()
        ctx.restore()
      }
    }
  }
}

const handleResize = () => {
  updateCanvasSize()
  createParticles()
}
</script>

<style scoped>
.particle-background {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  overflow: hidden;
  z-index: 1;
}

.particle-canvas {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
}
</style>