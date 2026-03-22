<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  status: string
}>()

const colorClass = computed(() => {
  const map: Record<string, string> = {
    uploaded: 'bg-gray-100 text-gray-700',
    processing: 'bg-blue-100 text-blue-700',
    media_ready: 'bg-blue-100 text-blue-700',
    intent_ready: 'bg-blue-100 text-blue-700',
    ready: 'bg-green-100 text-green-700',
    workflows_ready: 'bg-green-100 text-green-700',
    completed: 'bg-green-100 text-green-700',
    active: 'bg-green-100 text-green-700',
    failed: 'bg-red-100 text-red-700',
    revoked: 'bg-red-100 text-red-700',
    draft: 'bg-yellow-100 text-yellow-700',
  }
  return map[props.status] || 'bg-gray-100 text-gray-700'
})
</script>

<template>
  <span :class="colorClass" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize">
    {{ status.replace(/_/g, ' ') }}
  </span>
</template>
