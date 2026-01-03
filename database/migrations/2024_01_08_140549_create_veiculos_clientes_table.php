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
        Schema::create('veiculos_clientes', function (Blueprint $table) {
            $table->id();
            $table->string('placa')->unique();
            $table->year('ano');
            $table->string('cor', 4)->nullable();
            $table->unsignedBigInteger('id_cliente');
            $table->foreign('id_cliente')->references('id')->on('users');
            $table->unsignedBigInteger('id_veiculo');
            $table->foreign('id_veiculo')->references('id')->on('veiculos');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('veiculos_clientes');
    }
};
