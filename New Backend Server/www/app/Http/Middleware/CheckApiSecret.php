<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApiSecret
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Define your secret key here or in .env
        $serverSecret = env('API_SECRET', 'library_secret_key_123');

        // Check for 'X-API-SECRET' header
        $clientSecret = $request->header('X-API-SECRET');

        if ($clientSecret !== $serverSecret) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Invalid API Secret'
            ], 401);
        }

        return $next($request);
    }
}
