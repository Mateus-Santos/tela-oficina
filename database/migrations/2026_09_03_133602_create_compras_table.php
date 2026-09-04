<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compras', function (Blueprint $table) {
            $table->id();

            $table->foreignId('fornecedor_id')
                ->constrained('fornecedores')
                ->restrictOnDelete();

            $table->string('numero_nf');

            $table->string('serie_nf')->nullable();

            $table->string('chave_nf', 44)
                ->nullable()
                ->unique();

            $table->date('data_emissao')->nullable();

            $table->date('data_entrada');

            $table->decimal('valor_produtos', 12, 2);

            $table->decimal('desconto', 12, 2)
                ->default(0);

            $table->decimal('frete', 12, 2)
                ->default(0);

            $table->decimal('outras_despesas', 12, 2)
                ->default(0);

            $table->decimal('valor_total', 12, 2)
                ->nullable();

            $table->enum('status', [
                'pendente',
                'conferindo',
                'aprovada',
                'cancelada',
            ])->default('pendente');

            $table->text('observacoes')->nullable();

            $table->timestamps();

            $table->index('numero_nf');
            $table->index('data_entrada');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
