<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

class CsvImportService
{
    /**
     * @return array<int, array<string, string>>
     */
    public function read(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        if (!$path) {
            throw new RuntimeException('No se pudo leer el archivo CSV.');
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('No se pudo abrir el archivo CSV.');
        }

        try {
            $firstLine = fgets($handle);
            if ($firstLine === false) {
                return [];
            }

            $semicolonCount = substr_count($firstLine, ';');
            $commaCount = substr_count($firstLine, ',');
            $delimiter = $semicolonCount >= $commaCount ? ';' : ',';
            rewind($handle);

            $headers = fgetcsv($handle, 0, $delimiter);
            if ($headers === false) {
                return [];
            }

            $headers = array_map(function ($header): string {
                $header = $this->toUtf8((string) $header);
                $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;

                return Str::slug(trim($header), '_');
            }, $headers);

            $rows = [];
            while (($values = fgetcsv($handle, 0, $delimiter)) !== false) {
                $values = array_map(fn ($value): string => trim($this->toUtf8((string) $value)), $values);
                if (count(array_filter($values, static fn (string $value): bool => $value !== '')) === 0) {
                    continue;
                }

                $values = array_pad($values, count($headers), '');
                $rows[] = array_combine($headers, array_slice($values, 0, count($headers))) ?: [];
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    private function toUtf8(string $value): string
    {
        if (!mb_check_encoding($value, 'UTF-8')) {
            return mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
        }

        return $value;
    }
}
