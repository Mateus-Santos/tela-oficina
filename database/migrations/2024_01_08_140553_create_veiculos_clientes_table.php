<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('veiculos_clientes', function (Blueprint $table) {
            $table->id();
            $table->string('placa')->unique();
            $table->year('ano');
            $table->string('cor', 20)->nullable();
            $table->unsignedBigInteger('cliente_id');
            $table->foreign('cliente_id')->references('id')->on('clientes');
            $table->unsignedBigInteger('veiculo_id');
            $table->foreign('veiculo_id')->references('id')->on('veiculos');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('veiculos_clientes');
    }
};
