<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\EnderecoCliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CustomerAuthController extends Controller
{
    /**
     * Registro de novos clientes no e-commerce
     */
    public function register(Request $request)
    {
        $request->validate([
            'nome_completo' => 'required|string|max:255',
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
            'data_nascimento' => 'nullable|date',
            'email' => 'required|email|max:255|unique:clientes,email',
            'password' => 'required|string|min:8|confirmed',
            'telefone' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
        ]);

        $cpfLimpo = preg_replace('/[^0-9]/', '', $request->cpf);

        // Verifica CPF duplicado
        // Como o CPF é salvo encriptado, precisamos buscar ou validar de forma segura.
        // Uma alternativa segura de busca por correspondência exata para dados encriptados
        // sem expor a chave é descriptografar localmente em coleções pequenas, ou usar hashes de busca (Blind Index).
        // Para SQLite local, buscaremos descriptografando
        $exists = Cliente::all()->contains(function ($cliente) use ($cpfLimpo) {
            return preg_replace('/[^0-9]/', '', $cliente->cpf) === $cpfLimpo;
        });

        if ($exists) {
            throw ValidationException::withMessages([
                'cpf' => ['Este CPF já está cadastrado no sistema.'],
            ]);
        }

        $cliente = Cliente::create([
            'nome_completo' => $request->nome_completo,
            'cpf' => $cpfLimpo, // Cast 'encrypted' do Model cuida do AES-256 automaticamente
            'data_nascimento' => $request->data_nascimento,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'telefone' => $request->telefone,
            'whatsapp' => $request->whatsapp,
            'ativo' => true,
            'referral_code' => strtoupper(\Illuminate\Support\Str::random(8)),
        ]);

        $token = $cliente->createToken('store_auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'cliente' => [
                'id' => $cliente->id,
                'nome_completo' => $cliente->nome_completo,
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
        $cliente = $request->user();
        return response()->json([
            'success' => true,
            'cliente' => [
                'id' => $cliente->id,
                'nome_completo' => $cliente->nome_completo,
                'email' => $cliente->email,
                'cpf' => $cliente->cpf, // Descriptografado automaticamente pelo cast
                'telefone' => $cliente->telefone,
                'whatsapp' => $cliente->whatsapp,
                'pontos_saldo' => $cliente->pontos_saldo,
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
