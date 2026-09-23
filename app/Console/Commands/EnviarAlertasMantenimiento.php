<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AlertaConfiguracion;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReporteMantenimientoMail;
use Carbon\Carbon;

// Importamos los modelos de los 3 submódulos
use App\Models\EquipoInformatico;
use App\Models\HerramientaPlanta;
use App\Models\AparatoCalibrable;
use App\Models\Evento;

class EnviarAlertasMantenimiento extends Command
{
    // Firma actualizada para recibir los botones del panel
    protected $signature = 'alertas:mantenimiento {--force} {--tipo= : Filtra el envío a caducidades o averias}';
    protected $description = 'Vigila el reloj y envía los reportes automáticos de mantenimiento si toca, o de forma manual si se fuerza.';

    public function handle()
    {
        $config = AlertaConfiguracion::where('modulo', 'mantenimiento')->first();
        
        if (!$config || empty($config->destinatarios)) return;

        Carbon::setLocale('es');
        $hoy = Carbon::now();
        $diaActual = ucfirst($hoy->translatedFormat('l')); 
        $horaActual = $hoy->format('H:i');

        // Lógica del reloj
        $tocaHoy = ($config->dia_semana === $diaActual || $config->dia_semana === 'Diario');
        $tocaHora = (\Carbon\Carbon::parse($config->hora_ejecucion)->format('H:i') === $horaActual);
        
        // Detectamos si el usuario pulsó un botón en la interfaz
        $forzado = $this->option('force');

        // Si pulsamos el botón manual O si el reloj coincide
        if ($forzado || ($tocaHoy && $tocaHora)) {
            $this->info($forzado ? "Ejecución manual forzada..." : "Ejecutando envío programado...");
            
            // 1. Determinamos qué tipo de informe enviar
            $tipos = $config->tipos_reporte ?? [];

            // Si pulsaste un botón específico (ej: Reporte Caducidades), pisamos la configuración general
            if ($this->option('tipo')) {
                if ($this->option('tipo') === 'caducidades') {
                    $tipos = ['caducidades', 'preventivos'];
                } elseif ($this->option('tipo') === 'averias') {
                    $tipos = ['averias'];
                }
            }

            // 2. Extraemos los datos cruzando las 3 bases de datos
            $datos = $this->recopilarDatos($tipos, $hoy);

            // 3. Enviamos el correo a cada destinatario
            foreach ($config->destinatarios as $email) {
                Mail::to($email)->send(new ReporteMantenimientoMail($datos));
            }
            
            $this->info("Reportes enviados correctamente.");
        }
    }

    private function recopilarDatos($tipos, $hoy)
    {
        $datos = ['caducidades' => collect(), 'proximos' => collect(), 'averias' => collect()];
        $hoySoloDia = $hoy->copy()->startOfDay();
        
        // REPORTE DE CADUCIDADES (Incluye Planta, IT y Metrología)
        if (in_array('caducidades', $tipos)) {
            $criticas = collect();

            // Planta
            HerramientaPlanta::where('fecha_inspeccion', '<', $hoySoloDia)
                ->orWhere('fecha_mantenimiento', '<', $hoySoloDia)
                ->get()->each(function($item) use ($criticas, $hoySoloDia) {
                    if ($item->fecha_inspeccion && $item->fecha_inspeccion < $hoySoloDia) {
                        $criticas->push((object)['nombre' => $item->nombre, 'codigo' => $item->codigo ?? $item->id_interno, 'fecha_inspeccion' => $item->fecha_inspeccion]);
                    }
                    if ($item->fecha_mantenimiento && $item->fecha_mantenimiento < $hoySoloDia) {
                        $criticas->push((object)['nombre' => $item->nombre, 'codigo' => $item->codigo ?? $item->id_interno, 'fecha_inspeccion' => $item->fecha_mantenimiento]);
                    }
                });

            // IT
            EquipoInformatico::where('soporte_hasta', '<', $hoySoloDia)
                ->orWhere('fecha_mantenimiento', '<', $hoySoloDia)
                ->get()->each(function($item) use ($criticas, $hoySoloDia) {
                    if ($item->soporte_hasta && $item->soporte_hasta < $hoySoloDia) {
                        $criticas->push((object)['nombre' => $item->nombre, 'codigo' => $item->numero_serie, 'fecha_inspeccion' => $item->soporte_hasta]);
                    }
                    if ($item->fecha_mantenimiento && $item->fecha_mantenimiento < $hoySoloDia) {
                        $criticas->push((object)['nombre' => $item->nombre, 'codigo' => $item->numero_serie, 'fecha_inspeccion' => $item->fecha_mantenimiento]);
                    }
                });

            // Metrología
            AparatoCalibrable::where('proxima_calibracion', '<', $hoySoloDia)
                ->get()->each(function($item) use ($criticas) {
                    $criticas->push((object)['nombre' => $item->nombre, 'codigo' => $item->numero_serie, 'fecha_inspeccion' => $item->proxima_calibracion]);
                });

            $datos['caducidades'] = $criticas->sortBy('fecha_inspeccion')->values();
        }

        // REPORTE DE PREVENTIVOS (Próximos 30 días)
        if (in_array('preventivos', $tipos)) {
            $prev = collect();
            $en30Dias = $hoySoloDia->copy()->addDays(30);

            // Planta
            HerramientaPlanta::whereBetween('fecha_inspeccion', [$hoySoloDia, $en30Dias])
                ->orWhereBetween('fecha_mantenimiento', [$hoySoloDia, $en30Dias])
                ->get()->each(function($item) use ($prev, $hoySoloDia, $en30Dias) {
                    if ($item->fecha_inspeccion >= $hoySoloDia && $item->fecha_inspeccion <= $en30Dias) {
                        $prev->push((object)['nombre' => $item->nombre, 'codigo' => $item->codigo ?? $item->id_interno, 'fecha_inspeccion' => $item->fecha_inspeccion]);
                    }
                    if ($item->fecha_mantenimiento >= $hoySoloDia && $item->fecha_mantenimiento <= $en30Dias) {
                        $prev->push((object)['nombre' => $item->nombre, 'codigo' => $item->codigo ?? $item->id_interno, 'fecha_inspeccion' => $item->fecha_mantenimiento]);
                    }
                });

            // IT
            EquipoInformatico::whereBetween('soporte_hasta', [$hoySoloDia, $en30Dias])
                ->orWhereBetween('fecha_mantenimiento', [$hoySoloDia, $en30Dias])
                ->get()->each(function($item) use ($prev, $hoySoloDia, $en30Dias) {
                    if ($item->soporte_hasta >= $hoySoloDia && $item->soporte_hasta <= $en30Dias) {
                        $prev->push((object)['nombre' => $item->nombre, 'codigo' => $item->numero_serie, 'fecha_inspeccion' => $item->soporte_hasta]);
                    }
                    if ($item->fecha_mantenimiento >= $hoySoloDia && $item->fecha_mantenimiento <= $en30Dias) {
                        $prev->push((object)['nombre' => $item->nombre, 'codigo' => $item->numero_serie, 'fecha_inspeccion' => $item->fecha_mantenimiento]);
                    }
                });

            // Metrología
            AparatoCalibrable::whereBetween('proxima_calibracion', [$hoySoloDia, $en30Dias])
                ->get()->each(function($item) use ($prev) {
                    $prev->push((object)['nombre' => $item->nombre, 'codigo' => $item->numero_serie, 'fecha_inspeccion' => $item->proxima_calibracion]);
                });

            $datos['preventivos'] = $prev->sortBy('fecha_inspeccion')->values();
        }

        // REPORTE DE AVERÍAS Y TALLER (Últimos 7 días)
        if (in_array('averias', $tipos)) {
            $hace7Dias = $hoy->copy()->subDays(7)->startOfDay();
            $datos['averias'] = Evento::with(['eventable', 'usuario'])
                ->whereIn('tipo', ['averia', 'reparacion'])
                ->where('fecha', '>=', $hace7Dias)
                ->orderBy('fecha', 'desc')
                ->get();
        }

        return $datos;
    }
}