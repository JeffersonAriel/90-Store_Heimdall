<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fornecedor;
use App\Models\AvaliacaoFornecedor;
use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $productId = $request->input('product_id');

        $suppliers = Fornecedor::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('razao_social', 'like', "%{$search}%")
                      ->orWhere('nome_fantasia', 'like', "%{$search}%")
                      ->orWhere('cnpj', 'like', "%{$search}%")
                      ->orWhere('cpf', 'like', "%{$search}%")
                      ->orWhere('website', 'like', "%{$search}%")
                      ->orWhereHas('produtos', function ($pq) use ($search) {
                          $pq->where('nome', 'like', "%{$search}%")
                             ->orWhere('sku_base', 'like', "%{$search}%");
                      });
                });
            })
            ->when($productId, function ($query, $productId) {
                $query->whereHas('produtos', function ($q) use ($productId) {
                    $q->where('id', $productId);
                });
            })
            ->with(['produtos' => function ($query) {
                $query->select('id', 'fornecedor_id', 'nome', 'sku_base', 'ativo');
            }])
            ->withCount('produtos')
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        $allProducts = Produto::query()
            ->select('id', 'nome')
            ->orderBy('nome')
            ->get();

        return Inertia::render('Suppliers/Index', [
            'suppliers' => $suppliers,
            'products' => $allProducts,
            'filters' => $request->only('search', 'product_id')
        ]);
    }

    public function create()
    {
        return Inertia::render('Suppliers/Form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo_pessoa' => 'required|in:juridica,fisica',
            'razao_social' => 'required|string|max:255',
            'nome_fantasia' => 'nullable|string|max:255',
            'cnpj' => 'required_if:tipo_pessoa,juridica|nullable|string|max:20|unique:fornecedores,cnpj',
            'cpf' => 'required_if:tipo_pessoa,fisica|nullable|string|max:20|unique:fornecedores,cpf',
            'email' => 'nullable|email|max:255',
            'telefone' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'website' => 'nullable|string|max:255',
            'cep' => 'nullable|string|max:10',
            'logradouro' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:20',
            'complemento' => 'nullable|string|max:100',
            'bairro' => 'nullable|string|max:255',
            'cidade' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:2',
            'condicao_pagamento' => 'nullable|string',
            'prazo_medio_dias' => 'nullable|integer|min:0',
            'categorias_fornecidas' => 'nullable|array',
            'categorias_fornecidas.*' => 'string|max:100',
            'observacoes' => 'nullable|string',
            'ativo' => 'boolean',
        ]);

        $validated['prazo_medio_dias'] = $validated['prazo_medio_dias'] ?? 0;

        Fornecedor::create($validated);

        return redirect()->route('admin.suppliers.index')->with('success', 'Fornecedor cadastrado com sucesso!');
    }

    public function edit(Fornecedor $supplier)
    {
        return Inertia::render('Suppliers/Form', [
            'supplier' => $supplier
        ]);
    }

    public function update(Request $request, Fornecedor $supplier)
    {
        $validated = $request->validate([
            'tipo_pessoa' => 'required|in:juridica,fisica',
            'razao_social' => 'required|string|max:255',
            'nome_fantasia' => 'nullable|string|max:255',
            'cnpj' => "required_if:tipo_pessoa,juridica|nullable|string|max:20|unique:fornecedores,cnpj,{$supplier->id}",
            'cpf' => "required_if:tipo_pessoa,fisica|nullable|string|max:20|unique:fornecedores,cpf,{$supplier->id}",
            'email' => 'nullable|email|max:255',
            'telefone' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'website' => 'nullable|string|max:255',
            'cep' => 'nullable|string|max:10',
            'logradouro' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:20',
            'complemento' => 'nullable|string|max:100',
            'bairro' => 'nullable|string|max:255',
            'cidade' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:2',
            'condicao_pagamento' => 'nullable|string',
            'prazo_medio_dias' => 'nullable|integer|min:0',
            'categorias_fornecidas' => 'nullable|array',
            'categorias_fornecidas.*' => 'string|max:100',
            'observacoes' => 'nullable|string',
            'ativo' => 'boolean',
        ]);

        $validated['prazo_medio_dias'] = $validated['prazo_medio_dias'] ?? 0;

        $supplier->update($validated);

        return redirect()->route('admin.suppliers.index')->with('success', 'Fornecedor atualizado com sucesso!');
    }

    public function destroy(Fornecedor $supplier)
    {
        $supplier->delete();
        return redirect()->route('admin.suppliers.index')->with('success', 'Fornecedor removido com sucesso!');
    }

    public function evaluate(Request $request, Fornecedor $supplier)
    {
        $validated = $request->validate([
            'estrelas' => 'required|integer|between:1,5',
            'comentario' => 'nullable|string',
        ]);

        DB::transaction(function () use ($supplier, $validated) {
            AvaliacaoFornecedor::create([
                'fornecedor_id' => $supplier->id,
                'funcionario_id' => Auth::guard('admin')->id(),
                'estrelas' => $validated['estrelas'],
                'comentario' => $validated['comentario'],
            ]);

            // Atualiza avaliação média do fornecedor
            $avgStars = AvaliacaoFornecedor::where('fornecedor_id', $supplier->id)->avg('estrelas') ?? 0;
            $supplier->update(['avaliacao_media' => round($avgStars, 2)]);
        });

        return back()->with('success', 'Avaliação registrada com sucesso!');
    }

    public function show(Fornecedor $supplier)
    {
        $supplier->load(['produtos', 'avaliacoes.funcionario']);
        
        return Inertia::render('Suppliers/Show', [
            'supplier' => $supplier
        ]);
    }
}
