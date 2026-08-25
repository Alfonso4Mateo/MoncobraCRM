<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setting;
use App\Models\Personal;
use Illuminate\Support\Facades\Mail;
use App\Mail\AlertaPendientesMail;

class NotificarPendientesPRL extends Command
{
    protected $signature = 'cursos:notificar-pendientes';
    protected $description = 'Envía un correo avisando de los trabajadores pendientes de revisión PRL';

    public function handle()
    {
        // 1. Comprobar si el envío de pendientes está habilitado en la configuración
        $enviar = Setting::where('key', 'alertas_prl_enviar_pendientes')->value('value');
        if ($enviar !== null && $enviar === '0') {
            $this->info('El envío de alertas de pendientes está desactivado en la configuración.');
            return;
        }

        // 2. Rescatar los destinatarios
        $emailSetting = Setting::where('key', 'alertas_prl_email')->first();
        if (!$emailSetting || empty($emailSetting->value)) {
            $this->error('No hay correos configurados en el sistema.');
            return;
        }

        $emails = json_decode($emailSetting->value, true) ?? [];
        if (empty($emails)) {
            $this->error('La lista de correos está vacía.');
            return;
        }

        // 3. Buscar a los trabajadores: Solo Activos y NO revisados.
        $pendientes = Personal::where('activo', true)
                              ->where('prl_revisado', false)
                              ->get();

        if ($pendientes->isEmpty()) {
            $this->info('Todo en orden. No hay trabajadores pendientes de revisión. No se enviará correo.');
            return;
        }

        // 4. Enviar el correo
        foreach ($emails as $email) {
            Mail::to($email)->send(new AlertaPendientesMail($pendientes));
        }

        $this->info('Aviso enviado correctamente a ' . count($emails) . ' destinatario(s).');
    }
}