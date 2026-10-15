<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('produto_vendas');
        Schema::dropIfExists('vendas');
    }

    public function down(): void
    {
        Schema::create('vendas', function (Blueprint $table) {
            $table->id();
            $table->integer('desconto');
            $table->integer('juros');
            $table->date('data_venda');
            $table->date('data_venc');
            $table->timestamps();
        });

        Schema::create('produto_vendas', function (Blueprint $table) {
            $table->id();
            $table->integer('quantidade');
            $table->double('valor_uni');
            $table->double('valor_pagto')->nullable();
            $table->date('data_pagto')->nullable();

            $table
                ->foreignId('id_venda')
                ->constrained('vendas');

            $table
                ->foreignId('produto_id')
                ->constrained('produtos');

            $table
                ->foreignId('id_colaborador')
                ->constrained('colaboradores');

            $table
                ->foreignId('id_cliente')
                ->constrained('clientes');

            $table->timestamps();
        });
    }
};
