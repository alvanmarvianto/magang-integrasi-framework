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
        // Check if there's a token in the session
        $token = session('sanctum_token');
        
        if ($token) {
            // Find the token in the database
            $accessToken = PersonalAccessToken::findToken($token);
            
            if ($accessToken && $accessToken->tokenable) {
                // Set the user in the sanctum guard
                Auth::guard('sanctum')->setUser($accessToken->tokenable);
                
                // Set the token in the request header so Sanctum can authenticate
                $request->headers->set('Authorization', 'Bearer ' . $token);
            } elseif (!$accessToken) {
                // Token not found in database, clear session
                session()->forget('sanctum_token');
            }
        }

        return $next($request);
    }
}
