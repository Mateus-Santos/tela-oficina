<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contas_receber', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')
                ->nullable()
                ->constrained('clientes')
                ->restrictOnDelete();

            $table->foreignId('nota_id')
                ->nullable()
                ->constrained('notas')
                ->restrictOnDelete();

            $table->foreignId('categoria_financeira_id')
                ->nullable()
                ->constrained('categorias_financeiras')
                ->restrictOnDelete();

            $table->string('descricao');
            $table->decimal('valor_original', 12, 2);
            $table->decimal('desconto', 12, 2)->default(0);
            $table->decimal('juros', 12, 2)->default(0);
            $table->decimal('multa', 12, 2)->default(0);
            $table->date('data_emissao')->nullable();
            $table->date('data_vencimento');
            $table->date('data_quitacao')->nullable();
            $table->enum('status', [
                'aberta',
                'parcial',
                'quitada',
                'vencida',
                'cancelada'
            ])->default('aberta');
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->index('data_vencimento');
            $table->index('status');
            $table->index('cliente_id');
            $table->index('nota_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contas_receber');
    }
};
