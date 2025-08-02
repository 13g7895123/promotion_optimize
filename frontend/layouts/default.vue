<template>
  <div class="frontend-layout">
    <slot />
  </div>
</template>

<script setup lang="ts">
// 純粹的前台佈局 - 無後台管理邏輯
</script>

<style scoped>
.frontend-layout {
  min-height: 100vh;
  background: transparent;
}
</style>