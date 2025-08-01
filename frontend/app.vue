<template>
  <div id="app">
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
  </div>
</template>

<script setup>
// 應用程式根組件
useHead({
  title: '私人遊戲伺服器推廣平台',
  meta: [
    { charset: 'utf-8' },
    { name: 'viewport', content: 'width=device-width, initial-scale=1' },
    { name: 'description', content: '多服主共用的遊戲伺服器推廣平台' }
  ]
})
</script>