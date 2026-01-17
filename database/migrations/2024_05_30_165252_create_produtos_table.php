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
            $table->string('nome', 150);
            $table->text('descricao');
            $table->integer('quantidade')->default(0);
            $table->integer('estoque_minimo')->default(0);
            $table->decimal('preco_uni', 10, 2);
            $table->string('img')->nullable();
            $table->string('codigo_fabricante')->unique();
            $table->string('codigo_barras')->nullable()->unique();
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('fornecedor_id')->nullable();
            $table->index('nome');
            $table->index('codigo_barras');
            $table->foreign('fornecedor_id')
                ->references('id')
                ->on('fornecedores')
                ->onDelete('set null');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('produtos');
    }
};