<?php

namespace App\Models\Crm;

use App\Models\Cliente;
use App\Models\Funcionario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmSegmento extends Model
{
    use SoftDeletes;

    protected $table = 'crm_segmentos';

    protected $fillable = [
        'nome', 'descricao', 'cor', 'icone', 'tipo',
        'regras', 'total_clientes', 'atualizado_em', 'ativo',
    ];

    protected $casts = [
        'regras'         => 'array',
        'ativo'          => 'boolean',
        'total_clientes' => 'integer',
        'atualizado_em'  => 'datetime',
    ];

    public function clientes()
    {
        return $this->belongsToMany(Cliente::class, 'crm_segmento_clientes', 'segmento_id', 'cliente_id')
                    ->withPivot('adicionado_em');
    }

    public function scopeAtivo($query)
    {
        return $query->where('ativo', true);
    }
}


class CrmCampanha extends Model
{
    use SoftDeletes;

    protected $table = 'crm_campanhas';

    protected $fillable = [
        'segmento_id', 'template_id', 'criado_por',
        'nome', 'descricao', 'tipo', 'status',
        'conteudo', 'variaveis_valores', 'filtros',
        'agendada_para', 'iniciada_em', 'concluida_em',
        'total_destinatarios', 'total_enviados', 'total_abertos', 'total_erros',
    ];

    protected $casts = [
        'variaveis_valores'   => 'array',
        'filtros'             => 'array',
        'agendada_para'       => 'datetime',
        'iniciada_em'         => 'datetime',
        'concluida_em'        => 'datetime',
        'total_destinatarios' => 'integer',
        'total_enviados'      => 'integer',
        'total_abertos'       => 'integer',
        'total_erros'         => 'integer',
    ];

    public function segmento()  { return $this->belongsTo(CrmSegmento::class,       'segmento_id'); }
    public function template()  { return $this->belongsTo(CrmTemplateMensagem::class,'template_id'); }
    public function criadoPor() { return $this->belongsTo(Funcionario::class,        'criado_por'); }
    public function envios()    { return $this->hasMany(CrmCampanhaEnvio::class,     'campanha_id'); }

    public function getTaxaAberturaAttribute(): float
    {
        if (!$this->total_enviados) return 0;
        return round(($this->total_abertos / $this->total_enviados) * 100, 1);
    }

    public function getTaxaErroAttribute(): float
    {
        if (!$this->total_destinatarios) return 0;
        return round(($this->total_erros / $this->total_destinatarios) * 100, 1);
    }
}


class CrmCampanhaEnvio extends Model
{
    protected $table = 'crm_campanha_envios';

    protected $fillable = [
        'campanha_id', 'cliente_id', 'status', 'enviado_em', 'aberto_em', 'erro_msg',
    ];

    protected $casts = [
        'enviado_em' => 'datetime',
        'aberto_em'  => 'datetime',
    ];

    public function campanha() { return $this->belongsTo(CrmCampanha::class, 'campanha_id'); }
    public function cliente()  { return $this->belongsTo(Cliente::class,     'cliente_id'); }
}
