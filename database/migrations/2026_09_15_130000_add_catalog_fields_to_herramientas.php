<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('familias_herramientas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('descripcion')->nullable();
            $table->boolean('activo')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('herramientas', function (Blueprint $table) {
            $table->string('id_interno')->nullable()->unique()->after('id');
            $table->string('id_proveedor')->nullable()->after('proveedor');
            $table->string('almacen')->nullable()->after('ubicacion');
            $table->decimal('stock', 12, 3)->default(0)->after('almacen');
            $table->string('unidad_medida', 30)->default('ud')->after('stock');
            $table->date('fecha_registro')->nullable()->after('fecha_adquisicion');
            $table->string('ubicacion_2')->nullable()->after('ubicacion');
            $table->foreignId('familia_id')->nullable()->after('familia_energetica')->constrained('familias_herramientas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('herramientas', function (Blueprint $table) {
            $table->dropForeign(['familia_id']);
            $table->dropUnique(['id_interno']);
            $table->dropColumn(['id_interno', 'id_proveedor', 'almacen', 'stock', 'unidad_medida', 'fecha_registro', 'ubicacion_2', 'familia_id']);
        });

        Schema::dropIfExists('familias_herramientas');
    }
};
