<?php

return [
    
    'accepted' => 'El campo :attribute debe ser aceptado.',
    'array' => 'El campo :attribute debe ser un arreglo.',
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'date' => 'El campo :attribute no es una fecha válida.',
    'email' => 'El campo :attribute debe ser una dirección de correo válida.',
    'exists' => 'El :attribute seleccionado no es válido.',
    'file' => 'El campo :attribute debe ser un archivo válido.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'mimes' => 'El campo :attribute debe ser un archivo de tipo: :values.',
    'max' => [
        'numeric' => 'El campo :attribute no debe ser mayor que :max.',
        'file' => 'El archivo :attribute no debe pesar más de :max kilobytes.',
        'string' => 'El campo :attribute no debe tener más de :max caracteres.',
        'array' => 'El campo :attribute no debe tener más de :max elementos.',
    ],

    'min' => [
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'file' => 'El archivo :attribute debe pesar al menos :min kilobytes.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
        'array' => 'El campo :attribute debe tener al menos :min elementos.',
    ],

    'numeric' => 'El campo :attribute debe ser un número.',
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser una cadena de texto.',
    'unique' => 'El campo :attribute ya está en uso.',

    'attributes' => [
        'empresa_nombre' => 'nombre de la empresa',
        'cif_nif' => 'CIF / NIF',
        'direccion' => 'dirección',
        'localidad' => 'localidad',
        'codigo_postal' => 'código postal',
        'telefono' => 'teléfono',
        'email' => 'correo electrónico',
        'persona_contacto' => 'persona de contacto',
        'documento' => 'documento',
        'numero' => 'número',
        'fecha' => 'fecha',
        'cliente_id' => 'cliente',
        'titulo' => 'título',
        'ot' => 'OT',
        'archivo_pdf' => 'archivo PDF',
        'name' => 'nombre',
        'apellido' => 'apellido',
        'dni_nie' => 'DNI / NIE',
        'departamento' => 'departamento',
        'tipo_personal' => 'tipo de personal',
        'camiseta' => 'camiseta',
        'chaqueta' => 'chaqueta',
        'sudadera' => 'sudadera',
        'pantalon' => 'pantalón',
        'calzado' => 'calzado',
        'casco' => 'casco',
        'guantes' => 'guantes',
        'descripcion' => 'observaciones',
        'ultima_revision_medica' => 'última revisión médica',
        'proxima_revision_medica' => 'próxima revisión médica',
        'proyecto_ids' => 'proyectos',
        'proyecto_ids.*' => 'proyecto',
    ],

    'gt' => [
        'numeric' => 'El campo :attribute debe ser mayor que :value.',
        'file' => 'El archivo :attribute debe pesar más de :value kilobytes.',
        'string' => 'El campo :attribute debe tener más de :value caracteres.',
        'array' => 'El campo :attribute debe tener más de :value elementos.',
    ],

    'gte' => [
        'numeric' => 'El campo :attribute debe ser como mínimo :value.',
        'file' => 'El archivo :attribute debe pesar como mínimo :value kilobytes.',
        'string' => 'El campo :attribute debe tener como mínimo :value caracteres.',
        'array' => 'El campo :attribute debe tener como mínimo :value elementos.',
    ],

    'lt' => [
        'numeric' => 'El campo :attribute debe ser menor que :value.',
        'file' => 'El archivo :attribute debe pesar menos de :value kilobytes.',
        'string' => 'El campo :attribute debe tener menos de :value caracteres.',
        'array' => 'El campo :attribute debe tener menos de :value elementos.',
    ],

    'lte' => [
        'numeric' => 'El campo :attribute debe ser como máximo :value.',
        'file' => 'El archivo :attribute debe pesar como máximo :value kilobytes.',
        'string' => 'El campo :attribute debe tener como máximo :value caracteres.',
        'array' => 'El campo :attribute no debe tener más de :value elementos.',
    ],
];
