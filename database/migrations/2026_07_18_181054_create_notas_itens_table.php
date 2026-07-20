<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas_itens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nota_id');
            // Foreign Keys
            $table->foreign('nota_id')
                  ->references('id')
                  ->on('notas')
                  ->onDelete('cascade');
            $table->morphs('itemable');
            $table->string('descricao');
            $table->integer('quantidade')->default(1);
            $table->decimal('valor_unitario', 10, 2);
            $table->decimal('desconto', 10, 2)->default(0);
            $table->decimal('valor_total', 10, 2);
            $table->integer('garantia_dias')->nullable();
            $table->date('garantia_inicio')->nullable();
            $table->date('garantia_fim')->nullable();
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('notas_itens');
    }
};
