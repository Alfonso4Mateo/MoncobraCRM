<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Validation\ValidationException;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Resolve active proyecto from session and validate access.
     *
     * @throws ValidationException
     */
    protected function resolveActiveProyectoId(Request $request): int
    {
        $activeProyectoId = (int) $request->session()->get('active_proyecto_id');

        if ($activeProyectoId <= 0) {
            throw ValidationException::withMessages([
                'proyecto_id' => 'Selecciona un proyecto activo desde el selector lateral antes de crear registros.',
            ]);
        }

        $user = $request->user();

        if (!$user) {
            throw ValidationException::withMessages([
                'proyecto_id' => 'No se pudo validar el usuario autenticado.',
            ]);
        }

        $hasAccess = $user->role === 'superadmin'
            || $user->proyectos()->where('proyectos.id', $activeProyectoId)->exists();

        if (!$hasAccess) {
            throw ValidationException::withMessages([
                'proyecto_id' => 'No tienes acceso al proyecto activo seleccionado.',
            ]);
        }

        return $activeProyectoId;
    }

    /**
     * Resolve a proyecto for admin adjustment screens even when no active proyecto is selected.
     */
    protected function resolveProyectoForCorrelativo(Request $request): int
    {
        $activeProyectoId = (int) $request->session()->get('active_proyecto_id');
        $user = $request->user();

        if (!$user) {
            throw ValidationException::withMessages([
                'proyecto_id' => 'No se pudo validar el usuario autenticado.',
            ]);
        }

        if ($activeProyectoId > 0) {
            $hasAccess = $user->role === 'superadmin'
                || $user->proyectos()->where('proyectos.id', $activeProyectoId)->exists();

            if ($hasAccess) {
                return $activeProyectoId;
            }
        }

        $fallbackProyectoId = $user->role === 'superadmin'
            ? (int) Proyecto::query()->orderBy('id')->value('id')
            : (int) $user->proyectos()->orderBy('proyectos.id')->value('proyectos.id');

        if ($fallbackProyectoId > 0) {
            return $fallbackProyectoId;
        }

        throw ValidationException::withMessages([
            'proyecto_id' => 'Selecciona un proyecto activo desde el selector lateral antes de continuar.',
        ]);
    }
}
