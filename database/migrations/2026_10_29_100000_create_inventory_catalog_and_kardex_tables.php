<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario_articulos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->nullable()->constrained('proyectos')->nullOnDelete();
            $table->foreignId('clase_id')->nullable()->constrained('clases')->nullOnDelete();
            $table->string('codigo');
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('referencia_proveedor')->nullable();
            $table->boolean('tiene_variantes')->default(false);
            $table->json('tipos_atributos')->nullable();
            $table->unsignedInteger('stock_minimo_global')->default(0);
            $table->unsignedInteger('nivel_critico_global')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['proyecto_id', 'codigo']);
            $table->index(['proyecto_id', 'activo']);
        });

        if (!Schema::hasColumn('inventario_variantes', 'articulo_id')) {
            Schema::table('inventario_variantes', function (Blueprint $table) {
                $table->foreignId('articulo_id')->nullable()->after('id');
                $table->json('atributos')->nullable()->after('codigo');
                $table->string('codigo_barras')->nullable()->after('atributos');
                $table->boolean('activo')->default(true)->after('codigo_barras');
                $table->foreign('articulo_id')->references('id')->on('inventario_articulos')->nullOnDelete();
                $table->index(['articulo_id', 'activo']);
            });
        }

        Schema::create('inventario_stock_almacen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variante_id')->constrained('inventario_variantes')->cascadeOnDelete();
            $table->foreignId('almacen_id')->constrained('almacenes')->cascadeOnDelete();
            $table->unsignedInteger('cantidad')->default(0);
            $table->string('ubicacion_estanteria')->nullable();
            $table->timestamps();

            $table->unique(['variante_id', 'almacen_id']);
            $table->index(['almacen_id', 'cantidad']);
        });

        Schema::create('inventario_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->nullable()->constrained('proyectos')->nullOnDelete();
            $table->string('numero_documento');
            $table->enum('tipo', ['ENTRADA', 'SALIDA', 'TRASLADO', 'AJUSTE']);
            $table->foreignId('almacen_origen_id')->nullable()->constrained('almacenes')->nullOnDelete();
            $table->foreignId('almacen_destino_id')->nullable()->constrained('almacenes')->nullOnDelete();
            $table->foreignId('personal_id')->nullable()->constrained('personal')->nullOnDelete();
            $table->string('ot')->nullable();
            $table->text('motivo')->nullable();
            $table->text('observaciones')->nullable();
            $table->enum('estado', ['REGISTRADO', 'CANCELADO'])->default('REGISTRADO');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['proyecto_id', 'numero_documento']);
            $table->index(['proyecto_id', 'tipo', 'estado']);
            $table->index(['proyecto_id', 'created_at']);
        });

        Schema::create('inventario_movimiento_lineas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movimiento_id')->constrained('inventario_movimientos')->cascadeOnDelete();
            $table->foreignId('variante_id')->constrained('inventario_variantes')->restrictOnDelete();
            $table->unsignedInteger('cantidad');
            $table->unsignedInteger('saldo_anterior_origen')->nullable();
            $table->unsignedInteger('saldo_posterior_origen')->nullable();
            $table->unsignedInteger('saldo_anterior_destino')->nullable();
            $table->unsignedInteger('saldo_posterior_destino')->nullable();
            $table->timestamps();

            $table->index(['movimiento_id', 'variante_id']);
        });

        $this->migrateLegacyCatalog();
    }

    private function migrateLegacyCatalog(): void
    {
        $legacyVariants = DB::table('inventario_variantes')->orderBy('id')->get();

        foreach ($legacyVariants as $legacyVariant) {
            $articleId = DB::table('inventario_articulos')->insertGetId([
                'proyecto_id' => $legacyVariant->proyecto_id,
                'clase_id' => $legacyVariant->clase_id,
                'codigo' => $legacyVariant->codigo,
                'nombre' => $legacyVariant->nombre ?? $legacyVariant->descripcion,
                'descripcion' => $legacyVariant->descripcion,
                'referencia_proveedor' => $legacyVariant->referencia_proveedor,
                'tiene_variantes' => true,
                'stock_minimo_global' => $legacyVariant->stock_minimo ?? 0,
                'nivel_critico_global' => $legacyVariant->nivel_critico ?? 0,
                'activo' => true,
                'created_at' => $legacyVariant->created_at,
                'updated_at' => $legacyVariant->updated_at,
            ]);

            DB::table('inventario_variantes')
                ->where('id', $legacyVariant->id)
                ->update(['articulo_id' => $articleId]);
        }

        $legacyItems = DB::table('inventario')->orderBy('id')->get();
        foreach ($legacyItems as $item) {
            $variantId = $item->inventario_variante_id;

            if (!$variantId) {
                $articleId = DB::table('inventario_articulos')->insertGetId([
                    'proyecto_id' => $item->proyecto_id,
                    'clase_id' => $item->clase_id,
                    'codigo' => $item->codigo . '-MIG-' . $item->id,
                    'nombre' => $item->nombre ?: $item->descripcion,
                    'descripcion' => $item->descripcion,
                    'referencia_proveedor' => $item->referencia_proveedor,
                    'tiene_variantes' => false,
                    'stock_minimo_global' => $item->stock_minimo ?? 0,
                    'nivel_critico_global' => $item->nivel_critico ?? 0,
                    'activo' => true,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ]);

                $variantId = DB::table('inventario_variantes')->insertGetId([
                    'articulo_id' => $articleId,
                    'proyecto_id' => $item->proyecto_id,
                    'nombre' => $item->nombre ?: $item->descripcion,
                    'codigo' => $item->codigo . '-MIG-' . $item->id,
                    'descripcion' => $item->descripcion,
                    'referencia_proveedor' => $item->referencia_proveedor,
                    'clase_id' => $item->clase_id,
                    'activo' => true,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ]);
            }

            $warehouseId = DB::table('almacenes')
                ->where('proyecto_id', $item->proyecto_id)
                ->where('nombre', $item->almacen)
                ->value('id');

            if ($warehouseId) {
                $stock = DB::table('inventario_stock_almacen')
                    ->where('variante_id', $variantId)
                    ->where('almacen_id', $warehouseId)
                    ->first();

                if ($stock) {
                    DB::table('inventario_stock_almacen')
                        ->where('id', $stock->id)
                        ->update([
                            'cantidad' => (int) $stock->cantidad + max(0, (int) $item->stock_actual),
                            'updated_at' => now(),
                        ]);
                } else {
                    DB::table('inventario_stock_almacen')->insert([
                        'variante_id' => $variantId,
                        'almacen_id' => $warehouseId,
                        'cantidad' => max(0, (int) $item->stock_actual),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_movimiento_lineas');
        Schema::dropIfExists('inventario_movimientos');
        Schema::dropIfExists('inventario_stock_almacen');

        if (Schema::hasColumn('inventario_variantes', 'articulo_id')) {
            Schema::table('inventario_variantes', function (Blueprint $table) {
                $table->dropForeign(['articulo_id']);
                $table->dropIndex(['articulo_id', 'activo']);
                $table->dropColumn(['articulo_id', 'atributos', 'codigo_barras', 'activo']);
            });
        }

        Schema::dropIfExists('inventario_articulos');
    }
};
