<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\LimitExceededException;
use App\Http\Controllers\Controller;
use App\Http\Resources\WorkflowResource;
use App\Models\Recording;
use App\Models\UsageCounter;
use App\Models\Workflow;
use App\Services\UsageLimitService;
use App\Services\WorkflowGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkflowController extends Controller
{
    public function __construct(
        protected WorkflowGenerationService $workflowGenerationService,
        protected UsageLimitService $usageLimitService,
    ) {}

    /**
     * POST /api/v1/recordings/{recording}/workflows/generate
     * Generate workflows from a recording's intent.
     */
    public function generate(Request $request, string $recording): JsonResponse
    {
        $recording = Recording::where('organization_id', $request->user()->organization_id)
            ->findOrFail($recording);

        if ($recording->status !== 'intent_ready') {
            return response()->json([
                'message' => 'Recording must be in intent_ready status to generate workflows.',
            ], 422);
        }

        $intent = $recording->intent;

        if (!$intent) {
            return response()->json([
                'message' => 'No intent found for this recording.',
            ], 404);
        }

        try {
            $this->usageLimitService->canGenerateWorkflow($recording->organization);
        } catch (LimitExceededException $e) {
            return response()->json(['error' => $e->toArray()], 402);
        }

        $this->workflowGenerationService->generate($recording, $intent);

        // Increment usage counter
        $counter = UsageCounter::forCurrentPeriod($recording->organization_id);
        $counter->increment('conversions_count');

        $workflows = Workflow::where('recording_id', $recording->id)
            ->where('ai_intent_id', $intent->id)
            ->latest()
            ->get();

        return response()->json([
            'data' => WorkflowResource::collection($workflows),
        ], 201);
    }

    /**
     * GET /api/v1/workflows
     * List all workflows for user's organization.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Workflow::where('organization_id', $request->user()->organization_id);

        if ($request->has('engine')) {
            $query->where('engine', $request->query('engine'));
        }

        if ($request->has('variant')) {
            $query->where('variant', $request->query('variant'));
        }

        if ($request->has('recording_id')) {
            $query->where('recording_id', $request->query('recording_id'));
        }

        $workflows = $query->latest()->paginate(15);

        return WorkflowResource::collection($workflows);
    }

    /**
     * GET /api/v1/workflows/{id}
     * Show single workflow with full workflow_json.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $workflow = Workflow::where('organization_id', $request->user()->organization_id)
            ->findOrFail($id);

        return response()->json([
            'data' => new WorkflowResource($workflow),
        ]);
    }
}
