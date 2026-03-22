<script setup lang="ts">
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex flex-col items-center justify-center px-4">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-indigo-600">FlowCast</h1>
        <p class="text-gray-500 mt-1">Screen recording to automation workflow</p>
      </div>

      <div class="bg-white rounded-lg shadow-md p-8">
        <slot />
      </div>

      <p class="text-center text-gray-400 text-sm mt-8">&copy; {{ new Date().getFullYear() }} FlowCast. All rights reserved.</p>
    </div>
  </div>
</template>
