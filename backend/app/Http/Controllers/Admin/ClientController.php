<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $clients = Cliente::query()
            ->when($search, function ($query, $search) {
                // Como CPF está criptografado (encrypted cast), não conseguimos usar "like" direto
                // Mas podemos pesquisar por email, nome ou telefone
                $query->where('nome_completo', 'like', "%{$search}%")
                      ->orWhere('nome_social', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('telefone', 'like', "%{$search}%")
                      ->orWhere('whatsapp', 'like', "%{$search}%");
            })
            ->withCount('pedidos')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'filters' => $request->only('search'),
        ]);
    }

    public function show(Cliente $client)
    {
        $client->load(['enderecos', 'pedidos' => function ($q) {
            $q->orderBy('id', 'desc')->limit(10);
        }]);

        return response()->json([
            'success' => true,
            'client' => $client
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome_completo' => 'required|string|max:255',
            'cpf' => 'required|string|max:14',
            'email' => 'required|email|max:255|unique:clientes,email',
            'telefone' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
        ]);

        $data['password'] = bcrypt(\Illuminate\Support\Str::random(12));
        $data['ativo'] = true;

        Cliente::create($data);

        return back()->with('success', 'Cliente cadastrado com sucesso!');
    }

    public function update(Request $request, Cliente $client)
    {
        $data = $request->validate([
            'ativo' => 'required|boolean'
        ]);

        $client->update([
            'ativo' => $data['ativo']
        ]);

        return back()->with('success', 'Status do cliente atualizado com sucesso!');
    }

    public function destroy(Cliente $client)
    {
        $client->delete();
        return back()->with('success', 'Cliente removido com sucesso.');
    }
}
