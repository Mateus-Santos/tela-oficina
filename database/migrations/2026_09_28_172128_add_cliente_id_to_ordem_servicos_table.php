<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordem_servicos', function (Blueprint $table) {
            $table->foreignId('cliente_id')
                ->nullable()
                ->after('veiculo_cliente_id')
                ->constrained('clientes')
                ->nullOnDelete();
        });

        /*
         * Migração temporária das OS antigas.
         *
         * Enquanto veiculos_clientes.cliente_id ainda existe,
         * usamos esse vínculo legado para definir o responsável
         * histórico das ordens já cadastradas.
         */
        DB::table('ordem_servicos')
            ->whereNotNull('veiculo_cliente_id')
            ->orderBy('id')
            ->chunkById(200, function ($ordens) {
                foreach ($ordens as $ordem) {
                    $clienteId = DB::table(
                        'veiculos_clientes'
                    )
                        ->where(
                            'id',
                            $ordem->veiculo_cliente_id
                        )
                        ->value('cliente_id');

                    if (!$clienteId) {
                        continue;
                    }

                    DB::table('ordem_servicos')
                        ->where('id', $ordem->id)
                        ->update([
                            'cliente_id' => $clienteId,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('ordem_servicos', function (Blueprint $table) {
            $table->dropConstrainedForeignId(
                'cliente_id'
            );
        });
    }
};
