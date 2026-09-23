<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEpiRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El control de acceso real ya lo hace el middleware de permisos en la ruta.
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:255|unique:epis,nombre',
            'descripcion' => 'nullable|string|max:1000',
            'categoria' => 'nullable|string|max:100',

            'puestos' => 'nullable|array',
            'puestos.*' => 'exists:puestos,id',

            'cantidades' => 'nullable|array',
            'cantidades.*' => 'integer|min:1',
        ];
    }
}