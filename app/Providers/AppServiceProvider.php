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

        // 1. LA LLAVE MAESTRA (Soluciona el error 403 de Spatie)
        // Esto intercepta cualquier comprobación de permisos. Si eres superadmin, entras a todo.
        Gate::before(function (User $user, $ability) {
            // Como usas $user->role directamente en tu tabla, lo validamos así
            if ($user->role === 'superadmin') {
                return true;
            }
            return null; // Si no es superadmin, sigue con las comprobaciones normales
        });

        // NOTA IMPORTANTE: 
        // Hemos ELIMINADO los Gate::define de 'edit-user', 'delete-user', etc.
        // Laravel ahora enviará esas peticiones automáticamente a la clase UserPolicy 
        // que creamos, donde está programada la barrera de los proyectos.

        // 2. Gate aislado para proyectos (Si no tienes un ProyectoPolicy, está bien dejarlo aquí)
        Gate::define('manage-projects', function (User $user) {
            return $user->role === 'superadmin';
        });

        // 3. Personalización del correo de creación/restablecimiento de contraseña
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

