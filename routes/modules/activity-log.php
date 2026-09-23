<?php

use App\Http\Controllers\ActivityLogController;
use Illuminate\Support\Facades\Route;

// Cambiamos 'herramientas/auditoria' por 'auditoria'
Route::get('auditoria', [ActivityLogController::class, 'index'])
    ->name('activity-log.index')
    ->middleware('permission:activitylog.view');