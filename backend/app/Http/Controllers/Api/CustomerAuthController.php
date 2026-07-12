<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\EnderecoCliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class CustomerAuthController extends Controller
{
    /**
     * Registro de novos clientes no e-commerce
     */
    public function register(Request $request)
    {
        $request->validate([
            'nome_completo' => 'required|string|max:255',
            'nome_social' => 'nullable|string|max:255',
            'cpf' => [
                'required',
                'string',
                'max:18',
                function ($attribute, $value, $fail) {
                    if (!$this->validaCPF($value)) {
                        $fail('O CPF informado é inválido.');
                    }
                }
            ],
            'data_nascimento' => 'required|date',
            'email' => 'required|email|max:255|unique:clientes,email',
            'password' => 'required|string|min:8|confirmed',
            'telefone' => 'required|string|max:20',
            'is_whatsapp' => 'nullable|boolean',
            
            // Campos de Endereço
            'cep' => 'required|string|max:10',
            'logradouro' => 'required|string|max:255',
            'numero' => 'required|string|max:20',
            'complemento' => 'nullable|string|max:100',
            'bairro' => 'required|string|max:255',
            'cidade' => 'required|string|max:255',
            'estado' => 'required|string|max:2',
            'ponto_referencia' => 'nullable|string|max:255',
        ]);

        $cpfLimpo = preg_replace('/[^0-9]/', '', $request->cpf);

        // Verifica CPF duplicado
        $exists = Cliente::all()->contains(function ($cliente) use ($cpfLimpo) {
            return preg_replace('/[^0-9]/', '', $cliente->cpf) === $cpfLimpo;
        });

        if ($exists) {
            throw ValidationException::withMessages([
                'cpf' => ['Este CPF já está cadastrado no sistema.'],
            ]);
        }

        $cliente = null;

        DB::transaction(function () use ($request, $cpfLimpo, &$cliente) {
            $cliente = Cliente::create([
                'nome_completo' => $request->nome_completo,
                'nome_social' => $request->nome_social,
                'cpf' => $cpfLimpo,
                'data_nascimento' => $request->data_nascimento,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'telefone' => $request->telefone,
                'whatsapp' => $request->is_whatsapp ? $request->telefone : null,
                'ativo' => true,
                'referral_code' => strtoupper(\Illuminate\Support\Str::random(8)),
            ]);

            EnderecoCliente::create([
                'cliente_id' => $cliente->id,
                'apelido' => 'Principal',
                'cep' => preg_replace('/[^0-9]/', '', $request->cep),
                'logradouro' => $request->logradouro,
                'numero' => $request->numero,
                'complemento' => $request->complemento,
                'bairro' => $request->bairro,
                'cidade' => $request->cidade,
                'estado' => $request->estado,
                'ponto_referencia' => $request->ponto_referencia,
                'is_principal' => true,
            ]);
        });

        $token = $cliente->createToken('store_auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'cliente' => [
                'id' => $cliente->id,
                'nome_completo' => $cliente->nome_completo,
                'nome_social' => $cliente->nome_social,
                'email' => $cliente->email,
            ]
        ], 201);
    }

    /**
     * Login do cliente (SPA Vue 3) via Sanctum token
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $cliente = Cliente::where('email', $request->email)->first();

        if (!$cliente || !Hash::check($request->password, $cliente->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        if (!$cliente->ativo) {
            return response()->json(['success' => false, 'message' => 'Esta conta está inativa.'], 403);
        }

        // Deleta tokens antigos para manter apenas um ativo por dispositivo
        $cliente->tokens()->delete();

        $token = $cliente->createToken('store_auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'cliente' => [
                'id' => $cliente->id,
                'nome_completo' => $cliente->nome_completo,
                'email' => $cliente->email,
            ]
        ]);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sessão encerrada com sucesso.'
        ]);
    }

    /**
     * Retorna dados do perfil do cliente autenticado
     */
    public function profile(Request $request)
    {
        $cliente = $request->user()->load('enderecos');
        return response()->json([
            'success' => true,
            'cliente' => [
                'id'            => $cliente->id,
                'nome_completo' => $cliente->nome_completo,
                'email'         => $cliente->email,
                'cpf'           => $cliente->cpf,
                'telefone'      => $cliente->telefone,
                'whatsapp'      => $cliente->whatsapp,
                'pontos_saldo'  => $cliente->pontos_saldo,
                'referral_code' => $cliente->referral_code,
                'enderecos'     => $cliente->enderecos,
            ]
        ]);
    }

    /**
     * Atualiza dados cadastrais do cliente autenticado
     */
    public function updateProfile(Request $request)
    {
        $cliente = $request->user();

        $rules = [
            'nome_completo' => 'sometimes|required|string|max:255',
            'email'         => 'sometimes|required|email|max:255|unique:clientes,email,' . $cliente->id,
            'telefone'      => 'sometimes|nullable|string|max:20',
            'password'      => 'sometimes|nullable|string|min:8|confirmed',
        ];

        $request->validate($rules);

        if ($request->filled('nome_completo')) $cliente->nome_completo = $request->nome_completo;
        if ($request->filled('email'))         $cliente->email         = $request->email;
        if ($request->filled('telefone'))      $cliente->telefone      = $request->telefone;
        if ($request->filled('password'))      $cliente->password      = Hash::make($request->password);

        $cliente->save();

        return response()->json([
            'success' => true,
            'message' => 'Dados atualizados com sucesso.',
            'cliente' => [
                'id'            => $cliente->id,
                'nome_completo' => $cliente->nome_completo,
                'email'         => $cliente->email,
                'cpf'           => $cliente->cpf,
                'telefone'      => $cliente->telefone,
                'pontos_saldo'  => $cliente->pontos_saldo,
                'referral_code' => $cliente->referral_code,
            ]
        ]);
    }

    /**
     * Algoritmo de validação de CPF.
     */
    private function validaCPF($cpf): bool
    {
        $cpf = preg_replace('/[^0-9]/is', '', $cpf);
        if (strlen($cpf) != 11) return false;
        if (preg_match('/(\d)\1{10}/', $cpf)) return false;

        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) return false;
        }

        return true;
    }
}
