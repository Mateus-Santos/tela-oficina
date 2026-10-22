<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table
                ->string('codigo', 50)
                ->nullable()
                ->after('id');
        });

        DB::statement("
            UPDATE produtos
            SET codigo = CONCAT(
                'PRD-',
                LPAD(id, 8, '0')
            )
            WHERE codigo IS NULL
               OR codigo = ''
        ");

        Schema::table('produtos', function (Blueprint $table) {
            $table->unique(
                'codigo',
                'produtos_codigo_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->dropUnique(
                'produtos_codigo_unique'
            );

            $table->dropColumn('codigo');
        });
    }
};
