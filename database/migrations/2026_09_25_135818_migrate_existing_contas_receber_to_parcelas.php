<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('contas_receber')
            ->orderBy('id')
            ->eachById(function (object $conta) {
                $existe = DB::table('parcelas_contas_receber')
                    ->where('conta_receber_id', $conta->id)
                    ->exists();

                if ($existe) {
                    return;
                }

                DB::table('parcelas_contas_receber')->insert([
                    'conta_receber_id' => $conta->id,
                    'numero' => 1,
                    'valor' => $conta->valor_original,
                    'data_vencimento' => $conta->data_vencimento,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        // Migração de dados histórica.
        // A remoção das parcelas é responsabilidade da migration
        // que remove a tabela parcelas_contas_receber.
    }
};
