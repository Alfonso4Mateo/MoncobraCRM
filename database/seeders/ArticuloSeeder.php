<?php

namespace Database\Seeders;

use App\Models\Articulo;
use App\Models\Proyecto;
use Illuminate\Database\Seeder;

class ArticuloSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $proyectos = Proyecto::query()->pluck('id', 'nombre');

        $catalogo = [
            'Cádiz' => ['prefijo' => 'CAD', 'base' => 110.0],
            'Albacete' => ['prefijo' => 'ALB', 'base' => 95.0],
            'Madrid' => ['prefijo' => 'MAD', 'base' => 130.0],
        ];

        foreach ($catalogo as $proyectoNombre => $config) {
            $proyectoId = $proyectos->get($proyectoNombre);

            if (!$proyectoId) {
                continue;
            }

            for ($indice = 1; $indice <= 20; $indice++) {
                $numeroReferencia = sprintf('ART-%s-%03d', $config['prefijo'], $indice);
                $cantidad = ($indice % 5) + 1;
                $precio = round($config['base'] + ($indice * 3.75), 2);
                $margen = 8 + ($indice % 5);
                $total = round($cantidad * $precio * (1 + ($margen / 100)), 2);

                Articulo::updateOrCreate(
                    [
                        'proyecto_id' => $proyectoId,
                        'numero_referencia' => $numeroReferencia,
                    ],
                    [
                        'descripcion' => "Articulo de muestra {$numeroReferencia}",
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precio,
                        'margen' => $margen,
                        'total' => $total,
                    ]
                );
            }
        }
    }
}
