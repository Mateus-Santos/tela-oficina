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
        Schema::create('pessoa', function (Blueprint $table) {
            $table->bigIncrements('id_pessoa');
            $table->string('email', 50)->unique();
            $table->string('cpf', 11)->unique();
            $table->string('nome', 150);
            $table->string('rg', 9);
            $table->string('endereco', 200);
            $table->date('data_nascimento', 9);
            $table->string('telefone_1', 15);
            $table->string('telefone_2', 15);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pessoa');
    }
};
