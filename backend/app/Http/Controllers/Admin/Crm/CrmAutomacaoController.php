<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\CrmAutomacao;
use App\Models\Crm\CrmSegmento;
use App\Services\Crm\CrmAutomacaoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CrmAutomacaoController extends Controller
{
    public function index()
    {
        $automacoes = CrmAutomacao::with(['criadoPor:id,nome'])
            ->withCount('logs')
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Crm/Automacoes/Index', [
            'automacoes' => $automacoes,
            'gatilhos'   => self::getGatilhos(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome'        => 'required|string|max:191',
            'descricao'   => 'nullable|string',
            'gatilho'     => 'required|string',
            'delay_dias'  => 'nullable|integer|min:0',
            'delay_horas' => 'nullable|integer|min:0',
            'condicoes'   => 'nullable|array',
            'acoes'       => 'required|array',
            'ativa'       => 'boolean',
        ]);

        $data['criado_por'] = Auth::guard('admin')->id();

        CrmAutomacao::create($data);

        return back()->with('success', 'Automação criada!');
    }

    public function update(Request $request, CrmAutomacao $automacao)
    {
        $data = $request->validate([
            'nome'        => 'sometimes|required|string|max:191',
            'descricao'   => 'nullable|string',
            'gatilho'     => 'nullable|string',
            'delay_dias'  => 'nullable|integer|min:0',
            'delay_horas' => 'nullable|integer|min:0',
            'condicoes'   => 'nullable|array',
            'acoes'       => 'nullable|array',
            'ativa'       => 'boolean',
        ]);

        $automacao->update($data);

        return back()->with('success', 'Automação atualizada com sucesso!');
    }

    public function destroy(CrmAutomacao $automacao)
    {
        $automacao->delete();
        return back()->with('success', 'Automação removida com sucesso!');
    }

    public function executar(CrmAutomacao $automacao)
    {
        $count = CrmAutomacaoService::executarParaAutomacao($automacao);
        return back()->with('success', "Automação '{$automacao->nome}' executada para {$count} cliente(s)!");
    }

    private static function getGatilhos(): array
    {
        return [
            'apos_entrega'     => 'Após entrega do pedido',
            'dias_sem_compra'  => 'X dias sem compra',
            'aniversario'      => 'Aniversário do cliente',
            'etapa_pipeline'   => 'Ao entrar em etapa do pipeline',
            'pagamento_aprovado'=> 'Após pagamento aprovado',
            'carrinho_abandonado'=> 'Carrinho abandonado',
            'primeira_compra'  => 'Após primeira compra',
            'cliente_inativo'  => 'Cliente sem contato há X dias',
            'nps_baixo'        => 'NPS respondido <= 6',
        ];
    }
}


class CrmSegmentoController extends Controller
{
    public function index()
    {
        $segmentos = CrmSegmento::withCount('clientes')->orderBy('nome')->get();

        return Inertia::render('Crm/Segmentos/Index', [
            'segmentos' => $segmentos,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome'      => 'required|string|max:191',
            'descricao' => 'nullable|string',
            'cor'       => 'nullable|string|max:20',
            'icone'     => 'nullable|string|max:50',
            'tipo'      => 'required|in:automatico,manual',
            'regras'    => 'nullable|array',
        ]);

        CrmSegmento::create($data);

        return back()->with('success', 'Segmento criado!');
    }

    public function update(Request $request, CrmSegmento $segmento)
    {
        $segmento->update($request->validate([
            'nome'      => 'sometimes|required|string|max:191',
            'descricao' => 'nullable|string',
            'cor'       => 'nullable|string|max:20',
            'regras'    => 'nullable|array',
            'ativo'     => 'boolean',
        ]));

        return back()->with('success', 'Segmento atualizado!');
    }

    public function destroy(CrmSegmento $segmento)
    {
        $segmento->delete();
        return back()->with('success', 'Segmento removido.');
    }

    /**
     * Recalcula manualmente os membros de um segmento automático.
     */
    public function recalcular(CrmSegmento $segmento)
    {
        if ($segmento->tipo !== 'automatico') {
            return back()->with('error', 'Apenas segmentos automáticos podem ser recalculados.');
        }

        $regras   = $segmento->regras ?? [];
        $clienteIds = self::aplicarRegras($regras);

        DB::transaction(function () use ($segmento, $clienteIds) {
            DB::table('crm_segmento_clientes')->where('segmento_id', $segmento->id)->delete();

            $inserts = array_map(fn($id) => [
                'segmento_id'  => $segmento->id,
                'cliente_id'   => $id,
                'adicionado_em'=> now(),
            ], $clienteIds);

            if (!empty($inserts)) {
                DB::table('crm_segmento_clientes')->insert($inserts);
            }

            $segmento->update([
                'total_clientes' => count($clienteIds),
                'atualizado_em'  => now(),
            ]);
        });

        return back()->with('success', "Segmento recalculado: {$segmento->total_clientes} clientes.");
    }

    private static function aplicarRegras(array $regras): array
    {
        $query = DB::table('clientes')->whereNull('deleted_at')->where('ativo', true);

        foreach ($regras as $regra) {
            $campo    = $regra['campo']    ?? null;
            $operador = $regra['operador'] ?? '=';
            $valor    = $regra['valor']    ?? null;

            if (!$campo) continue;

            // Campos especiais com datas relativas
            if ($valor === '30_days_ago') {
                $valor = now()->subDays(30)->toDateTimeString();
            } elseif ($valor === '60_days_ago') {
                $valor = now()->subDays(60)->toDateTimeString();
            } elseif ($valor === '90_days_ago') {
                $valor = now()->subDays(90)->toDateTimeString();
            }

            $query->where($campo, $operador, $valor);
        }

        return $query->pluck('id')->toArray();
    }
}
