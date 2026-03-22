<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../lib/api'
import type { Recording } from '../types'
import StatusBadge from '../components/StatusBadge.vue'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import PipelineTimeline from '../components/PipelineTimeline.vue'
import JsonViewer from '../components/JsonViewer.vue'
import { ArrowLeft, RefreshCw, Play } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const recording = ref<Recording | null>(null)
const activeTab = ref<'transcript' | 'intent' | 'workflows'>('transcript')
const error = ref('')

const videoAsset = computed(() => {
  return recording.value?.assets?.find(a => a.type === 'normalized_video' || a.type === 'original_upload')
})

onMounted(async () => {
  try {
    const id = route.params.id as string
    recording.value = await api.recordings.show(id)

    // Try to load intent
    if (recording.value.status !== 'uploaded') {
      try {
        const intent = await api.recordings.getIntent(id)
        if (recording.value) {
          recording.value.intent = intent
        }
      } catch {
        // no intent yet
      }
    }
  } catch (err: any) {
    error.value = err.message || 'Failed to load recording'
  } finally {
    loading.value = false
  }
})

async function regenerateIntent() {
  if (!recording.value) return
  try {
    await api.recordings.regenerateIntent(recording.value.id)
    // Reload
    recording.value = await api.recordings.show(recording.value.id)
  } catch {
    // handle error
  }
}

async function generateWorkflows() {
  if (!recording.value) return
  try {
    await api.recordings.generateWorkflows(recording.value.id)
    recording.value = await api.recordings.show(recording.value.id)
  } catch {
    // handle error
  }
}
</script>

<template>
  <div>
    <button
      @click="router.push('/app/recordings')"
      class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 mb-4"
    >
      <ArrowLeft class="w-4 h-4" />
      Back to Recordings
    </button>

    <LoadingSpinner v-if="loading" size="lg" />

    <div v-else-if="error" class="bg-red-50 text-red-600 p-4 rounded-lg">
      {{ error }}
    </div>

    <template v-else-if="recording">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ recording.title || recording.original_filename }}</h1>
          <div class="flex items-center gap-3 mt-2">
            <StatusBadge :status="recording.status" />
            <span class="text-sm text-gray-500">{{ new Date(recording.created_at).toLocaleString() }}</span>
          </div>
        </div>
        <div class="flex gap-2">
          <button
            @click="regenerateIntent"
            class="inline-flex items-center gap-2 px-3 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50"
          >
            <RefreshCw class="w-4 h-4" />
            Regenerate Intent
          </button>
          <button
            @click="generateWorkflows"
            class="inline-flex items-center gap-2 px-3 py-2 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
          >
            <Play class="w-4 h-4" />
            Generate Workflows
          </button>
        </div>
      </div>

      <!-- Pipeline Timeline -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <h3 class="text-sm font-medium text-gray-700 mb-3">Pipeline Progress</h3>
        <PipelineTimeline :status="recording.status" />
      </div>

      <!-- Video Player -->
      <div v-if="videoAsset" class="bg-black rounded-lg mb-6 overflow-hidden">
        <video controls class="w-full max-h-96">
          <source :src="`/storage/${videoAsset.storage_path}`" :type="videoAsset.mime_type || 'video/mp4'" />
          Your browser does not support the video tag.
        </video>
      </div>

      <!-- Tabs -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="border-b border-gray-200">
          <nav class="flex">
            <button
              v-for="tab in ['transcript', 'intent', 'workflows'] as const"
              :key="tab"
              @click="activeTab = tab"
              class="px-6 py-3 text-sm font-medium border-b-2 capitalize"
              :class="activeTab === tab
                ? 'border-indigo-600 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700'"
            >
              {{ tab }}
            </button>
          </nav>
        </div>

        <div class="p-6">
          <!-- Transcript Tab -->
          <div v-if="activeTab === 'transcript'">
            <div v-if="recording.intent?.description" class="prose max-w-none">
              <p class="text-gray-700 whitespace-pre-wrap">{{ recording.intent.description }}</p>
            </div>
            <p v-else class="text-gray-400 text-sm">No transcript available yet.</p>
          </div>

          <!-- Intent Tab -->
          <div v-if="activeTab === 'intent'">
            <div v-if="recording.intent?.steps?.length" class="space-y-4">
              <div
                v-for="step in recording.intent.steps"
                :key="step.order"
                class="border border-gray-200 rounded-lg p-4"
              >
                <div class="flex items-center gap-3 mb-2">
                  <span class="inline-flex items-center justify-center w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full text-xs font-medium">
                    {{ step.order }}
                  </span>
                  <span class="font-medium text-gray-900">{{ step.action }}</span>
                  <span class="text-sm text-gray-500 bg-gray-100 px-2 py-0.5 rounded">{{ step.app }}</span>
                </div>
                <p class="text-sm text-gray-600 ml-9">{{ step.description }}</p>
                <div v-if="step.evidence" class="ml-9 mt-2 text-xs text-gray-400">
                  Evidence: {{ step.evidence.transcript_start }} — {{ step.evidence.transcript_end }}
                </div>
              </div>
            </div>
            <p v-else class="text-gray-400 text-sm">No intent data available yet.</p>
          </div>

          <!-- Workflows Tab -->
          <div v-if="activeTab === 'workflows'">
            <div v-if="recording.workflows?.length" class="space-y-4">
              <div
                v-for="wf in recording.workflows"
                :key="wf.id"
                class="border border-gray-200 rounded-lg p-4"
              >
                <div class="flex items-center justify-between mb-3">
                  <div>
                    <span class="font-medium text-gray-900">{{ wf.engine }}</span>
                    <span class="text-gray-400 mx-2">·</span>
                    <span class="text-sm text-gray-500">{{ wf.variant }}</span>
                    <span class="text-gray-400 mx-2">·</span>
                    <span class="text-sm text-gray-500">{{ wf.node_count }} nodes</span>
                  </div>
                  <div class="flex gap-2">
                    <button
                      @click="navigator.clipboard.writeText(JSON.stringify(wf.workflow_json, null, 2))"
                      class="px-3 py-1 text-xs border border-gray-300 rounded hover:bg-gray-50"
                    >
                      Copy JSON
                    </button>
                    <a
                      :href="`data:application/json;charset=utf-8,${encodeURIComponent(JSON.stringify(wf.workflow_json, null, 2))}`"
                      :download="`workflow-${wf.engine}-${wf.variant}.json`"
                      class="px-3 py-1 text-xs border border-gray-300 rounded hover:bg-gray-50"
                    >
                      Download
                    </a>
                  </div>
                </div>
                <JsonViewer :data="wf.workflow_json" />
              </div>
            </div>
            <p v-else class="text-gray-400 text-sm">No workflows generated yet.</p>
          </div>
        </div>
      </div>

      <!-- Error display -->
      <div v-if="recording.status === 'failed'" class="mt-6 bg-red-50 border border-red-200 rounded-lg p-4">
        <h3 class="text-sm font-medium text-red-800">Pipeline Failed</h3>
        <p class="text-sm text-red-600 mt-1">The processing pipeline encountered an error. Try regenerating the intent or contact support.</p>
      </div>
    </template>
  </div>
</template>
