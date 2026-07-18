<?php

namespace App\Models\Crm;

use App\Models\Cliente;
use App\Models\Funcionario;
use Illuminate\Database\Eloquent\Model;

class CrmAlerta extends Model
{
    protected $table = 'crm_alertas';

    protected $fillable = [
        'cliente_id', 'lead_id', 'responsavel_id',
        'tipo', 'titulo', 'descricao', 'prioridade',
        'lido', 'lido_em', 'resolvido', 'resolvido_em', 'metadata',
    ];

    protected $casts = [
        'lido'        => 'boolean',
        'resolvido'   => 'boolean',
        'lido_em'     => 'datetime',
        'resolvido_em'=> 'datetime',
        'metadata'    => 'array',
    ];

    public function cliente()    { return $this->belongsTo(Cliente::class,    'cliente_id'); }
    public function lead()       { return $this->belongsTo(CrmLead::class,    'lead_id'); }
    public function responsavel(){ return $this->belongsTo(Funcionario::class, 'responsavel_id'); }

    public function scopeNaoLido($query) { return $query->where('lido', false); }
    public function scopePendente($query){ return $query->where('resolvido', false); }
}
