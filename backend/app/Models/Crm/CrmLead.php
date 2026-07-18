<?php

namespace App\Models\Crm;

use App\Models\Cliente;
use App\Models\Funcionario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmLead extends Model
{
    use SoftDeletes;

    protected $table = 'crm_leads';

    protected $fillable = [
        'cliente_id', 'responsavel_id', 'etapa_id',
        'nome', 'email', 'telefone', 'whatsapp', 'empresa',
        'origem', 'interesse', 'temperatura', 'probabilidade',
        'valor_esperado', 'status', 'motivo_perda',
        'proxima_acao', 'data_proxima_acao', 'tags', 'campos_extras',
    ];

    protected $casts = [
        'valor_esperado'    => 'decimal:2',
        'probabilidade'     => 'integer',
        'data_proxima_acao' => 'datetime',
        'tags'              => 'array',
        'campos_extras'     => 'array',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function responsavel()
    {
        return $this->belongsTo(Funcionario::class, 'responsavel_id');
    }

    public function etapa()
    {
        return $this->belongsTo(CrmPipelineEtapa::class, 'etapa_id');
    }

    public function historicoEtapas()
    {
        return $this->hasMany(CrmLeadEtapa::class, 'lead_id')->orderBy('created_at', 'desc');
    }

    public function timeline()
    {
        return $this->hasMany(CrmTimelineEvento::class, 'lead_id')->orderBy('created_at', 'desc');
    }

    public function notas()
    {
        return $this->hasMany(CrmNota::class, 'lead_id')->orderBy('created_at', 'desc');
    }

    public function contatos()
    {
        return $this->hasMany(CrmContato::class, 'lead_id')->orderBy('realizado_em', 'desc');
    }

    public function tarefas()
    {
        return $this->hasMany(CrmTarefa::class, 'lead_id')->orderBy('vencimento_em');
    }

    public function documentos()
    {
        return $this->hasMany(CrmDocumento::class, 'lead_id');
    }

    public function getDiasNaEtapaAttribute(): int
    {
        if (!$this->etapa_id) return 0;
        $ultima = $this->historicoEtapas()->first();
        if ($ultima) {
            return (int) now()->diffInDays($ultima->created_at);
        }
        return (int) now()->diffInDays($this->created_at);
    }
}
