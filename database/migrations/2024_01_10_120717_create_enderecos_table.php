<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enderecos', function (Blueprint $table) {
            $table->id();
            $table->string('cep', 9);
            $table->string('cidade', 50);
            $table->string('bairro', 50);
            $table->string('estado', 2);
            $table->string('rua', 100);
            $table->string('numero', 20);
            $table->string('ponto_referencia', 200)->nullable();
            $table->foreignId('pessoa_id')
                  ->constrained('pessoas')
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enderecos');
    }
};