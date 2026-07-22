<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pedido extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pedidos';

    protected $fillable = [
        'cliente_id',
        'endereco_id',
        'cupom_id',
        'status',
        'codigo_rastreio',
        'url_rastreio',
        'subtotal',
        'desconto_cupom',
        'desconto_pontos',
        'valor_frete',
        'servico_frete_nome',
        'prazo_frete_dias',
        'total',
        'observacoes',
        'url_comprovante_pagamento',
        'motivo_cancelamento',
        'cancelado_por',
        'cancelado_em',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'desconto_cupom' => 'decimal:2',
        'desconto_pontos' => 'decimal:2',
        'valor_frete' => 'decimal:2',
        'total' => 'decimal:2',
        'cancelado_em' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function endereco()
    {
        return $this->belongsTo(EnderecoCliente::class, 'endereco_id');
    }

    public function cupom()
    {
        return $this->belongsTo(Cupom::class, 'cupom_id');
    }

    public function itens()
    {
        return $this->hasMany(ItemPedido::class, 'pedido_id');
    }

    public function pagamentos()
    {
        return $this->hasMany(Pagamento::class, 'pedido_id');
    }

    public function historicoStatus()
    {
        return $this->hasMany(HistoricoStatusPedido::class, 'pedido_id');
    }

    public function funcionarioCancelador()
    {
        return $this->belongsTo(Funcionario::class, 'cancelado_por');
    }
}
