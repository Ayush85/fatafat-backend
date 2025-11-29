<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\ApiKey;
use Illuminate\Http\Request;

class ApiKeyAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Check if the API key is present in the request header
        $apiKey = $request->header('API-Key');

        if (!$apiKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'API key missing',
            ], 400);
        }

        // For local development, allow default API keys from environment
        if (app()->environment('local')) {
            $defaultTestKey = env('DEFAULT_API_TEST_KEY', 'test-key-123');
            $defaultLiveKey = env('DEFAULT_API_LIVE_KEY', 'live-key-456');

            if ($apiKey === $defaultTestKey || $apiKey === $defaultLiveKey) {
                // Create a mock API key record for local development
                $mockApiKey = (object) [
                    'id' => 1,
                    'name' => 'Default Local API Key',
                    'test_public_key' => $defaultTestKey,
                    'live_public_key' => $defaultLiveKey,
                    'is_active' => 1,
                    'mode' => $apiKey === $defaultTestKey ? 'test' : 'live'
                ];
                $request->attributes->add(['api_key' => $mockApiKey]);
                return $next($request);
            }
        }

        // Check if the API key exists in the database (check both test and live keys)
        $apiKeyRecord = ApiKey::where(function ($query) use ($apiKey) {
            $query->where('test_public_key', $apiKey)
                  ->orWhere('live_public_key', $apiKey);
        })->where('is_active', 1)->first();

        if (!$apiKeyRecord) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid API key',
            ], 403);
        }

        // Optionally, you can store the API key's associated name or user in the request
        // to make it accessible within your controller
        $request->attributes->add(['api_key' => $apiKeyRecord]);

        return $next($request);
    }
}