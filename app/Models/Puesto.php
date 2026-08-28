<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Puesto extends Model
{
    // AÑADIMOS 'meses_revision_medica'
    protected $fillable = ['nombre', 'activo', 'meses_revision_medica'];

    public function cursos()
    {
        return $this->belongsToMany(Curso::class, 'curso_puesto')
                    ->withPivot('es_obligatorio')
                    ->withTimestamps();
    }

    public function personal()
    {
        return $this->belongsToMany(Personal::class, 'personal_puesto')
                    ->withTimestamps();
    }
}