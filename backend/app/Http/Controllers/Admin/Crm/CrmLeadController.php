<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\CrmLead;
use App\Models\Crm\CrmPipelineEtapa;
use App\Models\Crm\CrmLeadEtapa;
use App\Services\Crm\CrmTimelineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CrmLeadController extends Controller
{
    public function index(Request $request)
    {
        $leads = CrmLead::with(['etapa:id,nome,cor', 'responsavel:id,nome', 'cliente:id,nome_completo'])
            ->when($request->input('search'), fn($q, $s) =>
                $q->where('nome', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
            )
            ->when($request->input('origem'),    fn($q, $o) => $q->where('origem', $o))
            ->when($request->input('temperatura'),fn($q, $t) => $q->where('temperatura', $t))
            ->when($request->input('status'),    fn($q, $s) => $q->where('status', $s))
            ->when($request->input('etapa_id'),  fn($q, $e) => $q->where('etapa_id', $e))
            ->when($request->input('responsavel_id'), fn($q, $r) => $q->where('responsavel_id', $r))
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $etapas      = CrmPipelineEtapa::ativo()->get();
        $funcionarios= \App\Models\Funcionario::select('id', 'nome')->where('ativo', true)->orderBy('nome')->get();

        return Inertia::render('Crm/Leads/Index', [
            'leads'       => $leads,
            'etapas'      => $etapas,
            'funcionarios'=> $funcionarios,
            'filters'     => $request->only(['search', 'origem', 'temperatura', 'status', 'etapa_id', 'responsavel_id']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome'             => 'required|string|max:191',
            'email'            => 'nullable|email|max:191',
            'telefone'         => 'nullable|string|max:30',
            'whatsapp'         => 'nullable|string|max:30',
            'empresa'          => 'nullable|string|max:191',
            'origem'           => 'required|in:site,landing_page,whatsapp,instagram,facebook,google_ads,marketplace,indicacao,telefone,importacao,outro',
            'interesse'        => 'nullable|string',
            'temperatura'      => 'nullable|in:frio,morno,quente',
            'probabilidade'    => 'nullable|integer|min:0|max:100',
            'valor_esperado'   => 'nullable|numeric|min:0',
            'responsavel_id'   => 'nullable|exists:funcionarios,id',
            'etapa_id'         => 'nullable|exists:crm_pipeline_etapas,id',
            'proxima_acao'     => 'nullable|string',
            'data_proxima_acao'=> 'nullable|date',
            'tags'             => 'nullable|array',
        ]);

        $lead = CrmLead::create($data);

        CrmTimelineService::registrarLead('lead_criado', $lead->id,
            "Lead {$lead->nome} criado via {$lead->origem}",
            ['origem' => 'manual']
        );

        return back()->with('success', 'Lead criado com sucesso!');
    }

    public function show(CrmLead $lead)
    {
        $lead->load([
            'etapa', 'responsavel:id,nome,foto',
            'cliente:id,nome_completo,email',
            'historicoEtapas.etapaDe', 'historicoEtapas.etapaPara', 'historicoEtapas.funcionario:id,nome',
            'notas.funcionario:id,nome',
            'contatos.funcionario:id,nome',
            'tarefas.responsavel:id,nome',
            'documentos',
        ]);

        $timeline = $lead->timeline()->limit(30)->get()->map(fn($e) => array_merge($e->toArray(), [
            'icone_resolvido' => $e->iconeResolvido,
            'cor_resolvida'   => $e->corResolvida,
        ]));

        $etapas       = CrmPipelineEtapa::ativo()->get();
        $funcionarios = \App\Models\Funcionario::select('id', 'nome')->where('ativo', true)->get();

        return Inertia::render('Crm/Leads/Show', [
            'lead'        => $lead,
            'timeline'    => $timeline,
            'etapas'      => $etapas,
            'funcionarios'=> $funcionarios,
        ]);
    }

    public function update(Request $request, CrmLead $lead)
    {
        $data = $request->validate([
            'nome'             => 'sometimes|required|string|max:191',
            'email'            => 'nullable|email|max:191',
            'telefone'         => 'nullable|string|max:30',
            'whatsapp'         => 'nullable|string|max:30',
            'empresa'          => 'nullable|string|max:191',
            'origem'           => 'nullable|string',
            'interesse'        => 'nullable|string',
            'temperatura'      => 'nullable|in:frio,morno,quente',
            'probabilidade'    => 'nullable|integer|min:0|max:100',
            'valor_esperado'   => 'nullable|numeric|min:0',
            'responsavel_id'   => 'nullable|exists:funcionarios,id',
            'proxima_acao'     => 'nullable|string',
            'data_proxima_acao'=> 'nullable|date',
            'status'           => 'nullable|in:ativo,convertido,perdido',
            'motivo_perda'     => 'nullable|string',
            'tags'             => 'nullable|array',
        ]);

        $lead->update($data);

        return back()->with('success', 'Lead atualizado!');
    }

    public function destroy(CrmLead $lead)
    {
        $lead->delete();
        return back()->with('success', 'Lead removido.');
    }

    /**
     * Move o lead para outra etapa do pipeline.
     */
    public function moverEtapa(Request $request, CrmLead $lead)
    {
        $request->validate([
            'etapa_id'   => 'required|exists:crm_pipeline_etapas,id',
            'observacao' => 'nullable|string',
        ]);

        $etapaAnteriorId = $lead->etapa_id;
        $etapaNova       = CrmPipelineEtapa::findOrFail($request->etapa_id);

        // Calcula dias na etapa anterior
        $diasNaEtapa = 0;
        if ($etapaAnteriorId) {
            $ultima = CrmLeadEtapa::where('lead_id', $lead->id)->latest()->first();
            if ($ultima) {
                $diasNaEtapa = (int) $ultima->created_at->diffInDays(now());
            }
        }

        // Registra histórico
        CrmLeadEtapa::create([
            'lead_id'       => $lead->id,
            'etapa_de_id'   => $etapaAnteriorId,
            'etapa_para_id' => $request->etapa_id,
            'funcionario_id'=> Auth::guard('admin')->id(),
            'dias_na_etapa' => $diasNaEtapa,
            'observacao'    => $request->observacao,
        ]);

        // Atualiza probabilidade automaticamente
        $lead->update([
            'etapa_id'     => $request->etapa_id,
            'probabilidade'=> $etapaNova->probabilidade_default,
            'status'       => match ($etapaNova->tipo) {
                'ganho'   => 'convertido',
                'perdido' => 'perdido',
                default   => 'ativo',
            },
        ]);

        CrmTimelineService::registrarLead('etapa_pipeline', $lead->id,
            "Lead movido para: {$etapaNova->nome}",
            ['metadata' => ['etapa' => $etapaNova->nome, 'cor' => $etapaNova->cor]]
        );

        return back()->with('success', "Lead movido para {$etapaNova->nome}!");
    }
}

