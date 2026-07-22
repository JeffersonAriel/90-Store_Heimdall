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
        $apisFrete = \App\Models\ApiConfiguracao::where('tipo', 'frete')->get();
        
        return Inertia::render('Shipping/Index', [
            'freteRegra' => $freteRegra,
            'apisFrete' => $apisFrete
        ]);
    }

    /**
     * Atualiza as regras de frete mínimo e coordenadas
     */
    public function update(Request $request, int $id)
    {
        $regra = FreteRegra::findOrFail($id);

        $validated = $request->validate([
            'valor_minimo_gratis' => 'nullable|numeric|min:0',
            'raio_km_local' => 'required|numeric|min:0',
            'cep_origem' => 'required|string|max:10',
            'logradouro_origem' => 'nullable|string|max:100',
            'numero_origem' => 'nullable|string|max:20',
            'complemento_origem' => 'nullable|string|max:50',
            'bairro_origem' => 'nullable|string|max:60',
            'cidade_origem' => 'nullable|string|max:60',
            'estado_origem' => 'nullable|string|max:2',
            'documento_origem' => 'nullable|string|max:20',
            'telefone_origem' => 'nullable|string|max:20',
            'email_origem' => 'nullable|email|max:100',
            'lat_origem' => 'required|numeric',
            'lng_origem' => 'required|numeric',
            'servicos_locais_json' => 'nullable|string',
        ]);

        $regra->update($validated);

        return back()->with('success', 'Regras de frete e endereço de origem atualizados com sucesso!');
    }
}
