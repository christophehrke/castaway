<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../lib/api'
import type { Workflow } from '../types'
import StatusBadge from '../components/StatusBadge.vue'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import EmptyState from '../components/EmptyState.vue'

const router = useRouter()

const loading = ref(true)
const workflows = ref<Workflow[]>([])
const meta = ref<any>(null)
const currentPage = ref(1)
const engineFilter = ref('')
const variantFilter = ref('')

async function loadWorkflows() {
  loading.value = true
  try {
    const res = await api.workflows.list({
      engine: engineFilter.value || undefined,
      variant: variantFilter.value || undefined,
      page: currentPage.value,
    })
    workflows.value = res.data
    meta.value = res.meta
  } catch {
    // handle error
  } finally {
    loading.value = false
  }
}

function applyFilters() {
  currentPage.value = 1
  loadWorkflows()
}

function goToPage(page: number) {
  currentPage.value = page
  loadWorkflows()
}

onMounted(loadWorkflows)
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Workflows</h1>

    <!-- Filters -->
    <div class="flex gap-4 mb-6">
      <select
        v-model="engineFilter"
        @change="applyFilters"
        class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
      >
        <option value="">All Engines</option>
        <option value="n8n">n8n</option>
        <option value="make">Make</option>
        <option value="zapier">Zapier</option>
      </select>

      <select
        v-model="variantFilter"
        @change="applyFilters"
        class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
      >
        <option value="">All Variants</option>
        <option value="minimal">Minimal</option>
        <option value="robust">Robust</option>
        <option value="full">Full</option>
      </select>
    </div>

    <LoadingSpinner v-if="loading" size="lg" />

    <template v-else>
      <EmptyState v-if="workflows.length === 0" message="No workflows found" />

      <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Engine</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Variant</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nodes</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr
              v-for="wf in workflows"
              :key="wf.id"
              @click="router.push(`/app/workflows/${wf.id}`)"
              class="hover:bg-gray-50 cursor-pointer"
            >
              <td class="px-6 py-4 text-sm font-medium text-gray-900 capitalize">{{ wf.engine }}</td>
              <td class="px-6 py-4 text-sm text-gray-500 capitalize">{{ wf.variant }}</td>
              <td class="px-6 py-4 text-sm text-gray-500">{{ wf.node_count ?? '—' }}</td>
              <td class="px-6 py-4">
                <StatusBadge :status="wf.status" />
              </td>
              <td class="px-6 py-4 text-sm text-gray-500">
                {{ new Date(wf.created_at).toLocaleDateString() }}
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="meta?.last_page > 1" class="px-6 py-3 border-t border-gray-200 flex items-center justify-between">
          <p class="text-sm text-gray-500">Page {{ meta.current_page }} of {{ meta.last_page }}</p>
          <div class="flex gap-2">
            <button @click="goToPage(currentPage - 1)" :disabled="currentPage <= 1" class="px-3 py-1 text-sm border rounded-md disabled:opacity-50">Previous</button>
            <button @click="goToPage(currentPage + 1)" :disabled="currentPage >= meta.last_page" class="px-3 py-1 text-sm border rounded-md disabled:opacity-50">Next</button>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
