<template>
  <div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
          權限管理系統
        </h2>
        <div class="flex items-center space-x-4">
          <!-- View Toggle -->
          <div class="flex items-center space-x-2">
            <button
              @click="activeTab = 'roles'"
              :class="[
                'px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                activeTab === 'roles'
                  ? 'bg-primary-600 text-white'
                  : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'
              ]"
            >
              角色管理
            </button>
            <button
              @click="activeTab = 'permissions'"
              :class="[
                'px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                activeTab === 'permissions'
                  ? 'bg-primary-600 text-white'
                  : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'
              ]"
            >
              權限管理
            </button>
          </div>

          <!-- Add Button -->
          <button
            v-if="activeTab === 'roles'"
            @click="openRoleModal()"
            class="flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors"
          >
            <PlusIcon class="w-4 h-4 mr-2" />
            新增角色
          </button>

          <button
            v-if="activeTab === 'permissions'"
            @click="openPermissionModal()"
            class="flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors"
          >
            <PlusIcon class="w-4 h-4 mr-2" />
            新增權限
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto mb-4"></div>
        <p class="text-gray-600 dark:text-gray-300">載入資料中...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6">
        <div class="flex items-center">
          <ExclamationTriangleIcon class="w-5 h-5 text-red-600 dark:text-red-400 mr-2" />
          <span class="text-red-600 dark:text-red-400">{{ error }}</span>
          <button @click="refreshData()" class="ml-4 text-sm text-red-600 hover:text-red-800 underline">
            重試
          </button>
        </div>
      </div>

      <!-- Roles Tab -->
      <div v-else-if="activeTab === 'roles'" class="space-y-6">
        <!-- Roles Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="role in roles"
            :key="role.id"
            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 hover:shadow-md transition-shadow"
          >
            <!-- Role Header -->
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center">
                <div
                  class="w-12 h-12 rounded-lg flex items-center justify-center"
                  :class="getRoleLevelColor(role.level)"
                >
                  <ShieldCheckIcon class="w-6 h-6" />
                </div>
                <div class="ml-3">
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ role.display_name }}
                  </h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ formatRoleLevel(role.level) }}
                  </p>
                </div>
              </div>

              <!-- Role Actions -->
              <div class="flex items-center space-x-2">
                <button
                  @click="openAssignModal(role)"
                  class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 transition-colors"
                  title="分配權限"
                >
                  <CogIcon class="w-4 h-4" />
                </button>
                <button
                  @click="openRoleModal(role)"
                  class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300 transition-colors"
                  title="編輯角色"
                >
                  <PencilIcon class="w-4 h-4" />
                </button>
                <button
                  @click="handleDeleteRole(role)"
                  class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition-colors"
                  title="刪除角色"
                >
                  <TrashIcon class="w-4 h-4" />
                </button>
              </div>
            </div>

            <!-- Role Description -->
            <p v-if="role.description" class="text-sm text-gray-600 dark:text-gray-400 mb-4">
              {{ role.description }}
            </p>

            <!-- Role Stats -->
            <div class="grid grid-cols-2 gap-4 pt-4 border-t dark:border-gray-700">
              <div class="text-center">
                <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                  {{ rolePermissionCounts[role.id] || 0 }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">權限數量</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                  {{ roleUserCounts[role.id] || 0 }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">用戶數量</div>
              </div>
            </div>

            <!-- Status Badge -->
            <div class="mt-4 flex justify-center">
              <span
                :class="[
                  'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                  role.is_active
                    ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
                    : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
                ]"
              >
                {{ role.is_active ? '啟用' : '停用' }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Permissions Tab -->
      <div v-else-if="activeTab === 'permissions'" class="space-y-6">
        <!-- Search -->
        <div class="flex items-center space-x-4">
          <div class="relative flex-1">
            <input
              v-model="searchQuery"
              @input="onSearchPermissions"
              type="text"
              placeholder="搜尋權限..."
              class="w-full px-4 py-2 pl-10 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            />
            <MagnifyingGlassIcon class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" />
          </div>

          <select
            v-model="selectedResource"
            @change="onResourceChange"
            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          >
            <option value="">所有資源</option>
            <option value="users">用戶管理</option>
            <option value="servers">伺服器管理</option>
            <option value="promotions">推廣管理</option>
            <option value="rewards">獎勵管理</option>
            <option value="statistics">統計報表</option>
            <option value="system">系統設定</option>
          </select>
        </div>

        <!-- Permissions by Resource -->
        <div v-for="(resourcePermissions, resource) in groupedPermissions" :key="resource" class="space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
              <ServerIcon class="w-5 h-5 mr-2 text-primary-600 dark:text-primary-400" />
              {{ formatResource(resource) }}
            </h3>
            <span class="text-sm text-gray-500 dark:text-gray-400">
              {{ resourcePermissions.length }} 個權限
            </span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <div
              v-for="permission in resourcePermissions"
              :key="permission.id"
              class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow"
            >
              <!-- Permission Header -->
              <div class="flex items-center justify-between mb-2">
                <div class="flex items-center">
                  <div class="w-8 h-8 rounded bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center">
                    <KeyIcon class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                  </div>
                  <div class="ml-2">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                      {{ formatAction(permission.action) }}
                    </h4>
                  </div>
                </div>

                <!-- Permission Actions -->
                <div class="flex items-center space-x-1">
                  <button
                    @click="openPermissionModal(permission)"
                    class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300 transition-colors"
                    title="編輯權限"
                  >
                    <PencilIcon class="w-3 h-3" />
                  </button>
                  <button
                    @click="handleDeletePermission(permission)"
                    class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition-colors"
                    title="刪除權限"
                  >
                    <TrashIcon class="w-3 h-3" />
                  </button>
                </div>
              </div>

              <!-- Permission Description -->
              <p v-if="permission.description" class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                {{ permission.description }}
              </p>

              <!-- Permission Code -->
              <div class="bg-gray-100 dark:bg-gray-700 rounded px-2 py-1">
                <code class="text-xs text-gray-800 dark:text-gray-200">
                  {{ permission.name }}
                </code>
              </div>

              <!-- Status -->
              <div class="mt-2 text-right">
                <span
                  :class="[
                    'inline-flex px-1.5 py-0.5 text-xs font-semibold rounded',
                    permission.is_active
                      ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
                      : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
                  ]"
                >
                  {{ permission.is_active ? '啟用' : '停用' }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Role Modal -->
    <div v-if="showRoleModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
          {{ roleForm.id ? '編輯角色' : '新增角色' }}
        </h3>

        <form @submit.prevent="handleSaveRole" class="space-y-4">
          <!-- Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              角色名稱 *
            </label>
            <input
              v-model="roleForm.name"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            />
          </div>

          <!-- Display Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              顯示名稱 *
            </label>
            <input
              v-model="roleForm.display_name"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            />
          </div>

          <!-- Level -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              權限等級 *
            </label>
            <select
              v-model="roleForm.level"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            >
              <option value="1">1 - 一般用戶</option>
              <option value="2">2 - 審核員</option>
              <option value="3">3 - 服主</option>
              <option value="4">4 - 管理員</option>
              <option value="5">5 - 超級管理員</option>
            </select>
          </div>

          <!-- Description -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              描述
            </label>
            <textarea
              v-model="roleForm.description"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            ></textarea>
          </div>

          <!-- Active Status -->
          <div class="flex items-center">
            <input
              v-model="roleForm.is_active"
              type="checkbox"
              id="role-active"
              class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
            />
            <label for="role-active" class="ml-2 block text-sm text-gray-900 dark:text-white">
              啟用此角色
            </label>
          </div>

          <!-- Modal Actions -->
          <div class="flex justify-end space-x-3 mt-6">
            <button
              type="button"
              @click="showRoleModal = false"
              class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200"
            >
              取消
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 disabled:opacity-50 transition-colors duration-200"
            >
              {{ loading ? '保存中...' : '保存' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Permission Modal -->
    <div v-if="showPermissionModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
          {{ permissionForm.id ? '編輯權限' : '新增權限' }}
        </h3>

        <form @submit.prevent="handleSavePermission" class="space-y-4">
          <!-- Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              權限名稱 *
            </label>
            <input
              v-model="permissionForm.name"
              type="text"
              required
              placeholder="例如：users-create"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            />
          </div>

          <!-- Display Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              顯示名稱 *
            </label>
            <input
              v-model="permissionForm.display_name"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            />
          </div>

          <!-- Resource -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              資源 *
            </label>
            <select
              v-model="permissionForm.resource"
              required
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            >
              <option value="">請選擇資源</option>
              <option value="users">用戶管理</option>
              <option value="servers">伺服器管理</option>
              <option value="promotions">推廣管理</option>
              <option value="rewards">獎勵管理</option>
              <option value="statistics">統計報表</option>
              <option value="system">系統設定</option>
              <option value="permissions">權限管理</option>
              <option value="roles">角色管理</option>
            </select>
          </div>

          <!-- Action -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              操作 *
            </label>
            <select
              v-model="permissionForm.action"
              required
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            >
              <option value="">請選擇操作</option>
              <option value="view">查看</option>
              <option value="create">創建</option>
              <option value="update">更新</option>
              <option value="delete">刪除</option>
              <option value="approve">審核</option>
              <option value="suspend">暫停</option>
              <option value="export">匯出</option>
              <option value="import">匯入</option>
            </select>
          </div>

          <!-- Description -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              描述
            </label>
            <textarea
              v-model="permissionForm.description"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            ></textarea>
          </div>

          <!-- Active Status -->
          <div class="flex items-center">
            <input
              v-model="permissionForm.is_active"
              type="checkbox"
              id="permission-active"
              class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
            />
            <label for="permission-active" class="ml-2 block text-sm text-gray-900 dark:text-white">
              啟用此權限
            </label>
          </div>

          <!-- Modal Actions -->
          <div class="flex justify-end space-x-3 mt-6">
            <button
              type="button"
              @click="showPermissionModal = false"
              class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200"
            >
              取消
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 disabled:opacity-50 transition-colors duration-200"
            >
              {{ loading ? '保存中...' : '保存' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Assign Permissions Modal -->
    <div v-if="showAssignModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full p-6 max-h-[80vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            為角色「{{ selectedRole?.display_name }}」分配權限
          </h3>
          <button
            @click="showAssignModal = false"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
          >
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <!-- Search Permissions -->
        <div class="mb-6">
          <div class="relative">
            <input
              v-model="assignSearchQuery"
              type="text"
              placeholder="搜尋權限..."
              class="w-full px-4 py-2 pl-10 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            />
            <MagnifyingGlassIcon class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" />
          </div>
        </div>

        <!-- Permissions List -->
        <div class="space-y-4 mb-6">
          <div v-for="(resourcePermissions, resource) in filteredGroupedPermissions" :key="resource">
            <div class="flex items-center justify-between mb-2">
              <h4 class="text-md font-semibold text-gray-900 dark:text-white">
                {{ formatResource(resource) }}
              </h4>
              <div class="flex items-center space-x-2">
                <button
                  @click="selectAllResourcePermissions(resource, true)"
                  class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300"
                >
                  全選
                </button>
                <button
                  @click="selectAllResourcePermissions(resource, false)"
                  class="text-xs text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300"
                >
                  全不選
                </button>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 pl-4 border-l-2 border-gray-200 dark:border-gray-700">
              <label
                v-for="permission in resourcePermissions"
                :key="permission.id"
                class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer"
              >
                <input
                  v-model="selectedPermissions"
                  :value="permission.id"
                  type="checkbox"
                  class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                />
                <div class="ml-3">
                  <div class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ formatAction(permission.action) }}
                  </div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ permission.description }}
                  </div>
                </div>
              </label>
            </div>
          </div>
        </div>

        <!-- Modal Actions -->
        <div class="flex justify-end space-x-3">
          <button
            @click="showAssignModal = false"
            class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200"
          >
            取消
          </button>
          <button
            @click="handleAssignPermissions"
            :disabled="loading"
            class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 disabled:opacity-50 transition-colors duration-200"
          >
            {{ loading ? '保存中...' : '保存權限' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  PlusIcon,
  ExclamationTriangleIcon,
  ShieldCheckIcon,
  CogIcon,
  PencilIcon,
  TrashIcon,
  MagnifyingGlassIcon,
  ServerIcon,
  KeyIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'

// Use the permission management composable
const {
  permissions,
  groupedPermissions,
  roles,
  loading,
  error,
  selectedRole,
  selectedPermissions,
  showRoleModal,
  showPermissionModal,
  showAssignModal,
  roleForm,
  permissionForm,
  fetchPermissions,
  fetchRoles,
  createRole,
  updateRole,
  deleteRole,
  createPermission,
  updatePermission,
  deletePermission,
  assignPermissionsToRole,
  openRoleModal,
  openPermissionModal,
  openAssignModal,
  formatRoleLevel,
  getRoleLevelColor,
  formatResource,
  formatAction
} = usePermissionManagement()

// UI state
const activeTab = ref('roles')
const searchQuery = ref('')
const selectedResource = ref('')
const assignSearchQuery = ref('')

// Mock data for counts (in real app, this would come from API)
const rolePermissionCounts = ref({})
const roleUserCounts = ref({})

// Computed
const filteredGroupedPermissions = computed(() => {
  if (!assignSearchQuery.value) return groupedPermissions.value

  const filtered = {}
  const query = assignSearchQuery.value.toLowerCase()

  Object.keys(groupedPermissions.value).forEach(resource => {
    const resourcePermissions = groupedPermissions.value[resource].filter(p =>
      p.display_name.toLowerCase().includes(query) ||
      p.description.toLowerCase().includes(query) ||
      formatAction(p.action).toLowerCase().includes(query)
    )

    if (resourcePermissions.length > 0) {
      filtered[resource] = resourcePermissions
    }
  })

  return filtered
})

// Methods
const refreshData = () => {
  if (activeTab.value === 'roles') {
    fetchRoles()
  } else {
    fetchPermissions({ groupByResource: true })
  }
}

const onSearchPermissions = () => {
  // Implement debounced search for permissions
  setTimeout(() => {
    fetchPermissions({
      groupByResource: true,
      search: searchQuery.value,
      resource: selectedResource.value
    })
  }, 300)
}

const onResourceChange = () => {
  fetchPermissions({
    groupByResource: true,
    search: searchQuery.value,
    resource: selectedResource.value
  })
}

const handleSaveRole = async () => {
  if (roleForm.value.id) {
    await updateRole(roleForm.value.id, roleForm.value)
  } else {
    await createRole(roleForm.value)
  }
}

const handleDeleteRole = async (role) => {
  if (confirm(`確定要刪除角色 "${role.display_name}" 嗎？此操作無法撤銷。`)) {
    await deleteRole(role.id)
  }
}

const handleSavePermission = async () => {
  if (permissionForm.value.id) {
    await updatePermission(permissionForm.value.id, permissionForm.value)
  } else {
    await createPermission(permissionForm.value)
  }
}

const handleDeletePermission = async (permission) => {
  if (confirm(`確定要刪除權限 "${permission.display_name}" 嗎？此操作無法撤銷。`)) {
    await deletePermission(permission.id)
  }
}

const handleAssignPermissions = async () => {
  if (selectedRole.value) {
    await assignPermissionsToRole(selectedRole.value.id, selectedPermissions.value)
  }
}

const selectAllResourcePermissions = (resource, select) => {
  const resourcePermissions = groupedPermissions.value[resource] || []
  const permissionIds = resourcePermissions.map(p => p.id)

  if (select) {
    // Add all permissions from this resource
    permissionIds.forEach(id => {
      if (!selectedPermissions.value.includes(id)) {
        selectedPermissions.value.push(id)
      }
    })
  } else {
    // Remove all permissions from this resource
    selectedPermissions.value = selectedPermissions.value.filter(id =>
      !permissionIds.includes(id)
    )
  }
}

// Watch tab changes
watch(activeTab, (newTab) => {
  if (newTab === 'roles') {
    fetchRoles()
  } else {
    fetchPermissions({ groupByResource: true })
  }
})

// Initialize data
onMounted(() => {
  fetchRoles()
  fetchPermissions({ groupByResource: true })
})
</script>