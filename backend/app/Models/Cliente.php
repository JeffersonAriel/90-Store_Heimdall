<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class Cliente extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'clientes';

    protected $fillable = [
        'nome_completo',
        'nome_social',
        'cpf',
        'data_nascimento',
        'email',
        'password',
        'telefone',
        'whatsapp',
        'ativo',
        'referral_code',
        'referral_by',
        'pontos_saldo',
        // ── Campos CRM ──
        'vendedor_id',
        'segmento_crm',
        'tags_crm',
        'campos_extras_crm',
        'pontuacao_crm',
        'ltv',
        'primeiro_pedido_em',
        'ultimo_pedido_em',
        'total_pedidos_count',
        'total_gasto',
        'ticket_medio_crm',
        'media_dias_entre_compras',
        'risco_churn',
        'nps_score_medio',
        'origem_crm',
        'canal_aquisicao',
        'data_expiracao_vip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        // 'cpf' => 'encrypted' removido: substituído por accessor/mutator manual
        // para capturar DecryptException quando APP_KEY muda entre ambientes
        'data_nascimento'        => 'date',
        'ativo'                  => 'boolean',
        'pontos_saldo'           => 'integer',
        // ── Casts CRM ──
        'tags_crm'               => 'array',
        'campos_extras_crm'      => 'array',
        'pontuacao_crm'          => 'integer',
        'total_pedidos_count'    => 'integer',
        'ltv'                    => 'decimal:2',
        'total_gasto'            => 'decimal:2',
        'ticket_medio_crm'       => 'decimal:2',
        'nps_score_medio'        => 'decimal:1',
        'primeiro_pedido_em'     => 'datetime',
        'ultimo_pedido_em'       => 'datetime',
        'data_expiracao_vip'     => 'date',
        'media_dias_entre_compras'=> 'integer',
    ];

    // ── CPF: criptografado manualmente com tratamento de erro ─────────────
    public function getCpfAttribute(?string $value): ?string
    {
        if (empty($value)) return null;
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            // Retorna null se o CPF foi criptografado com outra APP_KEY
            // O cliente precisará atualizar o CPF no perfil
            return null;
        }
    }

    public function setCpfAttribute(?string $value): void
    {
        $this->attributes['cpf'] = $value ? Crypt::encryptString($value) : null;
    }

    // ── Relacionamentos Originais ─────────────────────────────────────────
    public function enderecos()
    {
        return $this->hasMany(EnderecoCliente::class, 'cliente_id');
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'cliente_id');
    }

    public function pontos()
    {
        return $this->hasMany(PontoFidelidade::class, 'cliente_id');
    }

    public function indicador()
    {
        return $this->belongsTo(self::class, 'referral_by');
    }

    public function indicados()
    {
        return $this->hasMany(self::class, 'referral_by');
    }

    // ── Relacionamentos CRM ───────────────────────────────────────────────
    public function vendedor()
    {
        return $this->belongsTo(Funcionario::class, 'vendedor_id');
    }

    public function timelineEventos()
    {
        return $this->hasMany(\App\Models\Crm\CrmTimelineEvento::class, 'cliente_id')
                    ->orderBy('created_at', 'desc');
    }

    public function crmNotas()
    {
        return $this->hasMany(\App\Models\Crm\CrmNota::class, 'cliente_id')
                    ->orderBy('created_at', 'desc');
    }

    public function crmContatos()
    {
        return $this->hasMany(\App\Models\Crm\CrmContato::class, 'cliente_id')
                    ->orderBy('realizado_em', 'desc');
    }

    public function crmTarefas()
    {
        return $this->hasMany(\App\Models\Crm\CrmTarefa::class, 'cliente_id');
    }

    public function crmOcorrencias()
    {
        return $this->hasMany(\App\Models\Crm\CrmOcorrencia::class, 'cliente_id');
    }

    public function crmDocumentos()
    {
        return $this->hasMany(\App\Models\Crm\CrmDocumento::class, 'cliente_id');
    }

    public function crmAlertas()
    {
        return $this->hasMany(\App\Models\Crm\CrmAlerta::class, 'cliente_id');
    }

    public function crmSegmentos()
    {
        return $this->belongsToMany(\App\Models\Crm\CrmSegmento::class, 'crm_segmento_clientes', 'cliente_id', 'segmento_id')
                    ->withPivot('adicionado_em');
    }

    public function satisfacaoRespostas()
    {
        return $this->hasMany(\App\Models\Crm\CrmSatisfacaoResposta::class, 'cliente_id');
    }

    // ── Helpers CRM ───────────────────────────────────────────────────────
    public function getIsVipAttribute(): bool
    {
        return $this->segmento_crm === 'VIP'
            && (!$this->data_expiracao_vip || $this->data_expiracao_vip->isFuture());
    }

    public function getDiasUltimaCompraAttribute(): ?int
    {
        if (!$this->ultimo_pedido_em) return null;
        return (int) $this->ultimo_pedido_em->diffInDays(now());
    }

    public function getCorRiscoChurnAttribute(): string
    {
        return match ($this->risco_churn) {
            'alto'  => '#ef4444',
            'medio' => '#f59e0b',
            'baixo' => '#10b981',
            default => '#94a3b8',
        };
    }
}

