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
        Schema::create('produtos_motores', function (Blueprint $table) {
            $table->string('nome');
            
            $table->unsignedBigInteger('produto_id');
            $table->unsignedBigInteger('motor_id');

            $table->timestamps();

            $table->primary(['produto_id', 'motor_id']);

            $table->foreign('produto_id')->references('id')->on('produtos')->onDelete('cascade');
            $table->foreign('motor_id')->references('id')->on('motores')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produtos_motores');
    }
};
