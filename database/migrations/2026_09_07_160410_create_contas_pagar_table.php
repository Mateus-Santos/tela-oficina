<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contas_pagar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fornecedor_id')
                ->nullable()
                ->constrained('fornecedores')
                ->nullOnDelete();
            $table->foreignId('nota_id')
                ->nullable()
                ->constrained('notas')
                ->nullOnDelete();
            $table->string('descricao');
            $table->decimal('valor', 10, 2);
            $table->date('data_emissao');
            $table->date('data_vencimento');
            $table->string('status')->default('aberta');
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contas_pagar');
    }
};
