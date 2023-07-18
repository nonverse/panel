<?php

namespace Pterodactyl\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->cookie('user_session')) {
            Auth::logout();
        }

        if ($request->user()) {
            $jwt = (array)JWT::decode($request->cookie('user_session'), new Key(config('auth.public_key'), 'RS256'));
            if ($jwt['sub'] !== $request->user()->uuid) {
                Auth::logout();
            }
        }

        return $next($request);
    }
}
