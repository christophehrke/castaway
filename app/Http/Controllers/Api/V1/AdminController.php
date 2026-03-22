<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CommandRun;
use App\Models\Organization;
use App\Models\PipelineError;
use App\Models\Recording;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function metrics(): JsonResponse
    {
        $recordingsByStatus = Recording::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $allStatuses = [
            'uploaded' => 0,
            'processing_media' => 0,
            'media_ready' => 0,
            'processing_ai' => 0,
            'intent_ready' => 0,
            'generating_workflows' => 0,
            'workflows_ready' => 0,
            'failed' => 0,
        ];

        $recordingsByStatus = array_merge($allStatuses, $recordingsByStatus);

        $pipelineErrorsByStage = PipelineError::query()
            ->whereNull('resolved_at')
            ->selectRaw('stage, count(*) as count')
            ->groupBy('stage')
            ->pluck('count', 'stage')
            ->toArray();

        $recentCommandRuns = CommandRun::query()
            ->orderByDesc('started_at')
            ->limit(10)
            ->get(['id', 'command', 'status', 'records_processed', 'records_failed', 'started_at', 'completed_at']);

        return response()->json([
            'data' => [
                'users_count' => User::count(),
                'organizations_count' => Organization::count(),
                'recordings_count' => Recording::count(),
                'workflows_count' => Workflow::count(),
                'failed_recordings_count' => Recording::where('status', 'failed')->count(),
                'active_subscriptions_count' => Subscription::whereIn('status', ['active', 'trialing'])->count(),
                'recordings_by_status' => $recordingsByStatus,
                'pipeline_errors_by_stage' => $pipelineErrorsByStage,
                'recent_command_runs' => $recentCommandRuns,
            ],
        ]);
    }

    public function failedRecordings(): JsonResponse
    {
        $recordings = Recording::where('status', 'failed')
            ->with(['organization:id,name', 'pipelineErrors'])
            ->orderByDesc('created_at')
            ->paginate(20, ['id', 'title', 'organization_id', 'status', 'created_at']);

        return response()->json($recordings);
    }

    public function reprocess(string $id): JsonResponse
    {
        $recording = Recording::findOrFail($id);

        $recording->update(['status' => 'uploaded']);

        $recording->pipelineErrors()
            ->whereNull('resolved_at')
            ->update(['resolved_at' => now()]);

        return response()->json([
            'data' => $recording->fresh(),
        ]);
    }

    public function commandRuns(Request $request): JsonResponse
    {
        $query = CommandRun::query()->orderByDesc('started_at');

        if ($request->has('command')) {
            $query->where('command', $request->query('command'));
        }

        return response()->json($query->paginate(20));
    }

    public function organizations(): JsonResponse
    {
        $organizations = Organization::query()
            ->withCount(['users', 'recordings'])
            ->with(['subscriptions' => function ($q) {
                $q->whereIn('status', ['active', 'trialing'])->with('plan:id,name')->latest()->limit(1);
            }])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($organizations);
    }
}
