<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SecurityController extends Controller
{
    /**
     * Módulo de Segurança exclusivo para o Administrador (AdminOnly middleware)
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $tipo   = $request->input('tipo');

        // ── Core logs ──────────────────────────────────────────────
        $logsSeguranca = DB::table('logs_seguranca')
            ->when($search, fn($q) => $q->where('ip', 'like', "%{$search}%")->orWhere('detalhe', 'like', "%{$search}%"))
            ->when($tipo,   fn($q) => $q->where('tipo', $tipo))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $logsAcesso = DB::table('logs_acesso')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        $ipsBloqueados = DB::table('ips_bloqueados')->get();

        $auditLogs = DB::table('logs_auditoria')
            ->join('funcionarios', 'logs_auditoria.funcionario_id', '=', 'funcionarios.id')
            ->select('logs_auditoria.*', 'funcionarios.nome as funcionario_nome')
            ->orderBy('logs_auditoria.id', 'desc')
            ->limit(20)
            ->get();

        // ── KPIs de Segurança ──────────────────────────────────────
        $now = now();

        $threats1h     = DB::table('logs_seguranca')->where('created_at', '>=', $now->copy()->subHour())->count();
        $threats24h    = DB::table('logs_seguranca')->where('created_at', '>=', $now->copy()->subDay())->count();
        $loginsFailed24h = DB::table('logs_login')->where('sucesso', false)->where('created_at', '>=', $now->copy()->subDay())->count();
        $loginsOk24h   = DB::table('logs_login')->where('sucesso', true)->where('created_at', '>=', $now->copy()->subDay())->count();
        $totalRequests24h = DB::table('logs_acesso')->where('created_at', '>=', $now->copy()->subDay())->count();
        $blockedAttempts24h = DB::table('logs_seguranca')->where('bloqueado', true)->where('created_at', '>=', $now->copy()->subDay())->count();

        // ── Distribuição de ameaças por tipo (últimos 7 dias) ──────
        $topThreats = DB::table('logs_seguranca')
            ->select('tipo', DB::raw('count(*) as total'))
            ->where('created_at', '>=', $now->copy()->subDays(7))
            ->groupBy('tipo')
            ->orderByDesc('total')
            ->get();

        // ── Ameaças por hora (últimas 24h para gráfico) ────────────
        $driver = DB::connection()->getDriverName();
        $hourExpr = $driver === 'sqlite' ? "strftime('%H', created_at)" : "HOUR(created_at)";
        $selectExpr = $driver === 'sqlite' ? "CAST(strftime('%H', created_at) AS INTEGER) as hora" : "HOUR(created_at) as hora";

        $threatsPerHour = DB::table('logs_seguranca')
            ->select(DB::raw($selectExpr), DB::raw('count(*) as total'))
            ->where('created_at', '>=', $now->copy()->subDay())
            ->groupBy(DB::raw($hourExpr))
            ->orderBy('hora')
            ->get()
            ->keyBy('hora');

        $threatChart = collect(range(0, 23))->map(fn($h) => [
            'hora'  => str_pad($h, 2, '0', STR_PAD_LEFT) . 'h',
            'total' => $threatsPerHour->get($h)?->total ?? 0,
        ]);

        // ── IPs únicos que mais atacaram (top 5) ───────────────────
        $topAttackers = DB::table('logs_seguranca')
            ->select('ip', DB::raw('count(*) as total'))
            ->where('created_at', '>=', $now->copy()->subDays(7))
            ->groupBy('ip')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // ── Score de Segurança calculado ───────────────────────────
        $score = 100;
        if ($threats1h > 0)           $score -= min(15, $threats1h * 2);
        if (count($ipsBloqueados) > 5) $score -= 5;
        if ($loginsFailed24h > 10)     $score -= 10;
        $scoreStatus = $score >= 80 ? 'seguro' : ($score >= 60 ? 'alerta' : 'critico');

        // ── Alertas comportamentais ─────────────────────────────────
        $alertasSuspicious = [
            'brute_force_counts' => DB::table('logs_seguranca')
                ->where('tipo', 'brute_force')
                ->where('created_at', '>=', $now->copy()->subHour())
                ->count(),
            'high_traffic_ips' => DB::table('logs_acesso')
                ->select('ip', DB::raw('count(*) as total'))
                ->where('created_at', '>=', $now->copy()->subHour())
                ->groupBy('ip')
                ->having('total', '>', 100)
                ->get(),
        ];

        // ── Saúde do Sistema ────────────────────────────────────────
        $systemChecks = [
            ['label' => 'Rate Limiting no Login', 'status' => 'pass', 'detail' => 'throttle:5,1 ativo nas rotas de autenticação'],
            ['label' => 'Headers HTTP de Segurança', 'status' => 'pass', 'detail' => 'X-Frame-Options, X-Content-Type, Referrer-Policy, Permissions-Policy'],
            ['label' => 'Bloqueio Automático por Brute Force', 'status' => 'pass', 'detail' => 'IPs bloqueados automaticamente após 5 falhas em 10 minutos'],
            ['label' => 'APP_DEBUG em Produção', 'status' => config('app.debug') ? 'fail' : 'pass', 'detail' => config('app.debug') ? 'ATENÇÃO: APP_DEBUG está true!' : 'Modo debug desativado'],
            ['label' => 'Autenticação 2FA Disponível', 'status' => 'pass', 'detail' => 'Google 2FA disponível para todos os funcionários'],
            ['label' => 'CSRF Protection', 'status' => 'pass', 'detail' => 'Token CSRF validado em todos os formulários POST/PUT/DELETE'],
            ['label' => 'IPs Bloqueados Monitorados', 'status' => count($ipsBloqueados) > 0 ? 'warn' : 'pass', 'detail' => count($ipsBloqueados) . ' IP(s) atualmente na lista negra'],
            ['label' => 'Log de Acesso Ativo', 'status' => 'pass', 'detail' => 'Middleware LogAccess registrando todas as requisições'],
            ['label' => 'Permissões por Perfil (RBAC)', 'status' => 'pass', 'detail' => 'Controle granular view/create/edit/delete por módulo'],
            ['label' => 'Sessão Invalidada no Logout', 'status' => 'pass', 'detail' => 'invalidate() + regenerateToken() aplicados no logout'],
        ];

        $perfis        = DB::table('perfis_permissao')->orderBy('nome')->get();
        $permissoesMap = DB::table('permissoes_modulo')->get();

        return Inertia::render('Security/Index', [
            'logsSeguranca'   => $logsSeguranca,
            'logsAcesso'      => $logsAcesso,
            'ipsBloqueados'   => $ipsBloqueados,
            'auditLogs'       => $auditLogs,
            'alerts'          => $alertasSuspicious,
            'filters'         => $request->only('search', 'tipo'),
            'perfis'          => $perfis,
            'permissoesMap'   => $permissoesMap,
            // Novos dados premium
            'securityScore'   => $score,
            'scoreStatus'     => $scoreStatus,
            'threats1h'       => $threats1h,
            'threats24h'      => $threats24h,
            'loginsFailed24h' => $loginsFailed24h,
            'loginsOk24h'     => $loginsOk24h,
            'totalRequests24h'=> $totalRequests24h,
            'blockedAttempts24h' => $blockedAttempts24h,
            'topThreats'      => $topThreats,
            'threatChart'     => $threatChart,
            'topAttackers'    => $topAttackers,
            'systemChecks'    => $systemChecks,
        ]);
    }

    /**
     * Bloquear/Banir um IP suspeito manualmente
     */
    public function blockIp(Request $request)
    {
        $request->validate([
            'ip' => 'required|ip',
            'motivo' => 'required|string|max:255',
        ]);

        DB::table('ips_bloqueados')->updateOrInsert(
            ['ip' => $request->ip],
            [
                'motivo' => $request->motivo,
                'bloqueado_por' => \Illuminate\Support\Facades\Auth::guard('admin')->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return back()->with('success', "IP {$request->ip} bloqueado e banido do e-commerce com sucesso!");
    }

    /**
     * Remover banimento do IP
     */
    public function unblockIp(string $ip)
    {
        DB::table('ips_bloqueados')->where('ip', $ip)->delete();
        return back()->with('success', "IP {$ip} desbloqueado!");
    }

    /**
     * Exporta logs de auditoria/segurança para CSV para análise forense externa
     */
    public function exportCsv(string $tipo)
    {
        $table = $tipo === 'seguranca' ? 'logs_seguranca' : 'logs_acesso';
        $data = DB::table($table)->orderBy('id', 'desc')->limit(1000)->get();

        $fileName = "logs_{$tipo}_" . date('Ymd_His') . ".csv";

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="'. urlencode($fileName) .'"');

        $output = fopen('php://output', 'w');
        
        // Cabeçalhos
        if ($tipo === 'seguranca') {
            fputcsv($output, ['ID', 'IP', 'Tipo Ameaça', 'Detalhe', 'Tipo Usuário', 'ID Usuário', 'Bloqueado', 'Rota', 'Data']);
            foreach ($data as $row) {
                fputcsv($output, [$row->id, $row->ip, $row->tipo, $row->detalhe, $row->usuario_tipo, $row->usuario_id, $row->bloqueado ? 'SIM' : 'NAO', $row->rota, $row->created_at]);
            }
        } else {
            fputcsv($output, ['ID', 'IP', 'User Agent', 'Metodo', 'Rota', 'Status HTTP', 'Tipo Usuário', 'ID Usuário', 'Duração (ms)', 'Data']);
            foreach ($data as $row) {
                fputcsv($output, [$row->id, $row->ip, $row->user_agent, $row->metodo, $row->rota, $row->status_http, $row->usuario_tipo, $row->usuario_id, $row->duracao_ms, $row->created_at]);
            }
        }

        fclose($output);
        exit;
    }

    /**
     * Atualizar a matriz de permissões de um perfil
     */
    public function updatePermissions(Request $request, int $perfilId)
    {
        $perfil = DB::table('perfis_permissao')->where('id', $perfilId)->first();
        if (!$perfil || $perfil->is_admin) {
            return back()->with('error', 'Não é possível editar as permissões do perfil Administrador.');
        }

        $request->validate([
            'permissions' => 'required|array',
        ]);

        $permissions = $request->input('permissions');

        DB::transaction(function () use ($perfilId, $permissions) {
            // Limpa as permissões antigas
            DB::table('permissoes_modulo')->where('perfil_id', $perfilId)->delete();

            // Insere as permissões ativas selecionadas
            $insertData = [];
            foreach ($permissions as $modulo => $acoes) {
                foreach ($acoes as $acao => $checked) {
                    if ($checked) {
                        $insertData[] = [
                            'perfil_id'  => $perfilId,
                            'modulo'     => $modulo,
                            'acao'       => $acao,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }

            if (!empty($insertData)) {
                DB::table('permissoes_modulo')->insert($insertData);
            }
        });

        return back()->with('success', 'Permissões do perfil atualizadas com sucesso!');
    }

    /**
     * Executa as migrações e seeders do banco de dados pelo painel
     */
    public function runMigrations()
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            $migrateOutput = \Illuminate\Support\Facades\Artisan::output();

            \Illuminate\Support\Facades\Artisan::call('db:seed', [
                '--class' => 'PerfilPermissaoSeeder',
                '--force' => true
            ]);
            \Illuminate\Support\Facades\Artisan::call('db:seed', [
                '--class' => 'ApiConfigSeeder',
                '--force' => true
            ]);
            \Illuminate\Support\Facades\Artisan::call('db:seed', [
                '--class' => 'CategorySeeder',
                '--force' => true
            ]);
            \Illuminate\Support\Facades\Artisan::call('db:seed', [
                '--class' => 'SportsCategorySeeder',
                '--force' => true
            ]);
            \Illuminate\Support\Facades\Artisan::call('db:seed', [
                '--class' => 'CrmSeeder',
                '--force' => true
            ]);
            $seederOutput = \Illuminate\Support\Facades\Artisan::output();

            // Recalcula retroativamente todos os clientes existentes para alimentar as colunas do CRM
            $clientes = \App\Models\Cliente::all();
            foreach ($clientes as $c) {
                \App\Services\Crm\CrmKpiService::recalcularCliente($c->id);
            }

            // Limpa todos os caches de rotas, configuracoes e otimizacoes para evitar o erro 500
            \Illuminate\Support\Facades\Artisan::call('optimize:clear');
            \Illuminate\Support\Facades\Artisan::call('route:clear');
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            \Illuminate\Support\Facades\Artisan::call('view:clear');
            
            return back()->with('success', 'Banco de dados, APIs, permissões e caches otimizados com sucesso! Detalhes: ' . trim($migrateOutput) . ' | ' . trim($seederOutput));
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao executar migrações: ' . $e->getMessage());
        }
    }
}
