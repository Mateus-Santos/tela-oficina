<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compra_devolucoes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('compra_id')->constrained('compras')->restrictOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('operacao_uuid')->unique();
            $table->string('referencia_documento', 80);
            $table->char('chave_nfe', 44)->nullable()->unique();
            $table->date('data_documento');
            $table->text('motivo');
            $table->string('status', 24)->default('efetivada');
            $table->timestamps();
            $table->index(['compra_id', 'status']);
        });

        Schema::create('compra_devolucao_itens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('compra_devolucao_id')
                ->constrained('compra_devolucoes')->restrictOnDelete();
            $table->foreignId('compra_item_id')
                ->constrained('compra_itens')->restrictOnDelete();
            $table->foreignId('produto_id')->constrained('produtos')->restrictOnDelete();
            $table->decimal('quantidade', 12, 3);
            $table->foreignId('movimentacao_estoque_id')->nullable()
                ->unique()->constrained('movimentacao_estoques')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['compra_devolucao_id', 'compra_item_id'], 'uq_devolucao_item_compra');
            $table->index('compra_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compra_devolucao_itens');
        Schema::dropIfExists('compra_devolucoes');
    }
};
