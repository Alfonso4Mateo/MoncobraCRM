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
        Schema::dropIfExists('pedido_cliente_albaran_cliente');

        Schema::create('pedido_cliente_albaran_cliente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_cliente_id')
                ->constrained('pedidos_clientes')
                ->cascadeOnDelete();
            $table->foreignId('albaran_cliente_id')
                ->constrained('albaranes_clientes')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['pedido_cliente_id', 'albaran_cliente_id'], 'pedido_albaran_unique');
            $table->index('pedido_cliente_id', 'pedido_albaran_pedido_idx');
            $table->index('albaran_cliente_id', 'pedido_albaran_albaran_idx');
        });

        $pedidosPorNumero = DB::table('pedidos_clientes')
            ->select('id', 'numero_pedido')
            ->orderBy('id')
            ->get()
            ->keyBy(fn ($pedido) => trim((string) $pedido->numero_pedido));

        $albaranes = DB::table('albaranes_clientes')
            ->select('id', 'pedido_cliente', 'proyecto_id')
            ->orderBy('id')
            ->get();

        $rows = [];

        foreach ($albaranes as $albaran) {
            $pedidoNumero = trim((string) ($albaran->pedido_cliente ?? ''));
            if ($pedidoNumero === '' || !$pedidosPorNumero->has($pedidoNumero)) {
                continue;
            }

            $pedido = $pedidosPorNumero->get($pedidoNumero);

            $rows[] = [
                'pedido_cliente_id' => $pedido->id,
                'albaran_cliente_id' => $albaran->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $pedidosConAlbaranSingular = DB::table('pedidos_clientes')
            ->whereNotNull('albaran_id')
            ->where('albaran_id', '>', 0)
            ->select('id', 'albaran_id')
            ->get();

        foreach ($pedidosConAlbaranSingular as $pedido) {
            $rows[] = [
                'pedido_cliente_id' => $pedido->id,
                'albaran_cliente_id' => $pedido->albaran_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows !== []) {
            $uniqueRows = collect($rows)
                ->unique(fn (array $row) => $row['pedido_cliente_id'] . '-' . $row['albaran_cliente_id'])
                ->values()
                ->all();

            DB::table('pedido_cliente_albaran_cliente')->insert($uniqueRows);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_cliente_albaran_cliente');
    }
};