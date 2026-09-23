<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEpiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $epiId = $this->route('epi')?->id;

        return [
            'nombre' => 'required|string|max:255|unique:epis,nombre,' . $epiId,
            'descripcion' => 'nullable|string|max:1000',
            'categoria' => 'nullable|string|max:100',

            'puestos' => 'nullable|array',
            'puestos.*' => 'exists:puestos,id',

            'cantidades' => 'nullable|array',
            'cantidades.*' => 'integer|min:1',
        ];
    }
}