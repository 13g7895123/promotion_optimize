<template>
  <div class="skeleton-loader" :class="{ 'animate': animate }">
    <!-- Predefined skeleton types -->
    <template v-if="type === 'card'">
      <div class="skeleton-card">
        <div class="skeleton-image" />
        <div class="skeleton-content">
          <div class="skeleton-title" />
          <div class="skeleton-text" />
          <div class="skeleton-text short" />
        </div>
      </div>
    </template>

    <template v-else-if="type === 'list'">
      <div 
        v-for="i in rows" 
        :key="i" 
        class="skeleton-list-item"
      >
        <div class="skeleton-avatar" />
        <div class="skeleton-content">
          <div class="skeleton-title" />
          <div class="skeleton-text" />
        </div>
      </div>
    </template>

    <template v-else-if="type === 'table'">
      <div class="skeleton-table">
        <div class="skeleton-table-header">
          <div 
            v-for="col in columns" 
            :key="col" 
            class="skeleton-table-cell"
          />
        </div>
        <div 
          v-for="i in rows" 
          :key="i" 
          class="skeleton-table-row"
        >
          <div 
            v-for="col in columns" 
            :key="col" 
            class="skeleton-table-cell"
          />
        </div>
      </div>
    </template>

    <template v-else-if="type === 'form'">
      <div class="skeleton-form">
        <div 
          v-for="i in rows" 
          :key="i" 
          class="skeleton-form-item"
        >
          <div class="skeleton-label" />
          <div class="skeleton-input" />
        </div>
      </div>
    </template>

    <template v-else-if="type === 'dashboard'">
      <div class="skeleton-dashboard">
        <!-- Stats Cards -->
        <div class="skeleton-stats">
          <div 
            v-for="i in 4" 
            :key="i" 
            class="skeleton-stat-card"
          >
            <div class="skeleton-stat-icon" />
            <div class="skeleton-stat-content">
              <div class="skeleton-stat-number" />
              <div class="skeleton-stat-label" />
            </div>
          </div>
        </div>
        
        <!-- Chart Area -->
        <div class="skeleton-chart">
          <div class="skeleton-chart-header">
            <div class="skeleton-chart-title" />
            <div class="skeleton-chart-actions" />
          </div>
          <div class="skeleton-chart-content" />
        </div>
        
        <!-- Table Area -->
        <div class="skeleton-table">
          <div class="skeleton-table-header">
            <div v-for="i in 5" :key="i" class="skeleton-table-cell" />
          </div>
          <div v-for="i in 5" :key="i" class="skeleton-table-row">
            <div v-for="j in 5" :key="j" class="skeleton-table-cell" />
          </div>
        </div>
      </div>
    </template>

    <!-- Custom skeleton content -->
    <template v-else>
      <slot>
        <!-- Default skeleton -->
        <div class="skeleton-default">
          <div 
            v-for="i in rows" 
            :key="i" 
            class="skeleton-line"
            :style="{ width: getLineWidth(i) }"
          />
        </div>
      </slot>
    </template>
  </div>
</template>

<script setup lang="ts">
interface Props {
  type?: 'card' | 'list' | 'table' | 'form' | 'dashboard' | 'custom'
  rows?: number
  columns?: number
  animate?: boolean
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  type: 'custom',
  rows: 3,
  columns: 4,
  animate: true,
  loading: true
})

// Generate random line widths for more natural look
const getLineWidth = (index: number): string => {
  const widths = ['100%', '90%', '85%', '95%', '75%', '100%', '80%']
  return widths[index % widths.length]
}
</script>

<style scoped>
.skeleton-loader {
  width: 100%;
}

/* Base skeleton styles */
.skeleton-element {
  background: linear-gradient(
    90deg,
    var(--el-fill-color-lighter) 25%,
    var(--el-fill-color-light) 50%,
    var(--el-fill-color-lighter) 75%
  );
  background-size: 200% 100%;
  border-radius: 4px;
}

.animate .skeleton-element,
.animate .skeleton-card .skeleton-image,
.animate .skeleton-title,
.animate .skeleton-text,
.animate .skeleton-avatar,
.animate .skeleton-input,
.animate .skeleton-line,
.animate .skeleton-table-cell,
.animate .skeleton-stat-icon,
.animate .skeleton-stat-number,
.animate .skeleton-stat-label,
.animate .skeleton-chart-content {
  animation: skeleton-loading 1.5s infinite;
}

@keyframes skeleton-loading {
  0% {
    background-position: -200% 0;
  }
  100% {
    background-position: 200% 0;
  }
}

/* Card skeleton */
.skeleton-card {
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 8px;
  overflow: hidden;
}

.skeleton-image {
  width: 100%;
  height: 200px;
  background: linear-gradient(
    90deg,
    var(--el-fill-color-lighter) 25%,
    var(--el-fill-color-light) 50%,
    var(--el-fill-color-lighter) 75%
  );
  background-size: 200% 100%;
}

.skeleton-content {
  padding: 16px;
}

.skeleton-title {
  height: 20px;
  margin-bottom: 12px;
  background: linear-gradient(
    90deg,
    var(--el-fill-color-lighter) 25%,
    var(--el-fill-color-light) 50%,
    var(--el-fill-color-lighter) 75%
  );
  background-size: 200% 100%;
  border-radius: 4px;
}

.skeleton-text {
  height: 16px;
  margin-bottom: 8px;
  background: linear-gradient(
    90deg,
    var(--el-fill-color-lighter) 25%,
    var(--el-fill-color-light) 50%,
    var(--el-fill-color-lighter) 75%
  );
  background-size: 200% 100%;
  border-radius: 4px;
}

.skeleton-text.short {
  width: 60%;
}

/* List skeleton */
.skeleton-list-item {
  display: flex;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid var(--el-border-color-lighter);
}

.skeleton-list-item:last-child {
  border-bottom: none;
}

.skeleton-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  margin-right: 12px;
  background: linear-gradient(
    90deg,
    var(--el-fill-color-lighter) 25%,
    var(--el-fill-color-light) 50%,
    var(--el-fill-color-lighter) 75%
  );
  background-size: 200% 100%;
  flex-shrink: 0;
}

.skeleton-list-item .skeleton-content {
  flex: 1;
  padding: 0;
}

/* Table skeleton */
.skeleton-table {
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 8px;
  overflow: hidden;
}

.skeleton-table-header {
  display: flex;
  background: var(--el-bg-color-page);
  border-bottom: 1px solid var(--el-border-color-lighter);
}

.skeleton-table-row {
  display: flex;
  border-bottom: 1px solid var(--el-border-color-lighter);
}

.skeleton-table-row:last-child {
  border-bottom: none;
}

.skeleton-table-cell {
  flex: 1;
  height: 40px;
  margin: 8px;
  background: linear-gradient(
    90deg,
    var(--el-fill-color-lighter) 25%,
    var(--el-fill-color-light) 50%,
    var(--el-fill-color-lighter) 75%
  );
  background-size: 200% 100%;
  border-radius: 4px;
}

/* Form skeleton */
.skeleton-form-item {
  margin-bottom: 24px;
}

.skeleton-label {
  width: 100px;
  height: 16px;
  margin-bottom: 8px;
  background: linear-gradient(
    90deg,
    var(--el-fill-color-lighter) 25%,
    var(--el-fill-color-light) 50%,
    var(--el-fill-color-lighter) 75%
  );
  background-size: 200% 100%;
  border-radius: 4px;
}

.skeleton-input {
  width: 100%;
  height: 40px;
  background: linear-gradient(
    90deg,
    var(--el-fill-color-lighter) 25%,
    var(--el-fill-color-light) 50%,
    var(--el-fill-color-lighter) 75%
  );
  background-size: 200% 100%;
  border-radius: 4px;
}

/* Dashboard skeleton */
.skeleton-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.skeleton-stat-card {
  display: flex;
  align-items: center;
  padding: 20px;
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 8px;
}

.skeleton-stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 8px;
  margin-right: 16px;
  background: linear-gradient(
    90deg,
    var(--el-fill-color-lighter) 25%,
    var(--el-fill-color-light) 50%,
    var(--el-fill-color-lighter) 75%
  );
  background-size: 200% 100%;
}

.skeleton-stat-content {
  flex: 1;
}

.skeleton-stat-number {
  width: 80px;
  height: 24px;
  margin-bottom: 8px;
  background: linear-gradient(
    90deg,
    var(--el-fill-color-lighter) 25%,
    var(--el-fill-color-light) 50%,
    var(--el-fill-color-lighter) 75%
  );
  background-size: 200% 100%;
  border-radius: 4px;
}

.skeleton-stat-label {
  width: 120px;
  height: 16px;
  background: linear-gradient(
    90deg,
    var(--el-fill-color-lighter) 25%,
    var(--el-fill-color-light) 50%,
    var(--el-fill-color-lighter) 75%
  );
  background-size: 200% 100%;
  border-radius: 4px;
}

.skeleton-chart {
  margin-bottom: 24px;
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 8px;
  padding: 20px;
}

.skeleton-chart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.skeleton-chart-title {
  width: 200px;
  height: 20px;
  background: linear-gradient(
    90deg,
    var(--el-fill-color-lighter) 25%,
    var(--el-fill-color-light) 50%,
    var(--el-fill-color-lighter) 75%
  );
  background-size: 200% 100%;
  border-radius: 4px;
}

.skeleton-chart-actions {
  width: 100px;
  height: 32px;
  background: linear-gradient(
    90deg,
    var(--el-fill-color-lighter) 25%,
    var(--el-fill-color-light) 50%,
    var(--el-fill-color-lighter) 75%
  );
  background-size: 200% 100%;
  border-radius: 4px;
}

.skeleton-chart-content {
  width: 100%;
  height: 300px;
  background: linear-gradient(
    90deg,
    var(--el-fill-color-lighter) 25%,
    var(--el-fill-color-light) 50%,
    var(--el-fill-color-lighter) 75%
  );
  background-size: 200% 100%;
  border-radius: 4px;
}

/* Default skeleton */
.skeleton-line {
  height: 16px;
  margin-bottom: 12px;
  background: linear-gradient(
    90deg,
    var(--el-fill-color-lighter) 25%,
    var(--el-fill-color-light) 50%,
    var(--el-fill-color-lighter) 75%
  );
  background-size: 200% 100%;
  border-radius: 4px;
}

.skeleton-line:last-child {
  margin-bottom: 0;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .skeleton-stats {
    grid-template-columns: 1fr;
  }
  
  .skeleton-chart-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  
  .skeleton-chart-content {
    height: 200px;
  }
  
  .skeleton-table-cell {
    margin: 4px;
    height: 32px;
  }
}
</style>