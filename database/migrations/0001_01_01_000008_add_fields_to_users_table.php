<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['user', 'admin', 'superadmin'])->default('user')->after('password');
            $table->foreignId('proyecto_id')->nullable()->constrained('proyectos')->nullOnDelete()->after('role');
            
            // Nuevos campos personales y RRHH
            $table->string('apellido')->nullable()->after('name');
            $table->string('dni_nie', 20)->nullable()->after('apellido');
            $table->string('departamento')->nullable()->after('dni_nie');
            $table->enum('tipo_personal', ['indefinido', 'temporal'])->nullable()->after('departamento');
            
            // Rutas de documentos
            $table->string('ruta_dni')->nullable()->after('tipo_personal');
            $table->string('ruta_contrato')->nullable()->after('ruta_dni');

            // Tallas EPIs
            $table->string('camiseta', 20)->nullable()->after('ruta_contrato');
            $table->string('chaqueta', 20)->nullable()->after('camiseta');
            $table->string('sudadera', 20)->nullable()->after('chaqueta');
            $table->string('pantalon', 20)->nullable()->after('sudadera');
            $table->string('calzado', 20)->nullable()->after('pantalon');
            $table->string('casco', 20)->nullable()->after('calzado');
            $table->string('guantes', 20)->nullable()->after('casco');

            // Campos existentes (ajustados al nuevo orden)
            $table->text('descripcion')->nullable()->after('guantes');
            $table->string('telefono', 20)->nullable()->after('descripcion');
            $table->string('avatar')->nullable()->after('telefono');
            $table->boolean('activo')->default(true)->after('avatar');
            $table->timestamp('ultimo_acceso')->nullable()->after('activo');

            // Campos para el Dashboard que tenías en tu Modelo
            $table->json('dashboard_panel_order')->nullable()->after('ultimo_acceso');
            $table->integer('personal_alerta_dias')->nullable()->after('dashboard_panel_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'apellido', 'dni_nie', 'departamento', 'tipo_personal',
                'ruta_dni', 'ruta_contrato', 
                'camiseta', 'chaqueta', 'sudadera', 'pantalon', 'calzado', 'casco', 'guantes',
                'role', 'proyecto_id', 'descripcion', 'telefono', 'avatar', 'activo', 'ultimo_acceso',
                'dashboard_panel_order', 'personal_alerta_dias'
            ]);
        });
    }
};