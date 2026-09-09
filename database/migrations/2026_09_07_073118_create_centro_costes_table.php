<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('centros_costes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique(); // Ej: "426"
            $table->string('descripcion', 255); // Ej: "Sum. Repuestos"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('centro_costes');
    }
};
