<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const organizationName = ref('')
const errors = ref<Record<string, string[]>>({})
const error = ref('')
const loading = ref(false)

async function handleSubmit() {
  error.value = ''
  errors.value = {}
  loading.value = true
  try {
    await auth.register({
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
      organization_name: organizationName.value,
    })
    router.push('/app/dashboard')
  } catch (err: any) {
    if (err.errors) {
      errors.value = err.errors
    } else {
      error.value = err.message || 'Registration failed'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <form @submit.prevent="handleSubmit" class="space-y-4">
    <h2 class="text-xl font-semibold text-gray-900 text-center">Create Account</h2>

    <div v-if="error" class="bg-red-50 text-red-600 text-sm p-3 rounded-md">
      {{ error }}
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
      <input
        v-model="name"
        type="text"
        required
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
      />
      <p v-if="errors.name" class="text-red-500 text-xs mt-1">{{ errors.name[0] }}</p>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
      <input
        v-model="email"
        type="email"
        required
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
      />
      <p v-if="errors.email" class="text-red-500 text-xs mt-1">{{ errors.email[0] }}</p>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Organization Name</label>
      <input
        v-model="organizationName"
        type="text"
        required
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
      />
      <p v-if="errors.organization_name" class="text-red-500 text-xs mt-1">{{ errors.organization_name[0] }}</p>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
      <input
        v-model="password"
        type="password"
        required
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
      />
      <p v-if="errors.password" class="text-red-500 text-xs mt-1">{{ errors.password[0] }}</p>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
      <input
        v-model="passwordConfirmation"
        type="password"
        required
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
      />
    </div>

    <button
      type="submit"
      :disabled="loading"
      class="w-full px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50 font-medium"
    >
      {{ loading ? 'Creating account...' : 'Create Account' }}
    </button>

    <p class="text-center text-sm text-gray-500">
      Already have an account?
      <RouterLink to="/login" class="text-indigo-600 hover:text-indigo-700 font-medium">Sign In</RouterLink>
    </p>
  </form>
</template>
