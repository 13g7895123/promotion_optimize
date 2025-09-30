<template>
  <div
    v-if="showModal"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    @click.self="closeModal"
  >
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-lg">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          QR 碼生成器
        </h3>
        <button
          @click="closeModal"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <div v-if="promotion" class="space-y-6">
        <!-- Promotion Info -->
        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
          <h4 class="font-semibold text-gray-900 dark:text-white mb-2">{{ promotion.title }}</h4>
          <p class="text-sm text-gray-600 dark:text-gray-400">{{ formatPromotionType(promotion.promotion_type) }}</p>
        </div>

        <!-- QR Code Options -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            QR 碼大小
          </label>
          <select
            v-model="qrOptions.size"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
          >
            <option value="150x150">小 (150x150)</option>
            <option value="200x200">中 (200x200)</option>
            <option value="300x300">大 (300x300)</option>
            <option value="400x400">特大 (400x400)</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            檔案格式
          </label>
          <select
            v-model="qrOptions.format"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
          >
            <option value="png">PNG</option>
            <option value="jpg">JPG</option>
            <option value="svg">SVG</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            錯誤修正等級
          </label>
          <select
            v-model="qrOptions.errorCorrection"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
          >
            <option value="L">低 (7%)</option>
            <option value="M">中 (15%)</option>
            <option value="Q">高 (25%)</option>
            <option value="H">最高 (30%)</option>
          </select>
        </div>

        <!-- Color Options -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              前景色
            </label>
            <input
              v-model="qrOptions.foregroundColor"
              type="color"
              class="w-full h-10 border border-gray-300 dark:border-gray-600 rounded-lg"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              背景色
            </label>
            <input
              v-model="qrOptions.backgroundColor"
              type="color"
              class="w-full h-10 border border-gray-300 dark:border-gray-600 rounded-lg"
            />
          </div>
        </div>

        <!-- Custom Link Option -->
        <div>
          <label class="flex items-center">
            <input
              v-model="useCustomLink"
              type="checkbox"
              class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
            />
            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">使用自定義連結</span>
          </label>
        </div>

        <div v-if="useCustomLink">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            自定義連結
          </label>
          <input
            v-model="customLink"
            type="url"
            placeholder="https://example.com/promotion"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
          />
        </div>

        <!-- Preview Button -->
        <button
          @click="generatePreview"
          :disabled="loading"
          class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg transition-colors"
        >
          <span v-if="loading" class="flex items-center justify-center">
            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
            生成中...
          </span>
          <span v-else>預覽 QR 碼</span>
        </button>

        <!-- QR Code Preview -->
        <div v-if="qrCodeUrl" class="text-center">
          <div class="bg-white p-4 rounded-lg border inline-block">
            <img :src="qrCodeUrl" :alt="`QR Code for ${promotion.title}`" class="max-w-full" />
          </div>

          <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
            掃描 QR 碼將導向推廣連結
          </div>

          <!-- Action Buttons -->
          <div class="flex justify-center space-x-4 mt-4">
            <button
              @click="copyQRCode"
              class="flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors"
            >
              <DocumentDuplicateIcon class="w-4 h-4 mr-2" />
              複製連結
            </button>

            <button
              @click="downloadQRCode"
              class="flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors"
            >
              <ArrowDownTrayIcon class="w-4 h-4 mr-2" />
              下載
            </button>

            <button
              @click="shareQRCode"
              class="flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors"
            >
              <ShareIcon class="w-4 h-4 mr-2" />
              分享
            </button>
          </div>
        </div>

        <!-- Close Button -->
        <div class="flex justify-end">
          <button
            @click="closeModal"
            class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
          >
            關閉
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  XMarkIcon,
  DocumentDuplicateIcon,
  ArrowDownTrayIcon,
  ShareIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  showModal: Boolean,
  promotion: Object
})

const emit = defineEmits(['close'])

const { generateQRCode, formatPromotionType } = usePromotionManagement()

// Reactive data
const loading = ref(false)
const qrCodeUrl = ref('')
const useCustomLink = ref(false)
const customLink = ref('')

const qrOptions = ref({
  size: '200x200',
  format: 'png',
  errorCorrection: 'M',
  foregroundColor: '#000000',
  backgroundColor: '#ffffff'
})

// Methods
const closeModal = () => {
  resetForm()
  emit('close')
}

const resetForm = () => {
  qrCodeUrl.value = ''
  useCustomLink.value = false
  customLink.value = ''
  qrOptions.value = {
    size: '200x200',
    format: 'png',
    errorCorrection: 'M',
    foregroundColor: '#000000',
    backgroundColor: '#ffffff'
  }
}

const generatePreview = async () => {
  if (!props.promotion) return

  loading.value = true

  try {
    let targetUrl

    if (useCustomLink.value && customLink.value) {
      targetUrl = customLink.value
    } else {
      // Generate default promotion URL
      targetUrl = `https://promotion.mercylife.cc/p/${props.promotion.id}?ref=${props.promotion.reference_code || 'ABC123'}`
    }

    // Use QR Server API for demonstration (in real app, this would be your backend)
    const qrParams = new URLSearchParams({
      size: qrOptions.value.size,
      data: targetUrl,
      format: qrOptions.value.format,
      ecc: qrOptions.value.errorCorrection,
      color: qrOptions.value.foregroundColor.replace('#', ''),
      bgcolor: qrOptions.value.backgroundColor.replace('#', '')
    })

    qrCodeUrl.value = `https://api.qrserver.com/v1/create-qr-code/?${qrParams.toString()}`

  } catch (error) {
    console.error('Failed to generate QR code:', error)
  } finally {
    loading.value = false
  }
}

const copyQRCode = async () => {
  try {
    let targetUrl

    if (useCustomLink.value && customLink.value) {
      targetUrl = customLink.value
    } else {
      targetUrl = `https://promotion.mercylife.cc/p/${props.promotion.id}?ref=${props.promotion.reference_code || 'ABC123'}`
    }

    await navigator.clipboard.writeText(targetUrl)
    console.log('Promotion URL copied to clipboard')
  } catch (err) {
    console.error('Failed to copy URL:', err)
  }
}

const downloadQRCode = () => {
  if (!qrCodeUrl.value) return

  const link = document.createElement('a')
  link.href = qrCodeUrl.value
  link.download = `promotion-${props.promotion.id}-qr.${qrOptions.value.format}`
  link.click()
}

const shareQRCode = async () => {
  if (!navigator.share || !qrCodeUrl.value) {
    console.log('Web Share API not supported')
    return
  }

  try {
    await navigator.share({
      title: `推廣活動：${props.promotion.title}`,
      text: '掃描 QR 碼參與推廣活動',
      url: useCustomLink.value && customLink.value ? customLink.value : `https://promotion.mercylife.cc/p/${props.promotion.id}`
    })
  } catch (err) {
    console.error('Error sharing:', err)
  }
}

// Watch for modal open to reset form
watch(() => props.showModal, (newVal) => {
  if (newVal) {
    resetForm()
  }
})
</script>