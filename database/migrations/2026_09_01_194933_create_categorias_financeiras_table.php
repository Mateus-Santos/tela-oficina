<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_financeiras', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->enum('tipo', ['entrada', 'saida']);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->unique(['nome', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias_financeiras');
    }
};
