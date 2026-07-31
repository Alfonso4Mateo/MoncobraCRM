<?php

use App\Http\Controllers\AlbaranClienteController;
use App\Http\Controllers\AlbaranProveedorController;
use App\Http\Controllers\BolsaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\PresupuestoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ProductoController;
use Illuminate\Support\Facades\Route;

// Clientes y documentos comerciales.
Route::resource('clientes', ClienteController::class);
Route::post('clientes/{cliente}/favorito', [ClienteController::class, 'toggleFavorito'])->name('clientes.favorito.toggle');

// Albaranes de cliente y vistas asociadas.
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

// Correlativo de albaranes (ajuste manual del contador/formato)
Route::get('albaranes/correlativo/editar', [AlbaranClienteController::class, 'editCorrelativo'])->name('albaranes.correlativo.edit');
Route::post('albaranes/correlativo', [AlbaranClienteController::class, 'updateCorrelativo'])->name('albaranes.correlativo.update');

// Presupuestos y correlativos asociados.
Route::get('presupuestos/{presupuesto}/preview', [PresupuestoController::class, 'preview'])->name('presupuestos.preview');
Route::get('presupuestos/{presupuesto}/pdf', [PresupuestoController::class, 'viewPdf'])->name('presupuestos.pdf');
Route::get('presupuestos/{presupuesto}/pdf/download', [PresupuestoController::class, 'downloadPdf'])->name('presupuestos.pdf.download');
Route::resource('presupuestos', PresupuestoController::class);
Route::get('presupuestos/correlativo/editar', [PresupuestoController::class, 'editCorrelativo'])->name('presupuestos.correlativo.edit');
Route::post('presupuestos/correlativo', [PresupuestoController::class, 'updateCorrelativo'])->name('presupuestos.correlativo.update');
Route::patch('presupuestos/{presupuesto}/estado', [PresupuestoController::class, 'updateEstado'])->name('presupuestos.estado.update');

// Proveedores, bolsa y pedidos base.
Route::resource('bolsa', BolsaController::class);
Route::resource('proveedores', ProveedorController::class);
Route::resource('albaranes-proveedores', AlbaranProveedorController::class);
Route::resource('pedidos', PedidoController::class);

// Pedidos de cliente: listado, alta, detalle y PDF.
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
Route::post('pedidos-clientes/{pedidoCliente}/facturar-cuota', [PedidoController::class, 'facturarCuota'])->name('pedidos-clientes.facturar-cuota');
Route::put('/pedidos-clientes/{pedidoCliente}/actualizar-descripcion', [PedidoController::class, 'actualizarDescripcion'])->name('pedidos-clientes.actualizar-descripcion');
Route::delete('/facturacion-manual/{id}', [App\Http\Controllers\PedidoController::class, 'destroyFacturacion'])->name('facturacion-manual.destroy');

// Vistas de catálogo y consultas auxiliares.
Route::resource('productos', ProductoController::class)->only(['index']);