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

    protected $appends = ['gateway_pagamento'];

    public function getGatewayPagamentoAttribute(): string
    {
        // 1. Tenta obter o pagamento mais recente da relação pagamentos
        $pagamentos = $this->relationLoaded('pagamentos') ? $this->pagamentos : $this->pagamentos()->get();
        if ($pagamentos && $pagamentos->isNotEmpty()) {
            $pagamento = $pagamentos->last();
            $gw  = strtolower($pagamento->gateway ?? '');
            $met = strtolower($pagamento->metodo ?? '');

            if ($gw === 'infinitepay' || str_contains($gw, 'infinitepay')) {
                if ($met === 'pix' || str_contains($met, 'pix')) {
                    return 'InfinitePay - Pix';
                }
                if (str_contains($met, 'cartao') || str_contains($met, 'card') || str_contains($met, 'credit') || str_contains($met, 'debit')) {
                    return 'InfinitePay - Cartão';
                }
                return 'InfinitePay';
            }

            if ($gw === 'pix_manual') {
                return 'Pix Manual';
            }

            if ($gw === 'dinheiro') return 'Dinheiro';
            if ($gw === 'cartao_presencial') return 'Cartão Presencial';
            if ($gw === 'mercadopago') return 'Mercado Pago';
            if ($gw === 'pagseguro') return 'PagSeguro';
            if ($gw === 'stripe') return 'Stripe';

            if (!empty($pagamento->gateway)) {
                return ucfirst($pagamento->gateway);
            }
        }

        // 2. Fallback caso não haja registro em pagamentos: verifica observações ou comprovante
        $obs = strtolower($this->observacoes ?? '');
        $comprovante = strtolower($this->url_comprovante_pagamento ?? '');

        if (str_contains($obs, 'infinitepay') || str_contains($comprovante, 'infinitepay') || str_contains($comprovante, 'pay.infinitepay.io')) {
            if (str_contains($obs, 'pix') || str_contains($comprovante, 'pix')) {
                return 'InfinitePay - Pix';
            }
            return 'InfinitePay - Cartão';
        }

        return 'Pix Manual';
    }

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
