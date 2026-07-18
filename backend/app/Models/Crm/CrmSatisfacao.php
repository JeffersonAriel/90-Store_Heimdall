<?php

namespace App\Models\Crm;

use App\Models\Cliente;
use App\Models\Pedido;
use Illuminate\Database\Eloquent\Model;

class CrmSatisfacaoPesquisa extends Model
{
    protected $table = 'crm_satisfacao_pesquisas';

    protected $fillable = [
        'nome', 'tipo', 'perguntas', 'ativa',
        'disparo_dias_apos_entrega', 'disparo_automatico',
    ];

    protected $casts = [
        'perguntas'           => 'array',
        'ativa'               => 'boolean',
        'disparo_automatico'  => 'boolean',
    ];

    public function respostas()
    {
        return $this->hasMany(CrmSatisfacaoResposta::class, 'pesquisa_id');
    }

    public function getNpsMediaAttribute(): ?float
    {
        return $this->respostas()->whereNotNull('nps_score')->avg('nps_score');
    }

    public function getNpsDistribuicaoAttribute(): array
    {
        $scores = $this->respostas()->whereNotNull('nps_score')->pluck('nps_score');
        $total  = $scores->count();
        if (!$total) return ['promotores' => 0, 'neutros' => 0, 'detratores' => 0, 'nps' => 0];

        $promotores = $scores->where('nps_score', '>=', 9)->count();
        $detratores = $scores->where('nps_score', '<=', 6)->count();
        $neutros    = $total - $promotores - $detratores;

        $nps = round((($promotores - $detratores) / $total) * 100);

        return compact('promotores', 'neutros', 'detratores', 'nps', 'total');
    }
}

class CrmSatisfacaoResposta extends Model
{
    protected $table = 'crm_satisfacao_respostas';

    protected $fillable = [
        'pesquisa_id', 'cliente_id', 'pedido_id',
        'respostas', 'nps_score', 'csat_score', 'ces_score',
        'comentario', 'respondido_em',
    ];

    protected $casts = [
        'respostas'     => 'array',
        'nps_score'     => 'integer',
        'csat_score'    => 'decimal:1',
        'ces_score'     => 'decimal:1',
        'respondido_em' => 'datetime',
    ];

    public function pesquisa() { return $this->belongsTo(CrmSatisfacaoPesquisa::class, 'pesquisa_id'); }
    public function cliente()  { return $this->belongsTo(Cliente::class, 'cliente_id'); }
    public function pedido()   { return $this->belongsTo(Pedido::class,  'pedido_id'); }

    public function getClassificacaoNpsAttribute(): string
    {
        if ($this->nps_score === null) return 'sem_resposta';
        if ($this->nps_score >= 9) return 'promotor';
        if ($this->nps_score >= 7) return 'neutro';
        return 'detrator';
    }
}
