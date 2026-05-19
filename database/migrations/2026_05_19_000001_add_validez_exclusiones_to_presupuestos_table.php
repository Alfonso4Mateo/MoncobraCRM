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
        Schema::table('presupuestos', function (Blueprint $table) {
            if (!Schema::hasColumn('presupuestos', 'validez_oferta')) {
                $table->string('validez_oferta')->nullable()->after('ot');
            }

            if (!Schema::hasColumn('presupuestos', 'exclusiones')) {
                $table->text('exclusiones')->nullable()->after('validez_oferta');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            if (Schema::hasColumn('presupuestos', 'exclusiones')) {
                $table->dropColumn('exclusiones');
            }

            if (Schema::hasColumn('presupuestos', 'validez_oferta')) {
                $table->dropColumn('validez_oferta');
            }
        });
    }
};
