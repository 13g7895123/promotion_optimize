<template>
  <div
    v-if="showCreateModal"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    @click.self="closeModal"
  >
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          新增推廣活動
        </h3>
        <button
          @click="closeModal"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <form @submit.prevent="handleSubmit">
        <!-- Server Selection -->
        <div class="mb-4">
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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <!-- Promotion Type -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              推廣類型 *
            </label>
            <select
              v-model="form.promotion_type"
              required
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            >
              <option value="referral">推薦獎勵</option>
              <option value="click">點擊獎勵</option>
              <option value="signup">註冊獎勵</option>
              <option value="activity">活動獎勵</option>
              <option value="milestone">里程碑獎勵</option>
            </select>
          </div>

          <!-- Status -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              狀態
            </label>
            <select
              v-model="form.status"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            >
              <option value="active">啟用中</option>
              <option value="inactive">已停用</option>
              <option value="draft">草稿</option>
              <option value="pending">待審核</option>
            </select>
          </div>
        </div>

        <!-- Title -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            推廣標題 *
          </label>
          <input
            v-model="form.title"
            type="text"
            required
            placeholder="輸入推廣活動標題"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
          />
        </div>

        <!-- Description -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            推廣描述
          </label>
          <textarea
            v-model="form.description"
            rows="3"
            placeholder="輸入推廣活動描述"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
          ></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <!-- Reward Type -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              獎勵類型 *
            </label>
            <select
              v-model="form.reward_type"
              required
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            >
              <option value="game_currency">遊戲幣</option>
              <option value="items">道具獎勵</option>
              <option value="points">積分獎勵</option>
              <option value="experience">經驗獎勵</option>
              <option value="other">其他獎勵</option>
            </select>
          </div>

          <!-- Reward Amount -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              獎勵數量 *
            </label>
            <input
              v-model="form.reward_amount"
              type="number"
              required
              min="1"
              placeholder="輸入獎勵數量"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            />
          </div>
        </div>

        <!-- Reward Description -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            獎勵說明
          </label>
          <input
            v-model="form.reward_description"
            type="text"
            placeholder="詳細描述獎勵內容"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
          />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <!-- Max Uses -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              最大使用次數
            </label>
            <input
              v-model="form.max_uses"
              type="number"
              min="1"
              placeholder="留空為無限制"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            />
          </div>

          <!-- Max Uses Per User -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              每用戶最大使用次數
            </label>
            <input
              v-model="form.max_uses_per_user"
              type="number"
              min="1"
              placeholder="留空為無限制"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
          <!-- Start Date -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              開始日期
            </label>
            <input
              v-model="form.start_date"
              type="datetime-local"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            />
          </div>

          <!-- End Date -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              結束日期
            </label>
            <input
              v-model="form.end_date"
              type="datetime-local"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            />
          </div>
        </div>

        <!-- Buttons -->
        <div class="flex items-center justify-end space-x-4">
          <button
            type="button"
            @click="closeModal"
            class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
          >
            取消
          </button>
          <button
            type="submit"
            :disabled="loading"
            class="px-4 py-2 bg-primary-600 hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg transition-colors"
          >
            <span v-if="loading" class="flex items-center">
              <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
              建立中...
            </span>
            <span v-else>建立推廣</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  showCreateModal: Boolean
})

const emit = defineEmits(['close', 'created'])

const { createPromotion, promotionForm: form, loading, resetPromotionForm } = usePromotionManagement()

const handleSubmit = async () => {
  const result = await createPromotion(form.value)

  if (result.success) {
    emit('created', result.promotion)
    closeModal()
  } else {
    console.error('Failed to create promotion:', result.error)
  }
}

const closeModal = () => {
  resetPromotionForm()
  emit('close')
}

// Reset form when modal opens
watch(() => props.showCreateModal, (newVal) => {
  if (newVal) {
    resetPromotionForm()
  }
})
</script>