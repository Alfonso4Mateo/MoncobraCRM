<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();


// ==========================================
// 1. ALERTAS DE PRL / CURSOS
// ==========================================
if (Schema::hasTable('settings')) {
    
    $dia = Setting::where('key', 'alertas_prl_dia')->value('value') ?? '1';
    $hora = Setting::where('key', 'alertas_prl_hora')->value('value') ?? '08:00';
    $diaSemana = ($dia == 7) ? 0 : $dia;

    Schedule::command('cursos:notificar-caducidades')
            ->weeklyOn($diaSemana, $hora)
            ->withoutOverlapping(); 
}

// ==========================================
// 2. ALERTAS DE MANTENIMIENTO Y CALIBRACIÓN
// ==========================================
// Se ejecuta cada minuto. El comando interno se encarga de comprobar en la tabla
// 'alertas_configuraciones' si coincide el día de la semana y la hora exacta.
Schedule::command('alertas:mantenimiento')
        ->everyMinute()
        ->withoutOverlapping();