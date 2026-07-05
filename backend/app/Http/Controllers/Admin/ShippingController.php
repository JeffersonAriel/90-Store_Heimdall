<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FreteRegra;
use Illuminate\Http\Request;

use Inertia\Inertia;

class ShippingController extends Controller
{
    public function index()
    {
        $freteRegra = FreteRegra::where('ativo', true)->first();
        
        return Inertia::render('Shipping/Index', [
            'freteRegra' => $freteRegra
        ]);
    }

    /**
     * Atualiza as regras de frete mínimo e coordenadas
     */
    public function update(Request $request, int $id)
    {
        $regra = FreteRegra::findOrFail($id);

        $validated = $request->validate([
            'valor_minimo_gratis' => 'required|numeric|min:0',
            'raio_km_local' => 'required|numeric|min:0',
            'cep_origem' => 'required|string|max:10',
            'lat_origem' => 'required|numeric',
            'lng_origem' => 'required|numeric',
        ]);

        $regra->update($validated);

        return back()->with('success', 'Regras de frete e coordenadas locais atualizadas com sucesso!');
    }
}
