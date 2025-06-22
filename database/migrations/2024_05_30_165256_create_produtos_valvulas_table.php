<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('produtos_valvulas', function (Blueprint $table) {
            $table->string('nome');
            
            $table->unsignedBigInteger('produto_id');
            $table->unsignedBigInteger('valvula_id');

            $table->timestamps();

            $table->primary(['produto_id', 'valvula_id']);

            $table->foreign('produto_id')->references('id')->on('produtos')->onDelete('cascade');
            $table->foreign('valvula_id')->references('id')->on('departamentos')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produtos_valvulas');
    }
};
