<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\CrmTarefa;
use App\Services\Crm\CrmTimelineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CrmTarefaController extends Controller
{
    public function index(Request $request)
    {
        $usuario = Auth::guard('admin')->user();

        $tarefas = CrmTarefa::with([
            'cliente:id,nome_completo',
            'lead:id,nome',
            'responsavel:id,nome',
        ])
        ->when(!$usuario->isAdmin(), fn($q) => $q->where('responsavel_id', $usuario->id))
        ->when($request->input('status'), fn($q, $s) => $q->whereIn('status', explode(',', $s)))
        ->when($request->input('tipo'),   fn($q, $t) => $q->where('tipo', $t))
        ->when($request->input('prioridade'), fn($q, $p) => $q->where('prioridade', $p))
        ->orderByRaw("CASE prioridade WHEN 'urgente' THEN 1 WHEN 'alta' THEN 2 WHEN 'media' THEN 3 ELSE 4 END")
        ->orderBy('vencimento_em')
        ->paginate(30)
        ->withQueryString();

        $funcionarios = \App\Models\Funcionario::select('id', 'nome')->where('ativo', true)->get();

        return Inertia::render('Crm/Tarefas/Index', [
            'tarefas'     => $tarefas,
            'funcionarios'=> $funcionarios,
            'filters'     => $request->only(['status', 'tipo', 'prioridade']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo'         => 'required|string|max:191',
            'descricao'      => 'nullable|string',
            'tipo'           => 'required|in:contato,ligacao,visita,whatsapp,email,proposta,cobranca,pos_venda,reuniao,pesquisa,outro',
            'prioridade'     => 'nullable|in:baixa,media,alta,urgente',
            'responsavel_id' => 'nullable|exists:funcionarios,id',
            'cliente_id'     => 'nullable|exists:clientes,id',
            'lead_id'        => 'nullable|exists:crm_leads,id',
            'vencimento_em'  => 'nullable|date',
        ]);

        $data['criado_por'] = Auth::guard('admin')->id();
        $data['status']     = 'pendente';

        $tarefa = CrmTarefa::create($data);

        if ($data['cliente_id'] ?? null) {
            CrmTimelineService::tarefaCriada($data['cliente_id'], $data['titulo']);
        }

        return back()->with('success', 'Tarefa criada!');
    }

    public function update(Request $request, CrmTarefa $tarefa)
    {
        $data = $request->validate([
            'titulo'         => 'sometimes|required|string|max:191',
            'descricao'      => 'nullable|string',
            'tipo'           => 'nullable|string',
            'prioridade'     => 'nullable|in:baixa,media,alta,urgente',
            'status'         => 'nullable|in:pendente,em_andamento,concluida,cancelada',
            'responsavel_id' => 'nullable|exists:funcionarios,id',
            'vencimento_em'  => 'nullable|date',
            'resultado'      => 'nullable|string',
        ]);

        if (($data['status'] ?? null) === 'concluida' && $tarefa->status !== 'concluida') {
            $data['concluida_em'] = now();
        }

        $tarefa->update($data);

        return back()->with('success', 'Tarefa atualizada!');
    }

    public function concluir(Request $request, CrmTarefa $tarefa)
    {
        $tarefa->update([
            'status'      => 'concluida',
            'concluida_em'=> now(),
            'resultado'   => $request->input('resultado'),
        ]);

        return back()->with('success', 'Tarefa concluída!');
    }

    public function destroy(CrmTarefa $tarefa)
    {
        $tarefa->delete();
        return back()->with('success', 'Tarefa removida.');
    }
}
