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
        $tipo = $request->input('tipo'); // brute_force, rate_limit, xss_tentativa, sql_tentativa

        $logsAcesso = DB::table('logs_acesso')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        $logsSeguranca = DB::table('logs_seguranca')
            ->when($search, function ($query, $search) {
                $query->where('ip', 'like', "%{$search}%")
                      ->orWhere('detalhe', 'like', "%{$search}%");
            })
            ->when($tipo, function ($query, $tipo) {
                $query->where('tipo', $tipo);
            })
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $ipsBloqueados = DB::table('ips_bloqueados')->get();

        // Auditoria granular de alteração de dados (quem alterou o quê)
        $auditLogs = DB::table('logs_auditoria')
            ->join('funcionarios', 'logs_auditoria.funcionario_id', '=', 'funcionarios.id')
            ->select('logs_auditoria.*', 'funcionarios.nome as funcionario_nome')
            ->orderBy('logs_auditoria.id', 'desc')
            ->limit(20)
            ->get();

        // Alertas de comportamento suspeito na última hora
        $alertasSuspicious = [
            'brute_force_counts' => DB::table('logs_seguranca')
                ->where('tipo', 'brute_force')
                ->where('created_at', '>=', now()->subHour())
                ->count(),
            'high_traffic_ips' => DB::table('logs_acesso')
                ->select('ip', DB::raw('count(*) as total'))
                ->where('created_at', '>=', now()->subHour())
                ->groupBy('ip')
                ->having('total', '>', 100)
                ->get(),
        ];

        $perfis = DB::table('perfis_permissao')->orderBy('nome')->get();
        $permissoesMap = DB::table('permissoes_modulo')->get();

        return Inertia::render('Security/Index', [
            'logsSeguranca' => $logsSeguranca,
            'logsAcesso' => $logsAcesso,
            'ipsBloqueados' => $ipsBloqueados,
            'auditLogs' => $auditLogs,
            'alerts' => $alertasSuspicious,
            'filters' => $request->only('search', 'tipo'),
            'perfis' => $perfis,
            'permissoesMap' => $permissoesMap,
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
}
