<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LogAccess
{
    /**
     * Middleware de Log de Tráfego e Acesso Geral (Heimdall & Loja).
     * Mapeia requisições registrando IP, rota, user agent e tempo de resposta.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $durationMs = round((microtime(true) - $startTime) * 1000);

        // Ignora rotas de assets estáticos e do próprio debug do Vite para não poluir o banco
        if ($request->is('_debugbar*') || $request->is('sanctum/*') || str_contains($request->getRequestUri(), 'vite') || str_contains($request->getRequestUri(), '.js') || str_contains($request->getRequestUri(), '.css')) {
            return $response;
        }

        try {
            $userType = 'anonimo';
            $userId = null;

            if (Auth::guard('admin')->check()) {
                $userType = 'funcionario';
                $userId = Auth::guard('admin')->id();
            } elseif (Auth::guard('web')->check()) {
                $userType = 'cliente';
                $userId = Auth::guard('web')->id();
            }

            DB::table('logs_acesso')->insert([
                'ip' => $request->ip() ?? '127.0.0.1',
                'user_agent' => $request->userAgent(),
                'metodo' => $request->method(),
                'rota' => substr($request->getRequestUri(), 0, 500),
                'status_http' => $response->getStatusCode(),
                'usuario_tipo' => $userType,
                'usuario_id' => $userId,
                'duracao_ms' => $durationMs,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Failsafe silencioso para não quebrar a navegação caso o banco sqlite/mysql falhe momentaneamente
        }

        return $response;
    }
}
