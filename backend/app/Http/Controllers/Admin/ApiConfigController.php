<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiConfiguracao;
use App\Models\FreteRegra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;

class ApiConfigController extends Controller
{
    public function index()
    {
        $apis = ApiConfiguracao::orderBy('tipo')->orderBy('fallback_ordem')->get();
        $freteRegra = FreteRegra::where('ativo', true)->first();

        // Oculta/limpa credenciais encriptadas enviadas para o front-end por segurança
        $apis->each(function ($api) {
            if ($api->credenciais_json) {
                $creds = json_decode($api->credenciais_json, true);
                $masked = [];
                foreach ($creds as $key => $val) {
                    $masked[$key] = '********'; // Mascara campos confidenciais
                }
                $api->credenciais_json = json_encode($masked);
            }
        });

        return Inertia::render('ApiConfig/Index', [
            'apis' => $apis,
            'freteRegra' => $freteRegra
        ]);
    }

    /**
     * Atualiza credenciais de uma API (Mercado Pago, Melhor Envio, etc.)
     */
    public function update(Request $request, string $slug)
    {
        $api = ApiConfiguracao::where('slug', $slug)->firstOrFail();

        $request->validate([
            'ativo' => 'boolean',
            'sandbox' => 'boolean',
            'credenciais' => 'required|array',
        ]);

        $novasCredenciais = $request->input('credenciais');
        
        // Se as novas credenciais vierem mascaradas (não alteradas pelo admin), mantém as anteriores
        if ($api->credenciais_json) {
            $antigas = json_decode($api->credenciais_json, true);
            foreach ($novasCredenciais as $key => $value) {
                if ($value === '********' && isset($antigas[$key])) {
                    $novasCredenciais[$key] = $antigas[$key];
                }
            }
        }

        $api->update([
            'ativo' => $request->input('ativo', true),
            'sandbox' => $request->input('sandbox', false),
            'credenciais_json' => json_encode($novasCredenciais),
        ]);

        return back()->with('success', "Configurações da API {$api->nome} salvas com sucesso!");
    }

    /**
     * Cadastra um novo Gateway manual ou API
     */
    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:100',
            'slug' => 'required|string|max:100|unique:apis_configuracao,slug',
            'tipo' => 'required|in:gateway,cep,frete',
            'ativo' => 'boolean',
            'credenciais' => 'required|array',
        ]);

        ApiConfiguracao::create([
            'nome' => $request->nome,
            'slug' => $request->slug,
            'tipo' => $request->tipo,
            'ativo' => $request->input('ativo', true),
            'sandbox' => false,
            'credenciais_json' => $request->credenciais, // mutator handle encryption
        ]);

        return back()->with('success', "Gateway {$request->nome} adicionado com sucesso!");
    }

    /**
     * Exclui um Gateway cadastrado manualmente
     */
    public function destroy(string $slug)
    {
        $api = ApiConfiguracao::where('slug', $slug)->firstOrFail();
        $api->delete();

        return back()->with('success', "Gateway {$api->nome} removido com sucesso!");
    }
}
