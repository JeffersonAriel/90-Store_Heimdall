<?php

namespace App\Models\Crm;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Model;

class CrmAutomacaoLog extends Model
{
    protected $table = 'crm_automacao_logs';

    protected $fillable = [
        'automacao_id', 'cliente_id', 'lead_id',
        'acao_executada', 'status', 'detalhes', 'erro_msg', 'executado_em',
    ];

    protected $casts = [
        'detalhes'     => 'array',
        'executado_em' => 'datetime',
    ];

    public function automacao() { return $this->belongsTo(CrmAutomacao::class, 'automacao_id'); }
    public function cliente()   { return $this->belongsTo(Cliente::class,      'cliente_id'); }
    public function lead()      { return $this->belongsTo(CrmLead::class,      'lead_id'); }
}
