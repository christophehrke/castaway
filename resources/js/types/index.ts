export interface User {
  id: string
  name: string
  email: string
  role: string
  organization_id: string
  is_superadmin: boolean
  created_at: string
}

export interface Organization {
  id: string
  name: string
  slug: string
  created_at: string
}

export interface ApiKey {
  id: string
  label: string
  key_prefix: string
  last_used_at: string | null
  revoked_at: string | null
  created_at: string
}

export interface Recording {
  id: string
  title: string | null
  original_filename: string
  mime_type: string
  file_size_bytes: number
  duration_seconds: number | null
  status: string
  created_at: string
  updated_at: string
  assets?: RecordingAsset[]
  intent?: AiIntent
  workflows?: Workflow[]
}

export interface RecordingAsset {
  id: string
  type: string
  storage_path: string
  mime_type: string | null
  metadata: any
  created_at: string
}

export interface AiIntent {
  id: string
  version: number
  status: string
  title: string | null
  description: string | null
  steps: IntentStep[] | null
  model_used: string | null
  created_at: string
}

export interface IntentStep {
  order: number
  action: string
  app: string
  description: string
  parameters: Record<string, any>
  evidence: {
    transcript_start: string
    transcript_end: string
    frame_numbers: number[]
  }
}

export interface Workflow {
  id: string
  engine: string
  variant: string
  version: number
  workflow_json: any
  node_count: number | null
  status: string
  created_at: string
}

export interface Plan {
  id: string
  code: string
  name: string
  description: string | null
  price_monthly_cents: number
  price_yearly_cents: number
  limits: PlanLimits
  sort_order: number
}

export interface PlanLimits {
  max_recordings_per_month: number
  max_minutes_per_recording: number
  max_storage_gb: number
  max_seats: number
}

export interface UsageData {
  recordings_count: number
  conversions_count: number
  storage_bytes: number
}
