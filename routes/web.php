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

// 1. CAMBIO: Nombre de ruta único para la página de bienvenida.
// Antes se llamaba 'dashboard', ahora 'welcome'.
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Authentication Routes
Auth::routes(['register' => false]);

// Protected Routes (Require Authentication)
Route::middleware('auth')->group(function () {
    
    // 2. Dashboard Real
    // Esta es la ruta a la que apunta el RouteServiceProvider que cambiamos antes.
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/panel-order', [DashboardController::class, 'updatePanelOrder'])->name('dashboard.panel-order.update');
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
    Route::get('/herramientas/gestion-proyectos/{proyecto}', [GestionProyectoController::class, 'show'])
        ->middleware('can:manage-projects')
        ->name('herramientas.proyectos.show');
    Route::post('/herramientas/gestion-proyectos', [GestionProyectoController::class, 'store'])
        ->middleware('can:manage-projects')
        ->name('herramientas.proyectos.store');
    Route::put('/herramientas/gestion-proyectos/{proyecto}', [GestionProyectoController::class, 'update'])
        ->middleware('can:manage-projects')
        ->name('herramientas.proyectos.update');
    
    // Profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/change-password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');
    
    // Documentos
    Route::get('documentos', [DocumentosController::class, 'index'])->name('documentos.index');
    Route::get('documentos/cargar', [DocumentosController::class, 'create'])->name('documentos.create');
    Route::post('documentos', [DocumentosController::class, 'store'])->name('documentos.store');
    Route::get('documentos/{documento}/descargar', [DocumentosController::class, 'download'])->name('documentos.download');
    Route::get('documentos/{documento}/preview', [DocumentosController::class, 'preview'])->name('documentos.preview');
    Route::delete('documentos/{documento}', [DocumentosController::class, 'destroy'])->name('documentos.destroy');

    // Recursos CRUD
    Route::resource('clientes', ClienteController::class);
    Route::post('clientes/{cliente}/favorito', [ClienteController::class, 'toggleFavorito'])->name('clientes.favorito.toggle');
    Route::get('albaranes/{albaran}/pdf', [AlbaranClienteController::class, 'pdfViewer'])->name('albaranes.pdf');
    Route::get('albaranes/{albaran}/pdf/file', [AlbaranClienteController::class, 'streamPdf'])->name('albaranes.pdf.file');
    Route::get('albaranes/{albaran}/pdf/download', [AlbaranClienteController::class, 'downloadPdf'])->name('albaranes.pdf.download');
    Route::get('albaranes/{albaran}/preview', [AlbaranClienteController::class, 'preview'])->name('albaranes.preview');
    Route::get('albaranes/{albaran}/editar', [AlbaranClienteController::class, 'edit'])
        ->whereNumber('albaran')
        ->name('albaranes.edit');
    Route::put('albaranes/{albaran}', [AlbaranClienteController::class, 'update'])->name('albaranes.update');
    Route::get('albaranes/{albaran}/pantalla-roja', [AlbaranClienteController::class, 'pantallaRoja'])->name('albaranes.pantalla-roja');
    Route::put('albaranes/{albaran}/pantalla-roja', [AlbaranClienteController::class, 'updatePantallaRoja'])->name('albaranes.pantalla-roja.update');
    Route::patch('albaranes/{albaran}/estado', [AlbaranClienteController::class, 'updateEstado'])->name('albaranes.estado.update');
    Route::resource('albaranes', AlbaranClienteController::class)
        ->parameters(['albaranes' => 'albaran'])
        ->except(['edit', 'update']);
    Route::get('presupuestos/{presupuesto}/preview', [PresupuestoController::class, 'preview'])->name('presupuestos.preview');
    Route::get('presupuestos/{presupuesto}/pdf', [PresupuestoController::class, 'viewPdf'])->name('presupuestos.pdf');
    Route::get('presupuestos/{presupuesto}/pdf/download', [PresupuestoController::class, 'downloadPdf'])->name('presupuestos.pdf.download');
    Route::resource('presupuestos', PresupuestoController::class);
    // Ajuste de correlativo de presupuestos (solo admin/superadmin)
    Route::get('presupuestos/correlativo/editar', [PresupuestoController::class, 'editCorrelativo'])->name('presupuestos.correlativo.edit');
    Route::post('presupuestos/correlativo', [PresupuestoController::class, 'updateCorrelativo'])->name('presupuestos.correlativo.update');
    // Ajuste de correlativo de albaranes (solo admin/superadmin)
    Route::get('albaranes/correlativo/editar', [AlbaranClienteController::class, 'editCorrelativo'])->name('albaranes.correlativo.edit');
    Route::post('albaranes/correlativo', [AlbaranClienteController::class, 'updateCorrelativo'])->name('albaranes.correlativo.update');
    Route::patch('presupuestos/{presupuesto}/estado', [PresupuestoController::class, 'updateEstado'])->name('presupuestos.estado.update');
    Route::resource('bolsa', BolsaController::class);
    Route::resource('proveedores', ProveedorController::class);
    Route::resource('albaranes-proveedores', AlbaranProveedorController::class);
    Route::resource('pedidos', PedidoController::class);
    Route::get('pedidos-clientes/create', [PedidoController::class, 'createCliente'])->name('pedidos-clientes.create');
    Route::post('pedidos-clientes', [PedidoController::class, 'storeCliente'])->name('pedidos-clientes.store');
    Route::get('pedidos-clientes', [PedidoController::class, 'indexClientes'])->name('pedidos-clientes.index');
    Route::get('pedidos-clientes/{pedidoCliente}/preview', [PedidoController::class, 'preview'])->name('pedidos-clientes.preview');
    Route::get('pedidos-clientes/{pedidoCliente}/pdf', [PedidoController::class, 'viewPdf'])->name('pedidos-clientes.pdf');
    Route::get('pedidos-clientes/{pedidoCliente}/pdf/download', [PedidoController::class, 'downloadPdf'])->name('pedidos-clientes.pdf.download');
    Route::patch('pedidos-clientes/{pedidoCliente}/estado', [PedidoController::class, 'updateEstado'])->name('pedidos-clientes.estado.update');
    Route::delete('pedidos-clientes/{pedidoCliente}', [PedidoController::class, 'destroyCliente'])->name('pedidos-clientes.destroy');
    Route::get('pedidos-clientes/data', [PedidoController::class, 'data'])->name('pedidos-clientes.data');
    Route::get('pedidos-clientes/{pedidoCliente}/albaranes', [PedidoController::class, 'albaranesCliente'])->name('pedidos-clientes.albaranes');
    Route::get('pedidos-clientes/{pedidoCliente}', [PedidoController::class, 'showCliente'])->name('pedidos-clientes.show');
    
    // Nota: 'only' limita las rutas generadas para optimizar el sistema.
    Route::resource('productos', ProductoController::class)->only(['index']);

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
    Route::get('almacenes/nuevo', [AlmacenController::class, 'create'])->name('almacenes.create');
    Route::post('almacenes', [AlmacenController::class, 'store'])->name('almacenes.store');
    Route::resource('clases', ClaseController::class);
    
    // Gestión de Usuarios (Solo admin y superadmin)
    Route::resource('users', UserController::class);
    Route::post('users/{user}/change-role', [UserController::class, 'changeRole'])->name('users.changeRole');
    Route::post('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggleActive');
    // Personal (entidad independiente de usuarios)
    Route::get('personal/tallas', [PersonalController::class, 'tallas'])->name('personal.tallas');
    Route::get('personal', [PersonalController::class, 'index'])->name('personal.index');
    Route::get('personal/crear', [PersonalController::class, 'create'])->name('personal.create');
    Route::post('personal', [PersonalController::class, 'store'])->name('personal.store');
    Route::get('personal/{personal}', [PersonalController::class, 'show'])->name('personal.show');
    Route::get('personal/{personal}/edit', [PersonalController::class, 'edit'])->name('personal.edit');
    Route::put('personal/{personal}', [PersonalController::class, 'update'])->name('personal.update');
    Route::delete('personal/{personal}', [PersonalController::class, 'destroy'])->name('personal.destroy');
    
});