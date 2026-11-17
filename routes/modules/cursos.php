<?php

use App\Http\Controllers\CursoController;
use App\Http\Controllers\EpiController;
use App\Http\Controllers\PersonalCursoController;
use App\Http\Controllers\PuestoController;
use Illuminate\Support\Facades\Route;

// =========================================================================
// 1. RUTAS ESTÁTICAS Y ESPECÍFICAS DE CURSOS Y ALERTAS
// =========================================================================
Route::get('cursos/gestion', [CursoController::class, 'gestion'])
    ->name('cursos.gestion')
    ->middleware('permission:cursos.edit');

Route::get('cursos/alertas', [CursoController::class, 'alertasCaducidad'])
    ->name('cursos.alertas')
    ->middleware('permission:cursos.alertas');

// Configuración de alertas de cursos (PRL)
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

// =========================================================================
// 2. RUTAS DINÁMICAS DE CURSOS (RESOURCE Y EXPORTACIÓN)
// =========================================================================
Route::resource('cursos', CursoController::class)
    ->except(['show'])
    ->middleware('permission:cursos.view|cursos.create|cursos.edit|cursos.delete');

Route::get('/cursos/{curso}', [CursoController::class, 'show'])
    ->name('cursos.show')
    ->middleware('permission:cursos.view|cursos.edit');

Route::get('/cursos/{curso}/exportar', [CursoController::class, 'export'])
    ->name('cursos.export')
    ->middleware('permission:cursos.export|cursos.view|cursos.edit');

Route::get('/cursos/{curso}/exportar-pdf', [CursoController::class, 'exportPdf'])
    ->name('cursos.export.pdf')
    ->middleware('permission:cursos.export|cursos.view|cursos.edit');

// =========================================================================
// 3. ACCIONES TRABAJADOR <-> CURSO (HISTORIAL, DIPLOMAS, RENOVACIÓN)
// =========================================================================
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
    ->middleware('permission:cursos.view|cursos.edit');

// =========================================================================
// 4. ACCIONES TRABAJADOR <-> PERFILES FORMATIVOS (PUESTOS)
// =========================================================================
Route::post('/personal/{personal}/puestos', [CursoController::class, 'syncPuestos'])
    ->name('personal.puestos.sync')
    ->middleware('permission:cursos.edit|cursos.normas');

Route::post('/personal/{personal}/puestos/add', [CursoController::class, 'addPuesto'])
    ->name('personal.puestos.add')
    ->middleware('permission:cursos.edit|cursos.normas');

Route::delete('/personal/{personal}/puestos/{puesto}', [CursoController::class, 'removePuesto'])
    ->name('personal.puestos.remove')
    ->middleware('permission:cursos.edit|cursos.normas');

// =========================================================================
// 5. MÓDULO DE PUESTOS Y AUDITORÍAS (PANEL DE NORMAS)
// =========================================================================
Route::get('puestos/{puesto}/auditoria', [PuestoController::class, 'auditoria'])
    ->name('puestos.auditoria')
    ->middleware('permission:cursos.view|cursos.normas|cursos.edit');

Route::get('puestos/{puesto}/exportar-auditoria', [PuestoController::class, 'exportAuditoria'])
    ->name('puestos.auditoria.export')
    ->middleware('permission:cursos.export|cursos.normas|cursos.view');

Route::get('puestos/{puesto}/epis-pdf', [PuestoController::class, 'exportEpisPdf'])
    ->name('puestos.epis.pdf')
    ->middleware('permission:cursos.view|cursos.normas|cursos.export|personal.export');

Route::post('puestos/{puesto}/cursos', [PuestoController::class, 'syncCursos'])
    ->name('puestos.sync-cursos')
    ->middleware('permission:cursos.normas|cursos.edit');

Route::post('puestos/{puesto}/epis', [PuestoController::class, 'syncEpis'])
    ->name('puestos.sync-epis')
    ->middleware('permission:cursos.normas|cursos.edit');

Route::resource('puestos', PuestoController::class)
    ->except(['show'])
    ->middleware('permission:cursos.normas|cursos.edit|cursos.view');

Route::post('puestos/{puesto}/epis-pdf/homologar', [PuestoController::class, 'homologarEpisPdf'])
    ->name('puestos.epis.pdf.homologar')
    ->middleware('permission:cursos.normas|cursos.edit');

Route::get('puestos/{puesto}/epis-pdf/revision/{documento}', [PuestoController::class, 'verRevisionEpisPdf'])
    ->name('puestos.epis.pdf.revision')
    ->middleware('permission:cursos.view|cursos.normas|cursos.export|personal.export');

// =========================================================================
// 6. MÓDULO DE CATÁLOGO DE EPIS (EQUIPOS DE PROTECCIÓN INDIVIDUAL)
// =========================================================================
Route::resource('epis', EpiController::class)
    ->except(['create', 'show', 'edit'])
    ->middleware('permission:epis.gestion');

Route::patch('epis/{epi}/toggle-activo', [EpiController::class, 'toggleActivo'])
    ->name('epis.toggle-activo')
    ->middleware('permission:epis.gestion');