<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Documento extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'documentos';

    protected $fillable = [
        'tipo',
        'numero_documento',
        'fecha_documento',
        'ot',
        'cliente',
        'original_name',
        'stored_name',
        'path',
        'mime_type',
        'size',
        'meta',
        'user_id',
    ];

    protected $casts = [
        'fecha_documento' => 'date',
        'size' => 'integer',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}