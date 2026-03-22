import type { User, Organization, ApiKey, Recording, Workflow, Plan, UsageData } from '../types'

const BASE_URL = '/api/v1'

function getToken(): string | null {
  return localStorage.getItem('auth_token')
}

function setToken(token: string): void {
  localStorage.setItem('auth_token', token)
}

function removeToken(): void {
  localStorage.removeItem('auth_token')
}

async function request<T>(
  method: string,
  path: string,
  body?: any,
  isFormData = false
): Promise<T> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
  }

  const token = getToken()
  if (token) {
    headers['Authorization'] = `Bearer ${token}`
  }

  if (!isFormData) {
    headers['Content-Type'] = 'application/json'
  }

  const options: RequestInit = {
    method,
    headers,
  }

  if (body) {
    options.body = isFormData ? body : JSON.stringify(body)
  }

  const response = await fetch(`${BASE_URL}${path}`, options)

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Request failed' }))
    throw { status: response.status, ...error }
  }

  if (response.status === 204) {
    return undefined as T
  }

  return response.json()
}

export const api = {
  auth: {
    async register(data: { name: string; email: string; password: string; password_confirmation: string; organization_name: string }) {
      const res = await request<{ data: { user: User; organization: Organization; token: string } }>('POST', '/auth/register', data)
      setToken(res.data.token)
      return res.data
    },

    async login(data: { email: string; password: string }) {
      const res = await request<{ data: { user: User; organization: Organization; token: string } }>('POST', '/auth/login', data)
      setToken(res.data.token)
      return res.data
    },

    async logout() {
      await request<void>('POST', '/auth/logout')
      removeToken()
    },

    async me() {
      const res = await request<{ data: { user: User; organization: Organization } }>('GET', '/me')
      return res.data
    },
  },

  apiKeys: {
    async list() {
      const res = await request<{ data: ApiKey[] }>('GET', '/api-keys')
      return res.data
    },

    async create(data: { label: string }) {
      const res = await request<{ data: ApiKey & { plaintext_key: string } }>('POST', '/api-keys', data)
      return res.data
    },

    async delete(id: string) {
      await request<void>('DELETE', `/api-keys/${id}`)
    },
  },

  recordings: {
    async list(page = 1) {
      const res = await request<{ data: Recording[]; meta: any }>('GET', `/recordings?page=${page}`)
      return res
    },

    async create(file: File, title?: string) {
      const formData = new FormData()
      formData.append('file', file)
      if (title) formData.append('title', title)
      const res = await request<{ data: Recording }>('POST', '/recordings', formData, true)
      return res.data
    },

    async show(id: string) {
      const res = await request<{ data: Recording }>('GET', `/recordings/${id}`)
      return res.data
    },

    async delete(id: string) {
      await request<void>('DELETE', `/recordings/${id}`)
    },

    async getIntent(recordingId: string) {
      const res = await request<{ data: any }>('GET', `/recordings/${recordingId}/intent`)
      return res.data
    },

    async regenerateIntent(recordingId: string) {
      const res = await request<{ message: string }>('POST', `/recordings/${recordingId}/intent/regenerate`)
      return res
    },

    async generateWorkflows(recordingId: string) {
      const res = await request<{ data: any }>('POST', `/recordings/${recordingId}/workflows/generate`)
      return res.data
    },
  },

  workflows: {
    async list(params?: { engine?: string; variant?: string; page?: number }) {
      const query = new URLSearchParams()
      if (params?.engine) query.set('engine', params.engine)
      if (params?.variant) query.set('variant', params.variant)
      if (params?.page) query.set('page', String(params.page))
      const qs = query.toString()
      const res = await request<{ data: Workflow[]; meta: any }>('GET', `/workflows${qs ? '?' + qs : ''}`)
      return res
    },

    async show(id: string) {
      const res = await request<{ data: Workflow }>('GET', `/workflows/${id}`)
      return res.data
    },
  },

  billing: {
    async plans() {
      const res = await request<{ data: Plan[] }>('GET', '/billing/plans')
      return res.data
    },

    async checkout(data: { plan_code: string; billing_cycle: string }) {
      const res = await request<{ data: { checkout_url: string } }>('POST', '/billing/checkout', data)
      return res.data
    },

    async portal() {
      const res = await request<{ data: { portal_url: string } }>('GET', '/billing/portal')
      return res.data
    },

    async usage() {
      const res = await request<{ data: UsageData }>('GET', '/billing/usage')
      return res.data
    },
  },

  admin: {
    metrics: () => request<{ data: any }>('GET', '/admin/metrics'),
    failedRecordings: (page = 1) => request<any>('GET', `/admin/recordings/failed?page=${page}`),
    reprocess: (id: string) => request<{ data: any }>('POST', `/admin/recordings/${id}/reprocess`),
    commandRuns: (page = 1, command?: string) => request<any>('GET', `/admin/command-runs?page=${page}${command ? `&command=${command}` : ''}`),
    organizations: (page = 1) => request<any>('GET', `/admin/organizations?page=${page}`),
  },
}

export { getToken, setToken, removeToken }
