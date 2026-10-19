<?php

use App\Http\Controllers\AlbaranClienteController;
use App\Http\Controllers\BolsaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\PresupuestoController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\CentroCosteController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------
// Clientes y documentos comerciales.
// ---------------------------------------------------------
Route::resource('clientes', ClienteController::class);
Route::post('clientes/{cliente}/favorito', [ClienteController::class, 'toggleFavorito'])->name('clientes.favorito.toggle');

// ---------------------------------------------------------
// Albaranes de cliente y vistas asociadas.
// ---------------------------------------------------------
// 1º Rutas estáticas
Route::get('albaranes/correlativo/editar', [AlbaranClienteController::class, 'editCorrelativo'])->name('albaranes.correlativo.edit');
Route::post('albaranes/correlativo', [AlbaranClienteController::class, 'updateCorrelativo'])->name('albaranes.correlativo.update');

// 2º Rutas dinámicas
Route::get('albaranes/{albaran}/pdf', [AlbaranClienteController::class, 'pdfViewer'])->name('albaranes.pdf');
Route::get('albaranes/{albaran}/pdf/file', [AlbaranClienteController::class, 'streamPdf'])->name('albaranes.pdf.file');
Route::get('albaranes/{albaran}/pdf/download', [AlbaranClienteController::class, 'downloadPdf'])->name('albaranes.pdf.download');
Route::get('albaranes/{albaran}/preview', [AlbaranClienteController::class, 'preview'])->name('albaranes.preview');
Route::get('albaranes/{albaran}/editar', [AlbaranClienteController::class, 'edit'])
    ->whereNumber('albaran')
    ->name('albaranes.edit');
Route::put('albaranes/{albaran}', [AlbaranClienteController::class, 'update'])->name('albaranes.update');
Route::patch('albaranes/{albaran}/estado', [AlbaranClienteController::class, 'updateEstado'])->name('albaranes.estado.update');
Route::patch('albaranes/{albaran}/desvincular', [AlbaranClienteController::class, 'desvincular'])->name('albaranes.desvincular');
// 3º Resource
Route::resource('albaranes', AlbaranClienteController::class)
    ->parameters(['albaranes' => 'albaran'])
    ->except(['edit', 'update']);

// ---------------------------------------------------------
// Presupuestos y correlativos asociados.
// ---------------------------------------------------------
// 1º Rutas estáticas (POR ENCIMA DEL RESOURCE)
Route::get('presupuestos/correlativo/editar', [PresupuestoController::class, 'editCorrelativo'])->name('presupuestos.correlativo.edit');
Route::post('presupuestos/correlativo', [PresupuestoController::class, 'updateCorrelativo'])->name('presupuestos.correlativo.update');

// 2º Rutas dinámicas
Route::get('presupuestos/{presupuesto}/preview', [PresupuestoController::class, 'preview'])->name('presupuestos.preview');
Route::get('presupuestos/{presupuesto}/pdf', [PresupuestoController::class, 'viewPdf'])->name('presupuestos.pdf');
Route::get('presupuestos/{presupuesto}/pdf/download', [PresupuestoController::class, 'downloadPdf'])->name('presupuestos.pdf.download');
Route::patch('presupuestos/{presupuesto}/estado', [PresupuestoController::class, 'updateEstado'])->name('presupuestos.estado.update');
Route::get('presupuestos/{presupuesto}/revision', [PresupuestoController::class, 'revision'])->name('presupuestos.revision');

// 3º Resource
Route::resource('presupuestos', PresupuestoController::class);

// ---------------------------------------------------------
// Gestión de Centros de Coste
// ---------------------------------------------------------
Route::get('centros-costes', [CentroCosteController::class, 'index'])->name('centros-costes.index');
Route::post('centros-costes', [CentroCosteController::class, 'store'])->name('centros-costes.store');
Route::delete('centros-costes/{centroCoste}', [CentroCosteController::class, 'destroy'])->name('centros-costes.destroy');

// ---------------------------------------------------------
// Proveedores, bolsa y pedidos base.
// ---------------------------------------------------------
// --- Bolsas globales ---
Route::get('/bolsas', [BolsaController::class, 'index'])->name('bolsas.index');
Route::get('/bolsas/crear', [BolsaController::class, 'create'])->name('bolsas.create');
Route::post('/bolsas', [BolsaController::class, 'store'])->name('bolsas.store');
Route::get('/bolsas/{bolsa}/editar', [BolsaController::class, 'edit'])->name('bolsas.edit');
Route::put('/bolsas/{bolsa}', [BolsaController::class, 'update'])->name('bolsas.update');
Route::get('/bolsas/{bolsa}', [BolsaController::class, 'show'])->name('bolsas.show');
Route::get('/bolsas-fungibles', [BolsaController::class, 'fungiblesDisponibles'])->name('bolsas.fungibles');
Route::get('/lineas-bolsa/{linea}/desglose', [BolsaController::class, 'lineaDesglose'])->name('lineas-bolsa.desglose');
Route::delete('/bolsas/{bolsa}', [BolsaController::class, 'destroy'])->name('bolsas.destroy');
Route::patch('/bolsas/{bolsa}/quitar-partida/{pedido}', [BolsaController::class, 'quitarPartida'])->name('bolsas.quitar-partida');


// ---------------------------------------------------------
// Pedidos de cliente: listado, alta, detalle y PDF.
// ---------------------------------------------------------
// 1º Rutas estáticas
Route::get('pedidos-clientes/create', [PedidoController::class, 'createCliente'])->name('pedidos-clientes.create');
Route::post('pedidos-clientes', [PedidoController::class, 'storeCliente'])->name('pedidos-clientes.store');
Route::get('pedidos-clientes', [PedidoController::class, 'indexClientes'])->name('pedidos-clientes.index');
Route::get('pedidos-clientes/data', [PedidoController::class, 'data'])->name('pedidos-clientes.data');

// 2º Rutas dinámicas (Usando siempre {pedidoCliente} para mantener consistencia)
Route::get('pedidos-clientes/{pedidoCliente}/editar', [PedidoController::class, 'editCliente'])->name('pedidos-clientes.edit');
Route::put('pedidos-clientes/{pedidoCliente}', [PedidoController::class, 'update'])->name('pedidos-clientes.update');
Route::get('pedidos-clientes/{pedidoCliente}/preview', [PedidoController::class, 'preview'])->name('pedidos-clientes.preview');
Route::get('pedidos-clientes/{pedidoCliente}/adjunto', [PedidoController::class, 'verAdjunto'])->name('pedidos-clientes.adjunto');
Route::get('pedidos-clientes/{pedidoCliente}/pdf', [PedidoController::class, 'viewPdf'])->name('pedidos-clientes.pdf');
Route::get('pedidos-clientes/{pedidoCliente}/pdf/download', [PedidoController::class, 'downloadPdf'])->name('pedidos-clientes.pdf.download');
Route::patch('pedidos-clientes/{pedidoCliente}/estado', [PedidoController::class, 'updateEstado'])->name('pedidos-clientes.estado.update'); 
Route::delete('pedidos-clientes/{pedidoCliente}', [PedidoController::class, 'destroyCliente'])->name('pedidos-clientes.destroy');
Route::get('pedidos-clientes/{pedidoCliente}/albaranes', [PedidoController::class, 'albaranesCliente'])->name('pedidos-clientes.albaranes');
Route::get('pedidos-clientes/{pedidoCliente}', [PedidoController::class, 'showCliente'])->name('pedidos-clientes.show');
Route::put('pedidos-clientes/{pedidoCliente}/actualizar-descripcion', [PedidoController::class, 'actualizarDescripcion'])->name('pedidos-clientes.actualizar-descripcion');

// --- Líneas de bolsa por partida (dentro del pedido) ---
Route::get('/pedidos-clientes/{pedidoCliente}/lineas-bolsa', [PedidoController::class, 'lineasBolsaDisponibles'])
    ->name('pedidos-clientes.lineas-bolsa');

// ---------------------------------------------------------
// Vistas de catálogo y consultas auxiliares.
// ---------------------------------------------------------
// Route::resource('productos', ProductoController::class)->only(['index']); // Controlador legado sin uso.
