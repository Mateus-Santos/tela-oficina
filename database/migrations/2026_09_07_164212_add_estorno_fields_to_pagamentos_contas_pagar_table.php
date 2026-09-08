<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagamentos_contas_pagar', function (Blueprint $table) {
            $table->timestamp('estornado_em')->nullable()->after('observacoes');
            $table->text('motivo_estorno')->nullable()->after('estornado_em');
        });
    }

    public function down(): void
    {
        Schema::table('pagamentos_contas_pagar', function (Blueprint $table) {
            $table->dropColumn([
                'estornado_em',
                'motivo_estorno',
            ]);
        });
    }
};
