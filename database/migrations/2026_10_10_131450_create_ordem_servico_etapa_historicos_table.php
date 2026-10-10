<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'ordem_servico_etapa_historicos',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId(
                    'ordem_servico_id'
                )
                    ->constrained('ordem_servicos')
                    ->cascadeOnDelete();

                $table->foreignId(
                    'etapa_origem_id'
                )
                    ->nullable()
                    ->constrained('etapas')
                    ->nullOnDelete();

                $table->foreignId(
                    'etapa_destino_id'
                )
                    ->nullable()
                    ->constrained('etapas')
                    ->nullOnDelete();

                $table->string(
                    'etapa_origem_nome',
                    100
                )->nullable();

                $table->string(
                    'etapa_destino_nome',
                    100
                );

                $table->foreignId(
                    'user_id'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->string(
                    'motivo',
                    255
                )->nullable();

                $table->timestamps();

                $table->index([
                    'ordem_servico_id',
                    'created_at',
                ]);

                $table->index([
                    'etapa_destino_id',
                    'created_at',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'ordem_servico_etapa_historicos'
        );
    }
};
