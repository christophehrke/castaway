<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use App\Models\PaddleWebhookEvent;
use App\Models\Plan;
use App\Services\UsageLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BillingController extends Controller
{
    public function __construct(
        protected UsageLimitService $usageLimitService,
    ) {}

    /**
     * GET /api/v1/billing/plans — public, no auth
     */
    public function plans(): AnonymousResourceCollection
    {
        $plans = Plan::active()->orderBy('sort_order')->get();

        return PlanResource::collection($plans);
    }

    /**
     * POST /api/v1/billing/checkout — auth:sanctum
     */
    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'plan_code' => ['required', 'exists:plans,code'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
        ]);

        $plan = Plan::where('code', $request->input('plan_code'))->firstOrFail();
        $user = $request->user();

        // Skeleton Paddle checkout — placeholder URL until real credentials
        $params = http_build_query([
            'product_id' => $plan->paddle_product_id,
            'plan' => $plan->code,
            'cycle' => $request->input('billing_cycle'),
            'customer_email' => $user->email,
            'organization_id' => $user->organization_id,
        ]);

        $baseUrl = config('services.paddle.sandbox')
            ? 'https://sandbox-checkout.paddle.com'
            : 'https://checkout.paddle.com';

        return response()->json([
            'data' => [
                'checkout_url' => "{$baseUrl}/checkout?{$params}",
            ],
        ]);
    }

    /**
     * GET /api/v1/billing/portal — auth:sanctum
     */
    public function portal(Request $request): JsonResponse
    {
        $user = $request->user();

        $subscription = $user->organization
            ? $user->organization->subscriptions()
                ->whereIn('status', ['active', 'trialing'])
                ->latest()
                ->first()
            : null;

        $paddleCustomerId = $subscription?->paddle_customer_id ?? 'unknown';

        $baseUrl = config('services.paddle.sandbox')
            ? 'https://sandbox-customer.paddle.com'
            : 'https://customer.paddle.com';

        return response()->json([
            'data' => [
                'portal_url' => "{$baseUrl}/portal?customer_id={$paddleCustomerId}",
            ],
        ]);
    }

    /**
     * POST /api/v1/billing/webhook — no auth, uses Paddle signature verification
     */
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->all();
        $paddleEventId = $payload['event_id'] ?? $payload['p_event_id'] ?? null;
        $eventType = $payload['event_type'] ?? $payload['alert_name'] ?? 'unknown';

        // Idempotent — skip if paddle_event_id already exists
        if ($paddleEventId && PaddleWebhookEvent::where('paddle_event_id', $paddleEventId)->exists()) {
            return response()->json(['status' => 'already_processed']);
        }

        PaddleWebhookEvent::create([
            'event_type' => $eventType,
            'paddle_event_id' => $paddleEventId,
            'payload' => $payload,
            'created_at' => now(),
        ]);

        return response()->json(['status' => 'received']);
    }

    /**
     * GET /api/v1/billing/usage — auth:sanctum
     */
    public function usage(Request $request): JsonResponse
    {
        $user = $request->user();
        $organization = $user->organization;

        if (! $organization) {
            return response()->json(['error' => 'No organization found'], 404);
        }

        $subscription = $organization->subscriptions()
            ->whereIn('status', ['active', 'trialing'])
            ->with('plan')
            ->latest()
            ->first();

        $subscriptionData = $subscription ? [
            'plan_code' => $subscription->plan?->code,
            'status' => $subscription->status,
            'current_period_end' => $subscription->current_period_end?->toIso8601String(),
        ] : null;

        return response()->json([
            'data' => [
                'subscription' => $subscriptionData,
                'usage' => $this->usageLimitService->getCurrentUsage($organization),
                'limits' => $this->usageLimitService->getPlanLimits($organization),
                'remaining' => $this->usageLimitService->getRemainingQuota($organization),
            ],
        ]);
    }
}
