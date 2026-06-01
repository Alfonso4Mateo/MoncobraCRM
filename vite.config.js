import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/js/app.js',
                'resources/css/login.css',
                'resources/css/register.css',
                'resources/css/reset-password.css',
                'resources/css/show_profile.css',
                'resources/css/usuarios-panel.css',
                'resources/css/usuarios-edit.css',
                'resources/css/usuarios-create.css',
                'resources/css/gestion-proyectos.css',
                'resources/css/proyecto-create.css',
                'resources/css/proyecto-edit.css',
                'resources/css/proyecto-show.css',
                'resources/css/presupuestos-index.css',
                'resources/css/presupuestos-create.css',
                'resources/css/documentos-create.css',
                'resources/css/clientes-index.css',
                'resources/css/clientes-create.css',
                'resources/css/clientes-edit.css',
                'resources/css/clientes-show.css',
                'resources/css/inventario-index.css',
                'resources/css/inventario-acciones.css',
                'resources/css/inventario-create.css',
                'resources/css/inventario-salida.css',
                'resources/css/inventario-traslado.css',
            ],
            refresh: true,
        }),
    ],
});
