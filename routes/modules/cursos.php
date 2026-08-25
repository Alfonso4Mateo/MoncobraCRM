<?php

use App\Http\Controllers\CursoController;
use App\Http\Controllers\PersonalCursoController;
use Illuminate\Support\Facades\Route;

// 1. PRIMERO LAS RUTAS ESTÁTICAS Y ESPECÍFICAS
Route::get('cursos/gestion', [CursoController::class, 'gestion'])
    ->name('cursos.gestion')
    ->middleware('permission:cursos.edit'); 

Route::get('cursos/alertas', [CursoController::class, 'alertasCaducidad'])
    ->name('cursos.alertas')
    ->middleware('permission:cursos.alertas'); 

// Configuración de alertas de cursos
Route::get('/cursos/configuracion-alertas', [CursoController::class, 'configAlertas'])
    ->name('cursos.config.alertas')
    ->middleware('permission:cursos.alertas'); 

Route::post('/cursos/configuracion-alertas', [CursoController::class, 'storeConfigAlertas'])
    ->name('cursos.config.alertas.store')
    ->middleware('permission:cursos.alertas'); 

Route::delete('/cursos/configuracion-alertas', [CursoController::class, 'destroyConfigAlertas'])
    ->name('cursos.config.alertas.destroy')
    ->middleware('permission:cursos.alertas'); 

Route::post('/cursos/configuracion-alertas/horario', [CursoController::class, 'storeHorarioAlertas'])
    ->name('cursos.config.alertas.horario')
    ->middleware('permission:cursos.alertas'); 

Route::post('/cursos/configuracion-alertas/enviar-manual', [CursoController::class, 'enviarAlertaManual'])
    ->name('cursos.config.alertas.manual')
    ->middleware('permission:cursos.alertas'); 

Route::post('/cursos/configuracion-alertas/enviar-manual/pendientes', [CursoController::class, 'enviarAlertaManualPendientes'])
    ->name('cursos.config.alertas.manual_pendientes')
    ->middleware('permission:cursos.alertas'); 

// 2. DESPUÉS LAS RUTAS DINÁMICAS (Resource y parámetros)
// Le ponemos el permiso maestro (view). Las restricciones fuertes (borrar, crear) las hace el Controlador por dentro.
Route::resource('cursos', CursoController::class)
    ->except(['show'])
    ->middleware('permission:cursos.view'); 

Route::get('/cursos/{curso}', [CursoController::class, 'show'])
    ->name('cursos.show')
    ->middleware('permission:cursos.view');

Route::get('/cursos/{curso}/exportar', [CursoController::class, 'export'])
    ->name('cursos.export')
    ->middleware('permission:cursos.export'); 

// --- CEREBRO CENTRALIZADO: ACCIONES TRABAJADOR <-> CURSO ---
Route::put('personal/{personal}/cursos', [PersonalCursoController::class, 'update'])
    ->name('personal.cursos.update')
    ->middleware('permission:cursos.edit'); 

Route::delete('personal/{personal}/cursos/{curso}', [PersonalCursoController::class, 'destroy'])
    ->name('personal.cursos.destroy')
    ->middleware('permission:cursos.edit'); 

Route::post('personal/{personal}/cursos/{curso}/renovar', [PersonalCursoController::class, 'renovar'])
    ->name('personal.cursos.renovar')
    ->middleware('permission:cursos.edit'); 

Route::get('personal/{personal}/cursos/{curso}/historial', [PersonalCursoController::class, 'historial'])
    ->name('personal.cursos.historial')
    ->middleware('permission:cursos.view');

// Módulo de Puestos y Auditorías (Panel de Normas)
Route::resource('puestos', App\Http\Controllers\PuestoController::class)
    ->middleware('permission:cursos.normas'); 

Route::post('puestos/{puesto}/cursos', [App\Http\Controllers\PuestoController::class, 'syncCursos'])
    ->name('puestos.sync-cursos')
    ->middleware('permission:cursos.normas'); 

Route::get('puestos/{puesto}/exportar-auditoria', [App\Http\Controllers\PuestoController::class, 'exportAuditoria'])
    ->name('puestos.auditoria.export')
    ->middleware('permission:cursos.export');