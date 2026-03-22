<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\LimitExceededException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRecordingRequest;
use App\Http\Resources\RecordingResource;
use App\Models\Recording;
use App\Models\UsageCounter;
use App\Services\UsageLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RecordingController extends Controller
{
    public function __construct(
        protected UsageLimitService $usageLimitService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Recording::where('organization_id', $request->user()->organization_id);

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        $recordings = $query->latest()->paginate(15);

        return RecordingResource::collection($recordings);
    }

    public function store(StoreRecordingRequest $request): JsonResponse
    {
        $user = $request->user();

        try {
            $this->usageLimitService->canUploadRecording($user->organization);
        } catch (LimitExceededException $e) {
            return response()->json(['error' => $e->toArray()], 402);
        }

        $file = $request->file('file');
        $recordingId = (string) Str::uuid();
        $ext = $file->getClientOriginalExtension();
        $storagePath = "recordings/{$user->organization_id}/{$recordingId}/original.{$ext}";

        Storage::put($storagePath, file_get_contents($file));

        $recording = Recording::create([
            'id' => $recordingId,
            'organization_id' => $user->organization_id,
            'user_id' => $user->id,
            'title' => $request->input('title', $file->getClientOriginalName()),
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size_bytes' => $file->getSize(),
            'status' => 'uploaded',
            'storage_path' => $storagePath,
        ]);

        $counter = UsageCounter::forCurrentPeriod($user->organization_id);
        $counter->increment('recordings_count');
        $counter->increment('storage_bytes', $file->getSize());

        return response()->json([
            'data' => new RecordingResource($recording),
        ], 201);
    }

    public function storeFromExtension(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:mp4,webm,mov,avi', 'max:512000'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $organization = $request->get('organization');

        try {
            $this->usageLimitService->canUploadRecording($organization);
        } catch (LimitExceededException $e) {
            return response()->json(['error' => $e->toArray()], 402);
        }

        $file = $request->file('file');
        $recordingId = (string) Str::uuid();
        $ext = $file->getClientOriginalExtension();
        $storagePath = "recordings/{$organization->id}/{$recordingId}/original.{$ext}";

        Storage::put($storagePath, file_get_contents($file));

        $recording = Recording::create([
            'id' => $recordingId,
            'organization_id' => $organization->id,
            'title' => $request->input('title', $file->getClientOriginalName()),
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size_bytes' => $file->getSize(),
            'status' => 'uploaded',
            'storage_path' => $storagePath,
        ]);

        $counter = UsageCounter::forCurrentPeriod($organization->id);
        $counter->increment('recordings_count');
        $counter->increment('storage_bytes', $file->getSize());

        return response()->json([
            'data' => new RecordingResource($recording),
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $recording = Recording::where('organization_id', $request->user()->organization_id)
            ->with(['assets', 'intent', 'workflows'])
            ->findOrFail($id);

        return response()->json([
            'data' => new RecordingResource($recording),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $recording = Recording::where('organization_id', $request->user()->organization_id)
            ->findOrFail($id);

        $recording->delete();

        return response()->json(null, 204);
    }
}
