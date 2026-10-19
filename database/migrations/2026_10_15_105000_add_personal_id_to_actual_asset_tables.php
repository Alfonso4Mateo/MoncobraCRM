<?php

use App\Models\AparatoCalibrable;
use App\Models\EquipoInformatico;
use App\Models\HerramientaPlanta;
use App\Models\SalidaStock;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            SalidaStock::make()->getTable(),
            EquipoInformatico::make()->getTable(),
            HerramientaPlanta::make()->getTable(),
            AparatoCalibrable::make()->getTable(),
        ];

        foreach (array_unique($tables) as $tableName) {
            if (!Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'personal_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('personal_id')
                    ->nullable()
                    ->constrained('personal')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        $tables = [
            SalidaStock::make()->getTable(),
            EquipoInformatico::make()->getTable(),
            HerramientaPlanta::make()->getTable(),
            AparatoCalibrable::make()->getTable(),
        ];

        foreach (array_unique($tables) as $tableName) {
            if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'personal_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropForeign(['personal_id']);
                $table->dropColumn('personal_id');
            });
        }
    }
};
