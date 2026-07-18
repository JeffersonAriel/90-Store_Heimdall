<?php

namespace App\Models\Crm;

use App\Models\Cliente;
use App\Models\Funcionario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmTarefa extends Model
{
    use SoftDeletes;

    protected $table = 'crm_tarefas';

    protected $fillable = [
        'cliente_id', 'lead_id', 'responsavel_id', 'criado_por', 'automacao_id',
        'titulo', 'descricao', 'tipo', 'status', 'prioridade',
        'vencimento_em', 'concluida_em', 'resultado',
    ];

    protected $casts = [
        'vencimento_em' => 'datetime',
        'concluida_em'  => 'datetime',
    ];

    public function cliente()    { return $this->belongsTo(Cliente::class,    'cliente_id'); }
    public function lead()       { return $this->belongsTo(CrmLead::class,    'lead_id'); }
    public function responsavel(){ return $this->belongsTo(Funcionario::class, 'responsavel_id'); }
    public function criadoPor()  { return $this->belongsTo(Funcionario::class, 'criado_por'); }

    public function scopePendentes($query)
    {
        return $query->whereIn('status', ['pendente', 'em_andamento']);
    }

    public function scopeVencidas($query)
    {
        return $query->where('vencimento_em', '<', now())->where('status', 'pendente');
    }

    public function getAtrasadaAttribute(): bool
    {
        return $this->vencimento_em && $this->vencimento_em->isPast() && $this->status === 'pendente';
    }
}
