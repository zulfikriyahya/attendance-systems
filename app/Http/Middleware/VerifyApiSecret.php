<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedKey = config('services.api.secret');
        $providedKey = $request->header('X-API-KEY');

        if (! $providedKey || $providedKey !== $expectedKey) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized - Invalid API Key'], 401);
        }

        return $next($request);
    }
}
