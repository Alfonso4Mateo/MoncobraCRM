<?php   

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Epi extends Model
{
    protected $fillable = ['nombre', 'descripcion', 'activo'];

    public function puestos() {
        return $this->belongsToMany(Puesto::class, 'epi_puesto');
    }
}