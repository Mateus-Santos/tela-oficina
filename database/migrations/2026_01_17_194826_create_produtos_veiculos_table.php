<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('produtos_veiculos', function (Blueprint $table) {
            $table->id();

            // Campos obrigatórios da tabela pivô
            $table->unsignedBigInteger('produto_id');
            $table->unsignedBigInteger('veiculo_id');

            // Foreign Keys
            $table->foreign('produto_id')
                  ->references('id')
                  ->on('produtos')
                  ->onDelete('cascade');

            $table->foreign('veiculo_id')
                  ->references('id')
                  ->on('veiculos')
                  ->onDelete('cascade');

            $table->timestamps();
            $table->unique(['produto_id', 'veiculo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produtos_veiculos');
    }
};