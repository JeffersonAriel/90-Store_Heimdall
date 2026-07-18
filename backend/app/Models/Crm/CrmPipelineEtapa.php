<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class CrmPipelineEtapa extends Model
{
    protected $table = 'crm_pipeline_etapas';

    protected $fillable = [
        'nome', 'ordem', 'cor', 'probabilidade_default', 'tipo', 'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'ordem' => 'integer',
        'probabilidade_default' => 'integer',
    ];

    public function leads()
    {
        return $this->hasMany(CrmLead::class, 'etapa_id');
    }

    public function scopeAtivo($query)
    {
        return $query->where('ativo', true)->orderBy('ordem');
    }

    public function getTotalLeadsAttribute(): int
    {
        return $this->leads()->where('status', 'ativo')->count();
    }

    public function getValorTotalAttribute()
    {
        return $this->leads()->where('status', 'ativo')->sum('valor_esperado');
    }
}
