<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permisos = [
            // CLIENTES
            'clientes.view', 'clientes.manage', 'clientes.historial', 'clientes.export', 'clientes.delete',

            // PRESUPUESTOS
            'presupuestos.view', 'presupuestos.manage', 'presupuestos.download', 'presupuestos.delete',

            // PEDIDOS CLIENTE
            'pedidos.view', 'pedidos.manage', 'pedidos.download', 'pedidos.delete',

            // ALBARANES CLIENTE
            'albaranes.view', 'albaranes.manage', 'albaranes.download', 'albaranes.delete',

            // INVENTARIO
            'inventario.view', 'inventario.manage', 'inventario.movimientos', 'inventario.admin',

            // DOCUMENTOS
            'documentos.view', 'documentos.create', 'documentos.edit', 'documentos.download', 'documentos.delete',

            // PERSONAL
            'personal.view', 'personal.create', 'personal.acciones', 'personal.bulk', 'personal.medico', 'personal.tallas', 'personal.delete', 'personal.edit', 'personal.export',

            // CURSOS Y EPIS (PRL)
            'cursos.view', 'cursos.plantilla', 'cursos.normas', 'cursos.create', 'cursos.edit', 'cursos.alertas', 'cursos.export', 'cursos.delete',
            'epis.gestion',

            // HERRAMIENTAS PLANTA
            'activos.manage',
            'herramientas.view', 'herramientas.manage', 'herramientas.movimientos', 'herramientas.eventos', 'herramientas.documentos', 'herramientas.delete',

            // EQUIPOS INFORMÁTICOS
            'equipos.view', 'equipos.manage', 'equipos.movimientos', 'equipos.eventos', 'equipos.delete',

            // APARATOS CALIBRABLES - METROLOGÍA
            'calibrables.view', 'calibrables.manage', 'calibrables.movimientos', 'calibrables.eventos', 'calibrables.delete',

            // ALERTAS Y CONTROL TOWER
            'alertas.view', 'alertas.config', 'alertas.enviar', 'alertas.delete',

            // USUARIOS
            'users.view', 'users.manage', 'users.permissions',

            // AUDITORÍA
            'audit.view', 'audit.export', 'audit.delete',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        // Mantener a todos los superadmins sincronizados con el catálogo completo
        $allPermissions = Permission::all();
        User::where('role', 'superadmin')->each(function (User $superadmin) use ($allPermissions) {
            $superadmin->syncPermissions($allPermissions);
        });

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}