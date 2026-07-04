<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricoStatusPedido extends Model
{
    use HasFactory;

    protected $table = 'historico_status_pedido';

    protected $fillable = [
        'pedido_id',
        'status_anterior',
        'status_novo',
        'funcionario_id',
        'observacao',
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class, 'funcionario_id');
    }
}
