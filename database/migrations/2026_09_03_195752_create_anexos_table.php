<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anexos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 50)->default('outro');
            $table->string('arquivo');
            $table->string('nome_original');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('tamanho');
            $table->morphs('anexavel');
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anexos');
    }
};
