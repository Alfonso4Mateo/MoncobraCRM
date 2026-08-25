<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AlertaPendientesMail extends Mailable
{
    use Queueable, SerializesModels;

    public $pendientes;

    public function __construct($pendientes)
    {
        $this->pendientes = $pendientes;
    }

    public function build()
    {
        return $this->subject('⚠️ Tienes trabajadores pendientes de revisar (PRL)')
                    ->view('emails.alerta_pendientes');
    }
}