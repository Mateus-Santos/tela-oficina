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
        Schema::create('peca', function (Blueprint $table) {
            $table->bigIncrements('id_peca');
            $table->string('montadora', 50);
            $table->string('nome', 255);
            $table->string('ano', 4);
            $table->string('veiculos', 50);
            $table->string('motor', 50);
            $table->string('descricao_peca', 255);
            $table->string('marcas', 50);
            $table->string('departamentos', 100);
            $table->string('produtos', 100);
            $table->string('vulvula', 100);
            $table->integer('quantidade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peca');
    }
};
