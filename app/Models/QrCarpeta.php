<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QrCarpeta extends Model
{
    use SoftDeletes;

    protected $table = 'qr_carpetas';
    protected $fillable = ['nombre', 'parent_id'];

    // Obtener la carpeta padre
    public function padre()
    {
        return $this->belongsTo(QrCarpeta::class, 'parent_id');
    }

    // Obtener el primer nivel de subcarpetas
    public function subcarpetas()
    {
        return $this->hasMany(QrCarpeta::class, 'parent_id');
    }

    // Obtener TODO el árbol de subcarpetas (recursivo)
    public function subcarpetasRecursivas()
    {
        return $this->subcarpetas()->with('subcarpetasRecursivas');
    }

    // Obtener los códigos QR que están dentro de esta carpeta
    public function qrs()
    {
        return $this->hasMany(EtiquetaQr::class, 'carpeta_id');
    }
}