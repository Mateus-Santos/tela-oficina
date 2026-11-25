<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compra_devolucoes', function (Blueprint $table): void {
            $table->string('modalidade', 24)->default('operacional');
            $table->string('situacao_financeira', 24)->default('nao_aplicavel');
            $table->decimal('valor_abatimento', 12, 2)->default(0);
            $table->char('assinatura_operacao', 64)->nullable();
        });
        Schema::table('compra_devolucao_itens', function (Blueprint $table): void {
            $table->string('modalidade', 24)->default('estoque');
            $table->decimal('valor_abatimento', 12, 2)->default(0);
        });
    }

    public function down(): void
    {
        // Nao descartar documentos financeiros e movimentos de estoque ja registrados.
        if (\Illuminate\Support\Facades\DB::table('compra_devolucoes')
            ->where('modalidade', 'documental')->exists()) {
            throw new RuntimeException('Rollback bloqueado: existem devolucoes documentais registradas.');
        }
        Schema::table('compra_devolucao_itens', function (Blueprint $table): void {
            $table->dropColumn(['modalidade', 'valor_abatimento']);
        });
        Schema::table('compra_devolucoes', function (Blueprint $table): void {
            $table->dropColumn([
                'modalidade', 'situacao_financeira', 'valor_abatimento', 'assinatura_operacao',
            ]);
        });
    }
};
