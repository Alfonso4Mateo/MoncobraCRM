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
Route::get('inventario/acciones', [InventarioAccionController::class, 'index'])->name('inventario.acciones.index');
Route::get('inventario/acciones/{tipo}/{id}', [InventarioAccionController::class, 'show'])
    ->where('tipo', 'salida|entrada|traslado')
    ->name('inventario.acciones.show');
Route::post('inventario/acciones/{tipo}/{id}/cancelar', [InventarioAccionController::class, 'cancel'])
    ->where('tipo', 'salida|entrada|traslado')
    ->name('inventario.acciones.cancel');

Route::post('inventario/entradas', [InventarioController::class, 'storeEntrada'])->name('inventario.entrada.store');
Route::get('inventario/salidas/nueva', [InventarioController::class, 'createSalida'])->name('inventario.salida.create');
Route::post('inventario/salidas', [InventarioController::class, 'storeSalida'])->name('inventario.salida.store');
Route::get('inventario/salidas/{salida}/documento', [InventarioController::class, 'showSalidaDocumento'])->name('inventario.salida.documento');
Route::get('inventario/traslados/nuevo', [InventarioController::class, 'createTraslado'])->name('inventario.traslado.create');
Route::post('inventario/traslados', [InventarioController::class, 'storeTraslado'])->name('inventario.traslado.store');
Route::get('inventario/nuevo-item', [InventarioController::class, 'createItem'])->name('inventario.item.create');
Route::resource('inventario', InventarioController::class);
Route::get('inventario/historial', [HistoricoController::class, 'index'])->name('inventario.acciones');
Route::resource('historico', HistoricoController::class)->only(['index']);

// Almacenes y clasificaciones.
Route::get('almacenes/nuevo', [AlmacenController::class, 'create'])->name('almacenes.create');
Route::post('almacenes', [AlmacenController::class, 'store'])->name('almacenes.store');
Route::resource('clases', ClaseController::class);

// Administración: usuarios y personal.
Route::resource('users', UserController::class);
Route::post('users/{user}/change-role', [UserController::class, 'changeRole'])->name('users.changeRole');
Route::post('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggleActive');

// Personal como entidad independiente de usuarios.
Route::get('personal/tallas', [PersonalController::class, 'tallas'])->name('personal.tallas');
Route::get('personal', [PersonalController::class, 'index'])->name('personal.index');
Route::get('personal/crear', [PersonalController::class, 'create'])->name('personal.create');
Route::post('personal', [PersonalController::class, 'store'])->name('personal.store');
Route::get('personal/{personal}', [PersonalController::class, 'show'])->name('personal.show');
Route::get('personal/{personal}/edit', [PersonalController::class, 'edit'])->name('personal.edit');
Route::put('personal/{personal}', [PersonalController::class, 'update'])->name('personal.update');
Route::delete('personal/{personal}', [PersonalController::class, 'destroy'])->name('personal.destroy');
Route::post('/departamentos', [DepartamentoController::class, 'store'])->name('departamentos.store');
Route::delete('/departamentos/{nombre}', [DepartamentoController::class, 'destroy'])->name('departamentos.destroy');