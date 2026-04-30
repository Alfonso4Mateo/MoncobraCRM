<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'apellido',
        'dni_nie',
        'email',
        'password',
        'role',
        'departamento',
        'tipo_personal',
        'camiseta',
        'pantalon',
        'calzado',
        'casco',
        'guantes',
        'descripcion',
        'telefono',
        'avatar',
        'activo',
        'ultimo_acceso',
        'dashboard_panel_order',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'ultimo_acceso' => 'datetime',
            'dashboard_panel_order' => 'array',
        ];
    }

    /**
     * Keep superadmins attached to all projects.
     */
    protected static function booted(): void
    {
        static::created(function (User $user) {
            $user->syncAllProjectsIfSuperadmin();
        });

        static::updated(function (User $user) {
            if ($user->wasChanged('role')) {
                $user->syncAllProjectsIfSuperadmin();
            }
        });
    }

    /**
     * Get the proyectos assigned to the user.
     */
    public function proyectos(): BelongsToMany
    {
        return $this->belongsToMany(Proyecto::class, 'proyecto_user')->withTimestamps();
    }

    /**
     * Backward-compatible accessor for the primary proyecto.
     */
    public function getProyectoAttribute(): ?Proyecto
    {
        return $this->proyectos->first();
    }

    /**
     * Sync all projects when user is superadmin.
     */
    public function syncAllProjectsIfSuperadmin(): void
    {
        if ($this->role !== 'superadmin') {
            return;
        }

        $projectIds = Proyecto::query()->pluck('id')->all();
        $this->proyectos()->sync($projectIds);
    }
}
