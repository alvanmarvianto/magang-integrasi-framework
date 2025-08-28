<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class SanctumSessionAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = session('sanctum_token');
        
        if ($token) {
            $accessToken = PersonalAccessToken::findToken($token);
            
            if ($accessToken && $accessToken->tokenable) {
                Auth::guard('sanctum')->setUser($accessToken->tokenable);
                
                $request->headers->set('Authorization', 'Bearer ' . $token);
            } elseif (!$accessToken) {
                session()->forget('sanctum_token');
            }
        }

        return $next($request);
    }
}
