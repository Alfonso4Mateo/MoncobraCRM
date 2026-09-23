<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
        public function up()
    {
        Schema::table('herramientas_planta', function (Blueprint $table) {
            $table->string('manual_pdf')->nullable()->after('imagen');
        });
    }

    public function down()
    {
        Schema::table('herramientas_planta', function (Blueprint $table) {
            $table->dropColumn('manual_pdf');
        });
    }
};
