<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notas', function (Blueprint $table) {
            $table->unsignedInteger('km_proxima_troca_oleo')
                ->nullable()
                ->after('km');
        });
    }

    public function down(): void
    {
        Schema::table('notas', function (Blueprint $table) {
            $table->dropColumn('km_proxima_troca_oleo');
        });
    }
};
