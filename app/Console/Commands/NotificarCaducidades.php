<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setting;
use App\Models\Personal;
use Illuminate\Support\Facades\Mail;
use App\Mail\AlertaCursosMail;
use Carbon\Carbon;

class NotificarCaducidades extends Command
{
    protected $signature = 'cursos:notificar-caducidades';
    protected $description = 'Revisa las caducidades de los cursos y envía un correo a los técnicos.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $enviar = Setting::where('key', 'alertas_prl_enviar_caducidades')->value('value');
        if ($enviar !== null && $enviar === '0') {
            $this->info('El envío de alertas de caducidades está desactivado en la configuración.');
            return 0;
        }

        $this->info('--- INICIANDO ESCANEO DE CURSOS ---');

        $emailSetting = Setting::where('key', 'alertas_prl_email')->value('value');
        $decodedEmails = json_decode((string) $emailSetting, true);
        $emails = is_array($decodedEmails)
            ? $decodedEmails
            : (is_string($emailSetting) && trim($emailSetting) !== '' ? [trim($emailSetting)] : []);

        if (empty($emails)) {
            $this->warn('❌ ABORTADO: No hay correos configurados en el panel de ajustes.');
            return 0;
        }

        $caducados = [];
        $enAviso = [];
        $hoy = Carbon::now()->startOfDay();

        // Buscamos personal activo y forzamos la carga de la tabla pivote por si acaso
        $personals = Personal::with(['cursos' => function($q) {
            $q->withPivot('fecha_realizacion', 'apto');
        }])->where('activo', 1)->get();

        $this->info('👥 Trabajadores activos encontrados en BD: ' . $personals->count());

        foreach ($personals as $personal) {
            foreach ($personal->cursos as $curso) {
                if (!empty($curso->pivot->fecha_realizacion) && $curso->meses_validez) {
                    
                    $fRealizacion = Carbon::parse($curso->pivot->fecha_realizacion)->startOfDay();
                    $fCaducidad = $fRealizacion->copy()->addMonths($curso->meses_validez);
                    $fAviso = $fCaducidad->copy()->subDays($curso->dias_aviso_previo ?? 30);

                    // Chivato en consola para ver qué está leyendo
                    $this->line("Analizando a {$personal->name} - Curso: {$curso->nombre} - Caduca: {$fCaducidad->format('d/m/Y')}");

                    if ($hoy->gt($fCaducidad)) {
                        $caducados[] = [
                            'trabajador' => $personal->name . ' ' . $personal->apellido,
                            'curso' => $curso->nombre,
                            'fecha' => $fCaducidad->format('d/m/Y')
                        ];
                    } elseif ($hoy->gte($fAviso)) {
                        $enAviso[] = [
                            'trabajador' => $personal->name . ' ' . $personal->apellido,
                            'curso' => $curso->nombre,
                            'fecha' => $fCaducidad->format('d/m/Y')
                        ];
                    }
                }
            }
        }

        $this->info("⚠️ Total Caducados detectados: " . count($caducados));
        $this->info("⚠️ Total En Aviso detectados: " . count($enAviso));

        if (empty($caducados) && empty($enAviso)) {
            $this->info('✅ Todo en orden. No hay cursos caducados ni en aviso.');
            return 0;
        }

        $this->info('📧 Enviando correo a: ' . implode(', ', $emails) . '...');
        
        foreach ($emails as $email) {
            Mail::to($email)->send(new AlertaCursosMail($caducados, $enAviso));
        }

        $this->info('🚀 ¡Correo enviado con éxito!');
        return 0;
    }
}