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
        Schema::create('colaborador', function (Blueprint $table) {
            $table->bigIncrements('id_colaborador');

            $table->unsignedBigInteger('id_pessoa');
            $table->unsignedBigInteger('id_user');

            $table->string('chave_pix', 50)->unique();
            $table->string('conta_banco', 30);

            $table->foreign('id_user')->references('id')->on('users');
            $table->foreign('id_pessoa')->references('id_pessoa')->on('pessoa');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colaborador');
    }
};
