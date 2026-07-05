<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = DB::table('funcionarios as f')
            ->leftJoin('perfis_permissao as p', 'f.perfil_id', '=', 'p.id')
            ->select('f.id', 'f.nome', 'f.email', 'f.ativo', 'f.created_at', 'p.nome as perfil_nome')
            ->orderBy('f.nome')
            ->paginate(20);

        $perfis = DB::table('perfis_permissao')->select('id', 'nome')->get();

        return Inertia::render('Employees/Index', [
            'employees' => $employees,
            'perfis'    => $perfis,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome'      => 'required|string|max:255',
            'email'     => 'required|email|unique:funcionarios,email',
            'senha'     => 'required|string|min:8',
            'perfil_id' => 'required|exists:perfis_permissao,id',
        ]);

        DB::table('funcionarios')->insert([
            'nome'       => $data['nome'],
            'email'      => $data['email'],
            'senha_hash' => Hash::make($data['senha']),
            'perfil_id'  => $data['perfil_id'],
            'ativo'      => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Funcionário criado com sucesso!');
    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'nome'      => 'required|string|max:255',
            'email'     => "required|email|unique:funcionarios,email,{$id}",
            'perfil_id' => 'required|exists:perfis_permissao,id',
            'ativo'     => 'boolean',
        ]);

        DB::table('funcionarios')->where('id', $id)->update([
            'nome'       => $data['nome'],
            'email'      => $data['email'],
            'perfil_id'  => $data['perfil_id'],
            'ativo'      => $data['ativo'] ?? true,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Funcionário atualizado com sucesso!');
    }

    public function destroy(int $id)
    {
        DB::table('funcionarios')->where('id', $id)->delete();
        return back()->with('success', 'Funcionário removido.');
    }
}
