<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compra_itens', function (Blueprint $table): void {
            $table->decimal('quantidade_avariada', 12, 3)->default(0);
            $table->decimal('quantidade_aceita', 12, 3)->nullable();
            $table->text('observacao_divergencia')->nullable();
        });
        Schema::create('compra_conferencia_historicos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('compra_id')->constrained('compras')->restrictOnDelete();
            $table->foreignId('compra_item_id')->constrained('compra_itens')->restrictOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('quantidade_nf', 12, 3);
            $table->decimal('quantidade_recebida', 12, 3);
            $table->decimal('quantidade_avariada', 12, 3);
            $table->decimal('quantidade_aceita', 12, 3);
            $table->string('classificacao', 24);
            $table->text('observacao')->nullable();
            $table->timestamp('created_at');
            $table->index(['compra_id', 'created_at']);
            $table->index(['compra_item_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compra_conferencia_historicos');
        Schema::table('compra_itens', function (Blueprint $table): void {
            $table->dropColumn(['quantidade_avariada', 'quantidade_aceita', 'observacao_divergencia']);
        });
    }
};
