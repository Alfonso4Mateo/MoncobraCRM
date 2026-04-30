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
            $table->string('apellido')->nullable()->after('name');
            $table->string('dni_nie', 20)->nullable()->after('apellido');
            $table->string('departamento')->nullable()->after('dni_nie');
            $table->string('tipo_personal')->nullable()->after('departamento');
            $table->string('camiseta')->nullable()->after('tipo_personal');
            $table->string('pantalon')->nullable()->after('camiseta');
            $table->string('calzado')->nullable()->after('pantalon');
            $table->string('casco')->nullable()->after('calzado');
            $table->string('guantes')->nullable()->after('casco');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'apellido',
                'dni_nie',
                'departamento',
                'tipo_personal',
                'camiseta',
                'pantalon',
                'calzado',
                'casco',
                'guantes',
            ]);
        });
    }
};
