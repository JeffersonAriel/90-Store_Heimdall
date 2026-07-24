<?php

namespace App\Mail;

use App\Models\Pedido;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CrmEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public $clienteNome;
    public $assuntoTexto;
    public $mensagemTexto;
    public $pedido;

    public function __construct(string $clienteNome, string $assuntoTexto, string $mensagemTexto, $pedido = null)
    {
        $this->clienteNome = $clienteNome;
        $this->assuntoTexto = $assuntoTexto;
        $this->mensagemTexto = $mensagemTexto;

        if ($pedido) {
            if (is_numeric($pedido)) {
                $this->pedido = Pedido::with(['endereco', 'itens.produto'])->find($pedido);
            } elseif ($pedido instanceof Pedido) {
                $this->pedido = $pedido->loadMissing(['endereco', 'itens.produto']);
            }
        }
    }

    public function build()
    {
        $fromAddress = config('mail.from.address');
        if (empty($fromAddress) || $fromAddress === 'hello@example.com') {
            $fromAddress = 'noreply@90store.com.br';
        }

        $fromName = config('mail.from.name', '90 Store');

        return $this->from($fromAddress, $fromName)
                    ->subject($this->assuntoTexto)
                    ->view('emails.crm_email_html');
    }
}
