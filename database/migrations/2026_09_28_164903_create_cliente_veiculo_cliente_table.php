<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_veiculo_cliente', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cliente_id')
                ->constrained('clientes')
                ->cascadeOnDelete();

            $table->foreignId('veiculo_cliente_id')
                ->constrained('veiculos_clientes')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique([
                'cliente_id',
                'veiculo_cliente_id',
            ]);
        });

        /*
         * Migra os vínculos atuais sem remover cliente_id.
         * Assim nenhum relacionamento existente é perdido.
         */
        DB::table('veiculos_clientes')
            ->whereNotNull('cliente_id')
            ->orderBy('id')
            ->chunkById(200, function ($veiculos) {
                $agora = now();

                $registros = $veiculos
                    ->map(fn ($veiculo) => [
                        'cliente_id' => $veiculo->cliente_id,
                        'veiculo_cliente_id' => $veiculo->id,
                        'created_at' => $agora,
                        'updated_at' => $agora,
                    ])
                    ->all();

                DB::table('cliente_veiculo_cliente')
                    ->insertOrIgnore($registros);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'cliente_veiculo_cliente'
        );
    }
};
