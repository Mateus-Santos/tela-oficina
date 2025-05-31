<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            $table->json('montadora');
            $table->string('nome', 150);
            $table->integer('ano');
            $table->json('veiculos');
            $table->string('motor', 50);
            $table->text('descricao');
            $table->json('marcas');
            $table->json('departamentos');
            $table->json('valvula');
            $table->integer('quantidade');
            $table->decimal('preco_uni', 10, 2);
            $table->string('img')->nullable();
            $table->string('codigo_fabricante')->unique();
            $table->timestamps();

            $table->index('nome');
            $table->index('ano');
        });
    }

    

    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};
