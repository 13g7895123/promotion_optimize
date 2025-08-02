<template>
  <div class="qr-code-generator">
    <!-- 配置面板 -->
    <div class="config-panel">
      <el-form :model="config" label-position="top" size="small">
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="尺寸">
              <el-select v-model="config.size" @change="generateQRCode">
                <el-option label="小 (128px)" :value="128" />
                <el-option label="中 (256px)" :value="256" />
                <el-option label="大 (512px)" :value="512" />
                <el-option label="特大 (1024px)" :value="1024" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="容錯級別">
              <el-select v-model="config.errorCorrectionLevel" @change="generateQRCode">
                <el-option label="低 (L)" value="L" />
                <el-option label="中 (M)" value="M" />
                <el-option label="高 (Q)" value="Q" />
                <el-option label="最高 (H)" value="H" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="前景色">
              <el-color-picker 
                v-model="config.color.dark" 
                @change="generateQRCode"
                show-alpha
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="背景色">
              <el-color-picker 
                v-model="config.color.light" 
                @change="generateQRCode"
                show-alpha
              />
            </el-form-item>
          </el-col>
        </el-row>

        <el-form-item label="邊距">
          <el-slider 
            v-model="config.margin" 
            :min="0" 
            :max="10"
            @change="generateQRCode"
          />
        </el-form-item>

        <el-form-item label="Logo (可選)">
          <el-upload
            :show-file-list="false"
            :before-upload="handleLogoUpload"
            accept="image/*"
            :disabled="uploading"
          >
            <el-button size="small" :loading="uploading">
              <el-icon><Upload /></el-icon>
              上傳 Logo
            </el-button>
          </el-upload>
          <div v-if="config.logo" class="logo-preview">
            <img :src="config.logo" alt="Logo preview" />
            <el-button 
              size="small" 
              type="danger" 
              :icon="Delete"
              @click="removeLogo"
              circle
            />
          </div>
        </el-form-item>
      </el-form>
    </div>

    <!-- QR Code 預覽 -->
    <div class="preview-panel">
      <div class="preview-container">
        <div v-if="loading" class="loading-container">
          <el-skeleton animated>
            <template #template>
              <el-skeleton-item variant="image" style="width: 256px; height: 256px;" />
            </template>
          </el-skeleton>
        </div>
        
        <div v-else-if="qrCodeUrl" class="qr-code-container">
          <img 
            :src="qrCodeUrl" 
            :alt="`QR Code for ${url}`"
            class="qr-code-image"
            :style="{ width: `${Math.min(config.size, 400)}px` }"
          />
          <div class="qr-info">
            <p class="qr-url">{{ truncateUrl(url) }}</p>
            <div class="qr-stats">
              <span>尺寸: {{ config.size }}x{{ config.size }}</span>
              <span>容錯: {{ config.errorCorrectionLevel }}</span>
            </div>
          </div>
        </div>

        <div v-else-if="error" class="error-container">
          <el-empty :image-size="100" description="生成失敗">
            <template #description>
              <p>{{ error }}</p>
            </template>
            <el-button type="primary" @click="generateQRCode">重新生成</el-button>
          </el-empty>
        </div>

        <div v-else class="empty-container">
          <el-empty :image-size="100" description="請提供要生成 QR Code 的網址" />
        </div>
      </div>

      <!-- 操作按鈕 -->
      <div v-if="qrCodeUrl && !loading" class="action-buttons">
        <el-button 
          type="primary" 
          :icon="Download"
          @click="downloadQRCode"
          :loading="downloading"
        >
          下載 PNG
        </el-button>
        <el-button 
          :icon="CopyDocument"
          @click="copyQRCode"
          :loading="copying"
        >
          複製圖片
        </el-button>
        <el-button 
          :icon="Share"
          @click="shareQRCode"
          v-if="canShare"
        >
          分享
        </el-button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Upload, Delete, Download, CopyDocument, Share } from '@element-plus/icons-vue'
import type { QRCodeOptions } from '~/types'

interface Props {
  url: string
  options?: Partial<QRCodeOptions>
}

interface Emits {
  (e: 'generated', qrCodeUrl: string): void
  (e: 'error', error: string): void
}

const props = withDefaults(defineProps<Props>(), {
  options: () => ({}),
})

const emit = defineEmits<Emits>()

// 響應式數據
const config = ref<QRCodeOptions>({
  size: 256,
  errorCorrectionLevel: 'M',
  margin: 4,
  color: {
    dark: '#000000',
    light: '#ffffff',
  },
  ...props.options,
})

const qrCodeUrl = ref<string>('')
const loading = ref(false)
const error = ref<string>('')
const uploading = ref(false)
const downloading = ref(false)
const copying = ref(false)

// 計算屬性
const canShare = computed(() => {
  return 'share' in navigator && 'canShare' in navigator
})

// 方法
const generateQRCode = async () => {
  if (!props.url) {
    error.value = '請提供有效的網址'
    return
  }

  loading.value = true
  error.value = ''

  try {
    // 動態導入 QRCode 生成庫
    const QRCode = await import('qrcode')
    
    const options = {
      width: config.value.size,
      margin: config.value.margin,
      color: {
        dark: config.value.color.dark,
        light: config.value.color.light,
      },
      errorCorrectionLevel: config.value.errorCorrectionLevel,
    }

    let qrDataUrl = await QRCode.toDataURL(props.url, options)

    // 如果有 Logo，添加到 QR Code 中心
    if (config.value.logo) {
      qrDataUrl = await addLogoToQRCode(qrDataUrl, config.value.logo)
    }

    qrCodeUrl.value = qrDataUrl
    emit('generated', qrDataUrl)
  } catch (err: any) {
    error.value = err.message || '生成 QR Code 失敗'
    emit('error', error.value)
  } finally {
    loading.value = false
  }
}

const addLogoToQRCode = async (qrDataUrl: string, logoUrl: string): Promise<string> => {
  return new Promise((resolve, reject) => {
    const canvas = document.createElement('canvas')
    const ctx = canvas.getContext('2d')
    if (!ctx) {
      reject(new Error('無法創建 Canvas 上下文'))
      return
    }

    const qrImage = new Image()
    const logoImage = new Image()

    qrImage.onload = () => {
      canvas.width = qrImage.width
      canvas.height = qrImage.height
      
      // 繪製 QR Code
      ctx.drawImage(qrImage, 0, 0)

      logoImage.onload = () => {
        // 計算 Logo 尺寸（QR Code 尺寸的 20%）
        const logoSize = Math.floor(qrImage.width * 0.2)
        const logoX = (qrImage.width - logoSize) / 2
        const logoY = (qrImage.height - logoSize) / 2

        // 添加白色背景（確保 Logo 清晰可見）
        const padding = 4
        ctx.fillStyle = '#ffffff'
        ctx.fillRect(logoX - padding, logoY - padding, logoSize + padding * 2, logoSize + padding * 2)

        // 繪製 Logo
        ctx.drawImage(logoImage, logoX, logoY, logoSize, logoSize)

        resolve(canvas.toDataURL('image/png'))
      }

      logoImage.onerror = () => {
        // Logo 載入失敗，返回原始 QR Code
        resolve(qrDataUrl)
      }

      logoImage.src = logoUrl
    }

    qrImage.onerror = () => {
      reject(new Error('QR Code 圖片載入失敗'))
    }

    qrImage.src = qrDataUrl
  })
}

const handleLogoUpload = (file: File): boolean => {
  const isImage = file.type.startsWith('image/')
  if (!isImage) {
    ElMessage.error('只能上傳圖片文件')
    return false
  }

  const isLt2M = file.size / 1024 / 1024 < 2
  if (!isLt2M) {
    ElMessage.error('圖片大小不能超過 2MB')
    return false
  }

  uploading.value = true
  
  const reader = new FileReader()
  reader.onload = (e) => {
    config.value.logo = e.target?.result as string
    uploading.value = false
    generateQRCode()
  }
  reader.onerror = () => {
    ElMessage.error('圖片讀取失敗')
    uploading.value = false
  }
  reader.readAsDataURL(file)

  return false // 阻止自動上傳
}

const removeLogo = () => {
  config.value.logo = undefined
  generateQRCode()
}

const downloadQRCode = () => {
  if (!qrCodeUrl.value) return

  downloading.value = true
  
  try {
    const link = document.createElement('a')
    link.download = `qrcode-${Date.now()}.png`
    link.href = qrCodeUrl.value
    link.click()
    
    ElMessage.success('QR Code 已下載')
  } catch (err) {
    ElMessage.error('下載失敗')
  } finally {
    downloading.value = false
  }
}

const copyQRCode = async () => {
  if (!qrCodeUrl.value) return

  copying.value = true

  try {
    // 將 Data URL 轉換為 Blob
    const response = await fetch(qrCodeUrl.value)
    const blob = await response.blob()
    
    // 複製到剪貼簿
    await navigator.clipboard.write([
      new ClipboardItem({ 'image/png': blob })
    ])
    
    ElMessage.success('QR Code 已複製到剪貼簿')
  } catch (err) {
    ElMessage.error('複製失敗，請嘗試右鍵保存圖片')
  } finally {
    copying.value = false
  }
}

const shareQRCode = async () => {
  if (!qrCodeUrl.value || !canShare.value) return

  try {
    const response = await fetch(qrCodeUrl.value)
    const blob = await response.blob()
    const file = new File([blob], 'qrcode.png', { type: 'image/png' })

    if (navigator.canShare({ files: [file] })) {
      await navigator.share({
        title: 'QR Code',
        text: `QR Code: ${props.url}`,
        files: [file],
      })
    } else {
      await navigator.share({
        title: 'QR Code',
        text: `QR Code: ${props.url}`,
        url: props.url,
      })
    }
  } catch (err) {
    if ((err as any).name !== 'AbortError') {
      ElMessage.error('分享失敗')
    }
  }
}

const truncateUrl = (url: string, maxLength: number = 50) => {
  return url.length > maxLength ? `${url.substring(0, maxLength)}...` : url
}

// 生命週期
watch(() => props.url, () => {
  if (props.url) {
    generateQRCode()
  }
}, { immediate: true })

watch(() => props.options, (newOptions) => {
  if (newOptions) {
    config.value = { ...config.value, ...newOptions }
    generateQRCode()
  }
}, { deep: true })
</script>

<style scoped lang="scss">
.qr-code-generator {
  display: flex;
  gap: 24px;

  @media (max-width: 768px) {
    flex-direction: column;
  }

  .config-panel {
    flex: 0 0 300px;
    background: var(--el-bg-color-page);
    border-radius: 8px;
    padding: 16px;

    @media (max-width: 768px) {
      flex: none;
    }

    .logo-preview {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-top: 8px;

      img {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 4px;
        border: 1px solid var(--el-border-color-light);
      }
    }
  }

  .preview-panel {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;

    .preview-container {
      width: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
      margin-bottom: 24px;

      .loading-container,
      .error-container,
      .empty-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 300px;
      }

      .qr-code-container {
        text-align: center;

        .qr-code-image {
          max-width: 100%;
          border-radius: 8px;
          box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
          margin-bottom: 16px;
        }

        .qr-info {
          .qr-url {
            font-size: 14px;
            color: var(--el-text-color-primary);
            margin: 0 0 8px 0;
            word-break: break-all;
          }

          .qr-stats {
            display: flex;
            justify-content: center;
            gap: 16px;
            font-size: 12px;
            color: var(--el-text-color-secondary);

            @media (max-width: 480px) {
              flex-direction: column;
              gap: 4px;
            }
          }
        }
      }
    }

    .action-buttons {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      justify-content: center;

      @media (max-width: 480px) {
        width: 100%;
        
        .el-button {
          flex: 1;
        }
      }
    }
  }
}

// Element Plus 樣式覆蓋
:deep(.el-form-item) {
  margin-bottom: 16px;
}

:deep(.el-slider) {
  margin: 0 8px;
}
</style>