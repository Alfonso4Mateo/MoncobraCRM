<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\AlbaranCliente;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            $pivotes = DB::table('pedido_cliente_albaran_cliente')
                ->where(function ($query): void {
                    $query->whereNull('importe_imputado')
                        ->orWhere('importe_imputado', '<', 0.001);
                })
                ->get(['pedido_cliente_id', 'albaran_cliente_id']);

            foreach ($pivotes as $pivote) {
                $albaran = AlbaranCliente::findOrFail($pivote->albaran_cliente_id);

                DB::table('pedido_cliente_albaran_cliente')
                    ->where('pedido_cliente_id', $pivote->pedido_cliente_id)
                    ->where('albaran_cliente_id', $pivote->albaran_cliente_id)
                    ->update([
                        'importe_imputado' => $albaran->total,
                        'updated_at' => now(),
                    ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Migración de datos destructiva: no es posible revertirla sin perder información histórica.
    }
};
