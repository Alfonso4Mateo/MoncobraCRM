<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;  

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        $isAdminOrSuperadmin = function (User $user): bool {
            return in_array($user->role, ['admin', 'superadmin']);
        };

        // Gate para gestionar usuarios (solo admin y superadmin)
        Gate::define('manage-users', $isAdminOrSuperadmin);

        // Gate para ver todos los usuarios
        Gate::define('view-users', $isAdminOrSuperadmin);

        // Gate para ver detalles de un usuario específico
        Gate::define('view-user', function (User $user, User $targetUser) {
            // Solo admin y superadmin pueden ver detalles de cualquier usuario
            return in_array($user->role, ['admin', 'superadmin']);
        });

        // Gate para editar un usuario específico
        Gate::define('edit-user', function (User $user, User $targetUser) {
            // Un superadmin puede editar a todos menos sí mismo
            if ($user->role === 'superadmin') {
                return $user->id !== $targetUser->id;
            }
            // Un admin puede editar a usuarios y otros admins
            if ($user->role === 'admin') {
                return in_array($targetUser->role, ['user', 'admin']) && $user->id !== $targetUser->id;
            }
            return false;
        });

        // Gate para eliminar un usuario
        Gate::define('delete-user', function (User $user, User $targetUser) {
            // Un superadmin puede eliminar cualquiera menos sí mismo
            if ($user->role === 'superadmin') {
                return $user->id !== $targetUser->id;
            }
            // Un admin solo puede eliminar usuarios regulares
            if ($user->role === 'admin') {
                return $targetUser->role === 'user' && $user->id !== $targetUser->id;
            }
            return false;
        });

        // Gate para cambiar el rol de un usuario
        Gate::define('change-user-role', function (User $user, User $targetUser) {
            // Un superadmin puede cambiar roles a cualquiera menos sí mismo
            if ($user->role === 'superadmin') {
                return $user->id !== $targetUser->id;
            }
            // Un admin puede cambiar roles de usuarios y otros admins (pero no superadmin)
            if ($user->role === 'admin') {
                return in_array($targetUser->role, ['user', 'admin']) && $user->id !== $targetUser->id;
            }
            return false;
        });

        // Gate para gestionar proyectos (solo superadmin)
        Gate::define('manage-projects', function (User $user) {
            return $user->role === 'superadmin';
        });

        // Personalización del correo de creación/restablecimiento de contraseña
        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            // Generamos la URL segura con el token y el email del trabajador
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject('¡Bienvenido al ERP Moncobra! Configura tu acceso')
                ->greeting('¡Hola, ' . $notifiable->name . '!')
                ->line('Se ha dado de alta tu perfil de trabajador en el sistema de Moncobra.')
                ->line('Para empezar a utilizar la plataforma, por favor configura tu contraseña de acceso haciendo clic en el siguiente botón:')
                ->action('Configurar mi contraseña', $url)
                ->line('Por motivos de seguridad, este enlace es de un solo uso y caducará en 60 minutos.')
                ->salutation('¡Un saludo, el equipo de Moncobra!');
        });
    }
}

