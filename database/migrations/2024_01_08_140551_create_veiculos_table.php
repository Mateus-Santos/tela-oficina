<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('veiculos', function (Blueprint $table) {
            $table->id();
            $table->year('ano');
            $table->string('modelo', 20);
            $table->timestamps();
            $table->unsignedBigInteger('montadora_id');
            $table->foreign('montadora_id')->references('id')->on('montadoras')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('veiculos');
    }
};
