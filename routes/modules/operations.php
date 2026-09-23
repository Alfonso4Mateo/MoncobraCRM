<?php

use App\Http\Controllers\AlmacenController;
use App\Http\Controllers\ClaseController;
use App\Http\Controllers\HistoricoController;
use App\Http\Controllers\InventarioAccionController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\PersonalController;
use App\Http\Controllers\PuestoTrabajoController; 
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\QrController;
use App\Http\Controllers\CalibrablesController;
use App\Http\Controllers\EquiposController;
use App\Http\Controllers\HerramientasPlantaController;
use App\Http\Controllers\AlertasMantenimientoController;

// ==========================================
// 0. ACTIVOS, HERRAMIENTAS Y CALIBRACIÓN
// ==========================================

// EQUIPOS INFORMÁTICOS
Route::get('equipos', [EquiposController::class, 'index'])->name('equipos.index');
Route::get('equipos/nuevo', [EquiposController::class, 'create'])->name('equipos.create');
Route::post('equipos', [EquiposController::class, 'store'])->name('equipos.store');
Route::get('equipos/{equipo}', [EquiposController::class, 'show'])->name('equipos.show');
Route::get('equipos/{equipo}/editar', [EquiposController::class, 'edit'])->name('equipos.edit');
Route::put('equipos/{equipo}', [EquiposController::class, 'update'])->name('equipos.update');
Route::delete('equipos/{equipo}', [EquiposController::class, 'destroy'])->name('equipos.destroy');
Route::post('equipos/{equipo}/eventos', [EquiposController::class, 'storeEvento'])->name('equipos.eventos.store');
Route::patch('equipos/{equipo}/estado', [EquiposController::class, 'updateEstado'])->name('equipos.estado');
Route::patch('equipos/{equipo}/asignar', [EquiposController::class, 'updateAsignacion'])->name('equipos.asignar');

// HERRAMIENTAS DE PLANTA
Route::get('herramientas', [HerramientasPlantaController::class, 'index'])->name('herramientas.index');
Route::get('herramientas/nuevo', [HerramientasPlantaController::class, 'create'])->name('herramientas.create');
Route::post('herramientas', [HerramientasPlantaController::class, 'store'])->name('herramientas.store');
Route::get('herramientas/{herramientaPlanta}', [HerramientasPlantaController::class, 'show'])->name('herramientas.planta.show');
Route::get('herramientas/{herramientaPlanta}/editar', [HerramientasPlantaController::class, 'edit'])->name('herramientas.planta.edit');
Route::put('herramientas/{herramientaPlanta}', [HerramientasPlantaController::class, 'update'])->name('herramientas.planta.update');
Route::delete('herramientas/{herramientaPlanta}', [HerramientasPlantaController::class, 'destroy'])->name('herramientas.planta.destroy');
Route::post('herramientas/{herramientaPlanta}/eventos', [HerramientasPlantaController::class, 'storeEvento'])->name('herramientas.planta.eventos.store');
Route::patch('herramientas/{herramientaPlanta}/estado', [HerramientasPlantaController::class, 'updateEstado'])->name('herramientas.planta.estado');
Route::patch('herramientas/{herramientaPlanta}/asignar', [HerramientasPlantaController::class, 'updateAsignacion'])->name('herramientas.planta.asignar');
// Rutas exclusivas de maquinaria
Route::post('herramientas/{herramientaPlanta}/manual', [HerramientasPlantaController::class, 'uploadManual'])->name('herramientas.planta.manual');
Route::post('herramientas/familias', [HerramientasPlantaController::class, 'storeFamilia'])->name('herramientas.familias.store');

// APARATOS CALIBRABLES (Prefijo unificado a 'calibrables')
Route::get('calibrables', [CalibrablesController::class, 'index'])->name('calibrables.index');
Route::get('calibrables/nuevo', [CalibrablesController::class, 'create'])->name('calibrables.create');
Route::post('calibrables', [CalibrablesController::class, 'store'])->name('calibrables.store');
Route::get('calibrables/{aparatoCalibrable}', [CalibrablesController::class, 'show'])->name('calibrables.show');
Route::get('calibrables/{aparatoCalibrable}/editar', [CalibrablesController::class, 'edit'])->name('calibrables.edit');
Route::put('calibrables/{aparatoCalibrable}', [CalibrablesController::class, 'update'])->name('calibrables.update');
Route::delete('calibrables/{aparatoCalibrable}', [CalibrablesController::class, 'destroy'])->name('calibrables.destroy');
Route::post('calibrables/{aparatoCalibrable}/eventos', [CalibrablesController::class, 'storeEvento'])->name('calibrables.eventos.store');
Route::patch('calibrables/{aparatoCalibrable}/estado', [CalibrablesController::class, 'updateEstado'])->name('calibrables.estado');
Route::patch('calibrables/{aparatoCalibrable}/asignar', [CalibrablesController::class, 'updateAsignacion'])->name('calibrables.asignar');

// CENTRO DE CONTROL Y ALERTAS
Route::get('alertas-mantenimiento', [AlertasMantenimientoController::class, 'index'])->name('alertas.index');
Route::get('alertas-mantenimiento/enviar', [AlertasMantenimientoController::class, 'enviarReporte'])->name('alertas.enviar');
Route::get('alertas-mantenimiento/configuracion', [AlertasMantenimientoController::class, 'configuracion'])->name('alertas.configuracion');
Route::post('alertas-mantenimiento/configuracion', [AlertasMantenimientoController::class, 'storeConfiguracion'])->name('alertas.configuracion.store');

// ENLACES CORTOS / QR (Redirecciones dinámicas)
Route::get('activos/{id}/editar', function (int $id) {
    if (App\Models\EquipoInformatico::find($id)) return redirect()->route('equipos.edit', $id);
    return redirect()->route('herramientas.planta.edit', $id);
})->name('herramientas.edit');

Route::get('activos/{id}', function (int $id) {
    if (App\Models\EquipoInformatico::find($id)) return redirect()->route('equipos.show', $id);
    return redirect()->route('herramientas.planta.show', $id);
})->name('herramientas.show');

// ==========================================
// 1. INVENTARIO: ACCIONES, STOCK Y MOVIMIENTOS
// ==========================================
Route::get('inventario/acciones', [InventarioAccionController::class, 'index'])
    ->name('inventario.acciones.index')
    ->middleware('permission:inventario.manage');
Route::get('inventario/acciones/{tipo}/{id}', [InventarioAccionController::class, 'show'])
    ->where('tipo', 'salida|entrada|traslado')
    ->name('inventario.acciones.show')
    ->middleware('permission:inventario.manage');
Route::post('inventario/acciones/{tipo}/{id}/cancelar', [InventarioAccionController::class, 'cancel'])
    ->where('tipo', 'salida|entrada|traslado')
    ->name('inventario.acciones.cancel')
    ->middleware('permission:inventario.manage');
Route::post('inventario/entradas', [InventarioController::class, 'storeEntrada'])
    ->name('inventario.entrada.store')
    ->middleware('permission:inventario.manage');
Route::get('inventario/salidas/nueva', [InventarioController::class, 'createSalida'])
    ->name('inventario.salida.create')
    ->middleware('permission:inventario.manage');
Route::post('inventario/salidas', [InventarioController::class, 'storeSalida'])
    ->name('inventario.salida.store')
    ->middleware('permission:inventario.manage');
Route::get('inventario/salidas/{salida}/documento', [InventarioController::class, 'showSalidaDocumento'])
    ->name('inventario.salida.documento')
    ->middleware('permission:inventario.manage');
Route::get('inventario/traslados/nuevo', [InventarioController::class, 'createTraslado'])
    ->name('inventario.traslado.create')
    ->middleware('permission:inventario.manage');
Route::post('inventario/traslados', [InventarioController::class, 'storeTraslado'])
    ->name('inventario.traslado.store')
    ->middleware('permission:inventario.manage');
Route::get('inventario/nuevo-item', [InventarioController::class, 'createItem'])
    ->name('inventario.item.create')
    ->middleware('permission:inventario.manage');
Route::resource('inventario', InventarioController::class);
Route::get('inventario/historial', [HistoricoController::class, 'index'])
    ->name('inventario.acciones')
    ->middleware('permission:inventario.manage');
Route::resource('historico', HistoricoController::class)->only(['index']);


// ==========================================
// 2. ALMACENES Y CLASIFICACIONES
// ==========================================
Route::get('almacenes/nuevo', [AlmacenController::class, 'create'])
    ->name('almacenes.create')
    ->middleware('permission:inventario.manage');
Route::post('almacenes', [AlmacenController::class, 'store'])
    ->name('almacenes.store')
    ->middleware('permission:inventario.manage');
Route::resource('clases', ClaseController::class);


// ==========================================
// 3. ADMINISTRACIÓN: USUARIOS Y PERMISOS
// ==========================================
Route::resource('users', UserController::class);
Route::post('users/{user}/change-role', [UserController::class, 'changeRole'])
    ->name('users.changeRole')
    ->middleware('permission:users.manage');
Route::post('users/{user}/toggle-active', [UserController::class, 'toggleActive'])
    ->name('users.toggleActive')
    ->middleware('permission:users.manage');
Route::get('users/{user}/permissions', [UserController::class, 'permissionsEdit'])
    ->name('users.permissions.edit')
    ->middleware('permission:users.permissions'); 
Route::put('users/{user}/permissions', [UserController::class, 'permissionsUpdate'])
    ->name('users.permissions.update')
    ->middleware('permission:users.permissions'); 
Route::post('/users/{user}/send-reset-link', [UserController::class, 'sendPasswordResetLink'])
    ->name('users.sendResetLink');


// ==========================================
// 4. GESTIÓN DE PUESTOS DE PERSONAL
// ==========================================
Route::get('personal/puestos-trabajo', [PuestoTrabajoController::class, 'index'])
    ->name('personal.puestos-trabajo.index')
    ->middleware('permission:personal.edit');

Route::post('personal/puestos-trabajo', [PuestoTrabajoController::class, 'store'])
    ->name('personal.puestos-trabajo.store')
    ->middleware('permission:personal.edit');

Route::put('personal/puestos-trabajo/{puestoTrabajo}', [PuestoTrabajoController::class, 'update'])
    ->name('personal.puestos-trabajo.update')
    ->middleware('permission:personal.edit');

Route::delete('personal/puestos-trabajo/{puestoTrabajo}', [PuestoTrabajoController::class, 'destroy'])
    ->name('personal.puestos-trabajo.destroy')
    ->middleware('permission:personal.edit');


// ==========================================
// 5. MÓDULO DE PERSONAL (Rutas estáticas primero)
// ==========================================
Route::get('personal', [PersonalController::class, 'index'])
    ->name('personal.index')
    ->middleware('permission:personal.view');

Route::get('personal/crear', [PersonalController::class, 'create'])
    ->name('personal.create')
    ->middleware('permission:personal.create');

Route::post('personal', [PersonalController::class, 'store'])
    ->name('personal.store')
    ->middleware('permission:personal.create');

Route::get('personal/tallas', [PersonalController::class, 'tallas'])
    ->name('personal.tallas')
    ->middleware('permission:personal.tallas');

Route::get('personal/puestos', [PersonalController::class, 'puestos'])
    ->name('personal.puestos.info')
    ->middleware('permission:personal.view');

Route::post('/personal/cursos/bulk', [PersonalController::class, 'assignBulkCourses'])
    ->name('personal.cursos.bulk')
    ->middleware('permission:cursos.edit'); 

Route::post('/personal/export/bulk', [PersonalController::class, 'exportBulk'])
    ->name('personal.export.bulk')
    ->middleware('permission:personal.export'); 

Route::post('/personal/departamento/bulk', [PersonalController::class, 'updateBulkDepartamento'])
    ->name('personal.departamento.bulk')
    ->middleware('permission:personal.edit');

Route::post('/personal/{personal}/prl-revisado', [PersonalController::class, 'marcarRevisadoPrl'])
    ->name('personal.prl.revisado')
    ->middleware('permission:cursos.edit');

Route::post('/personal/{personal}/toggle-status', [PersonalController::class, 'toggleStatus'])
    ->name('personal.toggleStatus')
    ->middleware('permission:personal.edit');


// ==========================================
// 6. MÓDULO DE PERSONAL (Rutas con parámetros al final)
// ==========================================
Route::get('personal/{personal}', [PersonalController::class, 'show'])
    ->name('personal.show')
    ->middleware('permission:personal.view');

Route::get('personal/{personal}/edit', [PersonalController::class, 'edit'])
    ->name('personal.edit')
    ->middleware('permission:personal.edit');

Route::put('personal/{personal}', [PersonalController::class, 'update'])
    ->name('personal.update')
    ->middleware('permission:personal.edit');

Route::delete('personal/{personal}', [PersonalController::class, 'destroy'])
    ->name('personal.destroy')
    ->middleware('permission:personal.destroy');

Route::post('/personal/{personal}/entregas-epi', [PersonalController::class, 'uploadDocumentosPrl'])
    ->name('personal.entregas_epi.store')
    ->middleware('permission:personal.edit');

Route::delete('/personal/{personal}/entregas-epi/{tipo}', [PersonalController::class, 'destroyDocumentosPrl'])
    ->name('personal.entregas_epi.destroy')
    ->middleware('permission:personal.edit');

Route::delete('/personal/{personal}/historial-prl/{historial}', [PersonalController::class, 'destroyHistorialPrl'])
    ->name('personal.historial_prl.destroy')
    ->middleware('permission:personal.edit');


// ==========================================
// 7. DEPARTAMENTOS Y ACCESORIOS DE CURSOS/PRL
// ==========================================
Route::post('/departamentos', [DepartamentoController::class, 'store'])
    ->name('departamentos.store')
    ->middleware('permission:personal.edit');

Route::delete('/departamentos/{nombre}', [DepartamentoController::class, 'destroy'])
    ->name('departamentos.destroy')
    ->middleware('permission:personal.edit');

Route::post('personal/{personal}/puestos', [App\Http\Controllers\CursoController::class, 'syncPuestos'])
    ->name('personal.puestos.sync')
    ->middleware('permission:cursos.edit'); 

Route::post('personal/{personal}/puestos/add', [App\Http\Controllers\CursoController::class, 'addPuesto'])
    ->name('personal.puestos.add')
    ->middleware('permission:cursos.edit'); 

Route::delete('personal/{personal}/puestos/{puesto}', [App\Http\Controllers\CursoController::class, 'removePuesto'])
    ->name('personal.puestos.remove')
    ->middleware('permission:cursos.edit'); 

Route::get('puestos/{puesto}/auditoria', [App\Http\Controllers\PuestoController::class, 'auditoria'])
    ->name('puestos.auditoria')
    ->middleware('permission:cursos.view');

// ==========================================
// 8. GESTOR DE CÓDIGOS QR 
// ==========================================
 Route::get('/admin/qrs', [App\Http\Controllers\QrController::class, 'index'])
    ->name('qrs.index');
Route::post('/admin/qrs/generar', [App\Http\Controllers\QrController::class, 'store'])
    ->name('qrs.store');
Route::post('/admin/qrs/carpetas', [App\Http\Controllers\QrController::class, 'storeCarpeta'])
    ->name('qrs.carpeta.store');
Route::get('/admin/qrs/{id}/download', [App\Http\Controllers\QrController::class, 'download'])                  
    ->name('qrs.download');
Route::delete('/admin/qrs/{id}', [App\Http\Controllers\QrController::class, 'destroy'])
    ->name('qrs.destroy');
Route::put('/admin/qrs/{id}/mover', [App\Http\Controllers\QrController::class, 'moverCarpeta'])
    ->name('qrs.mover');
Route::delete('/admin/qrs/carpetas/{id}', [App\Http\Controllers\QrController::class, 'destroyCarpeta'])
    ->name('qrs.carpeta.destroy');