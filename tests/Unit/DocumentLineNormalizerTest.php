<?php

namespace Tests\Unit;

use App\Services\DocumentLineNormalizer;
use PHPUnit\Framework\TestCase;

class DocumentLineNormalizerTest extends TestCase
{
    public function test_line_total_uses_rounded_unit_price_with_margin(): void
    {
        $normalizer = new DocumentLineNormalizer();

        $this->assertSame(37.02, $normalizer->calculateLineTotal(3, 10.01, 23.3));
    }

    public function test_normalize_and_sum_lines_share_the_same_rounding_rule(): void
    {
        $normalizer = new DocumentLineNormalizer();
        $lines = $normalizer->normalize([
            [
                'descripcion' => 'Linea A',
                'cantidad' => 3,
                'precio_unitario' => 10.01,
                'margen' => 23.3,
            ],
            [
                'descripcion' => 'Linea B',
                'cantidad' => 2,
                'precio_unitario' => 4.99,
                'margen' => 10,
            ],
        ]);

        $this->assertSame(37.02, $lines[0]['total']);
        $this->assertSame(10.98, $lines[1]['total']);
        $this->assertSame(48.0, $normalizer->sumLinesTotal($lines));
    }

    public function test_importe_mode_can_use_an_explicit_total(): void
    {
        $normalizer = new DocumentLineNormalizer();

        $this->assertSame(
            12.35,
            $normalizer->calculateLineTotal(99, 10, 50, 'importe', 12.345)
        );
    }
}
