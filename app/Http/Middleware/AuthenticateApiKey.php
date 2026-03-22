<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-Api-Key');

        if (!$key) {
            return response()->json(['message' => 'API key required.'], 401);
        }

        $hash = hash('sha256', $key);

        $apiKey = ApiKey::where('key_hash', $hash)
            ->whereNull('revoked_at')
            ->first();

        if (!$apiKey) {
            return response()->json(['message' => 'Invalid API key.'], 401);
        }

        $apiKey->update(['last_used_at' => now()]);

        $request->merge(['organization' => $apiKey->organization]);

        return $next($request);
    }
}
