<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { api } from '../lib/api'
import type { ApiKey } from '../types'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import EmptyState from '../components/EmptyState.vue'
import { Plus, X, Copy, AlertTriangle } from 'lucide-vue-next'

const loading = ref(true)
const apiKeys = ref<ApiKey[]>([])
const showCreate = ref(false)
const newLabel = ref('')
const createdKey = ref('')
const creating = ref(false)
const copied = ref(false)

async function loadKeys() {
  loading.value = true
  try {
    apiKeys.value = await api.apiKeys.list()
  } catch {
    // handle error
  } finally {
    loading.value = false
  }
}

async function createKey() {
  if (!newLabel.value.trim()) return
  creating.value = true
  try {
    const result = await api.apiKeys.create({ label: newLabel.value })
    createdKey.value = result.plain_text_key
    newLabel.value = ''
    await loadKeys()
  } catch {
    // handle error
  } finally {
    creating.value = false
  }
}

async function revokeKey(id: string) {
  if (!confirm('Are you sure you want to revoke this API key?')) return
  try {
    await api.apiKeys.delete(id)
    await loadKeys()
  } catch {
    // handle error
  }
}

function copyKey() {
  navigator.clipboard.writeText(createdKey.value)
  copied.value = true
  setTimeout(() => (copied.value = false), 2000)
}

onMounted(loadKeys)
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900">API Keys</h1>
      <button
        @click="showCreate = true; createdKey = ''"
        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-medium text-sm"
      >
        <Plus class="w-4 h-4" />
        Create API Key
      </button>
    </div>

    <!-- Create Key Dialog -->
    <div v-if="showCreate" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showCreate = false">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold">Create API Key</h3>
          <button @click="showCreate = false" class="text-gray-400 hover:text-gray-600">
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Show created key -->
        <div v-if="createdKey" class="space-y-4">
          <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex items-start gap-3">
              <AlertTriangle class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" />
              <div>
                <p class="text-sm font-medium text-yellow-800">Save this key now!</p>
                <p class="text-xs text-yellow-600 mt-1">This is the only time you'll see this key. Store it securely.</p>
              </div>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <code class="flex-1 bg-gray-100 px-3 py-2 rounded text-sm font-mono break-all">{{ createdKey }}</code>
            <button @click="copyKey" class="px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
              <Copy class="w-4 h-4" />
            </button>
          </div>
          <p v-if="copied" class="text-green-600 text-xs">Copied to clipboard!</p>
          <button @click="showCreate = false" class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 font-medium text-sm">
            Done
          </button>
        </div>

        <!-- Create form -->
        <div v-else class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Label</label>
            <input
              v-model="newLabel"
              type="text"
              placeholder="e.g., Chrome Extension"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
            />
          </div>
          <button
            @click="createKey"
            :disabled="creating || !newLabel.trim()"
            class="w-full px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50 font-medium"
          >
            {{ creating ? 'Creating...' : 'Create Key' }}
          </button>
        </div>
      </div>
    </div>

    <LoadingSpinner v-if="loading" size="lg" />

    <template v-else>
      <EmptyState v-if="apiKeys.length === 0" message="No API keys yet" />

      <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Label</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prefix</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Used</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="key in apiKeys" :key="key.id">
              <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ key.label }}</td>
              <td class="px-6 py-4 text-sm text-gray-500 font-mono">{{ key.key_prefix }}...</td>
              <td class="px-6 py-4 text-sm text-gray-500">
                {{ key.last_used_at ? new Date(key.last_used_at).toLocaleDateString() : 'Never' }}
              </td>
              <td class="px-6 py-4">
                <span
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :class="key.revoked_at ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'"
                >
                  {{ key.revoked_at ? 'Revoked' : 'Active' }}
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <button
                  v-if="!key.revoked_at"
                  @click="revokeKey(key.id)"
                  class="text-red-600 hover:text-red-800 text-sm font-medium"
                >
                  Revoke
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </div>
</template>
