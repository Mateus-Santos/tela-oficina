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
        Schema::create('trabalhos_realizados', function (Blueprint $table) {
            $table->bigIncrements('id_trabalhos_realizados');

            $table->unsignedBigInteger('id_colaborador');
            $table->unsignedBigInteger('id_manutencao');

            $table->foreign('id_colaborador')->references('id_colaborador')->on('colaborador');
            $table->foreign('id_manutencao')->references('id_manutencao')->on('manutencao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trabalhos_realizados');
    }
};
