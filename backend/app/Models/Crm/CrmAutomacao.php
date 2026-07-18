<?php

namespace App\Models\Crm;

use App\Models\Cliente;
use App\Models\Funcionario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmAutomacao extends Model
{
    use SoftDeletes;

    protected $table = 'crm_automacoes';

    protected $fillable = [
        'criado_por', 'nome', 'descricao', 'ativa',
        'gatilho', 'delay_dias', 'delay_horas',
        'condicoes', 'acoes',
        'total_execucoes', 'total_sucesso', 'total_erros',
    ];

    protected $casts = [
        'ativa'          => 'boolean',
        'condicoes'      => 'array',
        'acoes'          => 'array',
        'delay_dias'     => 'integer',
        'delay_horas'    => 'integer',
        'total_execucoes'=> 'integer',
        'total_sucesso'  => 'integer',
        'total_erros'    => 'integer',
    ];

    public function criadoPor() { return $this->belongsTo(Funcionario::class, 'criado_por'); }
    public function logs()      { return $this->hasMany(CrmAutomacaoLog::class, 'automacao_id'); }

    public function scopeAtiva($query)
    {
        return $query->where('ativa', true);
    }

    public function getTaxaSucessoAttribute(): float
    {
        if (!$this->total_execucoes) return 0;
        return round(($this->total_sucesso / $this->total_execucoes) * 100, 1);
    }
}


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
