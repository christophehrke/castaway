<script setup lang="ts">
import { ref } from 'vue'
import { Upload, X } from 'lucide-vue-next'

const emit = defineEmits<{
  (e: 'upload', file: File, title: string): void
  (e: 'close'): void
}>()

const title = ref('')
const selectedFile = ref<File | null>(null)
const isDragging = ref(false)

function handleDrop(event: DragEvent) {
  isDragging.value = false
  const files = event.dataTransfer?.files
  if (files?.length) {
    selectedFile.value = files[0]
  }
}

function handleFileSelect(event: Event) {
  const input = event.target as HTMLInputElement
  if (input.files?.length) {
    selectedFile.value = input.files[0]
  }
}

function submit() {
  if (selectedFile.value) {
    emit('upload', selectedFile.value, title.value)
  }
}
</script>

<template>
  <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="emit('close')">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold">Upload Recording</h3>
        <button @click="emit('close')" class="text-gray-400 hover:text-gray-600">
          <X class="w-5 h-5" />
        </button>
      </div>

      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Title (optional)</label>
          <input
            v-model="title"
            type="text"
            placeholder="Recording title"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
          />
        </div>

        <div
          @dragover.prevent="isDragging = true"
          @dragleave="isDragging = false"
          @drop.prevent="handleDrop"
          :class="isDragging ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300'"
          class="border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition-colors"
          @click="($refs.fileInput as HTMLInputElement).click()"
        >
          <Upload class="w-8 h-8 mx-auto text-gray-400 mb-2" />
          <p class="text-sm text-gray-600" v-if="!selectedFile">
            Drop your file here or <span class="text-indigo-600">browse</span>
          </p>
          <p class="text-sm text-gray-900 font-medium" v-else>
            {{ selectedFile.name }}
          </p>
          <p class="text-xs text-gray-400 mt-1">Video or screen recording files</p>
          <input
            ref="fileInput"
            type="file"
            accept="video/*,audio/*"
            class="hidden"
            @change="handleFileSelect"
          />
        </div>

        <button
          @click="submit"
          :disabled="!selectedFile"
          class="w-full px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed font-medium"
        >
          Upload
        </button>
      </div>
    </div>
  </div>
</template>
