<?php

namespace App\Services;

class DocumentLineNormalizer
{
    /**
     * Filtra solo las líneas que contienen una descripción válida.
     *
     * @param array<string, mixed>|string $rawLines
     * @return array<int, array<string, mixed>>
     */
    public function filter(array|string $rawLines): array
    {
        $lines = is_string($rawLines)
            ? json_decode($rawLines, true)
            : $rawLines;

        $lines = is_array($lines) ? $lines : [];

        return collect($lines)
            ->filter(fn ($line) => is_array($line) && !empty(trim((string) ($line['descripcion'] ?? ''))))
            ->values()
            ->all();
    }

    /**
     * Normaliza líneas de documentos con o sin cantidad entera.
     *
     * @param array<int, array<string, mixed>> $lines
     * @param bool $quantityAsInteger
     * @return array<int, array<string, mixed>>
     */
    public function normalize(array $lines, bool $quantityAsInteger = false): array
    {
        return collect($lines)
            ->map(function (array $line) use ($quantityAsInteger) {
                $quantity = max(0, (float) ($line['cantidad'] ?? 0));
                $unitPrice = max(0, (float) ($line['precio_unitario'] ?? ($line['precio'] ?? 0)));
                $margin = max(0, (float) ($line['margen'] ?? 0));

                $normalizedQuantity = $quantityAsInteger ? (int) max(0, round($quantity, 0)) : round($quantity, 2);
                $normalizedUnitPrice = round($unitPrice, 2);
                $normalizedMargin = round($margin, 2);

                // El margen se aplica en servidor para evitar divergencias entre formularios y PDFs.
                $priceWithMargin = $normalizedUnitPrice * (1 + ($normalizedMargin / 100));
                $normalizedPriceWithMargin = round($priceWithMargin, 2);
                $lineTotal = ($line['modo_consumo'] ?? 'cantidad') === 'importe'
                    && isset($line['total'])
                    && is_numeric($line['total'])
                    && (float) $line['total'] >= 0
                    ? round(max(0, (float) $line['total']), 2)
                    : round($normalizedPriceWithMargin * $normalizedQuantity, 2);

                $measure = trim((string) ($line['medida'] ?? ($line['unidad'] ?? '')));
                $measure = $measure !== '' ? $measure : null;

                return [
                    'articulo_id' => isset($line['articulo_id']) ? (int) $line['articulo_id'] : null,
                    'articulo' => trim((string) ($line['articulo'] ?? '')),
                    'descripcion' => trim((string) ($line['descripcion'] ?? '')),
                    'cantidad' => $normalizedQuantity,
                    'medida' => $measure,
                    'unidad' => $measure,
                    'precio_unitario' => $normalizedUnitPrice,
                    'margen' => $normalizedMargin,
                    'precio_con_margen' => $normalizedPriceWithMargin,
                    'total' => $lineTotal,
                    'uid' => isset($line['uid']) && trim((string) $line['uid']) !== '' ? trim((string) $line['uid']) : null,
                    'linea_id' => isset($line['linea_id']) && (int) $line['linea_id'] > 0 ? (int) $line['linea_id'] : null,
                    'pedido_id' => isset($line['pedido_id']) && (int) $line['pedido_id'] > 0 ? (int) $line['pedido_id'] : null,
                    'numero_pedido' => isset($line['numero_pedido']) && trim((string) $line['numero_pedido']) !== '' ? trim((string) $line['numero_pedido']) : null,
                    'modo_consumo' => ($line['modo_consumo'] ?? 'cantidad') === 'importe' ? 'importe' : 'cantidad',
                    'repartos' => is_array($line['repartos'] ?? null) ? $line['repartos'] : null,
                ];
            })
            ->values()
            ->all();
    }
}