<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('veiculo', function (Blueprint $table) {
            $table->bigIncrements('id_veiculo');
            $table->string('placa')->unique();
            $table->string('ano', 4);
            $table->string('marca', 20);
            $table->string('cor', 15);
            $table->unsignedBigInteger('id_user');
            $table->foreign('id_user')->references('id')->on('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('veiculo');
    }
};
