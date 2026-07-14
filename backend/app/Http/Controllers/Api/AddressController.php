<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EnderecoCliente;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    /**
     * Lista todos os endereços do cliente logado
     */
    public function index(Request $request)
    {
        $addresses = $request->user()->enderecos()->orderBy('is_principal', 'desc')->get();

        return response()->json([
            'success' => true,
            'enderecos' => $addresses
        ]);
    }

    /**
     * Adiciona um novo endereço para o cliente
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'apelido' => 'nullable|string|max:60',
            'cep' => 'required|string|max:10',
            'logradouro' => 'required|string|max:255',
            'numero' => 'required|string|max:20',
            'complemento' => 'nullable|string|max:100',
            'bairro' => 'required|string|max:255',
            'cidade' => 'required|string|max:255',
            'estado' => 'required|string|max:2',
            'ponto_referencia' => 'nullable|string|max:255',
            'is_principal' => 'boolean',
        ]);

        $cliente = $request->user();

        // Se for marcado como principal, desmarca os anteriores
        if ($request->input('is_principal', false)) {
            $cliente->enderecos()->update(['is_principal' => false]);
        }

        // Se for o primeiro endereço cadastrado, força a ser o principal
        $isFirst = !$cliente->enderecos()->exists();
        if ($isFirst) {
            $validated['is_principal'] = true;
        }

        $address = $cliente->enderecos()->create($validated);

        return response()->json([
            'success' => true,
            'endereco' => $address
        ], 201);
    }

    /**
     * Atualiza um endereço do cliente
     */
    public function update(Request $request, int $id)
    {
        $address = $request->user()->enderecos()->findOrFail($id);

        $validated = $request->validate([
            'apelido'         => 'nullable|string|max:60',
            'cep'             => 'required|string|max:10',
            'logradouro'      => 'required|string|max:255',
            'numero'          => 'required|string|max:20',
            'complemento'     => 'nullable|string|max:100',
            'bairro'          => 'required|string|max:255',
            'cidade'          => 'required|string|max:255',
            'estado'          => 'required|string|max:2',
            'ponto_referencia'=> 'nullable|string|max:255',
        ]);

        $address->update($validated);

        return response()->json([
            'success'  => true,
            'message'  => 'Endereço atualizado com sucesso.',
            'endereco' => $address->fresh(),
        ]);
    }

    /**
     * Define um endereço como principal
     */
    public function setPrincipal(Request $request, int $id)
    {
        $cliente = $request->user();
        $address = $cliente->enderecos()->findOrFail($id);

        $cliente->enderecos()->update(['is_principal' => false]);
        $address->update(['is_principal' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Endereço principal atualizado.',
        ]);
    }

    /**
     * Remove um endereço
     */
    public function destroy(Request $request, int $id)
    {
        $address = $request->user()->enderecos()->findOrFail($id);

        if ($address->is_principal) {
            return response()->json([
                'success' => false,
                'message' => 'Você não pode deletar seu endereço principal. Defina outro como principal primeiro.'
            ], 400);
        }

        try {
            $address->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            // Se falhar por chave estrangeira, tenta reassociar os pedidos ao endereço principal do cliente
            $principalAddress = $request->user()->enderecos()->where('is_principal', true)->first();
            
            if ($principalAddress && $principalAddress->id !== $address->id) {
                try {
                    \Illuminate\Support\Facades\DB::table('pedidos')
                        ->where('cliente_id', $request->user()->id)
                        ->where('endereco_id', $address->id)
                        ->update(['endereco_id' => $principalAddress->id]);
                    
                    // Tenta a exclusão novamente após reassociar
                    $address->delete();
                } catch (\Exception $ex) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Este endereço está vinculado a pedidos existentes e não pôde ser reassociado automaticamente.'
                    ], 400);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Este endereço não pode ser excluído pois está vinculado a um ou mais pedidos efetuados.'
                ], 400);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Endereço removido com sucesso.'
        ]);
    }
}
