<?php

namespace App\Models\Crm;

use App\Models\Cliente;
use App\Models\Funcionario;
use App\Models\Pedido;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmContato extends Model
{
    protected $table = 'crm_contatos';

    protected $fillable = [
        'cliente_id', 'lead_id', 'funcionario_id',
        'tipo', 'assunto', 'descricao', 'duracao_minutos', 'realizado_em',
    ];

    protected $casts = [
        'realizado_em'    => 'datetime',
        'duracao_minutos' => 'integer',
    ];

    public function cliente()   { return $this->belongsTo(Cliente::class,    'cliente_id'); }
    public function lead()      { return $this->belongsTo(CrmLead::class,    'lead_id'); }
    public function funcionario(){ return $this->belongsTo(Funcionario::class,'funcionario_id'); }
}


// ─────────────────────────────────────────────────────────────────────────────

