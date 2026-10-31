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
            $table->decimal('quantidade', 12, 3)->default(0)->change();
            $table->decimal('estoque_minimo', 12, 3)->default(0)->change();
        });
    }

    public function down(): void
    {
        // Retornar a INT descartaria saldos fracionados de modo irreversível.
        if (DB::table('produtos')
            ->whereRaw('quantidade <> TRUNCATE(quantidade, 0)')
            ->orWhereRaw('estoque_minimo <> TRUNCATE(estoque_minimo, 0)')
            ->exists()) {
            throw new RuntimeException(
                'Rollback bloqueado: há quantidades fracionadas em produtos. Faça uma migração específica de dados.'
            );
        }

        Schema::table('produtos', function (Blueprint $table) {
            $table->integer('quantidade')->default(0)->change();
            $table->integer('estoque_minimo')->default(0)->change();
        });
    }
};
