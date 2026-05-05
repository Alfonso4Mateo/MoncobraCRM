<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('personal')) {
            return;
        }

        $alreadyHasRows = DB::table('personal')->limit(1)->exists();
        if ($alreadyHasRows) {
            return;
        }

        $users = DB::table('users')
            ->select([
                'id',
                'name',
                'apellido',
                'dni_nie',
                'departamento',
                'tipo_personal',
                'telefono',
                'descripcion',
                'camiseta',
                'chaqueta',
                'sudadera',
                'pantalon',
                'calzado',
                'casco',
                'guantes',
                'activo',
                'created_at',
                'updated_at',
            ])
            ->orderBy('id')
            ->get();

        foreach ($users as $user) {
            DB::table('personal')->insert([
                'id' => $user->id,
                'name' => $user->name,
                'apellido' => $user->apellido,
                'dni_nie' => $user->dni_nie,
                'departamento' => $user->departamento,
                'tipo_personal' => $user->tipo_personal,
                'telefono' => $user->telefono,
                'descripcion' => $user->descripcion,
                'camiseta' => $user->camiseta,
                'chaqueta' => $user->chaqueta,
                'sudadera' => $user->sudadera,
                'pantalon' => $user->pantalon,
                'calzado' => $user->calzado,
                'casco' => $user->casco,
                'guantes' => $user->guantes,
                'activo' => $user->activo ?? true,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]);
        }

        if (Schema::hasTable('proyecto_user') && Schema::hasTable('personal_proyecto')) {
            $rows = DB::table('proyecto_user')
                ->select(['proyecto_id', 'user_id', 'created_at', 'updated_at'])
                ->get();

            foreach ($rows as $row) {
                DB::table('personal_proyecto')->insertOrIgnore([
                    'personal_id' => $row->user_id,
                    'proyecto_id' => $row->proyecto_id,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // No-op: data backfill is intentionally irreversible.
    }
};
