<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;

$path = 'C:\\Users\\Usuario\\Downloads\\ALADDIN_Puerto_Real_1.5_2026 (1).csv';
$reader = IOFactory::createReaderForFile($path);
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load($path);
$sheet = $spreadsheet->getSheetByName('Clientes');
if (!$sheet) {
    echo "missing\n";
    exit(1);
}

$highestColumnIndex = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());
$headers = [];
for ($col = 1; $col <= $highestColumnIndex; $col++) {
    $coord = PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '1';
    $value = $sheet->getCell($coord)->getValue();
    $value = trim((string) $value);
    $value = str_replace(["\r", "\n", "\t"], ' ', $value);
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/u', '_', $value) ?? $value;
    $value = trim($value, '_');
    $headers[$col] = $value;
}

$names = [];
for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
    $data = [];
    $empty = true;
    for ($col = 1; $col <= $highestColumnIndex; $col++) {
        $coord = PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row;
        $value = $sheet->getCell($coord)->getCalculatedValue();
        if ($value !== null && $value !== '') {
            $empty = false;
        }
        $data[$headers[$col] ?? ('col_'.$col)] = $value;
    }
    if (!$empty && trim((string)($data['cliente'] ?? '')) !== '') {
        $names[] = trim((string) $data['cliente']);
    }
}

echo json_encode($names, JSON_UNESCAPED_UNICODE);
