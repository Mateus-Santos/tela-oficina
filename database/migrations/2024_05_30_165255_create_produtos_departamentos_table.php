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
        Schema::create('produtos_departamentos', function (Blueprint $table) {
            $table->string('nome');
            
            $table->unsignedBigInteger('produto_id');
            $table->unsignedBigInteger('departamento_id');

            $table->timestamps();

            $table->primary(['produto_id', 'departamento_id']);

            $table->foreign('produto_id')->references('id')->on('produtos')->onDelete('cascade');
            $table->foreign('departamento_id')->references('id')->on('departamentos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produtos_departamentos');
    }
};
