<?php

namespace App\Models\Crm;

use App\Models\Cliente;
use App\Models\Funcionario;
use Illuminate\Database\Eloquent\Model;

class CrmTimelineEvento extends Model
{
    protected $table = 'crm_timeline_eventos';

    protected $fillable = [
        'cliente_id', 'lead_id', 'usuario_id', 'usuario_nome',
        'tipo', 'titulo', 'descricao', 'origem',
        'icone', 'cor', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    // Mapeamento de tipo → ícone e cor padrão
    public static array $tipoConfig = [
        'cliente_criado'     => ['icone' => 'user-plus',       'cor' => '#10b981'],
        'login'              => ['icone' => 'log-in',           'cor' => '#6366f1'],
        'pedido_criado'      => ['icone' => 'shopping-cart',    'cor' => '#3b82f6'],
        'pedido_cancelado'   => ['icone' => 'x-circle',         'cor' => '#ef4444'],
        'pagamento_aprovado' => ['icone' => 'check-circle',     'cor' => '#10b981'],
        'pagamento_recusado' => ['icone' => 'alert-circle',     'cor' => '#f97316'],
        'boleto_vencido'     => ['icone' => 'clock',            'cor' => '#f59e0b'],
        'pix_pago'           => ['icone' => 'zap',              'cor' => '#10b981'],
        'produto_entregue'   => ['icone' => 'package',          'cor' => '#10b981'],
        'mensagem_enviada'   => ['icone' => 'message-circle',   'cor' => '#6366f1'],
        'email_enviado'      => ['icone' => 'mail',             'cor' => '#6366f1'],
        'cupom_utilizado'    => ['icone' => 'tag',              'cor' => '#f59e0b'],
        'ticket_aberto'      => ['icone' => 'alert-triangle',   'cor' => '#f97316'],
        'anotacao_criada'    => ['icone' => 'edit',             'cor' => '#94a3b8'],
        'visita_realizada'   => ['icone' => 'map-pin',          'cor' => '#8b5cf6'],
        'ligacao_registrada' => ['icone' => 'phone',            'cor' => '#3b82f6'],
        'reuniao_realizada'  => ['icone' => 'users',            'cor' => '#8b5cf6'],
        'alteracao_cadastral'=> ['icone' => 'edit-2',           'cor' => '#94a3b8'],
        'mudanca_vendedor'   => ['icone' => 'user-check',       'cor' => '#6366f1'],
        'whatsapp_recebido'  => ['icone' => 'message-square',   'cor' => '#25d366'],
        'proposta_enviada'   => ['icone' => 'file-text',        'cor' => '#3b82f6'],
        'tarefa_criada'      => ['icone' => 'check-square',     'cor' => '#f59e0b'],
        'nota_criada'        => ['icone' => 'file',             'cor' => '#94a3b8'],
        'campanha_enviada'   => ['icone' => 'send',             'cor' => '#8b5cf6'],
        'pesquisa_respondida'=> ['icone' => 'bar-chart-2',      'cor' => '#10b981'],
        'carrinho_abandonado'=> ['icone' => 'shopping-cart',    'cor' => '#f97316'],
        'produto_visualizado'=> ['icone' => 'eye',              'cor' => '#6366f1'],
        'etapa_pipeline'     => ['icone' => 'git-branch',       'cor' => '#8b5cf6'],
        'lead_criado'        => ['icone' => 'user-plus',        'cor' => '#3b82f6'],
        'lead_convertido'    => ['icone' => 'award',            'cor' => '#10b981'],
        'automacao_executada'=> ['icone' => 'cpu',              'cor' => '#6366f1'],
        'custom'             => ['icone' => 'circle',           'cor' => '#94a3b8'],
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function lead()
    {
        return $this->belongsTo(CrmLead::class, 'lead_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Funcionario::class, 'usuario_id');
    }

    public function getIconeResolvidoAttribute(): string
    {
        if ($this->icone) return $this->icone;
        return static::$tipoConfig[$this->tipo]['icone'] ?? 'circle';
    }

    public function getCorResolvidaAttribute(): string
    {
        if ($this->cor) return $this->cor;
        return static::$tipoConfig[$this->tipo]['cor'] ?? '#94a3b8';
    }
}
