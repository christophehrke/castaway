<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../lib/api'
import type { Recording } from '../types'
import StatusBadge from '../components/StatusBadge.vue'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import EmptyState from '../components/EmptyState.vue'
import UploadDialog from '../components/UploadDialog.vue'
import { Plus } from 'lucide-vue-next'

const router = useRouter()

const loading = ref(true)
const recordings = ref<Recording[]>([])
const meta = ref<any>(null)
const currentPage = ref(1)
const showUpload = ref(false)
const uploading = ref(false)

async function loadRecordings() {
  loading.value = true
  try {
    const res = await api.recordings.list(currentPage.value)
    recordings.value = res.data
    meta.value = res.meta
  } catch {
    // handle error
  } finally {
    loading.value = false
  }
}

async function handleUpload(file: File, title: string) {
  uploading.value = true
  try {
    await api.recordings.create(file, title)
    showUpload.value = false
    await loadRecordings()
  } catch {
    // handle error
  } finally {
    uploading.value = false
  }
}

function goToPage(page: number) {
  currentPage.value = page
  loadRecordings()
}

function formatDuration(seconds: number | null): string {
  if (!seconds) return '—'
  const m = Math.floor(seconds / 60)
  const s = Math.floor(seconds % 60)
  return `${m}:${s.toString().padStart(2, '0')}`
}

onMounted(loadRecordings)
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Recordings</h1>
      <button
        @click="showUpload = true"
        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-medium text-sm"
      >
        <Plus class="w-4 h-4" />
        Upload Recording
      </button>
    </div>

    <LoadingSpinner v-if="loading" size="lg" />

    <template v-else>
      <EmptyState v-if="recordings.length === 0" message="No recordings yet" />

      <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr
              v-for="rec in recordings"
              :key="rec.id"
              @click="router.push(`/app/recordings/${rec.id}`)"
              class="hover:bg-gray-50 cursor-pointer"
            >
              <td class="px-6 py-4 text-sm font-medium text-gray-900">
                {{ rec.title || rec.original_filename }}
              </td>
              <td class="px-6 py-4">
                <StatusBadge :status="rec.status" />
              </td>
              <td class="px-6 py-4 text-sm text-gray-500">
                {{ formatDuration(rec.duration_seconds) }}
              </td>
              <td class="px-6 py-4 text-sm text-gray-500">
                {{ new Date(rec.created_at).toLocaleDateString() }}
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="meta?.last_page > 1" class="px-6 py-3 border-t border-gray-200 flex items-center justify-between">
          <p class="text-sm text-gray-500">
            Page {{ meta.current_page }} of {{ meta.last_page }}
          </p>
          <div class="flex gap-2">
            <button
              @click="goToPage(currentPage - 1)"
              :disabled="currentPage <= 1"
              class="px-3 py-1 text-sm border rounded-md disabled:opacity-50"
            >
              Previous
            </button>
            <button
              @click="goToPage(currentPage + 1)"
              :disabled="currentPage >= meta.last_page"
              class="px-3 py-1 text-sm border rounded-md disabled:opacity-50"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </template>

    <UploadDialog
      v-if="showUpload"
      @upload="handleUpload"
      @close="showUpload = false"
    />
  </div>
</template>
