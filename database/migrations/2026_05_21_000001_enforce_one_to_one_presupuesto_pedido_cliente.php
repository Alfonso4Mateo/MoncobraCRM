<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('pedidos_clientes')) {
            return;
        }

        $duplicados = DB::table('pedidos_clientes')
            ->whereNotNull('presupuesto_id')
            ->select('presupuesto_id', DB::raw('COUNT(*) as total'))
            ->groupBy('presupuesto_id')
            ->having('total', '>', 1)
            ->pluck('presupuesto_id');

        foreach ($duplicados as $presupuestoId) {
            $pedidos = DB::table('pedidos_clientes')
                ->where('presupuesto_id', $presupuestoId)
                ->orderBy('id')
                ->pluck('id');

            $pedidosAConservar = $pedidos->slice(0, 1)->all();
            $pedidosADesvincular = $pedidos->slice(1)->all();

            if ($pedidosADesvincular !== []) {
                DB::table('pedidos_clientes')
                    ->whereIn('id', $pedidosADesvincular)
                    ->update(['presupuesto_id' => null]);
            }

            if ($pedidosAConservar === []) {
                continue;
            }
        }

        Schema::table('pedidos_clientes', function (Blueprint $table) {
            $table->unique('presupuesto_id', 'pedidos_clientes_presupuesto_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('pedidos_clientes')) {
            return;
        }

        Schema::table('pedidos_clientes', function (Blueprint $table) {
            $table->dropUnique('pedidos_clientes_presupuesto_id_unique');
        });
    }
};