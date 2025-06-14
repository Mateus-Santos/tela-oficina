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
            $table->year('ano_modelo');
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
            $table->string('codigo_barras')->nullable()->unique();
            $table->string('localizacao')->nullable();
            $table->string('unidade_medida', 10)->default('UN');
            $table->boolean('status')->default(true);
            $table->integer('estoque_minimo')->default(0);
            $table->unsignedBigInteger('fornecedor_id')->nullable();
            $table->timestamps();

            $table->index('nome');
            $table->index('ano_modelo');
            $table->index('codigo_barras');

            $table->foreign('fornecedor_id')
                ->references('id')
                ->on('fornecedores')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('fornecedores');
        Schema::dropIfExists('produtos');
    }
};