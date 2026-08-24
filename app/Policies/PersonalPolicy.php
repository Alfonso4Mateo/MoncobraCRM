<?php

namespace App\Policies;

use App\Models\Personal;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PersonalPolicy
{
    use HandlesAuthorization;

    /**
     * MÉTODOS DE APOYO (PRIVADOS)
     * -----------------------------------------------------------------
     * Comprueba si el gerente (User) y el trabajador (Personal) comparten 
     * al menos un proyecto (sede/delegación).
     */
    private function compartenSede(User $user, Personal $personal): bool
    {
        // Extraemos los IDs en arrays simples
        $proyectosAdmin = $user->proyectos->pluck('id')->toArray();
        $proyectosTrabajador = $personal->proyectos->pluck('id')->toArray();

        // Buscamos si hay cruce de sedes
        $coincidencias = array_intersect($proyectosAdmin, $proyectosTrabajador);

        return count($coincidencias) > 0;
    }

    /**
     * MÉTODOS DE AUTORIZACIÓN (Vinculados a PersonalController)
     * -----------------------------------------------------------------
     * Nota: El superadmin NO se comprueba aquí porque el Gate::before
     * de tu AppServiceProvider ya le da acceso absoluto antes de llegar.
     */

    // Se ejecuta al intentar ver el perfil detallado del trabajador
    public function view(User $user, Personal $personal): bool
    {
        return $this->compartenSede($user, $personal);
    }

    // Se ejecuta al intentar cargar la vista de edición o guardar datos
    public function update(User $user, Personal $personal): bool
    {
        return $this->compartenSede($user, $personal);
    }

    // Se ejecuta al intentar eliminar la ficha
    public function delete(User $user, Personal $personal): bool
    {
        return $this->compartenSede($user, $personal);
    }
}