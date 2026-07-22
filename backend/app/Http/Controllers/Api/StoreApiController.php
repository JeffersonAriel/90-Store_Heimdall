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
            ->orderBy('esgotado', 'asc')
            ->when($request->input('categoria'), function ($query, $slug) {
                $category = CategoriaTipoProduto::where('slug', $slug)->first();
                if ($category) {
                    $categoryIds = $this->getAllCategoryDescendants($category->id);
                    $query->whereIn('categoria_id', $categoryIds);
                }
            })
            ->when($request->input('marca'), function ($query, $marca) {
                $query->where('marca', 'like', $marca);
            })
            ->when($request->input('ano'), function ($query, $ano) {
                $query->where('retro_year', $ano);
            })
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nome', 'like', "%{$search}%")
                      ->orWhere('descricao', 'like', "%{$search}%")
                      ->orWhere('marca', 'like', "%{$search}%");
                });
            })
            ->when($request->input('min_price'), function ($query, $minPrice) {
                $query->where(function ($q) use ($minPrice) {
                    $q->where('preco_venda', '>=', $minPrice)
                      ->orWhere('preco_desconto', '>=', $minPrice);
                });
            })
            ->when($request->input('max_price'), function ($query, $maxPrice) {
                $query->where(function ($q) use ($maxPrice) {
                    $q->where(function ($sub) use ($maxPrice) {
                        $sub->where('tem_desconto', true)->where('preco_desconto', '<=', $maxPrice);
                    })->orWhere(function ($sub) use ($maxPrice) {
                        $sub->where('tem_desconto', false)->where('preco_venda', '<=', $maxPrice);
                    });
                });
            })
            ->when($request->input('sort'), function ($query, $sort) {
                if ($sort === 'lowest_price') {
                    $query->orderByRaw('CASE WHEN tem_desconto = 1 THEN preco_desconto ELSE preco_venda END ASC');
                } elseif ($sort === 'highest_price') {
                    $query->orderByRaw('CASE WHEN tem_desconto = 1 THEN preco_desconto ELSE preco_venda END DESC');
                } elseif ($sort === 'newest') {
                    $query->orderBy('id', 'desc');
                } elseif ($sort === 'highlights' || $sort === 'destaques') {
                    $query->where('is_destaque', true)->orderBy('ordem_destaque', 'asc');
                } else {
                    $query->orderBy('is_destaque', 'desc')->orderBy('id', 'desc');
                }
            }, function ($query) {
                $query->orderBy('is_destaque', 'desc')->orderBy('id', 'desc');
            })
            ->when($request->input('limit'), function ($query, $limit) {
                $query->limit((int)$limit);
            })
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

        // Filtra as marcas de acordo com a categoria e busca atuais para só exibir marcas com produtos
        $brandsQuery = Produto::where('ativo', true)
            ->when($request->input('categoria'), function ($query, $slug) {
                $category = CategoriaTipoProduto::where('slug', $slug)->first();
                if ($category) {
                    $categoryIds = $this->getAllCategoryDescendants($category->id);
                    $query->whereIn('categoria_id', $categoryIds);
                }
            })
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nome', 'like', "%{$search}%")
                      ->orWhere('descricao', 'like', "%{$search}%")
                      ->orWhere('marca', 'like', "%{$search}%");
                });
            });

        $brands = $brandsQuery->whereNotNull('marca')
            ->where('marca', '<>', '')
            ->distinct()
            ->pluck('marca')
            ->map(function ($marca) {
                return trim($marca);
            })
            ->filter(function ($marca) {
                return !empty($marca);
            })
            ->unique()
            ->values();

        return response()->json([
            'success' => true,
            'produtos' => $products,
            'categorias' => $categories,
            'marcas' => $brands
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
            'peso_total' => 'nullable|numeric',
        ]);

        $pesoTotal = floatval($request->input('peso_total', 0.3));
        if ($pesoTotal <= 0) {
            $pesoTotal = 0.3;
        }

        // Como cada produto possui peso mínimo de 0.3kg ou 1.0kg nas configurações passadas, 
        // podemos inferir uma quantidade de itens dividindo o peso_total por 0.3 (peso padrão do produto),
        // garantindo que no mínimo seja 1 item.
        $itensCount = max(1, round($pesoTotal / 0.3));

        $options = $this->freteService->calcular($request->cep, $pesoTotal, $itensCount);

        return response()->json([
            'success' => true,
            'opcoes' => $options
        ]);
    }

    /**
     * Registra solicitação de "Me Avise Quando Chegar" para um produto esgotado
     */
    public function registerNotificationRequest(Request $request, $id)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        $product = Produto::findOrFail($id);

        \Illuminate\Support\Facades\DB::table('solicitacoes_avise_me')->insert([
            'produto_id' => $product->id,
            'nome' => $request->nome,
            'email' => $request->email,
            'status' => 'pendente',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sua solicitação foi registrada com sucesso. Avisaremos você assim que o estoque for reposto!'
        ]);
    }

    /**
     * Retorna recursivamente todos os IDs de categorias descendentes
     */
    private function getAllCategoryDescendants($categoryId)
    {
        $ids = [$categoryId];
        $allCategories = CategoriaTipoProduto::all();
        
        $added = true;
        while ($added) {
            $added = false;
            foreach ($allCategories as $cat) {
                if (in_array($cat->parent_id, $ids) && !in_array($cat->id, $ids)) {
                    $ids[] = $cat->id;
                    $added = true;
                }
            }
        }
        return $ids;
    }
}
