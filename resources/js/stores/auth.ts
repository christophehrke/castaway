import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api, getToken, removeToken } from '../lib/api'
import type { User, Organization } from '../types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const organization = ref<Organization | null>(null)
  const token = ref<string | null>(getToken())

  const isAuthenticated = computed(() => !!token.value)

  async function login(email: string, password: string) {
    const data = await api.auth.login({ email, password })
    user.value = data.user
    organization.value = data.organization
    token.value = data.token
  }

  async function register(payload: {
    name: string
    email: string
    password: string
    password_confirmation: string
    organization_name: string
  }) {
    const data = await api.auth.register(payload)
    user.value = data.user
    organization.value = data.organization
    token.value = data.token
  }

  async function logout() {
    try {
      await api.auth.logout()
    } catch {
      // ignore logout errors
    }
    user.value = null
    organization.value = null
    token.value = null
    removeToken()
  }

  async function fetchMe() {
    try {
      const data = await api.auth.me()
      user.value = data.user
      organization.value = data.organization
    } catch {
      user.value = null
      organization.value = null
      token.value = null
      removeToken()
    }
  }

  return {
    user,
    organization,
    token,
    isAuthenticated,
    login,
    register,
    logout,
    fetchMe,
  }
})
