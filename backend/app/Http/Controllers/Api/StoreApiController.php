<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use App\Models\CategoriaTipoProduto;
use App\Services\CepService;
use App\Services\FreteService;
use Illuminate\Http\Request;

class StoreApiController extends Controller
{
    protected $cepService;
    protected $freteService;

    public function __construct(CepService $cepService, FreteService $freteService)
    {
        $this->cepService = $cepService;
        $this->freteService = $freteService;
    }

    /**
     * Endpoint público para buscar catálogo da vitrine (Preços, descontos, fotos e categorias)
     */
    public function getCatalog(Request $request)
    {
        $products = Produto::where('ativo', true)
            ->with(['categoria', 'fotoCapa', 'fotos', 'fornecedor', 'variacoes' => function ($q) {
                $q->where('ativo', true);
            }])
            ->when($request->input('categoria'), function ($query, $slug) {
                $query->whereHas('categoria', function ($q) use ($slug) {
                    $q->where('slug', $slug);
                });
            })
            ->orderBy('is_destaque', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        // Limpa custos confidenciais da resposta JSON da loja
        $products->each(function ($p) {
            unset($p->preco_custo);
            $p->variacoes->each(function ($v) {
                unset($v->estoque_quantidade); // Mostra apenas se tem estoque (disponivel)
                $v->makeHidden(['estoque_quantidade', 'estoque_minimo', 'estoque_critico']);
            });
        });

        $categories = CategoriaTipoProduto::where('ativo', true)->orderBy('ordem')->get();

        return response()->json([
            'success' => true,
            'produtos' => $products,
            'categorias' => $categories
        ]);
    }

    /**
     * Endpoint público para ver um produto em detalhes
     */
    public function getProductDetail(string $slug)
    {
        $product = Produto::where('slug', $slug)
            ->where('ativo', true)
            ->with(['categoria', 'fotos', 'fornecedor', 'variacoes' => function ($q) {
                $q->where('ativo', true);
            }, 'atributosValores.atributo'])
            ->firstOrFail();

        // Oculta custos
        unset($product->preco_custo);
        $product->variacoes->makeHidden(['estoque_quantidade', 'estoque_minimo', 'estoque_critico']);

        return response()->json([
            'success' => true,
            'produto' => $product
        ]);
    }

    /**
     * Endpoint público de busca de CEP com redundância reativa (ViaCEP -> BrasilAPI -> ApiCEP)
     */
    public function lookupCep(string $cep)
    {
        $res = $this->cepService->buscar($cep);

        if ($res['success']) {
            return response()->json($res);
        }

        return response()->json($res, 400);
    }

    /**
     * Endpoint público para cotar fretes do carrinho
     */
    public function quoteShipping(Request $request)
    {
        $request->validate([
            'cep' => 'required|string',
            'peso_total' => 'required|numeric|min:0.1',
        ]);

        $options = $this->freteService->calcular($request->cep, $request->peso_total);

        return response()->json([
            'success' => true,
            'opcoes' => $options
        ]);
    }
}
