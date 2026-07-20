<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Crm\CrmTimelineEvento;
use App\Models\Crm\CrmNota;
use App\Models\Crm\CrmContato;
use App\Models\Crm\CrmDocumento;
use App\Models\Crm\CrmTarefa;
use App\Services\Crm\CrmTimelineService;
use App\Services\Crm\CrmKpiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CrmClienteController extends Controller
{
    /**
     * Lista de clientes com filtros CRM avançados.
     */
    public function index(Request $request)
    {
        $search   = $request->input('search');
        $segmento = $request->input('segmento');
        $risco    = $request->input('risco');
        $vendedor = $request->input('vendedor_id');
        $periodo  = $request->input('periodo');

        $clientes = Cliente::query()
            ->with(['vendedor:id,nome'])
            ->withCount(['pedidos' => function ($q) {
                $q->whereNotIn('status', ['aguardando_pagamento', 'cancelado']);
            }])
            ->when($search, fn($q) =>
                $q->where('nome_completo', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('telefone', 'like', "%{$search}%")
            )
            ->when($segmento, fn($q) => $q->where('segmento_crm', $segmento))
            ->when($risco,    fn($q) => $q->where('risco_churn', $risco))
            ->when($vendedor, fn($q) => $q->where('vendedor_id', $vendedor))
            ->when($periodo === 'inativos_30', fn($q) =>
                $q->where('ultimo_pedido_em', '<', now()->subDays(30))->whereNotNull('ultimo_pedido_em')
            )
            ->when($periodo === 'inativos_60', fn($q) =>
                $q->where('ultimo_pedido_em', '<', now()->subDays(60))->whereNotNull('ultimo_pedido_em')
            )
            ->when($periodo === 'inativos_90', fn($q) =>
                $q->where('ultimo_pedido_em', '<', now()->subDays(90))->whereNotNull('ultimo_pedido_em')
            )
            ->orderByDesc('total_gasto')
            ->paginate(20)
            ->withQueryString();

        $vendedores = \App\Models\Funcionario::select('id', 'nome')->where('ativo', true)->orderBy('nome')->get();

        return Inertia::render('Crm/Clientes/Index', [
            'clientes'  => $clientes,
            'vendedores'=> $vendedores,
            'filters'   => $request->only(['search', 'segmento', 'risco', 'vendedor_id', 'periodo']),
        ]);
    }

    /**
     * CRM 360° do cliente — visão completa.
     */
    public function show(Cliente $cliente)
    {
        $cliente->load([
            'vendedor:id,nome,foto',
            'enderecos',
            'crmSegmentos:id,nome,cor,icone',
        ]);

        // Pedidos recentes (últimos 10)
        $pedidos = $cliente->pedidos()
            ->whereNotIn('status', ['aguardando_pagamento', 'cancelado'])
            ->with(['itens.produto:id,nome', 'pagamentos:id,pedido_id,metodo,status,valor'])
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        // Timeline (últimos 50 eventos)
        $timeline = $cliente->timelineEventos()
            ->limit(50)
            ->get()
            ->map(fn($e) => array_merge($e->toArray(), [
                'icone_resolvido' => $e->iconeResolvido,
                'cor_resolvida'   => $e->corResolvida,
            ]));

        // Notas internas
        $notas = $cliente->crmNotas()
            ->with('funcionario:id,nome,foto')
            ->limit(20)
            ->get();

        // Contatos registrados
        $contatos = $cliente->crmContatos()
            ->with('funcionario:id,nome')
            ->limit(20)
            ->get();

        // Tarefas pendentes
        $tarefas = $cliente->crmTarefas()
            ->with('responsavel:id,nome')
            ->whereIn('status', ['pendente', 'em_andamento'])
            ->orderBy('vencimento_em')
            ->limit(10)
            ->get();

        // Ocorrências abertas
        $ocorrencias = $cliente->crmOcorrencias()
            ->with('responsavel:id,nome')
            ->whereNotIn('status', ['fechada'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Documentos
        $documentos = $cliente->crmDocumentos()
            ->with('funcionario:id,nome')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        // Alertas ativos
        $alertas = $cliente->crmAlertas()
            ->where('resolvido', false)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Métricas de satisfação
        $satisfacaoStats = \App\Models\Crm\CrmSatisfacaoResposta::where('cliente_id', $cliente->id)->get();
        $npsMedia = $satisfacaoStats->whereNotNull('nps_score')->avg('nps_score');

        // Funcionários para selects
        $funcionarios = \App\Models\Funcionario::select('id', 'nome')
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();

        return Inertia::render('Crm/Clientes/Show', [
            'cliente'       => $cliente,
            'pedidos'       => $pedidos,
            'timeline'      => $timeline,
            'notas'         => $notas,
            'contatos'      => $contatos,
            'tarefas'       => $tarefas,
            'ocorrencias'   => $ocorrencias,
            'documentos'    => $documentos,
            'alertas'       => $alertas,
            'npsMedia'      => $npsMedia ? round($npsMedia, 1) : null,
            'funcionarios'  => $funcionarios,
        ]);
    }

    /**
     * Atualiza dados CRM do cliente.
     */
    public function update(Request $request, Cliente $cliente)
    {
        $data = $request->validate([
            'vendedor_id'   => 'nullable|exists:funcionarios,id',
            'segmento_crm'  => 'nullable|string|max:60',
            'tags_crm'      => 'nullable|array',
            'risco_churn'   => 'nullable|in:baixo,medio,alto',
            'origem_crm'    => 'nullable|string|max:60',
            'canal_aquisicao'=> 'nullable|string|max:100',
            'data_expiracao_vip' => 'nullable|date',
            'campos_extras_crm'  => 'nullable|array',
        ]);

        // Detecta mudança de vendedor para timeline
        if (isset($data['vendedor_id']) && $data['vendedor_id'] != $cliente->vendedor_id) {
            $nomeVendedor = \App\Models\Funcionario::find($data['vendedor_id'])?->nome ?? 'Nenhum';
            CrmTimelineService::mudancaVendedor($cliente->id, $nomeVendedor);
        }

        $cliente->update($data);

        return back()->with('success', 'Dados CRM atualizados com sucesso!');
    }

    /**
     * Adiciona nota interna ao cliente.
     */
    public function addNota(Request $request, Cliente $cliente)
    {
        $data = $request->validate([
            'titulo'   => 'nullable|string|max:191',
            'conteudo' => 'required|string',
            'privada'  => 'boolean',
        ]);

        $nota = $cliente->crmNotas()->create([
            ...$data,
            'funcionario_id' => Auth::guard('admin')->id(),
        ]);

        CrmTimelineService::anotacaoCriada($cliente->id, $data['titulo'] ?? 'Nota interna');

        return back()->with('success', 'Nota adicionada!');
    }

    /**
     * Adiciona registro de contato/interação ao cliente.
     */
    public function addContato(Request $request, Cliente $cliente)
    {
        $data = $request->validate([
            'tipo'           => 'required|in:whatsapp,email,ligacao,visita,reuniao,videoconferencia,sms,outro',
            'assunto'        => 'nullable|string|max:191',
            'descricao'      => 'nullable|string',
            'duracao_minutos'=> 'nullable|integer|min:1',
            'realizado_em'   => 'required|date',
        ]);

        $cliente->crmContatos()->create([
            ...$data,
            'funcionario_id' => Auth::guard('admin')->id(),
        ]);

        CrmTimelineService::registrar(
            match ($data['tipo']) {
                'ligacao'         => 'ligacao_registrada',
                'visita'          => 'visita_realizada',
                'reuniao',
                'videoconferencia'=> 'reuniao_realizada',
                'whatsapp'        => 'mensagem_enviada',
                'email'           => 'email_enviado',
                default           => 'custom',
            },
            $cliente->id,
            ucfirst($data['tipo']) . ' registrado' . ($data['assunto'] ? ": {$data['assunto']}" : ''),
            ['origem' => 'manual']
        );

        return back()->with('success', 'Contato registrado!');
    }

    /**
     * Faz upload de documento/anexo para o cliente.
     */
    public function addDocumento(Request $request, Cliente $cliente)
    {
        $request->validate([
            'tipo'    => 'required|in:contrato,proposta,foto,anexo,nota_fiscal,comprovante,outro',
            'nome'    => 'required|string|max:191',
            'arquivo' => 'required|file|max:20480', // 20MB
            'descricao'=> 'nullable|string',
        ]);

        $arquivo = $request->file('arquivo');
        $caminho = $arquivo->store("crm/clientes/{$cliente->id}", 'public');

        $cliente->crmDocumentos()->create([
            'tipo'          => $request->tipo,
            'nome'          => $request->nome,
            'caminho'       => $caminho,
            'mime_type'     => $arquivo->getMimeType(),
            'tamanho_bytes' => $arquivo->getSize(),
            'descricao'     => $request->descricao,
            'funcionario_id'=> Auth::guard('admin')->id(),
        ]);

        return back()->with('success', 'Documento adicionado!');
    }
}
