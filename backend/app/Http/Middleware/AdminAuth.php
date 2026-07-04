<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Se o usuário não estiver autenticado no guard admin, redireciona para o login do admin
        if (!Auth::guard('admin')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Não autenticado.'], 401);
            }
            return redirect()->route('admin.login');
        }

        // Verifica se o funcionário está ativo
        $employee = Auth::guard('admin')->user();
        if (!$employee->ativo) {
            Auth::guard('admin')->logout();
            return redirect()->route('admin.login')->with('error', 'Esta conta de funcionário está inativa.');
        }

        return $next($request);
    }
}
