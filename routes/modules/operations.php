<?php

use App\Http\Controllers\AlmacenController;
use App\Http\Controllers\ClaseController;
use App\Http\Controllers\HistoricoController;
use App\Http\Controllers\InventarioAccionController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\PersonalController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DepartamentoController;

// Inventario: acciones, stock y movimientos.
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

// Almacenes y clasificaciones.
Route::get('almacenes/nuevo', [AlmacenController::class, 'create'])
    ->name('almacenes.create')
    ->middleware('permission:inventario.manage');
Route::post('almacenes', [AlmacenController::class, 'store'])
    ->name('almacenes.store')
    ->middleware('permission:inventario.manage');
Route::resource('clases', ClaseController::class);

// Administración: usuarios y personal.
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

// Personal como entidad independiente de usuarios.
Route::get('personal/tallas', [PersonalController::class, 'tallas'])
    ->name('personal.tallas')
    ->middleware('permission:personal.tallas');

Route::get('personal', [PersonalController::class, 'index'])
    ->name('personal.index')
    ->middleware('permission:personal.view');

Route::get('personal/crear', [PersonalController::class, 'create'])
    ->name('personal.create')
    ->middleware('permission:personal.create');

Route::post('personal', [PersonalController::class, 'store'])
    ->name('personal.store')
    ->middleware('permission:personal.create');

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

Route::post('/departamentos', [DepartamentoController::class, 'store'])
    ->name('departamentos.store')
    ->middleware('permission:personal.edit');

Route::delete('/departamentos/{nombre}', [DepartamentoController::class, 'destroy'])
    ->name('departamentos.destroy')
    ->middleware('permission:personal.edit');

Route::post('/personal/cursos/bulk', [App\Http\Controllers\PersonalController::class, 'assignBulkCourses'])
    ->name('personal.cursos.bulk')
    ->middleware('permission:cursos.edit'); 

Route::post('/personal/export/bulk', [App\Http\Controllers\PersonalController::class, 'exportBulk'])
    ->name('personal.export.bulk')
    ->middleware('permission:personal.export'); 

Route::post('/personal/departamento/bulk', [App\Http\Controllers\PersonalController::class, 'updateBulkDepartamento'])
    ->name('personal.departamento.bulk')
    ->middleware('permission:personal.edit');

// PROTEGIDAS RUTAS QUE ESTABAN EXPUESTAS (Sin middleware)
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

Route::post('/personal/{personal}/toggle-status', [App\Http\Controllers\PersonalController::class, 'toggleStatus'])
    ->name('personal.toggleStatus'); 