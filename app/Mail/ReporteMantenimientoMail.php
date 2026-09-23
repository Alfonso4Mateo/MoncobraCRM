<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReporteMantenimientoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $datos;

    // Recibimos los datos empaquetados desde el Comando
    public function __construct($datos)
    {
        $this->datos = $datos;
    }

    public function build()
    {
        return $this->subject('Reporte de Alertas y Mantenimiento Industrial')
                    ->view('emails.reporte'); 
    }
}