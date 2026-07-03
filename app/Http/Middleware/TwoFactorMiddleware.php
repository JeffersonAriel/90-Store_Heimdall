<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->two_factor_secret && ! $request->session()->get('2fa_verified')) {
            return redirect()->route('erp.2fa');
        }

        return $next($request);
    }
}
