<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CrmEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public $clienteNome;
    public $assuntoTexto;
    public $mensagemTexto;

    public function __construct(string $clienteNome, string $assuntoTexto, string $mensagemTexto)
    {
        $this->clienteNome = $clienteNome;
        $this->assuntoTexto = $assuntoTexto;
        $this->mensagemTexto = $mensagemTexto;
    }

    public function build()
    {
        return $this->subject($this->assuntoTexto)
                    ->markdown('emails.crm_email');
    }
}
