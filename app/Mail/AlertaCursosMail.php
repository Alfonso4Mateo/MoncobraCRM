<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AlertaCursosMail extends Mailable
{
    use Queueable, SerializesModels;

    public $caducados;
    public $enAviso;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($caducados, $enAviso)
    {
        $this->caducados = $caducados;
        $this->enAviso = $enAviso;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('⚠️ Alerta de Caducidad de Formación (PRL)')
                    ->view('emails.alertas_cursos');
    }
}