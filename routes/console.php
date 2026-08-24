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
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

// Mantenemos el comando por defecto por si lo necesitas
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// 1. Verificamos que la tabla settings exista para evitar que el framework 
// colapse al hacer migraciones desde cero en un servidor nuevo.
if (Schema::hasTable('settings')) {
    
    // 2. Rescatamos la configuración inyectada desde tu panel de control
    $dia = Setting::where('key', 'alertas_prl_dia')->value('value') ?? '1';
    $hora = Setting::where('key', 'alertas_prl_hora')->value('value') ?? '08:00';

    // 3. Traducimos el estándar del panel (1-7) al estándar Cron (0-6)
    // Si el día es 7 (Domingo), lo convertimos en 0. Si no, lo dejamos igual.
    $diaSemana = ($dia == 7) ? 0 : $dia;

    // 4. Programamos el comando con la frecuencia dinámica
    Schedule::command('cursos:notificar-caducidades')
            ->weeklyOn($diaSemana, $hora)
            ->withoutOverlapping(); // Evita que se envíen correos dobles si el servidor se atasca
}