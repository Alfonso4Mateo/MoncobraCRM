<?php

namespace App\Console\Commands;

use App\Mail\AlertaCursosMail;
use App\Models\Personal;
use App\Models\Proyecto;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class NotificarCaducidades extends Command
{
    protected $signature = 'cursos:notificar-caducidades {--proyecto= : ID del proyecto específico a procesar}';
    protected $description = 'Revisa las caducidades de los cursos por proyecto y envía un correo a los técnicos de cada sede.';

    public function handle(): int
    {
        $proyectoOption = $this->option('proyecto');

        $proyectos = $proyectoOption
            ? Proyecto::where('id', (int) $proyectoOption)->get()
            : Proyecto::orderBy('id')->get();

        if ($proyectos->isEmpty()) {
            $this->warn('No se encontraron proyectos para procesar.');
            return 0;
        }

        $hoy = Carbon::now()->startOfDay();

        foreach ($proyectos as $proyecto) {
            $this->procesarProyecto($proyecto, $hoy, $proyectoOption !== null);
        }

        return 0;
    }

    private function procesarProyecto(Proyecto $proyecto, Carbon $hoy, bool $esManual): void
    {
        $enviar = Setting::where('key', "alertas_prl_enviar_caducidades_proyecto_{$proyecto->id}")->value('value')
            ?? Setting::where('key', 'alertas_prl_enviar_caducidades')->value('value');

        if (!$esManual && $enviar !== null && $enviar === '0') {
            $this->line("[{$proyecto->nombre}] Envío de caducidades desactivado.");
            return;
        }

        $emailSetting = Setting::where('key', "alertas_prl_email_proyecto_{$proyecto->id}")->value('value');
        if ($emailSetting === null && $proyecto->id === (int) Proyecto::orderBy('id')->value('id')) {
            $emailSetting = Setting::where('key', 'alertas_prl_email')->value('value');
        }

        $decodedEmails = json_decode((string) $emailSetting, true);
        $emails = is_array($decodedEmails)
            ? $decodedEmails
            : (is_string($emailSetting) && trim($emailSetting) !== '' ? [trim($emailSetting)] : []);

        if (empty($emails)) {
            $this->line("[{$proyecto->nombre}] Sin destinatarios configurados.");
            return;
        }

        $caducados = [];
        $enAviso = [];

        // Solo trabajadores activos que pertenecen a ESTE proyecto y solo cursos vinculados a ESTE proyecto
        $personals = Personal::where('activo', true)
            ->whereHas('proyectos', fn ($q) => $q->where('proyectos.id', $proyecto->id))
            ->with(['cursos' => function ($q) use ($proyecto) {
                $q->whereHas('proyectos', fn ($pq) => $pq->where('proyectos.id', $proyecto->id))
                  ->withPivot('fecha_realizacion', 'apto');
            }])
            ->get();

        foreach ($personals as $personal) {
            $nombreTrabajador = trim($personal->name . ' ' . $personal->apellido);

            foreach ($personal->cursos as $curso) {
                $esApto = (bool) ($curso->pivot->apto ?? false);
                $fechaRaw = $curso->pivot->fecha_realizacion;

                if (!$esApto && !empty($fechaRaw)) {
                    $fRealizacion = Carbon::parse($fechaRaw)->startOfDay();
                    $caducados[] = [
                        'trabajador' => $nombreTrabajador,
                        'curso' => $curso->nombre,
                        'fecha' => 'No Apto (' . $fRealizacion->format('d/m/Y') . ')',
                    ];
                    continue;
                }

                if ($esApto && !empty($fechaRaw) && $curso->meses_validez) {
                    $fRealizacion = Carbon::parse($fechaRaw)->startOfDay();
                    $fCaducidad = $fRealizacion->copy()->addMonths($curso->meses_validez);
                    $fAviso = $fCaducidad->copy()->subDays($curso->dias_aviso_previo ?? 30);

                    if ($hoy->gt($fCaducidad)) {
                        $caducados[] = [
                            'trabajador' => $nombreTrabajador,
                            'curso' => $curso->nombre,
                            'fecha' => $fCaducidad->format('d/m/Y'),
                        ];
                    } elseif ($hoy->gte($fAviso)) {
                        $enAviso[] = [
                            'trabajador' => $nombreTrabajador,
                            'curso' => $curso->nombre,
                            'fecha' => $fCaducidad->format('d/m/Y'),
                        ];
                    }
                }
            }
        }

        if (empty($caducados) && empty($enAviso)) {
            $this->info("[{$proyecto->nombre}] Sin caducidades ni avisos pendientes.");
            return;
        }

        foreach ($emails as $email) {
            Mail::to($email)->send(
                (new AlertaCursosMail($caducados, $enAviso))
                    ->subject("⚠️ Alerta de Caducidad de Formación (PRL) - {$proyecto->nombre}")
            );
        }

        $this->info("[{$proyecto->nombre}] Correo enviado a: " . implode(', ', $emails));
    }
}