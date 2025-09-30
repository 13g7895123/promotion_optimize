<template>
  <div
    v-if="showLinkGeneratorModal"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    @click.self="closeModal"
  >
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-3xl max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          推廣連結生成器
        </h3>
        <button
          @click="closeModal"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <form @submit.prevent="handleGenerateLink">
        <!-- Server Selection -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            選擇伺服器 *
          </label>
          <select
            v-model="form.server_id"
            required
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
          >
            <option value="">請選擇伺服器</option>
            <option value="1">測試伺服器 1</option>
            <option value="2">測試伺服器 2</option>
            <option value="3">測試伺服器 3</option>
          </select>
        </div>

        <!-- Campaign Information -->
        <div class="mb-6">
          <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4">活動資訊</h4>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                活動名稱 *
              </label>
              <input
                v-model="form.campaign_name"
                type="text"
                required
                placeholder="例如：春季推廣活動"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                連結類型
              </label>
              <select
                v-model="linkType"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
              >
                <option value="general">一般推廣連結</option>
                <option value="referral">推薦連結</option>
                <option value="social">社交媒體連結</option>
                <option value="email">電子郵件連結</option>
              </select>
            </div>
          </div>
        </div>

        <!-- UTM Parameters -->
        <div class="mb-6">
          <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4">UTM 追蹤參數</h4>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                UTM Source
                <span class="text-xs text-gray-500 dark:text-gray-400">(流量來源)</span>
              </label>
              <input
                v-model="form.utm_source"
                type="text"
                placeholder="例如：facebook, google, email"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                UTM Medium
                <span class="text-xs text-gray-500 dark:text-gray-400">(媒介)</span>
              </label>
              <input
                v-model="form.utm_medium"
                type="text"
                placeholder="例如：cpc, social, email"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
              />
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                UTM Campaign
                <span class="text-xs text-gray-500 dark:text-gray-400">(活動名稱)</span>
              </label>
              <input
                v-model="form.utm_campaign"
                type="text"
                placeholder="例如：spring_promotion_2024"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
              />
            </div>
          </div>
        </div>

        <!-- Custom Parameters -->
        <div class="mb-6">
          <div class="flex items-center justify-between mb-4">
            <h4 class="text-md font-semibold text-gray-900 dark:text-white">自定義參數</h4>
            <button
              type="button"
              @click="addCustomParam"
              class="flex items-center px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors"
            >
              <PlusIcon class="w-4 h-4 mr-1" />
              新增參數
            </button>
          </div>

          <div v-if="customParams.length > 0" class="space-y-2">
            <div
              v-for="(param, index) in customParams"
              :key="index"
              class="flex items-center space-x-2"
            >
              <input
                v-model="param.key"
                type="text"
                placeholder="參數名稱"
                class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
              />
              <input
                v-model="param.value"
                type="text"
                placeholder="參數值"
                class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
              />
              <button
                type="button"
                @click="removeCustomParam(index)"
                class="p-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
              >
                <TrashIcon class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>

        <!-- Preview Section -->
        <div v-if="previewLink" class="mb-6">
          <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4">連結預覽</h4>

          <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">生成的連結：</span>
              <button
                type="button"
                @click="copyLink"
                class="flex items-center px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded transition-colors"
              >
                <DocumentDuplicateIcon class="w-4 h-4 mr-1" />
                複製
              </button>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded p-3 font-mono text-sm break-all border">
              {{ previewLink }}
            </div>

            <!-- QR Code Section -->
            <div class="mt-4 flex items-center space-x-4">
              <button
                type="button"
                @click="generateQR"
                class="flex items-center px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm rounded-lg transition-colors"
              >
                <QrCodeIcon class="w-4 h-4 mr-2" />
                生成 QR 碼
              </button>

              <div v-if="qrCodeUrl" class="flex items-center space-x-2">
                <img :src="qrCodeUrl" alt="QR Code" class="w-16 h-16 border rounded" />
                <button
                  @click="downloadQR"
                  class="flex items-center px-2 py-1 bg-gray-600 hover:bg-gray-700 text-white text-sm rounded transition-colors"
                >
                  <ArrowDownTrayIcon class="w-4 h-4 mr-1" />
                  下載
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Link Types Help -->
        <div class="mb-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
          <h5 class="text-sm font-semibold text-blue-800 dark:text-blue-200 mb-2">連結類型說明</h5>
          <div class="text-xs text-blue-700 dark:text-blue-300 space-y-1">
            <p><strong>一般推廣連結：</strong>適用於各種推廣活動</p>
            <p><strong>推薦連結：</strong>包含推薦人資訊，用於推薦獎勵</p>
            <p><strong>社交媒體連結：</strong>優化用於社交平台分享</p>
            <p><strong>電子郵件連結：</strong>適用於郵件行銷活動</p>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end space-x-4">
          <button
            type="button"
            @click="closeModal"
            class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
          >
            取消
          </button>

          <button
            type="button"
            @click="previewLinkGeneration"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
          >
            預覽連結
          </button>

          <button
            type="submit"
            :disabled="loading || !form.server_id || !form.campaign_name"
            class="px-4 py-2 bg-primary-600 hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg transition-colors"
          >
            <span v-if="loading" class="flex items-center">
              <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
              生成中...
            </span>
            <span v-else>生成並保存</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import {
  XMarkIcon,
  PlusIcon,
  TrashIcon,
  DocumentDuplicateIcon,
  QrCodeIcon,
  ArrowDownTrayIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  showLinkGeneratorModal: Boolean
})

const emit = defineEmits(['close', 'generated'])

const {
  generatePromotionLink,
  generateQRCode,
  linkForm: form,
  resetLinkForm,
  loading
} = usePromotionManagement()

// Local reactive data
const linkType = ref('general')
const customParams = ref([])
const previewLink = ref('')
const qrCodeUrl = ref('')

// Methods
const closeModal = () => {
  resetForm()
  emit('close')
}

const resetForm = () => {
  resetLinkForm()
  linkType.value = 'general'
  customParams.value = []
  previewLink.value = ''
  qrCodeUrl.value = ''
}

const addCustomParam = () => {
  customParams.value.push({ key: '', value: '' })
}

const removeCustomParam = (index) => {
  customParams.value.splice(index, 1)
}

const previewLinkGeneration = () => {
  if (!form.value.server_id || !form.value.campaign_name) return

  const baseUrl = 'https://promotion.mercylife.cc/p'
  const params = new URLSearchParams()

  // Add UTM parameters
  if (form.value.utm_source) params.append('utm_source', form.value.utm_source)
  if (form.value.utm_medium) params.append('utm_medium', form.value.utm_medium)
  if (form.value.utm_campaign) params.append('utm_campaign', form.value.utm_campaign)

  // Add campaign info
  params.append('campaign', form.value.campaign_name)
  params.append('server', form.value.server_id)
  params.append('type', linkType.value)

  // Add custom parameters
  customParams.value.forEach(param => {
    if (param.key && param.value) {
      params.append(param.key, param.value)
    }
  })

  // Generate unique tracking ID
  const trackingId = Math.random().toString(36).substr(2, 9)
  params.append('ref', trackingId)

  previewLink.value = `${baseUrl}?${params.toString()}`
}

const handleGenerateLink = async () => {
  previewLinkGeneration()

  if (!previewLink.value) return

  const linkData = {
    server_id: form.value.server_id,
    campaign_name: form.value.campaign_name,
    utm_source: form.value.utm_source,
    utm_medium: form.value.utm_medium,
    utm_campaign: form.value.utm_campaign,
    link_type: linkType.value,
    custom_params: customParams.value.reduce((acc, param) => {
      if (param.key && param.value) {
        acc[param.key] = param.value
      }
      return acc
    }, {}),
    generated_url: previewLink.value
  }

  const result = await generatePromotionLink(linkData)

  if (result.success) {
    emit('generated', {
      ...result.data,
      url: previewLink.value
    })
    console.log('Promotion link generated successfully')
  } else {
    console.error('Failed to generate promotion link:', result.error)
  }
}

const copyLink = async () => {
  try {
    await navigator.clipboard.writeText(previewLink.value)
    console.log('Link copied to clipboard')
  } catch (err) {
    console.error('Failed to copy link:', err)
  }
}

const generateQR = async () => {
  if (!previewLink.value) return

  try {
    // Mock QR code generation - in real app this would call the API
    qrCodeUrl.value = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(previewLink.value)}`
  } catch (error) {
    console.error('Failed to generate QR code:', error)
  }
}

const downloadQR = () => {
  if (!qrCodeUrl.value) return

  const link = document.createElement('a')
  link.href = qrCodeUrl.value
  link.download = `promotion-qr-${Date.now()}.png`
  link.click()
}

// Auto-populate UTM campaign when campaign name changes
watch(() => form.value.campaign_name, (newName) => {
  if (newName && !form.value.utm_campaign) {
    form.value.utm_campaign = newName.toLowerCase().replace(/\s+/g, '_')
  }
})

// Reset form when modal opens
watch(() => props.showLinkGeneratorModal, (newVal) => {
  if (newVal) {
    resetForm()
  }
})
</script>