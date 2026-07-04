<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FreteRegra;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    public function index()
    {
        // A listagem está integrada ao ApiConfigController.php para simplificar a view do painel central
        return redirect()->route('admin.api-config.index');
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
