<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyPaddleWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.paddle.webhook_secret');

        // Skip verification if secret is not configured (development)
        if (empty($secret)) {
            return $next($request);
        }

        $signature = $request->header('Paddle-Signature');

        if (! $signature) {
            return response()->json(['error' => 'Missing Paddle-Signature header'], 403);
        }

        // Parse ts= and h1= from Paddle-Signature header
        $parts = collect(explode(';', $signature))
            ->mapWithKeys(function (string $part) {
                [$key, $value] = explode('=', $part, 2);
                return [$key => $value];
            });

        $ts = $parts->get('ts');
        $h1 = $parts->get('h1');

        if (! $ts || ! $h1) {
            return response()->json(['error' => 'Invalid Paddle-Signature format'], 403);
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', "{$ts}:{$payload}", $secret);

        if (! hash_equals($expectedSignature, $h1)) {
            return response()->json(['error' => 'Invalid webhook signature'], 403);
        }

        return $next($request);
    }
}
