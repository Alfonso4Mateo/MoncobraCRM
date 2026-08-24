<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * MÉTODOS DE APOYO (PRIVADOS)
     * -----------------------------------------------------------------
     * Comprueba si el usuario autenticado y el usuario objetivo comparten 
     * al menos un proyecto (sede/delegación).
     */
    private function compartenSede(User $currentUser, User $targetUser): bool
    {
        // 1. Extraemos solo los IDs de los proyectos de ambos en formato Array
        $proyectosAdmin = $currentUser->proyectos->pluck('id')->toArray();
        $proyectosTrabajador = $targetUser->proyectos->pluck('id')->toArray();

        // 2. Comparamos los dos arrays. array_intersect devuelve los IDs que coinciden.
        $coincidencias = array_intersect($proyectosAdmin, $proyectosTrabajador);

        // 3. Si hay más de 0 coincidencias, significa que comparten sede (Devuelve true).
        return count($coincidencias) > 0;
    }


    /**
     * MÉTODOS DE AUTORIZACIÓN (Vinculados al UserController)
     * -----------------------------------------------------------------
     */

    // Se ejecuta con: $this->authorize('view-user', $user);
    public function viewUser(User $currentUser, User $targetUser): bool
    {
        // Un usuario siempre puede ver su propio perfil
        if ($currentUser->id === $targetUser->id) {
            return true;
        }

        // Si no es él mismo, comprobamos la sede
        return $this->compartenSede($currentUser, $targetUser);
    }

    // Se ejecuta con: $this->authorize('edit-user', $user);
    public function editUser(User $currentUser, User $targetUser): bool
    {
        // Un usuario siempre puede editar su propio perfil (sus datos básicos)
        if ($currentUser->id === $targetUser->id) {
            return true;
        }

        return $this->compartenSede($currentUser, $targetUser);
    }

    // Se ejecuta con: $this->authorize('delete-user', $user);
    public function deleteUser(User $currentUser, User $targetUser): bool
    {
        // Nadie puede eliminarse a sí mismo por seguridad
        if ($currentUser->id === $targetUser->id) {
            return false;
        }

        return $this->compartenSede($currentUser, $targetUser);
    }

    // Se ejecuta con: $this->authorize('change-user-role', $user);
    public function changeUserRole(User $currentUser, User $targetUser): bool
    {
        if ($currentUser->id === $targetUser->id) {
            return false; // Nadie puede cambiar su propio rol
        }

        return $this->compartenSede($currentUser, $targetUser);
    }
}