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
        Schema::table('personal', function (Blueprint $table) { 
            $table->string('bolsa_fod')->nullable()->after('gafas');
        });
    }

    public function down(): void
    {
        Schema::table('personal', function (Blueprint $table) { 
            $table->dropColumn('bolsa_fod');
        });
    }
};