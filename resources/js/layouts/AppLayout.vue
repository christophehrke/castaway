<script setup lang="ts">
import { useAuthStore } from '../stores/auth'
import { useRouter } from 'vue-router'
import { LayoutDashboard, Mic, GitBranch, Key, CreditCard, User, LogOut } from 'lucide-vue-next'

const auth = useAuthStore()
const router = useRouter()

const navItems = [
  { label: 'Dashboard', icon: LayoutDashboard, to: '/app/dashboard' },
  { label: 'Recordings', icon: Mic, to: '/app/recordings' },
  { label: 'Workflows', icon: GitBranch, to: '/app/workflows' },
  { label: 'API Keys', icon: Key, to: '/app/api-keys' },
  { label: 'Billing', icon: CreditCard, to: '/app/billing' },
  { label: 'Profile', icon: User, to: '/app/profile' },
]

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex">
    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r border-gray-200 flex flex-col">
      <div class="p-6 border-b border-gray-200">
        <h1 class="text-xl font-bold text-indigo-600">FlowCast</h1>
        <p class="text-xs text-gray-400 mt-1 truncate">{{ auth.organization?.name }}</p>
      </div>

      <nav class="flex-1 p-4 space-y-1">
        <RouterLink
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition-colors"
          :class="$route.path === item.to
            ? 'bg-indigo-50 text-indigo-700'
            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
        >
          <component :is="item.icon" class="w-5 h-5" />
          {{ item.label }}
        </RouterLink>
      </nav>

      <div class="p-4 border-t border-gray-200">
        <button
          @click="handleLogout"
          class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 w-full transition-colors"
        >
          <LogOut class="w-5 h-5" />
          Logout
        </button>
      </div>
    </aside>

    <!-- Main content -->
    <div class="flex-1 flex flex-col">
      <!-- Top bar -->
      <header class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
        <div></div>
        <div class="flex items-center gap-3">
          <span class="text-sm text-gray-600">{{ auth.user?.name }}</span>
          <div class="w-8 h-8 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center text-sm font-medium">
            {{ auth.user?.name?.charAt(0)?.toUpperCase() }}
          </div>
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1 p-6">
        <slot />
      </main>
    </div>
  </div>
</template>
