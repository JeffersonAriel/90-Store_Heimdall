<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\CrmTemplateMensagem;
use App\Models\Crm\CrmCampanha;
use App\Models\Crm\CrmCampanhaEnvio;
use App\Models\Crm\CrmSegmento;
use App\Services\Crm\CrmMensagemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CrmTemplateController extends Controller
{
    public function index()
    {
        $templates = CrmTemplateMensagem::with('criadoPor:id,nome')
            ->orderBy('tipo')->orderBy('categoria')->orderBy('nome')
            ->get();

        return Inertia::render('Crm/Templates/Index', [
            'templates'  => $templates,
            'variaveis'  => CrmMensagemService::getVariaveisDisponiveis(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome'      => 'required|string|max:191',
            'categoria' => 'required|string|max:60',
            'tipo'      => 'required|in:whatsapp,email,sms',
            'assunto'   => 'nullable|string|max:191',
            'conteudo'  => 'required|string',
            'variaveis' => 'nullable|array',
        ]);

        $data['criado_por'] = Auth::guard('admin')->id();
        $data['ativo']      = true;

        CrmTemplateMensagem::create($data);

        return back()->with('success', 'Template criado!');
    }

    public function update(Request $request, CrmTemplateMensagem $template)
    {
        $data = $request->validate([
            'nome'      => 'sometimes|required|string|max:191',
            'categoria' => 'nullable|string|max:60',
            'tipo'      => 'nullable|in:whatsapp,email,sms',
            'assunto'   => 'nullable|string|max:191',
            'conteudo'  => 'sometimes|required|string',
            'variaveis' => 'nullable|array',
            'ativo'     => 'boolean',
        ]);

        $template->update($data);

        return back()->with('success', 'Template atualizado!');
    }

    public function destroy(CrmTemplateMensagem $template)
    {
        $template->delete();
        return back()->with('success', 'Template removido.');
    }

    /**
     * Preview do template com dados de exemplo.
     */
    public function preview(Request $request, CrmTemplateMensagem $template)
    {
        $preview = CrmMensagemService::renderizar($template->conteudo, [
            '{{cliente}}'   => 'João Silva',
            '{{pedido}}'    => '1234',
            '{{valor}}'     => 'R$ 299,90',
            '{{produto}}'   => 'Camisa Polo',
            '{{vendedor}}'  => 'Maria Oliveira',
            '{{cupom}}'     => 'BEMVINDO10',
        ]);

        return response()->json(['preview' => $preview]);
    }
}


class CrmCampanhaController extends Controller
{
    public function index()
    {
        $campanhas = CrmCampanha::with(['segmento:id,nome', 'criadoPor:id,nome'])
            ->orderByDesc('created_at')
            ->paginate(20);

        $segmentos = CrmSegmento::select('id', 'nome')->ativo()->get();
        $templates = CrmTemplateMensagem::select('id', 'nome', 'tipo')->ativo()->get();

        return Inertia::render('Crm/Campanhas/Index', [
            'campanhas' => $campanhas,
            'segmentos' => $segmentos,
            'templates' => $templates,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome'         => 'required|string|max:191',
            'descricao'    => 'nullable|string',
            'tipo'         => 'required|in:whatsapp,email,sms,push',
            'segmento_id'  => 'nullable|exists:crm_segmentos,id',
            'template_id'  => 'nullable|exists:crm_templates_mensagem,id',
            'conteudo'     => 'nullable|string',
            'agendada_para'=> 'nullable|date',
        ]);

        $data['criado_por'] = Auth::guard('admin')->id();
        $data['status']     = 'rascunho';

        if ($data['segmento_id'] ?? null) {
            $data['total_destinatarios'] = DB::table('crm_segmento_clientes')
                ->where('segmento_id', $data['segmento_id'])->count();
        }

        CrmCampanha::create($data);

        return back()->with('success', 'Campanha criada!');
    }

    public function update(Request $request, CrmCampanha $campanha)
    {
        $campanha->update($request->validate([
            'nome'         => 'sometimes|required|string|max:191',
            'descricao'    => 'nullable|string',
            'conteudo'     => 'nullable|string',
            'agendada_para'=> 'nullable|date',
            'status'       => 'nullable|in:rascunho,agendada,pausada,cancelada',
        ]));

        return back()->with('success', 'Campanha atualizada!');
    }

    public function destroy(CrmCampanha $campanha)
    {
        $campanha->delete();
        return back()->with('success', 'Campanha removida.');
    }

    /**
     * Dispara a campanha — cria registros de envio pendentes.
     */
    public function disparar(CrmCampanha $campanha)
    {
        if (!in_array($campanha->status, ['rascunho', 'agendada'])) {
            return back()->with('error', 'Campanha não pode ser disparada neste status.');
        }

        $clienteIds = DB::table('crm_segmento_clientes')
            ->where('segmento_id', $campanha->segmento_id)
            ->pluck('cliente_id');

        if ($clienteIds->isEmpty()) {
            return back()->with('error', 'Nenhum cliente no segmento selecionado.');
        }

        // Cria envios pendentes em bulk
        $envios = $clienteIds->map(fn($id) => [
            'campanha_id' => $campanha->id,
            'cliente_id'  => $id,
            'status'      => 'pendente',
            'created_at'  => now(),
            'updated_at'  => now(),
        ])->toArray();

        CrmCampanhaEnvio::insert($envios);

        $campanha->update([
            'status'              => 'enviando',
            'iniciada_em'         => now(),
            'total_destinatarios' => $clienteIds->count(),
        ]);

        return back()->with('success', "Campanha disparada para {$clienteIds->count()} clientes!");
    }
}
