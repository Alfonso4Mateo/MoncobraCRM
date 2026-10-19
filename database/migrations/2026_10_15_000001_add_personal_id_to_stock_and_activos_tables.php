<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'salidas_stock',
        'equipos_informaticos',
        'herramientas_planta',
        'aparatos_calibrables',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (!Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'personal_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('personal_id')
                    ->nullable()
                    ->constrained('personal')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'personal_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['personal_id']);
                $table->dropColumn('personal_id');
            });
        }
    }
};
