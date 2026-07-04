<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $employee = Auth::guard('admin')->user();

        if (!$employee || !$employee->perfil || !$employee->perfil->is_admin) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Acesso restrito a administradores do sistema.'], 403);
            }
            return redirect()->route('admin.dashboard')->with('error', 'Acesso negado: esta seção é restrita a administradores.');
        }

        return $next($request);
    }
}
