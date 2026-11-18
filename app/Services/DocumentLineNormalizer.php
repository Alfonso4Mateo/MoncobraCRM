<?php

namespace App\Services;

use Illuminate\Support\Str;

class DocumentLineNormalizer
{
    public const DECIMALS = 2;

    public function roundMoney(float|int|string|null $amount): float
    {
        return round((float) ($amount ?? 0), self::DECIMALS);
    }

    public function roundQuantity(float|int|string|null $quantity, bool $asInteger = false): float|int
    {
        $clean = max(0.0, (float) ($quantity ?? 0));

        return $asInteger
            ? (int) round($clean, 0)
            : round($clean, self::DECIMALS);
    }

    public function calculateUnitWithMargin(float $unitPrice, float $marginPercent = 0.0): float
    {
        $cleanPrice = max(0.0, $unitPrice);
        $cleanMargin = max(0.0, $marginPercent);

        return round($cleanPrice * (1 + ($cleanMargin / 100)), self::DECIMALS);
    }

    public function calculateLineTotal(
        float $quantity,
        float $unitPrice,
        float $marginPercent = 0.0,
        string $modoConsumo = 'cantidad',
        ?float $explicitTotal = null
    ): float {
        if ($modoConsumo === 'importe' && $explicitTotal !== null && $explicitTotal >= 0) {
            return $this->roundMoney(max(0.0, $explicitTotal));
        }

        $effectiveQuantity = $modoConsumo === 'importe' ? 1.0 : max(0.0, $quantity);
        $unitWithMargin = $this->calculateUnitWithMargin($unitPrice, $marginPercent);

        return $this->roundMoney($unitWithMargin * $effectiveQuantity);
    }

    /**
     * @param iterable<int, array<string, mixed>> $lines
     */
    public function sumLinesTotal(iterable $lines): float
    {
        $sum = 0.0;

        foreach ($lines as $line) {
            if (is_array($line)) {
                $sum += (float) ($line['total'] ?? 0);
            }
        }

        return $this->roundMoney($sum);
    }

    /**
     * @param array<string, mixed>|string $rawLines
     * @return array<int, array<string, mixed>>
     */
    public function filter(array|string $rawLines): array
    {
        $lines = is_string($rawLines) ? json_decode($rawLines, true) : $rawLines;
        $lines = is_array($lines) ? $lines : [];

        return collect($lines)
            ->filter(fn ($line) => is_array($line) && !empty(trim((string) ($line['descripcion'] ?? ''))))
            ->values()
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $lines
     * @return array<int, array<string, mixed>>
     */
    public function normalize(
        array $lines,
        bool $quantityAsInteger = false,
        bool $ensureUid = false,
        bool $autoNumberArticulo = false
    ): array {
        return collect($lines)
            ->values()
            ->map(function (array $line, int $index) use ($quantityAsInteger, $ensureUid, $autoNumberArticulo) {
                $modoConsumo = ($line['modo_consumo'] ?? 'cantidad') === 'importe' ? 'importe' : 'cantidad';
                $rawQuantity = $modoConsumo === 'importe' ? 1.0 : max(0.0, (float) ($line['cantidad'] ?? 0));
                $unitPrice = max(0.0, (float) ($line['precio_unitario'] ?? ($line['precio'] ?? 0)));
                $margin = max(0.0, (float) ($line['margen'] ?? 0));
                $normalizedQuantity = $modoConsumo === 'importe'
                    ? ($quantityAsInteger ? 1 : 1.0)
                    : $this->roundQuantity($rawQuantity, $quantityAsInteger);
                $normalizedUnitPrice = $this->roundMoney($unitPrice);
                $normalizedMargin = $this->roundMoney($margin);
                $normalizedPriceWithMargin = $this->calculateUnitWithMargin($normalizedUnitPrice, $normalizedMargin);
                $explicitTotal = ($modoConsumo === 'importe' && isset($line['total']) && is_numeric($line['total']))
                    ? (float) $line['total']
                    : null;
                $lineTotal = $this->calculateLineTotal(
                    (float) $normalizedQuantity,
                    $normalizedUnitPrice,
                    $normalizedMargin,
                    $modoConsumo,
                    $explicitTotal
                );
                $measure = trim((string) ($line['medida'] ?? ($line['unidad'] ?? '')));
                $measure = $measure !== '' ? $measure : null;
                $articuloRaw = trim((string) ($line['articulo'] ?? ''));
                $articuloFinal = ($articuloRaw === '' && $autoNumberArticulo) ? (string) ($index + 1) : $articuloRaw;
                $uidRaw = trim((string) ($line['uid'] ?? ''));
                $uidFinal = $uidRaw !== '' ? $uidRaw : ($ensureUid ? (string) Str::uuid() : null);

                return [
                    'articulo_id' => isset($line['articulo_id']) ? (int) $line['articulo_id'] : null,
                    'articulo' => $articuloFinal,
                    'descripcion' => trim((string) ($line['descripcion'] ?? '')),
                    'cantidad' => $normalizedQuantity,
                    'medida' => $measure,
                    'unidad' => $measure,
                    'precio_unitario' => $normalizedUnitPrice,
                    'margen' => $normalizedMargin,
                    'precio_con_margen' => $normalizedPriceWithMargin,
                    'total' => $lineTotal,
                    'uid' => $uidFinal,
                    'linea_id' => isset($line['linea_id']) && (int) $line['linea_id'] > 0 ? (int) $line['linea_id'] : null,
                    'pedido_id' => isset($line['pedido_id']) && (int) $line['pedido_id'] > 0 ? (int) $line['pedido_id'] : null,
                    'numero_pedido' => isset($line['numero_pedido']) && trim((string) $line['numero_pedido']) !== '' ? trim((string) $line['numero_pedido']) : null,
                    'modo_consumo' => $modoConsumo,
                    'repartos' => is_array($line['repartos'] ?? null) ? $line['repartos'] : null,
                ];
            })
            ->values()
            ->all();
    }
}
