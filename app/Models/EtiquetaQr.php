<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EtiquetaQr extends Model
{
    use SoftDeletes;

    protected $table = 'etiquetas_qr';
    protected $fillable = ['titulo', 'contenido_datos', 'carpeta_id', 'ruta_archivo', 'activo'];

    // Obtener la carpeta a la que pertenece este QR
    public function carpeta()
    {
        return $this->belongsTo(QrCarpeta::class, 'carpeta_id');
    }
}