<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parcelas_contas_pagar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_pagar_id')
                ->constrained('contas_pagar')
                ->cascadeOnDelete();
            $table->unsignedInteger('numero');
            $table->decimal('valor', 10, 2);
            $table->date('data_vencimento');
            $table->timestamps();

            $table->unique([
                'conta_pagar_id',
                'numero',
            ]);

            $table->index('data_vencimento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcelas_contas_pagar');
    }
};
