<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Insumo;
use Illuminate\Http\Request;

class InsumoController extends Controller
{
    /**
     * Cadastra um novo insumo
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'custo' => 'required|numeric|min:0',
            'estoque' => 'required|integer|min:0',
            'categoria_id' => 'required|exists:categorias_tipo_produto,id',
        ]);

        Insumo::create($validated);

        return back()->with('success', 'Insumo cadastrado com sucesso!');
    }

    /**
     * Atualiza um insumo existente
     */
    public function update(Request $request, int $id)
    {
        $insumo = Insumo::findOrFail($id);

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'custo' => 'required|numeric|min:0',
            'estoque' => 'required|integer|min:0',
            'categoria_id' => 'required|exists:categorias_tipo_produto,id',
        ]);

        $insumo->update($validated);

        return back()->with('success', 'Insumo atualizado com sucesso!');
    }

    /**
     * Exclui um insumo
     */
    public function destroy(int $id)
    {
        $insumo = Insumo::findOrFail($id);
        $insumo->delete();

        return back()->with('success', 'Insumo excluído com sucesso!');
    }
}
