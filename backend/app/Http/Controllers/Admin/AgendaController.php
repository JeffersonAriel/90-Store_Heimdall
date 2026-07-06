<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\Funcionario;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AgendaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $events = Agenda::with(['funcionario', 'cliente'])->get();
        $funcionarios = Funcionario::select('id', 'nome')->get();
        $clientes = Cliente::select('id', 'nome_completo')->get();

        return Inertia::render('Agenda/Index', [
            'events' => $events,
            'funcionarios' => $funcionarios,
            'clientes' => $clientes,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after_or_equal:data_inicio',
            'cor' => 'nullable|string|max:10',
            'funcionario_id' => 'nullable|exists:funcionarios,id',
            'cliente_id' => 'nullable|exists:clientes,id',
        ]);

        Agenda::create([
            'titulo' => $request->titulo,
            'descricao' => $request->descricao,
            'data_inicio' => $request->data_inicio,
            'data_fim' => $request->data_fim,
            'cor' => $request->cor ?? '#3b82f6',
            'funcionario_id' => $request->funcionario_id,
            'cliente_id' => $request->cliente_id,
        ]);

        return back()->with('success', 'Compromisso agendado com sucesso!');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after_or_equal:data_inicio',
            'cor' => 'nullable|string|max:10',
            'funcionario_id' => 'nullable|exists:funcionarios,id',
            'cliente_id' => 'nullable|exists:clientes,id',
        ]);

        $event = Agenda::findOrFail($id);
        $event->update([
            'titulo' => $request->titulo,
            'descricao' => $request->descricao,
            'data_inicio' => $request->data_inicio,
            'data_fim' => $request->data_fim,
            'cor' => $request->cor ?? '#3b82f6',
            'funcionario_id' => $request->funcionario_id,
            'cliente_id' => $request->cliente_id,
        ]);

        return back()->with('success', 'Compromisso atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $event = Agenda::findOrFail($id);
        $event->delete();

        return back()->with('success', 'Compromisso removido da agenda!');
    }
}
