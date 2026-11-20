<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('parcelas_contas_pagar_socios_ajustes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_pagar_socio_id')->constrained('contas_pagar_socios', 'id', 's01_ajuste_conta_fk')->cascadeOnDelete();
            $table->foreignId('parcela_conta_pagar_socio_id')->constrained('parcelas_contas_pagar_socios', 'id', 's01_ajuste_parcela_fk')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('valor_anterior', 12, 2);
            $table->decimal('valor_novo', 12, 2);
            $table->date('vencimento_anterior');
            $table->date('vencimento_novo');
            $table->string('motivo', 500);
            $table->timestamps();
            $table->index(['conta_pagar_socio_id', 'created_at'], 'socio_ajustes_conta_data_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcelas_contas_pagar_socios_ajustes');
    }
};
