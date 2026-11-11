<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Class AttachCookieTokenToHeader
 *
 * Checks if incoming request has an HttpOnly 'access_token' cookie.
 * If no Bearer Authorization header is explicitly set (e.g. from web SPA browsers),
 * it dynamically injects the token as a Bearer header for Laravel Sanctum authentication.
 *
 * This provides full XSS protection (tokens cannot be read via JavaScript localStorage)
 * while preserving 100% interoperability with native mobile apps (Bearer headers).
 */
class AttachCookieTokenToHeader
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->bearerToken()) {
            $token = $request->cookie('access_token') ?: $request->cookie('token');
            if ($token) {
                $request->headers->set('Authorization', 'Bearer ' . $token);
            }
        }

        return $next($request);
    }
}

