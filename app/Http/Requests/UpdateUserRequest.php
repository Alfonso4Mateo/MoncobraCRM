<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($this->user?->id)],
            'password' => ['sometimes', 'min:8', 'confirmed'],
            'role' => ['sometimes', Rule::in(['user', 'admin', 'superadmin'])],
            'proyecto_id' => ['required_if:role,user', 'nullable', 'exists:proyectos,id'],
            'apellido' => ['nullable', 'string', 'max:255'],
            'dni_nie' => ['nullable', 'string', 'max:20', Rule::unique('users')->ignore($this->user?->id)],
            'departamento' => ['nullable', 'string', 'max:255'],
            'tipo_personal' => ['nullable', 'in:indefinido,temporal'],
            'camiseta' => ['nullable', 'string', 'max:20'],
            'chaqueta' => ['nullable', 'string', 'max:20'],
            'sudadera' => ['nullable', 'string', 'max:20'],
            'pantalon' => ['nullable', 'string', 'max:20'],
            'calzado' => ['nullable', 'string', 'max:20'],
            'casco' => ['nullable', 'string', 'max:20'],
            'guantes' => ['nullable', 'string', 'max:20'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'activo' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Este email ya está registrado.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'proyecto_id.required_if' => 'Los usuarios normales deben estar asignados a un proyecto.',
            'proyecto_id.exists' => 'El proyecto seleccionado no existe.',
        ];
    }
}
