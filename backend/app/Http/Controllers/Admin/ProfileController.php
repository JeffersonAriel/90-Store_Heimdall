<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function show()
    {
        return Inertia::render('Profile/Show', [
            'employee' => auth('admin')->user()->load('perfil'),
        ]);
    }

    public function update(Request $request)
    {
        /** @var \App\Models\Funcionario $employee */
        $employee = auth('admin')->user();

        $data = $request->validate([
            'nome'     => 'required|string|max:255',
            'email'    => 'required|email|unique:funcionarios,email,' . $employee->id,
            'telefone' => 'nullable|string|max:20',
            'cpf'      => 'nullable|string|max:14',
            'password' => 'nullable|string|min:8|confirmed',
        ], [
            'nome.required' => 'O nome é obrigatório.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Insira um e-mail válido.',
            'email.unique' => 'Este e-mail já está sendo utilizado.',
            'password.min' => 'A senha deve conter no mínimo 8 caracteres.',
            'password.confirmed' => 'A confirmação de senha não confere.',
        ]);

        $employee->nome = $data['nome'];
        $employee->email = $data['email'];
        $employee->telefone = $data['telefone'] ?? null;
        $employee->cpf = $data['cpf'] ?? null;

        if (!empty($data['password'])) {
            $employee->password = Hash::make($data['password']);
        }

        $employee->save();

        return back()->with('success', 'Perfil atualizado com sucesso!');
    }
}
