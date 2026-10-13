<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ubicacion extends Model
{
    use HasFactory;

    protected $table = 'ubicaciones';

    protected $fillable = ['nombre', 'descripcion'];

    public function aparatosCalibrables()
    {
        return $this->hasMany(AparatoCalibrable::class, 'ubicacion_id');
    }

    public function equiposInformaticos()
    {
        return $this->hasMany(EquipoInformatico::class, 'ubicacion_id');
    }

    public function herramientasPlanta()
    {
        return $this->hasMany(HerramientaPlanta::class, 'ubicacion_id');
    }
}