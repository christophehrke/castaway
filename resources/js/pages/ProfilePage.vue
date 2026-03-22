<script setup lang="ts">
import { useAuthStore } from '../stores/auth'
import { User, Building2, Mail } from 'lucide-vue-next'

const auth = useAuthStore()
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Profile</h1>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 max-w-lg">
      <div class="space-y-6">
        <div class="flex items-center gap-4">
          <div class="w-16 h-16 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center text-2xl font-bold">
            {{ auth.user?.name?.charAt(0)?.toUpperCase() }}
          </div>
          <div>
            <h2 class="text-lg font-semibold text-gray-900">{{ auth.user?.name }}</h2>
            <p class="text-sm text-gray-500 capitalize">{{ auth.user?.role }}</p>
          </div>
        </div>

        <div class="border-t border-gray-200 pt-6 space-y-4">
          <div class="flex items-center gap-3">
            <User class="w-5 h-5 text-gray-400" />
            <div>
              <p class="text-xs text-gray-500">Name</p>
              <p class="text-sm font-medium text-gray-900">{{ auth.user?.name }}</p>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <Mail class="w-5 h-5 text-gray-400" />
            <div>
              <p class="text-xs text-gray-500">Email</p>
              <p class="text-sm font-medium text-gray-900">{{ auth.user?.email }}</p>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <Building2 class="w-5 h-5 text-gray-400" />
            <div>
              <p class="text-xs text-gray-500">Organization</p>
              <p class="text-sm font-medium text-gray-900">{{ auth.organization?.name }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
