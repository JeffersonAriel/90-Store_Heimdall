<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PontoFidelidade extends Model
{
    use HasFactory;

    protected $table = 'pontos_fidelidade';

    protected $fillable = [
        'cliente_id',
        'pedido_id',
        'tipo',
        'quantidade',
        'descricao',
        'expira_em',
    ];

    protected $casts = [
        'quantidade' => 'integer',
        'expira_em' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }
}
