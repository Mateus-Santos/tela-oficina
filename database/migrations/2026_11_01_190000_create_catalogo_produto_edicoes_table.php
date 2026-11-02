<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('catalogo_produto_edicoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalogo_produto_id')->unique()
                ->constrained('catalogo_produtos')->cascadeOnDelete();
            $table->string('nome', 150)->nullable();
            $table->text('descricao')->nullable();
            $table->string('marca', 150)->nullable();
            $table->string('codigo_fabricante', 191)->nullable();
            $table->string('codigo_barras', 191)->nullable();
            $table->decimal('preco_venda', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_produto_edicoes');
    }
};
