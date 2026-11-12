<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEpiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:255|unique:epis,nombre',
            'descripcion' => 'nullable|string|max:1000',
            'categoria' => 'nullable|string|max:100',

            // Vinculación opcional con el módulo de Inventario
            'inventario_id' => 'nullable|integer|exists:inventario,id',
            'inventario_variante_id' => 'nullable|integer|exists:inventario_variantes,id',

            'puestos' => 'nullable|array',
            'puestos.*' => 'exists:puestos,id',

            'cantidades' => 'nullable|array',
            'cantidades.*' => 'integer|min:1',
        ];
    }
}