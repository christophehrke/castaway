# FlowCast — Implementation Audit Report

**Date:** 2026-03-22
**Revision:** 4.0 — Full codebase audit against `prompt.md` specification
**Auditor:** Automated (all files read and cross-referenced)

---

## Executive Summary

| Metric | Count |
|--------|-------|
| Overall Completion | **~70%** |
| Fully Implemented (✅) | 42 |
| Partially Implemented (⚠️) | 19 |
| Missing (❌) | 8 |
| Critical Issues (P0) | 5 |

The **backend is substantially complete** (~90%) — all models, migrations, controllers, services, commands, and middleware exist with real logic. The **frontend is functionally complete** (~60%) — all pages and layouts exist with correct data fetching and logic, but **none use shadcn-vue components** (the spec requirement); they all use raw HTML with Tailwind utility classes. The **Chrome extension** has a critical bug (MediaRecorder never created). **Testing is essentially absent** (only default Laravel example tests).

---

## 1. Backend — Models & Database

### Models

| Model | File | Fillable/Casts | Relationships | Status |
|-------|------|----------------|---------------|--------|
| Organization | `app/Models/Organization.php` | ✅ HasUuids, SoftDeletes, fillable: name, slug, paddle_customer_id | users, recordings, subscriptions, apiKeys, usageCounters, workflows | ✅ Complete |
| User | `app/Models/User.php` | ✅ HasApiTokens, HasUuids, SoftDeletes, fillable includes organization_id, role, is_superadmin | organization (belongsTo), recordings (via org) | ✅ Complete |
| OrganizationUser | `app/Models/OrganizationUser.php` | ✅ Pivot model, HasUuids | organization, user | ✅ Complete |
| ApiKey | `app/Models/ApiKey.php` | ✅ HasUuids, SoftDeletes, fillable: org_id, label, key_hash, key_prefix, last_used_at, revoked_at | organization | ✅ Complete |
| Plan | `app/Models/Plan.php` | ✅ HasUuids, fillable includes limits_json, casts limits_json as array | subscriptions | ✅ Complete |
| Subscription | `app/Models/Subscription.php` | ✅ HasUuids, fillable includes paddle fields, casts current_period_start/end as datetime | organization, plan | ✅ Complete |
| UsageCounter | `app/Models/UsageCounter.php` | ✅ HasUuids, fillable: org_id, period, recordings_count, conversions_count, ai_tokens_used, storage_bytes | organization | ✅ Complete |
| Recording | `app/Models/Recording.php` | ✅ HasUuids, SoftDeletes, fillable includes status, duration_seconds, file_size_bytes | organization, user, assets, intent (latestOfMany), workflows, pipelineErrors | ✅ Complete |
| RecordingAsset | `app/Models/RecordingAsset.php` | ✅ HasUuids, fillable: recording_id, type, storage_path, mime_type, size_bytes | recording | ✅ Complete |
| AiIntent | `app/Models/AiIntent.php` | ✅ HasUuids, casts steps/confidence_scores as array, tokens as integer | recording, workflows | ✅ Complete |
| Workflow | `app/Models/Workflow.php` | ✅ HasUuids, casts workflow_json as array | recording, aiIntent, organization | ✅ Complete |
| PipelineError | `app/Models/PipelineError.php` | ✅ HasUuids, fillable: recording_id, stage, error_message, context, resolved_at | recording | ✅ Complete |
| CommandRun | `app/Models/CommandRun.php` | ✅ HasUuids, fillable: command, status, started_at, completed_at, records_processed, records_failed, error_summary | — | ✅ Complete |
| PaddleWebhookEvent | `app/Models/PaddleWebhookEvent.php` | ✅ HasUuids, casts payload as array | — | ✅ Complete |

### Migrations

| Migration | Columns Match Spec | Indexes | Status |
|-----------|-------------------|---------|--------|
| `create_organizations_table` | ✅ id, name, slug, paddle_customer_id, timestamps, soft_deletes | unique(slug) | ✅ Complete |
| `create_users_table` | ✅ id, organization_id (FK), name, email, password, role (enum), is_superadmin, email_verified_at, remember_token, timestamps, soft_deletes | unique(email), index(organization_id) | ✅ Complete |
| `create_organization_user_table` | ✅ Pivot with role | unique(organization_id, user_id) | ✅ Complete |
| `create_api_keys_table` | ✅ id, organization_id (FK), label, key_hash, key_prefix, last_used_at, revoked_at, timestamps | unique(key_hash), index(organization_id) | ✅ Complete |
| `create_plans_table` | ✅ id, slug, name, description, price_monthly_cents, paddle_plan_id, limits_json, is_active, sort_order, timestamps | unique(slug) | ✅ Complete |
| `create_subscriptions_table` | ✅ id, organization_id (FK), plan_id (FK), paddle_subscription_id, status, current_period_start/end, timestamps | index(organization_id), unique(paddle_subscription_id) | ✅ Complete |
| `create_usage_counters_table` | ✅ id, organization_id (FK), period, recordings_count, conversions_count, ai_tokens_used, storage_bytes, timestamps | unique(organization_id, period) | ✅ Complete |
| `create_recordings_table` | ✅ id, organization_id (FK), user_id (FK nullable), title, original_filename, status (enum with all 7 states), duration_seconds, file_size_bytes, timestamps, soft_deletes | index(organization_id, status) | ✅ Complete |
| `create_recording_assets_table` | ✅ id, recording_id (FK), type (enum), storage_path, mime_type, size_bytes, timestamps | index(recording_id) | ✅ Complete |
| `create_ai_intents_table` | ✅ id, recording_id (FK), version, description, steps (JSON), confidence_scores (JSON), prompt_tokens, completion_tokens, total_tokens, model_used, timestamps | index(recording_id) | ✅ Complete |
| `create_workflows_table` | ✅ id, recording_id (FK), ai_intent_id (FK), organization_id (FK), engine, variant (enum: minimal, robust, with_logging), version, status, node_count, workflow_json, timestamps | index(organization_id, engine) | ✅ Complete |
| `create_pipeline_errors_table` | ✅ id, recording_id (FK), stage, error_message, context (JSON), resolved_at, timestamps | index(recording_id), index(stage) | ✅ Complete |
| `create_command_runs_table` | ✅ All columns match spec | index(command, started_at) | ✅ Complete |
| `create_paddle_webhook_events_table` | ✅ id, event_type, paddle_event_id, payload, processed_at, timestamps | unique(paddle_event_id) | ✅ Complete |
| `create_personal_access_tokens_table` | ✅ Standard Sanctum migration | — | ✅ Complete |

**Section Status: ✅ Complete** — All 14 spec tables + Sanctum tokens table present with correct schema.

---

## 2. Backend — API Controllers & Routes

### Routes (`routes/api.php`)

| Endpoint | Method | Controller | Route Defined | Controller Method | FormRequest | Resource | `{data}` Wrapper | Status |
|----------|--------|-----------|---------------|-------------------|-------------|----------|-------------------|--------|
| `/api/v1/auth/register` | POST | AuthController | ✅ | ✅ register() | ✅ RegisterRequest | ✅ UserResource | ✅ | ✅ |
| `/api/v1/auth/login` | POST | AuthController | ✅ | ✅ login() | ✅ LoginRequest | ✅ UserResource | ✅ | ✅ |
| `/api/v1/auth/logout` | POST | AuthController | ✅ | ✅ logout() | — | — | ✅ | ✅ |
| `/api/v1/auth/me` | GET | AuthController | ✅ | ✅ me() | — | ✅ UserResource + OrgResource | ✅ | ✅ |
| `/api/v1/api-keys` | GET | ApiKeyController | ✅ | ✅ index() | — | ✅ ApiKeyResource | ✅ | ✅ |
| `/api/v1/api-keys` | POST | ApiKeyController | ✅ | ✅ store() | ✅ StoreApiKeyRequest | ✅ ApiKeyResource | ✅ | ✅ |
| `/api/v1/api-keys/{id}` | DELETE | ApiKeyController | ✅ | ✅ destroy() | — | — | ✅ | ✅ |
| `/api/v1/recordings` | GET | RecordingController | ✅ | ✅ index() | — | ✅ RecordingResource | ✅ (paginated) | ✅ |
| `/api/v1/recordings` | POST | RecordingController | ✅ | ✅ store() | ✅ StoreRecordingRequest | ✅ RecordingResource | ✅ | ✅ |
| `/api/v1/recordings/{id}` | GET | RecordingController | ✅ | ✅ show() | — | ✅ RecordingResource | ✅ | ✅ |
| `/api/v1/recordings/{id}` | DELETE | RecordingController | ✅ | ✅ destroy() | — | — | ✅ | ✅ |
| `/api/v1/recordings/{id}/intent` | GET | IntentController | ✅ | ✅ show() | — | ✅ AiIntentResource | ✅ | ✅ |
| `/api/v1/recordings/{id}/intent/regenerate` | POST | IntentController | ✅ | ✅ regenerate() | — | — | ✅ | ✅ |
| `/api/v1/recordings/{id}/workflows/generate` | POST | WorkflowController | ✅ | ✅ generate() | — | ✅ WorkflowResource | ✅ | ✅ |
| `/api/v1/workflows` | GET | WorkflowController | ✅ | ✅ index() | — | ✅ WorkflowResource | ✅ (paginated) | ✅ |
| `/api/v1/workflows/{id}` | GET | WorkflowController | ✅ | ✅ show() | — | ✅ WorkflowResource | ✅ | ✅ |
| `/api/v1/billing/plans` | GET | BillingController | ✅ | ✅ plans() | — | ✅ PlanResource | ✅ | ✅ |
| `/api/v1/billing/checkout` | POST | BillingController | ✅ | ✅ checkout() | — | — | ✅ (skeleton) | ⚠️ Skeleton |
| `/api/v1/billing/portal` | GET | BillingController | ✅ | ✅ portal() | — | — | ✅ (skeleton) | ⚠️ Skeleton |
| `/api/v1/billing/usage` | GET | BillingController | ✅ | ✅ usage() | — | — | ✅ | ✅ |
| `/api/v1/paddle/webhook` | POST | BillingController | ✅ | ✅ webhook() | — | — | ✅ | ✅ |
| `/api/v1/extension/ping` | GET | ExtensionController | ✅ | ✅ ping() | — | — | ✅ | ✅ |
| `/api/v1/recordings/from-extension` | POST | RecordingController | ✅ | ✅ storeFromExtension() | — | ✅ RecordingResource | ✅ | ✅ |
| `/api/v1/admin/metrics` | GET | AdminController | ✅ | ✅ metrics() | — | — | ✅ | ✅ |
| `/api/v1/admin/failed-recordings` | GET | AdminController | ✅ | ✅ failedRecordings() | — | — | ⚠️ Raw paginator | ⚠️ |
| `/api/v1/admin/recordings/{id}/reprocess` | POST | AdminController | ✅ | ✅ reprocess() | — | — | ✅ | ✅ |
| `/api/v1/admin/command-runs` | GET | AdminController | ✅ | ✅ commandRuns() | — | — | ⚠️ Raw paginator | ⚠️ |
| `/api/v1/admin/organizations` | GET | AdminController | ✅ | ✅ organizations() | — | — | ⚠️ Raw paginator | ⚠️ |

### Issues Found

1. **`AdminController::failedRecordings()`** at [`AdminController.php`](app/Http/Controllers/Api/V1/AdminController.php:47) — Returns raw `Recording::paginate()` without `{data: ...}` wrapper or Resource transformation
2. **`AdminController::commandRuns()`** at [`AdminController.php`](app/Http/Controllers/Api/V1/AdminController.php:64) — Returns raw `CommandRun::paginate()` without wrapper
3. **`AdminController::organizations()`** at [`AdminController.php`](app/Http/Controllers/Api/V1/AdminController.php:71) — Returns raw `Organization::paginate()` without wrapper
4. **`ApiKeyController::store()`** response shape — Returns `{data: {api_key: ..., plaintext_key: ..., note: ...}}` but frontend `api.ts` expects `plain_text_key` field (key name mismatch)

**Section Status: ⚠️ Partial** — All routes/controllers exist but 3 admin endpoints lack `{data}` wrapper and there's a response field mismatch.

---

## 3. Backend — Services

| Service | File | Methods | Logic Correctness | Status |
|---------|------|---------|-------------------|--------|
| MediaProcessingService | `app/Services/MediaProcessingService.php` | ✅ normalizeVideo, extractAudio, extractFrames, generateThumbnail, getDuration | ✅ Real ffmpeg shell commands, proper error handling, PipelineError logging | ✅ Complete |
| AiProcessingService | `app/Services/AiProcessingService.php` | ✅ processRecording, transcribeAudio, analyzeFrames, fuseInsights, validateSteps | ✅ OpenAI Whisper + GPT-4o Vision + GPT-4 fusion, token tracking, proper prompts | ✅ Complete |
| WorkflowGenerationService | `app/Services/WorkflowGenerationService.php` | ✅ generateWorkflows, generateN8nWorkflow, buildNodes, buildConnections, createErrorHandlerNode, createLoggingNode | ✅ NODE_MAP for 20+ apps, 3 variants (minimal/robust/with_logging), proper n8n JSON | ✅ Complete |
| UsageLimitService | `app/Services/UsageLimitService.php` | ✅ canUploadRecording, canProcessDuration, canGenerateWorkflow, getPlanLimits, getCurrentUsage, getRemainingQuota | ✅ Checks plan limits, falls back to starter defaults, period-based counting | ✅ Complete |

### Issues Found

1. **`AiProcessingService::analyzeFrames()`** — Uses hardcoded `gpt-4o` model but doesn't reference the spec's suggested model version
2. **Duration limits** — `UsageLimitService::canProcessDuration()` checks `max_minutes_per_recording` correctly ✅
3. **AI token tracking** — `AiProcessingService` properly tracks `prompt_tokens`, `completion_tokens`, `total_tokens` and stores on `AiIntent` ✅

**Section Status: ✅ Complete** — All 4 services implemented with real business logic.

---

## 4. Backend — Artisan Commands & Scheduling

| Command | Signature | Logic | Logging to command_runs | Status |
|---------|-----------|-------|------------------------|--------|
| `admin:seed-superuser` | ✅ `admin:seed-superuser {--email=} {--password=} {--name=}` | ✅ Creates org + user + pivot with is_superadmin=true | ✅ | ✅ Complete |
| `plans:sync` | ✅ `plans:sync {--dry-run}` | ✅ Upserts 3 plans (Starter $29, Pro $79, Enterprise $249) with correct limits | ✅ | ✅ Complete |
| `recordings:process-media` | ✅ `recordings:process-media {--limit=10} {--recording-id=}` | ✅ Finds 'uploaded' recordings, calls MediaProcessingService, updates status | ✅ | ✅ Complete |
| `recordings:process-ai` | ✅ `recordings:process-ai {--limit=5} {--recording-id=}` | ✅ Finds 'media_ready' recordings, calls AiProcessingService | ✅ | ✅ Complete |
| `workflows:generate` | ✅ `workflows:generate {--limit=10} {--recording-id=}` | ✅ Finds 'intent_ready' recordings, calls WorkflowGenerationService | ✅ | ✅ Complete |
| `stats:aggregate` | ✅ `stats:aggregate {--period=} {--dry-run}` | ✅ Aggregates recordings, storage, tokens per org per period | ✅ | ✅ Complete |
| `billing:reconcile` | ✅ `billing:reconcile {--dry-run}` | ✅ Syncs Paddle subscription statuses (skeleton API calls) | ✅ | ✅ Complete |
| `recordings:cleanup-storage` | ✅ `recordings:cleanup-storage {--days=90} {--dry-run}` | ✅ Deletes old soft-deleted recording files from storage | ✅ | ✅ Complete |

### Scheduling (`routes/console.php`)

| Command | Scheduled | Interval | Status |
|---------|-----------|----------|--------|
| `recordings:process-media` | ✅ | everyMinute | ✅ |
| `recordings:process-ai` | ✅ | everyMinute | ✅ |
| `workflows:generate` | ✅ | everyFiveMinutes | ✅ |
| `stats:aggregate` | ✅ | daily | ✅ |
| `billing:reconcile` | ✅ | hourly | ✅ |
| `recordings:cleanup-storage` | ✅ | daily | ✅ |

**Section Status: ✅ Complete** — All 8 commands implemented with real logic and proper scheduling.

---

## 5. Backend — Middleware & Auth

| Middleware | File | Logic | Registered | Status |
|-----------|------|-------|------------|--------|
| Sanctum Auth | Built-in `auth:sanctum` | ✅ Token-based authentication | ✅ Used in `routes/api.php` | ✅ Complete |
| AuthenticateApiKey | `app/Http/Middleware/AuthenticateApiKey.php` | ✅ Reads `X-Api-Key` header, SHA-256 hashes, looks up in api_keys table, checks not revoked, updates last_used_at, sets Auth::login() | ✅ Aliased as `auth.apikey` in `bootstrap/app.php` | ✅ Complete |
| EnsureSuperAdmin | `app/Http/Middleware/EnsureSuperAdmin.php` | ✅ Checks `auth()->user()->is_superadmin` | ✅ Aliased as `superadmin` in `bootstrap/app.php` | ✅ Complete |
| VerifyPaddleWebhook | `app/Http/Middleware/VerifyPaddleWebhook.php` | ✅ Reads `Paddle-Signature` header, parses ts and h1, HMAC-SHA256 verification against `PADDLE_WEBHOOK_SECRET` | ✅ Aliased as `verify.paddle` in `bootstrap/app.php` | ✅ Complete |

### `bootstrap/app.php` Configuration

- ✅ Middleware aliases registered correctly
- ✅ API middleware group includes `throttle:api` and `Sanctum::statefulMiddleware` (via `statefulApi()`)
- ✅ Exception handling: `LimitExceededException` rendered as 429 JSON response

### `app/Exceptions/LimitExceededException.php`

- ✅ Extends base Exception
- ✅ Carries `$limitType` property
- ✅ Rendered by `bootstrap/app.php` exception handler

**Section Status: ✅ Complete**

---

## 6. Frontend — Pages & Components (Vue 3 + shadcn-vue)

> **CRITICAL FINDING:** All 11 page components and 3 layout components use **raw HTML elements** (`<button>`, `<input>`, `<table>`, `<label>`, `<select>`) with Tailwind utility classes. **None of them import or use any shadcn-vue components** (Button, Card, Table, Dialog, Tabs, Badge, Input, Label, Progress, etc.) despite these components being installed in `resources/js/components/ui/`. This is the single largest gap in the codebase.

| Page | File | Exists | Data Fetching | Features Present | Uses shadcn-vue | Status |
|------|------|--------|---------------|------------------|-----------------|--------|
| Login | `resources/js/pages/LoginPage.vue` | ✅ | ✅ auth store | ✅ Email/password form, error display, link to register | ❌ Raw `<input>`, `<button>`, `<label>` | ⚠️ Needs refactor |
| Register | `resources/js/pages/RegisterPage.vue` | ✅ | ✅ auth store | ✅ All fields (name, email, org, password, confirm), validation errors | ❌ Raw HTML | ⚠️ Needs refactor |
| Dashboard | `resources/js/pages/DashboardPage.vue` | ✅ | ✅ api.recordings.list + api.workflows.list + api.billing.usage | ✅ Stats cards, recent recordings, usage display | ❌ Raw HTML | ⚠️ Needs refactor |
| Recordings List | `resources/js/pages/RecordingsListPage.vue` | ✅ | ✅ api.recordings.list (paginated) | ✅ Upload dialog, table with status, pagination, empty state | ❌ Raw `<table>`, `<button>` | ⚠️ Needs refactor |
| Recording Detail | `resources/js/pages/RecordingDetailPage.vue` | ✅ | ✅ api.recordings.show + getIntent | ✅ Video player, transcript tab, intent steps, workflows tab, copy JSON, download, pipeline timeline, regenerate/generate buttons | ❌ Raw `<button>`, custom tabs (not `<Tabs>`) | ⚠️ Needs refactor |
| Workflows List | `resources/js/pages/WorkflowsListPage.vue` | ✅ | ✅ api.workflows.list (filtered) | ✅ Engine/variant filters, table, pagination, empty state | ❌ Raw `<table>`, `<select>` | ⚠️ Needs refactor |
| Workflow Detail | `resources/js/pages/WorkflowDetailPage.vue` | ✅ | ✅ api.workflows.show | ✅ Copy to clipboard, download JSON, metadata cards, JsonViewer | ❌ Raw `<button>`, `<div>` cards | ⚠️ Needs refactor |
| API Keys | `resources/js/pages/ApiKeysPage.vue` | ✅ | ✅ api.apiKeys.list + create + delete | ✅ Create dialog, save key warning, table, revoke | ❌ Raw `<table>`, `<input>`, custom modal | ⚠️ Needs refactor |
| Billing | `resources/js/pages/BillingPage.vue` | ✅ | ✅ api.billing.plans + usage | ✅ Current plan, usage summary with progress bars, plan grid, manage subscription button | ❌ Raw HTML progress bars | ⚠️ Needs refactor |
| Profile | `resources/js/pages/ProfilePage.vue` | ✅ | ✅ auth store | ✅ User info display (name, email, org, role) | ❌ Raw HTML | ⚠️ Needs refactor |
| Admin Dashboard | `resources/js/pages/AdminDashboardPage.vue` | ✅ | ✅ api.admin.metrics + failedRecordings | ✅ 6 metric cards, recordings by status, pipeline errors, command runs table, failed recordings with reprocess button | ❌ Raw HTML tables/cards | ⚠️ Needs refactor |

### Custom (Non-UI) Components

| Component | File | Uses shadcn-vue | Status |
|-----------|------|-----------------|--------|
| StatusBadge | `resources/js/components/StatusBadge.vue` | ❌ (should use `<Badge>`) | ⚠️ Needs refactor |
| LoadingSpinner | `resources/js/components/LoadingSpinner.vue` | ❌ (could use `<Skeleton>`) | ⚠️ Needs refactor |
| EmptyState | `resources/js/components/EmptyState.vue` | ❌ Raw HTML | ⚠️ Needs refactor |
| JsonViewer | `resources/js/components/JsonViewer.vue` | ❌ Raw `<pre>` | ⚠️ OK (no shadcn equiv) |
| PipelineTimeline | `resources/js/components/PipelineTimeline.vue` | ❌ Raw HTML | ⚠️ OK (custom component) |
| UploadDialog | `resources/js/components/UploadDialog.vue` | ❌ (should use `<Dialog>`, `<Input>`, `<Button>`) | ⚠️ Needs refactor |

### Additional Issues

- **WorkflowsListPage variant filter** uses value `"full"` at [`WorkflowsListPage.vue`](resources/js/pages/WorkflowsListPage.vue:74) but the spec and migration define the variant enum as `minimal`, `robust`, `with_logging` — **"full" should be "with_logging"**
- **PipelineTimeline stages** at [`PipelineTimeline.vue`](resources/js/components/PipelineTimeline.vue:9) — Lists `['uploaded', 'media_ready', 'intent_ready', 'workflows_ready']` but the spec pipeline is `uploaded → processing_media → media_ready → processing_ai → intent_ready → generating_workflows → workflows_ready`. The timeline only shows 4 milestone states (acceptable simplification but could show intermediate states)
- **RecordingDetailPage transcript** at [`RecordingDetailPage.vue`](resources/js/pages/RecordingDetailPage.vue:148) — Shows `recording.intent?.description` as transcript, but the spec mentions a dedicated transcript field; currently the transcript is embedded in the intent description (acceptable)

**Section Status: ⚠️ Partial** — All pages exist with correct logic and data fetching, but NONE use shadcn-vue components.

---

## 7. Frontend — Stores, Router, API Client

### Pinia Stores

| Store | File | Exists | Methods | Status |
|-------|------|--------|---------|--------|
| auth | `resources/js/stores/auth.ts` | ✅ | login, register, logout, fetchMe | ✅ Complete |
| recordings | — | ❌ | — | ❌ Missing (spec doesn't explicitly require, pages use api client directly) |

**Note:** The spec doesn't explicitly require separate Pinia stores beyond auth. Pages call the API client directly, which is a valid pattern.

### Vue Router (`resources/js/router/index.ts`)

| Feature | Status |
|---------|--------|
| All 11 routes defined | ✅ |
| Auth guard (beforeEach) | ✅ Redirects to /login if no token and route requires auth |
| Admin guard | ⚠️ Uses `meta: { requiresAdmin: true }` but guard checks `store.user?.is_superadmin` — **UserResource doesn't return `is_superadmin`** so this will always fail |
| Layout meta (auth, app, admin) | ✅ Set correctly on all routes |
| Lazy loading | ❌ All pages imported eagerly (minor optimization issue) |

### API Client (`resources/js/lib/api.ts`)

| Endpoint Group | Methods | Match Backend Routes | Status |
|---------------|---------|---------------------|--------|
| auth | login, register, logout, me | ✅ | ✅ |
| apiKeys | list, create, delete | ✅ | ✅ |
| recordings | list, show, upload, delete, getIntent, regenerateIntent, generateWorkflows | ✅ | ✅ |
| workflows | list, show | ✅ | ✅ |
| billing | plans, checkout, portal, usage | ✅ | ✅ |
| admin | metrics, failedRecordings, reprocess | ✅ | ✅ |

**Issues Found:**
1. **`api.apiKeys.create()`** at [`api.ts`](resources/js/lib/api.ts) — Returns `ApiKey & { plain_text_key: string }` but backend `ApiKeyController::store()` returns `{ api_key: ApiKeyResource, plaintext_key: string, note: string }`. Field name mismatch: `plain_text_key` vs `plaintext_key`
2. **Token storage** — `api.ts` stores token in `localStorage` via `setToken()` / `getToken()` / `removeToken()` helper functions ✅
3. **`api.admin`** — Missing `commandRuns()` and `organizations()` methods that backend provides

### TypeScript Types (`resources/js/types/index.ts`)

| Type | Matches API Response | Status |
|------|---------------------|--------|
| User | ⚠️ Has `is_superadmin: boolean` but `UserResource` doesn't return it | ⚠️ |
| Organization | ✅ | ✅ |
| ApiKey | ✅ | ✅ |
| Recording | ✅ Includes optional intent, workflows, assets | ✅ |
| RecordingAsset | ✅ | ✅ |
| AiIntent | ✅ | ✅ |
| IntentStep | ✅ Matches spec's step schema | ✅ |
| Workflow | ✅ | ✅ |
| Plan | ⚠️ Has `limits` object type but `PlanResource` returns flat `limits_json` field | ⚠️ |
| PlanLimits | ✅ | ✅ |
| UsageData | ⚠️ Only has `recordings_count`, `conversions_count`, `storage_bytes`, `ai_tokens_used` — Backend `billing/usage` returns a richer object with `subscription`, `limits`, `remaining` | ⚠️ |

**Section Status: ⚠️ Partial** — Core functionality works but type mismatches exist, admin guard is broken due to missing `is_superadmin` in UserResource, and API client response types don't fully match backend.

---

## 8. Frontend — Layouts

| Layout | File | Structure | Uses shadcn-vue | Status |
|--------|------|-----------|-----------------|--------|
| AuthLayout | `resources/js/layouts/AuthLayout.vue` | ✅ Centered card with FlowCast branding, slot for page content | ❌ Raw `<div>` with Tailwind | ⚠️ Needs refactor |
| AppLayout | `resources/js/layouts/AppLayout.vue` | ✅ Sidebar with nav items (Dashboard, Recordings, Workflows, API Keys, Billing, Profile), header with user avatar, main content slot | ❌ Raw HTML nav items, no `<Button>`, no `<DropdownMenu>` | ⚠️ Needs refactor |
| AdminLayout | `resources/js/layouts/AdminLayout.vue` | ✅ Sidebar with Admin Dashboard link + Back to App, red "Admin" badge, header, main content slot | ❌ Raw HTML | ⚠️ Needs refactor |

### Layout Switching (`App.vue`)

- ✅ Uses `<component :is="layoutComponent">` based on `route.meta.layout`
- ✅ Maps 'auth' → AuthLayout, 'app' → AppLayout, 'admin' → AdminLayout
- ✅ Falls back to AppLayout

**Section Status: ⚠️ Partial** — All 3 layouts exist with correct structure, but none use shadcn-vue components.

---

## 9. Chrome Extension

| File | Exists | Implementation | Status |
|------|--------|---------------|--------|
| `manifest.json` | ✅ | ✅ MV3, tabCapture + storage + offscreen permissions, correct icon references, service_worker background | ✅ |
| `background.js` | ✅ | ⚠️ **CRITICAL BUG**: `startRecording()` calls `chrome.tabCapture.getMediaStreamId()` but **never creates a MediaRecorder** — the streamId is obtained but no `getUserMedia()` or `new MediaRecorder()` is ever called. The `mediaRecorder` variable stays `null` | ⚠️ Critical |
| `popup.html` | ✅ | ✅ Clean UI with settings, recording states, upload progress, error handling | ✅ |
| `popup.js` | ✅ | ✅ State machine (not_connected → ready → recording → uploading → uploaded → error), ping validation, settings save | ✅ |
| `icons/` | ⚠️ | ❌ Only `.gitkeep` — **No actual icon images** (icon16.png, icon48.png, icon128.png referenced in manifest but not present) | ❌ Missing |

### Detailed `background.js` Issues

1. **Line 30-34** at [`background.js`](chrome-extension/background.js:28): `startRecording()` gets `streamId` from `chrome.tabCapture.getMediaStreamId()` but then does nothing with it. Should use the stream ID to create a MediaStream via `navigator.mediaDevices.getUserMedia()` (or an offscreen document), then create a `MediaRecorder` instance
2. **`stopRecording()`** at [`background.js`](chrome-extension/background.js:40): Tries to stop `mediaRecorder` which is always `null`
3. **`recordedChunks`** is populated by nothing — no `ondataavailable` handler was ever set
4. **`uploadRecording(blob)`** exists and works correctly but is never called because no blob is ever produced

**Section Status: ⚠️ Partial** — UI and communication layer is good, but the core recording functionality (MediaRecorder + blob capture) is incomplete. Icons are missing.

---

## 10. Configuration & DevOps

### `.env.example`

| Variable | Present | Correct | Status |
|----------|---------|---------|--------|
| DB_CONNECTION=pgsql | ✅ | ✅ | ✅ |
| OPENAI_API_KEY | ✅ | ✅ | ✅ |
| OPENAI_ORGANIZATION | ✅ | ✅ | ✅ |
| PADDLE_WEBHOOK_SECRET | ✅ | ✅ | ✅ |
| PADDLE_API_KEY | ✅ | ✅ | ✅ |
| PADDLE_SANDBOX | ✅ | ✅ | ✅ |
| AWS_* (S3) | ✅ | ✅ | ✅ |
| QUEUE_CONNECTION=sync | ✅ | ✅ No queues | ✅ |
| FILESYSTEM_DISK=local | ✅ | ✅ | ✅ |
| VITE_APP_NAME | ✅ | ✅ | ✅ |
| SUPERADMIN_EMAIL/PASSWORD | ❌ | — | ⚠️ Could be useful for seed command |

### `composer.json`

| Dependency | Required | Present | Status |
|-----------|----------|---------|--------|
| php ^8.3 | ✅ | ✅ | ✅ |
| laravel/framework ^12.0 | ✅ | ✅ | ✅ |
| laravel/sanctum ^4.3 | ✅ | ✅ | ✅ |
| openai-php/laravel ^0.19 | ✅ | ✅ | ✅ |
| league/flysystem-aws-s3-v3 ^3.32 | ✅ | ✅ | ✅ |

### `package.json`

| Dependency | Required | Present | Status |
|-----------|----------|---------|--------|
| vue ^3.5 | ✅ | ✅ | ✅ |
| vue-router ^4.6 | ✅ | ✅ | ✅ |
| pinia ^3.0 | ✅ | ✅ | ✅ |
| tailwindcss ^4.2 | ✅ | ✅ | ✅ |
| @tailwindcss/vite ^4.2 | ✅ | ✅ | ✅ |
| radix-vue ^1.9 | ✅ | ✅ (shadcn-vue dependency) | ✅ |
| class-variance-authority | ✅ | ✅ | ✅ |
| clsx | ✅ | ✅ | ✅ |
| tailwind-merge | ✅ | ✅ | ✅ |
| lucide-vue-next | ✅ | ✅ | ✅ |
| @vueuse/core | ✅ | ✅ | ✅ |
| typescript ^5.9 | ✅ | ✅ | ✅ |

### `vite.config.ts`

- ✅ Laravel Vite plugin configured with correct inputs
- ✅ Vue plugin with `transformAssetUrls`
- ✅ Tailwind CSS v4 plugin
- ✅ `@` alias for `resources/js`

### `components.json` (shadcn-vue)

- ✅ Style: "new-york" (matches spec)
- ✅ TypeScript enabled
- ✅ Framework: "vite"
- ✅ Component aliases configured to `@/components`
- ✅ Utils path: `@/lib/utils`

### `resources/css/app.css`

- ✅ Imports `tailwindcss`
- ✅ Defines CSS custom properties (`:root` theme variables)
- ✅ Uses `@theme inline` block for Tailwind v4 configuration
- ✅ Dark mode variables present (though app doesn't use dark mode)

### No Queue References

- ✅ `QUEUE_CONNECTION=sync` in `.env.example`
- ✅ No job classes in `app/Jobs/`
- ✅ All processing done via Artisan commands
- ✅ Spec requirement "no jobs, no queues" is **enforced** ✅

### `tsconfig.json`

- ✅ ESNext target/module
- ✅ Strict mode enabled
- ✅ Path alias `@/*` → `./resources/js/*`
- ✅ Includes `.ts` and `.vue` files

**Section Status: ✅ Complete** — All configuration files are correctly set up.

---

## 11. Testing

| Item | Exists | Status |
|------|--------|--------|
| `phpunit.xml` | ✅ Configured for SQLite :memory: testing | ✅ |
| `tests/TestCase.php` | ✅ Base test case | ✅ |
| `tests/Feature/ExampleTest.php` | ✅ Default Laravel example only | ❌ No real tests |
| `tests/Unit/ExampleTest.php` | ✅ Default Laravel example only | ❌ No real tests |
| `database/factories/UserFactory.php` | ⚠️ Missing `organization_id`, `role`, `is_superadmin` fields | ⚠️ Incomplete |
| RecordingFactory | ❌ Does not exist | ❌ Missing |
| OrganizationFactory | ❌ Does not exist | ❌ Missing |
| ApiKeyFactory | ❌ Does not exist | ❌ Missing |
| Feature tests for API endpoints | ❌ None exist | ❌ Missing |
| Unit tests for services | ❌ None exist | ❌ Missing |

### `UserFactory` Issues

At [`UserFactory.php`](database/factories/UserFactory.php:27), the `definition()` method returns:
- ✅ `name`, `email`, `password`, `remember_token`
- ❌ Missing `organization_id` (required, non-nullable FK)
- ❌ Missing `role` (has default 'member' in migration, but should be explicit)
- ❌ Missing `is_superadmin` (has default false in migration, but should be explicit)

**Section Status: ❌ Missing** — No real tests exist. UserFactory is incomplete. No other model factories exist.

---

## 12. Landing Page

| Feature | Status |
|---------|--------|
| `resources/views/landing.blade.php` exists | ✅ (14,410 bytes of content) |
| Served at `/` via `routes/web.php` | ✅ |
| SEO meta tags (OG, Twitter, JSON-LD) | ✅ |
| Navigation with links to /login, /register | ✅ |
| Hero section | ✅ |
| Features section | ✅ |
| Pricing section (3 plans) | ✅ |
| CTA section | ✅ |
| Uses `@vite(['resources/css/app.css'])` | ✅ |
| `resources/views/app.blade.php` (SPA shell) | ✅ Loads CSS + JS, has `<div id="app">` |
| `resources/views/welcome.blade.php` | ⚠️ Exists (72KB) — appears to be default Laravel welcome page, unused |

**Section Status: ✅ Complete** — Custom landing page exists with full marketing content and SEO.

---

## Prioritized Action Items

### P0 — Critical / Blocking

| # | Task | Files to Create/Modify | Complexity | Details |
|---|------|----------------------|------------|---------|
| 1 | **Refactor ALL frontend pages to use shadcn-vue components** | All 11 files in `resources/js/pages/`, 6 files in `resources/js/components/` | **L** | Replace raw `<button>` → `<Button>`, `<input>` → `<Input>`, `<label>` → `<Label>`, `<table>` → `<Table>`, manual modals → `<Dialog>`, manual tabs → `<Tabs>`, manual progress bars → `<Progress>`, inline badges → `<Badge>`, error alerts → `<Alert>`. This is the largest remaining work item. |
| 2 | **Refactor ALL frontend layouts to use shadcn-vue components** | `resources/js/layouts/AuthLayout.vue`, `AppLayout.vue`, `AdminLayout.vue` | **M** | Replace raw nav items with `<Button variant="ghost">`, use `<Separator>`, `<DropdownMenu>` for user menu, `<Card>` for auth card wrapper. |
| 3 | **Fix Chrome extension `background.js` — complete MediaRecorder implementation** | `chrome-extension/background.js` | **M** | After `getMediaStreamId()`, must: (1) open offscreen document or use `navigator.mediaDevices.getUserMedia()` with the stream ID, (2) create `new MediaRecorder(stream)`, (3) handle `ondataavailable` to populate `recordedChunks`, (4) on stop, create Blob and call `uploadRecording()`. |
| 4 | **Fix `UserResource` to include `is_superadmin` field** | `app/Http/Resources/UserResource.php` | **S** | Add `'is_superadmin' => $this->is_superadmin` to the `toArray()` response — the admin route guard in `router/index.ts` depends on this field. Without it, no user can access `/admin/dashboard`. |
| 5 | **Fix AdminController response wrappers** | `app/Http/Controllers/Api/V1/AdminController.php` | **S** | `failedRecordings()`, `commandRuns()`, and `organizations()` return raw paginators. Wrap with `RecordingResource::collection()`, proper Resources, or manual `{data: ...}` wrapper for consistency. |

### P1 — Important

| # | Task | Files to Create/Modify | Complexity | Details |
|---|------|----------------------|------------|---------|
| 6 | **Fix API key response field mismatch** | `app/Http/Controllers/Api/V1/ApiKeyController.php` OR `resources/js/lib/api.ts` | **S** | Backend returns `plaintext_key`, frontend expects `plain_text_key`. Align naming (prefer backend's `plaintext_key` and update frontend). |
| 7 | **Fix `UsageData` TypeScript type** | `resources/js/types/index.ts` | **S** | Update to match actual `billing/usage` API response shape which includes `subscription`, `plan`, `limits`, `remaining`, `current_period` fields in addition to basic counters. |
| 8 | **Fix `Plan` TypeScript type** | `resources/js/types/index.ts`, `resources/js/lib/api.ts` | **S** | `PlanResource` returns `limits_json` as the field name, but TS type uses `limits`. Either rename the Resource field to `limits` or update the TS type. |
| 9 | **Fix WorkflowsListPage variant filter value** | `resources/js/pages/WorkflowsListPage.vue` | **S** | Change variant option value from `"full"` to `"with_logging"` at line 74 to match the backend enum. |
| 10 | **Create Chrome extension icon assets** | `chrome-extension/icons/icon16.png`, `icon48.png`, `icon128.png` | **S** | Generate 3 PNG icons at 16×16, 48×48, and 128×128 pixels. Currently only `.gitkeep` exists. Extension will fail to load in Chrome without these. |
| 11 | **Update `UserFactory` with required fields** | `database/factories/UserFactory.php` | **S** | Add `organization_id` (via Organization factory or callback), `role` (default 'member'), `is_superadmin` (default false) to the factory definition. |
| 12 | **Add missing API client methods** | `resources/js/lib/api.ts` | **S** | Add `admin.commandRuns()` and `admin.organizations()` methods to match available backend endpoints. |
| 13 | **Refactor custom components to use shadcn-vue** | `resources/js/components/StatusBadge.vue`, `UploadDialog.vue`, `EmptyState.vue` | **M** | StatusBadge → use `<Badge>`, UploadDialog → use `<Dialog>` + `<Input>` + `<Button>`, EmptyState → use `<Card>` wrapper. |
| 14 | **Add route lazy loading** | `resources/js/router/index.ts` | **S** | Convert static imports to `() => import('../pages/XPage.vue')` for code splitting. |

### P2 — Nice-to-have

| # | Task | Files to Create/Modify | Complexity | Details |
|---|------|----------------------|------------|---------|
| 15 | **Create database factories** | `database/factories/OrganizationFactory.php`, `RecordingFactory.php`, `ApiKeyFactory.php`, `PlanFactory.php`, `WorkflowFactory.php` | **M** | Required for testing. Each factory should produce valid model instances with proper relationships. |
| 16 | **Write Feature tests for API endpoints** | `tests/Feature/Auth/`, `tests/Feature/Recording/`, `tests/Feature/Workflow/`, etc. | **L** | At minimum: auth (register/login/logout/me), CRUD for recordings, workflow generation, billing usage, admin endpoints. |
| 17 | **Write Unit tests for services** | `tests/Unit/UsageLimitServiceTest.php`, `tests/Unit/WorkflowGenerationServiceTest.php` | **M** | Test plan limit enforcement, workflow JSON structure, node mapping. |
| 18 | **Delete unused `welcome.blade.php`** | `resources/views/welcome.blade.php` | **S** | 72KB default Laravel welcome page is unused (landing.blade.php is served at `/`). |
| 19 | **Add `SUPERADMIN_EMAIL` / `SUPERADMIN_PASSWORD` to `.env.example`** | `.env.example` | **S** | The `admin:seed-superuser` command accepts these as options; documenting them in .env.example improves DX. |
| 20 | **Add dark mode support** | Multiple frontend files | **L** | CSS variables for dark mode are defined in `app.css` but no toggle exists and pages use hardcoded `text-gray-*` / `bg-white` classes. |
| 21 | **Improve error handling in frontend pages** | All page files | **M** | Many `catch` blocks are empty (`catch { // handle error }`). Should show user-facing error messages using `<Alert>` component. |
| 22 | **Add `PlanResource` field name consistency** | `app/Http/Resources/PlanResource.php` | **S** | Consider renaming `limits_json` to `limits` in the resource to match frontend expectations and improve API ergonomics. |

---

## Summary of Key Findings

1. **Backend is ~90% complete** — All models, migrations, controllers, services, commands, middleware are implemented with real logic. Minor issues: 3 admin endpoints missing `{data}` wrapper, `UserResource` missing `is_superadmin`.

2. **Frontend is functionally ~60% complete** — All pages exist with correct data fetching and business logic, but the **entire UI layer needs refactoring** from raw HTML to shadcn-vue components. This is the single largest remaining task.

3. **Chrome extension is ~50% complete** — UI layer (popup) is well-built, but the core recording mechanism (MediaRecorder) is incomplete. Missing icon assets.

4. **Testing is ~5% complete** — Only default Laravel example tests exist. No model factories (beyond incomplete UserFactory), no feature tests, no unit tests.

5. **Configuration is 100% complete** — All config files, environment variables, dependencies, and build tooling are correctly set up.

6. **No queue violations** — The "no jobs, no queues" requirement is properly enforced throughout.
