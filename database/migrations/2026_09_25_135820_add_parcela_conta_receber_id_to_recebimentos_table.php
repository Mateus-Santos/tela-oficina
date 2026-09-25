<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recebimentos', function (Blueprint $table) {
            $table->foreignId('parcela_conta_receber_id')
                ->nullable()
                ->after('conta_receber_id')
                ->constrained('parcelas_contas_receber')
                ->nullOnDelete();

            $table->index('parcela_conta_receber_id');
        });
    }

    public function down(): void
    {
        Schema::table('recebimentos', function (Blueprint $table) {
            $table->dropForeign([
                'parcela_conta_receber_id',
            ]);

            $table->dropIndex([
                'parcela_conta_receber_id',
            ]);

            $table->dropColumn(
                'parcela_conta_receber_id'
            );
        });
    }
};
