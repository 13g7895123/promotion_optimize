<template>
  <div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg-custom shadow-sm p-6">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
          用戶管理系統
        </h2>
        <div class="flex items-center space-x-4">
          <!-- Search -->
          <div class="relative">
            <input
              v-model="searchQuery"
              @input="onSearch"
              type="text"
              placeholder="搜索用戶..."
              class="w-64 px-4 py-2 pl-10 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            />
            <MagnifyingGlassIcon class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" />
          </div>

          <!-- Role Filter -->
          <select
            v-model="selectedRole"
            @change="onFilterChange"
            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          >
            <option value="">所有角色</option>
            <option value="super_admin">超級管理員</option>
            <option value="admin">管理員</option>
            <option value="server_owner">服主</option>
            <option value="moderator">審核員</option>
            <option value="user">一般用戶</option>
          </select>

          <!-- Status Filter -->
          <select
            v-model="selectedStatus"
            @change="onFilterChange"
            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          >
            <option value="">所有狀態</option>
            <option value="active">啟用</option>
            <option value="inactive">停用</option>
            <option value="suspended">暫停</option>
            <option value="pending">待審核</option>
          </select>

          <!-- Add User Button -->
          <button
            v-if="canAccess('users.create')"
            @click="openCreateModal"
            class="flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors"
          >
            <PlusIcon class="w-4 h-4 mr-2" />
            新增用戶
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto mb-4"></div>
        <p class="text-gray-600 dark:text-gray-300">載入用戶資料中...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6">
        <div class="flex items-center">
          <ExclamationTriangleIcon class="w-5 h-5 text-red-600 dark:text-red-400 mr-2" />
          <span class="text-red-600 dark:text-red-400">{{ error }}</span>
          <button @click="fetchUsers()" class="ml-4 text-sm text-red-600 hover:text-red-800 underline">
            重試
          </button>
        </div>
      </div>

      <!-- Users Table -->
      <div v-else class="space-y-4">
        <!-- Stats Row -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
            <div class="flex items-center">
              <UsersIcon class="w-8 h-8 text-blue-600 dark:text-blue-400" />
              <div class="ml-3">
                <p class="text-sm text-blue-600 dark:text-blue-400">總用戶數</p>
                <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ totalUsers }}</p>
              </div>
            </div>
          </div>

          <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
            <div class="flex items-center">
              <CheckCircleIcon class="w-8 h-8 text-green-600 dark:text-green-400" />
              <div class="ml-3">
                <p class="text-sm text-green-600 dark:text-green-400">啟用用戶</p>
                <p class="text-2xl font-bold text-green-900 dark:text-green-100">{{ activeUsersCount }}</p>
              </div>
            </div>
          </div>

          <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
            <div class="flex items-center">
              <ShieldCheckIcon class="w-8 h-8 text-purple-600 dark:text-purple-400" />
              <div class="ml-3">
                <p class="text-sm text-purple-600 dark:text-purple-400">管理員</p>
                <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">{{ adminUsersCount }}</p>
              </div>
            </div>
          </div>

          <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
            <div class="flex items-center">
              <ClockIcon class="w-8 h-8 text-yellow-600 dark:text-yellow-400" />
              <div class="ml-3">
                <p class="text-sm text-yellow-600 dark:text-yellow-400">待審核</p>
                <p class="text-2xl font-bold text-yellow-900 dark:text-yellow-100">{{ pendingUsersCount }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                {{ t('auth.user') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                {{ t('auth.role') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                {{ t('auth.status') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                {{ t('auth.last_login') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                {{ t('auth.actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
              <!-- User Info -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                    <span class="text-primary-600 dark:text-primary-400 font-semibold text-sm">
                      {{ (user.name || user.username || 'U').charAt(0).toUpperCase() }}
                    </span>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                      {{ user.name || user.username }}
                      <span v-if="user.username && user.name" class="text-xs text-gray-500">({{ user.username }})</span>
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ user.email }}</div>
                  </div>
                </div>
              </td>

              <!-- Role -->
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                  :class="getRoleColor(user.role)"
                >
                  {{ formatRole(user.role) }}
                </span>
              </td>

              <!-- Status -->
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                  :class="getStatusColor(user.status)"
                >
                  {{ formatStatus(user.status) }}
                </span>
              </td>

              <!-- Last Login -->
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                {{ formatLastLogin(user.last_login) }}
              </td>

              <!-- Actions -->
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex items-center space-x-2">
                  <!-- View Details -->
                  <button
                    @click="openDetailModal(user)"
                    class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300 transition-colors duration-200"
                    title="查看詳情"
                  >
                    <EyeIcon class="w-4 h-4" />
                  </button>

                  <!-- Edit -->
                  <button
                    @click="openEditModal(user)"
                    class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 transition-colors duration-200"
                    title="編輯用戶"
                  >
                    <PencilIcon class="w-4 h-4" />
                  </button>

                  <!-- Toggle Status -->
                  <button
                    @click="handleToggleStatus(user)"
                    :class="[
                      'transition-colors duration-200',
                      user.status === 'active'
                        ? 'text-yellow-600 dark:text-yellow-400 hover:text-yellow-900 dark:hover:text-yellow-300'
                        : 'text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300'
                    ]"
                    :title="user.status === 'active' ? '停用用戶' : '啟用用戶'"
                  >
                    <component :is="user.status === 'active' ? 'XCircleIcon' : 'CheckCircleIcon'" class="w-4 h-4" />
                  </button>

                  <!-- Delete -->
                  <button
                    @click="handleDeleteUser(user)"
                    class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition-colors duration-200"
                    title="刪除用戶"
                  >
                    <TrashIcon class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- No Users Found -->
        <div v-if="users.length === 0 && !loading" class="text-center py-12">
          <UsersIcon class="w-12 h-12 text-gray-400 mx-auto mb-4" />
          <p class="text-gray-500 dark:text-gray-400">找不到用戶資料</p>
        </div>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="flex items-center justify-between mt-6">
          <div class="text-sm text-gray-700 dark:text-gray-300">
            顯示第 {{ (currentPage - 1) * perPage + 1 }} 到 {{ Math.min(currentPage * perPage, totalUsers) }} 項，共 {{ totalUsers }} 項
          </div>

          <div class="flex items-center space-x-2">
            <button
              @click="prevPage()"
              :disabled="currentPage === 1"
              class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              <ChevronLeftIcon class="w-4 h-4" />
            </button>

            <span class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300">
              第 {{ currentPage }} / {{ totalPages }} 頁
            </span>

            <button
              @click="nextPage()"
              :disabled="currentPage === totalPages"
              class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              <ChevronRightIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create User Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
          新增用戶
        </h3>

        <form @submit.prevent="handleCreateUser" class="space-y-4">
          <!-- Username -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              用戶名 *
            </label>
            <input
              v-model="userForm.username"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            />
          </div>

          <!-- Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              姓名 *
            </label>
            <input
              v-model="userForm.name"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            />
          </div>

          <!-- Email -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              電子郵箱 *
            </label>
            <input
              v-model="userForm.email"
              type="email"
              required
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            />
          </div>

          <!-- Role -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              角色 *
            </label>
            <select
              v-model="userForm.role"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            >
              <option value="user">一般用戶</option>
              <option value="moderator">審核員</option>
              <option value="server_owner">服主</option>
              <option value="admin">管理員</option>
              <option value="super_admin">超級管理員</option>
            </select>
          </div>

          <!-- Password -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              密碼 *
            </label>
            <input
              v-model="userForm.password"
              type="password"
              required
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            />
          </div>

          <!-- Confirm Password -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              確認密碼 *
            </label>
            <input
              v-model="userForm.password_confirmation"
              type="password"
              required
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            />
          </div>

          <!-- Modal Actions -->
          <div class="flex justify-end space-x-3 mt-6">
            <button
              type="button"
              @click="showCreateModal = false"
              class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200"
            >
              取消
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 disabled:opacity-50 transition-colors duration-200"
            >
              {{ loading ? '創建中...' : '創建用戶' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit User Modal -->
    <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
          編輯用戶
        </h3>

        <form @submit.prevent="handleUpdateUser" class="space-y-4">
          <!-- Username -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              用戶名
            </label>
            <input
              v-model="userForm.username"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            />
          </div>

          <!-- Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              姓名
            </label>
            <input
              v-model="userForm.name"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            />
          </div>

          <!-- Email -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              電子郵箱
            </label>
            <input
              v-model="userForm.email"
              type="email"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            />
          </div>

          <!-- Role -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              角色
            </label>
            <select
              v-model="userForm.role"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            >
              <option value="user">一般用戶</option>
              <option value="moderator">審核員</option>
              <option value="server_owner">服主</option>
              <option value="admin">管理員</option>
              <option value="super_admin">超級管理員</option>
            </select>
          </div>

          <!-- Status -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              狀態
            </label>
            <select
              v-model="userForm.status"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            >
              <option value="active">啟用</option>
              <option value="inactive">停用</option>
              <option value="suspended">暫停</option>
              <option value="pending">待審核</option>
            </select>
          </div>

          <!-- Modal Actions -->
          <div class="flex justify-end space-x-3 mt-6">
            <button
              type="button"
              @click="showEditModal = false"
              class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200"
            >
              取消
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 disabled:opacity-50 transition-colors duration-200"
            >
              {{ loading ? '保存中...' : '保存更改' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- User Detail Modal -->
    <div v-if="showDetailModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            用戶詳情
          </h3>
          <button
            @click="showDetailModal = false"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
          >
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <div v-if="selectedUser" class="space-y-6">
          <!-- User Basic Info -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                用戶名
              </label>
              <p class="text-sm text-gray-900 dark:text-white">{{ selectedUser.username }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                姓名
              </label>
              <p class="text-sm text-gray-900 dark:text-white">{{ selectedUser.name }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                電子郵箱
              </label>
              <p class="text-sm text-gray-900 dark:text-white">{{ selectedUser.email }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                角色
              </label>
              <span
                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                :class="getRoleColor(selectedUser.role)"
              >
                {{ formatRole(selectedUser.role) }}
              </span>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                狀態
              </label>
              <span
                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                :class="getStatusColor(selectedUser.status)"
              >
                {{ formatStatus(selectedUser.status) }}
              </span>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                最後登入
              </label>
              <p class="text-sm text-gray-900 dark:text-white">{{ formatLastLogin(selectedUser.last_login) }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                註冊時間
              </label>
              <p class="text-sm text-gray-900 dark:text-white">{{ formatLastLogin(selectedUser.created_at) }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                更新時間
              </label>
              <p class="text-sm text-gray-900 dark:text-white">{{ formatLastLogin(selectedUser.updated_at) }}</p>
            </div>
          </div>

          <!-- Additional Stats -->
          <div v-if="selectedUser.stats" class="border-t dark:border-gray-700 pt-6">
            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">統計信息</h4>
            <div class="grid grid-cols-3 gap-4">
              <div class="text-center">
                <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                  {{ selectedUser.stats.promotions || 0 }}
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400">推廣次數</p>
              </div>
              <div class="text-center">
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                  {{ selectedUser.stats.rewards || 0 }}
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400">獲得獎勵</p>
              </div>
              <div class="text-center">
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                  {{ selectedUser.stats.referrals || 0 }}
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400">推薦人數</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  MagnifyingGlassIcon,
  UsersIcon,
  PlusIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  ShieldCheckIcon,
  ClockIcon,
  EyeIcon,
  PencilIcon,
  XCircleIcon,
  TrashIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'

// Use the user management composable
const {
  users,
  totalUsers,
  currentPage,
  perPage,
  selectedUser,
  loading,
  error,
  showCreateModal,
  showEditModal,
  showDetailModal,
  userForm,
  fetchUsers,
  createUser,
  updateUser,
  deleteUser,
  toggleUserStatus,
  openCreateModal,
  openEditModal,
  openDetailModal,
  formatRole,
  formatStatus,
  formatLastLogin,
  getStatusColor,
  getRoleColor,
  totalPages,
  nextPage,
  prevPage
} = useUserManagement()

// Use permissions
const { canAccess, canManageOtherUser } = usePermissions()

// Search and filter states
const searchQuery = ref('')
const selectedRole = ref('')
const selectedStatus = ref('')

// Debounced search
let searchTimeout = null
const onSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchUsers({
      search: searchQuery.value,
      role: selectedRole.value,
      status: selectedStatus.value
    })
  }, 300)
}

// Filter change handler
const onFilterChange = () => {
  fetchUsers({
    search: searchQuery.value,
    role: selectedRole.value,
    status: selectedStatus.value
  })
}

// User action handlers
const handleCreateUser = async () => {
  const result = await createUser({
    username: userForm.value.username,
    email: userForm.value.email,
    name: userForm.value.name,
    role: userForm.value.role,
    password: userForm.value.password,
    password_confirmation: userForm.value.password_confirmation
  })

  if (result.success) {
    // Show success message
    console.log('User created successfully')
  } else {
    // Show error message
    console.error('Failed to create user:', result.error)
  }
}

const handleUpdateUser = async () => {
  const result = await updateUser(userForm.value.id, {
    username: userForm.value.username,
    email: userForm.value.email,
    name: userForm.value.name,
    role: userForm.value.role,
    status: userForm.value.status
  })

  if (result.success) {
    // Show success message
    console.log('User updated successfully')
  } else {
    // Show error message
    console.error('Failed to update user:', result.error)
  }
}

const handleToggleStatus = async (user) => {
  const result = await toggleUserStatus(user.id)

  if (result.success) {
    console.log('User status updated successfully')
  } else {
    console.error('Failed to toggle user status:', result.error)
  }
}

const handleDeleteUser = async (user) => {
  if (confirm(`確定要刪除用戶 "${user.name || user.username}" 嗎？此操作無法撤銷。`)) {
    const result = await deleteUser(user.id)

    if (result.success) {
      console.log('User deleted successfully')
    } else {
      console.error('Failed to delete user:', result.error)
    }
  }
}

// Computed stats
const activeUsersCount = computed(() => {
  return users.value.filter(user => user.status === 'active').length
})

const adminUsersCount = computed(() => {
  return users.value.filter(user => ['admin', 'super_admin'].includes(user.role)).length
})

const pendingUsersCount = computed(() => {
  return users.value.filter(user => user.status === 'pending').length
})

// Initialize data
onMounted(() => {
  fetchUsers()
})
</script>