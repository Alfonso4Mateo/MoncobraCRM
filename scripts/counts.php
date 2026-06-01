<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Articulo;
use App\Models\AlbaranCliente;
use App\Models\Presupuesto;
use App\Models\PedidoCliente;
use App\Models\Cliente;
use App\Models\Inventario;

echo 'articulos:' . Articulo::count() . PHP_EOL;
if (class_exists(AlbaranCliente::class)) echo 'albaranes:' . AlbaranCliente::count() . PHP_EOL;
if (class_exists(Presupuesto::class)) echo 'presupuestos:' . Presupuesto::count() . PHP_EOL;
if (class_exists(PedidoCliente::class)) echo 'pedidos:' . PedidoCliente::count() . PHP_EOL;
if (class_exists(Cliente::class)) echo 'clientes:' . Cliente::count() . PHP_EOL;
if (class_exists(Inventario::class)) echo 'inventario:' . Inventario::count() . PHP_EOL;
