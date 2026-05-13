<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('articulos') && Schema::hasColumn('articulos', 'facturado')) {
            DB::table('articulos')
                ->whereNull('facturado')
                ->update(['facturado' => false]);
        }
    }

    public function down(): void
    {
        // No se revierte el backfill porque false es el estado seguro por defecto.
    }
};