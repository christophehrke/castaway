<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { api } from '../lib/api'
import type { Plan, UsageData } from '../types'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import { CreditCard, ExternalLink } from 'lucide-vue-next'

const loading = ref(true)
const plans = ref<Plan[]>([])
const usage = ref<UsageData | null>(null)

onMounted(async () => {
  try {
    const [plansData, usageData] = await Promise.all([
      api.billing.plans(),
      api.billing.usage().catch(() => null),
    ])
    plans.value = plansData
    usage.value = usageData
  } catch {
    // handle error
  } finally {
    loading.value = false
  }
})

async function openPortal() {
  try {
    const data = await api.billing.portal()
    window.open(data.portal_url, '_blank')
  } catch {
    // handle error
  }
}

function formatBytes(bytes: number): string {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Billing</h1>

    <LoadingSpinner v-if="loading" size="lg" />

    <template v-else>
      <!-- Current Plan -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-indigo-100 rounded-lg">
              <CreditCard class="w-5 h-5 text-indigo-600" />
            </div>
            <div>
              <h2 class="text-lg font-semibold text-gray-900">Current Plan</h2>
              <p class="text-sm text-gray-500">Manage your subscription and billing</p>
            </div>
          </div>
          <button
            @click="openPortal"
            class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 text-sm font-medium"
          >
            <ExternalLink class="w-4 h-4" />
            Manage Subscription
          </button>
        </div>
      </div>

      <!-- Usage Summary -->
      <div v-if="usage" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Usage Summary</h2>
        <div class="space-y-4">
          <div>
            <div class="flex items-center justify-between text-sm mb-1">
              <span class="text-gray-600">Recordings</span>
              <span class="font-medium">{{ usage.recordings_count }}</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div class="bg-indigo-600 h-2 rounded-full" :style="{ width: Math.min(usage.recordings_count * 10, 100) + '%' }" />
            </div>
          </div>

          <div>
            <div class="flex items-center justify-between text-sm mb-1">
              <span class="text-gray-600">Conversions</span>
              <span class="font-medium">{{ usage.conversions_count }}</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div class="bg-green-600 h-2 rounded-full" :style="{ width: Math.min(usage.conversions_count * 10, 100) + '%' }" />
            </div>
          </div>

          <div>
            <div class="flex items-center justify-between text-sm mb-1">
              <span class="text-gray-600">Storage</span>
              <span class="font-medium">{{ formatBytes(usage.storage_bytes) }}</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div class="bg-purple-600 h-2 rounded-full" :style="{ width: Math.min(usage.storage_bytes / (1024 * 1024 * 1024) * 10, 100) + '%' }" />
            </div>
          </div>
        </div>
      </div>

      <!-- Available Plans -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Available Plans</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div
            v-for="plan in plans"
            :key="plan.id"
            class="border border-gray-200 rounded-lg p-4"
          >
            <h3 class="font-semibold text-gray-900">{{ plan.name }}</h3>
            <p class="text-sm text-gray-500 mt-1">{{ plan.description }}</p>
            <p class="text-2xl font-bold text-gray-900 mt-3">
              ${{ (plan.price_monthly_cents / 100).toFixed(0) }}
              <span class="text-sm font-normal text-gray-500">/mo</span>
            </p>
            <ul class="mt-3 space-y-1 text-sm text-gray-600">
              <li>{{ plan.limits.max_recordings_per_month }} recordings/mo</li>
              <li>{{ plan.limits.max_minutes_per_recording }} min/recording</li>
              <li>{{ plan.limits.max_storage_gb }} GB storage</li>
              <li>{{ plan.limits.max_seats }} seats</li>
            </ul>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
