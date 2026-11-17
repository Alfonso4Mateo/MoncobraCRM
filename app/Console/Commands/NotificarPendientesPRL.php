<?php

namespace App\Console\Commands;

use App\Mail\AlertaPendientesMail;
use App\Models\Personal;
use App\Models\Proyecto;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class NotificarPendientesPRL extends Command
{
    protected $signature = 'cursos:notificar-pendientes {--proyecto= : ID del proyecto específico a procesar}';
    protected $description = 'Envía un correo por proyecto avisando de los trabajadores pendientes de revisión PRL';

    public function handle(): int
    {
        $proyectoOption = $this->option('proyecto');

        $proyectos = $proyectoOption
            ? Proyecto::where('id', (int) $proyectoOption)->get()
            : Proyecto::orderBy('id')->get();

        foreach ($proyectos as $proyecto) {
            $this->procesarProyecto($proyecto, $proyectoOption !== null);
        }

        return 0;
    }

    private function procesarProyecto(Proyecto $proyecto, bool $esManual): void
    {
        $enviar = Setting::where('key', "alertas_prl_enviar_pendientes_proyecto_{$proyecto->id}")->value('value')
            ?? Setting::where('key', 'alertas_prl_enviar_pendientes')->value('value');

        if (!$esManual && $enviar !== null && $enviar === '0') {
            $this->line("[{$proyecto->nombre}] Envío de pendientes desactivado.");
            return;
        }

        $emailSetting = Setting::where('key', "alertas_prl_email_proyecto_{$proyecto->id}")->value('value');
        if ($emailSetting === null && $proyecto->id === (int) Proyecto::orderBy('id')->value('id')) {
            $emailSetting = Setting::where('key', 'alertas_prl_email')->value('value');
        }

        $emails = json_decode((string) $emailSetting, true) ?? [];
        if (empty($emails)) {
            $this->line("[{$proyecto->nombre}] Lista de correos vacía.");
            return;
        }

        $pendientes = Personal::where('activo', true)
            ->whereHas('proyectos', fn ($q) => $q->where('proyectos.id', $proyecto->id))
            ->where(function ($q) {
                $q->where('prl_revisado', false)
                    ->orWhereNull('prl_revisado');
            })
            ->get();

        if ($pendientes->isEmpty()) {
            $this->info("[{$proyecto->nombre}] Sin trabajadores pendientes de revisión.");
            return;
        }

        foreach ($emails as $email) {
            Mail::to($email)->send(
                (new AlertaPendientesMail($pendientes))
                    ->subject("👷 Trabajadores Pendientes de Revisión PRL - {$proyecto->nombre}")
            );
        }

        $this->info("[{$proyecto->nombre}] Aviso enviado a " . count($emails) . ' destinatario(s).');
    }
}