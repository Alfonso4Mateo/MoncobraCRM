<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Presupuesto;
use App\Models\Cliente;
use App\Models\Proyecto;
use Illuminate\Support\Facades\Storage;

// Ensure a proyecto exists
$proyecto = Proyecto::first();
if (!$proyecto) {
    $proyecto = Proyecto::create(['nombre' => 'Proyecto de prueba', 'localizacion' => 'Local']);
}

// Ensure a cliente exists
$cliente = Cliente::first();
if (!$cliente) {
    $cliente = Cliente::create([
        'empresa_nombre' => 'Cliente de Prueba SL',
        'direccion' => 'C/ Ejemplo 1, Ciudad',
        'cif' => 'B00000000',
        'proyecto_id' => $proyecto->id,
    ]);
}

$sampleItems = [
    [
        'articulo' => 'REP-A350',
        'descripcion' => 'Repuestos A350 EoY 2025 3er lote',
        'cantidad' => 1,
        'precio_unitario' => 24000.00,
        'margen' => 0,
        'total' => 24000.00,
    ],
];

$presupuesto = Presupuesto::create([
    'documento' => 'Presupuesto',
    'numero' => 'PR2025-TEST',
    'fecha' => now()->toDateString(),
    'cliente_id' => $cliente->id,
    'proyecto_id' => $proyecto->id,
    'titulo' => 'Presupuesto de prueba generado por script',
    'ot' => null,
    'lista_articulos' => $sampleItems,
    'total' => collect($sampleItems)->sum('total'),
    'estado' => 'pendiente',
]);

// Generate PDF using barryvdh/laravel-dompdf if available
try {
    if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('presupuestos.pdf', compact('presupuesto'));
        $filePath = 'presupuestos/presupuesto-' . $presupuesto->id . '.pdf';
        Storage::disk('public')->put($filePath, $pdf->output());
        $presupuesto->update(['archivo_pdf' => $filePath]);
        echo "PDF generado: storage/app/public/{$filePath}\n";
        // Try to open it (Windows)
        $fullPath = storage_path('app/public/' . $filePath);
        if (PHP_OS_FAMILY === 'Windows') {
            // Use PowerShell Start-Process
            shell_exec('Start-Process "' . addslashes($fullPath) . '"');
        } else {
            // Try xdg-open / open
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'DAR') {
                shell_exec('open ' . escapeshellarg($fullPath));
            } else {
                shell_exec('xdg-open ' . escapeshellarg($fullPath) . ' >/dev/null 2>&1 &');
            }
        }
    } else {
        echo "La librería barryvdh/laravel-dompdf no está disponible.\n";
    }
} catch (Throwable $e) {
    echo "Error generando PDF: " . $e->getMessage() . "\n";
    report($e);
}

echo "Presupuesto creado con ID: {$presupuesto->id}\n";


