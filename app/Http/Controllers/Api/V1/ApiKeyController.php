<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApiKeyRequest;
use App\Http\Resources\ApiKeyResource;
use App\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class ApiKeyController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $apiKeys = ApiKey::where('organization_id', $request->user()->organization_id)->get();

        return ApiKeyResource::collection($apiKeys);
    }

    public function store(StoreApiKeyRequest $request): JsonResponse
    {
        $plainKey = 'fc_' . Str::random(45);

        $apiKey = ApiKey::create([
            'organization_id' => $request->user()->organization_id,
            'user_id' => $request->user()->id,
            'label' => $request->label,
            'key_hash' => hash('sha256', $plainKey),
            'key_prefix' => substr($plainKey, 0, 8),
        ]);

        return response()->json([
            'data' => [
                'api_key' => new ApiKeyResource($apiKey),
                'plaintext_key' => $plainKey,
                'note' => 'Store this key securely. It will not be shown again.',
            ],
        ], 201);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $apiKey = ApiKey::where('organization_id', $request->user()->organization_id)
            ->whereNull('revoked_at')
            ->findOrFail($id);

        $apiKey->update(['revoked_at' => now()]);

        return response()->json(null, 204);
    }
}
