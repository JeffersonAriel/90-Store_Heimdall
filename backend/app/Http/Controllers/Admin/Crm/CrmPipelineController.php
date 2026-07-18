<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\CrmPipelineEtapa;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * CrmPipelineController
 *
 * Kanban visual do pipeline de leads.
 * Extraído do CrmLeadController para classe própria.
 */
class CrmPipelineController extends Controller
{
    /**
     * Kanban visual — carrega todas as etapas com seus leads.
     */
    public function index()
    {
        $etapas = CrmPipelineEtapa::with([
            'leads' => fn($q) => $q->where('status', 'ativo')
                ->with(['responsavel:id,nome'])
                ->orderByDesc('created_at'),
        ])->ativo()->get()->map(function ($etapa) {
            $etapa->valor_total = $etapa->leads->sum('valor_esperado');
            $etapa->total_leads = $etapa->leads->count();
            return $etapa;
        });

        $funcionarios = \App\Models\Funcionario::select('id', 'nome')
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();

        $resumo = [
            'total_leads'           => $etapas->sum('total_leads'),
            'valor_total_pipeline'  => $etapas->sum('valor_total'),
            'leads_por_temperatura' => DB::table('crm_leads')
                ->where('status', 'ativo')
                ->selectRaw('temperatura, COUNT(*) as total')
                ->groupBy('temperatura')
                ->pluck('total', 'temperatura'),
        ];

        return Inertia::render('Crm/Pipeline/Index', [
            'etapas'      => $etapas,
            'funcionarios'=> $funcionarios,
            'resumo'      => $resumo,
        ]);
    }
}
