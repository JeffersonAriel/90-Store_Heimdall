<?php

namespace App\Models\Crm;

use App\Models\Cliente;
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
