<?php

namespace Database\Seeders;

use App\Models\Curso;
use Illuminate\Database\Seeder;

class CursosSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = config('cursos.categories', []);

        foreach ($catalog as $categoria => $cursos) {
            foreach ($cursos as $nombre) {
                Curso::updateOrCreate(
                    ['nombre' => $nombre],
                    [
                        'categoria' => $categoria,
                        'descripcion' => null,
                    ]
                );
            }
        }
    }
}