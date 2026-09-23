<?php

namespace App\Models;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Activity;

class Proyecto extends Model
{
    use Auditable;
    use HasFactory;

    /**
     * Keep project membership in sync for superadmins.
     */
    protected static function booted(): void
    {
        static::created(function (Proyecto $proyecto) {
            $superadminIds = User::query()
                ->where('role', 'superadmin')
                ->pluck('id')
                ->all();

            if (!empty($superadminIds)) {
                $proyecto->usuarios()->syncWithoutDetaching($superadminIds);
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'localizacion',
        'imagen',
    ];

    /**
     * Get the users that belong to the proyecto.
     */
    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'proyecto_user')->withTimestamps();
    }

    /**
     * Get clientes linked to this proyecto.
     */
    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class, 'proyecto_id');
    }

    /**
     * Get albaranes linked to this proyecto.
     */
    public function albaranes(): HasMany
    {
        return $this->hasMany(AlbaranCliente::class, 'proyecto_id');
    }

    /**
     * Get presupuestos linked to this proyecto.
     */
    public function presupuestos(): HasMany
    {
        return $this->hasMany(Presupuesto::class, 'proyecto_id');
    }

    /**
     * Get inventario items linked to this proyecto.
     */
    public function inventarioItems(): HasMany
    {
        return $this->hasMany(Inventario::class, 'proyecto_id');
    }

    /**
     * Get pedidos clientes linked to this proyecto.
     */
    public function pedidosClientes(): HasMany
    {
        return $this->hasMany(PedidoCliente::class, 'proyecto_id');
    }

    /**
     * Get articulos linked to this proyecto.
     */
    public function articulos(): HasMany
    {
        return $this->hasMany(Articulo::class, 'proyecto_id');
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $nombre = trim((string) $this->getAttribute('nombre'));
        $localizacion = trim((string) $this->getAttribute('localizacion'));
        $referencia = $nombre !== '' ? 'Proyecto: ' . $nombre : '';

        if ($localizacion !== '') {
            $referencia .= ($referencia !== '' ? ' - ' : '') . $localizacion;
        }

        if ($referencia === '') {
            $referencia = 'Sin referencia (ID: ' . $this->getKey() . ')';
        }

        $activity->properties = $activity->properties->merge([
            'reference_name' => $referencia,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        if ($eventName === 'deleted' && !$activity->properties->get('old')) {
            $activity->properties = $activity->properties->put('old', $this->getAttributes());
        }
    }
}
