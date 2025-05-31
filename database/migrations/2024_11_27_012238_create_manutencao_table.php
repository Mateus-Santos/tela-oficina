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
        Schema::create('manutencaos', function (Blueprint $table) {
            $table->id();
            $table->string('setor');
            $table->text('descricao');
            $table->float('valor');
            $table->string('nivel');
            $table->unsignedBigInteger('id_contrato_servico');
            $table->foreign('id_contrato_servico')->references('id')->on('contrato_servicos');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manutencao');
    }
};
