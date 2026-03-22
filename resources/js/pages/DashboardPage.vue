<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { api } from '../lib/api'
import type { Recording } from '../types'
import StatusBadge from '../components/StatusBadge.vue'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import { Mic, GitBranch, BarChart3, Upload, List } from 'lucide-vue-next'

const auth = useAuthStore()
const router = useRouter()

const loading = ref(true)
const recordingsCount = ref(0)
const workflowsCount = ref(0)
const recentRecordings = ref<Recording[]>([])
const usage = ref<any>(null)

onMounted(async () => {
  try {
    const [recRes, wfRes] = await Promise.all([
      api.recordings.list(1),
      api.workflows.list({ page: 1 }),
    ])
    recordingsCount.value = recRes.meta?.total ?? recRes.data.length
    workflowsCount.value = wfRes.meta?.total ?? wfRes.data.length
    recentRecordings.value = recRes.data.slice(0, 5)

    try {
      usage.value = await api.billing.usage()
    } catch {
      // billing may not be set up
    }
  } catch {
    // handle gracefully
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Welcome back, {{ auth.user?.name }}!</h1>

    <LoadingSpinner v-if="loading" size="lg" />

    <template v-else>
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-indigo-100 rounded-lg">
              <Mic class="w-5 h-5 text-indigo-600" />
            </div>
            <div>
              <p class="text-sm text-gray-500">Recordings</p>
              <p class="text-2xl font-bold text-gray-900">{{ recordingsCount }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-green-100 rounded-lg">
              <GitBranch class="w-5 h-5 text-green-600" />
            </div>
            <div>
              <p class="text-sm text-gray-500">Workflows</p>
              <p class="text-2xl font-bold text-gray-900">{{ workflowsCount }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-purple-100 rounded-lg">
              <BarChart3 class="w-5 h-5 text-purple-600" />
            </div>
            <div>
              <p class="text-sm text-gray-500">Usage Remaining</p>
              <p class="text-2xl font-bold text-gray-900">{{ usage?.recordings_count ?? '—' }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="flex gap-3 mb-8">
        <button
          @click="router.push('/app/recordings')"
          class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-medium text-sm"
        >
          <Upload class="w-4 h-4" />
          Upload Recording
        </button>
        <button
          @click="router.push('/app/recordings')"
          class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50 font-medium text-sm"
        >
          <List class="w-4 h-4" />
          View All Recordings
        </button>
      </div>

      <!-- Recent Recordings -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900">Recent Recordings</h2>
        </div>
        <div v-if="recentRecordings.length === 0" class="p-6 text-center text-gray-400 text-sm">
          No recordings yet. Upload your first recording to get started.
        </div>
        <div v-else class="divide-y divide-gray-200">
          <div
            v-for="rec in recentRecordings"
            :key="rec.id"
            @click="router.push(`/app/recordings/${rec.id}`)"
            class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 cursor-pointer"
          >
            <div>
              <p class="font-medium text-gray-900">{{ rec.title || rec.original_filename }}</p>
              <p class="text-sm text-gray-500">{{ new Date(rec.created_at).toLocaleDateString() }}</p>
            </div>
            <StatusBadge :status="rec.status" />
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
