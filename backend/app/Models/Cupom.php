<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cupom extends Model
{
    use HasFactory;

    protected $table = 'cupons';

    protected $fillable = [
        'codigo',
        'tipo',
        'valor',
        'valor_minimo_pedido',
        'limite_uso_total',
        'limite_uso_por_cliente',
        'usos_atuais',
        'validade',
        'ativo',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'valor_minimo_pedido' => 'decimal:2',
        'limite_uso_total' => 'integer',
        'limite_uso_por_cliente' => 'integer',
        'usos_atuais' => 'integer',
        'validade' => 'datetime',
        'ativo' => 'boolean',
    ];

    public function usos()
    {
        return $this->hasMany(UsoCupom::class, 'cupom_id');
    }
}
