<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LancamentoFinanceiro;
use App\Models\ContaBancaria;
use App\Services\FinancialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class FinancialController extends Controller
{
    protected $financialService;

    public function __construct(FinancialService $financialService)
    {
        $this->financialService = $financialService;
    }

    /**
     * Lista lançamentos com possibilidade de conciliação manual
     */
    public function index(Request $request)
    {
        $tipo = $request->input('tipo');
        $categoria = $request->input('categoria');
        $status = $request->input('status');

        $transactions = LancamentoFinanceiro::query()
            ->with(['conta', 'pedido', 'fornecedor', 'funcionarioCriador'])
            ->when($tipo, function ($query, $tipo) {
                $query->where('tipo', $tipo);
            })
            ->when($categoria, function ($query, $categoria) {
                $query->where('categoria', $categoria);
            })
            ->when($status, function ($query, $status) {
                if ($status === 'conciliado') {
                    $query->where('conciliado', true);
                } elseif ($status === 'pendente') {
                    $query->where('conciliado', false);
                }
            })
            ->orderBy('data_lancamento', 'desc')
            ->paginate(15)
            ->withQueryString();

        $accounts = ContaBancaria::where('ativa', true)->get();

        $lucroLiquido = LancamentoFinanceiro::where('conciliado', true)->where('tipo', 'entrada')->sum('valor')
            - LancamentoFinanceiro::where('conciliado', true)->where('tipo', 'saida')->sum('valor');
        
        $contasReceber = LancamentoFinanceiro::where('conciliado', false)->where('tipo', 'entrada')->sum('valor');
        
        $contasPagar = LancamentoFinanceiro::where('conciliado', false)->where('tipo', 'saida')->sum('valor');

        return Inertia::render('Financial/Index', [
            'transactions' => $transactions,
            'accounts' => $accounts,
            'filters' => $request->only('tipo', 'categoria', 'status'),
            'metrics' => [
                'lucro_liquido' => floatval($lucroLiquido),
                'contas_receber' => floatval($contasReceber),
                'contas_pagar' => floatval($contasPagar),
            ]
        ]);
    }

    /**
     * Cadastra um novo lançamento financeiro manual
     */
    public function store(Request $request)
    {
        $request->validate([
            'tipo' => 'required|in:entrada,saida',
            'categoria' => 'required|string|max:100',
            'descricao' => 'required|string|max:255',
            'valor' => 'required|numeric|min:0.01',
            'data_lancamento' => 'required|date',
            'conta_id' => 'nullable|exists:contas_bancarias,id',
            'conciliado' => 'boolean',
            'recorrente' => 'boolean',
            'recorrencias' => 'nullable|integer|min:2|max:36',
            'frequencia' => 'nullable|in:mensal,semanal',
        ]);

        $isConciliado = $request->boolean('conciliado');
        $isRecorrente = $request->boolean('recorrente');
        $recorrencias = $request->input('recorrencias', 1);
        $frequencia = $request->input('frequencia', 'mensal');

        try {
            if ($isRecorrente && $recorrencias > 1) {
                $baseDate = \Carbon\Carbon::parse($request->data_lancamento);
                DB::transaction(function () use ($request, $baseDate, $recorrencias, $frequencia, $isConciliado) {
                    for ($i = 0; $i < $recorrencias; $i++) {
                        $occurrenceDate = $baseDate->copy();
                        if ($frequencia === 'semanal') {
                            $occurrenceDate->addWeeks($i);
                        } else {
                            $occurrenceDate->addMonths($i);
                        }

                        $numeroParcela = $i + 1;
                        $descFinal = $request->descricao . " ({$numeroParcela}/{$recorrencias})";

                        // Somente a primeira parcela pode ser considerada conciliada se o checkbox foi marcado,
                        // as parcelas futuras nascem pendentes de pagamento.
                        $conciliadoParcela = ($i === 0) ? $isConciliado : false;

                        LancamentoFinanceiro::create([
                            'tipo' => $request->tipo,
                            'categoria' => $request->categoria,
                            'descricao' => $descFinal,
                            'valor' => $request->valor,
                            'data_lancamento' => $occurrenceDate->toDateString(),
                            'data_competencia' => $occurrenceDate->toDateString(),
                            'conta_id' => $conciliadoParcela ? $request->conta_id : null,
                            'conciliado' => $conciliadoParcela,
                            'conciliado_por' => $conciliadoParcela ? Auth::guard('admin')->id() : null,
                            'conciliado_em' => $conciliadoParcela ? now() : null,
                            'created_by' => Auth::guard('admin')->id(),
                        ]);
                    }
                });
            } else {
                LancamentoFinanceiro::create([
                    'tipo' => $request->tipo,
                    'categoria' => $request->categoria,
                    'descricao' => $request->descricao,
                    'valor' => $request->valor,
                    'data_lancamento' => $request->data_lancamento,
                    'data_competencia' => $request->data_lancamento,
                    'conta_id' => $request->conta_id,
                    'conciliado' => $isConciliado,
                    'conciliado_por' => $isConciliado ? Auth::guard('admin')->id() : null,
                    'conciliado_em' => $isConciliado ? now() : null,
                    'created_by' => Auth::guard('admin')->id(),
                ]);
            }

            return back()->with('success', 'Lançamento financeiro cadastrado com sucesso!');
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao salvar lançamento: ' . $e->getMessage());
        }
    }

    /**
     * Conciliação manual de lançamentos
     */
    public function reconcile(Request $request, int $id)
    {
        $success = $this->financialService->reconcile($id, Auth::guard('admin')->id());

        if ($success) {
            return back()->with('success', 'Lançamento financeiro conciliado com sucesso!');
        }

        return back()->with('error', 'Não foi possível conciliar o lançamento.');
    }

    /**
     * Relatórios e exportação para BI
     */
    public function reports(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        $report = $this->financialService->getProfitReport($startDate, $endDate);

        return Inertia::render('Financial/Reports', [
            'report' => $report,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * Endpoint leve pronto para carga ETL no Pentaho ou consumo no Power BI (JSON de exportação)
     */
    public function biExport(Request $request)
    {
        // View-like query que une vendas, custos e lucros
        $data = DB::table('itens_pedido as ip')
            ->join('pedidos as p', 'ip.pedido_id', '=', 'p.id')
            ->join('produtos as prod', 'ip.produto_id', '=', 'prod.id')
            ->join('fornecedores as f', 'prod.fornecedor_id', '=', 'f.id')
            ->select(
                'p.id as pedido_id',
                'p.created_at as data_pedido',
                'prod.nome as produto_nome',
                'ip.sku_snapshot as sku',
                'ip.tipo_estoque_snapshot as tipo_estoque',
                'f.razao_social as fornecedor_nome',
                'ip.quantidade',
                'ip.preco_custo_snapshot as custo_unitario',
                'ip.preco_venda_snapshot as venda_unitario',
                DB::raw('(ip.preco_venda_snapshot - ip.preco_custo_snapshot) * ip.quantidade as lucro_total'),
                DB::raw('ip.preco_venda_snapshot * ip.quantidade as faturamento_total')
            )
            ->where('p.status', '!=', 'cancelado')
            ->orderBy('p.id', 'desc')
            ->get();

        return response()->json($data);
    }
}
