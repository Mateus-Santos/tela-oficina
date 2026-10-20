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
            $table->dropUnique(
                'produtos_codigo_fabricante_unique'
            );

            $table->unique(
                [
                    'marca_id',
                    'codigo_fabricante',
                ],
                'produtos_marca_codigo_fabricante_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->dropUnique(
                'produtos_marca_codigo_fabricante_unique'
            );
        });

        $possuiDuplicados = DB::table('produtos')
            ->select('codigo_fabricante')
            ->whereNotNull('codigo_fabricante')
            ->groupBy('codigo_fabricante')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if (! $possuiDuplicados) {
            Schema::table('produtos', function (Blueprint $table) {
                $table->unique(
                    'codigo_fabricante',
                    'produtos_codigo_fabricante_unique'
                );
            });
        }
    }
};
