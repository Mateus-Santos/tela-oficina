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
            $table->string('setor');
            $table->string('descricao', 200);
            $table->float('valor');
            $table->float('nivel');
            $table->unsignedBigInteger('id_contrato_servico');
            $table->foreign('id_contrato_servico')->references('id')->on('contrato_servico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manutencao');
    }
};
