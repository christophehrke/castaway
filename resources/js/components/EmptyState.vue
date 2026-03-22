<script setup lang="ts">
import { Inbox } from 'lucide-vue-next'

defineProps<{
  message?: string
}>()
</script>

<template>
  <div class="flex flex-col items-center justify-center py-12 text-gray-400">
    <Inbox class="w-12 h-12 mb-3" />
    <p class="text-sm">{{ message || 'No items found' }}</p>
    <slot />
  </div>
</template>
