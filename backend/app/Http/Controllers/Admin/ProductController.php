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
        $insumos = \App\Models\Insumo::with('categoria')->orderBy('nome')->get();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'suppliers' => $suppliers,
            'insumos' => $insumos,
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
            'genero' => 'required|in:Masculino,Feminino,Unissex,Infantil',
            'marca' => 'nullable|string|max:100',
            'sku_base' => 'required|string|max:100|unique:produtos,sku_base',
            'descricao' => 'nullable|string',
            'descricao_curta' => 'nullable|string',
            'preco_custo' => 'required|numeric|min:0',
            'preco_venda' => 'required|numeric|min:0',
            'tem_desconto' => 'boolean',
            'preco_desconto' => 'nullable|numeric|min:0',
            'ativo' => 'boolean',
            'esgotado' => 'boolean',
            'is_destaque' => 'boolean',
            'is_retro' => 'boolean',
            'retro_year' => 'nullable|integer',
            'estoque_critico' => 'nullable|integer|min:0',
            'peso_kg' => 'nullable|numeric|min:0',
            'dimensoes_json' => 'nullable|array',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            'permite_personalizacao' => 'boolean',
            
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

            // Processa upload de imagens por cor
            if ($request->hasFile('fotos_por_cor')) {
                foreach ($request->file('fotos_por_cor') as $cor => $files) {
                    foreach ($files as $index => $file) {
                        $path = $file->store('products', 'public');
                        $product->fotos()->create([
                            'url' => '/storage/' . $path,
                            'ordem' => $index,
                            'cor' => $cor,
                            'is_capa' => ($product->fotos()->count() === 0),
                        ]);
                    }
                }
            }

            // Processa URLs de imagens por cor (suporta nova linha e vírgula como separador)
            if ($request->filled('fotos_url_por_cor')) {
                $hasCover = $product->fotos()->where('is_capa', true)->exists();
                foreach ($request->input('fotos_url_por_cor') as $cor => $urlText) {
                    if (empty($urlText)) continue;
                    $rawUrls = preg_split('/[\n,]+/', str_replace("\r", "", $urlText));
                    foreach ($rawUrls as $index => $url) {
                        $url = trim($url);
                        if (empty($url)) continue;
                        $isCapa = !$hasCover;
                        if ($isCapa) $hasCover = true;
                        $product->fotos()->create([
                            'url' => $url,
                            'ordem' => $index + 100,
                            'cor' => ($cor === 'Geral') ? null : $cor,
                            'is_capa' => $isCapa,
                        ]);
                    }
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
            'genero' => 'required|in:Masculino,Feminino,Unissex,Infantil',
            'marca' => 'nullable|string|max:100',
            'sku_base' => "required|string|max:100|unique:produtos,sku_base,{$product->id}",
            'descricao' => 'nullable|string',
            'descricao_curta' => 'nullable|string',
            'preco_custo' => 'required|numeric|min:0',
            'preco_venda' => 'required|numeric|min:0',
            'tem_desconto' => 'boolean',
            'preco_desconto' => 'nullable|numeric|min:0',
            'ativo' => 'boolean',
            'esgotado' => 'boolean',
            'is_destaque' => 'boolean',
            'is_retro' => 'boolean',
            'retro_year' => 'nullable|integer',
            'estoque_critico' => 'nullable|integer|min:0',
            'peso_kg' => 'nullable|numeric|min:0',
            'dimensoes_json' => 'nullable|array',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            'permite_personalizacao' => 'boolean',
            'atributos' => 'nullable|array',
            'variacoes' => 'required|array|min:1',
            'variacoes.*.id' => 'nullable|integer|exists:variacoes_produto,id',
            'variacoes.*.sku' => 'nullable|string|max:100',
            'variacoes.*.tamanho' => 'nullable|string|max:30',
            'variacoes.*.cor' => 'nullable|string|max:60',
            'variacoes.*.preco_adicional' => 'required|numeric|min:0',
            'variacoes.*.tipo_estoque' => 'required|in:proprio,dropshipping',
            'variacoes.*.estoque_quantidade' => 'nullable|integer|min:0',
            'variacoes.*.estoque_minimo' => 'nullable|integer|min:0',
            'deleted_photos' => 'nullable|array',
            'deleted_photos.*' => 'integer|exists:fotos_produto,id',
            'fotos_por_cor' => 'nullable|array',
            'fotos_url_por_cor' => 'nullable|array',
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

            // Sincroniza variações
            $existingIds = [];
            foreach ($validated['variacoes'] as $varData) {
                if (!empty($varData['id'])) {
                    $variation = $product->variacoes()->find($varData['id']);
                    if ($variation) {
                        // Permite atualizar todos os campos da variação
                        $variation->update([
                            'sku' => $varData['sku'] ?? $variation->sku,
                            'tamanho' => $varData['tamanho'] ?? $variation->tamanho,
                            'cor' => $varData['cor'] ?? $variation->cor,
                            'preco_adicional' => $varData['preco_adicional'],
                            'tipo_estoque' => $varData['tipo_estoque'],
                        ]);
                        
                        // Ajuste de estoque
                        if ($variation->tipo_estoque === 'proprio' && isset($varData['estoque_quantidade'])) {
                            $newQtd = (int)$varData['estoque_quantidade'];
                            if ($newQtd !== $variation->estoque_quantidade) {
                                MovimentacaoEstoque::create([
                                    'variacao_id' => $variation->id,
                                    'quantidade' => abs($newQtd - $variation->estoque_quantidade),
                                    'estoque_antes' => $variation->estoque_quantidade,
                                    'estoque_depois' => $newQtd,
                                    'tipo' => $newQtd > $variation->estoque_quantidade ? 'entrada' : 'saida',
                                    'motivo' => 'Ajuste manual via Edição de Produto',
                                ]);
                                $variation->update(['estoque_quantidade' => $newQtd]);
                            }
                        }
                        $existingIds[] = $variation->id;
                    }
                } else {
                    // Nova variação
                    $variation = $product->variacoes()->create([
                        'sku' => $varData['sku'],
                        'tamanho' => $varData['tamanho'],
                        'cor' => $varData['cor'],
                        'preco_adicional' => $varData['preco_adicional'],
                        'tipo_estoque' => $varData['tipo_estoque'],
                        'estoque_quantidade' => $varData['estoque_quantidade'] ?? 0,
                    ]);
                    
                    if ($variation->tipo_estoque === 'proprio' && $variation->estoque_quantidade > 0) {
                        MovimentacaoEstoque::create([
                            'variacao_id' => $variation->id,
                            'quantidade' => $variation->estoque_quantidade,
                            'estoque_antes' => 0,
                            'estoque_depois' => $variation->estoque_quantidade,
                            'tipo' => 'entrada',
                            'motivo' => 'Adicionado via Edição de Produto',
                        ]);
                    }
                    $existingIds[] = $variation->id;
                }
            }
            
            // Inativa variações removidas da grade
            $product->variacoes()->whereNotIn('id', $existingIds)->update(['ativo' => false]);

            // Deleta fotos selecionadas para exclusão
            if (!empty($validated['deleted_photos'])) {
                $fotosToDelete = $product->fotos()->whereIn('id', $validated['deleted_photos'])->get();
                foreach($fotosToDelete as $foto) {
                    $path = str_replace('/storage/', '', $foto->url);
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
                    $foto->delete();
                }
            }

            // Processa novas fotos adicionadas
            if ($request->hasFile('fotos_por_cor')) {
                foreach ($request->file('fotos_por_cor') as $cor => $files) {
                    foreach ($files as $index => $file) {
                        $path = $file->store('products', 'public');
                        $product->fotos()->create([
                            'url' => '/storage/' . $path,
                            'ordem' => $index,
                            'cor' => $cor,
                            'is_capa' => ($product->fotos()->count() === 0),
                        ]);
                    }
                }
            }

            // Processa novas URLs de imagens por cor (sincronização: adiciona novas e remove deletadas)
            if ($request->has('fotos_url_por_cor')) {
                foreach ($request->input('fotos_url_por_cor') as $cor => $urlText) {
                    $corValue = ($cor === 'Geral') ? null : $cor;
                    
                    // Parseia as URLs enviadas
                    $submittedUrls = [];
                    if (!empty($urlText)) {
                        $rawUrls = preg_split('/[\n,]+/', str_replace("\r", "", $urlText));
                        foreach ($rawUrls as $url) {
                            $url = trim($url);
                            if (!empty($url)) {
                                $submittedUrls[] = $url;
                            }
                        }
                    }
                    
                    // Busca as fotos atuais desta cor no banco e deleta as que foram removidas
                    $existingPhotosOfColor = $product->fotos()->where('cor', $corValue)->get();
                    foreach ($existingPhotosOfColor as $foto) {
                        if (!in_array($foto->url, $submittedUrls)) {
                            // Se for arquivo local, remove da storage
                            if (strpos($foto->url, '/storage/') === 0) {
                                $path = str_replace('/storage/', '', $foto->url);
                                \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
                            }
                            $foto->delete();
                        }
                    }
                    
                    // Cria novas fotos ou atualiza as cores se mudaram
                    $hasCover = $product->fotos()->where('is_capa', true)->exists();
                    foreach ($submittedUrls as $index => $url) {
                        $existing = $product->fotos()->where('url', $url)->first();
                        if (!$existing) {
                            $isCapa = !$hasCover;
                            if ($isCapa) $hasCover = true;
                            $product->fotos()->create([
                                'url' => $url,
                                'ordem' => $index + 100,
                                'cor' => $corValue,
                                'is_capa' => $isCapa,
                            ]);
                        } else {
                            $existing->update([
                                'cor' => $corValue,
                                'ordem' => $index + 100
                            ]);
                        }
                    }
                }
            }
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
