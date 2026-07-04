<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class BlockBannedIps
{
    /**
     * Módulo de Segurança do Heimdall.
     * Intercepta requisições e bloqueia IPs cadastrados na lista de banimento de forma síncrona.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        try {
            $isBanned = DB::table('ips_bloqueados')
                ->where('ip', $ip)
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                })
                ->exists();

            if ($isBanned) {
                // Registra a tentativa bloqueada no log de segurança
                DB::table('logs_seguranca')->insert([
                    'ip' => $ip,
                    'tipo' => 'ip_banido',
                    'detalhe' => 'Tentativa de acesso de IP banido pelo administrador.',
                    'usuario_tipo' => 'anonimo',
                    'bloqueado' => true,
                    'rota' => $request->getRequestUri(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Seu endereço IP está bloqueado temporariamente ou permanentemente para acesso a este servidor por políticas de segurança.'
                ], 403);
            }
        } catch (\Exception $e) {
            // Failsafe
        }

        return $next($request);
    }
}
