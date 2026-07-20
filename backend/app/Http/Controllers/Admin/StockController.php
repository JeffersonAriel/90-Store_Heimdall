<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VariacaoProduto;
use App\Models\MovimentacaoEstoque;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class StockController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Lista o estoque atual com filtros e alertas de estoque mínimo e crítico
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $alerta = $request->input('alerta'); // min, critico

        $stock = VariacaoProduto::query()
            ->where('tipo_estoque', 'proprio')
            ->join('produtos', 'variacoes_produto.produto_id', '=', 'produtos.id')
            ->whereNull('produtos.deleted_at')
            ->select('variacoes_produto.*', 'produtos.nome as produto_nome', 'produtos.estoque_critico as estoque_critico')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('produtos.nome', 'like', "%{$search}%")
                       ->orWhere('variacoes_produto.sku', 'like', "%{$search}%");
                });
            })
            ->when($alerta === 'critico', function ($query) {
                $query->whereRaw('variacoes_produto.estoque_quantidade <= produtos.estoque_critico');
            })
            ->when($alerta === 'min', function ($query) {
                $query->whereRaw('variacoes_produto.estoque_quantidade <= variacoes_produto.estoque_minimo')
                      ->whereRaw('variacoes_produto.estoque_quantidade > produtos.estoque_critico');
            })
            ->orderBy('variacoes_produto.id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $countTotal = VariacaoProduto::where('tipo_estoque', 'proprio')->count();

        $countCritico = VariacaoProduto::where('tipo_estoque', 'proprio')
            ->join('produtos', 'variacoes_produto.produto_id', '=', 'produtos.id')
            ->whereNull('produtos.deleted_at')
            ->whereRaw('variacoes_produto.estoque_quantidade <= produtos.estoque_critico')
            ->count();

        $countMinimo = VariacaoProduto::where('tipo_estoque', 'proprio')
            ->join('produtos', 'variacoes_produto.produto_id', '=', 'produtos.id')
            ->whereNull('produtos.deleted_at')
            ->whereRaw('variacoes_produto.estoque_quantidade <= variacoes_produto.estoque_minimo')
            ->whereRaw('variacoes_produto.estoque_quantidade > produtos.estoque_critico')
            ->count();

        return Inertia::render('Stock/Index', [
            'stock' => $stock,
            'filters' => $request->only('search', 'alerta'),
            'counts' => [
                'total' => $countTotal,
                'min' => $countMinimo,
                'critico' => $countCritico
            ]
        ]);
    }

    /**
     * Salva o ajuste manual de estoque com justificativa obrigatória
     */
    public function adjust(Request $request, int $id)
    {
        $request->validate([
            'estoque_quantidade' => 'required|integer|min:0',
            'motivo' => 'required|string|max:255',
        ]);

        try {
            $this->stockService->manualAdjustment(
                $id,
                $request->estoque_quantidade,
                $request->motivo,
                Auth::guard('admin')->id()
            );

            return back()->with('success', 'Ajuste manual de estoque executado e logado com sucesso!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Retorna o histórico completo de movimentações de estoque para auditoria
     */
    public function history(int $id)
    {
        $history = MovimentacaoEstoque::with(['funcionario', 'pedido'])
            ->where('variacao_id', $id)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($history);
    }
}
