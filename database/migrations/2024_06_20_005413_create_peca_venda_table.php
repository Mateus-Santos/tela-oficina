<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peca_venda', function (Blueprint $table) {
            $table->id();
            $table->integer('quantidade');
            $table->double('valor_uni');
            $table->double('valor_pagto')->nullable();
            $table->date('data_pagto')->nullable();
            $table->unsignedBigInteger('id_venda');
            $table->unsignedBigInteger('id_peca');
            $table->unsignedBigInteger('id_colaborador');
            $table->unsignedBigInteger('id_cliente');
            $table->foreign('id_peca')->references('id_peca')->on('peca');
            $table->foreign('id_venda')->references('id')->on('venda');
            $table->foreign('id_colaborador')->references('id_colaborador')->on('colaborador');
            $table->foreign('id_cliente')->references('id_cliente')->on('cliente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peca_venda');
    }
};
