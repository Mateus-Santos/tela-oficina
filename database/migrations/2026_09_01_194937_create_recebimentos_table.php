<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recebimentos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('conta_receber_id')
                ->constrained('contas_receber')
                ->restrictOnDelete();

            $table->foreignId('forma_pagamento_id')
                ->constrained('formas_pagamento')
                ->restrictOnDelete();

            $table->decimal('valor', 12, 2);
            $table->dateTime('data_pagamento');
            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->index('data_pagamento');
            $table->index('conta_receber_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recebimentos');
    }
};
