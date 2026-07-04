<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $module, string $action = 'view'): Response
    {
        $employee = Auth::guard('admin')->user();

        if (!$employee) {
            return $request->expectsJson() 
                ? response()->json(['message' => 'Não autenticado.'], 401)
                : redirect()->route('admin.login');
        }

        // Administradores com is_admin = true têm acesso irrestrito a todos os módulos e ações
        if ($employee->perfil && $employee->perfil->is_admin) {
            return $next($request);
        }

        // Verifica a permissão específica na tabela permissoes_modulo do perfil
        $hasPermission = $employee->perfil && $employee->perfil->permissions()
            ->where('modulo', $module)
            ->where('acao', $action)
            ->exists();

        if (!$hasPermission) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Você não possui permissão para acessar este recurso.'], 403);
            }
            return redirect()->route('admin.dashboard')->with('error', 'Acesso negado: você não tem permissão para esta ação.');
        }

        return $next($request);
    }
}
