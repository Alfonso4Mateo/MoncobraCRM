<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Añadir nombre_proveedor al catálogo maestro y variantes
        Schema::table('inventario_articulos', function (Blueprint $table) {
            if (!Schema::hasColumn('inventario_articulos', 'nombre_proveedor')) {
                $table->string('nombre_proveedor')->nullable()->after('referencia_proveedor');
            }
        });

        Schema::table('inventario_variantes', function (Blueprint $table) {
            if (!Schema::hasColumn('inventario_variantes', 'nombre_proveedor')) {
                $table->string('nombre_proveedor')->nullable()->after('referencia_proveedor');
            }
        });

        // 2. Tabla para almacenar los informes generados y reutilizarlos en el día
        Schema::create('inventario_informes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proyecto_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('solicitante_nombre'); // Ej: Rocío Ramírez
            $table->string('tipo_informe', 40);   // salidas, entradas, traslados, stock_actual
            $table->json('filtros')->nullable();  // desde, hasta, clase_id, q...
            $table->string('snapshot_hash', 64)->index(); // Huella de filtros + estado de stock
            $table->string('archivo_path');       // Ruta en storage/app/...
            $table->string('archivo_nombre');     // Nombre amigable del CSV
            $table->unsignedInteger('total_filas')->default(0);
            $table->json('datos_preview')->nullable(); // Guarda las filas para verlas en el navegador sin descargar
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_informes');

        Schema::table('inventario_variantes', function (Blueprint $table) {
            $table->dropColumn('nombre_proveedor');
        });

        Schema::table('inventario_articulos', function (Blueprint $table) {
            $table->dropColumn('nombre_proveedor');
        });
    }
};