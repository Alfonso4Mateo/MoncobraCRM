<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Puesto extends Model
{
    protected $fillable = ['nombre', 'activo'];

    // Un puesto tiene unas normas (cursos)
    public function cursos()
    {
        return $this->belongsToMany(Curso::class, 'curso_puesto')
                    ->withPivot('es_obligatorio')
                    ->withTimestamps();
    }

    // Un puesto está ocupado por trabajadores
    public function personal()
    {
        return $this->belongsToMany(Personal::class, 'personal_puesto')
                    ->withTimestamps();
    }
}