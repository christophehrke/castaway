<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AiIntentResource;
use App\Models\Recording;
use App\Services\AiProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntentController extends Controller
{
    public function __construct(
        protected AiProcessingService $aiProcessingService,
    ) {}

    /**
     * GET /api/v1/recordings/{recording}/intent
     * Returns the latest AI intent for a recording.
     */
    public function show(Request $request, string $recording): JsonResponse
    {
        $recording = Recording::where('organization_id', $request->user()->organization_id)
            ->findOrFail($recording);

        $intent = $recording->intent;

        if (!$intent) {
            return response()->json([
                'message' => 'No intent found for this recording.',
            ], 404);
        }

        return response()->json([
            'data' => new AiIntentResource($intent),
        ]);
    }

    /**
     * POST /api/v1/recordings/{recording}/intent/regenerate
     * Triggers re-processing of AI intent.
     */
    public function regenerate(Request $request, string $recording): JsonResponse
    {
        $recording = Recording::where('organization_id', $request->user()->organization_id)
            ->findOrFail($recording);

        $allowedStatuses = ['media_ready', 'intent_ready', 'generating_workflows', 'workflows_ready'];

        if (!in_array($recording->status, $allowedStatuses)) {
            return response()->json([
                'message' => 'Recording must be in media_ready or later status to regenerate intent.',
            ], 422);
        }

        $this->aiProcessingService->process($recording);

        return response()->json([
            'message' => 'Intent regeneration started.',
        ], 202);
    }
}
