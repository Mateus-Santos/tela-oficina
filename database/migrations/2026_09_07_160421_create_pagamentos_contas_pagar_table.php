<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagamentos_contas_pagar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_pagar_id')
                ->constrained('contas_pagar')
                ->cascadeOnDelete();
            $table->decimal('valor', 10, 2);
            $table->date('data_pagamento');
            $table->string('forma_pagamento');
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagamentos_contas_pagar');
    }
};
