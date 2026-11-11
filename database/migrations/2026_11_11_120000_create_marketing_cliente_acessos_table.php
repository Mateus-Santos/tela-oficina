<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_cliente_acessos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cliente_id')->unique()
                ->constrained('clientes')->cascadeOnDelete();
            $table->boolean('portal_inicio')->default(false);
            $table->boolean('solicitacoes')->default(false);
            $table->foreignId('atualizado_por')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_cliente_acessos');
    }
};
