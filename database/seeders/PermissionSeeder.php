<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar caché de Spatie por seguridad antes de sembrar
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permisos = [

            // CLIENTES
            'clientes.view', 'clientes.manage', 'clientes.historial', 'clientes.export', 'clientes.delete',

            //PRESUPUESTOS
            'presupuestos.view', 'presupuestos.manage', 'presupuestos.download', 'presupuestos.delete',

            //PEDIDOS CLIENTE
            'pedidos.view', 'pedidos.manage', 'pedidos.download', 'pedidos.delete',

            //ALBARANES CLIENTE
            'albaranes.view', 'albaranes.manage', 'albaranes.download', 'albaranes.delete',
            
            // INVENTARIO
            'inventario.view', 'inventario.movimientos', 'inventario.admin',

            // DOCUMENTOS 
            'documentos.view', 'documentos.create', 'documentos.edit', 'documentos.download', 'documentos.delete',

            // PERSONAL
            'personal.view', 'personal.create', 'personal.acciones', 'personal.bulk', 'personal.medico', 'personal.tallas', 'personal.delete', 'personal.edit', 'personal.export',

            // CURSOS
            'cursos.view', 'cursos.plantilla', 'cursos.normas', 'cursos.create', 'cursos.edit', 'cursos.alertas', 'cursos.export', 'cursos.delete',
            
            // USUARIOS
            'users.view', 'users.manage', 'users.permissions',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }
    }
}