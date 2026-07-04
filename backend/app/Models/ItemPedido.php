<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemPedido extends Model
{
    use HasFactory;

    protected $table = 'itens_pedido';

    public $timestamps = false;

    protected $fillable = [
        'pedido_id',
        'produto_id',
        'variacao_id',
        'nome_snapshot',
        'sku_snapshot',
        'tipo_estoque_snapshot',
        'preco_custo_snapshot',
        'preco_venda_snapshot',
        'quantidade',
        'subtotal',
    ];

    protected $casts = [
        'preco_custo_snapshot' => 'decimal:2',
        'preco_venda_snapshot' => 'decimal:2',
        'quantidade' => 'integer',
        'subtotal' => 'decimal:2',
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    public function variacao()
    {
        return $this->belongsTo(VariacaoProduto::class, 'variacao_id');
    }
}
