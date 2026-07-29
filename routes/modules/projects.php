<?php

use App\Http\Controllers\GestionProyectoController;
use App\Http\Controllers\ProyectoContextController;
use App\Http\Controllers\ProyectoController;
use Illuminate\Support\Facades\Route;

// Contexto y herramientas de proyectos.
Route::get('/proyectos/{proyecto}/seleccionar', [ProyectoContextController::class, 'seleccionar'])
    ->name('proyectos.seleccionar');
Route::get('/herramientas/gestion-proyectos', [GestionProyectoController::class, 'index'])
    ->middleware('can:manage-projects')
    ->name('herramientas.proyectos.index');
Route::get('/herramientas/gestion-proyectos/crear', [GestionProyectoController::class, 'create'])
    ->middleware('can:manage-projects')
    ->name('herramientas.proyectos.create');
Route::get('/herramientas/gestion-proyectos/{proyecto}/editar', [GestionProyectoController::class, 'edit'])
    ->middleware('can:manage-projects')
    ->name('herramientas.proyectos.edit');
Route::get('/herramientas/gestion-proyectos/{proyecto}', [ProyectoController::class, 'show'])
    ->middleware('can:manage-projects')
    ->name('herramientas.proyectos.show');
Route::post('/herramientas/gestion-proyectos', [GestionProyectoController::class, 'store'])
    ->middleware('can:manage-projects')
    ->name('herramientas.proyectos.store');
Route::put('/herramientas/gestion-proyectos/{proyecto}', [GestionProyectoController::class, 'update'])
    ->middleware('can:manage-projects')
    ->name('herramientas.proyectos.update');
Route::post('/herramientas/gestion-proyectos/{proyecto}/assign-user', [ProyectoController::class, 'assignUser'])
    ->middleware('can:manage-projects')
    ->name('herramientas.proyectos.assignUser');