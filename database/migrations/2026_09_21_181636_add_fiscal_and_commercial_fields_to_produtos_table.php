<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->string('ncm', 8)->nullable();
            $table->string('cest', 7)->nullable();
            $table->string('ex_tipi', 3)->nullable();
            $table->unsignedTinyInteger('origem_mercadoria')->nullable();
            $table->string('unidade_comercial', 6)->nullable();
            $table->string('unidade_tributavel', 6)->nullable();
            $table->decimal('fator_conversao', 12, 6)->nullable();
            $table->decimal('peso_liquido', 12, 3)->nullable();
            $table->decimal('peso_bruto', 12, 3)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->dropColumn([
                'ncm',
                'cest',
                'ex_tipi',
                'origem_mercadoria',
                'unidade_comercial',
                'unidade_tributavel',
                'fator_conversao',
                'peso_liquido',
                'peso_bruto',
            ]);
        });
    }
};
