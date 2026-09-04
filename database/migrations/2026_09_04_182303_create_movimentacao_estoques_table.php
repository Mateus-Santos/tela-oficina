<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimentacao_estoques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produto_id')->constrained('produtos')->restrictOnDelete();
            $table->enum('tipo', ['entrada', 'saida', 'ajuste']);
            $table->decimal('quantidade', 12, 3);
            $table->decimal('saldo_anterior', 12, 3);
            $table->decimal('saldo_posterior', 12, 3);
            $table->decimal('valor_unitario', 12, 2)->nullable();
            $table->string('origem_type')->nullable();
            $table->unsignedBigInteger('origem_id')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(['origem_type', 'origem_id']);
            $table->index('produto_id');
            $table->index('tipo');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimentacao_estoques');
    }
};
