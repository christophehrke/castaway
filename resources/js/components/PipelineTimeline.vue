<script setup lang="ts">
import { computed } from 'vue'
import { Check, Clock, AlertCircle } from 'lucide-vue-next'

const props = defineProps<{
  status: string
}>()

const stages = ['uploaded', 'media_ready', 'intent_ready', 'workflows_ready']

const currentIndex = computed(() => {
  if (props.status === 'failed') return -1
  return stages.indexOf(props.status)
})
</script>

<template>
  <div class="flex items-center gap-2">
    <div
      v-for="(stage, i) in stages"
      :key="stage"
      class="flex items-center gap-2"
    >
      <div
        class="flex items-center justify-center w-8 h-8 rounded-full text-xs font-medium"
        :class="{
          'bg-green-100 text-green-600': i <= currentIndex,
          'bg-gray-100 text-gray-400': i > currentIndex && status !== 'failed',
          'bg-red-100 text-red-600': status === 'failed' && i === 0,
        }"
      >
        <Check v-if="i < currentIndex" class="w-4 h-4" />
        <Clock v-else-if="i === currentIndex && i >= 0" class="w-4 h-4" />
        <AlertCircle v-else-if="status === 'failed' && i === 0" class="w-4 h-4" />
        <span v-else>{{ i + 1 }}</span>
      </div>
      <span class="text-xs text-gray-500 capitalize hidden sm:inline">{{ stage.replace(/_/g, ' ') }}</span>
      <div v-if="i < stages.length - 1" class="w-8 h-0.5 bg-gray-200" :class="{ 'bg-green-400': i < currentIndex }" />
    </div>
  </div>
</template>
