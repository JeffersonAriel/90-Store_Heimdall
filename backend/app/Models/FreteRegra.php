<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FreteRegra extends Model
{
    use HasFactory;

    protected $table = 'fretes_regras';

    protected $fillable = [
        'nome',
        'tipo',
        'valor_minimo_gratis',
        'raio_km_local',
        'lat_origem',
        'lng_origem',
        'cep_origem',
        'servicos_locais_json',
        'ativo',
    ];

    protected $casts = [
        'valor_minimo_gratis' => 'decimal:2',
        'raio_km_local' => 'decimal:2',
        'lat_origem' => 'decimal:8',
        'lng_origem' => 'decimal:8',
        'servicos_locais_json' => 'array',
        'ativo' => 'boolean',
    ];
}
