<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoriaTipoProduto;
use App\Models\AtributoCategoria;
use App\Models\OpcaoAtributo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = CategoriaTipoProduto::with('parent')
            ->withCount('produtos')
            ->orderBy('ordem')
            ->get();

        return Inertia::render('Categories/Index', [
            'categories' => $categories
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:categorias_tipo_produto,id',
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'icone' => 'nullable|string|max:60',
            'ordem' => 'integer',
            'ativo' => 'boolean',
        ]);

        $slug = Str::slug($validated['nome']);
        $count = CategoriaTipoProduto::where('slug', 'like', "{$slug}%")->count();
        if ($count > 0) {
            $slug = "{$slug}-" . ($count + 1);
        }

        CategoriaTipoProduto::create(array_merge($validated, ['slug' => $slug]));

        return back()->with('success', 'Categoria cadastrada com sucesso!');
    }

    public function update(Request $request, CategoriaTipoProduto $category)
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:categorias_tipo_produto,id',
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'icone' => 'nullable|string|max:60',
            'ordem' => 'integer',
            'ativo' => 'boolean',
        ]);

        $category->update($validated);

        return back()->with('success', 'Categoria atualizada com sucesso!');
    }

    public function destroy(CategoriaTipoProduto $category)
    {
        if ($category->produtos()->exists()) {
            return back()->with('error', 'Esta categoria não pode ser removida pois possui produtos vinculados.');
        }

        $category->delete();
        return back()->with('success', 'Categoria removida com sucesso!');
    }

    public function addAttribute(Request $request, CategoriaTipoProduto $category)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'tipo' => 'required|in:select,text,number',
            'obrigatorio' => 'boolean',
            'opcoes' => 'nullable|array',
        ]);

        DB::transaction(function () use ($category, $validated) {
            $attribute = $category->atributos()->create([
                'nome' => $validated['nome'],
                'tipo' => $validated['tipo'],
                'obrigatorio' => $validated['obrigatorio'],
                'ordem' => $category->atributos()->count() + 1
            ]);

            if ($validated['tipo'] === 'select' && !empty($validated['opcoes'])) {
                foreach ($validated['opcoes'] as $index => $opcaoValor) {
                    if ($opcaoValor !== null && $opcaoValor !== '') {
                        $attribute->opcoes()->create([
                            'valor' => $opcaoValor,
                            'ordem' => $index
                        ]);
                    }
                }
            }
        });

        return back()->with('success', 'Atributo adicionado com sucesso!');
    }
}
