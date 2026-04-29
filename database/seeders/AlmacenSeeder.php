<?php

namespace Database\Seeders;

use App\Models\Almacen;
use App\Models\Proyecto;
use Illuminate\Database\Seeder;

class AlmacenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $proyectos = Proyecto::query()->pluck('id', 'nombre');

        $almacenes = [
            ['proyecto' => 'Cádiz', 'nombre' => 'Central', 'descripcion' => 'Almacen principal de la sede de Cádiz.'],
            ['proyecto' => 'Cádiz', 'nombre' => 'Norte', 'descripcion' => 'Zona norte para consumibles y repuestos.'],
            ['proyecto' => 'Cádiz', 'nombre' => 'Sur', 'descripcion' => 'Almacen auxiliar para materiales voluminosos.'],
            ['proyecto' => 'Albacete', 'nombre' => 'Central', 'descripcion' => 'Plataforma central de Albacete.'],
            ['proyecto' => 'Albacete', 'nombre' => 'Norte', 'descripcion' => 'Area de herramientas y electricidad.'],
            ['proyecto' => 'Albacete', 'nombre' => 'Sur', 'descripcion' => 'Zona de consumibles y seguridad.'],
            ['proyecto' => 'Madrid', 'nombre' => 'Centro', 'descripcion' => 'Hub principal de Madrid.'],
            ['proyecto' => 'Madrid', 'nombre' => 'Norte', 'descripcion' => 'Reserva de repuestos y herramientas.'],
            ['proyecto' => 'Madrid', 'nombre' => 'Sur', 'descripcion' => 'Zona de materiales y lubricantes.'],
        ];

        foreach ($almacenes as $almacen) {
            $proyectoId = $proyectos->get($almacen['proyecto']);

            if (!$proyectoId) {
                continue;
            }

            Almacen::updateOrCreate(
                [
                    'proyecto_id' => $proyectoId,
                    'nombre' => $almacen['nombre'],
                ],
                [
                    'descripcion' => $almacen['descripcion'],
                ]
            );
        }
    }
}
