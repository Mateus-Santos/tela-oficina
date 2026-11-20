<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compra_conciliacao_efetivacoes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('compra_id')->unique()->constrained('compras')->restrictOnDelete();
            $table->foreignId('compra_pre_conciliacao_id')->unique()
                ->constrained('compra_pre_conciliacoes')->restrictOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('operacao_uuid')->unique();
            $table->string('tratamento', 24);
            $table->string('status', 40);
            $table->decimal('valor', 12, 2);
            $table->foreignId('conta_pagar_id')->nullable()->constrained('contas_pagar')->restrictOnDelete();
            $table->decimal('valor_conta_antes', 15, 2)->nullable();
            $table->decimal('valor_conta_depois', 15, 2)->nullable();
            $table->string('documento_confirmacao', 100);
            $table->text('justificativa');
            $table->timestamps();
            $table->index(['tratamento', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compra_conciliacao_efetivacoes');
    }
};
