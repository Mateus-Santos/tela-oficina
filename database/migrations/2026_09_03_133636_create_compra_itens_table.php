<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compra_itens', function (Blueprint $table) {
            $table->id();

            $table->foreignId('compra_id')
                ->constrained('compras')
                ->cascadeOnDelete();

            $table->foreignId('produto_id')
                ->constrained('produtos')
                ->restrictOnDelete();

            $table->string('descricao');

            $table->decimal('quantidade', 12, 3);

            $table->decimal('quantidade_conferida', 12, 3)
                ->nullable();

            $table->decimal('valor_unitario', 12, 2);

            $table->decimal('desconto', 12, 2)
                ->default(0);

            $table->decimal('valor_total', 12, 2);

            $table->timestamps();

            $table->index('compra_id');
            $table->index('produto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compra_itens');
    }
};
