<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pagamento extends Model
{
    use HasFactory;

    protected $table = 'pagamentos';

    protected $fillable = [
        'pedido_id',
        'gateway',
        'gateway_id_externo',
        'metodo',
        'status',
        'valor',
        'payload_json',
        'webhook_json',
        'expires_at',
        'paid_at',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'payload_json' => 'array',
        'webhook_json' => 'array',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }
}
