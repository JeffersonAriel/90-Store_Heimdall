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

        $transactions = LancamentoFinanceiro::query()
            ->with(['conta', 'pedido', 'fornecedor', 'funcionarioCriador'])
            ->when($tipo, function ($query, $tipo) {
                $query->where('tipo', $tipo);
            })
            ->when($categoria, function ($query, $categoria) {
                $query->where('categoria', $categoria);
            })
            ->orderBy('data_lancamento', 'desc')
            ->paginate(15)
            ->withQueryString();

        $accounts = ContaBancaria::where('ativa', true)->get();

        return Inertia::render('Financial/Index', [
            'transactions' => $transactions,
            'accounts' => $accounts,
            'filters' => $request->only('tipo', 'categoria')
        ]);
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
