<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrato_servico', function (Blueprint $table) {
            $table->id();
            $table->datetime('data_abertura');
            $table->idatetimed('data_fechamento');
            $table->string('descricao');
            $table->string('status');
            $table->unsignedBigInteger('id_veiculo');
            $table->foreign('id_veiculo')->references('id_veiculo')->on('veiculo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrato_servico');
    }
};
