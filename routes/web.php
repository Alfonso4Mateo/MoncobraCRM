<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\AlbaranClienteController;
use App\Http\Controllers\PresupuestoController;
use App\Http\Controllers\BolsaController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\AlbaranProveedorController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\InventarioAccionController;
use App\Http\Controllers\HistoricoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DocumentosController;
use App\Http\Controllers\PersonalController;
use App\Http\Controllers\GestionProyectoController;
use App\Http\Controllers\ProyectoContextController;
use App\Http\Controllers\AlmacenController;
use App\Http\Controllers\ClaseController;
use App\Http\Controllers\ProyectoController;

// Página inicial: redirige al dashboard si hay sesión, o al login si no.
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Rutas de autenticación generadas por Laravel UI.
Auth::routes(['register' => false]);

// Rutas protegidas: todo lo que requiere sesión activa entra aquí.
Route::middleware('auth')->group(function () {
    // Panel principal y preferencias del dashboard.
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/panel-order', [DashboardController::class, 'updatePanelOrder'])->name('dashboard.panel-order.update');
    require __DIR__ . '/modules/projects.php';
    require __DIR__ . '/modules/documents.php';
    require __DIR__ . '/modules/commercial.php';
    require __DIR__ . '/modules/operations.php';
    require __DIR__ . '/modules/cursos.php';
    
});