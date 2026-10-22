<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalogo_montadoras', function (Blueprint $table) {
            $table->foreignId('montadora_id')
                ->nullable()
                ->after('catalogo_fonte_id')
                ->constrained('montadoras')
                ->nullOnDelete();
        });

        Schema::table('catalogo_veiculos', function (Blueprint $table) {
            $table->index(
                'catalogo_fonte_id',
                'idx_cat_veic_fonte'
            );

            $table->dropUnique('uq_catalogo_veiculos_fonte_chave');

            $table->foreignId('veiculo_id')
                ->nullable()
                ->after('catalogo_montadora_id')
                ->constrained('veiculos')
                ->nullOnDelete();

            $table->unique(
                ['catalogo_fonte_id', 'catalogo_montadora_id', 'chave_externa'],
                'uq_cat_veic_fonte_mont_chave'
            );
        });
    }

    public function down(): void
    {
        Schema::table('catalogo_veiculos', function (Blueprint $table) {
            $table->dropUnique('uq_cat_veic_fonte_mont_chave');
            $table->dropConstrainedForeignId('veiculo_id');
        });

        Schema::table('catalogo_montadoras', function (Blueprint $table) {
            $table->dropConstrainedForeignId('montadora_id');
        });

        // Não recriamos a antiga UNIQUE (fonte + chave_externa): a Sky reutiliza
        // alguns IDs de veículo entre montadoras. Recriá-la após a sincronização
        // poderia exigir apagar dados válidos durante um rollback.
    }
};
