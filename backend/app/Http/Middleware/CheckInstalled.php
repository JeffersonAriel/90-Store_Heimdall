<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class CheckInstalled
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Se for uma requisição para a API ou console debug, ignora a validação de instalador
        if ($request->is('api/*') || $request->is('_debugbar*')) {
            return $next($request);
        }

        $isInstallRoute = $request->is('install') || $request->is('install/*');

        try {
            // Verifica se a tabela 'instalacao' existe
            $isInstalled = Schema::hasTable('instalacao') && DB::table('instalacao')->where('concluida', true)->exists();
        } catch (\Exception $e) {
            $isInstalled = false;
        }

        if ($isInstalled) {
            if ($isInstallRoute) {
                return redirect()->route('admin.login')->with('error', 'O sistema já está instalado.');
            }
            return $next($request);
        }

        // Se não estiver instalado e não estiver na rota de instalação, redireciona de forma segura
        if (!$isInstallRoute) {
            return redirect('/install');
        }

        return $next($request);
    }
}
