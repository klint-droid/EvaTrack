<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateExternalApiKey
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $providedKey = $request->header('X-API-KEY');
        $validKey = env('EXTERNAL_SYSTEM_API_KEY');

        // Check if API key is present and matches using timing-attack safe comparison
        if (!$providedKey || !hash_equals((string) $validKey, (string) $providedKey)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized: Invalid or missing X-API-KEY header.',
            ], 401);
        }

        return $next($request);
    }
}
