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
        // Se a rota for o próprio instalador, verifica se já está instalado para bloquear
        $isInstallRoute = $request->is('install') || $request->is('install/*');

        try {
            // Verifica se a tabela 'instalacao' existe e se possui registro de concluída
            if (Schema::hasTable('instalacao')) {
                $instalacao = DB::table('instalacao')->first();
                $isInstalled = $instalacao && $instalacao->concluida;
            } else {
                $isInstalled = false;
            }
        } catch (\Exception $e) {
            $isInstalled = false;
        }

        if ($isInstalled) {
            if ($isInstallRoute) {
                return redirect()->route('admin.login')->with('error', 'O sistema já está instalado.');
            }
            return $next($request);
        }

        // Se não estiver instalado e não estiver na rota de instalação, redireciona
        if (!$isInstallRoute) {
            return redirect('/install');
        }

        return $next($request);
    }
}
