<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsoCupom extends Model
{
    use HasFactory;

    protected $table = 'uso_cupons';

    protected $fillable = [
        'cupom_id',
        'cliente_id',
        'pedido_id',
    ];

    public function cupom()
    {
        return $this->belongsTo(Cupom::class, 'cupom_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }
}
