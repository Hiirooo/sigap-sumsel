<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyAppBhpToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedToken = (string) config('services.app_bhp.token');
        $providedToken = (string) $request->header('X-SIGAP-TOKEN');

        if ($expectedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
            return response()->json(['message' => 'Token API tidak valid.'], 401);
        }

        return $next($request);
    }
}
