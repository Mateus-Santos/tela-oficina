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
        Schema::create('ordem_servicos', function (Blueprint $table) {
            $table->id();
            $table->dateTime('data_abertura');
            $table->dateTime('data_fechamento')->nullable();
            $table->string('descricao');
            $table->string('status');
            $table->float('valor');
            $table->string('nivel');
            $table->unsignedBigInteger('setor_servico_id');
            $table->foreign('setor_servico_id')->references('id')->on('setor_servicos');
            $table->unsignedBigInteger('veiculo_cliente_id');
            $table->foreign('veiculo_cliente_id')->references('id')->on('veiculo_clientes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordem_servicos');
    }
};
