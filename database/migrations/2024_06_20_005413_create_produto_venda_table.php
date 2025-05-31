<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produto_vendas', function (Blueprint $table) {
            $table->id();
            $table->integer('quantidade');
            $table->double('valor_uni');
            $table->double('valor_pagto')->nullable();
            $table->date('data_pagto')->nullable();
            $table->unsignedBigInteger('id_venda');
            $table->unsignedBigInteger('id_produto');
            $table->unsignedBigInteger('id_colaborador');
            $table->unsignedBigInteger('id_cliente');
            $table->foreign('id_produto')->references('id')->on('produtos');
            $table->foreign('id_venda')->references('id')->on('vendas');
            $table->foreign('id_colaborador')->references('id')->on('colaboradors');
            $table->foreign('id_cliente')->references('id')->on('clientes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produto_venda');
    }
};
