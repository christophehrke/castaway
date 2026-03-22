<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { api } from '../lib/api'
import { Users, Building2, Mic, AlertTriangle, Workflow, CreditCard } from 'lucide-vue-next'
import LoadingSpinner from '../components/LoadingSpinner.vue'

const loading = ref(true)
const metrics = ref<any>(null)
const failedRecordings = ref<any[]>([])
const reprocessing = ref<Set<string>>(new Set())

async function loadData() {
  loading.value = true
  try {
    const res = await api.admin.metrics()
    metrics.value = res.data
    const failed = await api.admin.failedRecordings()
    failedRecordings.value = failed.data || []
  } catch (e) {
    console.error('Failed to load admin data', e)
  } finally {
    loading.value = false
  }
}

async function reprocess(id: string) {
  reprocessing.value.add(id)
  try {
    await api.admin.reprocess(id)
    failedRecordings.value = failedRecordings.value.filter(r => r.id !== id)
  } catch (e) {
    console.error('Reprocess failed', e)
  } finally {
    reprocessing.value.delete(id)
  }
}

onMounted(loadData)
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Admin Dashboard</h1>

    <LoadingSpinner v-if="loading" />

    <template v-else-if="metrics">
      <!-- Metric Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-blue-100 rounded-lg">
              <Users class="w-5 h-5 text-blue-600" />
            </div>
            <div>
              <p class="text-xs text-gray-500">Users</p>
              <p class="text-xl font-bold text-gray-900">{{ metrics.users_count }}</p>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-green-100 rounded-lg">
              <Building2 class="w-5 h-5 text-green-600" />
            </div>
            <div>
              <p class="text-xs text-gray-500">Organizations</p>
              <p class="text-xl font-bold text-gray-900">{{ metrics.organizations_count }}</p>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-indigo-100 rounded-lg">
              <Mic class="w-5 h-5 text-indigo-600" />
            </div>
            <div>
              <p class="text-xs text-gray-500">Recordings</p>
              <p class="text-xl font-bold text-gray-900">{{ metrics.recordings_count }}</p>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-purple-100 rounded-lg">
              <Workflow class="w-5 h-5 text-purple-600" />
            </div>
            <div>
              <p class="text-xs text-gray-500">Workflows</p>
              <p class="text-xl font-bold text-gray-900">{{ metrics.workflows_count }}</p>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-red-100 rounded-lg">
              <AlertTriangle class="w-5 h-5 text-red-600" />
            </div>
            <div>
              <p class="text-xs text-gray-500">Failed</p>
              <p class="text-xl font-bold text-gray-900">{{ metrics.failed_recordings_count }}</p>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-emerald-100 rounded-lg">
              <CreditCard class="w-5 h-5 text-emerald-600" />
            </div>
            <div>
              <p class="text-xs text-gray-500">Subscriptions</p>
              <p class="text-xl font-bold text-gray-900">{{ metrics.active_subscriptions_count }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Recordings by Status -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Recordings by Status</h2>
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b">
                <th class="text-left py-2 text-gray-500">Status</th>
                <th class="text-right py-2 text-gray-500">Count</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(count, status) in metrics.recordings_by_status" :key="status" class="border-b last:border-0">
                <td class="py-2">{{ status }}</td>
                <td class="text-right py-2 font-mono">{{ count }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pipeline Errors by Stage -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Pipeline Errors by Stage</h2>
          <table class="w-full text-sm" v-if="Object.keys(metrics.pipeline_errors_by_stage).length">
            <thead>
              <tr class="border-b">
                <th class="text-left py-2 text-gray-500">Stage</th>
                <th class="text-right py-2 text-gray-500">Count</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(count, stage) in metrics.pipeline_errors_by_stage" :key="stage" class="border-b last:border-0">
                <td class="py-2">{{ stage }}</td>
                <td class="text-right py-2 font-mono">{{ count }}</td>
              </tr>
            </tbody>
          </table>
          <p v-else class="text-gray-400 text-center py-4">No unresolved pipeline errors</p>
        </div>
      </div>

      <!-- Recent Command Runs -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Command Runs</h2>
        <div class="overflow-x-auto">
          <table class="w-full text-sm" v-if="metrics.recent_command_runs?.length">
            <thead>
              <tr class="border-b">
                <th class="text-left py-2 text-gray-500">Command</th>
                <th class="text-left py-2 text-gray-500">Status</th>
                <th class="text-right py-2 text-gray-500">Processed</th>
                <th class="text-right py-2 text-gray-500">Failed</th>
                <th class="text-left py-2 text-gray-500">Started</th>
                <th class="text-left py-2 text-gray-500">Completed</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="run in metrics.recent_command_runs" :key="run.id" class="border-b last:border-0">
                <td class="py-2 font-mono text-xs">{{ run.command }}</td>
                <td class="py-2">
                  <span
                    class="px-2 py-0.5 rounded-full text-xs font-medium"
                    :class="{
                      'bg-green-100 text-green-700': run.status === 'completed',
                      'bg-yellow-100 text-yellow-700': run.status === 'running',
                      'bg-red-100 text-red-700': run.status === 'failed',
                    }"
                  >{{ run.status }}</span>
                </td>
                <td class="text-right py-2 font-mono">{{ run.records_processed ?? '—' }}</td>
                <td class="text-right py-2 font-mono">{{ run.records_failed ?? '—' }}</td>
                <td class="py-2 text-xs text-gray-500">{{ run.started_at }}</td>
                <td class="py-2 text-xs text-gray-500">{{ run.completed_at ?? '—' }}</td>
              </tr>
            </tbody>
          </table>
          <p v-else class="text-gray-400 text-center py-4">No command runs yet</p>
        </div>
      </div>

      <!-- Failed Recordings -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Failed Recordings</h2>
        <div class="overflow-x-auto">
          <table class="w-full text-sm" v-if="failedRecordings.length">
            <thead>
              <tr class="border-b">
                <th class="text-left py-2 text-gray-500">Title</th>
                <th class="text-left py-2 text-gray-500">Organization</th>
                <th class="text-left py-2 text-gray-500">Created</th>
                <th class="text-right py-2 text-gray-500">Errors</th>
                <th class="text-right py-2 text-gray-500">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="rec in failedRecordings" :key="rec.id" class="border-b last:border-0">
                <td class="py-2">{{ rec.title }}</td>
                <td class="py-2">{{ rec.organization?.name ?? '—' }}</td>
                <td class="py-2 text-xs text-gray-500">{{ rec.created_at }}</td>
                <td class="text-right py-2 font-mono">{{ rec.pipeline_errors?.length ?? 0 }}</td>
                <td class="text-right py-2">
                  <button
                    @click="reprocess(rec.id)"
                    :disabled="reprocessing.has(rec.id)"
                    class="px-3 py-1 bg-indigo-600 text-white rounded text-xs hover:bg-indigo-700 disabled:opacity-50"
                  >
                    {{ reprocessing.has(rec.id) ? 'Reprocessing…' : 'Reprocess' }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
          <p v-else class="text-gray-400 text-center py-4">No failed recordings</p>
        </div>
      </div>
    </template>
  </div>
</template>
