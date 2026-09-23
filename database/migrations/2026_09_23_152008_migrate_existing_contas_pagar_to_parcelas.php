<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('contas_pagar')
            ->orderBy('id')
            ->eachById(function (object $conta) {
                $existe = DB::table('parcelas_contas_pagar')
                    ->where('conta_pagar_id', $conta->id)
                    ->where('numero', 1)
                    ->exists();

                if ($existe) {
                    return;
                }

                DB::table('parcelas_contas_pagar')->insert([
                    'conta_pagar_id' => $conta->id,
                    'numero' => 1,
                    'valor' => $conta->valor,
                    'data_vencimento' => $conta->data_vencimento,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        DB::table('parcelas_contas_pagar')
            ->where('numero', 1)
            ->whereNotNull('conta_pagar_id')
            ->delete();
    }
};
