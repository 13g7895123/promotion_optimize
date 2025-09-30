<template>
  <div class="relative">
    <canvas :id="chartId" :width="width" :height="height"></canvas>
  </div>
</template>

<script setup>
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js'

ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend
)

const props = defineProps({
  data: {
    type: Object,
    required: true
  },
  options: {
    type: Object,
    default: () => ({})
  },
  width: {
    type: Number,
    default: 400
  },
  height: {
    type: Number,
    default: 200
  },
  responsive: {
    type: Boolean,
    default: true
  }
})

// Generate unique chart ID
const chartId = `bar-chart-${Math.random().toString(36).substr(2, 9)}`
let chartInstance = null

const defaultOptions = {
  responsive: props.responsive,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top',
      labels: {
        boxWidth: 12,
        padding: 15
      }
    },
    tooltip: {
      mode: 'index',
      intersect: false,
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      titleColor: '#fff',
      bodyColor: '#fff',
      borderColor: '#374151',
      borderWidth: 1
    }
  },
  scales: {
    x: {
      grid: {
        display: false
      },
      ticks: {
        color: '#6B7280'
      }
    },
    y: {
      beginAtZero: true,
      grid: {
        color: 'rgba(107, 114, 128, 0.1)'
      },
      ticks: {
        color: '#6B7280'
      }
    }
  }
}

const createChart = () => {
  const canvas = document.getElementById(chartId)
  if (!canvas) return

  const ctx = canvas.getContext('2d')

  if (chartInstance) {
    chartInstance.destroy()
  }

  chartInstance = new ChartJS(ctx, {
    type: 'bar',
    data: props.data,
    options: {
      ...defaultOptions,
      ...props.options
    }
  })
}

const updateChart = () => {
  if (chartInstance) {
    chartInstance.data = props.data
    chartInstance.options = {
      ...defaultOptions,
      ...props.options
    }
    chartInstance.update()
  }
}

watch(() => props.data, updateChart, { deep: true })
watch(() => props.options, updateChart, { deep: true })

onMounted(() => {
  nextTick(() => {
    createChart()
  })
})

onBeforeUnmount(() => {
  if (chartInstance) {
    chartInstance.destroy()
  }
})
</script>