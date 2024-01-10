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
        Schema::create('endereco', function (Blueprint $table) {
            $table->bigIncrements('id_endereco');
            $table->string('cep', 11);
            $table->string('cidade', 50);
            $table->string('bairro', 50);
            $table->string('estado', 50);
            $table->string('rua', 50);
            $table->string('endereco', 50);
            $table->integer('numero');
            $table->string('ponto_referencia', 100);

            $table->unsignedBigInteger('id_pessoa');
            $table->foreign('id_pessoa')->references('id_pessoa')->on('pessoa');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('endereco');
    }
};
