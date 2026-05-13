<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('articulos') && !Schema::hasColumn('articulos', 'medida')) {
            Schema::table('articulos', function (Blueprint $table) {
                $table->string('medida', 50)->nullable()->after('cantidad');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('articulos') && Schema::hasColumn('articulos', 'medida')) {
            Schema::table('articulos', function (Blueprint $table) {
                $table->dropColumn('medida');
            });
        }
    }
};
