<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal', function (Blueprint $table) {
            $table->date('ultima_revision_medica')->nullable()->after('descripcion');
            $table->date('proxima_revision_medica')->nullable()->after('ultima_revision_medica');
        });
    }

    public function down(): void
    {
        Schema::table('personal', function (Blueprint $table) {
            $table->dropColumn(['ultima_revision_medica', 'proxima_revision_medica']);
        });
    }
};
