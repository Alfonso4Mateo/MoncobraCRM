<?php

namespace Database\Seeders;

use App\Models\AlbaranCliente;
use App\Models\Articulo;
use App\Models\Cliente;
use App\Models\Inventario;
use App\Models\PedidoCliente;
use App\Models\Presupuesto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ImportXlsxSeeder extends Seeder
{
    public function run(): void
    {
        // Usamos database_path() para que apunte automáticamente a 'factumon/database/data/'
        // ¡IMPORTANTE! Asegúrate de que el nombre del archivo aquí coincida exactamente con el que has guardado.
        $path = database_path('data/ALADDIN_Puerto_Real_1.5_2026 (1).csv'); 
        // Si lo has renombrado, sería: $path = database_path('data/importacion_principal.csv');

        if (! file_exists($path)) {
            $this->command?->error("Archivo no encontrado en la ruta dinámica: {$path}");
            return;
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($path);

        DB::transaction(function () use ($spreadsheet): void {
            $this->importClients($spreadsheet->getSheetByName('Clientes'));
            $this->importPresupuestos(
                $spreadsheet->getSheetByName('Presupuestos'),
                $spreadsheet->getSheetByName('Detalle_Presupuestos')
            );
            $this->importAlbaranesClientes(
                $spreadsheet->getSheetByName('Albaran_Cliente'),
                $spreadsheet->getSheetByName('Detalle_Albaran_Cliente')
            );
            $this->importArticulos($spreadsheet->getSheetByName('Lista de productos'));
            $this->importInventario($spreadsheet->getSheetByName('Lista de inventario_modelo'));
        });

        $this->command?->info('Workbook import completed.');
    }

    private function importClients(?Worksheet $sheet): void
    {
        if ($sheet === null) {
            return;
        }

        foreach ($this->sheetRows($sheet) as $row) {
            $empresa = trim((string) ($row['cliente'] ?? ''));
            if ($empresa === '') {
                continue;
            }
if (str_starts_with($empresa, 'PROV-')) {
                // Omitimos clientes que parecen ser proveedores
                continue;
            }

            $tipo = strtolower(trim((string) ($row['tipo'] ?? '')));
            if(str_contains($tipo, 'proveedor') || str_contains($tipo, 'prov')) {
                // Omitimos clientes que en su tipo mencionan ser proveedores
                continue;
            }
            
            $cif = trim((string) ($row['cif'] ?? ''));

            // Actualizamos o creamos basándonos en el nombre de la empresa para no duplicar
            Cliente::updateOrCreate(
                ['empresa_nombre' => $empresa],
                [
                    'cif_nif' => $cif !== '' ? $cif : null, // Ya no inventamos CIFs sintéticos
                    'direccion' => trim((string) ($row['direccion'] ?? '')),
                    'localidad' => trim((string) ($row['localidad'] ?? '')),
                    'codigo_postal' => trim((string) ($row['cp'] ?? '')),
                    'telefono' => $this->normalizeContactValue($row['contacto'] ?? null),
                    'email' => $this->extractEmail($row['contacto'] ?? null),
                    'persona_contacto' => $this->extractContactName($row['contacto'] ?? null),
                ]
            );
        }
    }

    private function importPresupuestos(?Worksheet $sheet, ?Worksheet $detailSheet): void
    {
        if ($sheet === null) {
            return;
        }

        $details = $this->groupDetailLines($detailSheet, 'numero');

        foreach ($this->sheetRows($sheet) as $row) {
            $numero = trim((string) ($row['numero'] ?? ''));
            $clienteName = trim((string) ($row['cliente'] ?? ''));
            
            if ($numero === '' || $clienteName === '') {
                continue;
            }

            $clienteId = $this->resolveClienteId($clienteName);
            
            // Si el cliente no existe en la base de datos (ej. es un PROV-*), omitimos el presupuesto
            if ($clienteId === null) {
                continue;
            }

            Presupuesto::updateOrCreate(
                ['numero' => $numero],
                [
                    'documento' => 'presupuesto',
                    'fecha' => $this->excelDate($row['fecha'] ?? null),
                    'cliente_id' => $clienteId,
                    'titulo' => trim((string) ($row['titulo'] ?? '')),
                    'ot' => trim((string) ($row['ot'] ?? '')),
                    'total' => $this->normalizeMoney($row['total'] ?? null),
                    'estado' => $this->normalizeEstado($row['estado'] ?? null, ['pendiente', 'aceptado', 'rechazado', 'pendiente pedido']),
                    'validez_oferta' => trim((string) ($row['observaciones'] ?? '')),
                    'exclusiones' => trim((string) ($row['exclusiones'] ?? '')),
                    'lista_articulos' => $details[$numero] ?? [],
                ]
            );
        }
    }

    private function importAlbaranesClientes(?Worksheet $sheet, ?Worksheet $detailSheet): void
    {
        if ($sheet === null) {
            return;
        }

        $details = $this->groupDetailLines($detailSheet, 'numero');

        foreach ($this->sheetRows($sheet) as $row) {
            $numero = trim((string) ($row['numero'] ?? ''));
            $clienteName = trim((string) ($row['cliente'] ?? ''));
            
            if ($numero === '' || $clienteName === '') {
                continue;
            }

            $clienteId = $this->resolveClienteId($clienteName);

            // Si el cliente no existe en la base de datos, omitimos el albarán
            if ($clienteId === null) {
                continue;
            }

            AlbaranCliente::updateOrCreate(
                ['numero' => $numero],
                [
                    'documento' => 'albaran',
                    'fecha' => $this->excelDate($row['fecha'] ?? null),
                    'cliente_id' => $clienteId,
                    'ot' => trim((string) ($row['pedido_cliente'] ?? ($row['ot'] ?? ''))),
                    'pedido_cliente' => trim((string) ($row['pedido_cliente'] ?? '')),
                    'titulo' => trim((string) ($row['titulo'] ?? '')),
                    'lista_articulos' => $details[$numero] ?? [],
                    'total' => $this->normalizeMoney($row['total'] ?? null),
                    'estado' => $this->normalizeEstado($row['estado'] ?? null, ['aceptado', 'entregado', 'pendiente']),
                ]
            );
        }
    }

    private function importArticulos(?Worksheet $sheet): void
    {
        if ($sheet === null) {
            return;
        }

        foreach ($this->sheetRows($sheet) as $row) {
            $codigo = trim((string) ($row['codigo'] ?? ''));
            $descripcion = trim((string) ($row['descripcion_producto'] ?? ($row['descripci_n_producto'] ?? '')));
            if ($codigo === '' || $descripcion === '') {
                continue;
            }

            Articulo::updateOrCreate(
                ['numero_referencia' => $codigo],
                [
                    'descripcion' => $descripcion,
                    'cantidad' => 1,
                    'medida' => null,
                    'precio_unitario' => $this->normalizeMoney($row['precio_por_unidad'] ?? null),
                    'margen' => 0,
                    'total' => $this->normalizeMoney($row['precio_por_unidad'] ?? null),
                ]
            );
        }
    }

    private function importInventario(?Worksheet $sheet): void
    {
        if ($sheet === null) {
            return;
        }

        foreach ($this->sheetRows($sheet, 3) as $row) {
            $codigo = trim((string) ($row['id_de_inventario'] ?? ''));
            $nombre = trim((string) ($row['nombre'] ?? ''));
            if ($codigo === '' || $nombre === '') {
                continue;
            }

            Inventario::updateOrCreate(
                ['codigo' => $codigo],
                [
                    'nombre' => $nombre,
                    'descripcion' => trim((string) ($row['descripci_n'] ?? ($row['descripcion'] ?? ''))),
                    'referencia_proveedor' => null,
                    'clase' => null,
                    'ubicacion' => null,
                    'almacen' => 'IMPORTADO',
                    'stock_actual' => (int) round($this->normalizeMoney($row['cantidad_en_existencias'] ?? 0)),
                    'stock_minimo' => (int) round($this->normalizeMoney($row['nivel_del_nuevo_pedido'] ?? 0)),
                    'nivel_critico' => (int) round($this->normalizeMoney($row['nivel_del_nuevo_pedido'] ?? 0)),
                ]
            );
        }
    }

    private function sheetRows(Worksheet $sheet, int $headerRow = 1): array
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());

        $headers = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $headerRow;
            $headers[$col] = $this->normalizeHeader($sheet->getCell($coord)->getValue());
        }

        $rows = [];
        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $data = [];
            $empty = true;

            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row;
                $value = $sheet->getCell($coord)->getCalculatedValue();
                if ($value !== null && $value !== '') {
                    $empty = false;
                }
                $data[$headers[$col] ?? ('col_' . $col)] = $value;
            }

            if (! $empty) {
                $rows[] = $data;
            }
        }

        return $rows;
    }

    private function groupDetailLines(?Worksheet $sheet, string $parentKey): array
    {
        if ($sheet === null) {
            return [];
        }

        $grouped = [];
        foreach ($this->sheetRows($sheet) as $row) {
            $parent = trim((string) ($row[$parentKey] ?? ''));
            if ($parent === '') {
                continue;
            }

            $grouped[$parent][] = [
                'descripcion' => trim((string) ($row['descripcion'] ?? '')),
                'cantidad' => $this->normalizeMoney($row['cantidad'] ?? 0),
                'precio_unitario' => $this->normalizeMoney($row['precio_un'] ?? ($row['precio_un_'] ?? ($row['precio'] ?? 0))),
                'total' => $this->normalizeMoney($row['total'] ?? 0),
                'estado' => trim((string) ($row['estado'] ?? '')),
            ];
        }

        return $grouped;
    }

    private function normalizeHeader($value): string
    {
        $value = trim((string) $value);
        $value = str_replace(["\r", "\n", "\t"], ' ', $value);
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/u', '_', $value) ?? $value;
        $value = trim($value, '_');

        return match ($value) {
            'c_odigo_pedido' => 'codigo_pedido',
            'n_umero' => 'numero',
            'n_mero' => 'numero',
            'unidades' => 'cantidad',
            'unid' => 'cantidad',
            'n_o_factura' => 'numero_factura',
            'n_o_albaran' => 'numero_albaran',
            'n_o_oferta' => 'numero_oferta',
            'n_o_delegacion' => 'numero_delegacion',
            default => $value,
        };
    }

    private function excelDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return (new \DateTime((string) $value))->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeMoney($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return round((float) $value, 2);
        }

        $value = str_replace(['.', ' '], ['', ''], trim((string) $value));
        $value = str_replace(',', '.', $value);

        return round((float) $value, 2);
    }

    private function normalizeEstado($value, array $allowed): string
    {
        $value = strtolower(trim((string) $value));
        $value = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $value);

        foreach ($allowed as $candidate) {
            if (str_contains($value, $candidate)) {
                return $candidate;
            }
        }

        return $allowed[0] ?? 'pendiente';
    }

    private function extractEmail($value): ?string
    {
        $text = is_array($value) ? implode(' ', $value) : (string) $value;
        if (preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $text, $m)) {
            return $m[0];
        }

        return null;
    }

    private function extractContactName($value): ?string
    {
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        if (str_contains($text, '<')) {
            $text = trim(explode('<', $text, 2)[0]);
        }

        return $text !== '' ? $text : null;
    }

    private function normalizeContactValue($value): ?string
    {
        $text = trim((string) $value);
        return $text !== '' ? $text : null;
    }

    private function resolveClienteId(string $name): ?int
    {
        // Busca al cliente por su nombre exacto en la BD y devuelve su ID.
        // Si no existe, devuelve null.
        $cliente = Cliente::where('empresa_nombre', $name)->first();
        return $cliente ? $cliente->id : null;
    }
}