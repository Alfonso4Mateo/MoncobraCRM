<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Puesto de Trabajo (distinto del modelo Puesto, que es el "Perfil Formativo").
 * Se usa para la vigilancia de la salud: cada puesto define cada cuántos
 * meses corresponde repetir el reconocimiento médico.
 */
class PuestoTrabajo extends Model
{
    protected $table = 'puestos_trabajo';

    protected $fillable = ['nombre', 'periodicidad_meses', 'activo'];

    protected $casts = [
        'periodicidad_meses' => 'integer',
        'activo' => 'boolean',
    ];

    public function personal()
    {
        return $this->hasMany(Personal::class, 'puesto_trabajo_id');
    }
}
