<?php

namespace App\Policies;

use App\Models\Curso;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CursoPolicy
{
    use HandlesAuthorization;

    private function compartenSede(User $user, Curso $curso): bool
    {
        $proyectosAdmin = $user->proyectos->pluck('id')->toArray();
        $proyectosCurso = $curso->proyectos->pluck('id')->toArray();

        $coincidencias = array_intersect($proyectosAdmin, $proyectosCurso);

        return count($coincidencias) > 0;
    }

    public function view(User $user, Curso $curso): bool
    {
        return $this->compartenSede($user, $curso);
    }

    public function update(User $user, Curso $curso): bool
    {
        return $this->compartenSede($user, $curso);
    }

    public function delete(User $user, Curso $curso): bool
    {
        return $this->compartenSede($user, $curso);
    }
}