<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AuthLayout from './layouts/AuthLayout.vue'
import AppLayout from './layouts/AppLayout.vue'
import AdminLayout from './layouts/AdminLayout.vue'

const route = useRoute()

const layout = computed(() => {
  const meta = route.meta?.layout as string
  if (meta === 'auth') return AuthLayout
  if (meta === 'admin') return AdminLayout
  return AppLayout
})
</script>

<template>
  <component :is="layout">
    <RouterView />
  </component>
</template>
