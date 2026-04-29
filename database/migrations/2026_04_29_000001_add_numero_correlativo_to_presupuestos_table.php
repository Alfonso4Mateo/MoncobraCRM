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
        Schema::table('presupuestos', function (Blueprint $table) {
            if (!Schema::hasColumn('presupuestos', 'numero_correlativo')) {
                $table->unsignedInteger('numero_correlativo')->nullable()->after('numero');
                $table->index(['proyecto_id', 'numero_correlativo']);
            }
        });

        DB::transaction(function () {
            $rows = DB::table('presupuestos')
                ->select('id', 'proyecto_id')
                ->orderBy('proyecto_id')
                ->orderBy('id')
                ->get();

            $counters = [];

            foreach ($rows as $row) {
                $projectKey = $row->proyecto_id ?? 0;
                $counters[$projectKey] = ($counters[$projectKey] ?? 0) + 1;

                DB::table('presupuestos')
                    ->where('id', $row->id)
                    ->update(['numero_correlativo' => $counters[$projectKey]]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            if (Schema::hasColumn('presupuestos', 'numero_correlativo')) {
                $table->dropIndex(['proyecto_id', 'numero_correlativo']);
                $table->dropColumn('numero_correlativo');
            }
        });
    }
};