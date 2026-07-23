<?php

namespace App\Mail;

use App\Models\Pedido;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $pedido;
    public $statusLabel;

    public function __construct(Pedido $pedido)
    {
        $this->pedido = $pedido->loadMissing(['cliente', 'endereco', 'itens.produto']);
        $this->statusLabel = static::getStatusLabel($pedido->status);
    }

    public function build()
    {
        $subject = "Atualização do Pedido #{$this->pedido->id} — {$this->statusLabel}";

        return $this->subject($subject)
                    ->markdown('emails.order_status_updated');
    }

    public static function getStatusLabel(string $status): string
    {
        return match ($status) {
            'aguardando_pagamento' => 'Aguardando Pagamento',
            'em_separacao'         => 'Pagamento Confirmado — Em Separação',
            'em_envio'             => 'Em Rota de Envio',
            'enviado'              => 'Pedido Enviado',
            'entregue'             => 'Pedido Entregue',
            'cancelado'            => 'Pedido Cancelado',
            'devolvido'            => 'Pedido Devolvido',
            default                => ucfirst($status),
        };
    }
}
