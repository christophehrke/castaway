<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../lib/api'
import type { Workflow } from '../types'
import StatusBadge from '../components/StatusBadge.vue'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import JsonViewer from '../components/JsonViewer.vue'
import { ArrowLeft, Download, Copy } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const workflow = ref<Workflow | null>(null)
const copied = ref(false)

onMounted(async () => {
  try {
    workflow.value = await api.workflows.show(route.params.id as string)
  } catch {
    // handle error
  } finally {
    loading.value = false
  }
})

function copyJson() {
  if (!workflow.value) return
  navigator.clipboard.writeText(JSON.stringify(workflow.value.workflow_json, null, 2))
  copied.value = true
  setTimeout(() => (copied.value = false), 2000)
}

function downloadJson() {
  if (!workflow.value) return
  const blob = new Blob([JSON.stringify(workflow.value.workflow_json, null, 2)], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `workflow-${workflow.value.engine}-${workflow.value.variant}.json`
  a.click()
  URL.revokeObjectURL(url)
}
</script>

<template>
  <div>
    <button
      @click="router.push('/app/workflows')"
      class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 mb-4"
    >
      <ArrowLeft class="w-4 h-4" />
      Back to Workflows
    </button>

    <LoadingSpinner v-if="loading" size="lg" />

    <template v-else-if="workflow">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 capitalize">{{ workflow.engine }} — {{ workflow.variant }}</h1>
          <div class="flex items-center gap-3 mt-2">
            <StatusBadge :status="workflow.status" />
            <span class="text-sm text-gray-500">Version {{ workflow.version }}</span>
            <span class="text-sm text-gray-500">{{ workflow.node_count ?? 0 }} nodes</span>
          </div>
        </div>
        <div class="flex gap-2">
          <button
            @click="copyJson"
            class="inline-flex items-center gap-2 px-3 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50"
          >
            <Copy class="w-4 h-4" />
            {{ copied ? 'Copied!' : 'Copy to Clipboard' }}
          </button>
          <button
            @click="downloadJson"
            class="inline-flex items-center gap-2 px-3 py-2 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
          >
            <Download class="w-4 h-4" />
            Download JSON
          </button>
        </div>
      </div>

      <!-- Metadata -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
          <p class="text-xs text-gray-500">Engine</p>
          <p class="font-medium capitalize">{{ workflow.engine }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
          <p class="text-xs text-gray-500">Variant</p>
          <p class="font-medium capitalize">{{ workflow.variant }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
          <p class="text-xs text-gray-500">Version</p>
          <p class="font-medium">{{ workflow.version }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
          <p class="text-xs text-gray-500">Created</p>
          <p class="font-medium">{{ new Date(workflow.created_at).toLocaleDateString() }}</p>
        </div>
      </div>

      <!-- JSON Viewer -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-sm font-medium text-gray-700 mb-3">Workflow JSON</h3>
        <JsonViewer :data="workflow.workflow_json" />
      </div>
    </template>
  </div>
</template>
