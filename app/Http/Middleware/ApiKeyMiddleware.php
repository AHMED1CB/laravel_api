<?php
namespace App\Http\Middleware;

use Closure;

class ApiKeyMiddleware
{
    public function handle($request, Closure $next)
    {
        $apiKey = $request->header('X-API-KEY');
        $validApiKey = env('API_KEY');

        if ($apiKey !== $validApiKey) {
            return response()->json(['error' => 'Api Key Missed'], 301);
        }

        return $next($request);
    }
}
