<?php

namespace App\Mail\Marketing;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConfirmarCadastroCliente extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly string $url) {}

    public function build(): self
    {
        return $this
            ->subject('Confirme sua conta na SOS Mecânica')
            ->view('emails.marketing.confirmar-cadastro');
    }
}
