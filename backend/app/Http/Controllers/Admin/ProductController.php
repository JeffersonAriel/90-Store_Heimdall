<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use App\Models\Fornecedor;
use App\Models\CategoriaTipoProduto;
use App\Models\VariacaoProduto;
use App\Models\FotoProduto;
use App\Models\MovimentacaoEstoque;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $categoriaId = $request->input('categoria_id');
        $fornecedorId = $request->input('fornecedor_id');

        $products = Produto::query()
            ->when($search, function ($query, $search) {
                $query->where('nome', 'like', "%{$search}%")
                      ->orWhere('sku_base', 'like', "%{$search}%");
            })
            ->when($categoriaId, function ($query, $categoriaId) {
                $query->where('categoria_id', $categoriaId);
            })
            ->when($fornecedorId, function ($query, $fornecedorId) {
                $query->where('fornecedor_id', $fornecedorId);
            })
            ->with(['categoria', 'fornecedor', 'fotoCapa', 'variacoes'])
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        $categories = CategoriaTipoProduto::where('ativo', true)->get();
        $suppliers = Fornecedor::where('ativo', true)->get();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'suppliers' => $suppliers,
            'filters' => $request->only('search', 'categoria_id', 'fornecedor_id')
        ]);
    }

    public function create()
    {
        $categories = CategoriaTipoProduto::with('atributos.opcoes')->where('ativo', true)->get();
        $suppliers = Fornecedor::where('ativo', true)->get();

        return Inertia::render('Products/Form', [
            'categories' => $categories,
            'suppliers' => $suppliers
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fornecedor_id' => 'required|exists:fornecedores,id',
            'categoria_id' => 'required|exists:categorias_tipo_produto,id',
            'nome' => 'required|string|max:255',
            'sku_base' => 'required|string|max:100|unique:produtos,sku_base',
            'descricao' => 'nullable|string',
            'descricao_curta' => 'nullable|string',
            'preco_custo' => 'required|numeric|min:0',
            'preco_venda' => 'required|numeric|min:0',
            'tem_desconto' => 'boolean',
            'preco_desconto' => 'nullable|numeric|min:0',
            'ativo' => 'boolean',
            'is_destaque' => 'boolean',
            'peso_kg' => 'nullable|numeric|min:0',
            'dimensoes_json' => 'nullable|array',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            
            // Atributos customizados da categoria
            'atributos' => 'nullable|array',
            
            // Variações
            'variacoes' => 'required|array|min:1',
            'variacoes.*.sku' => 'required|string|max:100|unique:variacoes_produto,sku',
            'variacoes.*.tamanho' => 'nullable|string|max:30',
            'variacoes.*.cor' => 'nullable|string|max:60',
            'variacoes.*.preco_adicional' => 'required|numeric|min:0',
            'variacoes.*.tipo_estoque' => 'required|in:proprio,dropshipping',
            'variacoes.*.estoque_quantidade' => 'required_if:variacoes.*.tipo_estoque,proprio|integer|min:0',
            'variacoes.*.estoque_minimo' => 'nullable|integer|min:0',
            'variacoes.*.estoque_critico' => 'nullable|integer|min:0',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $slug = Str::slug($validated['nome']);
            $count = Produto::where('slug', 'like', "{$slug}%")->count();
            if ($count > 0) {
                $slug = "{$slug}-" . ($count + 1);
            }

            $product = Produto::create(array_merge($validated, ['slug' => $slug]));

            // Salva valores de atributos
            if ($request->filled('atributos')) {
                foreach ($request->input('atributos') as $atributoId => $valor) {
                    if ($valor !== null && $valor !== '') {
                        $isOpcao = is_numeric($valor) && DB::table('opcoes_atributo')->where('id', $valor)->exists();
                        $product->atributosValores()->create([
                            'atributo_id' => $atributoId,
                            'opcao_id' => $isOpcao ? $valor : null,
                            'valor_livre' => !$isOpcao ? $valor : null,
                        ]);
                    }
                }
            }

            // Salva variações e movimentação de estoque inicial
            foreach ($validated['variacoes'] as $varData) {
                $variation = $product->variacoes()->create($varData);

                if ($variation->tipo_estoque === 'proprio' && $variation->estoque_quantidade > 0) {
                    MovimentacaoEstoque::create([
                        'variacao_id' => $variation->id,
                        'quantidade' => $variation->estoque_quantidade,
                        'estoque_antes' => 0,
                        'estoque_depois' => $variation->estoque_quantidade,
                        'tipo' => 'entrada',
                        'motivo' => 'Carga inicial no cadastro do produto',
                    ]);
                }
            }

            // Processa upload de imagens
            if ($request->hasFile('imagens')) {
                foreach ($request->file('imagens') as $index => $file) {
                    $path = $file->store('products', 'public');
                    $product->fotos()->create([
                        'url' => '/storage/' . $path,
                        'ordem' => $index,
                        'is_capa' => $index === 0,
                    ]);
                }
            }
        });

        return redirect()->route('admin.products.index')->with('success', 'Produto cadastrado com sucesso!');
    }

    public function edit(Produto $product)
    {
        $product->load(['variacoes', 'fotos', 'atributosValores']);
        $categories = CategoriaTipoProduto::with('atributos.opcoes')->where('ativo', true)->get();
        $suppliers = Fornecedor::where('ativo', true)->get();

        return Inertia::render('Products/Form', [
            'product' => $product,
            'categories' => $categories,
            'suppliers' => $suppliers
        ]);
    }

    public function update(Request $request, Produto $product)
    {
        $validated = $request->validate([
            'fornecedor_id' => 'required|exists:fornecedores,id',
            'categoria_id' => 'required|exists:categorias_tipo_produto,id',
            'nome' => 'required|string|max:255',
            'sku_base' => "required|string|max:100|unique:produtos,sku_base,{$product->id}",
            'descricao' => 'nullable|string',
            'descricao_curta' => 'nullable|string',
            'preco_custo' => 'required|numeric|min:0',
            'preco_venda' => 'required|numeric|min:0',
            'tem_desconto' => 'boolean',
            'preco_desconto' => 'nullable|numeric|min:0',
            'ativo' => 'boolean',
            'is_destaque' => 'boolean',
            'peso_kg' => 'nullable|numeric|min:0',
            'dimensoes_json' => 'nullable|array',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            'atributos' => 'nullable|array',
        ]);

        DB::transaction(function () use ($product, $validated, $request) {
            $product->update($validated);

            // Atualiza atributos
            $product->atributosValores()->delete();
            if ($request->filled('atributos')) {
                foreach ($request->input('atributos') as $atributoId => $valor) {
                    if ($valor !== null && $valor !== '') {
                        $isOpcao = is_numeric($valor) && DB::table('opcoes_atributo')->where('id', $valor)->exists();
                        $product->atributosValores()->create([
                            'atributo_id' => $atributoId,
                            'opcao_id' => $isOpcao ? $valor : null,
                            'valor_livre' => !$isOpcao ? $valor : null,
                        ]);
                    }
                }
            }

            // Fotos gerenciadas separadamente via endpoints de mídia
        });

        return redirect()->route('admin.products.index')->with('success', 'Produto atualizado com sucesso!');
    }

    public function destroy(Produto $product)
    {
        $product->delete();
        return redirect()->route('admin.products.index')->with('success', 'Produto removido com sucesso!');
    }

    public function addVariation(Request $request, Produto $product)
    {
        $validated = $request->validate([
            'sku' => 'required|string|max:100|unique:variacoes_produto,sku',
            'tamanho' => 'nullable|string|max:30',
            'cor' => 'nullable|string|max:60',
            'preco_adicional' => 'required|numeric|min:0',
            'tipo_estoque' => 'required|in:proprio,dropshipping',
            'estoque_quantidade' => 'required_if:tipo_estoque,proprio|integer|min:0',
            'estoque_minimo' => 'nullable|integer|min:0',
            'estoque_critico' => 'nullable|integer|min:0',
        ]);

        DB::transaction(function () use ($product, $validated) {
            $variation = $product->variacoes()->create($validated);

            if ($variation->tipo_estoque === 'proprio' && $variation->estoque_quantidade > 0) {
                MovimentacaoEstoque::create([
                    'variacao_id' => $variation->id,
                    'quantidade' => $variation->estoque_quantidade,
                    'estoque_antes' => 0,
                    'estoque_depois' => $variation->estoque_quantidade,
                    'tipo' => 'entrada',
                    'motivo' => 'Carga inicial no cadastro de variação',
                ]);
            }
        });

        return back()->with('success', 'Variação adicionada com sucesso!');
    }
}
