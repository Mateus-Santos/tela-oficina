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
        Schema::create('colaborador_venda', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_venda');
            $table->unsignedBigInteger('id_colaborador');

            $table->foreign('id_colaborador')->references('id_colaborador')->on('colaborador');
            $table->foreign('id_venda')->references('id')->on('venda');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colaborador_venda');
    }
};
