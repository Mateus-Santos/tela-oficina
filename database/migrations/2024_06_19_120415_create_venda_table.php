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
        Schema::create('venda', function (Blueprint $table) {
            $table->id();
            $table->float('valor_total');
            $table->integer('quantidade');
            $table->double('valor_uni');
            $table->double('desconto');
            $table->double('juros');
            $table->double('valor_pagto')->nullable();
            $table->date('data_venda');
            $table->date('data_venc');
            $table->date('data_pagto');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venda');
    }
};
