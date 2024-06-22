<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('peca_venda', function (Blueprint $table) {
            $table->id();
            $table->integer('quantidade');
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peca_venda');
    }
};
