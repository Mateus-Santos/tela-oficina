<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parcelas_contas_receber', function (Blueprint $table) {
            $table->id();

            $table->foreignId('conta_receber_id')
                ->constrained('contas_receber')
                ->cascadeOnDelete();

            $table->unsignedInteger('numero');
            $table->decimal('valor', 12, 2);
            $table->date('data_vencimento');
            $table->timestamps();

            $table->unique([
                'conta_receber_id',
                'numero',
            ]);

            $table->index('data_vencimento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcelas_contas_receber');
    }
};
