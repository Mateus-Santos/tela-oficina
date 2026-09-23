<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagamentos_contas_pagar', function (Blueprint $table) {
            $table->foreignId('parcela_conta_pagar_id')
                ->nullable()
                ->after('conta_pagar_id')
                ->constrained('parcelas_contas_pagar')
                ->nullOnDelete();

            $table->index('parcela_conta_pagar_id');
        });
    }

    public function down(): void
    {
        Schema::table('pagamentos_contas_pagar', function (Blueprint $table) {
            $table->dropForeign(['parcela_conta_pagar_id']);
            $table->dropIndex(['parcela_conta_pagar_id']);
            $table->dropColumn('parcela_conta_pagar_id');
        });
    }
};
