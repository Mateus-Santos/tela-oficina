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
        Schema::create('manutencao', function (Blueprint $table) {
            $table->bigIncrements('id_manutencao');
            $table->string('peca', 30);
            $table->string('descricao', 200);
            $table->float('valor');

            $table->unsignedBigInteger('id_cliente');
            $table->unsignedBigInteger('id_veiculo');

            $table->foreign('id_cliente')->references('id_cliente')->on('cliente');
            $table->foreign('id_veiculo')->references('id_veiculo')->on('veiculo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
