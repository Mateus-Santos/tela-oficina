<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compra_pre_conciliacoes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('compra_id')->unique()->constrained('compras')->restrictOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 24)->default('rascunho');
            $table->string('tratamento_proposto', 24);
            $table->decimal('valor_proposto', 12, 2);
            $table->string('referencia_documento', 100)->nullable();
            $table->text('justificativa');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compra_pre_conciliacoes');
    }
};
