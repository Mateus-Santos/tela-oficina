<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('contas_receber_socios')) {
            Schema::create('contas_receber_socios', function (Blueprint $table) {
                $table->id();
                $table->foreignId('socio_id')->constrained('socios')->restrictOnDelete();
                $table->string('devedor', 180);
                $table->string('descricao');
                $table->string('origem', 30); // dinheiro | pix | cartao | transferencia | outros
                $table->string('identificacao_cartao', 120)->nullable();
                $table->decimal('valor', 12, 2);
                $table->date('data_operacao');
                $table->string('status', 30)->default('aberta'); // aberta | parcial | quitada
                $table->date('data_quitacao')->nullable();
                $table->text('observacoes')->nullable();
                $table->timestamps();
                $table->index(['socio_id', 'status'], 'cr_socios_socio_status_idx');
            });
        }

        if (!Schema::hasTable('parcelas_contas_receber_socios')) {
            Schema::create('parcelas_contas_receber_socios', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conta_receber_socio_id')
                    ->constrained('contas_receber_socios')->restrictOnDelete();
                $table->unsignedInteger('numero');
                $table->decimal('valor', 12, 2);
                $table->date('data_vencimento');
                $table->timestamps();
                $table->unique(['conta_receber_socio_id', 'numero'], 'pcr_socios_conta_num_unq');
                $table->index('data_vencimento', 'pcr_socios_vencimento_idx');
            });
        }

        if (!Schema::hasTable('recebimentos_contas_receber_socios')) {
            Schema::create('recebimentos_contas_receber_socios', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conta_receber_socio_id');
                $table->foreign('conta_receber_socio_id', 'rcr_socios_conta_fk')
                    ->references('id')->on('contas_receber_socios')->restrictOnDelete();
                $table->foreignId('parcela_conta_receber_socio_id');
                $table->foreign('parcela_conta_receber_socio_id', 'rcr_socios_parcela_fk')
                    ->references('id')->on('parcelas_contas_receber_socios')->restrictOnDelete();
                $table->decimal('valor', 12, 2);
                $table->date('data_recebimento');
                $table->string('meio_recebimento', 30); // dinheiro | pix | transferencia | deposito | outros
                $table->text('observacoes')->nullable();
                $table->timestamp('estornado_em')->nullable();
                $table->text('motivo_estorno')->nullable();
                $table->timestamps();
                $table->index(['conta_receber_socio_id', 'estornado_em'], 'rcr_socios_conta_estorno_idx');
            });
        } else {
            // Completa somente a tabela deixada parcial pela tentativa de 04/11.
            Schema::table('recebimentos_contas_receber_socios', function (Blueprint $table) {
                $table->foreign('conta_receber_socio_id', 'rcr_socios_conta_fk')
                    ->references('id')->on('contas_receber_socios')->restrictOnDelete();
                $table->foreign('parcela_conta_receber_socio_id', 'rcr_socios_parcela_fk')
                    ->references('id')->on('parcelas_contas_receber_socios')->restrictOnDelete();
                $table->index(['conta_receber_socio_id', 'estornado_em'], 'rcr_socios_conta_estorno_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('recebimentos_contas_receber_socios');
        Schema::dropIfExists('parcelas_contas_receber_socios');
        Schema::dropIfExists('contas_receber_socios');
    }
};
