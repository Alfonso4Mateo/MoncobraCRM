<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aparatos_calibrables', function (Blueprint $table) {
            $table->string('certificado_pdf')->nullable()->after('certificado');
        });
    }

    public function down(): void
    {
        Schema::table('aparatos_calibrables', function (Blueprint $table) {
            $table->dropColumn('certificado_pdf');
        });
    }
};
