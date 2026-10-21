<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'produto_aplicacoes',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('produto_id')
                    ->constrained('produtos')
                    ->cascadeOnDelete();

                $table->foreignId('veiculo_id')
                    ->nullable()
                    ->constrained('veiculos')
                    ->nullOnDelete();

                $table->foreignId(
                    'catalogo_produto_aplicacao_id'
                )
                    ->nullable()
                    ->constrained(
                        'catalogo_produto_aplicacoes'
                    )
                    ->nullOnDelete();

                $table->char(
                    'chave_aplicacao',
                    64
                );

                $table->string(
                    'mes_ano',
                    100
                )->nullable();

                $table->string(
                    'modelo',
                    191
                )->nullable();

                $table->string(
                    'motor',
                    191
                )->nullable();

                $table->string(
                    'comp',
                    100
                )->nullable();

                $table->string(
                    'valvulas',
                    100
                )->nullable();

                $table->string(
                    'cilindros',
                    100
                )->nullable();

                $table->string(
                    'combustivel',
                    191
                )->nullable();

                $table->text(
                    'complemento'
                )->nullable();

                $table->string(
                    'origem',
                    50
                )->default('manual');

                $table->string(
                    'status_resolucao',
                    20
                )->default('resolvida');

                $table->timestamps();

                $table->unique(
                    [
                        'produto_id',
                        'origem',
                        'chave_aplicacao',
                    ],
                    'uq_produto_aplicacoes_origem_chave'
                );

                $table->unique(
                    'catalogo_produto_aplicacao_id',
                    'uq_produto_aplicacoes_catalogo'
                );

                $table->index(
                    [
                        'produto_id',
                        'veiculo_id',
                    ],
                    'idx_produto_aplicacoes_produto_veiculo'
                );

                $table->index(
                    [
                        'origem',
                        'status_resolucao',
                    ],
                    'idx_produto_aplicacoes_origem_status'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'produto_aplicacoes'
        );
    }
};
