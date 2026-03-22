<script setup lang="ts">
defineProps<{
  size?: 'sm' | 'md' | 'lg'
}>()
</script>

<template>
  <div class="flex items-center justify-center">
    <div
      class="animate-spin rounded-full border-2 border-gray-300 border-t-indigo-600"
      :class="{
        'w-4 h-4': size === 'sm',
        'w-8 h-8': !size || size === 'md',
        'w-12 h-12': size === 'lg',
      }"
    />
  </div>
</template>
