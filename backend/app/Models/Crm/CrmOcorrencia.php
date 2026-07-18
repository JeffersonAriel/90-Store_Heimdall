<?php

namespace App\Models\Crm;

use App\Models\Cliente;
use App\Models\Funcionario;
use App\Models\Pedido;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmOcorrencia extends Model
{
    use SoftDeletes;

    protected $table = 'crm_ocorrencias';

    protected $fillable = [
        'cliente_id', 'lead_id', 'pedido_id',
        'responsavel_id', 'resolvido_por',
        'tipo', 'assunto', 'descricao', 'status', 'prioridade', 'resolvido_em',
    ];

    protected $casts = [
        'resolvido_em' => 'datetime',
    ];

    public function cliente()    { return $this->belongsTo(Cliente::class,    'cliente_id'); }
    public function lead()       { return $this->belongsTo(CrmLead::class,    'lead_id'); }
    public function pedido()     { return $this->belongsTo(Pedido::class,     'pedido_id'); }
    public function responsavel(){ return $this->belongsTo(Funcionario::class, 'responsavel_id'); }
    public function resolvidoPor(){ return $this->belongsTo(Funcionario::class,'resolvido_por'); }

    public function getTempoResolucaoAttribute(): ?int
    {
        if (!$this->resolvido_em) return null;
        return (int) $this->created_at->diffInHours($this->resolvido_em);
    }
}
