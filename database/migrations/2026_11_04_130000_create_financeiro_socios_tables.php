<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('socios', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 150);
            $table->boolean('ativo')->default(true);
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });

        Schema::create('contas_pagar_socios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('socio_id')->constrained('socios')->restrictOnDelete();
            $table->string('descricao');
            $table->string('credor')->nullable();
            $table->string('categoria', 80)->default('Outros');
            $table->decimal('valor', 12, 2);
            $table->date('data_emissao');
            $table->string('status', 30)->default('aberta');
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->index(['socio_id', 'status']);
        });

        Schema::create('parcelas_contas_pagar_socios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_pagar_socio_id')
                ->constrained('contas_pagar_socios')->restrictOnDelete();
            $table->unsignedInteger('numero');
            $table->decimal('valor', 12, 2);
            $table->date('data_vencimento');
            $table->timestamps();
            $table->unique(['conta_pagar_socio_id', 'numero'], 'parc_socios_conta_num_unq');
            $table->index('data_vencimento');
        });

        Schema::create('pagamentos_contas_pagar_socios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_pagar_socio_id')
                ->constrained('contas_pagar_socios')->restrictOnDelete();
            $table->foreignId('parcela_conta_pagar_socio_id');
            $table->foreign('parcela_conta_pagar_socio_id', 'pag_socios_parcela_fk')
                ->references('id')->on('parcelas_contas_pagar_socios')->restrictOnDelete();
            $table->decimal('valor', 12, 2);
            $table->date('data_pagamento');
            $table->string('origem', 20); // pessoal | empresa
            $table->foreignId('forma_pagamento_id')->nullable()
                ->constrained('formas_pagamento')->restrictOnDelete();
            $table->text('observacoes')->nullable();
            $table->timestamp('estornado_em')->nullable();
            $table->text('motivo_estorno')->nullable();
            $table->timestamps();
            $table->index(['conta_pagar_socio_id', 'estornado_em'], 'pag_socios_conta_estorno_idx');
        });

        Schema::create('retiradas_socios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('socio_id')->constrained('socios')->restrictOnDelete();
            $table->foreignId('pagamento_conta_pagar_socio_id')->nullable()
                ->unique('ret_socios_pagamento_unq')
                ->constrained('pagamentos_contas_pagar_socios')->restrictOnDelete();
            $table->string('tipo', 30); // pagamento_divida | retirada_livre | remuneracao
            $table->decimal('valor', 12, 2);
            $table->date('data_retirada');
            $table->text('observacoes')->nullable();
            $table->timestamp('estornado_em')->nullable();
            $table->text('motivo_estorno')->nullable();
            $table->timestamps();
            $table->index(['socio_id', 'data_retirada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retiradas_socios');
        Schema::dropIfExists('pagamentos_contas_pagar_socios');
        Schema::dropIfExists('parcelas_contas_pagar_socios');
        Schema::dropIfExists('contas_pagar_socios');
        Schema::dropIfExists('socios');
    }
};
