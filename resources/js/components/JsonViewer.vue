<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  data: any
}>()

const formatted = computed(() => {
  try {
    return JSON.stringify(props.data, null, 2)
  } catch {
    return String(props.data)
  }
})
</script>

<template>
  <pre class="bg-gray-900 text-green-400 p-4 rounded-lg overflow-x-auto text-sm font-mono max-h-96 overflow-y-auto"><code>{{ formatted }}</code></pre>
</template>
