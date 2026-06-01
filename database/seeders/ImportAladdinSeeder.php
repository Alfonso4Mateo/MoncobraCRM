<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Articulo;

class ImportAladdinSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = 'C:\\Users\\Usuario\\Downloads\\aladdin_temporal.csv';

        if (!file_exists($path)) {
            $this->command->error("CSV not found at {$path}");
            return;
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            $this->command->error('Unable to open CSV file.');
            return;
        }

        $row = 0;
        $inserted = 0;
        $failures = [];

        while (($data = fgetcsv($handle, 0, ';')) !== false) {
            $row++;
            
            // Skip empty lines
            if (count($data) === 1 && trim($data[0]) === '') {
                continue;
            }

            // Header detection
            if ($row === 1) {
                $first = strtolower(trim($data[0] ?? ''));
                if (str_contains($first, 'número') || str_contains($first, 'numero')) {
                    continue; // header
                }
            }

            // Normalize columns (some rows may use different column counts)
            $numero = isset($data[0]) ? trim($data[0]) : null;
            $descripcion = isset($data[1]) ? trim($data[1]) : null;
            $cantidadRaw = isset($data[2]) ? trim($data[2]) : null;
            $precioRaw = isset($data[3]) ? trim($data[3]) : null;
            $totalRaw = isset($data[4]) ? trim($data[4]) : null;
            $estado = isset($data[5]) ? trim($data[5]) : null; // Por si decides usarlo a futuro

            // Skip rows without descripcion
            if ($descripcion === null || $descripcion === '') {
                $failures[] = "Row {$row}: missing descripcion";
                continue;
            }

            // Normalize numeric values: replace comma with dot
            $normalize = function (?string $s) {
                if ($s === null) {
                    return 0;
                }
                $s = trim($s);
                // Remove thousands separators (spaces)
                $s = str_replace(' ', '', $s);
                $s = str_replace([','], ['.'], $s);
                // If empty after cleaning, return 0
                if ($s === '' || $s === '-') {
                    return 0;
                }
                return (float) $s;
            };

            $cantidad = $normalize($cantidadRaw);
            $precio = $normalize($precioRaw);
            $total = $normalize($totalRaw);

            try {
                // Usamos updateOrCreate en lugar de create para evitar datos duplicados 
                // si el seeder se interrumpe y tienes que volver a lanzarlo.
                Articulo::updateOrCreate(
                    ['numero_referencia' => $numero ?? 'SIN-REF-' . uniqid()],
                    [
                        'proyecto_id' => null,
                        'descripcion' => $descripcion,
                        'cantidad' => $cantidad,
                        'medida' => null,
                        'precio_unitario' => $precio,
                        'margen' => 0,
                        'total' => $total,
                    ]
                );

                $inserted++;
            } catch (\Throwable $e) {
                $failures[] = "Row {$row}: " . $e->getMessage();
            }
        }

        fclose($handle);

        $this->command->info("Import finished. Inserted or Updated: {$inserted}. Failures: " . count($failures));
        if (count($failures) > 0) {
            foreach ($failures as $f) {
                $this->command->error($f);
            }
        }
    }
}