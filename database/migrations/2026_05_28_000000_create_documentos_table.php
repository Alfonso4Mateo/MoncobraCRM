<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 50)->index();
            $table->string('numero_documento', 120)->nullable()->index();
            $table->date('fecha_documento')->nullable()->index();
            $table->string('ot', 120)->nullable()->index();
            $table->string('cliente', 191)->nullable()->index();
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('path');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};