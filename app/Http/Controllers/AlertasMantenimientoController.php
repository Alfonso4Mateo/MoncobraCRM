<?php

namespace App\Http\Controllers;

use App\Models\EquipoInformatico;
use App\Models\HerramientaPlanta;
use App\Models\AparatoCalibrable;
use App\Models\Evento;
use Illuminate\Http\Request;

class AlertasMantenimientoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $hoy = now()->startOfDay();
        $en30Dias = now()->addDays(30)->endOfDay();
        $hace7Dias = now()->subDays(7)->startOfDay();

        // 1. ALERTAS CRÍTICAS (Caducadas / Vencidas)
        $criticas = collect();

        // -> Planta (OCA o Mantenimiento vencido)
        HerramientaPlanta::where('fecha_inspeccion', '<', $hoy)
            ->orWhere('fecha_mantenimiento', '<', $hoy)
            ->get()->each(function($item) use ($criticas, $hoy) {
                if ($item->fecha_inspeccion && $item->fecha_inspeccion < $hoy) {
                    $criticas->push((object)['modulo' => 'Planta', 'icono' => 'fa-tools', 'nombre' => $item->nombre, 'alerta' => 'Inspección Legal (OCA) Vencida', 'fecha' => $item->fecha_inspeccion, 'url' => route('herramientas.planta.show', $item)]);
                }
                if ($item->fecha_mantenimiento && $item->fecha_mantenimiento < $hoy) {
                    $criticas->push((object)['modulo' => 'Planta', 'icono' => 'fa-wrench', 'nombre' => $item->nombre, 'alerta' => 'Mantenimiento Interno Vencido', 'fecha' => $item->fecha_mantenimiento, 'url' => route('herramientas.planta.show', $item)]);
                }
            });

        // -> IT (EOL o Mantenimiento vencido)
        EquipoInformatico::where('soporte_hasta', '<', $hoy)
            ->orWhere('fecha_mantenimiento', '<', $hoy)
            ->get()->each(function($item) use ($criticas, $hoy) {
                if ($item->soporte_hasta && $item->soporte_hasta < $hoy) {
                    $criticas->push((object)['modulo' => 'IT', 'icono' => 'fa-desktop', 'nombre' => $item->nombre, 'alerta' => 'Ciclo de vida agotado (EOL)', 'fecha' => $item->soporte_hasta, 'url' => route('equipos.show', $item)]);
                }
                if ($item->fecha_mantenimiento && $item->fecha_mantenimiento < $hoy) {
                    $criticas->push((object)['modulo' => 'IT', 'icono' => 'fa-wrench', 'nombre' => $item->nombre, 'alerta' => 'Mantenimiento IT Vencido', 'fecha' => $item->fecha_mantenimiento, 'url' => route('equipos.show', $item)]);
                }
            });

        // -> Metrología (Calibración vencida)
        AparatoCalibrable::where('proxima_calibracion', '<', $hoy)
            ->get()->each(function($item) use ($criticas) {
                $criticas->push((object)['modulo' => 'Metrología', 'icono' => 'fa-ruler-combined', 'nombre' => $item->nombre, 'alerta' => 'Calibración Caducada', 'fecha' => $item->proxima_calibracion, 'url' => route('calibrables.show', $item)]);
            });

        // 2. PREVISIÓN A 30 DÍAS (Próximos vencimientos)
        $proximos = collect();

        HerramientaPlanta::whereBetween('fecha_inspeccion', [$hoy, $en30Dias])
            ->orWhereBetween('fecha_mantenimiento', [$hoy, $en30Dias])
            ->get()->each(function($item) use ($proximos, $hoy, $en30Dias) {
                if ($item->fecha_inspeccion && $item->fecha_inspeccion >= $hoy && $item->fecha_inspeccion <= $en30Dias) {
                    $proximos->push((object)['modulo' => 'Planta', 'icono' => 'fa-tools', 'nombre' => $item->nombre, 'alerta' => 'Inspección Legal Próxima', 'fecha' => $item->fecha_inspeccion, 'url' => route('herramientas.planta.show', $item)]);
                }
                if ($item->fecha_mantenimiento && $item->fecha_mantenimiento >= $hoy && $item->fecha_mantenimiento <= $en30Dias) {
                    $proximos->push((object)['modulo' => 'Planta', 'icono' => 'fa-wrench', 'nombre' => $item->nombre, 'alerta' => 'Preventivo Próximo', 'fecha' => $item->fecha_mantenimiento, 'url' => route('herramientas.planta.show', $item)]);
                }
            });

        EquipoInformatico::whereBetween('soporte_hasta', [$hoy, $en30Dias])->get()->each(function($item) use ($proximos) {
            $proximos->push((object)['modulo' => 'IT', 'icono' => 'fa-desktop', 'nombre' => $item->nombre, 'alerta' => 'Renovación IT Cercana', 'fecha' => $item->soporte_hasta, 'url' => route('equipos.show', $item)]);
        });

        AparatoCalibrable::whereBetween('proxima_calibracion', [$hoy, $en30Dias])->get()->each(function($item) use ($proximos) {
            $proximos->push((object)['modulo' => 'Metrología', 'icono' => 'fa-ruler-combined', 'nombre' => $item->nombre, 'alerta' => 'Calibración Próxima', 'fecha' => $item->proxima_calibracion, 'url' => route('calibrables.show', $item)]);
        });

        // 3. INFORME DE AVERÍAS (Últimos 7 días) - Limitado a las 15 más recientes
        $averias = Evento::with(['eventable', 'usuario'])
            ->whereIn('tipo', ['averia', 'reparacion'])
            ->where('fecha', '>=', $hace7Dias)
            ->orderBy('fecha', 'desc')
            ->limit(15) // <- Cortafuegos de rendimiento
            ->get();

        // Ordenamos las colecciones por fecha y las limitamos a las 25 más urgentes
        $criticas = $criticas->sortBy('fecha')->take(25)->values();
        $proximos = $proximos->sortBy('fecha')->take(25)->values();

        return view('herramientas.alertas.index', compact('criticas', 'proximos', 'averias'));
    }

    public function enviarReporte(\Illuminate\Http\Request $request)
    {
        $config = \App\Models\AlertaConfiguracion::where('modulo', 'mantenimiento')->first();
        
        if (!$config || empty($config->destinatarios)) {
            return back()->with('error', 'No hay destinatarios configurados para enviar el reporte.');
        }

        // Preparamos los parámetros para el comando en segundo plano
        $params = ['--force' => true];
        
        // Si pulsaste un botón específico, le pasamos esa orden al cartero
        if ($request->has('tipo')) {
            $params['--tipo'] = $request->tipo;
        }

        \Illuminate\Support\Facades\Artisan::call('alertas:mantenimiento', $params);

        return back()->with('success', 'El reporte solicitado se ha forzado y enviado a los destinatarios configurados.');
    }

    public function configuracion()
    {
        // Buscamos la configuración o la creamos por defecto si es la primera vez que entran
        $config = \App\Models\AlertaConfiguracion::firstOrCreate(
            ['modulo' => 'mantenimiento'],
            [
                'destinatarios' => [],
                'dia_semana' => 'Lunes',
                'hora_ejecucion' => '08:00',
                'tipos_reporte' => ['caducidades', 'averias']
            ]
        );

        return view('herramientas.alertas.configuracion', compact('config'));
    }

    public function storeConfiguracion(Request $request)
    {
        $request->validate([
            'destinatarios' => 'nullable|array',
            'destinatarios.*' => 'email',
            'dia_semana' => 'required|string',
            'hora_ejecucion' => 'required|date_format:H:i',
            'tipos_reporte' => 'nullable|array',
        ]);

        $config = \App\Models\AlertaConfiguracion::where('modulo', 'mantenimiento')->firstOrFail();
        
        $config->update([
            // Si el array viene vacío (borraron todos los correos), guardamos un array vacío []
            'destinatarios' => $request->destinatarios ?? [],
            'dia_semana' => $request->dia_semana,
            'hora_ejecucion' => $request->hora_ejecucion,
            'tipos_reporte' => $request->tipos_reporte ?? [],
        ]);

        return back()->with('success', 'Configuración de envíos automatizados guardada correctamente.');
    }
}