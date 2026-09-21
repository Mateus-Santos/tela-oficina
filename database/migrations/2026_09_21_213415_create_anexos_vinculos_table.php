<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anexos_vinculos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anexo_id')
                ->constrained('anexos')
                ->cascadeOnDelete();
            $table->string('vinculavel_type');
            $table->unsignedBigInteger('vinculavel_id');
            $table->string('tipo', 50)->default('outro');
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(
                ['vinculavel_type', 'vinculavel_id'],
                'anexos_vinculos_vinculavel_index'
            );
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anexos_vinculos');
    }
};
