<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

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
            $table->double('preco_uni');
            $table->string('img')->nullable();
            $table->string('codigo_fabricante')->unique();
            $table->timestamps();
        });
    }

    

    public function down(): void
    {
        Schema::dropIfExists('peca');
    }
};
