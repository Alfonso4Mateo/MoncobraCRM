<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEpiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('epis.gestion') ?? false;
    }

    public function rules(): array
    {
        $epi = $this->route('epi');
        $epiId = is_object($epi) ? $epi->id : $epi;

        return [
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('epis', 'nombre')->ignore($epiId),
            ],
            'descripcion' => 'nullable|string|max:1000',
            'categoria' => 'nullable|string|max:100',

            // Vinculación opcional con las 3 vías del módulo de Inventario
            'inventario_id' => 'nullable|integer|exists:inventario,id',
            'inventario_variante_id' => 'nullable|integer|exists:inventario_variantes,id',
            'inventario_articulo_id' => 'nullable|integer|exists:inventario_articulos,id',

            'puestos' => 'nullable|array',
            'puestos.*' => 'exists:puestos,id',

            'cantidades' => 'nullable|array',
            'cantidades.*' => 'integer|min:1',
        ];
    }
}