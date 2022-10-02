<?php

namespace App\Http\Middleware;

use App\Models\Affiliate\AffiliateDevice;
use App\Models\Affiliate\AffiliateToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->bearerToken()) {
            $token = AffiliateToken::whereApiToken($request->bearerToken())->first();
            if ($token) {
                $request->merge(['affiliate' => $token->affiliate]);
                return $next($request);
            }
        } else {
            return response()->json([
                'error' => true,
                'message' => 'Error de autenticación',
                'data' => []
            ], 401);
        }
        return response()->json([
            'error' => true,
            'message' => 'Error de autenticación',
            'data' => []
        ], 401);
    }
}
